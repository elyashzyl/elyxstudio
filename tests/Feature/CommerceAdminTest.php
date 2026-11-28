<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function adminCommerceProduct(array $attributes = []): Product
{
    $reservedStock = (int) ($attributes['reserved_stock'] ?? 0);
    unset($attributes['reserved_stock']);

    $product = Product::create(array_merge([
        'name' => 'Admin Product',
        'slug' => 'admin-product',
        'type' => 'product',
        'category' => 'Studio goods',
        'tagline' => 'A product managed by the studio.',
        'description' => 'A product for admin tests.',
        'features' => ['Test feature'],
        'price' => 40,
        'stock' => 10,
        'is_featured' => false,
        'is_active' => true,
    ], $attributes));

    if ($reservedStock > 0) {
        $product->forceFill(['reserved_stock' => $reservedStock])->save();
    }

    return $product->fresh();
}

test('only superadmins can access the commerce workspace', function () {
    $seller = User::factory()->create(['role' => 'seller']);

    $this->actingAs($seller)
        ->get(route('admin.commerce.index'))
        ->assertForbidden();

    $admin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($admin)
        ->get(route('admin.commerce.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Commerce/AdminDashboard')
            ->where('stats.products', 0));
});

test('admin commerce payload exposes reservation counts', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    adminCommerceProduct(['reserved_stock' => 2]);

    $this->actingAs($admin)
        ->get(route('admin.commerce.index'))
        ->assertInertia(fn ($page) => $page
            ->where('products.0.reserved_stock', 2)
            ->where('products.0.available_stock', 8));
});

test('superadmins can create and update catalog products', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($admin)
        ->post(route('admin.commerce.products.store'), [
            'name' => 'Launch Website',
            'slug' => 'launch-website',
            'type' => 'service',
            'category' => 'Websites',
            'tagline' => 'A focused website package.',
            'description' => 'A complete website package.',
            'features' => "Strategy call\nResponsive build",
            'price' => 1500,
            'stock' => null,
            'is_active' => true,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'launch-website')->firstOrFail();

    expect($product->features)->toBe(['Strategy call', 'Responsive build'])
        ->and($product->type)->toBe('service');

    $this->actingAs($admin)
        ->patch(route('admin.commerce.products.update', $product), [
            'name' => 'Launch Website Plus',
            'slug' => 'launch-website-plus',
            'type' => 'service',
            'category' => 'Websites',
            'description' => 'An expanded website package.',
            'features' => "Strategy call\nAnalytics setup",
            'price' => 1800,
            'stock' => null,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect($product->fresh()->name)->toBe('Launch Website Plus')
        ->and($product->fresh()->slug)->toBe('launch-website-plus');
});

test('completing an order consumes reserved physical stock', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $product = adminCommerceProduct(['reserved_stock' => 2]);
    $order = new Order;
    $order->forceFill([
        'order_number' => 'ELYX-TEST-001',
        'confirmation_token' => str_repeat('a', 48),
        'status' => 'in_progress',
        'payment_status' => 'paid',
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 80,
        'tax' => 0,
        'total' => 80,
    ])->save();
    $item = new OrderItem;
    $item->forceFill([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'type' => $product->type,
        'unit_price' => 40,
        'quantity' => 2,
        'line_total' => 80,
        'stock_reserved' => true,
    ])->save();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.status', $order), ['status' => 'completed'])
        ->assertRedirect();

    expect($product->fresh()->stock)->toBe(8)
        ->and($product->fresh()->reserved_stock)->toBe(0)
        ->and($item->fresh()->stock_reserved)->toBeFalse()
        ->and($order->fresh()->status)->toBe('completed');
});

test('cancelling an order releases reserved physical stock without consuming it', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $product = adminCommerceProduct(['reserved_stock' => 3]);
    $order = new Order;
    $order->forceFill([
        'order_number' => 'ELYX-TEST-002',
        'confirmation_token' => str_repeat('b', 48),
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 120,
        'tax' => 0,
        'total' => 120,
    ])->save();
    $item = new OrderItem;
    $item->forceFill([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'type' => $product->type,
        'unit_price' => 40,
        'quantity' => 3,
        'line_total' => 120,
        'stock_reserved' => true,
    ])->save();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.status', $order), ['status' => 'cancelled'])
        ->assertRedirect();

    expect($product->fresh()->stock)->toBe(10)
        ->and($product->fresh()->reserved_stock)->toBe(0)
        ->and($order->fresh()->status)->toBe('cancelled');
});

