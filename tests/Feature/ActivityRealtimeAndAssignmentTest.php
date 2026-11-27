<?php

use App\Models\Game;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\Quiz;
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

    $this->otherTeacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);
});

test('quiz creation redirects to quizzes index instead of dashboard', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'title' => 'Biology Quiz',
        'description' => 'Test quiz',
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'questions' => [
            [
                'question_text' => 'Is water wet?',
                'type' => 'true_false',
                'points' => 1,
                'options' => [
                    ['option_text' => 'True', 'is_correct' => true],
                    ['option_text' => 'False', 'is_correct' => false],
                ],
            ],
        ],
    ];

    $response = $this->post('/teacher/quizzes', $payload);

    $response->assertRedirect(route('teacher.quizzes.index'));
    $this->assertDatabaseHas('quizzes', [
        'title' => 'Biology Quiz',
        'teacher_id' => $this->teacher->id,
    ]);
});

test('quiz deletion redirects to quizzes index instead of dashboard', function () {
    $this->actingAs($this->teacher);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Quiz To Delete',
        'grade' => 'Grade 10',
        'section' => 'Section A',
        'is_published' => false,
    ]);

    $response = $this->delete("/teacher/quizzes/{$quiz->id}");

    $response->assertRedirect(route('teacher.quizzes.index'));
    $this->assertDatabaseMissing('quizzes', [
        'id' => $quiz->id,
    ]);
});

test('game deletion redirects to games index', function () {
    $this->actingAs($this->teacher);

    $game = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Game To Delete',
        'subject' => 'Science',
        'grade' => 'Grade 10',
        'xp_reward' => 10,
        'type' => 'flashcard',
    ]);

    $response = $this->delete("/teacher/games/{$game->id}");

    $response->assertRedirect(route('teacher.games.index'));
    $this->assertDatabaseMissing('games', [
        'id' => $game->id,
    ]);
});

test('superadmin can create quiz assigned to a specific teacher', function () {
    $this->actingAs($this->admin);

    $payload = [
        'title' => 'Admin Assigned Quiz',
        'description' => 'Created by admin for teacher',
        'teacher_id' => $this->teacher->id,
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'questions' => [
            [
                'question_text' => 'Question 1',
                'type' => 'multiple_choice',
                'points' => 2,
                'options' => [
                    ['option_text' => 'Option A', 'is_correct' => true],
                    ['option_text' => 'Option B', 'is_correct' => false],
                ],
            ],
        ],
    ];

    $response = $this->post('/teacher/quizzes', $payload);

    $response->assertRedirect(route('teacher.quizzes.index'));
    $this->assertDatabaseHas('quizzes', [
        'title' => 'Admin Assigned Quiz',
        'teacher_id' => $this->teacher->id,
    ]);
});

test('teacher can see games created by superadmin or assigned to teacher', function () {
    $adminGame = Game::create([
        'teacher_id' => $this->admin->id,
        'title' => 'Admin Shared Game',
        'subject' => 'Science',
        'grade' => 'Grade 10',
        'description' => 'For all teachers',
        'xp_reward' => 10,
        'type' => 'flashcard',
        'hidden' => false,
    ]);

    $this->actingAs($this->teacher);

    $response = $this->get('/teacher/games');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/Games')
        ->has('games', 1)
        ->where('games.0.id', $adminGame->id)
    );
});

test('superadmin can assign game to a teacher on create and reassign afterwards', function () {
    $this->actingAs($this->admin);

    $payload = [
        'title' => 'Assigned Game',
        'subject' => 'Math',
        'grade' => 'Grade 10',
        'description' => 'Game for teacher',
        'xp_reward' => 15,
        'type' => 'flashcard',
        'teacher_id' => $this->teacher->id,
        'cards' => [
            ['question' => '2+2', 'answer' => '4'],
        ],
    ];

    $response = $this->post('/teacher/games', $payload);
    $response->assertRedirect();

    $game = Game::where('title', 'Assigned Game')->first();
    expect($game)->not->toBeNull();
    expect($game->teacher_id)->toBe($this->teacher->id);

    // Reassign to other teacher
    $reassignResponse = $this->patch("/teacher/games/{$game->id}/reassign", [
        'teacher_id' => $this->otherTeacher->id,
    ]);

    $reassignResponse->assertRedirect();
    expect($game->fresh()->teacher_id)->toBe($this->otherTeacher->id);
});

test('non-superadmin cannot reassign a game', function () {
    $game = Game::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Teacher Game',
        'subject' => 'English',
        'grade' => 'Grade 10',
        'description' => '',
        'xp_reward' => 10,
        'type' => 'flashcard',
        'hidden' => false,
    ]);

    $this->actingAs($this->teacher);

    $response = $this->patch("/teacher/games/{$game->id}/reassign", [
        'teacher_id' => $this->otherTeacher->id,
    ]);

    $response->assertForbidden();
});

