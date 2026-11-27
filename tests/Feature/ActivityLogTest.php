<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('activity log exposes user avatar when user has profile photo', function () {
    $user = User::factory()->create([
        'profile_photo_updated_at' => now(),
    ]);

    $log = ActivityLog::create([
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'user_role' => $user->role,
        'action' => 'login',
        'description' => 'User logged in',
    ]);

    expect($log->avatar)->not->toBeNull();
    expect($log->avatar)->toContain(route('profile.avatar', $user, false));
});

test('activity log avatar is null for guest or user without photo', function () {
    $guestLog = ActivityLog::create([
        'user_id' => null,
        'user_name' => 'Guest',
        'action' => 'failed_login',
        'description' => 'Guest failed login',
    ]);

    expect($guestLog->avatar)->toBeNull();

    $userWithoutPhoto = User::factory()->create(['profile_photo_updated_at' => null]);
    $userLog = ActivityLog::create([
        'user_id' => $userWithoutPhoto->id,
        'user_name' => $userWithoutPhoto->name,
        'action' => 'logout',
        'description' => 'User logged out',
    ]);

    expect($userLog->avatar)->toBeNull();
});

test('superadmin can view activity logs with user avatar data', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    $student = User::factory()->create([
        'role' => 'student',
        'profile_photo_updated_at' => now(),
    ]);

    ActivityLog::create([
        'user_id' => $student->id,
        'user_name' => $student->name,
        'user_email' => $student->email,
        'user_role' => 'student',
        'action' => 'quiz_submitted',
        'description' => 'Submitted quiz',
    ]);

    $response = $this->actingAs($superadmin)->get('/teacher/activity-logs');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/ActivityLogs')
        ->has('logs.data', 1)
        ->where('logs.data.0.avatar', fn ($avatar) => str_contains((string) $avatar, route('profile.avatar', $student, false)))
    );
});
