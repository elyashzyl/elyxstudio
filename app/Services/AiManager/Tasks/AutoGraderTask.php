<?php

namespace App\Services\AiManager\Tasks;

use App\Helpers\PointsHelper;
use App\Models\AiManagerLog;
use App\Models\ExamAttempt;
use App\Models\PracticalAttempt;
use App\Models\QuizAttempt;
use App\Models\SeatworkAttempt;
use App\Services\ActivityAiGraderService;
use App\Services\AiConfigService;
use App\Services\AiManager\GuardrailPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoGraderTask
{
    /**
     * Execute the auto-grader task across pending attempts.
     *
     * @return array{processed: int, graded: int, flagged: int, skipped: int}
     */
    public function execute(string $mode, float $confidenceThreshold): array
    {
        $stats = [
            'processed' => 0,
            'graded' => 0,
            'flagged' => 0,
            'skipped' => 0,
        ];

        // 1. Grade pending Practical attempts
        $this->gradePracticalAttempts($mode, $confidenceThreshold, $stats);

        // 2. Grade pending Seatwork attempts
        $this->gradeSeatworkAttempts($mode, $confidenceThreshold, $stats);

        // 3. Grade pending Quiz attempts
        $this->gradeQuizAttempts($mode, $confidenceThreshold, $stats);

        // 4. Grade pending Exam attempts
        $this->gradeExamAttempts($mode, $confidenceThreshold, $stats);

        return $stats;
    }

    protected function gradePracticalAttempts(string $mode, float $threshold, array &$stats): void
    {
        $attempts = PracticalAttempt::with(['practical.criteria', 'scores'])
            ->where('status', 'submitted')
            ->whereDoesntHave('scores')
            ->latest('submitted_at')
            ->limit(15)
            ->get();

        foreach ($attempts as $attempt) {
            $stats['processed']++;

            try {
                $eval = ActivityAiGraderService::gradePracticalAttempt($attempt);
                $totalScore = (int) ($eval['total_score'] ?? 0);
                $hasFallback = false;

                foreach ($attempt->practical->criteria as $criterion) {
                    if (! isset($eval['scores'][$criterion->id])) {
                        $hasFallback = true;
                        break;
                    }
                }

                $confidence = $hasFallback ? 0.75 : 0.90;
                $canApprove = GuardrailPolicy::canAutoApproveScore($confidence, $threshold);
                $isAutonomous = GuardrailPolicy::allowsAutonomousMutation($mode);

                if ($isAutonomous && $canApprove) {
                    DB::transaction(function () use ($attempt, $eval, $totalScore) {
                        foreach ($attempt->practical->criteria as $criterion) {
                            $critScore = min((int) ($eval['scores'][$criterion->id] ?? 0), $criterion->max_points);
                            $attempt->scores()->updateOrCreate(
                                ['criterion_id' => $criterion->id],
                                [
                                    'score' => $critScore,
                                    'comment' => $eval['comments'][$criterion->id] ?? '',
                                ]
                            );
                        }
                        $attempt->update(['total_score' => $totalScore]);
                    });

                    PointsHelper::award(
                        $attempt->student_id,
                        \App\Enums\ActivityType::Practical->value,
                        $attempt->practical_id,
                        $totalScore,
                        $attempt->practical->max_score ?? 0
                    );

                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'success',
                        'target_type' => PracticalAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Auto-graded practical '{$attempt->practical?->title}' for student #{$attempt->student_id} (Score: {$totalScore}/{$attempt->practical?->max_score})",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'new_score' => $totalScore,
                            'scores' => $eval['scores'] ?? [],
                        ],
                    ]);

                    $stats['graded']++;
                } else {
                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'flagged_for_review',
                        'target_type' => PracticalAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Practical submission #{$attempt->id} pending manual teacher review (Confidence: " . round($confidence * 100) . "%)",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'proposed_score' => $totalScore,
                        ],
                    ]);

                    $stats['flagged']++;
                }
            } catch (\Throwable $e) {
                Log::warning("AutoGraderTask practical #{$attempt->id} failed: " . $e->getMessage());
                AiManagerLog::create([
                    'event_type' => 'auto_grade',
                    'status' => 'failed',
                    'target_type' => PracticalAttempt::class,
                    'target_id' => $attempt->id,
                    'confidence' => 0.00,
                    'summary' => "Failed to auto-grade practical #{$attempt->id}: " . $e->getMessage(),
                    'payload' => ['error' => $e->getMessage()],
                ]);
                $stats['skipped']++;
            }
        }
    }

    protected function gradeSeatworkAttempts(string $mode, float $threshold, array &$stats): void
    {
        $attempts = SeatworkAttempt::with(['seatwork', 'answers.question.options', 'answers.question.matchingPairs'])
            ->where('status', 'submitted')
            ->whereNull('score')
            ->latest('submitted_at')
            ->limit(15)
            ->get();

        foreach ($attempts as $attempt) {
            $stats['processed']++;

            try {
                $eval = ActivityAiGraderService::gradeSeatworkAttempt($attempt);
                $totalScore = (int) ($eval['total_score'] ?? 0);

                // Check confidence based on whether essays were evaluated
                $hasEssay = $attempt->answers->contains(fn ($a) => $a->question?->type === 'essay');
                $confidence = $hasEssay ? 0.88 : 0.95;

                $canApprove = GuardrailPolicy::canAutoApproveScore($confidence, $threshold);
                $isAutonomous = GuardrailPolicy::allowsAutonomousMutation($mode);

                if ($isAutonomous && $canApprove) {
                    $previousAnswers = [];
                    foreach ($attempt->answers as $ans) {
                        $previousAnswers[$ans->id] = [
                            'is_correct' => $ans->is_correct,
                            'points_awarded' => $ans->points_awarded,
                            'feedback' => $ans->feedback,
                        ];
                    }

                    DB::transaction(function () use ($attempt, $eval, $totalScore) {
                        foreach ($eval['answers'] as $ansData) {
                            $attempt->answers()->where('id', $ansData['id'])->update([
                                'is_correct' => $ansData['is_correct'],
                                'feedback' => $ansData['feedback'] ?? null,
                                'points_awarded' => $ansData['points_awarded'] ?? null,
                            ]);
                        }
                        $attempt->update(['score' => $totalScore]);
                    });

                    PointsHelper::award(
                        $attempt->student_id,
                        \App\Enums\ActivityType::Seatwork->value,
                        $attempt->seatwork_id,
                        $totalScore,
                        $attempt->seatwork?->totalPoints() ?? 0
                    );

                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'success',
                        'target_type' => SeatworkAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Auto-graded seatwork '{$attempt->seatwork?->title}' for student #{$attempt->student_id} (Score: {$totalScore}/{$attempt->seatwork?->totalPoints()})",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'previous_answers' => $previousAnswers,
                            'new_score' => $totalScore,
                        ],
                    ]);

                    $stats['graded']++;
                } else {
                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'flagged_for_review',
                        'target_type' => SeatworkAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Seatwork submission #{$attempt->id} flagged for teacher review (Confidence: " . round($confidence * 100) . "%)",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'proposed_score' => $totalScore,
                        ],
                    ]);

                    $stats['flagged']++;
                }
            } catch (\Throwable $e) {
                Log::warning("AutoGraderTask seatwork #{$attempt->id} failed: " . $e->getMessage());
                AiManagerLog::create([
                    'event_type' => 'auto_grade',
                    'status' => 'failed',
                    'target_type' => SeatworkAttempt::class,
                    'target_id' => $attempt->id,
                    'confidence' => 0.00,
                    'summary' => "Failed to auto-grade seatwork #{$attempt->id}: " . $e->getMessage(),
                    'payload' => ['error' => $e->getMessage()],
                ]);
                $stats['skipped']++;
            }
        }
    }

    protected function gradeQuizAttempts(string $mode, float $threshold, array &$stats): void
    {
        $attempts = QuizAttempt::with(['quiz', 'answers.question.options'])
            ->where('status', 'submitted')
            ->whereNull('score')
            ->latest('submitted_at')
            ->limit(15)
            ->get();

        foreach ($attempts as $attempt) {
            $stats['processed']++;

            try {
                $eval = ActivityAiGraderService::gradeQuizAttempt($attempt);
                $totalScore = (int) ($eval['total_score'] ?? 0);
                $confidence = 0.95;

                $canApprove = GuardrailPolicy::canAutoApproveScore($confidence, $threshold);
                $isAutonomous = GuardrailPolicy::allowsAutonomousMutation($mode);

                if ($isAutonomous && $canApprove) {
                    $previousAnswers = [];
                    foreach ($attempt->answers as $ans) {
                        $previousAnswers[$ans->id] = [
                            'is_correct' => $ans->is_correct,
                        ];
                    }

                    DB::transaction(function () use ($attempt, $eval, $totalScore) {
                        foreach ($eval['answers'] as $ansData) {
                            $attempt->answers()->where('id', $ansData['id'])->update([
                                'is_correct' => $ansData['is_correct'],
                            ]);
                        }
                        $attempt->update(['score' => $totalScore]);
                    });

                    PointsHelper::award(
                        $attempt->student_id,
                        'Quiz',
                        $attempt->quiz_id,
                        $totalScore,
                        $attempt->quiz?->totalPoints() ?? 0
                    );

                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'success',
                        'target_type' => QuizAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Auto-graded quiz '{$attempt->quiz?->title}' for student #{$attempt->student_id} (Score: {$totalScore}/{$attempt->quiz?->totalPoints()})",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'previous_answers' => $previousAnswers,
                            'new_score' => $totalScore,
                        ],
                    ]);

                    $stats['graded']++;
                } else {
                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'flagged_for_review',
                        'target_type' => QuizAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Quiz submission #{$attempt->id} flagged for teacher review (Confidence: " . round($confidence * 100) . "%)",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'proposed_score' => $totalScore,
                        ],
                    ]);

                    $stats['flagged']++;
                }
            } catch (\Throwable $e) {
                Log::warning("AutoGraderTask quiz #{$attempt->id} failed: " . $e->getMessage());
                AiManagerLog::create([
                    'event_type' => 'auto_grade',
                    'status' => 'failed',
                    'target_type' => QuizAttempt::class,
                    'target_id' => $attempt->id,
                    'confidence' => 0.00,
                    'summary' => "Failed to auto-grade quiz #{$attempt->id}: " . $e->getMessage(),
                    'payload' => ['error' => $e->getMessage()],
                ]);
                $stats['skipped']++;
            }
        }
    }

    protected function gradeExamAttempts(string $mode, float $threshold, array &$stats): void
    {
        $attempts = ExamAttempt::with(['exam.sections.criteria', 'exam.sections.questions', 'answers.question'])
            ->where('status', 'submitted')
            ->where(function ($query) {
                $query->whereNull('total_score')
                    ->orWhere(function ($q) {
                        $q->whereHas('exam.sections', fn ($sq) => $sq->where('section_type', 'practical'))
                            ->whereDoesntHave('sectionScores');
                    });
            })
            ->latest('submitted_at')
            ->limit(15)
            ->get();

        foreach ($attempts as $attempt) {
            $stats['processed']++;

            try {
                $eval = ActivityAiGraderService::gradeExamAttempt($attempt);
                $totalScore = (int) ($eval['total_score'] ?? 0);

                $hasPractical = $attempt->exam->sections->contains(fn ($s) => $s->section_type === 'practical');
                $confidence = $hasPractical ? 0.85 : 0.95;

                $canApprove = GuardrailPolicy::canAutoApproveScore($confidence, $threshold);
                $isAutonomous = GuardrailPolicy::allowsAutonomousMutation($mode);

                if ($isAutonomous && $canApprove) {
                    DB::transaction(function () use ($attempt, $eval, $totalScore) {
                        foreach ($attempt->exam->sections as $section) {
                            if ($section->section_type !== 'practical') continue;

                            foreach ($section->criteria as $criterion) {
                                $score = min((int) ($eval['scores'][$criterion->id] ?? 0), $criterion->max_points);

                                $attempt->sectionScores()->updateOrCreate(
                                    ['exam_criterion_id' => $criterion->id],
                                    [
                                        'exam_section_id' => $section->id,
                                        'score' => $score,
                                        'comment' => $eval['comments'][$criterion->id] ?? '',
                                    ]
                                );
                            }
                        }

                        foreach ($eval['answers'] as $ansData) {
                            $attempt->answers()->where('id', $ansData['id'])->update([
                                'is_correct' => $ansData['is_correct'],
                                'points_earned' => $ansData['points_earned'],
                            ]);
                        }

                        $attempt->update(['total_score' => $totalScore]);
                    });

                    PointsHelper::award(
                        $attempt->student_id,
                        \App\Enums\ActivityType::Exam->value,
                        $attempt->exam_id,
                        $totalScore,
                        $attempt->exam?->max_score ?? 0
                    );

                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'success',
                        'target_type' => ExamAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Auto-graded exam '{$attempt->exam?->title}' for student #{$attempt->student_id} (Score: {$totalScore}/{$attempt->exam?->max_score})",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'new_score' => $totalScore,
                        ],
                    ]);

                    $stats['graded']++;
                } else {
                    AiManagerLog::create([
                        'event_type' => 'auto_grade',
                        'status' => 'flagged_for_review',
                        'target_type' => ExamAttempt::class,
                        'target_id' => $attempt->id,
                        'confidence' => $confidence,
                        'summary' => "Exam submission #{$attempt->id} flagged for teacher review (Confidence: " . round($confidence * 100) . "%)",
                        'payload' => [
                            'previous_score' => null,
                            'previous_status' => 'submitted',
                            'proposed_score' => $totalScore,
                        ],
                    ]);

                    $stats['flagged']++;
                }
            } catch (\Throwable $e) {
                Log::warning("AutoGraderTask exam #{$attempt->id} failed: " . $e->getMessage());
                AiManagerLog::create([
                    'event_type' => 'auto_grade',
                    'status' => 'failed',
                    'target_type' => ExamAttempt::class,
                    'target_id' => $attempt->id,
                    'confidence' => 0.00,
                    'summary' => "Failed to auto-grade exam #{$attempt->id}: " . $e->getMessage(),
                    'payload' => ['error' => $e->getMessage()],
                ]);
                $stats['skipped']++;
            }
        }
    }
}
