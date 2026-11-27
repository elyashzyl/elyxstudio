<?php

use App\Models\AiManagerLog;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\PracticalCriterion;
use App\Models\PracticalScore;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Setting;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\AiManager\AiSystemManagerService;
use App\Services\AiManager\GuardrailPolicy;
use App\Services\AiManager\Tasks\AnomalyDetectorTask;
use App\Services\AiManager\Tasks\AutoGraderTask;
use App\Services\AiManager\Tasks\StorageHealerTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create([
        'name' => 'Grade 10',
        'code' => 'G10',
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

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
});

test('autonomous run is halted when ai_manager_enabled is false', function () {
    Setting::set('ai_manager_enabled', 'false');

    $service = new AiSystemManagerService();
    $result = $service->runInspection();

    expect($result['skipped'])->toBeTrue()
        ->and($result['success'])->toBeFalse()
        ->and($result['summary'])->toContain('disabled');

    expect(AiManagerLog::where('event_type', 'inspection')->count())->toBe(0);
});

test('force flag runs inspection even if ai_manager_enabled is false', function () {
    Setting::set('ai_manager_enabled', 'false');

    $service = new AiSystemManagerService();
    $result = $service->runInspection(force: true, triggeredBy: 'manual_test');

    expect($result['skipped'])->toBeFalse()
        ->and($result['success'])->toBeTrue()
        ->and($result['summary'])->toContain('AI Manager inspection completed');

    expect(AiManagerLog::where('event_type', 'inspection')->count())->toBe(1);
});

test('guardrail policy asserts forbidden actions and protected models throw DomainException', function () {
    expect(fn () => GuardrailPolicy::assertActionAllowed('delete', User::class))
        ->toThrow(DomainException::class);

    expect(fn () => GuardrailPolicy::assertActionAllowed('destroy', Quiz::class))
        ->toThrow(DomainException::class);

    expect(fn () => GuardrailPolicy::assertActionAllowed('migrate:fresh'))
        ->toThrow(DomainException::class);

    expect(fn () => GuardrailPolicy::assertActionAllowed('db:wipe'))
        ->toThrow(DomainException::class);

    expect(fn () => GuardrailPolicy::assertActionAllowed('truncate', Practical::class))
        ->toThrow(DomainException::class);

    expect(fn () => GuardrailPolicy::assertActionAllowed('inspect'))
        ->not->toThrow(DomainException::class);
});

test('guardrail policy asserts protected settings throw DomainException', function () {
    expect(fn () => GuardrailPolicy::assertSettingMutable('support_email'))
        ->toThrow(DomainException::class);

    expect(fn () => GuardrailPolicy::assertSettingMutable('payment_email'))
        ->toThrow(DomainException::class);

    expect(fn () => GuardrailPolicy::assertSettingMutable('ai_manager_mode'))
        ->not->toThrow(DomainException::class);
});

test('guardrail policy verifies confidence threshold floor', function () {
    expect(GuardrailPolicy::canAutoApproveScore(0.84, 0.85))->toBeFalse()
        ->and(GuardrailPolicy::canAutoApproveScore(0.85, 0.85))->toBeTrue()
        ->and(GuardrailPolicy::canAutoApproveScore(0.95, 0.85))->toBeTrue()
        ->and(GuardrailPolicy::allowsAutonomousMutation('autonomous'))->toBeTrue()
        ->and(GuardrailPolicy::allowsAutonomousMutation('advisory'))->toBeFalse()
        ->and(GuardrailPolicy::allowsAutonomousMutation('approval'))->toBeFalse();
});

test('guardrail flags practical submissions for review when confidence is below threshold', function () {
    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Biology Lab Practical',
        'max_score' => 50,
        'is_published' => true,
    ]);

    PracticalCriterion::create([
        'practical_id' => $practical->id,
        'criterion_name' => 'Hypothesis Formulation',
        'description' => 'Clear scientific hypothesis',
        'max_points' => 50,
        'order' => 1,
    ]);

    $attempt = PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $this->student->id,
        'status' => 'submitted',
        'submission_text' => '',
        'submission_file' => null,
        'submitted_at' => now(),
    ]);

    // High threshold 0.95: Rubric confidence is 0.90, so 0.90 < 0.95 triggers flagged_for_review
    $task = new AutoGraderTask();
    $stats = $task->execute('autonomous', 0.95);

    expect($stats['flagged'])->toBe(1)
        ->and($stats['graded'])->toBe(0);

    $attempt->refresh();
    expect($attempt->total_score)->toBeNull();

    $log = AiManagerLog::where('target_type', PracticalAttempt::class)
        ->where('target_id', $attempt->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('flagged_for_review')
        ->and($log->event_type)->toBe('auto_grade');
});

