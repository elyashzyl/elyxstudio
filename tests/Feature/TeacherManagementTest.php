<?php

use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
});

test('only superadmins can view manage teachers page', function () {
    $this->actingAs($this->student)->get(route('teacher.teachers.index'))
        ->assertRedirect(route('student.dashboard'));

    $this->actingAs($this->teacher)->get(route('teacher.teachers.index'))
        ->assertForbidden();

    $grade = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 10, 'is_active' => true]);
    $section = Section::create(['grade_level_id' => $grade->id, 'name' => 'Diamond', 'is_active' => true]);
    $subject = Subject::create(['name' => 'Mathematics', 'is_active' => true]);

    $response = $this->actingAs($this->superadmin)->get(route('teacher.teachers.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Teachers')
        ->has('teachers')
        ->has('subjects')
        ->has('gradeLevels')
        ->has('sections')
    );
});

test('superadmin can view teacher profile show page with full details', function () {
    $grade = GradeLevel::create(['name' => 'Grade 9', 'display_order' => 9, 'is_active' => true]);
    $section = Section::create(['grade_level_id' => $grade->id, 'name' => 'Emerald', 'is_active' => true]);
    $subject = Subject::create(['name' => 'Science', 'is_active' => true]);

    $this->teacher->update([
        'gender' => 'Female',
        'bio' => 'Experienced physics and chemistry teacher.',
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
        'grade' => 'Grade 9',
        'messenger_enabled' => true,
        'allow_classmate_messaging' => false,
    ]);
    $this->teacher->subjects()->sync([$subject->id]);

    $response = $this->actingAs($this->superadmin)->get(route('teacher.teachers.show', $this->teacher));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/TeacherShow')
        ->where('teacher.name', $this->teacher->name)
        ->where('teacher.gender', 'Female')
        ->where('teacher.bio', 'Experienced physics and chemistry teacher.')
        ->where('teacher.grade_level.name', 'Grade 9')
        ->where('teacher.section.name', 'Emerald')
        ->where('teacher.messenger_enabled', true)
        ->where('teacher.allow_classmate_messaging', false)
        ->has('teacher.subjects', 1)
        ->where('teacher.subjects.0.name', 'Science')
    );
});

test('superadmin can update teacher details completely', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 10, 'is_active' => true]);
    $section = Section::create(['grade_level_id' => $grade->id, 'name' => 'Rizal', 'is_active' => true]);
    $sub1 = Subject::create(['name' => 'Biology', 'is_active' => true]);
    $sub2 = Subject::create(['name' => 'Chemistry', 'is_active' => true]);

    $response = $this->actingAs($this->superadmin)->put(route('teacher.teachers.update', $this->teacher), [
        'name' => 'Maria Santos, LPT',
        'email' => 'maria.santos@edupulse.test',
        'gender' => 'Female',
        'bio' => 'Adviser for Grade 10 - Rizal. Biology specialist.',
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
        'subject_ids' => [$sub1->id, $sub2->id],
        'password' => 'new-secret-password-123',
        'messenger_enabled' => false,
        'allow_classmate_messaging' => true,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->teacher->refresh();
    expect($this->teacher->name)->toBe('Maria Santos, LPT');
    expect($this->teacher->email)->toBe('maria.santos@edupulse.test');
    expect($this->teacher->gender)->toBe('Female');
    expect($this->teacher->bio)->toBe('Adviser for Grade 10 - Rizal. Biology specialist.');
    expect($this->teacher->grade_level_id)->toBe($grade->id);
    expect($this->teacher->grade)->toBe('Grade 10');
    expect($this->teacher->section_id)->toBe($section->id);
    expect($this->teacher->messenger_enabled)->toBeFalse();
    expect($this->teacher->allow_classmate_messaging)->toBeTrue();
    expect($this->teacher->subjects()->pluck('subjects.id')->all())->toEqualCanonicalizing([$sub1->id, $sub2->id]);
    expect(Hash::check('new-secret-password-123', $this->teacher->password))->toBeTrue();
});

test('updating teacher without password keeps existing password', function () {
    $originalPassword = $this->teacher->password;

    $response = $this->actingAs($this->superadmin)->put(route('teacher.teachers.update', $this->teacher), [
        'name' => 'Renamed Teacher',
        'email' => $this->teacher->email,
        'gender' => 'Male',
        'password' => '',
    ]);

    $response->assertSessionHasNoErrors();
    $this->teacher->refresh();
    expect($this->teacher->name)->toBe('Renamed Teacher');
    expect($this->teacher->password)->toBe($originalPassword);
});

test('clearing advisory grade level clears section and grade string', function () {
    $grade = GradeLevel::create(['name' => 'Grade 8', 'display_order' => 8, 'is_active' => true]);
    $section = Section::create(['grade_level_id' => $grade->id, 'name' => 'Sampaguita', 'is_active' => true]);

    $this->teacher->update([
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
        'grade' => 'Grade 8',
    ]);

    $response = $this->actingAs($this->superadmin)->put(route('teacher.teachers.update', $this->teacher), [
        'name' => $this->teacher->name,
        'email' => $this->teacher->email,
        'grade_level_id' => null,
        'section_id' => $section->id, // Attempt to retain section without grade
    ]);

    $response->assertSessionHasNoErrors();
    $this->teacher->refresh();
    expect($this->teacher->grade_level_id)->toBeNull();
    expect($this->teacher->grade)->toBeNull();
    expect($this->teacher->section_id)->toBeNull();
});

test('teacher update validates gender and email uniqueness', function () {
    $other = User::factory()->create([
        'role' => 'teacher',
        'email' => 'taken@edupulse.test',
    ]);

    $response = $this->actingAs($this->superadmin)->put(route('teacher.teachers.update', $this->teacher), [
        'name' => 'Teacher',
        'email' => 'taken@edupulse.test',
        'gender' => 'InvalidGender',
        'password' => 'short',
    ]);

    $response->assertSessionHasErrors(['email', 'gender', 'password']);
});
