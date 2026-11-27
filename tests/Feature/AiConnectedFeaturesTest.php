<?php

use App\Enums\ActivityType;
use App\Enums\GameType;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Game;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\AiManager\Tasks\AutoGraderTask;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1]);
    $this->section = Section::create([
        'name' => 'St. Jude',
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->gradeLevel->id,
        'section_id' => $this->section->id,
        'email_verified_at' => now(),
    ]);

    $this->student->teachers()->attach($this->teacher->id);
});

test('material generate endpoint supports newly added game types like fourpics, crossword, wordsearch and codepuzzle', function () {
    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Computer Science Basics',
        'subject' => 'ICT',
        'grade_level_id' => $this->gradeLevel->id,
        'file_name' => 'cs_sample.txt',
        'file_path' => 'teaching-materials/cs_sample.txt',
        'file_type' => 'txt',
        'file_data' => base64_encode("CPU executes program instructions. RAM stores volatile memory data. GPU handles parallel rendering operations. SSD delivers high-speed persistent storage."),
    ]);

    $this->actingAs($this->teacher);

    // 1. FourPics
    $res1 = $this->postJson(route('teacher.materials.generate', $material), [
        'activity' => 'game',
        'game_type' => 'fourpics',
        'count' => 3,
    ]);
    $res1->assertOk()->assertJson(['ok' => true]);
    $items1 = $res1->json('items');
    expect($items1)->toBeArray()->not->toBeEmpty();
    expect($items1[0])->toHaveKeys(['question', 'answer', 'options']);
    expect($items1[0]['options'])->toHaveCount(4);

    // 2. Crossword
    $res2 = $this->postJson(route('teacher.materials.generate', $material), [
        'activity' => 'game',
        'game_type' => 'crossword',
        'count' => 3,
    ]);
    $res2->assertOk()->assertJson(['ok' => true]);
    $items2 = $res2->json('items');
    expect($items2)->toBeArray()->not->toBeEmpty();
    expect($items2[0])->toHaveKeys(['question', 'answer']);

    // 3. WordSearch
    $res3 = $this->postJson(route('teacher.materials.generate', $material), [
        'activity' => 'game',
        'game_type' => 'wordsearch',
        'count' => 3,
    ]);
    $res3->assertOk()->assertJson(['ok' => true]);

    // 4. CodePuzzle
    $res4 = $this->postJson(route('teacher.materials.generate', $material), [
        'activity' => 'game',
        'game_type' => 'codepuzzle',
        'count' => 3,
    ]);
    $res4->assertOk()->assertJson(['ok' => true]);
});

test('ai creator can parse and store an exam with questions and practical criteria', function () {
    $this->actingAs($this->teacher);

    $rawText = "Midterm Examination in Science\n"
        . "Instructions: Answer all parts thoroughly.\n\n"
        . "1. What is the powerhouse of the cell?\n"
        . "A. Nucleus\nB. Mitochondria\nC. Ribosome\nD. Vacuole\n"
        . "Answer: B\n\n"
        . "2. Enumerate 3 parts of an atom.\n"
        . "Answer: proton, neutron, electron\n\n"
        . "Part II: Laboratory Practical Rubric\n"
        . "Criterion: Microscope Focusing | Max Points: 20 | Description: Successfully focuses specimen on 40x magnification.\n";

    $parseRes = $this->postJson(route('teacher.ai-creator.parse'), [
        'activity' => 'exam',
        'raw_text' => $rawText,
        'count' => 3,
    ]);
    $parseRes->assertOk();

    // Store the exam via AI Creator
    $storePayload = [
        'activity' => 'exam',
        'title' => 'Science Midterm Examination',
        'grade_level_ids' => [$this->gradeLevel->id],
        'instructions' => 'Follow all section instructions.',
        'time_limit_minutes' => 60,
        'action' => 'save_and_publish',
        'questions' => [
            [
                'question_text' => 'What is the powerhouse of the cell?',
                'type' => 'multiple_choice',
                'points' => 2,
                'options' => [
                    ['option_text' => 'Nucleus', 'is_correct' => false],
                    ['option_text' => 'Mitochondria', 'is_correct' => true],
                    ['option_text' => 'Ribosome', 'is_correct' => false],
                    ['option_text' => 'Vacuole', 'is_correct' => false],
                ],
            ],
            [
                'question_text' => 'Enumerate parts of an atom',
                'type' => 'enumeration',
                'points' => 3,
                'enum_items' => ['Proton', 'Neutron', 'Electron'],
            ],
        ],
        'criteria' => [
            [
                'criterion_name' => 'Microscope Focusing',
                'description' => 'Focuses specimen on 40x',
                'max_points' => 20,
            ],
        ],
    ];

    $storeRes = $this->post(route('teacher.ai-creator.store'), $storePayload);
    $storeRes->assertRedirect(route('teacher.exams.index'));

    $createdExam = Exam::where('title', 'Science Midterm Examination')->with('sections.questions.options', 'sections.criteria')->first();
    expect($createdExam)->not->toBeNull();
    expect($createdExam->sections)->toHaveCount(2);
    expect($createdExam->sections[0]->section_type)->toBe('questions');
    expect($createdExam->sections[0]->questions)->toHaveCount(2);
    expect($createdExam->sections[1]->section_type)->toBe('practical');
    expect($createdExam->sections[1]->criteria)->toHaveCount(1);
});

