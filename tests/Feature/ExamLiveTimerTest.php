<?php

namespace Tests\Feature;

use App\Events\ExamSessionClosed;
use App\Events\ExamTimerExpired;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GradeLevel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ExamLiveTimerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private GradeLevel $gradeLevel;
    private Exam $exam;
    private ExamAttempt $attempt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->gradeLevel = GradeLevel::create(['name' => 'Grade 8']);

        $this->student = User::factory()->create([
            'role' => 'student',
            'grade' => '8',
            'grade_level_id' => $this->gradeLevel->id,
        ]);

        $this->exam = Exam::create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Live Sync Midterm Exam',
            'grade' => '8',
            'time_limit_minutes' => 60,
            'is_published' => true,
            'closes_at' => now()->addDay(),
        ]);

        $this->attempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function test_student_can_sync_timer_when_attempt_is_active(): void
    {
        Event::fake([ExamTimerExpired::class]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.exams.sync-timer', $this->attempt->id));

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'attempt_id' => $this->attempt->id,
                'is_expired' => false,
            ]);

        $this->assertNotNull($response->json('remaining_seconds'));
        $this->assertGreaterThan(3500, $response->json('remaining_seconds'));
        Event::assertNotDispatched(ExamTimerExpired::class);
    }

    public function test_sync_timer_dispatches_expired_event_when_time_limit_exceeded(): void
    {
        Event::fake([ExamTimerExpired::class]);

        // Start time was 65 minutes ago for a 60-minute exam
        $this->attempt->update(['started_at' => now()->subMinutes(65)]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.exams.sync-timer', $this->attempt->id));

        $response->assertOk()
            ->assertJson([
                'ok' => true,
                'attempt_id' => $this->attempt->id,
                'remaining_seconds' => 0,
                'is_expired' => true,
            ]);

        Event::assertDispatched(ExamTimerExpired::class, function ($event) {
            return $event->examId === $this->exam->id && $event->attemptId === $this->attempt->id;
        });
    }

    public function test_another_student_cannot_sync_timer_for_different_attempt(): void
    {
        $otherStudent = User::factory()->create([
            'role' => 'student',
            'grade' => '8',
        ]);

        $response = $this->actingAs($otherStudent)
            ->postJson(route('student.exams.sync-timer', $this->attempt->id));

        $response->assertForbidden();
    }

    public function test_teacher_closing_exam_now_dispatches_session_closed_event(): void
    {
        Event::fake([ExamSessionClosed::class]);

        $response = $this->actingAs($this->teacher)
            ->patch(route('teacher.exams.close-now', $this->exam->id));

        $response->assertRedirect();
        $this->assertTrue($this->exam->fresh()->isClosed());

        Event::assertDispatched(ExamSessionClosed::class, function ($event) {
            return $event->examId === $this->exam->id;
        });
    }

    public function test_teacher_unpublishing_exam_dispatches_session_closed_event(): void
    {
        Event::fake([ExamSessionClosed::class]);

        $response = $this->actingAs($this->teacher)
            ->patch(route('teacher.exams.publish', $this->exam->id));

        $response->assertRedirect();
        $this->assertFalse($this->exam->fresh()->is_published);

        Event::assertDispatched(ExamSessionClosed::class, function ($event) {
            return $event->examId === $this->exam->id;
        });
    }

    public function test_student_can_auto_submit_in_progress_attempt_after_exam_closes(): void
    {
        // Teacher closes exam
        $this->exam->update(['closes_at' => now()->subMinute()]);

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.submit', $this->exam->id), [
                'answers' => [],
                'attempt_id' => $this->attempt->id,
            ]);

        $response->assertRedirect(route('student.exams.result', $this->attempt->id));
        $this->assertEquals('submitted', $this->attempt->fresh()->status);
    }

    public function test_submission_uses_the_active_attempt_and_redirects_to_its_result_when_other_attempts_exist(): void
    {
        $otherAttempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'total_score' => 5,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.submit', $this->exam->id), [
                'answers' => [],
                'attempt_id' => $this->attempt->id,
            ]);

        $response->assertStatus(303)
            ->assertRedirect(route('student.exams.result', $this->attempt->id));

        $this->assertDatabaseHas('exam_attempts', [
            'id' => $this->attempt->id,
            'status' => 'submitted',
        ]);
        $this->assertDatabaseHas('exam_attempts', [
            'id' => $otherAttempt->id,
            'status' => 'submitted',
            'total_score' => 5,
        ]);
    }

    public function test_take_prefers_an_in_progress_attempt_over_a_newer_submitted_attempt(): void
    {
        $submittedAttempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'total_score' => 5,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.take', $this->exam->id));

        $response->assertInertia(fn ($page) => $page
            ->component('Student/Exam/Take')
            ->where('attempt.id', $this->attempt->id)
        );

        $this->assertNotSame($this->attempt->id, $submittedAttempt->id);
    }

    public function test_exam_listing_points_to_the_active_attempt_when_multiple_attempts_exist(): void
    {
        $this->student->teachers()->attach($this->teacher->id);
        $this->exam->gradeLevels()->sync([$this->gradeLevel->id]);
        $this->exam->sections()->create([
            'section_type' => 'questions',
            'title' => 'Questions',
            'order' => 1,
        ]);

        $submittedAttempt = ExamAttempt::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'total_score' => 5,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now()->subMinutes(5),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.index'));

        $response->assertInertia(fn ($page) => $page
            ->component('Student/Exams/Index')
            ->where('exams.0.status', 'in_progress')
            ->where('exams.0.attempt_id', $this->attempt->id)
        );

        $this->assertNotSame($this->attempt->id, $submittedAttempt->id);
    }
}