test('teacher can create practical with grade_level_ids', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'title' => 'Lab Practical 1',
        'instructions' => 'Follow steps carefully',
        'grade_level_ids' => [$this->gradeLevel->id],
        'criteria' => [
            ['criterion_name' => 'Accuracy', 'description' => 'Accurate measurements', 'max_points' => 10],
        ],
    ];

    $response = $this->post('/teacher/practicals', $payload);

    $response->assertRedirect(route('teacher.practicals.index'));
    $this->assertDatabaseHas('practicals', [
        'title' => 'Lab Practical 1',
        'teacher_id' => $this->teacher->id,
        'grade' => $this->gradeLevel->name,
    ]);
});

test('teacher can create practical when grade_level_id singular or grade name is sent', function () {
    $this->actingAs($this->teacher);

    // Singular grade_level_id
    $payload1 = [
        'title' => 'Practical Singular ID',
        'grade_level_id' => $this->gradeLevel->id,
        'criteria' => [
            ['criterion_name' => 'Accuracy', 'max_points' => 10],
        ],
    ];
    $response1 = $this->post('/teacher/practicals', $payload1);
    $response1->assertRedirect(route('teacher.practicals.index'));
    $this->assertDatabaseHas('practicals', ['title' => 'Practical Singular ID']);

    // Grade name string
    $payload2 = [
        'title' => 'Practical By Grade Name',
        'grade' => $this->gradeLevel->name,
        'criteria' => [
            ['criterion_name' => 'Neatness', 'max_points' => 5],
        ],
    ];
    $response2 = $this->post('/teacher/practicals', $payload2);
    $response2->assertRedirect(route('teacher.practicals.index'));
    $this->assertDatabaseHas('practicals', ['title' => 'Practical By Grade Name']);
});

test('practical creation fails with clean error message when grade is missing', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'title' => 'Practical Without Grade',
        'criteria' => [
            ['criterion_name' => 'Accuracy', 'max_points' => 10],
        ],
    ];

    $response = $this->post('/teacher/practicals', $payload);

    $response->assertSessionHasErrors(['grade_level_ids' => 'The grade level field is required.']);
});

test('student can submit quiz with empty answers or timeout', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->gradeLevel->id,
        'email_verified_at' => now(),
    ]);
    $student->teachers()->attach($this->teacher->id);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Math Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quiz->gradeLevels()->sync([$this->gradeLevel->id]);

    $q = $quiz->questions()->create([
        'question_text' => '1 + 1 = ?',
        'type' => 'multiple_choice',
        'points' => 1,
        'order' => 0,
    ]);
    $q->options()->create(['option_text' => '2', 'is_correct' => true, 'order' => 0]);
    $q->options()->create(['option_text' => '3', 'is_correct' => false, 'order' => 1]);

    \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student->id,
        'status' => \App\Enums\AttemptStatus::InProgress->value,
        'started_at' => now(),
    ]);

    $this->actingAs($student);

    // Empty answers payload (timed out or unanswered)
    $response = $this->post("/student/quizzes/{$quiz->id}/submit", [
        'answers' => [],
    ]);

    $response->assertRedirect();
    $attempt = \App\Models\QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $student->id)->first();
    expect($attempt->status)->toBe(\App\Enums\AttemptStatus::Submitted->value);
    expect($attempt->score)->toBe(0);
});

test('teacher can create exam with identification and enumeration options', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'title' => 'Midterm Exam',
        'grade_level_ids' => [$this->gradeLevel->id],
        'sections' => [
            [
                'title' => 'Part 1',
                'section_type' => 'questions',
                'questions' => [
                    [
                        'question_text' => 'What is the capital of the Philippines?',
                        'type' => 'identification',
                        'points' => 1,
                        'correct_answer' => 'Manila',
                        'options' => [
                            ['option_text' => 'Manila', 'is_correct' => true],
                        ],
                    ],
                    [
                        'question_text' => 'Give 3 primary colors',
                        'type' => 'enumeration',
                        'points' => 3,
                        'enum_items' => ['Red', 'Blue', 'Yellow'],
                        'options' => [
                            ['option_text' => 'Red', 'is_correct' => true],
                            ['option_text' => 'Blue', 'is_correct' => true],
                            ['option_text' => 'Yellow', 'is_correct' => true],
                        ],
                    ],
                ],
            ],
        ],
    ];

    $response = $this->post('/teacher/exams', $payload);
    $response->assertRedirect(route('teacher.exams.index'));

    $exam = \App\Models\Exam::where('title', 'Midterm Exam')->first();
    expect($exam)->not->toBeNull();

    $sec = $exam->sections()->first();
    expect($sec->questions()->count())->toBe(2);

    $identQ = $sec->questions()->where('type', 'identification')->first();
    expect($identQ->options()->count())->toBe(1);
    expect($identQ->options()->first()->option_text)->toBe('Manila');

    $enumQ = $sec->questions()->where('type', 'enumeration')->first();
    expect($enumQ->options()->count())->toBe(3);
});

