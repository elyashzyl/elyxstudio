<?php

namespace App\Services\AiManager\Tasks;

use App\Models\AiManagerLog;
use App\Services\AiConfigService;
use Illuminate\Support\Facades\Log;

class HealthCheckTask
{
    /**
     * Execute health check diagnostics.
     *
     * @return array{healthy: bool, disk_free_mb: float, ai_latency_ms: ?int, ai_status: string}
     */
    public function execute(): array
    {
        $storagePath = storage_path('app');
        $freeBytes = @disk_free_space($storagePath);
        $diskFreeMb = $freeBytes !== false ? round($freeBytes / 1024 / 1024, 2) : 1024.0;

        $aiTest = AiConfigService::testConnection();
        $aiLatency = $aiTest['latency_ms'] ?? null;
        $aiSuccess = (bool) ($aiTest['success'] ?? false);
        $aiProvider = (string) ($aiTest['provider'] ?? 'unknown');

        $healthy = $aiSuccess && $diskFreeMb >= 100.0;

        // If latency high (> 5000ms) or provider failed or disk low, log warning
        if (! $aiSuccess || ($aiLatency !== null && $aiLatency > 5000) || $diskFreeMb < 250.0) {
            $warnings = [];
            if (! $aiSuccess) {
                $warnings[] = "AI Provider '{$aiProvider}' connection issue: " . ($aiTest['message'] ?? 'Unavailable');
            } elseif ($aiLatency !== null && $aiLatency > 5000) {
                $warnings[] = "High latency detected on {$aiProvider} ({$aiLatency}ms)";
            }
            if ($diskFreeMb < 250.0) {
                $warnings[] = "Low disk space warning: {$diskFreeMb}MB free";
            }

            AiManagerLog::create([
                'event_type' => 'inspection',
                'status' => 'flagged_for_review',
                'target_type' => null,
                'target_id' => null,
                'confidence' => 1.00,
                'summary' => 'System health warning: ' . implode('; ', $warnings),
                'payload' => [
                    'ai_test' => $aiTest,
                    'disk_free_mb' => $diskFreeMb,
                ],
            ]);
        }

        return [
            'healthy' => $healthy,
            'disk_free_mb' => $diskFreeMb,
            'ai_latency_ms' => $aiLatency,
            'ai_status' => $aiSuccess ? 'connected' : 'degraded',
        ];
    }
}
