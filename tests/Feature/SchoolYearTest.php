<?php

use App\Models\Exam;
use App\Models\Game;
use App\Models\Practical;
use App\Models\Quiz;
use App\Models\SchoolYear;
use App\Models\Seatwork;
use App\Models\TeachingMaterial;
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

test('2026-2027 school year exists by default and new items belong to 2026-2027', function () {
    $current = SchoolYear::current();
    expect($current)->not->toBeNull();
    expect($current->name)->toBe('2026-2027');
    expect($current->is_current)->toBeTrue();

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => '2026-2027 Physics Module',
        'subject' => 'Physics',
        'file_path' => 'teaching-materials/physics.pdf',
        'file_name' => 'physics.pdf',
        'file_size' => 2048,
        'file_type' => 'pdf',
    ]);

    $game = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => '2026-2027 Physics Flashcards',
        'subject' => 'Physics',
        'type' => 'flashcard',
        'xp_reward' => 50,
    ]);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => '2026-2027 Physics Quiz 1',
        'grade' => 'Grade 10',
    ]);

    expect($material->school_year_id)->toBe($current->id);
    expect($game->school_year_id)->toBe($current->id);
    expect($quiz->school_year_id)->toBe($current->id);
});

test('starting a new school year starts fresh while preserving 2026-2027 content', function () {
    $sy2026 = SchoolYear::current();

    TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Legacy 2026-2027 Material',
        'subject' => 'Science',
        'file_path' => 'teaching-materials/legacy.pdf',
        'file_name' => 'legacy.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
    ]);

    // Teacher creates and sets 2027-2028 as current school year
    $storeRes = $this->actingAs($this->teacher)->post(route('teacher.school-years.store'), [
        'name' => '2027-2028',
        'description' => 'Next Academic Year',
        'is_current' => true,
        'is_active' => true,
    ]);
    $storeRes->assertRedirect(route('teacher.school-years.index'));

    $sy2027 = SchoolYear::where('name', '2027-2028')->first();
    expect($sy2027)->not->toBeNull();
    expect($sy2027->is_current)->toBeTrue();
    expect($sy2026->fresh()->is_current)->toBeFalse();

    // Viewing materials in the new 2027-2028 school year starts fresh (0 materials)
    $matRes = $this->actingAs($this->teacher)->get(route('teacher.materials.index'));
    $matRes->assertOk();
    $matRes->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Materials')
        ->has('materials', 0)
    );

    // Switching view back to 2026-2027 shows the previous material
    $this->actingAs($this->teacher)->post(route('teacher.school-years.switch'), [
        'school_year_id' => $sy2026->id,
    ]);

    $matRes2026 = $this->actingAs($this->teacher)->get(route('teacher.materials.index'));
    $matRes2026->assertOk();
    $matRes2026->assertInertia(fn (Assert $page) => $page
        ->component('Teacher/Materials')
        ->has('materials', 1)
        ->where('materials.0.title', 'Legacy 2026-2027 Material')
    );
});

test('teacher can move selected items or move all items from 2026-2027 to 2027-2028', function () {
    $sy2026 = SchoolYear::current();
    $sy2027 = SchoolYear::create([
        'name' => '2027-2028',
        'is_current' => false,
        'is_active' => true,
    ]);

    $mat1 = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Material 1',
        'subject' => 'Math',
        'file_path' => 'teaching-materials/m1.pdf',
        'file_name' => 'm1.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
        'school_year_id' => $sy2026->id,
    ]);

    $mat2 = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Material 2',
        'subject' => 'Math',
        'file_path' => 'teaching-materials/m2.pdf',
        'file_name' => 'm2.pdf',
        'file_size' => 1024,
        'file_type' => 'pdf',
        'school_year_id' => $sy2026->id,
    ]);

    $game1 = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Algebra Game',
        'subject' => 'Math',
        'type' => 'flashcard',
        'xp_reward' => 50,
        'school_year_id' => $sy2026->id,
    ]);

    $quiz1 = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Algebra Quiz',
        'grade' => 'Grade 10',
        'school_year_id' => $sy2026->id,
    ]);

    // 1. Move only selected items (mat1 and game1) to 2027-2028
    $selectedRes = $this->actingAs($this->teacher)->post(route('teacher.school-years.transfer'), [
        'mode' => 'selected',
        'source_school_year_id' => $sy2026->id,
        'target_school_year_id' => $sy2027->id,
        'items' => [
            ['type' => 'material', 'id' => $mat1->id],
            ['type' => 'game', 'id' => $game1->id],
        ],
    ]);
    $selectedRes->assertSessionHasNoErrors();

    expect($mat1->fresh()->school_year_id)->toBe($sy2027->id);
    expect($game1->fresh()->school_year_id)->toBe($sy2027->id);
    // mat2 and quiz1 remain in 2026-2027
    expect($mat2->fresh()->school_year_id)->toBe($sy2026->id);
    expect($quiz1->fresh()->school_year_id)->toBe($sy2026->id);

    // 2. Move ALL remaining items from 2026-2027 to 2027-2028
    $moveAllRes = $this->actingAs($this->teacher)->post(route('teacher.school-years.transfer'), [
        'mode' => 'all',
        'source_school_year_id' => $sy2026->id,
        'target_school_year_id' => $sy2027->id,
        'categories' => ['materials', 'games', 'quizzes', 'seatworks', 'practicals', 'exams'],
    ]);
    $moveAllRes->assertSessionHasNoErrors();

    expect($mat2->fresh()->school_year_id)->toBe($sy2027->id);
    expect($quiz1->fresh()->school_year_id)->toBe($sy2027->id);
});
