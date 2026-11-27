<?php

use App\Models\Game;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Subject;
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
});

test('teacher can view grade levels page and create a grade level with section', function () {
    $response = $this->actingAs($this->teacher)->get(route('teacher.grade-levels.index'));
    $response->assertOk();

    $storeRes = $this->actingAs($this->teacher)->post(route('teacher.grade-levels.store'), [
        'name' => 'Grade 11',
        'display_order' => 11,
        'is_active' => true,
    ]);
    $storeRes->assertRedirect(route('teacher.grade-levels.index'));

    $gradeLevel = GradeLevel::where('name', 'Grade 11')->first();
    expect($gradeLevel)->not->toBeNull();
    expect($gradeLevel->display_order)->toBe(11);

    // Add section
    $sectionRes = $this->actingAs($this->teacher)->post(route('teacher.grade-levels.sections.store', $gradeLevel), [
        'name' => 'Diamond',
        'is_active' => true,
    ]);
    $sectionRes->assertRedirect(route('teacher.grade-levels.index'));

    expect($gradeLevel->sections()->where('name', 'Diamond')->exists())->toBeTrue();
});

test('teacher games index passes gradeLevels prop', function () {
    GradeLevel::create(['name' => 'Grade 7', 'display_order' => 7, 'is_active' => true]);
    GradeLevel::create(['name' => 'Grade 8', 'display_order' => 8, 'is_active' => true]);

    $response = $this->actingAs($this->teacher)->get(route('teacher.games.index'));
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Games')
        ->has('gradeLevels', 2)
    );
});

test('updating user grade_level_id syncs grade name string', function () {
    $gl = GradeLevel::create(['name' => 'Grade 12', 'display_order' => 12, 'is_active' => true]);
    $student = User::factory()->create([
        'role' => 'student',
        'grade' => null,
        'grade_level_id' => null,
    ]);

    $response = $this->actingAs($this->superadmin)->put("/admin/users/{$student->id}", [
        'name' => $student->name,
        'email' => $student->email,
        'role' => 'student',
        'grade_level_id' => $gl->id,
    ]);

    $response->assertSessionHasNoErrors();
    $student->refresh();
    expect($student->grade_level_id)->toBe($gl->id);
    expect($student->grade)->toBe('Grade 12');
});
