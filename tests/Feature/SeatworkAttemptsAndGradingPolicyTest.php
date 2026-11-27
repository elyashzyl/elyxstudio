<?php

use App\Enums\AttemptStatus;
use App\Models\GradeLevel;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\SeatworkQuestion;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentPoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create(['name' => 'Grade 10']);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->gradeLevel->id,
        'email_verified_at' => now(),
    ]);

    $this->student->teachers()->attach($this->teacher->id);

    $this->seatwork = Seatwork::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Biology Seatwork',
        'grade' => 'Grade 10',
        'is_published' => true,
        'max_attempts' => 3,
        'grading_policy' => 'first',
    ]);
    $this->seatwork->gradeLevels()->attach($this->gradeLevel->id);

    // Add 2 questions of 10 points each (total 20 points)
    $this->q1 = SeatworkQuestion::create([
        'seatwork_id' => $this->seatwork->id,
        'type' => 'identification',
        'question_text' => 'What is the powerhouse of the cell?',
        'points' => 10,
        'order' => 1,
    ]);
    $this->q1->options()->create(['option_text' => 'mitochondria', 'is_correct' => true]);

    $this->q2 = SeatworkQuestion::create([
        'seatwork_id' => $this->seatwork->id,
        'type' => 'identification',
        'question_text' => 'What is the control center of the cell?',
        'points' => 10,
        'order' => 2,
    ]);
    $this->q2->options()->create(['option_text' => 'nucleus', 'is_correct' => true]);
});

test('first policy keeps attempt 1 as recorded grade even if attempt 2 has higher score', function () {
    $this->actingAs($this->student);

    // Attempt 1: submit answer for q1 only (10/20)
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [
            $this->q1->id => 'mitochondria',
            $this->q2->id => 'wrong',
        ],
    ])->assertRedirect();

    $att1 = SeatworkAttempt::where('seatwork_id', $this->seatwork->id)
        ->where('student_id', $this->student->id)
        ->where('attempt_number', 1)
        ->first();

    expect($att1)->not->toBeNull()
        ->and($att1->score)->toBe(10)
        ->and((bool) $att1->is_recorded)->toBeTrue();

    // Attempt 2: submit answers for both (20/20)
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [
            $this->q1->id => 'mitochondria',
            $this->q2->id => 'nucleus',
        ],
    ])->assertRedirect();

    $att1->refresh();
    $att2 = SeatworkAttempt::where('seatwork_id', $this->seatwork->id)
        ->where('student_id', $this->student->id)
        ->where('attempt_number', 2)
        ->first();

    expect($att2)->not->toBeNull()
        ->and($att2->score)->toBe(20)
        ->and((bool) $att1->is_recorded)->toBeTrue()
        ->and((bool) $att2->is_recorded)->toBeFalse();
});

test('second policy switches recorded to attempt 2 once submitted', function () {
    $this->seatwork->update(['grading_policy' => 'second']);
    $this->actingAs($this->student);

    // Attempt 1 (20/20)
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [
            $this->q1->id => 'mitochondria',
            $this->q2->id => 'nucleus',
        ],
    ]);

    $att1 = SeatworkAttempt::where('attempt_number', 1)->first();
    // Fallback before attempt 2 exists: attempt 1 is recorded
    expect((bool) $att1->is_recorded)->toBeTrue();

    // Attempt 2 (10/20)
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [
            $this->q1->id => 'mitochondria',
            $this->q2->id => 'wrong',
        ],
    ]);

    $att1->refresh();
    $att2 = SeatworkAttempt::where('attempt_number', 2)->first();

    expect((bool) $att1->is_recorded)->toBeFalse()
        ->and((bool) $att2->is_recorded)->toBeTrue();
});

test('third policy switches recorded to attempt 3 once submitted', function () {
    $this->seatwork->update(['grading_policy' => 'third']);
    $this->actingAs($this->student);

    // Attempt 1
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'wrong', $this->q2->id => 'wrong'],
    ]);

    // Attempt 2
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'wrong'],
    ]);

    $att2 = SeatworkAttempt::where('attempt_number', 2)->first();
    expect((bool) $att2->is_recorded)->toBeTrue();

    // Attempt 3
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    $att1 = SeatworkAttempt::where('attempt_number', 1)->first();
    $att2->refresh();
    $att3 = SeatworkAttempt::where('attempt_number', 3)->first();

    expect((bool) $att1->is_recorded)->toBeFalse()
        ->and((bool) $att2->is_recorded)->toBeFalse()
        ->and((bool) $att3->is_recorded)->toBeTrue();
});

