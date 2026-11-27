<?php

namespace App\Services\AiManager;

use App\Models\AiManagerLog;
use App\Services\AiConfigService;
use App\Services\AiManager\Tasks\AnomalyDetectorTask;
use App\Services\AiManager\Tasks\AutoGraderTask;
use App\Services\AiManager\Tasks\GameReopenApproverTask;
use App\Services\AiManager\Tasks\HealthCheckTask;
use App\Services\AiManager\Tasks\StorageHealerTask;

class AiSystemManagerService
{
    /**
     * Run master inspection loop.
     *
     * @return array{success: bool, skipped: bool, mode: string, summary: string, details: array}
     */
    public function runInspection(bool $force = false, string $triggeredBy = 'scheduler'): array
    {
        $enabled = AiConfigService::isManagerEnabled();
        $mode = AiConfigService::getManagerMode();
        $confidenceThreshold = AiConfigService::getManagerConfidenceThreshold();
        $autoGradeEnabled = AiConfigService::isManagerAutoGradeEnabled();
        $selfHealEnabled = AiConfigService::isManagerSelfHealEnabled();
        $anomalyEnabled = AiConfigService::isManagerAnomalyDetectionEnabled();
        $reopenEnabled = AiConfigService::isManagerGameReopenEnabled();

        if (! $enabled && ! $force) {
            return [
                'success' => false,
                'skipped' => true,
                'mode' => $mode,
                'summary' => 'AI System Manager is currently disabled in System Settings.',
                'details' => [],
            ];
        }

        $details = [
            'triggered_by' => $triggeredBy,
            'mode' => $mode,
            'confidence_threshold' => $confidenceThreshold,
        ];

        // 1. Health check
        $healthTask = new HealthCheckTask();
        $details['health'] = $healthTask->execute();

        // 2. Storage healing
        if ($selfHealEnabled) {
            $storageTask = new StorageHealerTask();
            $details['storage'] = $storageTask->execute($mode);
        } else {
            $details['storage'] = ['skipped' => true];
        }

        // 3. Auto-grading
        if ($autoGradeEnabled) {
            $graderTask = new AutoGraderTask();
            $details['grading'] = $graderTask->execute($mode, $confidenceThreshold);
        } else {
            $details['grading'] = ['skipped' => true];
        }

        // 4. Anomaly detection
        if ($anomalyEnabled) {
            $anomalyTask = new AnomalyDetectorTask();
            $details['anomalies'] = $anomalyTask->execute($mode);
        } else {
            $details['anomalies'] = ['skipped' => true];
        }

        // 5. Game Reopen Auto-Approval
        if ($reopenEnabled) {
            $reopenTask = new GameReopenApproverTask();
            $details['game_reopen'] = $reopenTask->execute($mode, $confidenceThreshold);
        } else {
            $details['game_reopen'] = ['skipped' => true];
        }

        // Synthesize summary message
        $gradedCount = $details['grading']['graded'] ?? 0;
        $healedCount = $details['storage']['healed'] ?? 0;
        $reopenApprovedCount = $details['game_reopen']['approved'] ?? 0;
        $flaggedCount = ($details['grading']['flagged'] ?? 0)
            + ($details['storage']['flagged'] ?? 0)
            + ($details['anomalies']['anomalies_flagged'] ?? 0)
            + ($details['game_reopen']['flagged'] ?? 0);

        $summary = sprintf(
            'AI Manager inspection completed (%s mode, %s). Graded: %d, Reopens approved: %d, Healed: %d, Flagged for review: %d.',
            ucfirst($mode),
            $triggeredBy,
            $gradedCount,
            $reopenApprovedCount,
            $healedCount,
            $flaggedCount
        );

        AiManagerLog::create([
            'event_type' => 'inspection',
            'status' => 'success',
            'target_type' => null,
            'target_id' => null,
            'confidence' => 1.00,
            'summary' => $summary,
            'payload' => $details,
        ]);

        return [
            'success' => true,
            'skipped' => false,
            'mode' => $mode,
            'summary' => $summary,
            'details' => $details,
        ];
    }

    /**
     * Get real-time status and operational metrics for the Superadmin UI.
     *
     * @return array<string, mixed>
     */
    public function getStatus(): array
    {
        $today = now()->startOfDay();

        $lastInspection = AiManagerLog::where('event_type', 'inspection')
            ->latest('created_at')
            ->first();

        $todayLogs = AiManagerLog::where('created_at', '>=', $today);

        return [
            'enabled' => AiConfigService::isManagerEnabled(),
            'mode' => AiConfigService::getManagerMode(),
            'interval_minutes' => AiConfigService::getManagerInterval(),
            'confidence_threshold' => AiConfigService::getManagerConfidenceThreshold(),
            'auto_grade_enabled' => AiConfigService::isManagerAutoGradeEnabled(),
            'self_heal_enabled' => AiConfigService::isManagerSelfHealEnabled(),
            'anomaly_detection_enabled' => AiConfigService::isManagerAnomalyDetectionEnabled(),
            'game_reopen_enabled' => AiConfigService::isManagerGameReopenEnabled(),
            'last_inspection' => $lastInspection ? [
                'created_at' => $lastInspection->created_at->toIso8601String(),
                'summary' => $lastInspection->summary,
                'status' => $lastInspection->status,
            ] : null,
            'stats_today' => [
                'total_actions' => (clone $todayLogs)->count(),
                'auto_graded' => (clone $todayLogs)->where('event_type', 'auto_grade')->where('status', 'success')->count(),
                'reopens_approved' => (clone $todayLogs)->where('event_type', 'game_reopen')->where('status', 'success')->count(),
                'files_healed' => (clone $todayLogs)->where('event_type', 'storage_heal')->where('status', 'success')->count(),
                'flagged_reviews' => (clone $todayLogs)->where('status', 'flagged_for_review')->count(),
                'reverted' => (clone $todayLogs)->whereNotNull('reverted_at')->count(),
            ],
        ];
    }
}
