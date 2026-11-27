<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\Commerce\CartService;
use App\Services\Commerce\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CommerceController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly InventoryService $inventory,
    ) {}

    public function home(): Response
    {
        return Inertia::render('Storefront/Home', [
            'featuredProducts' => Product::query()->publiclyVisible()->where('is_featured', true)->orderBy('id')->limit(3)->get(),
            'services' => Product::query()->publiclyVisible()->where('type', 'service')->orderBy('price')->limit(3)->get(),
        ]);
    }

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'category' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'in:all,product,digital,service'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $query = Product::query()->publiclyVisible();

        if (! empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['type']) && $filters['type'] !== 'all') {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['search'])) {
            $search = addcslashes($filters['search'], '\\%_');
            $query->where(fn ($builder) => $builder
                ->where('name', 'like', "%{$search}%")
                ->orWhere('tagline', 'like', "%{$search}%"));
        }

        return Inertia::render('Storefront/Shop', [
            'products' => $query->orderByDesc('is_featured')->orderBy('name')->limit(100)->get(),
            'categories' => Product::query()->publiclyVisible()->distinct()->orderBy('category')->pluck('category'),
            'filters' => $filters,
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->is_active && $product->isApproved(), 404);

        return Inertia::render('Storefront/Product', [
            'product' => $product,
            'relatedProducts' => Product::query()
                ->publiclyVisible()
                ->where('id', '!=', $product->id)
                ->where('category', $product->category)
                ->limit(3)
                ->get(),
        ]);
    }

    public function cart(): Response
    {
        return Inertia::render('Storefront/Cart', $this->cartProps());
    }

    public function addToCart(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active && $product->isApproved(), 404);

        if ($product->stock !== null && $product->available_stock < 1) {
            return back()->with('error', "{$product->name} is currently out of stock.");
        }

        $validated = $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.config('commerce.cart.max_line_quantity', 99)],
        ]);
        $this->cart->add($product, (int) ($validated['quantity'] ?? 1));

        return back()->with('success', "{$product->name} added to your cart.");
    }

    public function updateCart(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.config('commerce.cart.max_line_quantity', 99)],
        ]);
        $this->cart->update($product, (int) $validated['quantity']);

        return back();
    }

    public function removeFromCart(Product $product): RedirectResponse
    {
        $this->cart->remove($product);

        return back();
    }

    public function checkout(): Response|RedirectResponse
    {
        if ($this->cart->summary()['count'] === 0) {
            return redirect()->route('shop')->with('info', 'Add something to your cart before checking out.');
        }

        $checkoutKey = session('elyxstudio_checkout_key') ?: (string) Str::uuid();
        session()->put('elyxstudio_checkout_key', $checkoutKey);

        return Inertia::render('Storefront/Checkout', [
            ...$this->cartProps(),
            'checkoutKey' => $checkoutKey,
        ]);
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'checkout_key' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email:rfc', 'max:190'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $sessionHash = hash('sha256', $request->session()->getId());

        $existingOrder = Order::query()
            ->where('checkout_key', $validated['checkout_key'])
            ->where('checkout_session_hash', $sessionHash)
            ->first();

        if ($existingOrder) {
            return redirect()->to($existingOrder->confirmationUrl());
        }

        if (session('elyxstudio_checkout_key') !== $validated['checkout_key']) {
            throw ValidationException::withMessages([
                'checkout_key' => 'This checkout session has expired. Please return to checkout and try again.',
            ]);
        }

        $summary = $this->cart->summary();

        if ($summary['count'] === 0) {
            return redirect()->route('shop')->with('info', 'Your cart is empty.');
        }

        $order = DB::transaction(function () use ($validated, $summary, $request, $sessionHash): Order {
            $cartItems = $summary['items'];
            $products = Product::query()
                ->publiclyVisible()
                ->whereIn('id', $cartItems->pluck('product.id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $lockedItems = $cartItems->map(function (array $item) use ($products): array {
                $product = $products->get($item['product']->id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'cart' => 'One of the items in your cart is no longer available.',
                    ]);
                }

                return [
                    'product' => $product,
                    'quantity' => (int) $item['quantity'],
                    'line_total' => $this->cart->lineTotalCents($product, (int) $item['quantity']) / 100,
                    'line_total_cents' => $this->cart->lineTotalCents($product, (int) $item['quantity']),
                ];
            });
            $this->inventory->reserve($lockedItems);
            $lockedSummary = $this->cart->summarize($lockedItems);

            $order = new Order;
            $order->forceFill([
                'user_id' => $request->user()?->id,
                'order_number' => 'ELYX-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'confirmation_token' => Str::random(48),
                'checkout_key' => $validated['checkout_key'],
                'checkout_session_hash' => $sessionHash,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'reservation_expires_at' => $lockedSummary['items']->contains(fn (array $item): bool => $item['product']->stock !== null)
                    ? now()->addMinutes((int) config('commerce.reservation_minutes', 60))
                    : null,
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_note' => $validated['customer_note'] ?? null,
                'subtotal' => $lockedSummary['subtotal'],
                'tax' => $lockedSummary['tax'],
                'total' => $lockedSummary['total'],
            ]);
            $order->save();

            foreach ($lockedSummary['items'] as $item) {
                $product = $item['product'];
                $orderItem = $order->items()->make([
                    'name' => $product->name,
                    'type' => $product->type,
                    'unit_price' => $product->price,
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]);
                $orderItem->forceFill([
                    'product_id' => $product->id,
                    'stock_reserved' => $product->stock !== null,
                ]);
                $orderItem->save();
            }

            return $order;
        });

        $this->cart->clear();

        return redirect()->to($order->confirmationUrl());
    }

    public function confirmation(Order $order): Response
    {
        $order->load('items');

        return Inertia::render('Storefront/Confirmation', [
            'order' => [
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'customer_email' => $order->customer_email,
                'total' => $order->total,
                'items' => $order->items->map(fn ($item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                ])->values(),
            ],
        ]);
    }

    /** @return array{items: mixed, count: int, subtotal: float, tax: float, total: float} */
    private function cartProps(): array
    {
        $summary = $this->cart->summary();

        return [
            'items' => $summary['items']->map(fn (array $item) => [
                'product' => $item['product'],
                'quantity' => $item['quantity'],
                'line_total' => $item['line_total'],
            ])->values(),
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
            'tax' => $summary['tax'],
            'total' => $summary['total'],
        ];
    }
}
