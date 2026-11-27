<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;

uses(RefreshDatabase::class);

function roleProduct(array $attributes = []): Product
{
    $internal = array_intersect_key($attributes, array_flip([
        'seller_id',
        'approval_status',
        'rejection_reason',
        'submitted_at',
        'approved_at',
        'approved_by',
    ]));
    $public = array_diff_key($attributes, $internal);

    $product = Product::create(array_merge([
        'name' => 'Role Product',
        'slug' => 'role-product-'.Str::lower(Str::random(8)),
        'type' => 'digital',
        'category' => 'Digital assets',
        'tagline' => 'A product used by commerce role tests.',
        'description' => 'A test product for commerce role boundaries.',
        'features' => ['Test feature'],
        'price' => 49,
        'stock' => null,
        'is_featured' => false,
        'is_active' => true,
    ], $public));

    if ($internal !== []) {
        $product->forceFill($internal)->save();
    }

    return $product->fresh();
}

function roleOrder(?User $user = null, array $attributes = []): Order
{
    $order = new Order;
    $order->forceFill(array_merge([
        'user_id' => $user?->id,
        'order_number' => 'ELYX-ROLE-'.Str::upper(Str::random(8)),
        'confirmation_token' => Str::random(48),
        'status' => 'pending',
        'payment_status' => 'unpaid',
        'customer_name' => 'Commerce Customer',
        'customer_email' => 'customer@example.com',
        'subtotal' => 49,
        'tax' => 0,
        'total' => 49,
    ], $attributes))->save();

    return $order->fresh();
}

test('buyers can only view their own account orders', function () {
    $buyer = User::factory()->create(['role' => 'buyer']);
    $otherBuyer = User::factory()->create(['role' => 'buyer']);
    $ownOrder = roleOrder($buyer);
    $otherOrder = roleOrder($otherBuyer, ['order_number' => 'ELYX-ROLE-OTHER']);

    $this->actingAs($buyer)
        ->get(route('account'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Commerce/BuyerDashboard')
            ->has('orders', 1)
            ->where('orders.0.id', $ownOrder->id));

    $this->actingAs($buyer)
        ->get(route('account.orders.show', $ownOrder))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Commerce/Order')
            ->where('order.order_number', $ownOrder->order_number));

    $this->actingAs($buyer)
        ->get(route('account.orders.show', $otherOrder))
        ->assertNotFound();
});

test('seller dashboards and sales are scoped to the authenticated seller', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    $ownProduct = roleProduct(['seller_id' => $seller->id]);
    $otherProduct = roleProduct(['seller_id' => $otherSeller->id]);
    $order = roleOrder(null, ['order_number' => 'ELYX-ROLE-SALE']);

    $ownItem = new OrderItem;
    $ownItem->forceFill([
        'order_id' => $order->id,
        'product_id' => $ownProduct->id,
        'name' => $ownProduct->name,
        'type' => $ownProduct->type,
        'unit_price' => 49,
        'quantity' => 1,
        'line_total' => 49,
    ])->save();

    $otherItem = new OrderItem;
    $otherItem->forceFill([
        'order_id' => $order->id,
        'product_id' => $otherProduct->id,
        'name' => $otherProduct->name,
        'type' => $otherProduct->type,
        'unit_price' => 49,
        'quantity' => 1,
        'line_total' => 49,
    ])->save();

    $this->actingAs($seller)
        ->get(route('seller.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Commerce/SellerDashboard')
            ->has('products', 1)
            ->where('products.0.id', $ownProduct->id)
            ->has('orders', 1)
            ->has('orders.0.items', 1)
            ->where('orders.0.items.0.name', $ownProduct->name));
});

test('buyers and teachers cannot access seller workflows', function () {
    $buyer = User::factory()->create(['role' => 'buyer']);
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($buyer)
        ->get(route('seller.dashboard'))
        ->assertRedirect(route('account'));

    $this->actingAs($teacher)
        ->get(route('seller.dashboard'))
        ->assertRedirect(route('teacher.dashboard'));

    $this->actingAs($buyer)
        ->post(route('seller.products.store'), [])
        ->assertForbidden();
});