test('highest policy records attempt with maximum score', function () {
    $this->seatwork->update(['grading_policy' => 'highest']);
    $this->actingAs($this->student);

    // Attempt 1: 10 pts
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'wrong'],
    ]);

    // Attempt 2: 20 pts
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    // Attempt 3: 0 pts
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'wrong', $this->q2->id => 'wrong'],
    ]);

    $att1 = SeatworkAttempt::where('attempt_number', 1)->first();
    $att2 = SeatworkAttempt::where('attempt_number', 2)->first();
    $att3 = SeatworkAttempt::where('attempt_number', 3)->first();

    expect((bool) $att1->is_recorded)->toBeFalse()
        ->and((bool) $att2->is_recorded)->toBeTrue()
        ->and((bool) $att3->is_recorded)->toBeFalse();
});

test('latest policy records the newest attempt', function () {
    $this->seatwork->update(['grading_policy' => 'latest']);
    $this->actingAs($this->student);

    // Attempt 1
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    // Attempt 2
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'wrong', $this->q2->id => 'wrong'],
    ]);

    $att1 = SeatworkAttempt::where('attempt_number', 1)->first();
    $att2 = SeatworkAttempt::where('attempt_number', 2)->first();

    expect((bool) $att1->is_recorded)->toBeFalse()
        ->and((bool) $att2->is_recorded)->toBeTrue();
});

test('teacher can manually set an attempt as recorded grade', function () {
    $this->actingAs($this->student);

    // Submit attempt 1 (10 pts)
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'wrong'],
    ]);

    // Submit attempt 2 (20 pts)
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    $att1 = SeatworkAttempt::where('attempt_number', 1)->first();
    $att2 = SeatworkAttempt::where('attempt_number', 2)->first();

    // Teacher sets attempt 2 as recorded
    $this->actingAs($this->teacher);
    $this->post(route('teacher.seatworks.attempts.record', [$this->seatwork, $att2]))
        ->assertRedirect();

    $att1->refresh();
    $att2->refresh();
    $this->seatwork->refresh();

    expect((bool) $att1->is_recorded)->toBeFalse()
        ->and((bool) $att2->is_recorded)->toBeTrue()
        ->and($this->seatwork->grading_policy)->toBe('manual');
});

test('student cannot take seatwork beyond max_attempts', function () {
    $this->seatwork->update(['max_attempts' => 2]);
    $this->actingAs($this->student);

    // Attempt 1
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    // Attempt 2
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    // Attempt 3 request should redirect to results with limit reached
    $response = $this->get(route('student.seatworks.take', $this->seatwork));
    $response->assertRedirect(route('student.seatworks.result', ['attempt' => 1]));

    expect(SeatworkAttempt::where('seatwork_id', $this->seatwork->id)->count())->toBe(2);
});

test('changing seatwork policy re-evaluates all recorded attempts for students', function () {
    $this->actingAs($this->student);

    // Attempt 1: 10 pts
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'wrong'],
    ]);

    // Attempt 2: 20 pts
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    $att1 = SeatworkAttempt::where('attempt_number', 1)->first();
    $att2 = SeatworkAttempt::where('attempt_number', 2)->first();
    expect((bool) $att1->is_recorded)->toBeTrue();

    // Teacher changes policy to 'second'
    $this->actingAs($this->teacher);
    $this->put(route('teacher.seatworks.update', $this->seatwork), [
        'title' => $this->seatwork->title,
        'instructions' => $this->seatwork->instructions,
        'grade' => $this->seatwork->grade,
        'grade_level_ids' => [$this->gradeLevel->id],
        'time_limit_minutes' => $this->seatwork->time_limit_minutes,
        'max_attempts' => 3,
        'grading_policy' => 'second',
        'questions' => [
            [
                'id' => $this->q1->id,
                'type' => 'identification',
                'question_text' => $this->q1->question_text,
                'points' => 10,
                'order' => 1,
                'options' => [['option_text' => 'mitochondria', 'is_correct' => true]],
            ],
            [
                'id' => $this->q2->id,
                'type' => 'identification',
                'question_text' => $this->q2->question_text,
                'points' => 10,
                'order' => 2,
                'options' => [['option_text' => 'nucleus', 'is_correct' => true]],
            ],
        ],
    ])->assertRedirect();

    $att1->refresh();
    $att2->refresh();

    expect((bool) $att1->is_recorded)->toBeFalse()
        ->and((bool) $att2->is_recorded)->toBeTrue();
});

