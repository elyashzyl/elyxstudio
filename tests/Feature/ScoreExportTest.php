<?php

use App\Models\GradeLevel;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $gradeLevel = GradeLevel::create(['name' => 'Grade 10']);
    $this->section = Section::create([
        'name' => 'Diamond',
        'grade_level_id' => $gradeLevel->id,
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'section_id' => $this->section->id,
        'email_verified_at' => now(),
    ]);

    $this->student->teachers()->attach($this->teacher->id);

    $this->quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Biology Chapter 1',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    QuizAttempt::create([
        'quiz_id' => $this->quiz->id,
        'student_id' => $this->student->id,
        'score' => 18,
        'total_points' => 20,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
});

test('teacher can export gradebook scores as CSV', function () {
    $response = $this->actingAs($this->teacher)->get(route('teacher.students.export-scores'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    
    $content = $response->streamedContent();
    expect($content)->toContain('Student Name')
        ->and($content)->toContain('Activity Title')
        ->and($content)->toContain($this->student->name)
        ->and($content)->toContain('Biology Chapter 1')
        ->and($content)->toContain('18')
        ->and($content)->toContain('20');
});

test('teacher can export scores for a single student', function () {
    $response = $this->actingAs($this->teacher)->get(route('teacher.students.export-scores', [
        'student_id' => $this->student->id,
    ]));

    $response->assertOk();
    $content = $response->streamedContent();
    expect($content)->toContain($this->student->name)
        ->and($content)->toContain('Biology Chapter 1');
});

test('student cannot export teacher scores', function () {
    $response = $this->actingAs($this->student)->get(route('teacher.students.export-scores'));

    $response->assertRedirect(route('student.dashboard'));
});

test('teacher can export student report card as PDF', function () {
    $response = $this->actingAs($this->teacher)->get(route('teacher.students.export-report-card-pdf', [
        'student_id' => $this->student->id,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('report_card_');
    expect($response->getContent())->toBeString()->and(strlen($response->getContent()))->toBeGreaterThan(100);
});

test('teacher can export class batch report cards as PDF', function () {
    $response = $this->actingAs($this->teacher)->get(route('teacher.students.export-report-card-pdf', [
        'grade' => 'Grade 10',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toBeString()->and(strlen($response->getContent()))->toBeGreaterThan(100);
});

test('student cannot export teacher report cards as PDF', function () {
    $response = $this->actingAs($this->student)->get(route('teacher.students.export-report-card-pdf'));

    $response->assertRedirect(route('student.dashboard'));
});

test('teacher export fails with 422 if batch student count exceeds 60', function () {
    $bulkStudents = User::factory()->count(65)->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'section_id' => $this->section->id,
    ]);
    $this->teacher->students()->attach($bulkStudents->pluck('id'));

    $response = $this->actingAs($this->teacher)->get(route('teacher.students.export-report-card-pdf', [
        'grade' => 'Grade 10',
    ]));

    $response->assertStatus(422);
});

test('teacher can export student report card with many activities without memory exhaustion', function () {
    for ($i = 1; $i <= 30; $i++) {
        $quiz = Quiz::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Biology Lesson ' . $i,
            'grade' => 'Grade 10',
            'is_published' => true,
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $this->student->id,
            'score' => 15 + ($i % 5),
            'total_points' => 20,
            'status' => 'submitted',
            'submitted_at' => now()->subDays($i),
        ]);
    }

    $response = $this->actingAs($this->teacher)->get(route('teacher.students.export-report-card-pdf', [
        'student_id' => $this->student->id,
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->getContent())->toBeString()->and(strlen($response->getContent()))->toBeGreaterThan(1000);
});

test('teacher dashboard includes visual analytics pass rate and completion metrics', function () {
    $response = $this->actingAs($this->teacher)->get(route('teacher.dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/Dashboard')
        ->has('passRateMetrics')
        ->has('completionMetrics')
        ->where('passRateMetrics.totalGraded', 1)
        ->where('passRateMetrics.passedCount', 1)
        ->where('passRateMetrics.passRate', 100)
        ->has('completionMetrics.byType')
    );
});
