<?php

namespace App\Services\AiManager\Tasks;

use App\Models\AiManagerLog;
use App\Models\Message;
use App\Models\TeachingMaterial;
use App\Services\AiManager\GuardrailPolicy;
use App\Services\SecureFileService;
use Illuminate\Support\Facades\Log;

class StorageHealerTask
{
    /**
     * Execute self-healing checks on teaching materials and message attachments.
     *
     * @return array{scanned: int, healed: int, flagged: int, healthy: int}
     */
    public function execute(string $mode): array
    {
        $stats = [
            'scanned' => 0,
            'healed' => 0,
            'flagged' => 0,
            'healthy' => 0,
        ];

        $isAutonomous = GuardrailPolicy::allowsAutonomousMutation($mode);

        // 1. Check Teaching Materials
        $materials = TeachingMaterial::whereNotNull('file_path')
            ->whereNotNull('file_data')
            ->latest()
            ->limit(100)
            ->get();

        foreach ($materials as $material) {
            $stats['scanned']++;
            $resolved = SecureFileService::resolve($material->file_path);

            if ($resolved && file_exists($resolved)) {
                $stats['healthy']++;
                continue;
            }

            // File missing on disk!
            if ($isAutonomous) {
                try {
                    $healedPath = $material->getResolvedFilePath();
                    if ($healedPath && file_exists($healedPath)) {
                        if (\App\Services\CloudStorageService::isCloudEnabled()) {
                            \App\Services\CloudStorageService::uploadFromLocal($material->file_path, $healedPath);
                        }
                        $size = filesize($healedPath);
                        AiManagerLog::create([
                            'event_type' => 'storage_heal',
                            'status' => 'success',
                            'target_type' => TeachingMaterial::class,
                            'target_id' => $material->id,
                            'confidence' => 1.00,
                            'summary' => "Self-healed missing file '{$material->file_name}' from database backup ({$size} bytes)",
                            'payload' => [
                                'file_path' => $material->file_path,
                                'file_name' => $material->file_name,
                                'restored_bytes' => $size,
                            ],
                        ]);
                        $stats['healed']++;
                    } else {
                        AiManagerLog::create([
                            'event_type' => 'storage_heal',
                            'status' => 'failed',
                            'target_type' => TeachingMaterial::class,
                            'target_id' => $material->id,
                            'confidence' => 0.00,
                            'summary' => "Failed to self-heal missing file '{$material->file_name}' for material #{$material->id}",
                            'payload' => ['file_path' => $material->file_path],
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("StorageHealerTask failed for material #{$material->id}: " . $e->getMessage());
                }
            } else {
                AiManagerLog::create([
                    'event_type' => 'storage_heal',
                    'status' => 'flagged_for_review',
                    'target_type' => TeachingMaterial::class,
                    'target_id' => $material->id,
                    'confidence' => 1.00,
                    'summary' => "Missing disk file '{$material->file_name}' detected. Available in database for self-healing.",
                    'payload' => [
                        'file_path' => $material->file_path,
                        'file_name' => $material->file_name,
                    ],
                ]);
                $stats['flagged']++;
            }
        }

        // 2. Check Message Attachments
        $messages = Message::whereNotNull('attachment_path')
            ->whereNotNull('attachment_data')
            ->latest()
            ->limit(100)
            ->get();

        foreach ($messages as $msg) {
            $stats['scanned']++;
            $resolved = SecureFileService::resolve($msg->attachment_path);

            if ($resolved && file_exists($resolved)) {
                $stats['healthy']++;
                continue;
            }

            if ($isAutonomous) {
                try {
                    $healedPath = $msg->getResolvedAttachmentPath();
                    if ($healedPath && file_exists($healedPath)) {
                        if (\App\Services\CloudStorageService::isCloudEnabled()) {
                            \App\Services\CloudStorageService::uploadFromLocal($msg->attachment_path, $healedPath);
                        }
                        $size = filesize($healedPath);
                        AiManagerLog::create([
                            'event_type' => 'storage_heal',
                            'status' => 'success',
                            'target_type' => Message::class,
                            'target_id' => $msg->id,
                            'confidence' => 1.00,
                            'summary' => "Self-healed missing chat attachment '{$msg->attachment_name}' from database backup ({$size} bytes)",
                            'payload' => [
                                'attachment_path' => $msg->attachment_path,
                                'attachment_name' => $msg->attachment_name,
                                'restored_bytes' => $size,
                            ],
                        ]);
                        $stats['healed']++;
                    } else {
                        AiManagerLog::create([
                            'event_type' => 'storage_heal',
                            'status' => 'failed',
                            'target_type' => Message::class,
                            'target_id' => $msg->id,
                            'confidence' => 0.00,
                            'summary' => "Failed to self-heal missing chat attachment for message #{$msg->id}",
                            'payload' => ['attachment_path' => $msg->attachment_path],
                        ]);
                    }
                } catch (\Throwable $e) {
                    Log::warning("StorageHealerTask failed for message #{$msg->id}: " . $e->getMessage());
                }
            } else {
                AiManagerLog::create([
                    'event_type' => 'storage_heal',
                    'status' => 'flagged_for_review',
                    'target_type' => Message::class,
                    'target_id' => $msg->id,
                    'confidence' => 1.00,
                    'summary' => "Missing message attachment '{$msg->attachment_name}' detected. Available in database for self-healing.",
                    'payload' => [
                        'attachment_path' => $msg->attachment_path,
                        'attachment_name' => $msg->attachment_name,
                    ],
                ]);
                $stats['flagged']++;
            }
        }

        return $stats;
    }
}
