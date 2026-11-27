<?php

use App\Models\GradeLevel;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\Section;
use App\Models\User;
use App\Services\AnswerMatcherService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create([
        'name' => 'Grade 10',
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->section = Section::create([
        'name' => 'Section A',
        'grade_level_id' => $this->gradeLevel->id,
        'is_active' => true,
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $this->gradeLevel->id,
        'email_verified_at' => now(),
    ]);

    $this->student->teachers()->attach($this->teacher->id);
});

test('answer matcher service matches exact and case-insensitive answers', function () {
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'Selection Tool'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'selection tool'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'SELECTION TOOL'))->toBeTrue();
});

test('answer matcher service handles filler words and articles', function () {
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'Selection'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'the selection tool'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'the selection'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Gradient', 'a gradient'))->toBeTrue();
});

test('answer matcher service handles singular and plural forms', function () {
    expect(AnswerMatcherService::isNearMatch('Swatches', 'swatch'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Swatch', 'swatches'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Gradient', 'gradients'))->toBeTrue();
});

test('answer matcher service tolerates minor typos', function () {
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'Selction Tool'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'selction'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Transparency', 'transparancy'))->toBeTrue();
});

test('answer matcher service handles symbols and textual translations', function () {
    expect(AnswerMatcherService::isNearMatch('Fill & Stroke', 'fill and stroke'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Red, Green & Blue', 'red, green and blue'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('+', 'plus'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('&', 'and'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('100%', '100 percent'))->toBeTrue();
});

test('answer matcher service handles shortcuts and alternate terminology', function () {
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'black arrow'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'V'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Direct Selection Tool', 'white arrow'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Direct Selection Tool', 'A'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Type Tool', 'Text Tool'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Type Tool', 'T'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Undo', 'Ctrl+Z'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Undo', 'Cmd+Z'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Stroke', 'outline'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Stroke', 'border'))->toBeTrue();
});

test('answer matcher service rejects clearly incorrect answers', function () {
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'Pen Tool'))->toBeFalse();
    expect(AnswerMatcherService::isNearMatch('Fill', 'Stroke'))->toBeFalse();
    expect(AnswerMatcherService::isNearMatch('Gradient', 'Swatch'))->toBeFalse();
    expect(AnswerMatcherService::isNearMatch('Rectangle Tool', 'Ellipse Tool'))->toBeFalse();
});

test('student quiz submission grades identification near match correctly', function () {
    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'InDesign Tools Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quiz->gradeLevels()->sync([$this->gradeLevel->id]);
    $quiz->sections()->sync([$this->section->id]);

    $question = $quiz->questions()->create([
        'question_text' => 'Which tool is used to move an already-created rectangle without changing its shape?',
        'type' => 'identification',
        'points' => 5,
        'order' => 0,
    ]);

    $question->options()->create([
        'option_text' => 'Selection Tool',
        'is_correct' => true,
        'order' => 0,
    ]);

    $attempt = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'score' => 0,
        'total_points' => 5,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    // Student submits "selection" without "Tool"
    $response = $this->actingAs($this->student)->post("/student/quizzes/{$quiz->id}/submit", [
        'answers' => [
            $question->id => 'selection',
        ],
    ]);

    $response->assertRedirect();

    $attempt->refresh();
    expect($attempt->status)->toBe('submitted');
    expect($attempt->score)->toBe(5);

    $this->assertDatabaseHas('quiz_answers', [
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'answer_text' => 'selection',
        'is_correct' => true,
    ]);
});

test('student quiz submission grades incorrect identification answer as zero', function () {
    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'InDesign Tools Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quiz->gradeLevels()->sync([$this->gradeLevel->id]);
    $quiz->sections()->sync([$this->section->id]);

    $question = $quiz->questions()->create([
        'question_text' => 'Which tool is used to move an already-created rectangle without changing its shape?',
        'type' => 'identification',
        'points' => 5,
        'order' => 0,
    ]);

    $question->options()->create([
        'option_text' => 'Selection Tool',
        'is_correct' => true,
        'order' => 0,
    ]);

    $attempt = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'score' => 0,
        'total_points' => 5,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    // Student submits incorrect answer "Pen Tool"
    $response = $this->actingAs($this->student)->post("/student/quizzes/{$quiz->id}/submit", [
        'answers' => [
            $question->id => 'Pen Tool',
        ],
    ]);

    $response->assertRedirect();

    $attempt->refresh();
    expect($attempt->status)->toBe('submitted');
    expect($attempt->score)->toBe(0);

    $this->assertDatabaseHas('quiz_answers', [
        'quiz_attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'answer_text' => 'Pen Tool',
        'is_correct' => false,
    ]);
});