test('unpaid orders cannot enter fulfillment', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $order = new Order;
    $order->forceFill([
        'order_number' => 'ELYX-TEST-003',
        'confirmation_token' => str_repeat('c', 48),
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 40,
        'tax' => 0,
        'total' => 40,
    ])->save();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.status', $order), ['status' => 'confirmed'])
        ->assertSessionHasErrors('status');

    expect($order->fresh()->status)->toBe('pending');
});

test('superadmins can verify payment before moving an order into fulfillment', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $order = new Order;
    $order->forceFill([
        'order_number' => 'ELYX-TEST-004',
        'confirmation_token' => str_repeat('d', 48),
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 40,
        'tax' => 0,
        'total' => 40,
    ])->save();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.payment-status', $order), ['payment_status' => 'paid'])
        ->assertRedirect();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.status', $order), ['status' => 'confirmed'])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe('confirmed')
        ->and($order->fresh()->payment_status)->toBe('paid');
});

test('invalid payment status transitions are rejected', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $paidOrder = new Order;
    $paidOrder->forceFill([
        'order_number' => 'ELYX-TEST-PAID',
        'confirmation_token' => str_repeat('g', 48),
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 40,
        'tax' => 0,
        'total' => 40,
    ])->save();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.payment-status', $paidOrder), ['payment_status' => 'failed'])
        ->assertSessionHasErrors('payment_status');

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.payment-status', $paidOrder), ['payment_status' => 'unpaid'])
        ->assertSessionHasErrors('payment_status');

    $failedOrder = new Order;
    $failedOrder->forceFill([
        'order_number' => 'ELYX-TEST-FAILED',
        'confirmation_token' => str_repeat('h', 48),
        'status' => 'cancelled',
        'payment_status' => 'failed',
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 40,
        'tax' => 0,
        'total' => 40,
    ])->save();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.payment-status', $failedOrder), ['payment_status' => 'paid'])
        ->assertSessionHasErrors('payment_status');

    expect($paidOrder->fresh()->payment_status)->toBe('paid')
        ->and($failedOrder->fresh()->payment_status)->toBe('failed');
});

test('failed payment releases inventory and cancels the order', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $product = adminCommerceProduct(['reserved_stock' => 2]);
    $order = new Order;
    $order->forceFill([
        'order_number' => 'ELYX-TEST-005',
        'confirmation_token' => str_repeat('e', 48),
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 80,
        'tax' => 0,
        'total' => 80,
    ])->save();
    $item = new OrderItem;
    $item->forceFill([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'type' => $product->type,
        'unit_price' => 40,
        'quantity' => 2,
        'line_total' => 80,
        'stock_reserved' => true,
    ])->save();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.orders.payment-status', $order), ['payment_status' => 'failed'])
        ->assertRedirect();

    expect($product->fresh()->reserved_stock)->toBe(0)
        ->and($item->fresh()->stock_reserved)->toBeFalse()
        ->and($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->payment_status)->toBe('failed');
});

test('reserved products reject unsafe stock changes', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $product = adminCommerceProduct(['reserved_stock' => 2]);

    $this->actingAs($admin)
        ->patch(route('admin.commerce.products.update', $product), ['stock' => 1])
        ->assertSessionHasErrors('stock');

    $this->actingAs($admin)
        ->patch(route('admin.commerce.products.update', $product), ['stock' => null])
        ->assertSessionHasErrors('stock');

    expect($product->fresh()->stock)->toBe(10);
});

test('expired unpaid reservations are released by the commerce command', function () {
    $product = adminCommerceProduct(['reserved_stock' => 2]);
    $order = new Order;
    $order->forceFill([
        'order_number' => 'ELYX-TEST-006',
        'confirmation_token' => str_repeat('f', 48),
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'reservation_expires_at' => now()->subMinute(),
        'customer_name' => 'Test Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 80,
        'tax' => 0,
        'total' => 80,
    ])->save();
    $item = new OrderItem;
    $item->forceFill([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'name' => $product->name,
        'type' => $product->type,
        'unit_price' => 40,
        'quantity' => 2,
        'line_total' => 80,
        'stock_reserved' => true,
    ])->save();

    $this->artisan('commerce:expire-reservations')->assertSuccessful();

    expect($product->fresh()->reserved_stock)->toBe(0)
        ->and($item->fresh()->stock_reserved)->toBeFalse()
        ->and($order->fresh()->status)->toBe('cancelled')
        ->and($order->fresh()->reservation_expires_at)->toBeNull();
});