test('finished seatwork remains visible on student seatworks index even when closed', function () {
    $this->actingAs($this->student);

    // Submit an attempt
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    // Close the seatwork
    $this->seatwork->update(['closes_at' => now()->subDay()]);

    $response = $this->get(route('student.seatworks.index'));
    $response->assertOk();

    $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
        ->component('Student/Seatworks/Index')
        ->has('seatworks', 1)
        ->where('seatworks.0.id', $this->seatwork->id)
        ->where('seatworks.0.status', 'submitted')
        ->where('seatworks.0.score', 20)
        ->where('seatworks.0.is_closed', true)
        ->where('seatworks.0.can_retake', false)
        ->has('seatworks.0.result_attempt_id')
    );
});

test('visiting take on closed seatwork redirects to existing result', function () {
    $this->actingAs($this->student);

    // Submit attempt
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria'],
    ]);
    $attempt = SeatworkAttempt::where('seatwork_id', $this->seatwork->id)->first();

    // Close seatwork
    $this->seatwork->update(['closes_at' => now()->subHour()]);

    $response = $this->get(route('student.seatworks.take', $this->seatwork));
    $response->assertRedirect(route('student.seatworks.result', ['attempt' => $attempt->id]));
});

test('submitting seatwork returns 303 redirect to result', function () {
    $this->actingAs($this->student);

    $this->get(route('student.seatworks.take', $this->seatwork));
    $response = $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    $attempt = SeatworkAttempt::where('seatwork_id', $this->seatwork->id)->latest('id')->first();
    $response->assertStatus(303);
    $response->assertRedirect(route('student.seatworks.result', ['attempt' => $attempt->id]));
});

test('student seatwork submission grades locally without AI and leaves essays pending', function () {
    Setting::set('ai_provider', '9router');
    Setting::set('ninerouter_api_key', 'test-key');
    Setting::set('ninerouter_base_url', 'https://ai.example.test/v1');
    Http::fake();

    $enum = $this->seatwork->questions()->create([
        'type' => 'enumeration',
        'question_text' => 'Name two cell structures.',
        'points' => 10,
        'order' => 3,
    ]);
    $enum->options()->create(['option_text' => 'cell membrane', 'order' => 0]);
    $enum->options()->create(['option_text' => 'cytoplasm', 'order' => 1]);

    $essay = $this->seatwork->questions()->create([
        'type' => 'essay',
        'question_text' => 'Explain the function of the cell membrane.',
        'criteria' => 'Must explain selective transport.',
        'points' => 10,
        'order' => 4,
    ]);

    $this->actingAs($this->student);
    $this->get(route('student.seatworks.take', $this->seatwork));
    $response = $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [
            $this->q1->id => 'mitochondria',
            $this->q2->id => 'nucleus',
            $enum->id => "1. cell membrane\n2. cytoplasm",
            $essay->id => 'It controls what enters and leaves the cell.',
        ],
    ]);

    $attempt = SeatworkAttempt::where('seatwork_id', $this->seatwork->id)->latest('id')->first();
    $response->assertStatus(303);
    Http::assertNothingSent();

    expect($attempt->score)->toBe(30)
        ->and($attempt->answers()->where('question_id', $enum->id)->value('points_awarded'))->toBe(10)
        ->and($attempt->answers()->where('question_id', $essay->id)->value('is_correct'))->toBeFalse()
        ->and($attempt->answers()->where('question_id', $essay->id)->value('points_awarded'))->toBe(0)
        ->and($attempt->answers()->where('question_id', $essay->id)->value('feedback'))->toBe('Awaiting teacher review.');
});

test('teacher seatwork show view loads attempts with student section and provides sections list', function () {
    $sectionA = Section::create([
        'grade_level_id' => $this->gradeLevel->id,
        'name' => 'Section Emerald',
        'is_active' => true,
    ]);

    $this->student->update(['section_id' => $sectionA->id]);

    $this->actingAs($this->student);
    $this->get(route('student.seatworks.take', $this->seatwork));
    $this->post(route('student.seatworks.submit', $this->seatwork), [
        'answers' => [$this->q1->id => 'mitochondria', $this->q2->id => 'nucleus'],
    ]);

    $this->actingAs($this->teacher);
    $response = $this->get(route('teacher.seatworks.show', $this->seatwork));
    $response->assertOk();

    $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
        ->component('Teacher/Seatworks/Show')
        ->has('attempts', 1)
        ->has('sections')
        ->where('attempts.0.student.section_id', $sectionA->id)
        ->where('attempts.0.student.section.name', 'Section Emerald')
    );
});
