<?php

use App\Models\Quarter;
use App\Models\Subject;
use App\Models\TeachingMaterial;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
});

test('default seeded quarters exist in database', function () {
    expect(Quarter::count())->toBeGreaterThanOrEqual(4);
    expect(Quarter::where('code', 'Q1')->exists())->toBeTrue();
    expect(Quarter::where('code', 'Q2')->exists())->toBeTrue();
    expect(Quarter::where('code', 'Q3')->exists())->toBeTrue();
    expect(Quarter::where('code', 'Q4')->exists())->toBeTrue();
});

test('teacher can view quarters page and manage quarters', function () {
    $response = $this->actingAs($this->teacher)->get(route('teacher.quarters.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Quarters/Index')
        ->has('quarters')
    );

    // Create a new Quarter
    $storeRes = $this->actingAs($this->teacher)->post(route('teacher.quarters.store'), [
        'name' => 'Midterm Intensive',
        'code' => 'MID',
        'description' => 'Midterm special cycle',
        'display_order' => 5,
        'is_active' => true,
        'start_date' => '2026-06-01',
        'end_date' => '2026-07-15',
    ]);
    $storeRes->assertRedirect(route('teacher.quarters.index'));

    $created = Quarter::where('code', 'MID')->first();
    expect($created)->not->toBeNull();
    expect($created->name)->toBe('Midterm Intensive');
    expect($created->display_order)->toBe(5);

    // Update the Quarter
    $updateRes = $this->actingAs($this->teacher)->put(route('teacher.quarters.update', $created), [
        'name' => 'Midterm Term Revised',
        'code' => 'MID-R',
        'description' => 'Updated description',
        'display_order' => 6,
        'is_active' => false,
    ]);
    $updateRes->assertRedirect(route('teacher.quarters.index'));

    $created->refresh();
    expect($created->name)->toBe('Midterm Term Revised');
    expect($created->code)->toBe('MID-R');
    expect($created->is_active)->toBeFalse();

    // Delete the Quarter
    $deleteRes = $this->actingAs($this->teacher)->delete(route('teacher.quarters.destroy', $created));
    $deleteRes->assertRedirect(route('teacher.quarters.index'));

    expect(Quarter::where('id', $created->id)->exists())->toBeFalse();
});

test('student cannot access quarters management', function () {
    $response = $this->actingAs($this->student)->get(route('teacher.quarters.index'));
    $response->assertRedirect(route('student.dashboard'));
});

test('teacher and student materials and games index pass active quarters for folder navigation', function () {
    $q1 = Quarter::where('code', 'Q1')->first();

    TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Q1 Biology Lecture',
        'subject' => 'Biology',
        'file_path' => 'teaching-materials/test.pdf',
        'file_name' => 'test.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
        'quarter_id' => $q1?->id,
    ]);

    $matRes = $this->actingAs($this->teacher)->get(route('teacher.materials.index'));
    $matRes->assertOk();
    $matRes->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Materials')
        ->has('quarters')
        ->where('materials.0.quarter_id', $q1?->id)
        ->where('materials.0.quarter_code', 'Q1')
    );

    $studentMatRes = $this->actingAs($this->student)->get('/student/materials');
    $studentMatRes->assertOk();
    $studentMatRes->assertInertia(fn (Assert $page) => $page
        ->component('Student/Materials')
        ->has('quarters')
        ->where('materials.0.quarter_id', $q1?->id)
        ->where('materials.0.quarter_code', 'Q1')
    );

    $gameRes = $this->actingAs($this->teacher)->get(route('teacher.games.index'));
    $gameRes->assertOk();
    $gameRes->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Games')
        ->has('quarters')
    );
});

test('superadmin can assign subjects to a teacher in teacherUpdate', function () {
    $subject1 = Subject::create(['name' => 'Physics', 'is_active' => true]);
    $subject2 = Subject::create(['name' => 'Chemistry', 'is_active' => true]);

    $response = $this->actingAs($this->superadmin)->from('/teacher/teachers')->put("/teacher/teachers/{$this->teacher->id}", [
        'name' => 'Updated Teacher Name',
        'email' => $this->teacher->email,
        'grade' => 'Grade 10',
        'section' => 'A',
        'subject_ids' => [$subject1->id, $subject2->id],
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect('/teacher/teachers');

    $this->teacher->refresh();
    expect($this->teacher->subjects()->pluck('subjects.id')->all())->toEqualCanonicalizing([$subject1->id, $subject2->id]);
});

test('superadmin can assign subjects to a teacher in UserController update and store', function () {
    $math = Subject::create(['name' => 'Advanced Math', 'is_active' => true]);
    $biology = Subject::create(['name' => 'Biology', 'is_active' => true]);

    // Store new teacher with subjects
    $storeRes = $this->actingAs($this->superadmin)->post('/admin/users', [
        'name' => 'New Science Teacher',
        'email' => 'scienceteacher@edupulse.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'teacher',
        'subject_ids' => [$biology->id],
    ]);
    $storeRes->assertSessionHasNoErrors();

    $newTeacher = User::where('email', 'scienceteacher@edupulse.test')->first();
    expect($newTeacher)->not->toBeNull();
    expect($newTeacher->subjects()->pluck('subjects.id')->all())->toEqualCanonicalizing([$biology->id]);

    // Update teacher with additional subject
    $updateRes = $this->actingAs($this->superadmin)->put("/admin/users/{$newTeacher->id}", [
        'name' => 'New Science Teacher Updated',
        'email' => 'scienceteacher@edupulse.test',
        'role' => 'teacher',
        'subject_ids' => [$math->id, $biology->id],
    ]);
    $updateRes->assertSessionHasNoErrors();

    $newTeacher->refresh();
    expect($newTeacher->subjects()->pluck('subjects.id')->all())->toEqualCanonicalizing([$math->id, $biology->id]);
});
