<?php

use App\Models\User;
use App\Models\UserProfilePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('settings/Profile'));
});

test('profile information can update the ELYX account details', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
            'gender' => 'Male',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->name)->toBe('Test User')
        ->and($user->gender)->toBe('Male');
});

test('profile customization can update bio and upload a photo', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('profile.customize'), [
            'bio' => 'I build thoughtful digital products.',
            'profile_photo' => UploadedFile::fake()->image('profile.png', 300, 300),
        ]);

    $response->assertSessionHasNoErrors();
    $user->refresh();

    expect($user->bio)->toBe('I build thoughtful digital products.')
        ->and($user->profile_photo_updated_at)->not->toBeNull()
        ->and($user->profilePhoto)->not->toBeNull()
        ->and($user->profilePhoto->mime_type)->toBe('image/png')
        ->and($user->profilePhoto->contents())->not->toBe('');
});

test('profile photo is stored in the database, not on disk', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.customize'), [
        'profile_photo' => UploadedFile::fake()->image('profile.png'),
    ])->assertSessionHasNoErrors();

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
    expect(UserProfilePhoto::where('user_id', $user->id)->exists())->toBeTrue();
});

test('profile photo larger than 512px is resized on upload', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.customize'), [
        'profile_photo' => UploadedFile::fake()->image('huge.png', 1200, 800),
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->profilePhoto)->not->toBeNull();

    $raw = $user->profilePhoto->contents();
    $image = imagecreatefromstring($raw);
    expect($image)->not->toBeFalse();
    expect(imagesx($image))->toBe(512);
    expect(imagesy($image))->toBe(341);
    imagedestroy($image);
});

test('bio-only update keeps the existing profile photo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.customize'), [
        'profile_photo' => UploadedFile::fake()->image('profile.png'),
    ])->assertSessionHasNoErrors();

    $this->actingAs($user)->post(route('profile.customize'), [
        'bio' => 'Updated bio',
    ])->assertSessionHasNoErrors();

    $user->refresh();
    expect($user->bio)->toBe('Updated bio')
        ->and($user->profilePhoto)->not->toBeNull()
        ->and($user->avatar)->not->toBeNull();
});

test('profile avatar endpoint serves the stored profile photo', function () {
    $user = User::factory()->create(['profile_photo_updated_at' => now()]);
    $contents = UploadedFile::fake()->image('avatar.png')->getContent();
    $user->profilePhoto()->create([
        'mime_type' => 'image/png',
        'data' => base64_encode($contents),
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('profile.avatar', $user));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png');
    expect($response->getContent())->toBe($contents);
});

test('profile avatar endpoint returns 404 when the user has no photo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.avatar', $user))
        ->assertNotFound();
});

test('profile customization rejects unsupported photo types', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('profile.customize'), [
            'profile_photo' => UploadedFile::fake()->create('profile.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasErrors('profile_photo');
    expect($user->fresh()->profilePhoto)->toBeNull()
        ->and($user->fresh()->profile_photo_updated_at)->toBeNull();
});

test('profile customization replaces and removes the previous photo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.customize'), [
        'profile_photo' => UploadedFile::fake()->image('old.png'),
    ])->assertSessionHasNoErrors();
    $oldData = $user->fresh()->profilePhoto->data;

    $this->actingAs($user)->post(route('profile.customize'), [
        'profile_photo' => UploadedFile::fake()->image('new.jpg', 50, 50),
    ])->assertSessionHasNoErrors();

    expect(UserProfilePhoto::where('user_id', $user->id)->count())->toBe(1)
        ->and($user->fresh()->profilePhoto->data)->not->toBe($oldData)
        ->and($user->fresh()->profilePhoto->mime_type)->toBe('image/jpeg');

    $this->actingAs($user)->post(route('profile.customize'), [
        'remove_profile_photo' => true,
    ])->assertSessionHasNoErrors();

    expect($user->fresh()->profile_photo_updated_at)->toBeNull()
        ->and($user->fresh()->avatar)->toBeNull()
        ->and(UserProfilePhoto::where('user_id', $user->id)->exists())->toBeFalse();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $this->assertGuest();
    expect($user->fresh())->toBeNull();
});

test('deleting a user removes their stored profile photo', function () {
    $user = User::factory()->create(['profile_photo_updated_at' => now()]);
    $user->profilePhoto()->create(['mime_type' => 'image/png', 'data' => base64_encode('png')]);

    $user->delete();

    expect(UserProfilePhoto::where('user_id', $user->id)->exists())->toBeFalse();
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ])
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});