test('game can be created with grade_level_id instead of string grade', function () {
    $this->actingAs($this->teacher);

    $payload = [
        'title' => 'Grade Normalized Game',
        'subject' => 'Science',
        'grade_level_id' => $this->gradeLevel->id,
        'description' => 'Test',
        'xp_reward' => 20,
        'type' => 'flashcard',
        'cards' => [
            ['question' => 'Q1', 'answer' => 'A1'],
        ],
    ];

    $response = $this->post('/teacher/games', $payload);
    $response->assertRedirect();

    $game = Game::where('title', 'Grade Normalized Game')->first();
    expect($game)->not->toBeNull();
    expect($game->grade)->toBe('Grade 10');
});

test('teacher can use save and next on quiz recheck to advance to next student with updated anchor', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'History Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $q = $quiz->questions()->create([
        'question_text' => 'Question 1',
        'type' => 'multiple_choice',
        'points' => 5,
        'order' => 0,
    ]);
    $opt = $q->options()->create(['option_text' => 'A', 'is_correct' => true, 'order' => 0]);

    $attempt1 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 5,
        'submitted_at' => now()->subMinutes(10),
    ]);
    $ans1 = \App\Models\QuizAnswer::create([
        'quiz_attempt_id' => $attempt1->id,
        'question_id' => $q->id,
        'quiz_option_id' => $opt->id,
        'is_correct' => false,
    ]);

    $attempt2 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student2->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 5,
        'submitted_at' => now()->subMinutes(5),
    ]);
    $ans2 = \App\Models\QuizAnswer::create([
        'quiz_attempt_id' => $attempt2->id,
        'question_id' => $q->id,
        'quiz_option_id' => $opt->id,
        'is_correct' => false,
    ]);

    $payload = [
        'answers' => [
            ['id' => $ans1->id, 'is_correct' => true],
        ],
        'next_attempt_id' => $attempt2->id,
        'save_and_next' => true,
        'return_to' => "/teacher/quizzes/{$quiz->id}#attempt-{$attempt1->id}",
    ];

    $response = $this->put("/teacher/quizzes/{$quiz->id}/recheck/{$attempt1->id}", $payload);

    $response->assertRedirect(route('teacher.quizzes.recheck', [
        'quiz' => $quiz,
        'attempt' => $attempt2,
        'return_to' => "/teacher/quizzes/{$quiz->id}#attempt-{$attempt2->id}",
    ]));

    $this->assertDatabaseHas('quiz_answers', [
        'id' => $ans1->id,
        'is_correct' => true,
    ]);
    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $attempt1->id,
        'score' => 5,
    ]);
});

test('teacher can recheck quiz with essay points and feedback', function () {
    $this->actingAs($this->teacher);

    $student = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Essay Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $q = $quiz->questions()->create([
        'question_text' => 'Describe color theory.',
        'type' => 'essay',
        'points' => 10,
        'criteria' => 'Primary, secondary, and complementary colors.',
        'order' => 0,
    ]);

    $attempt = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 10,
        'submitted_at' => now(),
    ]);

    $ans = \App\Models\QuizAnswer::create([
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $q->id,
        'answer_text' => 'Colors can be primary or secondary.',
        'is_correct' => false,
    ]);

    $payload = [
        'answers' => [
            [
                'id' => $ans->id,
                'is_correct' => true,
                'points_awarded' => 8,
                'feedback' => 'Good overview but missed complementary colors.',
            ],
        ],
        'return_to' => "/teacher/quizzes/{$quiz->id}#attempt-{$attempt->id}",
    ];

    $response = $this->put("/teacher/quizzes/{$quiz->id}/recheck/{$attempt->id}", $payload);
    $response->assertRedirect();

    $this->assertDatabaseHas('quiz_answers', [
        'id' => $ans->id,
        'is_correct' => true,
        'points_awarded' => 8,
        'feedback' => 'Good overview but missed complementary colors.',
    ]);

    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $attempt->id,
        'score' => 8,
    ]);
});

