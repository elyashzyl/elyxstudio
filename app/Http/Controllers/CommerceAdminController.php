<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Commerce\InventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CommerceAdminController extends Controller
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function index(Request $request): Response
    {
        $this->authorizeCommerce();

        $status = $request->string('status')->toString();
        $products = Product::query()
            ->with('seller:id,name,email')
            ->orderByDesc('is_active')
            ->orderByDesc('submitted_at')
            ->orderBy('name')
            ->limit(500)
            ->get();
        $products->each(fn (Product $product) => $product->makeVisible([
            'reserved_stock',
            'seller_id',
            'approval_status',
            'rejection_reason',
            'submitted_at',
            'approved_at',
            'approved_by',
        ]));

        return Inertia::render(
            $request->is('admin/commerce*') ? 'Commerce/AdminDashboard' : 'Teacher/Commerce',
            [
                'products' => $products,
                'sellers' => User::query()->where('role', 'seller')->orderBy('name')->get(['id', 'name', 'email']),
                'orders' => Order::query()
                    ->with('items')
                    ->when($status && $status !== 'all', fn ($query) => $query->where('status', $status))
                    ->latest()
                    ->limit(100)
                    ->get(),
                'filters' => ['status' => $status ?: 'all'],
                'basePath' => $request->is('admin/commerce*') ? '/admin/commerce' : '/teacher/commerce',
                'stats' => [
                    'products' => Product::query()->where('is_active', true)->where('approval_status', 'approved')->count(),
                    'pending_products' => Product::query()->whereIn('approval_status', ['draft', 'submitted'])->count(),
                    'pending_orders' => Order::query()->whereIn('status', ['pending', 'confirmed', 'in_progress'])->count(),
                    'reserved_stock' => Product::query()->sum('reserved_stock'),
                    'order_value' => (float) Order::query()->where('payment_status', 'paid')->whereNotIn('status', ['cancelled'])->sum('total'),
                ],
            ],
        );
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $this->authorizeCommerce();

        $data = $this->productData($request);
        $sellerId = $data['seller_id'] ?? null;
        unset($data['seller_id']);

        $product = new Product;
        $product->fill($data);
        $product->forceFill([
            'seller_id' => $sellerId,
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'rejection_reason' => null,
        ])->save();

        return back()->with('success', 'Product added to the ELYX catalog.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeCommerce();
        $data = $this->productData($request, $product);
        $sellerWasProvided = array_key_exists('seller_id', $data);
        $sellerId = $data['seller_id'] ?? null;
        unset($data['seller_id']);

        DB::transaction(function () use ($data, $sellerWasProvided, $sellerId, $product): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $stock = array_key_exists('stock', $data) ? $data['stock'] : $lockedProduct->stock;

            if ($stock !== null && $stock < $lockedProduct->reserved_stock) {
                throw ValidationException::withMessages([
                    'stock' => "Stock cannot be lower than the {$lockedProduct->reserved_stock} units already reserved.",
                ]);
            }

            if ($stock === null && $lockedProduct->reserved_stock > 0) {
                throw ValidationException::withMessages([
                    'stock' => 'A product with active reservations cannot be changed to unlimited stock.',
                ]);
            }

            $lockedProduct->update($data);
            if ($sellerWasProvided) {
                $lockedProduct->forceFill(['seller_id' => $sellerId])->save();
            }
        });

        return back()->with('success', "{$product->name} updated.");
    }

    public function approveProduct(Product $product): RedirectResponse
    {
        $this->authorizeCommerce();

        $product->forceFill([
            'approval_status' => 'approved',
            'rejection_reason' => null,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
            'is_active' => true,
        ])->save();

        return back()->with('success', "{$product->name} is now live in the storefront.");
    }

    public function rejectProduct(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeCommerce();

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $product->forceFill([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'approved_at' => null,
            'approved_by' => null,
            'is_active' => false,
        ])->save();

        return back()->with('success', "{$product->name} was returned to the seller with feedback.");
    }

    public function archiveProduct(Product $product): RedirectResponse
    {
        $this->authorizeCommerce();

        $product->forceFill(['is_active' => false])->save();

        return back()->with('success', "{$product->name} was archived from the storefront.");
    }

    public function updateOrderStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeCommerce();

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,confirmed,in_progress,completed,cancelled'],
        ]);

        DB::transaction(function () use ($order, $validated): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $nextStatus = $validated['status'];

            if ($lockedOrder->status === $nextStatus) {
                return;
            }

            $this->assertStatusTransition($lockedOrder, $nextStatus);
            $lockedOrder->load('items');

            if ($nextStatus === 'completed') {
                $this->inventory->finalize($lockedOrder);
            } elseif ($nextStatus === 'cancelled') {
                $this->inventory->release($lockedOrder);
            }

            $lockedOrder->forceFill([
                'status' => $nextStatus,
                'reservation_expires_at' => in_array($nextStatus, ['completed', 'cancelled'], true) ? null : $lockedOrder->reservation_expires_at,
            ])->save();
        });

        return back()->with('success', "Order {$order->order_number} marked as {$validated['status']}.");
    }

    public function updateOrderPaymentStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeCommerce();

        $validated = $request->validate([
            'payment_status' => ['required', 'string', 'in:unpaid,paid,failed,refunded'],
        ]);

        DB::transaction(function () use ($order, $validated): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $current = $lockedOrder->payment_status;
            $next = $validated['payment_status'];

            if ($current === $next) {
                return;
            }

            if ($lockedOrder->status === 'cancelled' && $next !== 'refunded' && $next !== 'failed') {
                throw ValidationException::withMessages([
                    'payment_status' => 'A cancelled order can only be marked as refunded or failed.',
                ]);
            }

            $allowed = [
                'unpaid' => ['paid', 'failed'],
                'paid' => ['refunded'],
                'failed' => [],
                'refunded' => [],
            ];

            if (! in_array($next, $allowed[$current] ?? [], true)) {
                throw ValidationException::withMessages([
                    'payment_status' => 'This payment status transition is not allowed.',
                ]);
            }

            if ($next === 'failed') {
                $lockedOrder->load('items');
                $this->inventory->release($lockedOrder);
                $lockedOrder->forceFill([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                    'reservation_expires_at' => null,
                ])->save();

                return;
            }

            $lockedOrder->forceFill([
                'payment_status' => $next,
                'reservation_expires_at' => $next === 'paid' ? null : $lockedOrder->reservation_expires_at,
            ])->save();
        });

        return back()->with('success', "Order {$order->order_number} payment status updated.");
    }

    private function authorizeCommerce(): void
    {
        abort_unless(Auth::user()?->isSuperadmin(), 403);
    }

    private function assertStatusTransition(Order $order, string $next): void
    {
        $allowed = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        if (! in_array($next, $allowed[$order->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "The order cannot move from {$order->status} to {$next}.",
            ]);
        }

        if (in_array($next, ['confirmed', 'in_progress', 'completed'], true) && $order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'status' => 'The order must have a verified paid status before it can move into fulfillment.',
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function productData(Request $request, ?Product $product = null): array
    {
        $optional = $product ? 'sometimes' : 'required';
        $validated = $request->validate([
            'name' => [$optional, 'string', 'max:160'],
            'slug' => [
                $optional,
                'string',
                'max:180',
                'alpha_dash',
                Rule::unique('products', 'slug')->ignore($product?->id),
            ],
            'type' => [$optional, 'string', 'in:product,digital,service'],
            'category' => [$optional, 'string', 'max:100'],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => [$optional, 'string', 'max:5000'],
            'features' => ['sometimes', 'nullable'],
            'image_url' => ['sometimes', 'nullable', 'url', 'max:1000'],
            'price' => [$optional, 'numeric', 'min:0', 'max:9999999999.99'],
            'compare_at_price' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'billing_period' => ['sometimes', 'nullable', 'string', 'max:50'],
            'stock' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:4294967295'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'seller_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'seller')),
            ],
        ]);

        if (array_key_exists('features', $validated)) {
            $features = $validated['features'];
            if (is_string($features)) {
                $features = preg_split('/\r?\n/', $features) ?: [];
            }

            if (! is_array($features)) {
                throw ValidationException::withMessages([
                    'features' => 'Features must be listed one per line.',
                ]);
            }

            $validated['features'] = collect($features)
                ->map(fn ($feature) => trim((string) $feature))
                ->filter()
                ->take(30)
                ->map(fn (string $feature) => mb_substr($feature, 0, 200))
                ->values()
                ->all();
        }

        $price = array_key_exists('price', $validated) ? $validated['price'] : $product?->price;
        $compareAtPrice = array_key_exists('compare_at_price', $validated)
            ? $validated['compare_at_price']
            : $product?->compare_at_price;

        if ($compareAtPrice !== null && $price !== null && (float) $compareAtPrice < (float) $price) {
            throw ValidationException::withMessages([
                'compare_at_price' => 'Compare-at price must be greater than or equal to the sale price.',
            ]);
        }

        if (! $product) {
            $validated['features'] ??= [];
            $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
            $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        }

        return $validated;
    }
}
