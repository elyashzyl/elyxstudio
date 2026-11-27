<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\Section;
use App\Models\User;
use App\Services\ActivityAiGraderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityGenderFilterAndExamCompileTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_show_endpoints_and_results_expose_student_gender(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1, 'is_active' => true]);
        $section = Section::create(['name' => 'Section A', 'grade_level_id' => $gradeLevel->id, 'is_active' => true]);

        $maleStudent = User::factory()->create([
            'role' => 'student',
            'grade' => 'Grade 10',
            'grade_level_id' => $gradeLevel->id,
            'section_id' => $section->id,
            'gender' => 'Male',
        ]);

        $femaleStudent = User::factory()->create([
            'role' => 'student',
            'grade' => 'Grade 10',
            'grade_level_id' => $gradeLevel->id,
            'section_id' => $section->id,
            'gender' => 'Female',
        ]);

        $teacher->students()->attach([$maleStudent->id, $femaleStudent->id]);

        // 1. Quiz Show
        $quiz = Quiz::create([
            'teacher_id' => $teacher->id,
            'title' => 'Biology Quiz',
            'grade' => 'Grade 10',
            'is_published' => true,
        ]);
        $quiz->gradeLevels()->sync([$gradeLevel->id]);
        $q = $quiz->questions()->create(['question_text' => 'What is DNA?', 'type' => 'identification', 'points' => 5]);
        $q->options()->create(['option_text' => 'Deoxyribonucleic acid', 'is_correct' => true, 'order' => 0]);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $maleStudent->id,
            'status' => 'submitted',
            'score' => 5,
            'total_points' => 5,
            'submitted_at' => now(),
        ]);
        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'student_id' => $femaleStudent->id,
            'status' => 'submitted',
            'score' => 5,
            'total_points' => 5,
            'submitted_at' => now(),
        ]);

        $res = $this->actingAs($teacher)->get("/teacher/quizzes/{$quiz->id}");
        $res->assertOk();
        $attempts = $res->viewData('page')['props']['attempts'];
        $this->assertCount(2, $attempts);
        $this->assertEquals('Male', $attempts[0]['student']['gender']);
        $this->assertNotNull($res->viewData('page')['props']['sections']);

        // 2. Seatwork Show
        $seatwork = Seatwork::create([
            'teacher_id' => $teacher->id,
            'title' => 'Math Seatwork',
            'grade' => 'Grade 10',
            'is_published' => true,
        ]);
        $seatwork->gradeLevels()->sync([$gradeLevel->id]);
        $sq = $seatwork->questions()->create(['question_text' => '2+2', 'type' => 'multiple_choice', 'points' => 1]);
        $sq->options()->create(['option_text' => '4', 'is_correct' => true, 'order' => 0]);

        SeatworkAttempt::create([
            'seatwork_id' => $seatwork->id,
            'student_id' => $maleStudent->id,
            'status' => 'submitted',
            'score' => 1,
            'total_points' => 1,
            'attempt_number' => 1,
            'is_recorded' => true,
            'submitted_at' => now(),
        ]);

        $res = $this->actingAs($teacher)->get("/teacher/seatworks/{$seatwork->id}");
        $res->assertOk();
        $sAttempts = $res->viewData('page')['props']['attempts'];
        $this->assertEquals('Male', $sAttempts[0]['student']['gender']);

        // 3. Results page
        $res = $this->actingAs($teacher)->get('/teacher/results');
        $res->assertOk();
        $activities = $res->viewData('page')['props']['activities'];
        $this->assertNotEmpty($activities);
        $this->assertArrayHasKey('student_gender', $activities[0]);

        // 4. Practical Show
        $practical = Practical::create([
            'teacher_id' => $teacher->id,
            'title' => 'Biology Practical',
            'grade' => 'Grade 10',
            'is_published' => true,
        ]);
        $practical->gradeLevels()->sync([$gradeLevel->id]);
        $practical->criteria()->create([
            'criterion_name' => 'Specimen Preparation',
            'max_points' => 10,
            'order' => 0,
        ]);

        \App\Models\PracticalAttempt::create([
            'practical_id' => $practical->id,
            'student_id' => $maleStudent->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $res = $this->actingAs($teacher)->get("/teacher/practicals/{$practical->id}");
        $res->assertOk();
        $pAttempts = $res->viewData('page')['props']['attempts'];
        $this->assertEquals('Male', $pAttempts[0]['student']['gender']);
        $this->assertNotNull($res->viewData('page')['props']['sections']);
    }

    public function test_teacher_can_create_quiz_with_rich_question_types(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1, 'is_active' => true]);
        $section = Section::create(['name' => 'Section A', 'grade_level_id' => $gradeLevel->id, 'is_active' => true]);

        $payload = [
            'title' => 'Comprehensive Science Quiz',
            'description' => 'Test with multiple question types',
            'grade_level_ids' => [$gradeLevel->id],
            'section_ids' => [$section->id],
            'questions' => [
                [
                    'question_text' => 'What is H2O?',
                    'type' => 'identification',
                    'points' => 2,
                    'correct_answer' => 'Water',
                ],
                [
                    'question_text' => 'List 3 states of matter',
                    'type' => 'enumeration',
                    'points' => 3,
                    'enum_items' => ['Solid', 'Liquid', 'Gas'],
                ],
                [
                    'question_text' => 'Match elements to symbols',
                    'type' => 'matching',
                    'points' => 4,
                    'matching_pairs' => [
                        ['left_text' => 'Gold', 'right_text' => 'Au'],
                        ['left_text' => 'Silver', 'right_text' => 'Ag'],
                    ],
                ],
                [
                    'question_text' => 'Explain the greenhouse effect.',
                    'type' => 'essay',
                    'points' => 5,
                    'criteria' => 'Clear definition, gases involved, environmental impact',
                ],
            ],
        ];

        $res = $this->actingAs($teacher)->post('/teacher/quizzes', $payload);
        $res->assertRedirect('/teacher/quizzes');

        $quiz = Quiz::where('title', 'Comprehensive Science Quiz')->first();
        $this->assertNotNull($quiz);
        $this->assertCount(4, $quiz->questions);

        $matchingQ = $quiz->questions()->where('type', 'matching')->first();
        $this->assertNotNull($matchingQ);
        $this->assertCount(2, $matchingQ->matchingPairs);

        $enumQ = $quiz->questions()->where('type', 'enumeration')->first();
        $this->assertNotNull($enumQ);
        $this->assertCount(3, $enumQ->options);

        $essayQ = $quiz->questions()->where('type', 'essay')->first();
        $this->assertNotNull($essayQ);
        $this->assertStringContainsString('environmental impact', $essayQ->criteria);
    }

    public function test_teacher_can_edit_exam_content_and_recalculate_max_score(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1, 'is_active' => true]);

        $exam = Exam::create([
            'teacher_id' => $teacher->id,
            'title' => 'Original Exam',
            'grade' => 'Grade 10',
            'max_score' => 2,
        ]);
        $exam->gradeLevels()->sync([$gradeLevel->id]);

        $questionSection = $exam->sections()->create([
            'title' => 'Questions',
            'section_type' => 'questions',
            'order' => 0,
        ]);
        $multipleChoice = $questionSection->questions()->create([
            'question_text' => 'Original question',
            'type' => 'multiple_choice',
            'points' => 2,
            'order' => 0,
        ]);
        $correctOption = $multipleChoice->options()->create([
            'option_text' => 'Original correct answer',
            'is_correct' => true,
            'order' => 0,
        ]);
        $wrongOption = $multipleChoice->options()->create([
            'option_text' => 'Original wrong answer',
            'is_correct' => false,
            'order' => 1,
        ]);

        $matching = $questionSection->questions()->create([
            'question_text' => 'Match the terms',
            'type' => 'matching',
            'points' => 1,
            'order' => 1,
        ]);
        $pair = $matching->matchingPairs()->create([
            'left_text' => 'Old left',
            'right_text' => 'Old right',
            'order' => 0,
        ]);

        $practicalSection = $exam->sections()->create([
            'title' => 'Practical',
            'section_type' => 'practical',
            'order' => 1,
        ]);
        $criterion = $practicalSection->criteria()->create([
            'criterion_name' => 'Original criterion',
            'description' => 'Original description',
            'max_points' => 5,
            'order' => 0,
        ]);

        $this->actingAs($otherTeacher)
            ->get("/teacher/exams/{$exam->id}/edit")
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get("/teacher/exams/{$exam->id}/edit")
            ->assertOk();

        $response = $this->put("/teacher/exams/{$exam->id}", [
            'title' => 'Updated Exam',
            'instructions' => 'Updated instructions',
            'grade_level_ids' => [$gradeLevel->id],
            'sections' => [
                [
                    'id' => $questionSection->id,
                    'title' => 'Updated Questions',
                    'section_type' => 'questions',
                    'instructions' => 'Answer all items.',
                    'questions' => [
                        [
                            'id' => $multipleChoice->id,
                            'question_text' => 'Updated question',
                            'type' => 'multiple_choice',
                            'points' => 4,
                            'options' => [
                                ['id' => $correctOption->id, 'option_text' => 'Updated correct answer', 'is_correct' => true],
                                ['id' => $wrongOption->id, 'option_text' => 'Updated wrong answer', 'is_correct' => false],
                            ],
                        ],
                        [
                            'id' => $matching->id,
                            'question_text' => 'Updated matching question',
                            'type' => 'matching',
                            'points' => 3,
                            'matching_pairs' => [
                                ['id' => $pair->id, 'left_text' => 'Updated left', 'right_text' => 'Updated right'],
                            ],
                        ],
                    ],
                ],
                [
                    'id' => $practicalSection->id,
                    'title' => 'Updated Practical',
                    'section_type' => 'practical',
                    'instructions' => 'Complete the task.',
                    'criteria' => [
                        [
                            'id' => $criterion->id,
                            'criterion_name' => 'Updated criterion',
                            'description' => 'Updated description',
                            'max_points' => 7,
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertRedirect(route('teacher.exams.show', $exam));
        $exam->refresh();

        $this->assertSame('Updated Exam', $exam->title);
        $this->assertSame(14, $exam->max_score);
        $this->assertDatabaseHas('exam_questions', [
            'id' => $multipleChoice->id,
            'question_text' => 'Updated question',
            'points' => 4,
        ]);
        $this->assertDatabaseHas('exam_options', [
            'id' => $correctOption->id,
            'option_text' => 'Updated correct answer',
            'is_correct' => 1,
        ]);
        $this->assertDatabaseHas('exam_matching_pairs', [
            'id' => $pair->id,
            'left_text' => 'Updated left',
            'right_text' => 'Updated right',
        ]);
        $this->assertDatabaseHas('exam_criteria', [
            'id' => $criterion->id,
            'criterion_name' => 'Updated criterion',
            'max_points' => 7,
        ]);
    }

    public function test_teacher_can_compile_quizzes_seatworks_and_practicals_into_exam(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $gradeLevel = GradeLevel::create(['name' => 'Grade 10', 'display_order' => 1, 'is_active' => true]);

        // 1. Create a quiz
        $quiz = Quiz::create(['teacher_id' => $teacher->id, 'title' => 'Quarter 1 Quiz', 'grade' => 'Grade 10', 'time_limit_minutes' => 20]);
        $quiz->gradeLevels()->sync([$gradeLevel->id]);
        $quizQ = $quiz->questions()->create(['question_text' => 'What is photosynthesis?', 'type' => 'identification', 'points' => 5]);
        $quizQ->options()->create(['option_text' => 'Process by plants to make food', 'is_correct' => true, 'order' => 0]);

        // 2. Create a seatwork
        $seatwork = Seatwork::create(['teacher_id' => $teacher->id, 'title' => 'Lab Seatwork', 'grade' => 'Grade 10', 'time_limit_minutes' => 30]);
        $seatwork->gradeLevels()->sync([$gradeLevel->id]);
        $seatworkQ = $seatwork->questions()->create(['question_text' => 'Water boils at 100C', 'type' => 'true_false', 'points' => 2]);
        $seatworkQ->options()->create(['option_text' => 'True', 'is_correct' => true, 'order' => 0]);
        $seatworkQ->options()->create(['option_text' => 'False', 'is_correct' => false, 'order' => 1]);

        // 3. Create a practical
        $practical = Practical::create(['teacher_id' => $teacher->id, 'title' => 'Microscope Lab Practical', 'grade' => 'Grade 10', 'time_limit_minutes' => 45]);
        $practical->gradeLevels()->sync([$gradeLevel->id]);
        $practical->criteria()->create(['criterion_name' => 'Slide Preparation', 'description' => 'Proper staining and cover slip', 'max_points' => 15]);

        // 4. Test compilation endpoint without AI rephrase
        $res = $this->actingAs($teacher)->postJson('/teacher/exams/compile-activities', [
            'quiz_ids' => [$quiz->id],
            'seatwork_ids' => [$seatwork->id],
            'practical_ids' => [$practical->id],
            'rephrase' => false,
        ]);

        $res->assertOk();
        $json = $res->json();
        $this->assertTrue($json['ok']);
        $this->assertCount(3, $json['sections']);
        $this->assertEquals('questions', $json['sections'][0]['section_type']);
        $this->assertEquals('questions', $json['sections'][1]['section_type']);
        $this->assertEquals('practical', $json['sections'][2]['section_type']);
        $this->assertCount(1, $json['sections'][0]['questions']);
        $this->assertEquals('identification', $json['sections'][0]['questions'][0]['type']);
        $this->assertCount(1, $json['sections'][2]['criteria']);
        $this->assertEquals('Slide Preparation', $json['sections'][2]['criteria'][0]['criterion_name']);
        $this->assertEquals([$gradeLevel->id], $json['grade_level_ids']);
    }
}