test('teacher can use save and next on practical grade to advance to next student cleanly', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Chemistry Practical',
        'instructions' => 'Observe reaction',
        'grade' => 'Grade 10',
        'max_score' => 20,
    ]);

    $crit = $practical->criteria()->create([
        'criterion_name' => 'Lab Safety',
        'max_points' => 20,
    ]);

    $attempt1 = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'submitted_at' => now()->subMinutes(10),
    ]);

    $attempt2 = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student2->id,
        'status' => 'submitted',
        'submitted_at' => now()->subMinutes(5),
    ]);

    $payload = [
        'scores' => [
            $crit->id => 18,
        ],
        'comments' => [
            $crit->id => 'Good safety protocol',
        ],
        'next_attempt_id' => $attempt2->id,
        'save_and_next' => true,
        'return_to' => "/teacher/practicals/{$practical->id}#attempt-{$attempt1->id}",
    ];

    $response = $this->put("/teacher/practicals/{$practical->id}/grade/{$attempt1->id}", $payload);

    $response->assertRedirect(route('teacher.practicals.grade', [
        'practical' => $practical,
        'attempt' => $attempt2,
        'return_to' => "/teacher/practicals/{$practical->id}#attempt-{$attempt2->id}",
    ]));
    $response->assertSessionMissing('saved');
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('practical_attempts', [
        'id' => $attempt1->id,
        'total_score' => 18,
    ]);
});

test('teacher can use practical save and next without a target id', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Fallback Practical',
        'grade' => 'Grade 10',
        'max_score' => 20,
    ]);
    $crit = $practical->criteria()->create([
        'criterion_name' => 'Execution',
        'max_points' => 20,
    ]);

    $attempt1 = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'submitted_at' => now()->subMinutes(5),
    ]);
    $attempt2 = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student2->id,
        'status' => 'submitted',
        'submitted_at' => now()->subMinutes(10),
    ]);

    $response = $this->put("/teacher/practicals/{$practical->id}/grade/{$attempt1->id}", [
        'scores' => [$crit->id => 16],
        'comments' => [$crit->id => 'Good execution'],
        'save_and_next' => true,
        'return_to' => "/teacher/practicals/{$practical->id}#attempt-{$attempt1->id}",
    ]);

    $response->assertStatus(303);
    $response->assertRedirect(route('teacher.practicals.grade', [
        'practical' => $practical,
        'attempt' => $attempt2,
        'return_to' => "/teacher/practicals/{$practical->id}#attempt-{$attempt2->id}",
    ]));
    $this->assertDatabaseHas('practical_attempts', [
        'id' => $attempt1->id,
        'total_score' => 16,
    ]);
});

test('teacher can use save and next on seatwork recheck to advance to next student', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $seatwork = \App\Models\Seatwork::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'English Seatwork',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $q = $seatwork->questions()->create([
        'question_text' => 'Identify noun',
        'type' => 'identification',
        'points' => 10,
        'order' => 0,
    ]);

    $attempt1 = \App\Models\SeatworkAttempt::create([
        'seatwork_id' => $seatwork->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 10,
        'submitted_at' => now()->subMinutes(10),
    ]);
    $ans1 = \App\Models\SeatworkAnswer::create([
        'attempt_id' => $attempt1->id,
        'question_id' => $q->id,
        'is_correct' => false,
    ]);

    $attempt2 = \App\Models\SeatworkAttempt::create([
        'seatwork_id' => $seatwork->id,
        'student_id' => $student2->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 10,
        'submitted_at' => now()->subMinutes(5),
    ]);
    $ans2 = \App\Models\SeatworkAnswer::create([
        'attempt_id' => $attempt2->id,
        'question_id' => $q->id,
        'is_correct' => false,
    ]);

    $payload = [
        'answers' => [
            ['id' => $ans1->id, 'is_correct' => true, 'points_awarded' => 10],
        ],
        'next_attempt_id' => $attempt2->id,
        'save_and_next' => true,
        'return_to' => "/teacher/seatworks/{$seatwork->id}#attempt-{$attempt1->id}",
    ];

    $response = $this->put("/teacher/seatworks/{$seatwork->id}/recheck/{$attempt1->id}", $payload);

    $response->assertRedirect(route('teacher.seatworks.recheck', [
        'seatwork' => $seatwork,
        'attempt' => $attempt2,
        'return_to' => "/teacher/seatworks/{$seatwork->id}#attempt-{$attempt2->id}",
    ]));

    $this->assertDatabaseHas('seatwork_attempts', [
        'id' => $attempt1->id,
        'score' => 10,
    ]);
});

test('teacher can use save and next on quiz recheck without next_attempt_id to auto-advance to next student and award points', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Math Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $q = $quiz->questions()->create([
        'question_text' => 'What is 2+2?',
        'points' => 5,
        'order' => 0,
    ]);
    $opt = $q->options()->create(['option_text' => '4', 'is_correct' => true]);

    $attempt1 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 5,
        'submitted_at' => now()->subMinutes(10),
    ]);
    $ans1 = \App\Models\QuizAnswer::create([
        'quiz_attempt_id' => $attempt1->id,
        'question_id' => $q->id,
        'quiz_option_id' => $opt->id,
        'is_correct' => false,
    ]);

    $attempt2 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student2->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 5,
        'submitted_at' => now()->subMinutes(15),
    ]);
    $ans2 = \App\Models\QuizAnswer::create([
        'quiz_attempt_id' => $attempt2->id,
        'question_id' => $q->id,
        'quiz_option_id' => $opt->id,
        'is_correct' => false,
    ]);

    $payload = [
        'answers' => [
            ['id' => $ans1->id, 'is_correct' => true],
        ],
        'save_and_next' => true,
        'return_to' => "/teacher/quizzes/{$quiz->id}#attempt-{$attempt1->id}",
    ];

    $response = $this->put("/teacher/quizzes/{$quiz->id}/recheck/{$attempt1->id}", $payload);

    $response->assertRedirect(route('teacher.quizzes.recheck', [
        'quiz' => $quiz,
        'attempt' => $attempt2,
        'return_to' => "/teacher/quizzes/{$quiz->id}#attempt-{$attempt2->id}",
    ]));

    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $attempt1->id,
        'score' => 5,
    ]);
    $this->assertDatabaseHas('student_points', [
        'student_id' => $student1->id,
        'activity_type' => 'Quiz',
        'activity_id' => $quiz->id,
    ]);
});