test('sellers cannot edit another seller product or control protected fields', function () {
    $seller = User::factory()->create(['role' => 'seller']);
    $otherSeller = User::factory()->create(['role' => 'seller']);
    $otherProduct = roleProduct([
        'seller_id' => $otherSeller->id,
        'approval_status' => 'rejected',
        'is_active' => false,
    ]);
    $ownProduct = roleProduct([
        'seller_id' => $seller->id,
        'approval_status' => 'rejected',
        'is_active' => false,
    ]);

    $this->actingAs($seller)
        ->patch(route('seller.products.update', $otherProduct), [
            'name' => 'Unauthorized update',
        ])
        ->assertNotFound();

    $this->actingAs($seller)
        ->patch(route('seller.products.update', $ownProduct), [
            'name' => 'Resubmitted product',
            'is_active' => true,
            'is_featured' => true,
            'stock' => 999,
            'seller_id' => $otherSeller->id,
            'approval_status' => 'approved',
        ])
        ->assertRedirect();

    $updated = $ownProduct->fresh();
    expect($updated->name)->toBe('Resubmitted product')
        ->and($updated->seller_id)->toBe($seller->id)
        ->and($updated->stock)->toBeNull()
        ->and($updated->is_active)->toBeFalse()
        ->and($updated->is_featured)->toBeFalse()
        ->and($updated->approval_status)->toBe('submitted');
});

test('seller submissions are inactive and require admin approval', function () {
    $seller = User::factory()->create(['role' => 'seller']);

    $this->actingAs($seller)
        ->post(route('seller.products.store'), [
            'name' => 'Seller Submitted Kit',
            'slug' => 'seller-submitted-kit',
            'type' => 'digital',
            'category' => 'Digital assets',
            'description' => 'A product submitted by a seller.',
            'price' => 79,
            'stock' => 50,
            'is_active' => true,
            'is_featured' => true,
        ])
        ->assertRedirect();

    $product = Product::query()->where('slug', 'seller-submitted-kit')->firstOrFail();

    expect($product->seller_id)->toBe($seller->id)
        ->and($product->approval_status)->toBe('submitted')
        ->and($product->is_active)->toBeFalse()
        ->and($product->stock)->toBeNull();

    $this->get(route('shop.product', $product))->assertNotFound();
});

test('admins approve or reject seller products and public visibility follows approval', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);
    $seller = User::factory()->create(['role' => 'seller']);
    $pending = roleProduct([
        'seller_id' => $seller->id,
        'approval_status' => 'submitted',
        'is_active' => true,
    ]);

    $this->actingAs($seller)
        ->patch(route('admin.commerce.products.approve', $pending))
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('admin.commerce.products.approve', $pending))
        ->assertRedirect();

    expect($pending->fresh()->approval_status)->toBe('approved')
        ->and($pending->fresh()->is_active)->toBeTrue();

    $this->get(route('shop.product', $pending->fresh()))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('product.slug', $pending->slug));

    $rejected = roleProduct([
        'seller_id' => $seller->id,
        'approval_status' => 'submitted',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.commerce.products.reject', $rejected), [
            'rejection_reason' => 'Please add clearer deliverables.',
        ])
        ->assertRedirect();

    expect($rejected->fresh()->approval_status)->toBe('rejected')
        ->and($rejected->fresh()->is_active)->toBeFalse()
        ->and($rejected->fresh()->rejection_reason)->toBe('Please add clearer deliverables.');

    $this->get(route('shop.product', $rejected->fresh()))->assertNotFound();
});

test('admin user management aliases expose the full user workflow', function () {
    $admin = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Commerce/Users')
            ->where('basePath', '/admin/users'));

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Commerce Buyer',
            'email' => 'commerce-buyer@example.com',
            'role' => 'buyer',
            'password' => 'password123',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'email' => 'commerce-buyer@example.com',
        'role' => 'buyer',
    ]);
});

test('buyer and seller registration and login use commerce destinations', function () {
    $this->skipUnlessFortifyHas(Features::registration());

    $this->post(route('register.store'), [
        'name' => 'Registered Buyer',
        'email' => 'registered-buyer@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'buyer',
    ])->assertRedirect(route('account'));

    $this->assertAuthenticatedAs(User::query()->where('email', 'registered-buyer@example.com')->firstOrFail());
    $this->post('/logout');

    $this->post(route('register.store'), [
        'name' => 'Registered Seller',
        'email' => 'registered-seller@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'seller',
    ])->assertRedirect(route('seller.dashboard'));

    $this->post('/logout');
    $buyer = User::factory()->create(['role' => 'buyer']);
    $seller = User::factory()->create(['role' => 'seller']);

    $this->post('/login', [
        'email' => $buyer->email,
        'password' => 'password',
    ])->assertRedirect(route('account'));

    $this->post('/logout');

    $this->post('/login', [
        'email' => $seller->email,
        'password' => 'password',
    ])->assertRedirect(route('seller.dashboard'));
});
