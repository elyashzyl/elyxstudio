<?php

use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('buyers can create an ELYX account', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test Buyer',
        'email' => 'buyer@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'buyer',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('account'));
    expect(User::query()->where('email', 'buyer@example.com')->value('role'))->toBe('buyer');
});

test('public registration cannot create an administrator account', function () {
    $this->post(route('register.store'), [
        'name' => 'Attempted Admin',
        'email' => 'admin-attempt@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => 'superadmin',
    ])->assertSessionHasErrors('role');

    $this->assertGuest();
});