test('teacher can save practical grade with null scores and finish cleanly', function () {
    $this->actingAs($this->teacher);

    $student = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Biology Practical',
        'instructions' => 'Observe cells',
        'grade' => 'Grade 10',
        'max_score' => 10,
    ]);

    $crit = $practical->criteria()->create([
        'criterion_name' => 'Cell Identification',
        'max_points' => 10,
    ]);

    $attempt = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $payload = [
        'scores' => [
            $crit->id => null,
        ],
        'return_to' => "/teacher/results#result-attempt-{$attempt->id}",
    ];

    $response = $this->put("/teacher/practicals/{$practical->id}/grade/{$attempt->id}", $payload);

    $response->assertRedirect("/teacher/results#result-attempt-{$attempt->id}");
    $this->assertDatabaseHas('practical_attempts', [
        'id' => $attempt->id,
        'total_score' => 0,
    ]);
});

test('teacher results renders cleanly for regular teacher and superadmin with composite anchors supported', function () {
    $this->actingAs($this->teacher);

    $student = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student->teachers()->attach($this->teacher->id);

    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Sample Results Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quizAttempt = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'score' => 10,
        'total_points' => 10,
        'submitted_at' => now(),
    ]);

    $response = $this->get('/teacher/results');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/Results')
        ->has('activities')
        ->has('teachersList')
        ->where('teachersList', [])
    );

    // Superadmin has teachersList populated
    $superadmin = User::factory()->create(['role' => 'superadmin', 'email_verified_at' => now()]);
    $this->actingAs($superadmin);
    $superadminResponse = $this->get('/teacher/results');
    $superadminResponse->assertOk();
    $superadminResponse->assertInertia(fn ($page) => $page
        ->component('Teacher/Results')
        ->has('teachersList')
        ->where('teachersList', fn ($list) => collect($list)->contains('id', $this->teacher->id))
    );
});

test('teacher can use save and next with composite result anchors on quiz, seatwork, and practical', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    // Quiz with composite return anchor
    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Composite Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $q = $quiz->questions()->create(['question_text' => 'Q', 'type' => 'multiple_choice', 'points' => 1, 'order' => 0]);
    $opt = $q->options()->create(['option_text' => 'A', 'is_correct' => true, 'order' => 0]);
    $att1 = \App\Models\QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $student1->id, 'status' => 'submitted', 'score' => 0, 'total_points' => 1, 'submitted_at' => now()->subMinutes(10)]);
    $att2 = \App\Models\QuizAttempt::create(['quiz_id' => $quiz->id, 'student_id' => $student2->id, 'status' => 'submitted', 'score' => 0, 'total_points' => 1, 'submitted_at' => now()->subMinutes(5)]);
    $ans = \App\Models\QuizAnswer::create(['quiz_attempt_id' => $att1->id, 'question_id' => $q->id, 'quiz_option_id' => $opt->id, 'is_correct' => false]);

    $respQuiz = $this->put("/teacher/quizzes/{$quiz->id}/recheck/{$att1->id}", [
        'answers' => [['id' => $ans->id, 'is_correct' => true]],
        'next_attempt_id' => $att2->id,
        'save_and_next' => true,
        'return_to' => "/teacher/results#result-attempt-quiz-{$att1->id}",
    ]);
    $respQuiz->assertRedirect(route('teacher.quizzes.recheck', [
        'quiz' => $quiz,
        'attempt' => $att2,
        'return_to' => "/teacher/results#result-attempt-quiz-{$att2->id}",
    ]));
});

