<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SecureFileService
{
    /**
     * Store an uploaded file outside the web root, syncing to cloud storage if enabled.
     */
    public static function store(UploadedFile $file, string $directory): string
    {
        Storage::disk('local')->makeDirectory($directory);

        $path = $file->store($directory, 'local');

        if (CloudStorageService::isCloudEnabled()) {
            CloudStorageService::uploadFromLocal($path);
        }

        return $path;
    }

    /**
     * Resolve a stored file without allowing traversal outside an upload root.
     *
     * Checks local disk, auto-heals from cloud storage if enabled, and falls
     * back to bundled seed files when necessary.
     */
    public static function resolve(?string $relativePath): ?string
    {
        if (! is_string($relativePath) || trim($relativePath) === '' || str_contains($relativePath, "\0")) {
            return null;
        }

        $relative = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);

        // Check configured local disk (supports Storage::fake in tests)
        try {
            $localPath = Storage::disk('local')->path($relative);
            if (file_exists($localPath)) {
                return $localPath;
            }
        } catch (\Throwable) {
        }

        foreach ([storage_path('app/private'), storage_path('app/public')] as $root) {
            $rootPath = realpath($root);
            if ($rootPath === false) {
                continue;
            }

            $candidate = realpath($rootPath . DIRECTORY_SEPARATOR . $relative);
            if ($candidate === false || ! is_file($candidate)) {
                continue;
            }

            if (self::isWithin($candidate, $rootPath)) {
                return $candidate;
            }
        }

        // Auto-heal from Cloud Storage (S3 / Cloudflare R2) if enabled and missing on local disk
        if (CloudStorageService::isCloudEnabled() && CloudStorageService::exists($relativePath)) {
            try {
                $targetLocalPath = Storage::disk('local')->path($relative);
            } catch (\Throwable) {
                $targetLocalPath = storage_path('app/private/' . $relative);
            }

            if (CloudStorageService::pullToLocal($relativePath, $targetLocalPath) && file_exists($targetLocalPath)) {
                return $targetLocalPath;
            }
        }

        // Fallback: bundled seed materials in database/seeders/data
        $seedRoot = realpath(database_path('seeders/data'));
        if ($seedRoot !== false) {
            $seedCandidate = realpath($seedRoot . DIRECTORY_SEPARATOR . $relative);
            if ($seedCandidate !== false && is_file($seedCandidate) && self::isWithin($seedCandidate, $seedRoot)) {
                // Self-heal: restore to active storage so subsequent reads hit storage directly
                $targetDir = storage_path('app/public/' . dirname($relative));
                if (! is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }
                $targetFile = storage_path('app/public/' . $relative);
                if (! file_exists($targetFile)) {
                    @copy($seedCandidate, $targetFile);
                }

                return $seedCandidate;
            }
        }

        return null;
    }

    /**
     * Delete a stored file from local upload root and cloud storage.
     */
    public static function delete(?string $relativePath): void
    {
        if (! is_string($relativePath) || trim($relativePath) === '') {
            return;
        }

        if (CloudStorageService::isCloudEnabled()) {
            CloudStorageService::delete($relativePath);
        }

        $resolved = self::resolve($relativePath);

        if ($resolved !== null) {
            $seedRoot = realpath(database_path('seeders/data'));
            if ($seedRoot !== false && self::isWithin($resolved, $seedRoot)) {
                return;
            }

            @unlink($resolved);
        }
    }

    /**
     * Obtain a direct public or temporary signed streaming URL if cloud storage is configured.
     */
    public static function getStreamingUrl(?string $relativePath, int $minutes = 60): ?string
    {
        if (! is_string($relativePath) || trim($relativePath) === '') {
            return null;
        }

        return CloudStorageService::getStreamingUrl($relativePath, $minutes);
    }

    /**
     * Return a safe filename for a Content-Disposition header.
     */
    public static function safeFilename(?string $filename, string $fallback = 'download'): string
    {
        $filename = basename(str_replace('\\', '/', (string) $filename));
        $filename = preg_replace('/[^\pL\pN._ -]+/u', '_', $filename) ?: '';
        $filename = trim($filename, " .\\/");

        if ($filename === '') {
            $filename = $fallback;
        }

        return mb_substr($filename, 0, 180);
    }

    private static function isWithin(string $path, string $root): bool
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $root = rtrim($root, DIRECTORY_SEPARATOR);

        return $path === $root || str_starts_with($path, $root . DIRECTORY_SEPARATOR);
    }
}
