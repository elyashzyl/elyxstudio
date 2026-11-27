<?php

use App\Models\GradeLevel;
use App\Models\Section;
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

    $this->otherTeacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'email_verified_at' => now(),
    ]);
});

test('students page includes complete assignment options and student details', function () {
    $grade = GradeLevel::create([
        'name' => 'Grade 10',
        'display_order' => 10,
        'is_active' => true,
    ]);
    $section = Section::create([
        'grade_level_id' => $grade->id,
        'name' => 'Rizal',
        'is_active' => true,
    ]);

    $this->student->update([
        'gender' => 'Female',
        'bio' => 'Needs additional reading support.',
        'grade' => 'Grade 10',
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
        'messenger_enabled' => false,
        'allow_classmate_messaging' => true,
    ]);
    $this->teacher->students()->attach($this->student->id);

    $response = $this->actingAs($this->teacher)->get(route('teacher.students'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Students')
        ->has('students', 1)
        ->where('students.0.id', $this->student->id)
        ->where('students.0.gender', 'Female')
        ->where('students.0.bio', 'Needs additional reading support.')
        ->where('students.0.grade_level.name', 'Grade 10')
        ->where('students.0.section.name', 'Rizal')
        ->where('students.0.messenger_enabled', false)
        ->has('gradeLevels', 1)
        ->has('sections', 1)
    );
});

test('only an assigned teacher or superadmin can update a student', function () {
    $response = $this->actingAs($this->otherTeacher)->put(route('teacher.students.update', $this->student), [
        'name' => 'Should Not Update',
        'email' => $this->student->email,
    ]);

    $response->assertForbidden();
    expect($this->student->fresh()->name)->not->toBe('Should Not Update');

    $response = $this->actingAs($this->superadmin)->put(route('teacher.students.update', $this->student), [
        'name' => 'Updated By Admin',
        'email' => $this->student->email,
    ]);

    $response->assertSessionHasNoErrors();
    expect($this->student->fresh()->name)->toBe('Updated By Admin');
});

test('assigned teacher can update all student profile details', function () {
    $grade = GradeLevel::create([
        'name' => 'Grade 9',
        'display_order' => 9,
        'is_active' => true,
    ]);
    $section = Section::create([
        'grade_level_id' => $grade->id,
        'name' => 'Emerald',
        'is_active' => true,
    ]);
    $this->teacher->students()->attach($this->student->id);

    $response = $this->actingAs($this->teacher)->put(route('teacher.students.update', $this->student), [
        'name' => 'Ana Reyes',
        'email' => 'ana.reyes@edupulse.test',
        'gender' => 'Female',
        'bio' => 'Excellent in collaborative activities.',
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
        'password' => 'new-student-password-123',
        'messenger_enabled' => false,
        'allow_classmate_messaging' => false,
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->student->refresh();
    expect($this->student->name)->toBe('Ana Reyes');
    expect($this->student->email)->toBe('ana.reyes@edupulse.test');
    expect($this->student->gender)->toBe('Female');
    expect($this->student->bio)->toBe('Excellent in collaborative activities.');
    expect($this->student->grade_level_id)->toBe($grade->id);
    expect($this->student->grade)->toBe('Grade 9');
    expect($this->student->section_id)->toBe($section->id);
    expect($this->student->messenger_enabled)->toBeFalse();
    expect($this->student->allow_classmate_messaging)->toBeFalse();
    expect(Hash::check('new-student-password-123', $this->student->password))->toBeTrue();
});

test('updating a student without a password keeps the existing password', function () {
    $this->teacher->students()->attach($this->student->id);
    $originalPassword = $this->student->password;

    $response = $this->actingAs($this->teacher)->put(route('teacher.students.update', $this->student), [
        'name' => 'Renamed Student',
        'email' => $this->student->email,
        'password' => '',
    ]);

    $response->assertSessionHasNoErrors();
    $this->student->refresh();
    expect($this->student->name)->toBe('Renamed Student');
    expect($this->student->password)->toBe($originalPassword);
});

test('profile-only student updates preserve existing academic assignment', function () {
    $grade = GradeLevel::create([
        'name' => 'Grade 6',
        'display_order' => 6,
        'is_active' => true,
    ]);
    $section = Section::create([
        'grade_level_id' => $grade->id,
        'name' => 'Mabini',
        'is_active' => true,
    ]);
    $this->student->update([
        'grade_level_id' => $grade->id,
        'grade' => 'Grade 6',
        'section_id' => $section->id,
    ]);
    $this->teacher->students()->attach($this->student->id);

    $response = $this->actingAs($this->teacher)->put(route('teacher.students.update', $this->student), [
        'name' => 'Updated Student',
        'email' => $this->student->email,
    ]);

    $response->assertSessionHasNoErrors();
    $this->student->refresh();
    expect($this->student->grade_level_id)->toBe($grade->id);
    expect($this->student->grade)->toBe('Grade 6');
    expect($this->student->section_id)->toBe($section->id);
});

test('clearing a student grade level also clears its grade and section', function () {
    $grade = GradeLevel::create([
        'name' => 'Grade 8',
        'display_order' => 8,
        'is_active' => true,
    ]);
    $section = Section::create([
        'grade_level_id' => $grade->id,
        'name' => 'Sampaguita',
        'is_active' => true,
    ]);
    $this->student->update([
        'grade_level_id' => $grade->id,
        'grade' => 'Grade 8',
        'section_id' => $section->id,
    ]);
    $this->teacher->students()->attach($this->student->id);

    $response = $this->actingAs($this->teacher)->put(route('teacher.students.update', $this->student), [
        'name' => $this->student->name,
        'email' => $this->student->email,
        'grade_level_id' => null,
        'section_id' => $section->id,
    ]);

    $response->assertSessionHasNoErrors();
    $this->student->refresh();
    expect($this->student->grade_level_id)->toBeNull();
    expect($this->student->grade)->toBeNull();
    expect($this->student->section_id)->toBeNull();
});

test('student update clears a section from another grade and validates profile data', function () {
    $grade = GradeLevel::create([
        'name' => 'Grade 7',
        'display_order' => 7,
        'is_active' => true,
    ]);
    $otherGrade = GradeLevel::create([
        'name' => 'Grade 8',
        'display_order' => 8,
        'is_active' => true,
    ]);
    $wrongSection = Section::create([
        'grade_level_id' => $otherGrade->id,
        'name' => 'Wrong Section',
        'is_active' => true,
    ]);
    $this->teacher->students()->attach($this->student->id);

    $response = $this->actingAs($this->teacher)->put(route('teacher.students.update', $this->student), [
        'name' => 'Valid Student',
        'email' => $this->student->email,
        'gender' => 'Female',
        'grade_level_id' => $grade->id,
        'section_id' => $wrongSection->id,
    ]);

    $response->assertSessionHasNoErrors();
    expect($this->student->fresh()->section_id)->toBeNull();

    $other = User::factory()->create(['email' => 'taken@edupulse.test', 'role' => 'student']);
    $response = $this->actingAs($this->teacher)->put(route('teacher.students.update', $this->student), [
        'name' => 'Valid Student',
        'email' => $other->email,
        'gender' => 'Unknown',
        'password' => 'short',
    ]);

    $response->assertSessionHasErrors(['email', 'gender', 'password']);
});