test('autonomous mode auto-grades practical attempt when confidence meets threshold', function () {
    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Chemistry Titration Practical',
        'max_score' => 20,
        'is_published' => true,
    ]);

    PracticalCriterion::create([
        'practical_id' => $practical->id,
        'criterion_name' => 'Safety Protocols',
        'description' => 'Adherence to lab safety',
        'max_points' => 20,
        'order' => 1,
    ]);

    $attempt = PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $this->student->id,
        'status' => 'submitted',
        'submission_text' => '',
        'submission_file' => null,
        'submitted_at' => now(),
    ]);

    // Threshold 0.85: Empty submission evaluated cleanly with confidence 0.90 >= 0.85
    $task = new AutoGraderTask();
    $stats = $task->execute('autonomous', 0.85);

    expect($stats['graded'])->toBe(1);

    $attempt->refresh();
    expect($attempt->total_score)->toBe(0);

    $log = AiManagerLog::where('target_type', PracticalAttempt::class)
        ->where('target_id', $attempt->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('success')
        ->and($log->canBeReverted())->toBeTrue();
});

test('one-click rollback restores previous practical attempt score and status cleanly', function () {
    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Physics Optics Practical',
        'max_score' => 30,
        'is_published' => true,
    ]);

    $criterion = PracticalCriterion::create([
        'practical_id' => $practical->id,
        'criterion_name' => 'Focal Length Measurement',
        'description' => 'Precise convex lens measurement',
        'max_points' => 30,
        'order' => 1,
    ]);

    $attempt = PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $this->student->id,
        'status' => 'submitted',
        'total_score' => 28,
        'submitted_at' => now(),
    ]);

    PracticalScore::create([
        'attempt_id' => $attempt->id,
        'criterion_id' => $criterion->id,
        'score' => 28,
        'comment' => 'Great measurement technique',
    ]);

    $log = AiManagerLog::create([
        'event_type' => 'auto_grade',
        'status' => 'success',
        'target_type' => PracticalAttempt::class,
        'target_id' => $attempt->id,
        'confidence' => 0.90,
        'summary' => 'Auto-graded practical optics',
        'payload' => [
            'previous_score' => null,
            'previous_status' => 'submitted',
            'new_score' => 28,
            'previous_criteria_scores' => null,
        ],
    ]);

    expect($log->canBeReverted())->toBeTrue();

    $reverted = $log->revert($this->superadmin);
    expect($reverted)->toBeTrue();

    $attempt->refresh();
    expect($attempt->total_score)->toBeNull()
        ->and($attempt->scores()->count())->toBe(0);

    $log->refresh();
    expect($log->reverted_at)->not->toBeNull()
        ->and($log->canBeReverted())->toBeFalse();
});

test('superadmin can rollback ai action via controller endpoint', function () {
    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Biology Dissection',
        'max_score' => 40,
        'is_published' => true,
    ]);

    $attempt = PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $this->student->id,
        'status' => 'submitted',
        'total_score' => 35,
        'submitted_at' => now(),
    ]);

    $log = AiManagerLog::create([
        'event_type' => 'auto_grade',
        'status' => 'success',
        'target_type' => PracticalAttempt::class,
        'target_id' => $attempt->id,
        'confidence' => 0.90,
        'summary' => 'Auto-graded biology dissection',
        'payload' => [
            'previous_score' => null,
            'previous_status' => 'submitted',
            'new_score' => 35,
        ],
    ]);

    $response = $this->actingAs($this->superadmin)
        ->from('/teacher/ai-manager')
        ->post("/teacher/ai-manager/logs/{$log->id}/revert");

    $response->assertRedirect('/teacher/ai-manager')
        ->assertSessionHas('success');

    $attempt->refresh();
    expect($attempt->total_score)->toBeNull();

    $log->refresh();
    expect($log->reverted_at)->not->toBeNull();
});

test('non-superadmin cannot rollback ai action via controller endpoint', function () {
    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Geology Rock Classification',
        'max_score' => 25,
        'is_published' => true,
    ]);

    $attempt = PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $this->student->id,
        'status' => 'submitted',
        'total_score' => 20,
        'submitted_at' => now(),
    ]);

    $log = AiManagerLog::create([
        'event_type' => 'auto_grade',
        'status' => 'success',
        'target_type' => PracticalAttempt::class,
        'target_id' => $attempt->id,
        'confidence' => 0.90,
        'summary' => 'Auto-graded geology rocks',
        'payload' => [
            'previous_score' => null,
            'previous_status' => 'submitted',
            'new_score' => 20,
        ],
    ]);

    // Teacher forbidden
    $this->actingAs($this->teacher)
        ->post("/teacher/ai-manager/logs/{$log->id}/revert")
        ->assertForbidden();

    // Student forbidden
    $this->actingAs($this->student)
        ->post("/teacher/ai-manager/logs/{$log->id}/revert")
        ->assertForbidden();

    $log->refresh();
    expect($log->reverted_at)->toBeNull();
});