test('student quiz submission grades multiple choice with letter or text answer', function () {
    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'InDesign Multiple Choice Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quiz->gradeLevels()->sync([$this->gradeLevel->id]);
    $quiz->sections()->sync([$this->section->id]);

    $question = $quiz->questions()->create([
        'question_text' => 'A student changes only the color inside a shape. Which property was modified?',
        'type' => 'multiple_choice',
        'points' => 5,
        'order' => 0,
    ]);

    $optStroke = $question->options()->create([
        'option_text' => 'Stroke',
        'is_correct' => false,
        'order' => 0,
    ]);
    $optFill = $question->options()->create([
        'option_text' => 'Fill',
        'is_correct' => true,
        'order' => 1,
    ]);

    // 1. Submit with letter 'B' (index 1 -> Fill)
    $attempt1 = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'score' => 0,
        'total_points' => 5,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $this->actingAs($this->student)->post("/student/quizzes/{$quiz->id}/submit", [
        'answers' => [
            $question->id => 'B',
        ],
    ]);

    $attempt1->refresh();
    expect($attempt1->score)->toBe(5);

    // 2. Submit with option text 'fill'
    $student2 = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $this->gradeLevel->id,
        'email_verified_at' => now(),
    ]);
    $student2->teachers()->attach($this->teacher->id);

    $attempt2 = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student2->id,
        'score' => 0,
        'total_points' => 5,
        'status' => 'in_progress',
        'started_at' => now(),
    ]);

    $this->actingAs($student2)->post("/student/quizzes/{$quiz->id}/submit", [
        'answers' => [
            $question->id => 'fill',
        ],
    ]);

    $attempt2->refresh();
    expect($attempt2->score)->toBe(5);
});

test('teacher can create quiz with identification questions', function () {
    $response = $this->actingAs($this->teacher)->post('/teacher/quizzes', [
        'title' => 'InDesign Identification Quiz',
        'grade_level_ids' => [$this->gradeLevel->id],
        'section_ids' => [$this->section->id],
        'questions' => [
            [
                'question_text' => 'Tool to create circular graphic element',
                'type' => 'identification',
                'points' => 2,
                'options' => [
                    ['option_text' => 'Ellipse Tool', 'is_correct' => true],
                ],
            ],
            [
                'question_text' => 'Changes color inside shape',
                'type' => 'multiple_choice',
                'points' => 1,
                'options' => [
                    ['option_text' => 'Fill', 'is_correct' => true],
                    ['option_text' => 'Stroke', 'is_correct' => false],
                ],
            ],
        ],
    ]);

    $response->assertRedirect('/teacher/quizzes');

    $this->assertDatabaseHas('quizzes', [
        'title' => 'InDesign Identification Quiz',
    ]);

    $this->assertDatabaseHas('questions', [
        'question_text' => 'Tool to create circular graphic element',
        'type' => 'identification',
    ]);
});

test('seatwork identification evaluates near and conceptually aligned answers', function () {
    // 1. Direct near match
    $resultNear = AnswerMatcherService::evaluateIdentificationWithAi(
        ['Selection Tool'],
        'selection',
        'Tool used to select items'
    );
    expect($resultNear['is_correct'])->toBeTrue();

    // 2. AI approval when concept is aligned
    \Illuminate\Support\Facades\Http::fake([
        '*' => \Illuminate\Support\Facades\Http::response([
            'choices' => [
                ['message' => ['content' => json_encode(['is_correct' => true, 'feedback' => 'Approved (conceptually aligned).'])]],
            ],
        ], 200),
    ]);

    \App\Models\Setting::set('ai_provider', '9router');
    \App\Models\Setting::set('ninerouter_api_key', 'test-key');

    $resultAi = AnswerMatcherService::evaluateIdentificationWithAi(
        ['Direct Selection Tool'],
        'white arrow pointer to modify anchor points',
        'Tool used to adjust path points'
    );
    expect($resultAi['is_correct'])->toBeTrue()
        ->and($resultAi['feedback'])->toContain('conceptually aligned');
});

