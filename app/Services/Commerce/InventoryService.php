<?php

namespace App\Services\Commerce;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function reserve(Collection $items): void
    {
        foreach ($items as $item) {
            /** @var Product $product */
            $product = $item['product'];

            if ($product->stock === null) {
                continue;
            }

            $quantity = (int) $item['quantity'];
            $available = (int) $product->stock - (int) $product->reserved_stock;

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => "Only {$available} of {$product->name} remain available.",
                ]);
            }

            $product->increment('reserved_stock', $quantity);
        }
    }

    public function finalize(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items->sortBy('product_id') as $item) {
            if (! $item->stock_reserved || ! $item->product_id) {
                continue;
            }

            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if (! $product || $product->stock === null) {
                throw ValidationException::withMessages([
                    'status' => "Inventory for {$item->name} is no longer available.",
                ]);
            }

            $quantity = (int) $item->quantity;
            if ($product->reserved_stock < $quantity || $product->stock < $quantity) {
                throw ValidationException::withMessages([
                    'status' => "There is not enough inventory to complete {$item->name}.",
                ]);
            }

            $product->decrement('reserved_stock', $quantity);
            $product->decrement('stock', $quantity);
            $item->forceFill(['stock_reserved' => false])->save();
        }
    }

    public function release(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items->sortBy('product_id') as $item) {
            if (! $item->stock_reserved || ! $item->product_id) {
                continue;
            }

            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if (! $product) {
                continue;
            }

            $product->decrement('reserved_stock', min((int) $product->reserved_stock, (int) $item->quantity));
            $item->forceFill(['stock_reserved' => false])->save();
        }
    }
}
