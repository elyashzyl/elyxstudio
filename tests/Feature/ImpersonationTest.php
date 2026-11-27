<?php

use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1]);
    $this->section = Section::create(['name' => 'Section A', 'grade_level_id' => $this->gradeLevel->id]);

    $this->admin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $this->section->id,
        'email_verified_at' => null,
    ]);
});

test('superadmin can impersonate a teacher via POST', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.users.impersonate', $this->teacher));

    $response->assertRedirect(route('teacher.dashboard'));
    expect(auth()->id())->toBe($this->teacher->id);
    expect(session('impersonated_by'))->toBe($this->admin->id);
});

test('superadmin can impersonate a student via POST and sets email_verified_at', function () {
    expect($this->student->email_verified_at)->toBeNull();

    $response = $this->actingAs($this->admin)
        ->post(route('admin.users.impersonate', $this->student));

    $response->assertRedirect(route('student.dashboard'));
    expect(auth()->id())->toBe($this->student->id);
    expect(session('impersonated_by'))->toBe($this->admin->id);
    expect($this->student->fresh()->email_verified_at)->not->toBeNull();
});

test('superadmin can impersonate via GET without 405 error', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.users.impersonate', $this->student));

    $response->assertRedirect(route('student.dashboard'));
    expect(auth()->id())->toBe($this->student->id);
    expect(session('impersonated_by'))->toBe($this->admin->id);
});

test('inertia request receives 409 location response for clean real-time redirect', function () {
    $response = $this->actingAs($this->admin)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->post(route('admin.users.impersonate', $this->student));

    $response->assertStatus(409);
    expect($response->headers->get('X-Inertia-Location'))->toBe(route('student.dashboard'));
    expect(auth()->id())->toBe($this->student->id);
    expect(session('impersonated_by'))->toBe($this->admin->id);
});

test('superadmin can leave impersonation via POST and GET', function () {
    // Via POST
    $response = $this->actingAs($this->student)
        ->withSession(['impersonated_by' => $this->admin->id])
        ->post(route('users.leave-impersonation'));

    $response->assertRedirect(route('teacher.dashboard'));
    expect(auth()->id())->toBe($this->admin->id);
    expect(session('impersonated_by'))->toBeNull();

    // Via GET
    $responseGet = $this->actingAs($this->student)
        ->withSession(['impersonated_by' => $this->admin->id])
        ->get(route('users.leave-impersonation'));

    $responseGet->assertRedirect(route('teacher.dashboard'));
    expect(auth()->id())->toBe($this->admin->id);
    expect(session('impersonated_by'))->toBeNull();
});

test('superadmin already impersonating can switch to another user', function () {
    $otherStudent = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $this->section->id,
    ]);

    // Currently impersonating $this->teacher
    $response = $this->actingAs($this->teacher)
        ->withSession(['impersonated_by' => $this->admin->id])
        ->post(route('admin.users.impersonate', $otherStudent));

    $response->assertRedirect(route('student.dashboard'));
    expect(auth()->id())->toBe($otherStudent->id);
    expect(session('impersonated_by'))->toBe($this->admin->id);
});

test('non-superadmin cannot impersonate', function () {
    $response = $this->actingAs($this->teacher)
        ->post(route('admin.users.impersonate', $this->student));

    $response->assertStatus(403);
});
