<?php

use App\Enums\ActivityType;
use App\Enums\GameType;
use App\Models\Game;
use App\Models\GameProgress;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentPoint;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1]);
    $this->section = Section::create(['name' => 'Section A', 'grade_level_id' => $this->gradeLevel->id]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->subject = Subject::create(['name' => 'Computer Studies', 'is_active' => true]);
    $this->subject->teachers()->sync([$this->teacher->id]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $this->section->id,
        'email_verified_at' => now(),
        'total_points' => 50,
    ]);

    // Give student initial points record
    StudentPoint::create([
        'student_id' => $this->student->id,
        'activity_type' => 'Manual',
        'activity_id' => 0,
        'points' => 50,
        'score' => 50,
        'total' => 50,
    ]);

    $this->game = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'HTML Elements Crossword',
        'subject' => 'Computer Studies',
        'subject_id' => $this->subject->id,
        'grade' => 'Grade 10',
        'xp_reward' => 20,
        'type' => GameType::Crossword->value,
    ]);

    $this->game->cards()->createMany([
        ['question' => 'Heading tag', 'answer' => 'HONE', 'order' => 0],
        ['question' => 'Anchor tag', 'answer' => 'LINK', 'order' => 1],
    ]);
});

test('using a hint deducts xp and increments hints_used count', function () {
    $this->actingAs($this->student);

    $response = $this->postJson("/student/games/{$this->game->id}/hint");

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'cost' => 2,
            'hints_used' => 1,
            'total_points' => 48,
        ]);

    $progress = GameProgress::where('user_id', $this->student->id)
        ->where('game_id', $this->game->id)
        ->first();

    expect($progress)->not->toBeNull();
    expect($progress->hints_used)->toBe(1);

    $deduction = StudentPoint::where('student_id', $this->student->id)
        ->where('activity_type', ActivityType::Deduction->value)
        ->where('activity_id', $this->game->id)
        ->first();

    expect($deduction)->not->toBeNull();
    expect($deduction->points)->toBe(-2);
    expect($this->student->fresh()->total_points)->toBe(48);
});

test('using multiple hints deducts xp incrementally and tracks count', function () {
    $this->actingAs($this->student);

    $this->postJson("/student/games/{$this->game->id}/hint")->assertOk();
    $this->postJson("/student/games/{$this->game->id}/hint")->assertOk();
    $this->postJson("/student/games/{$this->game->id}/hint")->assertOk();

    $progress = GameProgress::where('user_id', $this->student->id)
        ->where('game_id', $this->game->id)
        ->first();

    expect($progress->hints_used)->toBe(3);
    expect($this->student->fresh()->total_points)->toBe(44);

    $deductions = StudentPoint::where('student_id', $this->student->id)
        ->where('activity_type', ActivityType::Deduction->value)
        ->where('activity_id', $this->game->id)
        ->get();

    expect($deductions)->toHaveCount(3);
});

test('hint deduction respects custom configured hint cost setting', function () {
    Setting::set('hint_xp_cost', 5);

    $this->actingAs($this->student);

    $response = $this->postJson("/student/games/{$this->game->id}/hint");

    $response->assertOk()
        ->assertJson([
            'ok' => true,
            'cost' => 5,
            'hints_used' => 1,
            'total_points' => 45,
        ]);

    expect($this->student->fresh()->total_points)->toBe(45);
});

test('hints cannot be used in live arena games', function () {
    $liveGame = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Live Match',
        'subject' => 'Computer Studies',
        'grade' => 'Grade 10',
        'xp_reward' => 50,
        'type' => GameType::SpeedQuiz->value,
        'is_live' => true,
    ]);

    $this->actingAs($this->student);

    $response = $this->postJson("/student/games/{$liveGame->id}/hint");
    $response->assertStatus(403);
});

test('student total points does not drop below zero when using hints with low xp', function () {
    $poorStudent = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $this->section->id,
        'total_points' => 1,
    ]);

    StudentPoint::create([
        'student_id' => $poorStudent->id,
        'activity_type' => 'Manual',
        'activity_id' => 0,
        'points' => 1,
    ]);

    $this->actingAs($poorStudent);

    $response = $this->postJson("/student/games/{$this->game->id}/hint");
    $response->assertOk();

    expect($poorStudent->fresh()->total_points)->toBe(0);
});

test('student games index exposes hint cost to frontend', function () {
    $this->actingAs($this->student);

    $response = $this->get('/student/games');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->has('hintCost'));
});