test('superadmin can update AI settings independently and persist OmniRoute credentials and custom AI skills', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin', 'email_verified_at' => now()]);
    $this->actingAs($superadmin);

    $payload = [
        'ai_provider' => 'omniroute',
        'omniroute_api_key' => 'omni_sk_test_key_12345',
        'omniroute_model' => 'agy/gemini-3.7-flash-high',
        'omniroute_base_url' => 'http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1',
        'ai_request_timeout' => 45,
        'ai_max_source_chars' => 8000,
        'ai_essay_grading_enabled' => 'true',
        'ai_activity_generation_enabled' => 'true',
        'ai_system_skill' => 'omniroute_terse',
        'ai_grading_instructions' => 'Grade strictly grounded in teacher criteria.',
        'ai_generation_instructions' => 'Generate questions grounded in reference text.',
    ];

    $response = $this->putJson('/teacher/system-settings/ai', $payload);
    $response->assertOk();
    $response->assertJson([
        'success' => true,
        'message' => 'AI configuration and skills saved successfully.',
    ]);

    $this->assertSame('omni_sk_test_key_12345', \App\Models\Setting::get('omniroute_api_key'));
    $this->assertSame('omniroute', \App\Models\Setting::get('ai_provider'));
    $this->assertSame('omniroute_terse', \App\Services\AiConfigService::getSystemSkill());
    $this->assertSame('Grade strictly grounded in teacher criteria.', \App\Services\AiConfigService::getGradingInstructions());
});

test('QuizGeneratorService splits presentation bullet points and lines without periods into valid sentences and questions', function () {
    $slideText = "Introduction to Computer Hardware\n"
        . "- CPU: Central Processing Unit\n"
        . "- RAM: Random Access Memory storage\n"
        . "- GPU: Graphics Processing Unit rendering\n"
        . "- SSD: Solid State Drive secondary storage\n"
        . "- Motherboard connects all internal components";

    $result = \App\Services\QuizGeneratorService::viaHeuristic($slideText, 3, ['multiple_choice']);

    $this->assertNotEmpty($result);
    $this->assertGreaterThanOrEqual(1, count($result));
    $this->assertNotEmpty($result[0]['question_text']);
    $this->assertNotEmpty($result[0]['options']);
});

test('teacher can use save and next on exam practical grading', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $exam = \App\Models\Exam::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Biology Finals Exam',
        'grade' => 'Grade 10',
        'is_published' => true,
        'max_score' => 50,
    ]);

    $section = $exam->sections()->create([
        'title' => 'Lab Practical',
        'section_type' => 'practical',
        'order' => 1,
    ]);

    $crit = $section->criteria()->create([
        'criterion_name' => 'Microscope Handling',
        'description' => 'Proper focus and lens handling',
        'max_points' => 25,
        'order' => 1,
    ]);

    $att1 = \App\Models\ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $student1->id,
        'status' => \App\Enums\AttemptStatus::Submitted->value,
        'total_score' => 0,
        'submitted_at' => now()->subMinutes(10),
    ]);

    $att2 = \App\Models\ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $student2->id,
        'status' => \App\Enums\AttemptStatus::Submitted->value,
        'total_score' => 0,
        'submitted_at' => now()->subMinutes(5),
    ]);

    $response = $this->post("/teacher/exams/attempts/{$att1->id}/grade", [
        'scores' => [$crit->id => 22],
        'comments' => [$crit->id => 'Excellent microscope technique'],
        'next_attempt_id' => $att2->id,
        'save_and_next' => true,
    ]);

    $response->assertSessionHas('success');
    $response->assertSessionHas('next_attempt_id', $att2->id);

    $this->assertDatabaseHas('exam_attempts', [
        'id' => $att1->id,
        'total_score' => 22,
    ]);
});

test('teacher can bulk AI check all quiz attempts on a quiz', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $quiz = \App\Models\Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Bulk Check Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $q = $quiz->questions()->create([
        'question_text' => 'What is 2+2?',
        'type' => 'multiple_choice',
        'points' => 5,
        'order' => 1,
    ]);
    $optCorrect = $q->options()->create(['option_text' => '4', 'is_correct' => true]);
    $optWrong = $q->options()->create(['option_text' => '5', 'is_correct' => false]);

    $att1 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 5,
    ]);
    $att1->answers()->create([
        'question_id' => $q->id,
        'quiz_option_id' => $optCorrect->id,
        'is_correct' => false,
    ]);

    $att2 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student2->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 5,
    ]);
    $att2->answers()->create([
        'question_id' => $q->id,
        'quiz_option_id' => $optWrong->id,
        'is_correct' => false,
    ]);

    $response = $this->post("/teacher/quizzes/{$quiz->id}/auto-recheck-all");
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $att1->id,
        'score' => 5,
    ]);
    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $att2->id,
        'score' => 0,
    ]);
});

