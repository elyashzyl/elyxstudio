<?php

use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

function commerceProduct(array $attributes = []): Product
{
    return Product::create(array_merge([
        'name' => 'Nebula Brand Kit',
        'slug' => 'nebula-brand-kit',
        'type' => 'digital',
        'category' => 'Digital assets',
        'tagline' => 'A launch-ready visual identity.',
        'description' => 'A complete editable brand system.',
        'features' => ['Logo suite', 'Color system'],
        'price' => 149,
        'stock' => null,
        'is_featured' => true,
        'is_active' => true,
    ], $attributes));
}

test('the public storefront exposes the catalog and slug-based product pages', function () {
    $product = commerceProduct();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Home')
            ->has('featuredProducts', 1));

    $this->get(route('shop'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Shop')
            ->has('products', 1)
            ->where('products.0.slug', $product->slug));

    $this->get(route('shop.product', $product))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Product')
            ->where('product.slug', $product->slug));
});

test('customers can add update and remove products in the session cart', function () {
    $product = commerceProduct(['price' => 25, 'stock' => 3]);

    $this->post(route('cart.add', $product), ['quantity' => 2])
        ->assertRedirect();

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Cart')
            ->where('count', 2)
            ->where('subtotal', 50));

    $this->patch(route('cart.update', $product), ['quantity' => 99])
        ->assertRedirect();

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page->where('count', 3));

    $this->delete(route('cart.remove', $product))
        ->assertRedirect();

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page->where('count', 0));
});

test('cart mutations invalidate an existing checkout key', function () {
    $product = commerceProduct(['price' => 25]);

    $this->post(route('cart.add', $product));
    $this->get(route('checkout'));
    $checkoutKey = session('elyxstudio_checkout_key');

    $this->patch(route('cart.update', $product), ['quantity' => 2])
        ->assertRedirect();

    expect(session('elyxstudio_checkout_key'))->toBeNull();

    $this->get(route('checkout'));

    expect(session('elyxstudio_checkout_key'))
        ->not->toBe($checkoutKey);
});

test('zero stock products cannot be added to the cart', function () {
    $product = commerceProduct([
        'name' => 'Sold out notebook',
        'slug' => 'sold-out-notebook',
        'type' => 'product',
        'stock' => 0,
    ]);

    $this->from(route('shop.product', $product))
        ->post(route('cart.add', $product))
        ->assertRedirect(route('shop.product', $product))
        ->assertSessionHas('error');

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page->where('count', 0));
});

test('empty carts cannot reach checkout', function () {
    $this->get(route('checkout'))
        ->assertRedirect(route('shop'))
        ->assertSessionHas('info');
});

test('checkout validates customer details and persists an order snapshot', function () {
    $product = commerceProduct(['price' => 125, 'stock' => 4]);

    $this->post(route('cart.add', $product), ['quantity' => 2]);

    $this->get(route('checkout'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('checkoutKey', fn ($key) => is_string($key)));

    $checkoutKey = session('elyxstudio_checkout_key');

    $this->post(route('checkout.store'), [
        'checkout_key' => $checkoutKey,
        'customer_name' => 'Ada Lovelace',
        'customer_email' => 'ada@example.com',
        'customer_note' => 'Please send the project details.',
    ])->assertRedirect();

    $order = Order::query()->with('items')->firstOrFail();

    expect($order->order_number)->toStartWith('ELYX-')
        ->and($order->confirmation_token)->toHaveLength(48)
        ->and($order->checkout_key)->toBe($checkoutKey)
        ->and($order->reservation_expires_at)->not->toBeNull()
        ->and($order->status)->toBe('pending')
        ->and($order->payment_status)->toBe('unpaid')
        ->and((float) $order->total)->toBe(250.0)
        ->and($order->items)->toHaveCount(1)
        ->and($order->items->first()->name)->toBe($product->name)
        ->and((float) $order->items->first()->unit_price)->toBe(125.0)
        ->and($order->items->first()->quantity)->toBe(2);

    $confirmationUrl = $order->confirmationUrl();

    $this->get($confirmationUrl)
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertInertia(fn ($page) => $page
            ->component('Storefront/Confirmation')
            ->where('order.order_number', $order->order_number)
            ->missing('order.confirmation_token')
            ->missing('order.checkout_key')
            ->missing('order.checkout_session_hash'));

    $order->forceFill(['payment_status' => 'paid'])->save();

    $this->get($order->fresh()->confirmationUrl())
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('order.order_number', $order->order_number));

    $this->get('/orders/'.$order->order_number)->assertNotFound();
    $this->get(route('order.confirmation', ['order' => $order->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'customer_email' => 'ada@example.com',
        'subtotal' => 250,
    ]);

    $this->get(route('cart'))
        ->assertInertia(fn ($page) => $page->where('count', 0));
});

test('digital-only checkout does not create a stock reservation expiry', function () {
    $product = commerceProduct(['price' => 75, 'stock' => null]);

    $this->post(route('cart.add', $product));
    $this->get(route('checkout'));

    $this->post(route('checkout.store'), [
        'checkout_key' => session('elyxstudio_checkout_key'),
        'customer_name' => 'Ada Lovelace',
        'customer_email' => 'ada@example.com',
    ])->assertRedirect();

    expect(Order::query()->firstOrFail()->reservation_expires_at)->toBeNull();
});

test('expired signed confirmation URLs are rejected', function () {
    $product = commerceProduct(['price' => 75]);

    $this->post(route('cart.add', $product));
    $this->get(route('checkout'));
    $this->post(route('checkout.store'), [
        'checkout_key' => session('elyxstudio_checkout_key'),
        'customer_name' => 'Ada Lovelace',
        'customer_email' => 'ada@example.com',
    ])->assertRedirect();

    $order = Order::query()->firstOrFail();
    $expiredUrl = URL::temporarySignedRoute(
        'order.confirmation',
        now()->subMinute(),
        ['order' => $order->id],
    );

    $this->get($expiredUrl)->assertForbidden();
});

test('checkout rejects invalid customer details before creating an order', function () {
    $product = commerceProduct();
    $this->post(route('cart.add', $product));
    $this->get(route('checkout'));

    $this->post(route('checkout.store'), [
        'checkout_key' => session('elyxstudio_checkout_key'),
        'customer_name' => '',
        'customer_email' => 'not-an-email',
    ])->assertSessionHasErrors(['customer_name', 'customer_email']);

    expect(Order::count())->toBe(0);
});

test('checkout submissions are idempotent for the same session key', function () {
    $product = commerceProduct(['price' => 25]);
    $this->post(route('cart.add', $product));
    $this->get(route('checkout'));
    $payload = [
        'checkout_key' => session('elyxstudio_checkout_key'),
        'customer_name' => 'Ada Lovelace',
        'customer_email' => 'ada@example.com',
    ];

    $this->post(route('checkout.store'), $payload)->assertRedirect();
    $this->post(route('checkout.store'), $payload)->assertRedirect();

    expect(Order::count())->toBe(1);
});

test('public product payloads do not expose internal reservation counts', function () {
    $product = commerceProduct(['stock' => 5]);
    $product->forceFill(['reserved_stock' => 2])->save();

    $this->get(route('shop.product', $product))
        ->assertInertia(fn ($page) => $page
            ->where('product.available_stock', 3)
            ->missing('product.reserved_stock'));
});
