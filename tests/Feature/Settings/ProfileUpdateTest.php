<?php

use App\Models\User;
use App\Models\UserProfilePhoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('profile.edit'));

    $response->assertRedirect(route('student.profile'));
});

test('profile information can update grade level and section', function () {
    $gradeLevel = \App\Models\GradeLevel::create(['name' => 'Grade 10']);
    $section = \App\Models\Section::create([
        'name' => 'St. Thomas',
        'grade_level_id' => $gradeLevel->id,
    ]);
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
            'gender' => 'Male',
            'grade_level_id' => $gradeLevel->id,
            'section_id' => $section->id,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user->refresh();

    expect($user->gender)->toBe('Male');
    expect($user->grade_level_id)->toBe($gradeLevel->id);
    expect($user->section_id)->toBe($section->id);
});

test('profile customization can update bio and upload a photo', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('profile.customize'), [
            'bio' => 'I enjoy learning with my classmates.',
            'profile_photo' => UploadedFile::fake()->image('profile.png', 300, 300),
        ]);

    $response->assertSessionHasNoErrors();
    $user->refresh();

    expect($user->bio)->toBe('I enjoy learning with my classmates.');
    expect($user->profile_photo_updated_at)->not->toBeNull();
    expect($user->profilePhoto)->not->toBeNull();
    expect($user->profilePhoto->mime_type)->toBe('image/png');
    expect($user->profilePhoto->contents())->not->toBe('');
    expect($user->avatar)->toContain(route('profile.avatar', $user, false));
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
    expect($user->bio)->toBe('Updated bio');
    expect($user->profilePhoto)->not->toBeNull();
    expect($user->avatar)->not->toBeNull();
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
    expect($user->fresh()->profilePhoto)->toBeNull();
    expect($user->fresh()->profile_photo_updated_at)->toBeNull();
});

test('profile customization replaces and removes the previous photo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('profile.customize'), [
        'profile_photo' => UploadedFile::fake()->image('old.png'),
    ])->assertSessionHasNoErrors();
    $oldData = $user->fresh()->profilePhoto->data;

    $response = $this
        ->actingAs($user)
        ->post(route('profile.customize'), [
            'profile_photo' => UploadedFile::fake()->image('new.jpg', 50, 50),
        ]);

    $response->assertSessionHasNoErrors();
    expect(UserProfilePhoto::where('user_id', $user->id)->count())->toBe(1);
    expect($user->fresh()->profilePhoto->data)->not->toBe($oldData);
    expect($user->fresh()->profilePhoto->mime_type)->toBe('image/jpeg');

    $response = $this
        ->actingAs($user)
        ->post(route('profile.customize'), [
            'remove_profile_photo' => true,
        ]);

    $response->assertSessionHasNoErrors();
    expect($user->fresh()->profile_photo_updated_at)->toBeNull();
    expect($user->fresh()->avatar)->toBeNull();
    expect(UserProfilePhoto::where('user_id', $user->id)->exists())->toBeFalse();
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('profile.destroy'), [
            'password' => 'password',
        ]);

    $response
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

    $response = $this
        ->actingAs($user)
        ->from(route('profile.edit'))
        ->delete(route('profile.destroy'), [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrors('password')
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh())->not->toBeNull();
});