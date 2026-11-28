<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SellerController extends Controller
{
    public function index(Request $request): Response
    {
        $seller = $request->user();
        $products = Product::query()
            ->where('seller_id', $seller->id)
            ->latest()
            ->get();
        $products->each(fn (Product $product) => $product->makeVisible([
            'approval_status',
            'rejection_reason',
            'submitted_at',
        ]));

        $orders = Order::query()
            ->whereHas('items.product', fn ($query) => $query->where('seller_id', $seller->id))
            ->with(['items' => fn ($query) => $query
                ->whereHas('product', fn ($productQuery) => $productQuery->where('seller_id', $seller->id))
                ->select(['id', 'order_id', 'product_id', 'name', 'quantity', 'line_total'])])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'customer_name' => $order->customer_name,
                'created_at' => $order->created_at?->toIso8601String(),
                'items' => $order->items->map(fn ($item): array => [
                    'name' => $item->name,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                ])->values(),
            ])->values();

        return Inertia::render('Commerce/SellerDashboard', [
            'products' => $products,
            'orders' => $orders,
            'stats' => [
                'products' => $products->count(),
                'pending' => $products->whereIn('approval_status', ['draft', 'submitted'])->count(),
                'approved' => $products->where('approval_status', 'approved')->count(),
                'sales' => (float) $orders->where('payment_status', 'paid')->sum(fn (array $order): float => (float) collect($order['items'])->sum('line_total')),
            ],
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $seller = $request->user();
        $data = $this->productData($request);

        $product = new Product;
        $product->forceFill([
            ...$data,
            'seller_id' => $seller->id,
            'stock' => null,
            'is_featured' => false,
            'is_active' => false,
            'approval_status' => 'submitted',
            'rejection_reason' => null,
            'submitted_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return back()->with('success', "{$product->name} was submitted for admin review.");
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $seller = $request->user();
        abort_unless((int) $product->seller_id === (int) $seller->id, 404);
        abort_unless(in_array($product->approval_status, ['draft', 'rejected'], true), 422, 'Only draft or rejected products can be edited.');

        $data = $this->productData($request, $product);
        $product->forceFill([
            ...$data,
            'stock' => null,
            'is_featured' => false,
            'is_active' => false,
            'approval_status' => 'submitted',
            'rejection_reason' => null,
            'submitted_at' => now(),
            'approved_at' => null,
            'approved_by' => null,
        ])->save();

        return back()->with('success', "{$product->name} was resubmitted for review.");
    }

    public function submitProduct(Request $request, Product $product): RedirectResponse
    {
        $seller = $request->user();
        abort_unless((int) $product->seller_id === (int) $seller->id, 404);
        abort_unless(in_array($product->approval_status, ['draft', 'rejected'], true), 422, 'This product is already under review or approved.');

        $product->forceFill([
            'approval_status' => 'submitted',
            'rejection_reason' => null,
            'submitted_at' => now(),
            'is_active' => false,
        ])->save();

        return back()->with('success', "{$product->name} was submitted for admin review.");
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
        ]);

        if (array_key_exists('features', $validated)) {
            $features = is_string($validated['features'])
                ? preg_split('/\r?\n/', $validated['features'])
                : $validated['features'];

            if (! is_array($features)) {
                throw ValidationException::withMessages(['features' => 'Features must be listed one per line.']);
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

        $validated['features'] ??= $product?->features ?? [];

        return $validated;
    }
}
