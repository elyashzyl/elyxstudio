<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('buyers are redirected away from seller workflows', function () {
    $buyer = User::factory()->create(['role' => 'buyer']);

    $this->actingAs($buyer)
        ->get(route('seller.dashboard'))
        ->assertRedirect(route('account'));
});

test('sellers cannot access the admin workspace', function () {
    $seller = User::factory()->create(['role' => 'seller']);

    $this->actingAs($seller)
        ->get(route('admin.commerce.index'))
        ->assertForbidden();
});

test('buyers cannot submit seller products', function () {
    $buyer = User::factory()->create(['role' => 'buyer']);

    $this->actingAs($buyer)
        ->post(route('seller.products.store'), [])
        ->assertForbidden();
});

test('guests cannot access commerce panels', function () {
    $this->get(route('seller.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.commerce.index'))->assertRedirect(route('login'));
});
