<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->seller = User::factory()->create([
        'role' => 'seller',
        'email_verified_at' => now(),
    ]);

    $this->buyer = User::factory()->create([
        'role' => 'buyer',
        'email_verified_at' => null,
    ]);
});

test('superadmin can impersonate a seller via POST', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.users.impersonate', $this->seller));

    $response->assertRedirect(route('seller.dashboard'));
    expect(auth()->id())->toBe($this->seller->id)
        ->and(session('impersonated_by'))->toBe($this->admin->id);
});

test('superadmin can impersonate a buyer via POST and verifies the account', function () {
    expect($this->buyer->email_verified_at)->toBeNull();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.users.impersonate', $this->buyer));

    $response->assertRedirect(route('account'));
    expect(auth()->id())->toBe($this->buyer->id)
        ->and(session('impersonated_by'))->toBe($this->admin->id)
        ->and($this->buyer->fresh()->email_verified_at)->not->toBeNull();
});

test('superadmin can impersonate via GET without 405 error', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.users.impersonate', $this->buyer));

    $response->assertRedirect(route('account'));
    expect(auth()->id())->toBe($this->buyer->id)
        ->and(session('impersonated_by'))->toBe($this->admin->id);
});

test('inertia request receives a 409 location response for clean real-time redirect', function () {
    $response = $this->actingAs($this->admin)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->post(route('admin.users.impersonate', $this->buyer));

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toBe(route('account'))
        ->and(auth()->id())->toBe($this->buyer->id)
        ->and(session('impersonated_by'))->toBe($this->admin->id);
});

test('superadmin can leave impersonation via POST and GET', function () {
    $response = $this->actingAs($this->buyer)
        ->withSession(['impersonated_by' => $this->admin->id])
        ->post(route('users.leave-impersonation'));

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toBe(route('admin.commerce.index'))
        ->and(auth()->id())->toBe($this->admin->id)
        ->and(session('impersonated_by'))->toBeNull();

    $responseGet = $this->actingAs($this->buyer)
        ->withSession(['impersonated_by' => $this->admin->id])
        ->get(route('users.leave-impersonation'));

    $responseGet->assertStatus(409);
    expect($responseGet->headers->get('X-Inertia-Location'))->toBe(route('admin.commerce.index'))
        ->and(auth()->id())->toBe($this->admin->id)
        ->and(session('impersonated_by'))->toBeNull();
});

test('superadmin already impersonating can switch to another user', function () {
    $otherSeller = User::factory()->create(['role' => 'seller']);

    $response = $this->actingAs($this->seller)
        ->withSession(['impersonated_by' => $this->admin->id])
        ->post(route('admin.users.impersonate', $otherSeller));

    $response->assertRedirect(route('seller.dashboard'));
    expect(auth()->id())->toBe($otherSeller->id)
        ->and(session('impersonated_by'))->toBe($this->admin->id);
});

test('non-superadmin cannot impersonate', function () {
    $response = $this->actingAs($this->seller)
        ->post(route('admin.users.impersonate', $this->buyer));

    $response->assertStatus(403);
});