test('storage healer task restores missing material file from database base64 in autonomous mode', function () {
    $testContent = 'EduPulse database file backup test content ' . uniqid();
    $relative = 'materials/self_heal_test_' . uniqid() . '.txt';
    $dest = storage_path('app/public/' . $relative);

    if (file_exists($dest)) {
        @unlink($dest);
    }

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Self Healing Test Material',
        'subject' => 'Science',
        'file_path' => $relative,
        'file_name' => basename($relative),
        'file_size' => strlen($testContent),
        'file_type' => 'text/plain',
        'file_data' => base64_encode($testContent),
    ]);

    try {
        $task = new StorageHealerTask();
        $stats = $task->execute('autonomous');

        expect($stats['healed'])->toBeGreaterThanOrEqual(1);
        expect(file_exists($dest))->toBeTrue();
        expect(file_get_contents($dest))->toBe($testContent);

        $log = AiManagerLog::where('event_type', 'storage_heal')
            ->where('target_type', TeachingMaterial::class)
            ->where('target_id', $material->id)
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->status)->toBe('success')
            ->and($log->summary)->toContain('Self-healed missing file');
    } finally {
        if (file_exists($dest)) {
            @unlink($dest);
        }
    }
});

test('storage healer task flags missing material file without restoring in advisory mode', function () {
    $testContent = 'Advisory mode unhealed file content ' . uniqid();
    $relative = 'materials/advisory_test_' . uniqid() . '.txt';
    $dest = storage_path('app/public/' . $relative);

    if (file_exists($dest)) {
        @unlink($dest);
    }

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Advisory Test Material',
        'subject' => 'Math',
        'file_path' => $relative,
        'file_name' => basename($relative),
        'file_size' => strlen($testContent),
        'file_type' => 'text/plain',
        'file_data' => base64_encode($testContent),
    ]);

    try {
        $task = new StorageHealerTask();
        $stats = $task->execute('advisory');

        expect($stats['flagged'])->toBeGreaterThanOrEqual(1);
        expect(file_exists($dest))->toBeFalse();

        $log = AiManagerLog::where('event_type', 'storage_heal')
            ->where('target_type', TeachingMaterial::class)
            ->where('target_id', $material->id)
            ->first();

        expect($log)->not->toBeNull()
            ->and($log->status)->toBe('flagged_for_review')
            ->and($log->summary)->toContain('Missing disk file');
    } finally {
        if (file_exists($dest)) {
            @unlink($dest);
        }
    }
});

test('anomaly detector flags suspiciously fast submissions with high score', function () {
    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Calculus Speed Test',
        'grade' => 'Grade 10',
        'description' => 'Derivatives and integrals',
        'is_published' => true,
    ]);

    $attempt = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'score' => 95,
        'total_points' => 100,
        'status' => 'submitted',
        'started_at' => now()->subSeconds(4),
        'submitted_at' => now(),
    ]);

    $task = new AnomalyDetectorTask();
    $stats = $task->execute('autonomous');

    expect($stats['anomalies_flagged'])->toBeGreaterThanOrEqual(1);

    $log = AiManagerLog::where('event_type', 'anomaly_flag')
        ->where('target_type', QuizAttempt::class)
        ->where('target_id', $attempt->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('flagged_for_review')
        ->and($log->summary)->toContain('Suspiciously fast submission');
});

test('anomaly detector auto-submits abandoned quiz sessions older than 24 hours in autonomous mode', function () {
    $quiz = Quiz::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Ancient World History',
        'grade' => 'Grade 10',
        'description' => 'Civilizations of the Mediterranean',
        'is_published' => true,
    ]);

    $attempt = QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $this->student->id,
        'score' => 0,
        'total_points' => 50,
        'status' => 'in_progress',
        'started_at' => now()->subHours(26),
        'submitted_at' => null,
    ]);

    $task = new AnomalyDetectorTask();
    $stats = $task->execute('autonomous');

    expect($stats['abandoned_resolved'])->toBeGreaterThanOrEqual(1);

    $attempt->refresh();
    expect($attempt->status)->toBe('submitted')
        ->and($attempt->score)->toBe(0)
        ->and($attempt->submitted_at)->not->toBeNull();

    $log = AiManagerLog::where('event_type', 'anomaly_flag')
        ->where('target_type', QuizAttempt::class)
        ->where('target_id', $attempt->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->status)->toBe('success')
        ->and($log->summary)->toContain('Auto-submitted abandoned quiz session');
});

test('artisan edupulse:ai-manager-tick command executes successfully', function () {
    Setting::set('ai_manager_enabled', 'false');

    $this->artisan('edupulse:ai-manager-tick')
        ->expectsOutputToContain('disabled')
        ->assertSuccessful();

    $this->artisan('edupulse:ai-manager-tick --force')
        ->expectsOutputToContain('AI Manager inspection completed')
        ->assertSuccessful();
});

test('superadmin can view ai manager logs and trigger manual inspection', function () {
    $this->actingAs($this->superadmin)
        ->get('/teacher/ai-manager')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Teacher/AiManagerLogs'));

    $response = $this->actingAs($this->superadmin)
        ->from('/teacher/ai-manager')
        ->post('/teacher/ai-manager/run');

    $response->assertRedirect('/teacher/ai-manager')
        ->assertSessionHas('success');

    expect(AiManagerLog::where('event_type', 'inspection')->count())->toBeGreaterThanOrEqual(1);
});

test('teacher cannot access ai manager logs or trigger manual inspection', function () {
    $this->actingAs($this->teacher)
        ->get('/teacher/ai-manager')
        ->assertForbidden();

    $this->actingAs($this->teacher)
        ->post('/teacher/ai-manager/run')
        ->assertForbidden();
});
