<?php

use App\Models\GradeLevel;
use App\Models\Poll;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create([
        'name' => 'Grade 10',
        'display_order' => 1,
    ]);
    $this->section = Section::create([
        'name' => 'Section A',
        'grade_level_id' => $this->gradeLevel->id,
    ]);
    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);
    $this->otherTeacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);
    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);
});

test('teacher can create and manage a targeted classroom poll', function () {
    $this->actingAs($this->teacher);

    $create = $this->post('/teacher/polls', [
        'question' => 'Which topic should we review?',
        'options' => ['Cell structure', 'Genetics'],
        'target_grade_level_id' => $this->gradeLevel->id,
        'target_section_id' => $this->section->id,
        'is_active' => true,
    ]);

    $create->assertRedirect();

    $poll = Poll::query()->firstOrFail();
    expect($poll->teacher_id)->toBe($this->teacher->id);
    expect($poll->options)->toBe(['Cell structure', 'Genetics']);
    expect($poll->target_grade_level_id)->toBe($this->gradeLevel->id);
    expect($poll->target_section_id)->toBe($this->section->id);
    expect($poll->is_active)->toBeTrue();

    $this->get('/teacher/polls')->assertOk();

    $this->patch("/teacher/polls/{$poll->id}/toggle-active")
        ->assertRedirect();
    expect($poll->fresh()->is_active)->toBeFalse();

    $this->patch("/teacher/polls/{$poll->id}/toggle-active")
        ->assertRedirect();
    expect($poll->fresh()->is_active)->toBeTrue();

    $this->delete("/teacher/polls/{$poll->id}")
        ->assertRedirect();
    expect(Poll::find($poll->id))->toBeNull();
});

test('teacher cannot manage another teachers poll but superadmin can', function () {
    $poll = Poll::create([
        'teacher_id' => $this->otherTeacher->id,
        'question' => 'Other teacher poll',
        'options' => ['Yes', 'No'],
        'is_active' => true,
    ]);

    $this->actingAs($this->teacher);
    $this->patch("/teacher/polls/{$poll->id}/toggle-active")->assertForbidden();
    $this->delete("/teacher/polls/{$poll->id}")->assertForbidden();

    $this->actingAs($this->superadmin);
    $this->get('/teacher/polls')->assertOk();
    $this->patch("/teacher/polls/{$poll->id}/toggle-active")->assertRedirect();
    expect($poll->fresh()->is_active)->toBeFalse();
});

test('poll creation validates question and at least two non-empty options', function () {
    $this->actingAs($this->teacher);

    $this->post('/teacher/polls', [
        'question' => '',
        'options' => ['Only one'],
    ])->assertSessionHasErrors(['question', 'options']);

    $this->post('/teacher/polls', [
        'question' => 'Valid question',
        'options' => ['First', '   '],
    ])->assertSessionHasErrors('options.1');
});
