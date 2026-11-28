<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('superadmins are sent to the admin commerce workspace', function () {
    $user = User::factory()->create(['role' => 'superadmin']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.commerce.index'));
});

test('sellers are sent to the seller dashboard', function () {
    $user = User::factory()->create(['role' => 'seller']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('seller.dashboard'));
});

test('buyers are sent to their account dashboard', function () {
    $user = User::factory()->create(['role' => 'buyer']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('account'));
});
