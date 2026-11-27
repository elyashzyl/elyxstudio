<?php

namespace App\Services\AiManager\Tasks;

use App\Models\AiManagerLog;
use App\Models\QuizAttempt;
use App\Models\SeatworkAttempt;
use App\Services\AiManager\GuardrailPolicy;
use Illuminate\Support\Facades\Log;

class AnomalyDetectorTask
{
    /**
     * Execute anomaly detection audits.
     *
     * @return array{anomalies_flagged: int, abandoned_resolved: int, orphans_found: int}
     */
    public function execute(string $mode): array
    {
        $stats = [
            'anomalies_flagged' => 0,
            'abandoned_resolved' => 0,
            'orphans_found' => 0,
        ];

        $isAutonomous = GuardrailPolicy::allowsAutonomousMutation($mode);

        // 1. Detect suspiciously fast student submissions (< 10 seconds with score >= 80%)
        $this->detectFastSubmissions($stats);

        // 2. Detect and handle abandoned in-progress sessions (> 24h old)
        $this->handleAbandonedSessions($isAutonomous, $stats);

        // 3. Detect orphan attempts with missing student records
        $this->detectOrphanRecords($stats);

        return $stats;
    }

    protected function detectFastSubmissions(array &$stats): void
    {
        $recentQuizAttempts = QuizAttempt::with('quiz')
            ->where('status', 'submitted')
            ->whereNotNull('started_at')
            ->whereNotNull('submitted_at')
            ->whereNotNull('score')
            ->where('submitted_at', '>=', now()->subDays(3))
            ->latest('submitted_at')
            ->limit(50)
            ->get();

        foreach ($recentQuizAttempts as $attempt) {
            $durationSeconds = $attempt->submitted_at->diffInSeconds($attempt->started_at);
            $totalPoints = $attempt->total_points ?: ($attempt->quiz?->totalPoints() ?: 0);
            $score = $attempt->score ?? 0;

            if ($durationSeconds < 10 && $totalPoints > 0 && ($score / $totalPoints) >= 0.80) {
                // Check if already flagged recently
                $alreadyFlagged = AiManagerLog::where('event_type', 'anomaly_flag')
                    ->where('target_type', QuizAttempt::class)
                    ->where('target_id', $attempt->id)
                    ->exists();

                if (! $alreadyFlagged) {
                    AiManagerLog::create([
                        'event_type' => 'anomaly_flag',
                        'status' => 'flagged_for_review',
                        'target_type' => QuizAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => 0.95,
                        'summary' => "Suspiciously fast submission: Quiz #{$attempt->quiz_id} finished in {$durationSeconds}s with score {$score}/{$totalPoints} by student #{$attempt->student_id}",
                        'payload' => [
                            'anomaly_type' => 'fast_submission',
                            'duration_seconds' => $durationSeconds,
                            'score' => $score,
                            'total_points' => $totalPoints,
                        ],
                    ]);

                    $stats['anomalies_flagged']++;
                }
            }
        }
    }

    protected function handleAbandonedSessions(bool $isAutonomous, array &$stats): void
    {
        $staleQuizSessions = QuizAttempt::where('status', 'in_progress')
            ->where('started_at', '<=', now()->subHours(24))
            ->limit(25)
            ->get();

        foreach ($staleQuizSessions as $attempt) {
            $alreadyFlagged = AiManagerLog::where('event_type', 'anomaly_flag')
                ->where('target_type', QuizAttempt::class)
                ->where('target_id', $attempt->id)
                ->exists();

            if ($isAutonomous) {
                // Auto-close abandoned session
                $score = (int) $attempt->answers()->where('is_correct', true)->count();
                $attempt->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'score' => $score,
                ]);

                AiManagerLog::create([
                    'event_type' => 'anomaly_flag',
                    'status' => 'success',
                    'target_type' => QuizAttempt::class,
                    'target_id' => $attempt->id,
                    'confidence' => 1.00,
                    'summary' => "Auto-submitted abandoned quiz session #{$attempt->id} (inactive > 24 hours, awarded {$score} points)",
                    'payload' => [
                        'anomaly_type' => 'abandoned_session_resolved',
                        'score' => $score,
                    ],
                ]);

                $stats['abandoned_resolved']++;
            } elseif (! $alreadyFlagged) {
                AiManagerLog::create([
                    'event_type' => 'anomaly_flag',
                    'status' => 'flagged_for_review',
                    'target_type' => QuizAttempt::class,
                    'target_id' => $attempt->id,
                    'confidence' => 1.00,
                    'summary' => "Abandoned quiz session detected: Attempt #{$attempt->id} in progress since " . $attempt->started_at?->diffForHumans(),
                    'payload' => [
                        'anomaly_type' => 'abandoned_session',
                        'started_at' => (string) $attempt->started_at,
                    ],
                ]);

                $stats['anomalies_flagged']++;
            }
        }
    }

    protected function detectOrphanRecords(array &$stats): void
    {
        $orphanQuizAttempts = QuizAttempt::whereDoesntHave('student')
            ->limit(10)
            ->get();

        foreach ($orphanQuizAttempts as $attempt) {
            $alreadyFlagged = AiManagerLog::where('event_type', 'anomaly_flag')
                ->where('target_type', QuizAttempt::class)
                ->where('target_id', $attempt->id)
                ->exists();

            if (! $alreadyFlagged) {
                AiManagerLog::create([
                    'event_type' => 'anomaly_flag',
                    'status' => 'flagged_for_review',
                    'target_type' => QuizAttempt::class,
                    'target_id' => $attempt->id,
                    'confidence' => 1.00,
                    'summary' => "Orphan record: Quiz attempt #{$attempt->id} references missing student #{$attempt->student_id}",
                    'payload' => [
                        'anomaly_type' => 'orphan_attempt',
                        'student_id' => $attempt->student_id,
                    ],
                ]);

                $stats['orphans_found']++;
            }
        }
    }
}