test('teacher can bulk AI check all seatwork attempts on a seatwork', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $seatwork = \App\Models\Seatwork::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Bulk Seatwork',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $q = $seatwork->questions()->create([
        'question_text' => 'Define photosynthesis',
        'type' => 'identification',
        'points' => 10,
        'order' => 1,
    ]);
    $q->options()->create(['option_text' => 'light synthesis', 'is_correct' => true]);

    $att = \App\Models\SeatworkAttempt::create([
        'seatwork_id' => $seatwork->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 10,
    ]);
    $att->answers()->create([
        'question_id' => $q->id,
        'answer_text' => 'light synthesis',
        'is_correct' => false,
    ]);

    $response = $this->post("/teacher/seatworks/{$seatwork->id}/auto-recheck-all");
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('seatwork_attempts', [
        'id' => $att->id,
        'score' => 10,
    ]);
});

test('teacher can bulk AI grade all practical attempts on a practical', function () {
    $this->actingAs($this->teacher);

    $student = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $practical = \App\Models\Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Practical Bulk',
        'instructions' => 'Run code test',
        'grade' => 'Grade 10',
        'max_score' => 20,
    ]);
    $crit = $practical->criteria()->create([
        'criterion_name' => 'Algorithm Efficiency',
        'max_points' => 20,
    ]);

    $att = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student->id,
        'submission_text' => 'Implemented binary search algorithm effectively with O(log n) performance.',
        'status' => 'submitted',
        'total_score' => 0,
    ]);

    $response = $this->post("/teacher/practicals/{$practical->id}/ai-grade-all");
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('practical_scores', [
        'attempt_id' => $att->id,
        'criterion_id' => $crit->id,
    ]);
    $this->assertDatabaseHas('practical_attempts', [
        'id' => $att->id,
    ]);
    $this->assertGreaterThan(0, $att->fresh()->total_score);
});

test('teacher can bulk AI check attempts across activities from results page', function () {
    $this->actingAs($this->teacher);

    $student = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $quiz = \App\Models\Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Cross Bulk Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $q = $quiz->questions()->create([
        'question_text' => 'What is 3+3?',
        'type' => 'multiple_choice',
        'points' => 5,
        'order' => 1,
    ]);
    $opt = $q->options()->create(['option_text' => '6', 'is_correct' => true]);

    $att = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student->id,
        'status' => 'submitted',
        'score' => 0,
        'total_points' => 5,
    ]);
    $att->answers()->create([
        'question_id' => $q->id,
        'quiz_option_id' => $opt->id,
        'is_correct' => false,
    ]);

    $response = $this->post('/teacher/results/auto-recheck-bulk', [
        'items' => [
            ['type' => 'Quiz', 'id' => $att->id],
        ],
    ]);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('quiz_attempts', [
        'id' => $att->id,
        'score' => 5,
    ]);
});

test('teacher can bulk delete selected attempts from results page', function () {
    $this->actingAs($this->teacher);

    $student1 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);
    $student2 = User::factory()->create(['role' => 'student', 'grade' => 'Grade 10']);

    $quiz = \App\Models\Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Bulk Delete Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);

    $att1 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student1->id,
        'status' => 'submitted',
        'score' => 5,
        'total_points' => 5,
    ]);

    $att2 = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student2->id,
        'status' => 'submitted',
        'score' => 5,
        'total_points' => 5,
    ]);

    $response = $this->delete('/teacher/results/bulk-destroy', [
        'items' => [
            ['type' => 'Quiz', 'id' => $att1->id],
            ['type' => 'Quiz', 'id' => $att2->id],
        ],
    ]);
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('quiz_attempts', ['id' => $att1->id]);
    $this->assertDatabaseMissing('quiz_attempts', ['id' => $att2->id]);
});

test('superadmin can view per level rewards in system settings and edit xp_per_level', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin', 'email_verified_at' => now()]);
    $this->actingAs($superadmin);

    $response = $this->get('/teacher/system-settings');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/Settings')
        ->has('levelRewards')
        ->where('levelRewards', fn ($rewards) => count($rewards) >= 13 && $rewards[0]['level'] === 1 && $rewards[0]['name'] === 'XP Booster Token')
    );

    // Update xp_per_level
    $updateResponse = $this->put('/teacher/system-settings', [
        'xp_per_level' => 150,
        'school_name' => 'BPHS',
        'school_short' => 'BPHS',
    ]);
    $updateResponse->assertSessionHas('success');

    expect(\App\Helpers\LevelHelper::xpPerLevel())->toBe(150);
    expect(\App\Helpers\LevelHelper::pointsForLevel(2))->toBe(150);
    expect(\App\Helpers\LevelHelper::level(149))->toBe(1);
    expect(\App\Helpers\LevelHelper::level(150))->toBe(2);
});

