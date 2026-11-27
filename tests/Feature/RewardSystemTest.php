<?php

namespace Tests\Feature;

use App\Helpers\PointsHelper;
use App\Models\ClassTodo;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\PracticalCriterion;
use App\Models\PracticalScore;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizOption;
use App\Models\RewardRedemption;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\Section;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private GradeLevel $gradeLevel;
    private Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->gradeLevel = GradeLevel::create(['name' => 'Grade 8', 'display_order' => 1, 'is_active' => true]);
        $this->section = Section::create(['name' => 'Sapphire', 'grade_level_id' => $this->gradeLevel->id, 'is_active' => true]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'grade' => 'Grade 8',
            'grade_level_id' => $this->gradeLevel->id,
            'section_id' => $this->section->id,
            'total_points' => 500, // Level 6
        ]);

        $this->student->teachers()->attach($this->teacher->id);

        \App\Models\StudentPoint::create([
            'student_id' => $this->student->id,
            'activity_type' => 'manual',
            'activity_id' => 1,
            'points' => 500,
            'score' => 500,
            'total' => 500,
            'reason' => 'Initial XP',
        ]);
    }

    public function test_student_can_activate_xp_booster_and_it_doubles_awarded_points(): void
    {
        $this->actingAs($this->student);

        // Activate XP Booster (Level 1)
        $res = $this->post('/student/rewards/1/use');
        $res->assertRedirect();
        $this->student->refresh();
        $this->assertTrue((bool) $this->student->active_xp_booster);

        // Award points on a quiz (score 10/10 -> standard 10 XP, with booster 20 XP)
        $quiz = Quiz::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Cell Biology Quiz',
            'grade' => 'Grade 8',
            'is_published' => true,
        ]);

        PointsHelper::award($this->student->id, 'Quiz', $quiz->id, 10, 10);

        $this->student->refresh();
        // Base points: 500 + 20 booster = 520
        $this->assertEquals(520, $this->student->total_points);
        $this->assertFalse((bool) $this->student->active_xp_booster);

        // Level 1 redemption marked used
        $this->assertTrue(RewardService::isUsed($this->student, 1));
    }

    public function test_student_can_equip_and_unequip_cosmetic_badge(): void
    {
        $this->actingAs($this->student);

        // Student is Level 6, can equip Level 2 Golden Pencil
        $res = $this->post('/student/rewards/equip-badge', ['badge' => 'golden_pencil']);
        $res->assertRedirect();
        $this->student->refresh();
        $this->assertEquals('golden_pencil', $this->student->equipped_badge);

        // Unequip
        $res2 = $this->post('/student/rewards/equip-badge', ['badge' => 'golden_pencil']);
        $res2->assertRedirect();
        $this->student->refresh();
        $this->assertNull($this->student->equipped_badge);
    }

    public function test_student_cannot_equip_badge_above_their_level(): void
    {
        $this->actingAs($this->student);

        // Student is Level 6, cannot equip Level 12 Crown of Knowledge
        $res = $this->postJson('/student/rewards/equip-badge', ['badge' => 'crown_of_knowledge']);
        $res->assertStatus(422);

        $this->student->refresh();
        $this->assertNull($this->student->equipped_badge);
    }

    public function test_student_can_use_homework_pass_to_complete_seatwork_with_full_marks(): void
    {
        $this->actingAs($this->student);

        $seatwork = Seatwork::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Lab Worksheet #4',
            'grade' => 'Grade 8',
            'is_published' => true,
        ]);
        $seatwork->gradeLevels()->attach($this->gradeLevel->id);

        \App\Models\SeatworkQuestion::create([
            'seatwork_id' => $seatwork->id,
            'question_text' => 'What is the powerhouse of the cell?',
            'type' => 'identification',
            'points' => 10,
            'order' => 1,
        ]);

        $res = $this->post('/student/rewards/use-homework-pass', [
            'seatwork_id' => $seatwork->id,
        ]);
        $res->assertRedirect();

        $attempt = SeatworkAttempt::where('seatwork_id', $seatwork->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($attempt);
        $this->assertEquals(10, $attempt->score);
        $this->assertEquals(\App\Enums\AttemptStatus::Submitted->value, $attempt->status);

        // Level 3 redemption marked used
        $this->assertTrue(RewardService::isUsed($this->student, 3));
    }

    public function test_brain_power_shield_protects_student_from_one_wrong_answer_in_quiz(): void
    {
        $this->actingAs($this->student);

        // Activate Brain Shield (Level 4)
        $this->student->update(['active_brain_shield' => true]);

        $quiz = Quiz::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Chemistry Acids & Bases',
            'grade' => 'Grade 8',
            'is_published' => true,
        ]);
        $quiz->gradeLevels()->attach($this->gradeLevel->id);

        $q1 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is the pH of water?',
            'type' => 'multiple_choice',
            'points' => 5,
            'order' => 1,
        ]);
        $optCorrect1 = QuizOption::create(['question_id' => $q1->id, 'option_text' => '7', 'is_correct' => true, 'order' => 1]);
        $optWrong1 = QuizOption::create(['question_id' => $q1->id, 'option_text' => '14', 'is_correct' => false, 'order' => 2]);

        $q2 = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'What is hydrochloric acid?',
            'type' => 'multiple_choice',
            'points' => 5,
            'order' => 2,
        ]);
        $optCorrect2 = QuizOption::create(['question_id' => $q2->id, 'option_text' => 'HCl', 'is_correct' => true, 'order' => 1]);
        $optWrong2 = QuizOption::create(['question_id' => $q2->id, 'option_text' => 'NaCl', 'is_correct' => false, 'order' => 2]);

        // Student starts quiz
        $this->get("/student/quizzes/{$quiz->id}/take")->assertOk();

        // Submit with 1 correct and 1 wrong answer
        $res = $this->post("/student/quizzes/{$quiz->id}/submit", [
            'answers' => [
                $q1->id => $optCorrect1->id, // Correct (+5)
                $q2->id => $optWrong2->id,   // Wrong, but Brain Shield forgives it (+5)
            ],
        ]);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $this->student->id)->first();
        $this->assertNotNull($attempt);
        // Both questions credited (5 + 5 = 10)
        $this->assertEquals(10, $attempt->score);

        $this->student->refresh();
        $this->assertFalse((bool) $this->student->active_brain_shield);
        $this->assertTrue(RewardService::isUsed($this->student, 4));
    }

    public function test_time_warp_hourglass_extends_timer_and_marks_consumed(): void
    {
        $this->actingAs($this->student);

        $quiz = Quiz::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Speed Physics Quiz',
            'grade' => 'Grade 8',
            'time_limit_minutes' => 10,
            'is_published' => true,
        ]);

        $res = $this->postJson('/student/rewards/use-time-warp', [
            'quiz_id' => $quiz->id,
        ]);

        $res->assertOk();
        $res->assertJsonFragment(['ok' => true, 'added_seconds' => 30]);

        $this->assertTrue(RewardService::isUsed($this->student, 5));
    }

    public function test_star_compass_eliminates_two_wrong_options(): void
    {
        $this->actingAs($this->student);

        $options = [
            ['id' => 1, 'option_text' => 'Mars', 'is_correct' => false],
            ['id' => 2, 'option_text' => 'Jupiter', 'is_correct' => false],
            ['id' => 3, 'option_text' => 'Earth', 'is_correct' => true],
            ['id' => 4, 'option_text' => 'Saturn', 'is_correct' => false],
        ];

        $res = $this->postJson('/student/rewards/use-star-compass', [
            'question_id' => 99,
            'options' => $options,
        ]);

        $res->assertOk();
        $data = $res->json();
        $this->assertTrue($data['ok']);
        $this->assertCount(2, $data['eliminated_options']);
        $this->assertNotContains(3, $data['eliminated_options']); // Correct option 3 never eliminated

        $this->assertTrue(RewardService::isUsed($this->student, 6));
    }

    public function test_locked_rewards_cannot_be_consumed(): void
    {
        // Student with 0 XP is Level 1
        $novice = User::factory()->create([
            'role' => 'student',
            'total_points' => 0,
        ]);

        $this->actingAs($novice);

        // Cannot use Level 3 Homework Pass
        $res = $this->postJson('/student/rewards/use-homework-pass', ['seatwork_id' => 1]);
        $res->assertStatus(422);

        // Cannot use Level 5 Time Warp
        $res2 = $this->postJson('/student/rewards/use-time-warp', ['quiz_id' => 1]);
        $res2->assertStatus(422);
    }

    private function setStudentPoints(int $points): void
    {
        \App\Models\StudentPoint::where('student_id', $this->student->id)->update(['points' => $points]);
        $this->student->update(['total_points' => $points]);
    }

    private function makeQuizWithTwoQuestions(): array
    {
        $quiz = Quiz::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Reward Quiz',
            'grade' => 'Grade 8',
            'is_published' => true,
        ]);
        $quiz->gradeLevels()->attach($this->gradeLevel->id);

        $questions = [];
        foreach ([1, 2] as $i) {
            $q = Question::create(['quiz_id' => $quiz->id, 'question_text' => "Q{$i}", 'type' => 'multiple_choice', 'points' => 5, 'order' => $i]);
            $right = QuizOption::create(['question_id' => $q->id, 'option_text' => 'Right', 'is_correct' => true, 'order' => 1]);
            $wrong = QuizOption::create(['question_id' => $q->id, 'option_text' => 'Wrong', 'is_correct' => false, 'order' => 2]);
            $questions[] = [$q, $right, $wrong];
        }

        return [$quiz, $questions];
    }

    public function test_unarmed_shield_does_not_auto_apply(): void
    {
        $this->actingAs($this->student);
        [$quiz, $qs] = $this->makeQuizWithTwoQuestions();

        $this->get("/student/quizzes/{$quiz->id}/take")->assertOk();
        $this->post("/student/quizzes/{$quiz->id}/submit", [
            'answers' => [$qs[0][0]->id => $qs[0][1]->id, $qs[1][0]->id => $qs[1][2]->id],
        ])->assertRedirect();

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $this->student->id)->first();
        $this->assertEquals(5, $attempt->score);
        $this->assertFalse(RewardService::isUsed($this->student, 4));
    }

    public function test_homework_pass_can_complete_a_quiz(): void
    {
        $this->actingAs($this->student);
        [$quiz, $qs] = $this->makeQuizWithTwoQuestions();

        $this->post('/student/rewards/use-homework-pass', ['type' => 'quiz', 'id' => $quiz->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $this->student->id)->first();
        $this->assertEquals(\App\Enums\AttemptStatus::Submitted->value, $attempt->status);
        $this->assertEquals(10, $attempt->score);
        $this->assertEquals(2, \Illuminate\Support\Facades\DB::table('quiz_answers')->where('quiz_attempt_id', $attempt->id)->where('is_correct', true)->count());
        $this->assertTrue(RewardService::isUsed($this->student, 3));

        // Single pass is gone now
        $this->postJson('/student/rewards/use-homework-pass', ['type' => 'quiz', 'id' => $quiz->id])->assertStatus(422);
    }

    public function test_xp_grant_reward_adds_xp_once(): void
    {
        $this->setStudentPoints(600); // Level 7
        $this->actingAs($this->student);

        $this->post('/student/rewards/7/use')->assertRedirect()->assertSessionHas('success');
        $this->student->refresh();
        $this->assertEquals(650, $this->student->total_points);
        $this->assertTrue(RewardService::isUsed($this->student, 7));

        $this->post('/student/rewards/7/use')->assertRedirect();
        $this->student->refresh();
        $this->assertEquals(650, $this->student->total_points);
    }

    public function test_only_one_booster_can_be_active_and_higher_boosters_apply(): void
    {
        $this->setStudentPoints(800); // Level 9
        $this->actingAs($this->student);

        $this->post('/student/rewards/1/use')->assertRedirect();
        $this->post('/student/rewards/8/use')->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseMissing('reward_redemptions', ['user_id' => $this->student->id, 'level' => 8]);

        // Swap to the 1.5× Focus Potion
        $this->post('/student/rewards/1/use')->assertRedirect();
        $this->post('/student/rewards/8/use')->assertRedirect()->assertSessionHas('success');

        $quiz = Quiz::create(['teacher_id' => $this->teacher->id, 'title' => 'Boost', 'grade' => 'Grade 8', 'is_published' => true]);
        PointsHelper::award($this->student->id, 'Quiz', $quiz->id, 10, 10);

        $this->student->refresh();
        $this->assertEquals(815, $this->student->total_points);
        $this->assertTrue(RewardService::isUsed($this->student, 8));
        $this->assertFalse(RewardService::isUsed($this->student, 1));
    }

    public function test_streak_freeze_reward_adds_freezes(): void
    {
        $this->setStudentPoints(1700); // Level 18
        $this->student->update(['streak_freeze_count' => 1]);
        $this->actingAs($this->student);

        $this->post('/student/rewards/18/use')->assertRedirect()->assertSessionHas('success');
        $this->student->refresh();
        $this->assertEquals(2, $this->student->streak_freeze_count);
        $this->assertTrue(RewardService::isUsed($this->student, 18));
    }

    public function test_equipped_badge_adds_xp_bonus(): void
    {
        $this->student->update(['equipped_badge' => 'golden_pencil']); // +5%
        $quiz = Quiz::create(['teacher_id' => $this->teacher->id, 'title' => 'Badge', 'grade' => 'Grade 8', 'is_published' => true]);

        PointsHelper::award($this->student->id, 'Quiz', $quiz->id, 10, 10);

        $this->student->refresh();
        $this->assertEquals(511, $this->student->total_points);
    }

    public function test_student_can_use_homework_pass_to_complete_practical_with_full_marks(): void
    {
        $this->actingAs($this->student);

        $practical = Practical::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'HTML Portfolio Project',
            'grade' => 'Grade 8',
            'is_published' => true,
            'max_attempts' => 2,
        ]);
        $practical->gradeLevels()->attach($this->gradeLevel->id);

        PracticalCriterion::create([
            'practical_id' => $practical->id,
            'criterion_name' => 'Structure & Semantics',
            'max_points' => 30,
            'order' => 1,
        ]);
        PracticalCriterion::create([
            'practical_id' => $practical->id,
            'criterion_name' => 'Design & Styling',
            'max_points' => 20,
            'order' => 2,
        ]);

        $res = $this->post('/student/rewards/use-homework-pass', [
            'type' => 'practical',
            'id' => $practical->id,
        ]);
        $res->assertRedirect();
        $res->assertSessionHas('success');

        $attempt = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($attempt);
        $this->assertEquals(50, $attempt->total_score);
        $this->assertEquals(\App\Enums\AttemptStatus::Submitted->value, $attempt->status);

        $this->assertDatabaseHas('practical_scores', [
            'attempt_id' => $attempt->id,
            'score' => 30,
        ]);
        $this->assertDatabaseHas('practical_scores', [
            'attempt_id' => $attempt->id,
            'score' => 20,
        ]);

        $this->assertTrue(RewardService::isUsed($this->student, 3));
    }

    public function test_student_cannot_use_homework_pass_on_closed_practical(): void
    {
        $this->actingAs($this->student);

        $practical = Practical::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Closed Database Practical',
            'grade' => 'Grade 8',
            'is_published' => true,
            'closes_at' => now()->subDay(),
        ]);
        $practical->gradeLevels()->attach($this->gradeLevel->id);

        $res = $this->postJson('/student/rewards/use-homework-pass', [
            'type' => 'practical',
            'id' => $practical->id,
        ]);
        $res->assertStatus(422);

        $this->assertDatabaseMissing('practical_attempts', [
            'practical_id' => $practical->id,
            'student_id' => $this->student->id,
        ]);
        $this->assertFalse(RewardService::isUsed($this->student, 3));
    }

    public function test_homework_pass_from_take_view_redirects_to_practical_result(): void
    {
        $this->actingAs($this->student);

        $practical = Practical::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Algorithm Challenge',
            'grade' => 'Grade 8',
            'is_published' => true,
            'max_score' => 100,
        ]);
        $practical->gradeLevels()->attach($this->gradeLevel->id);

        $res = $this->from("/student/practicals/{$practical->id}/take")
            ->post('/student/rewards/use-homework-pass', [
                'practical_id' => $practical->id,
            ]);

        $attempt = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $this->student->id)
            ->first();

        $this->assertNotNull($attempt);
        $res->assertRedirect(route('student.practicals.result', ['attempt' => $attempt->id]));
    }
}
