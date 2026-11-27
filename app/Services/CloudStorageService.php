<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CloudStorageService
{
    /**
     * Get the active storage driver.
     * Options: 'local', 's3', 'r2'
     */
    public static function getDriver(): string
    {
        return (string) Setting::get('storage_driver', config('filesystems.default', 'local'));
    }

    /**
     * Determine if external cloud storage (S3 or Cloudflare R2) is active and configured.
     */
    public static function isCloudEnabled(): bool
    {
        $driver = self::getDriver();
        if ($driver !== 's3' && $driver !== 'r2') {
            return false;
        }

        $bucket = self::getBucket();
        $key = self::getKey();
        $secret = self::getSecret();

        return ! empty($bucket) && ! empty($key) && ! empty($secret);
    }

    public static function getKey(): ?string
    {
        return Setting::get('storage_s3_key', config('filesystems.disks.s3.key'));
    }

    public static function getSecret(): ?string
    {
        return Setting::get('storage_s3_secret', config('filesystems.disks.s3.secret'));
    }

    public static function getRegion(): string
    {
        $driver = self::getDriver();
        $default = $driver === 'r2' ? 'auto' : (config('filesystems.disks.s3.region') ?: 'us-east-1');
        return (string) Setting::get('storage_s3_region', $default);
    }

    public static function getBucket(): ?string
    {
        return Setting::get('storage_s3_bucket', config('filesystems.disks.s3.bucket'));
    }

    public static function getEndpoint(): ?string
    {
        return Setting::get('storage_s3_endpoint', config('filesystems.disks.s3.endpoint'));
    }

    public static function getPublicUrl(): ?string
    {
        return Setting::get('storage_s3_public_url', config('filesystems.disks.s3.url'));
    }

    public static function getUsePathStyle(): bool
    {
        $val = Setting::get('storage_s3_use_path_style');
        if ($val !== null) {
            return in_array($val, ['true', '1', 1, true], true);
        }
        return (bool) config('filesystems.disks.s3.use_path_style_endpoint', true);
    }

    /**
     * Configure the runtime S3 disk dynamically based on database settings or overrides.
     *
     * @param array<string, mixed>|null $overrides
     */
    public static function configureDisk(?array $overrides = null): void
    {
        $driver = $overrides['storage_driver'] ?? self::getDriver();
        $key = $overrides['storage_s3_key'] ?? self::getKey();
        $secret = $overrides['storage_s3_secret'] ?? self::getSecret();
        $bucket = $overrides['storage_s3_bucket'] ?? self::getBucket();
        $region = $overrides['storage_s3_region'] ?? self::getRegion();
        $endpoint = $overrides['storage_s3_endpoint'] ?? self::getEndpoint();
        $publicUrl = $overrides['storage_s3_public_url'] ?? self::getPublicUrl();

        $usePathStyle = isset($overrides['storage_s3_use_path_style'])
            ? in_array($overrides['storage_s3_use_path_style'], ['true', '1', 1, true], true)
            : self::getUsePathStyle();

        $resolvedRegion = $region ?: ($driver === 'r2' ? 'auto' : 'us-east-1');

        Config::set('filesystems.disks.s3', [
            'driver' => 's3',
            'key' => $key,
            'secret' => $secret,
            'region' => $resolvedRegion,
            'bucket' => $bucket,
            'url' => $publicUrl,
            'endpoint' => $endpoint,
            'use_path_style_endpoint' => $usePathStyle,
            'throw' => true,
        ]);
    }

    /**
     * Test connection to configured S3/R2 bucket with a temporary probe file.
     *
     * @param array<string, mixed> $overrides
     * @return array{success: bool, message: string, latency_ms?: float}
     */
    public static function testConnection(array $overrides = []): array
    {
        $driver = (string) ($overrides['storage_driver'] ?? self::getDriver());

        if ($driver === 'local') {
            return [
                'success' => true,
                'message' => 'Local filesystem driver active. Local disk storage is operational.',
                'latency_ms' => 0.1,
            ];
        }

        $bucket = (string) ($overrides['storage_s3_bucket'] ?? self::getBucket() ?? '');
        $key = (string) ($overrides['storage_s3_key'] ?? self::getKey() ?? '');
        $secret = (string) ($overrides['storage_s3_secret'] ?? self::getSecret() ?? '');

        if (empty($bucket) || empty($key) || empty($secret)) {
            return [
                'success' => false,
                'message' => 'Cloud storage credentials (Key, Secret, Bucket) are incomplete.',
            ];
        }

        self::configureDisk($overrides);

        $driverName = $driver === 'r2' ? 'Cloudflare R2' : 'Amazon S3';
        $probePath = 'probes/probe_' . uniqid() . '.txt';
        $content = 'EduPulse storage connection probe generated at ' . now()->toIso8601String();
        $start = microtime(true);

        try {
            $disk = Storage::disk('s3');
            $disk->put($probePath, $content);

            if (! $disk->exists($probePath)) {
                return [
                    'success' => false,
                    'message' => "Probe file written to {$driverName} but could not be verified on remote bucket.",
                ];
            }

            // Cleanup probe
            $disk->delete($probePath);

            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'success' => true,
                'message' => "Successfully connected to {$driverName} bucket ({$bucket}) in {$latency}ms.",
                'latency_ms' => $latency,
            ];
        } catch (Throwable $e) {
            Log::warning("Cloud storage test connection failed for {$driverName}: " . $e->getMessage());

            return [
                'success' => false,
                'message' => "Connection failed to {$driverName}: " . $e->getMessage(),
            ];
        }
    }

    /**
     * Store an uploaded file directly in cloud storage.
     */
    public static function store(UploadedFile $file, string $directory): ?string
    {
        if (! self::isCloudEnabled()) {
            return null;
        }

        self::configureDisk();

        try {
            $path = Storage::disk('s3')->putFile($directory, $file);
            return $path ?: null;
        } catch (Throwable $e) {
            Log::error('Cloud storage store failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload an existing local file or raw stream to cloud storage at specific relative path.
     */
    public static function uploadFromLocal(string $relativePath, ?string $localAbsolutePath = null): bool
    {
        if (! self::isCloudEnabled()) {
            return false;
        }

        self::configureDisk();

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        try {
            if ($localAbsolutePath && file_exists($localAbsolutePath)) {
                $stream = @fopen($localAbsolutePath, 'r');
                if ($stream !== false) {
                    $success = Storage::disk('s3')->put($normalized, $stream);
                    if (is_resource($stream)) {
                        @fclose($stream);
                    }
                    return (bool) $success;
                }
            }

            if (Storage::disk('local')->exists($relativePath)) {
                $content = Storage::disk('local')->get($relativePath);
                if ($content !== null) {
                    return (bool) Storage::disk('s3')->put($normalized, $content);
                }
            }

            return false;
        } catch (Throwable $e) {
            Log::warning("Cloud storage uploadFromLocal failed for {$normalized}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a file exists in cloud storage.
     */
    public static function exists(string $relativePath): bool
    {
        if (! self::isCloudEnabled()) {
            return false;
        }

        self::configureDisk();

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        try {
            return Storage::disk('s3')->exists($normalized);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Download a file from cloud storage to a local path (self-healing / caching mechanism).
     */
    public static function pullToLocal(string $relativePath, string $localDestination): bool
    {
        if (! self::isCloudEnabled()) {
            return false;
        }

        self::configureDisk();

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        try {
            $disk = Storage::disk('s3');
            if (! $disk->exists($normalized)) {
                return false;
            }

            $content = $disk->get($normalized);
            if ($content === null) {
                return false;
            }

            $dir = dirname($localDestination);
            if (! is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            return @file_put_contents($localDestination, $content) !== false;
        } catch (Throwable $e) {
            Log::warning("Cloud storage pullToLocal failed for {$normalized}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a file from cloud storage.
     */
    public static function delete(string $relativePath): bool
    {
        if (! self::isCloudEnabled()) {
            return false;
        }

        self::configureDisk();

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        try {
            return Storage::disk('s3')->delete($normalized);
        } catch (Throwable $e) {
            Log::warning("Cloud storage delete failed for {$normalized}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a public or temporary URL for direct streaming if supported.
     */
    public static function getStreamingUrl(string $relativePath, int $minutes = 60): ?string
    {
        if (! self::isCloudEnabled()) {
            return null;
        }

        self::configureDisk();

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        try {
            $publicUrl = self::getPublicUrl();
            if ($publicUrl) {
                return rtrim($publicUrl, '/') . '/' . $normalized;
            }

            return Storage::disk('s3')->temporaryUrl($normalized, now()->addMinutes($minutes));
        } catch (Throwable) {
            return null;
        }
    }
}
