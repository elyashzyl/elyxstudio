<?php

namespace App\Services\Commerce;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartService
{
    private const SESSION_KEY = 'elyxstudio_cart';

    /** @return array<string, int> */
    public function quantities(): array
    {
        $quantities = session()->get(self::SESSION_KEY, []);
        $normalized = $this->normalizeQuantities(is_array($quantities) ? $quantities : []);

        if ($normalized !== $quantities) {
            session()->put(self::SESSION_KEY, $normalized);
        }

        return $normalized;
    }

    public function add(Product $product, int $quantity = 1): void
    {
        $quantities = $this->quantities();
        $id = (string) $product->id;
        $requested = min($this->lineLimit(), max(1, $quantity));
        $nextQuantity = min(
            $this->lineLimit(),
            ($quantities[$id] ?? 0) + $requested,
        );

        if ($product->stock !== null) {
            $nextQuantity = min($nextQuantity, max(0, (int) $product->available_stock));
        }

        if ($nextQuantity <= 0) {
            unset($quantities[$id]);
        } else {
            $quantities[$id] = $nextQuantity;
        }

        $this->invalidateCheckout();
        session()->put(self::SESSION_KEY, $this->normalizeQuantities($quantities));
    }

    public function update(Product $product, int $quantity): void
    {
        $quantities = $this->quantities();
        $id = (string) $product->id;

        if ($quantity <= 0) {
            unset($quantities[$id]);
        } else {
            $nextQuantity = min($this->lineLimit(), $quantity);

            if ($product->stock !== null) {
                $nextQuantity = min($nextQuantity, max(0, (int) $product->available_stock));
            }

            if ($nextQuantity <= 0) {
                unset($quantities[$id]);
            } else {
                $quantities[$id] = $nextQuantity;
            }
        }

        $this->invalidateCheckout();
        session()->put(self::SESSION_KEY, $this->normalizeQuantities($quantities));
    }

    public function remove(Product $product): void
    {
        $quantities = $this->quantities();
        unset($quantities[(string) $product->id]);
        $this->invalidateCheckout();
        session()->put(self::SESSION_KEY, $quantities);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
        session()->forget('elyxstudio_checkout_key');
    }

    private function invalidateCheckout(): void
    {
        session()->forget('elyxstudio_checkout_key');
    }

    /** @return Collection<int, array{product: Product, quantity: int, line_total: float, line_total_cents: int}> */
    public function items(): Collection
    {
        $quantities = $this->quantities();
        $products = Product::query()
            ->publiclyVisible()
            ->whereIn('id', array_keys($quantities))
            ->get()
            ->keyBy(fn (Product $product) => (string) $product->id);

        $normalizedQuantities = [];

        $items = collect($quantities)
            ->map(function (int $quantity, string $id) use ($products, &$normalizedQuantities): ?array {
                $product = $products->get($id);

                if (! $product) {
                    return null;
                }

                $availableQuantity = $product->stock === null
                    ? min($quantity, $this->lineLimit())
                    : min($quantity, max(0, (int) $product->available_stock));

                if ($availableQuantity <= 0) {
                    return null;
                }

                $normalizedQuantities[$id] = $availableQuantity;
                $lineTotalCents = $this->lineTotalCents($product, $availableQuantity);

                return [
                    'product' => $product,
                    'quantity' => $availableQuantity,
                    'line_total' => $lineTotalCents / 100,
                    'line_total_cents' => $lineTotalCents,
                ];
            })
            ->filter()
            ->values();

        $normalizedQuantities = $this->normalizeQuantities($normalizedQuantities);
        if ($normalizedQuantities !== $quantities) {
            session()->put(self::SESSION_KEY, $normalizedQuantities);
        }

        return $items;
    }

    public function lineTotalCents(Product $product, int $quantity): int
    {
        return $this->priceToCents((string) $product->price) * max(0, $quantity);
    }

    /** @param Collection<int, array{product: Product, quantity: int, line_total: float, line_total_cents?: int}> $items */
    public function summarize(Collection $items): array
    {
        $subtotalCents = (int) $items->sum(
            fn (array $item): int => $item['line_total_cents'] ?? $this->priceToCents((string) $item['line_total']),
        );
        $taxCents = 0;

        return [
            'items' => $items,
            'count' => (int) $items->sum('quantity'),
            'subtotal' => $subtotalCents / 100,
            'tax' => $taxCents / 100,
            'total' => ($subtotalCents + $taxCents) / 100,
        ];
    }

    /** @return array{items: Collection, count: int, subtotal: float, tax: float, total: float} */
    public function summary(): array
    {
        return $this->summarize($this->items());
    }

    /** @param array<mixed, mixed> $quantities */
    private function normalizeQuantities(array $quantities): array
    {
        $normalized = [];
        $total = 0;

        foreach ($quantities as $id => $quantity) {
            if (! is_string($id) && ! is_int($id)) {
                continue;
            }

            $id = (string) $id;
            if (! ctype_digit($id) || (int) $id < 1 || ! is_numeric($quantity)) {
                continue;
            }

            $quantity = (int) $quantity;
            if ($quantity < 1) {
                continue;
            }

            $quantity = min($quantity, $this->lineLimit(), $this->totalLimit() - $total);
            if ($quantity < 1) {
                break;
            }

            $normalized[$id] = $quantity;
            $total += $quantity;

            if ($total >= $this->totalLimit()) {
                break;
            }
        }

        return $normalized;
    }

    private function lineLimit(): int
    {
        return max(1, (int) config('commerce.cart.max_line_quantity', 99));
    }

    private function totalLimit(): int
    {
        return max($this->lineLimit(), (int) config('commerce.cart.max_total_quantity', 99));
    }

    private function priceToCents(string $price): int
    {
        [$whole, $fraction] = array_pad(explode('.', $price, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }
}
