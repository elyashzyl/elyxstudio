<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ErrorLogService
{
    /**
     * Get list of log files in storage/logs directory.
     */
    public static function getLogFiles(): array
    {
        $logPath = storage_path('logs');
        if (! File::isDirectory($logPath)) {
            return [];
        }

        $files = File::glob($logPath . '/*.log');
        $result = [];

        foreach ($files as $filePath) {
            $name = basename($filePath);
            $size = File::size($filePath);
            $modified = File::lastModified($filePath);

            $result[] = [
                'name' => $name,
                'path' => $filePath,
                'size' => $size,
                'formatted_size' => self::formatBytes($size),
                'modified_at' => Carbon::createFromTimestamp($modified)->toIso8601String(),
                'is_current' => $name === 'laravel.log',
            ];
        }

        usort($result, fn ($a, $b) => strcmp($b['modified_at'], $a['modified_at']));

        return $result;
    }

    /**
     * Parse log entries from selected or default log file.
     */
    public static function getEntries(?string $fileName = null, int $limit = 100, ?string $level = null, ?string $search = null): array
    {
        $filePath = self::resolveLogFilePath($fileName);
        if (! $filePath || ! File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);
        if (empty(trim($content))) {
            return [];
        }

        // Standard Laravel/Monolog log pattern: [YYYY-MM-DD HH:MM:SS] env.LEVEL: Message
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[\+-]\d{2}:\d{2})?)\]\s+([a-zA-Z0-9_\-]+)\.([A-Z]+):\s+(.*?)(?=(?:^\[\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})|\z)/ms';

        if (! preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            return [];
        }

        // Read in reverse to get most recent logs first
        $matches = array_reverse($matches);
        $entries = [];
        $searchLower = $search ? mb_strtolower(trim($search)) : null;
        $levelUpper = $level && $level !== 'all' ? strtoupper($level) : null;

        foreach ($matches as $index => $match) {
            $rawDate = $match[1];
            $environment = $match[2];
            $entryLevel = strtoupper($match[3]);
            $body = trim($match[4]);

            // Level filtering
            if ($levelUpper) {
                if ($levelUpper === 'ERROR' && ! in_array($entryLevel, ['EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR'])) {
                    continue;
                } elseif ($levelUpper === 'WARNING' && $entryLevel !== 'WARNING') {
                    continue;
                } elseif ($levelUpper === 'INFO' && ! in_array($entryLevel, ['INFO', 'NOTICE'])) {
                    continue;
                } elseif ($levelUpper === 'DEBUG' && $entryLevel !== 'DEBUG') {
                    continue;
                } elseif (! in_array($levelUpper, ['ERROR', 'WARNING', 'INFO', 'DEBUG']) && $entryLevel !== $levelUpper) {
                    continue;
                }
            }

            // Keyword search
            if ($searchLower && ! str_contains(mb_strtolower($body), $searchLower)) {
                continue;
            }

            // Separate main message from stack trace
            $lines = explode("\n", $body);
            $message = trim($lines[0] ?? '');
            $stackLines = array_slice($lines, 1);
            $stackTrace = trim(implode("\n", $stackLines));

            // Extract file and line if present in message or stack trace
            $file = null;
            $line = null;
            if (preg_match('/in\s+([\/\\\\\w\.\-]+):(\d+)/i', $message, $loc)) {
                $file = $loc[1];
                $line = (int) $loc[2];
            } elseif (preg_match('/#0\s+([\/\\\\\w\.\-]+)\((\d+)\):/i', $stackTrace, $loc)) {
                $file = $loc[1];
                $line = (int) $loc[2];
            }

            try {
                $dateObj = Carbon::parse($rawDate);
                $formattedDate = $dateObj->format('M d, Y h:i:s A');
                $timeAgo = $dateObj->diffForHumans();
            } catch (\Throwable) {
                $formattedDate = $rawDate;
                $timeAgo = '';
            }

            $entries[] = [
                'id' => md5($rawDate . $index . substr($message, 0, 40)),
                'timestamp' => $formattedDate,
                'raw_timestamp' => $rawDate,
                'time_ago' => $timeAgo,
                'environment' => $environment,
                'level' => $entryLevel,
                'level_type' => self::normalizeLevel($entryLevel),
                'message' => $message,
                'file' => $file ? basename($file) : null,
                'full_file' => $file,
                'line' => $line,
                'stack_trace' => $stackTrace,
                'has_trace' => ! empty($stackTrace),
            ];

            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    /**
     * Compute summary statistics for log file.
     */
    public static function getStats(?string $fileName = null): array
    {
        $filePath = self::resolveLogFilePath($fileName);
        if (! $filePath || ! File::exists($filePath)) {
            return [
                'total' => 0,
                'errors' => 0,
                'warnings' => 0,
                'info' => 0,
                'file_size' => '0 B',
                'file_name' => $fileName ?: 'laravel.log',
                'last_modified' => '—',
                'exists' => false,
            ];
        }

        $size = File::size($filePath);
        $modified = File::lastModified($filePath);
        $content = File::get($filePath);

        $errorCount = preg_match_all('/\]\s+[a-zA-Z0-9_\-]+\.(EMERGENCY|ALERT|CRITICAL|ERROR):/i', $content);
        $warningCount = preg_match_all('/\]\s+[a-zA-Z0-9_\-]+\.WARNING:/i', $content);
        $infoCount = preg_match_all('/\]\s+[a-zA-Z0-9_\-]+\.(INFO|NOTICE|DEBUG):/i', $content);

        return [
            'total' => ($errorCount ?: 0) + ($warningCount ?: 0) + ($infoCount ?: 0),
            'errors' => $errorCount ?: 0,
            'warnings' => $warningCount ?: 0,
            'info' => $infoCount ?: 0,
            'file_size' => self::formatBytes($size),
            'file_name' => basename($filePath),
            'last_modified' => Carbon::createFromTimestamp($modified)->diffForHumans(),
            'exists' => true,
        ];
    }

    /**
     * Clear / truncate log file.
     */
    public static function clear(?string $fileName = null): bool
    {
        $filePath = self::resolveLogFilePath($fileName);
        if (! $filePath) {
            return false;
        }

        File::put($filePath, '');

        return true;
    }

    /**
     * Download raw log file.
     */
    public static function download(?string $fileName = null): BinaryFileResponse
    {
        $filePath = self::resolveLogFilePath($fileName);
        if (! $filePath || ! File::exists($filePath)) {
            // create empty file if not present
            File::ensureDirectoryExists(storage_path('logs'));
            File::put(storage_path('logs/laravel.log'), '');
            $filePath = storage_path('logs/laravel.log');
        }

        return response()->download($filePath, basename($filePath));
    }

    /**
     * Write sample log entries for testing purposes.
     */
    public static function generateTestLogs(string $triggeredBy): void
    {
        Log::info("Test diagnostic notice triggered by superadmin {$triggeredBy} from admin console.");
        Log::warning("Sample system warning: Background worker high memory threshold reached (78MB / 128MB).");
        Log::error("Sample handled exception: Connection timeout to external mail gateway while sending notification", [
            'operator' => $triggeredBy,
            'component' => 'MailNotifier',
            'retries' => 3,
        ]);
    }

    protected static function resolveLogFilePath(?string $fileName): ?string
    {
        $logPath = storage_path('logs');
        if (! File::isDirectory($logPath)) {
            File::makeDirectory($logPath, 0755, true, true);
        }

        if ($fileName) {
            $sanitized = basename($fileName);
            return $logPath . DIRECTORY_SEPARATOR . $sanitized;
        }

        return $logPath . DIRECTORY_SEPARATOR . 'laravel.log';
    }

    protected static function normalizeLevel(string $level): string
    {
        return match ($level) {
            'EMERGENCY', 'ALERT', 'CRITICAL', 'ERROR' => 'error',
            'WARNING' => 'warning',
            'NOTICE', 'INFO' => 'info',
            'DEBUG' => 'debug',
            default => 'info',
        };
    }

    protected static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