test('ai creator can parse and store games of different types', function () {
    $this->actingAs($this->teacher);

    $rawText = "Hardware Components Guide\n"
        . "CPU is the central processor.\n"
        . "RAM provides temporary working space.\n"
        . "Motherboard connects all peripherals together.\n";

    $parseRes = $this->postJson(route('teacher.ai-creator.parse'), [
        'activity' => 'game',
        'game_type' => 'fourpics',
        'raw_text' => $rawText,
        'count' => 3,
    ]);
    $parseRes->assertOk();
    $items = $parseRes->json('items');
    expect($items)->not->toBeEmpty();

    // Store game via AI creator
    $storePayload = [
        'activity' => 'game',
        'game_type' => 'fourpics',
        'title' => 'Hardware 4 Pics 1 Word',
        'subject' => 'Computer Hardware',
        'grade_level_ids' => [$this->gradeLevel->id],
        'description' => 'Guess hardware component from clues',
        'xp_reward' => 30,
        'cards' => $items,
    ];

    $storeRes = $this->post(route('teacher.ai-creator.store'), $storePayload);
    $storeRes->assertRedirect(route('teacher.games.index'));

    $createdGame = Game::where('title', 'Hardware 4 Pics 1 Word')->with('cards')->first();
    expect($createdGame)->not->toBeNull();
    expect($createdGame->type)->toBe('fourpics');
    expect($createdGame->cards->count())->toBeGreaterThan(0);
});

test('student submitting exam grades enumeration with AnswerMatcher lenient alignment and proportional points', function () {
    $exam = Exam::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Chemistry Periodic Exam',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $exam->gradeLevels()->attach($this->gradeLevel->id);

    $section = $exam->sections()->create([
        'title' => 'Section A',
        'section_type' => 'questions',
        'order' => 0,
    ]);

    $enumQ = $section->questions()->create([
        'question_text' => 'Enumerate the three primary subatomic particles',
        'type' => 'enumeration',
        'points' => 6,
        'order' => 0,
    ]);

    $enumQ->options()->createMany([
        ['option_text' => 'Protons', 'is_correct' => true, 'order' => 0],
        ['option_text' => 'Neutrons', 'is_correct' => true, 'order' => 1],
        ['option_text' => 'Electrons', 'is_correct' => true, 'order' => 2],
    ]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'status' => 'in_progress',
    ]);

    $this->actingAs($this->student);

    // Student provides slightly varied singular answers with typos or case differences
    $res = $this->post(route('student.exams.submit', $exam), [
        'answers' => [
            $enumQ->id => ["proton", "electron", "nuetron"], // 3 out of 3 matches!
        ],
    ]);

    $res->assertRedirect();
    $attempt->refresh();
    expect($attempt->total_score)->toBe(6);

    $answerRecord = $attempt->answers()->where('exam_question_id', $enumQ->id)->first();
    expect($answerRecord->is_correct)->toBeTrue();
    expect($answerRecord->points_earned)->toBe(6);
});

test('exam autoRecheck and ai supervisor autoGraderTask re-evaluates exam questions and practical criteria', function () {
    $exam = Exam::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Robotics Assessment',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $exam->gradeLevels()->attach($this->gradeLevel->id);

    $qSection = $exam->sections()->create([
        'title' => 'Questions',
        'section_type' => 'questions',
        'order' => 0,
    ]);

    $identQ = $qSection->questions()->create([
        'question_text' => 'What sensor measures distance using sonic reflection?',
        'type' => 'identification',
        'points' => 5,
        'order' => 0,
    ]);
    $identQ->options()->create([
        'option_text' => 'Ultrasonic Sensor',
        'is_correct' => true,
        'order' => 0,
    ]);

    $pSection = $exam->sections()->create([
        'title' => 'Robotics Circuit Demonstration',
        'section_type' => 'practical',
        'order' => 1,
    ]);

    $crit = $pSection->criteria()->create([
        'criterion_name' => 'Wiring Accuracy',
        'description' => 'Correctly connects VCC, GND, and Echo pins',
        'max_points' => 20,
        'order' => 0,
    ]);

    $attempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    // Answer with shortcut/synonym: "ultrasonic" instead of full "Ultrasonic Sensor"
    $attempt->answers()->create([
        'exam_question_id' => $identQ->id,
        'answer_text' => 'ultrasonic',
        'is_correct' => false, // Initially marked false
        'points_earned' => 0,
    ]);

    // 1. Teacher clicks Auto Recheck
    $this->actingAs($this->teacher);
    $recheckRes = $this->put(route('teacher.exams.auto-recheck', $attempt));
    $recheckRes->assertRedirect();

    $attempt->refresh();
    $answerRecord = $attempt->answers()->where('exam_question_id', $identQ->id)->first();
    // AnswerMatcherService recognizes "ultrasonic" as near match!
    expect($answerRecord->is_correct)->toBeTrue();
    expect($answerRecord->points_earned)->toBe(5);
    // Practical criteria awarded
    expect($attempt->sectionScores()->where('exam_criterion_id', $crit->id)->exists())->toBeTrue();
    expect($attempt->total_score)->toBeGreaterThanOrEqual(20);

    // 2. Test AutoGraderTask
    $anotherAttempt = ExamAttempt::create([
        'exam_id' => $exam->id,
        'student_id' => $this->student->id,
        'status' => 'submitted',
        'total_score' => null,
        'submitted_at' => now(),
    ]);
    $anotherAttempt->answers()->create([
        'exam_question_id' => $identQ->id,
        'answer_text' => 'Ultrasonic Sensor',
        'is_correct' => true,
        'points_earned' => 5,
    ]);

    $task = new AutoGraderTask();
    $stats = $task->execute('autonomous', 0.80);
    expect($stats['processed'])->toBeGreaterThan(0);
});