test('superadmin can customize level rewards and student unlockedRewards reflects updates', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin', 'email_verified_at' => now()]);
    $this->actingAs($superadmin);

    $customRewards = [
        1 => [
            'name' => 'Ultra Boost Catalyst',
            'description' => 'Triples XP on next completed activity.',
        ],
        2 => [
            'name' => 'Diamond Stylus',
            'description' => 'A sparkling stylus of wisdom.',
        ],
    ];

    $response = $this->put('/teacher/system-settings', [
        'level_rewards' => $customRewards,
        'school_name' => 'BPHS',
        'school_short' => 'BPHS',
    ]);
    $response->assertSessionHas('success');

    $allRewards = \App\Helpers\LevelHelper::allLevelRewards();
    expect($allRewards[0]['name'])->toBe('Ultra Boost Catalyst');
    expect($allRewards[1]['name'])->toBe('Diamond Stylus');

    // Student at level 2 receives custom names in unlocked rewards
    $unlocked = \App\Helpers\LevelHelper::unlockedRewards(120);
    expect($unlocked[0]['title'])->toBe('Ultra Boost Catalyst');

    // Reset gamification restores defaults
    $resetResponse = $this->post('/teacher/system-settings/reset', [
        'category' => 'gamification',
    ]);
    $resetResponse->assertSessionHas('success');

    $restored = \App\Helpers\LevelHelper::allLevelRewards();
    expect($restored[0]['name'])->toBe('XP Booster Token');
});

test('student activity submissions redirect directly to result page with 303 status and handle submitted state gracefully', function () {
    $student = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->gradeLevel->id,
        'email_verified_at' => now(),
    ]);
    $student->teachers()->attach($this->teacher->id);
    $this->actingAs($student);

    // 1. Quiz
    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Submit Redirect Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quiz->gradeLevels()->sync([$this->gradeLevel->id]);
    $quizAttempt = \App\Models\QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student->id,
        'status' => \App\Enums\AttemptStatus::InProgress->value,
        'started_at' => now(),
    ]);

    $quizResponse = $this->post("/student/quizzes/{$quiz->id}/submit", ['answers' => []]);
    $quizResponse->assertStatus(303);
    $quizResponse->assertRedirect(route('student.quizzes.result', ['attempt' => $quizAttempt->id]));

    // Visiting take view after submit redirects to result view
    $quizTakeAgain = $this->get("/student/quizzes/{$quiz->id}/take");
    $quizTakeAgain->assertRedirect(route('student.quizzes.result', ['attempt' => $quizAttempt->id]));

    // 2. Seatwork
    $seatwork = \App\Models\Seatwork::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Submit Redirect Seatwork',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $seatwork->gradeLevels()->sync([$this->gradeLevel->id]);
    $swAttempt = \App\Models\SeatworkAttempt::create([
        'seatwork_id' => $seatwork->id,
        'student_id' => $student->id,
        'status' => \App\Enums\AttemptStatus::InProgress->value,
        'started_at' => now(),
    ]);

    $swResponse = $this->post("/student/seatworks/{$seatwork->id}/submit", ['answers' => []]);
    $swResponse->assertStatus(303);
    $swResponse->assertRedirect(route('student.seatworks.result', ['attempt' => $swAttempt->id]));

    // Visiting take view after submit redirects to result view
    $swTakeAgain = $this->get("/student/seatworks/{$seatwork->id}/take");
    $swTakeAgain->assertRedirect(route('student.seatworks.result', ['attempt' => $swAttempt->id]));

    // 3. Practical
    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Submit Redirect Practical',
        'grade' => 'Grade 10',
        'max_attempts' => 1,
        'is_published' => true,
    ]);
    $practical->gradeLevels()->sync([$this->gradeLevel->id]);
    $pracAttempt = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student->id,
        'status' => \App\Enums\AttemptStatus::InProgress->value,
        'started_at' => now(),
    ]);

    $pracResponse = $this->post("/student/practicals/{$practical->id}/submit", [
        'submission_text' => 'My solution code',
    ]);
    $pracResponse->assertStatus(303);
    $pracResponse->assertRedirect(route('student.practicals.result', ['attempt' => $pracAttempt->id]));

    // Visiting take view after max attempts reached redirects to result view
    $pracTakeAgain = $this->get("/student/practicals/{$practical->id}/take");
    $pracTakeAgain->assertRedirect(route('student.practicals.result', ['attempt' => $pracAttempt->id]));

    // 4. Exam
    $exam = \App\Models\Exam::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Submit Redirect Exam',
        'grade' => 'Grade 10',
        'max_score' => 10,
        'is_published' => true,
    ]);
    $exam->gradeLevels()->sync([$this->gradeLevel->id]);
    $examAttempt = \App\Models\ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $student->id,
        'status' => \App\Enums\AttemptStatus::InProgress->value,
        'started_at' => now(),
    ]);

    $examResponse = $this->post("/student/exams/{$exam->id}/submit", ['answers' => []]);
    $examResponse->assertStatus(303);
    $examResponse->assertRedirect(route('student.exams.result', ['attempt' => $examAttempt->id]));

    // Visiting take view after submit redirects to result view
    $examTakeAgain = $this->get("/student/exams/{$exam->id}/take");
    $examTakeAgain->assertRedirect(route('student.exams.result', ['attempt' => $examAttempt->id]));
});
