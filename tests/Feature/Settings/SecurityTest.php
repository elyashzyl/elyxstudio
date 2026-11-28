<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

function confirmedSecuritySession(): array
{
    return ['auth.password_confirmed_at' => now()->unix()];
}

test('buyers can view the security settings page', function () {
    $user = User::factory()->create(['role' => 'buyer']);

    $this->actingAs($user)
        ->withSession(confirmedSecuritySession())
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/Security'));
});

test('sellers can view the security settings page', function () {
    $seller = User::factory()->create(['role' => 'seller']);

    $this->actingAs($seller)
        ->withSession(confirmedSecuritySession())
        ->get(route('security.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/Security'));
});

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('security.edit'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrors('current_password')
        ->assertRedirect(route('security.edit'));
});