test('answer matcher service handles phrases word order and conceptual matches', function () {
    // Word order inversion and morphological variation
    expect(AnswerMatcherService::isNearMatch('Consistency and readability', 'readability and consistency'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Consistency and readability', 'consistent and readable'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Consistency and readability', 'it should have consistency and readability'))->toBeTrue();

    // Multi-concept phrase summaries
    expect(AnswerMatcherService::isNearMatch(
        'Adjusting the color or transparency to improve readability',
        'adjust color or transparency'
    ))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch(
        'Adjusting the color or transparency to improve readability',
        'adjust transparency to improve readability'
    ))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch(
        'Adjusting the color or transparency to improve readability',
        'adjusting color and transparency'
    ))->toBeTrue();

    // Conversational wrappers and shortcuts
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'use selection tool'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'the selection tool is used'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'black arrow'))->toBeTrue();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'V'))->toBeTrue();

    // Rejections of distinct or conflicting tools
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'Direct Selection Tool'))->toBeFalse();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'Pen Tool'))->toBeFalse();
    expect(AnswerMatcherService::isNearMatch('Selection Tool', 'Rectangle Tool'))->toBeFalse();
    expect(AnswerMatcherService::isNearMatch('Fill', 'Stroke'))->toBeFalse();
});

test('seatwork enumeration awards points for near and aligned items', function () {
    $options = [
        ['option_text' => 'Selection Tool'],
        ['option_text' => 'Pen Tool'],
        ['option_text' => 'Type Tool'],
    ];

    // Student provides items with slight typo, plural, and filler words
    $studentText = "1. selction\n2. pens tool\n3. type";

    $eval = AnswerMatcherService::evaluateEnumerationWithAi(
        $options,
        $studentText,
        'Enumerate 3 basic InDesign tools',
        6
    );

    expect($eval['is_correct'])->toBeTrue()
        ->and($eval['match_count'])->toBe(3)
        ->and($eval['points_awarded'])->toBe(6);
});

test('seatwork checker auto recheck approves aligned identification and enumeration attempts', function () {
    $seatwork = Seatwork::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Graphic Design Seatwork',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $seatwork->gradeLevels()->sync([$this->gradeLevel->id]);

    $qIdent = $seatwork->questions()->create([
        'question_text' => 'Tool used to select paths and points',
        'type' => 'identification',
        'points' => 5,
        'order' => 0,
    ]);
    $qIdent->options()->create([
        'option_text' => 'Direct Selection Tool',
        'is_correct' => true,
        'order' => 0,
    ]);

    $qEnum = $seatwork->questions()->create([
        'question_text' => 'List two selection tools in InDesign',
        'type' => 'enumeration',
        'points' => 10,
        'order' => 1,
    ]);
    $qEnum->options()->create(['option_text' => 'Selection Tool', 'order' => 0]);
    $qEnum->options()->create(['option_text' => 'Direct Selection Tool', 'order' => 1]);

    $attempt = SeatworkAttempt::create([
        'seatwork_id' => $seatwork->id,
        'student_id' => $this->student->id,
        'score' => 0,
        'total_points' => 15,
        'status' => 'submitted',
        'started_at' => now(),
        'submitted_at' => now(),
    ]);

    $ansIdent = $attempt->answers()->create([
        'question_id' => $qIdent->id,
        'answer_text' => 'direct selection',
        'is_correct' => false,
    ]);

    $ansEnum = $attempt->answers()->create([
        'question_id' => $qEnum->id,
        'answer_text' => "1. selection\n2. direct selection",
        'is_correct' => false,
    ]);

    // Teacher runs autoRecheck on attempt
    $response = $this->actingAs($this->teacher)
        ->put("/teacher/seatworks/{$seatwork->id}/auto-recheck/{$attempt->id}");

    $response->assertRedirect();

    $attempt->refresh();
    $ansIdent->refresh();
    $ansEnum->refresh();

    expect($ansIdent->is_correct)->toBeTrue()
        ->and($ansEnum->is_correct)->toBeTrue()
        ->and($ansEnum->points_awarded)->toBe(10)
        ->and($attempt->score)->toBe(15);
});
