<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class BackupService
{
    /**
     * Get the directory where backups are stored.
     */
    public static function getBackupDirectory(): string
    {
        $path = storage_path('app/backups');
        if (! file_exists($path)) {
            mkdir($path, 0755, true);
            file_put_contents($path . '/.gitignore', "*\n!.gitignore\n");
        }

        return $path;
    }

    /**
     * List all available backups.
     */
    public static function listBackups(): array
    {
        $dir = self::getBackupDirectory();
        $files = scandir($dir);
        $backups = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitignore') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $file;
            if (! is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (! in_array($ext, ['zip', 'sqlite', 'sql'])) {
                continue;
            }

            $size = (int) filesize($fullPath);
            $mtime = (int) filemtime($fullPath);
            $isFull = $ext === 'zip';

            $syncedToCloud = false;
            if (CloudStorageService::isCloudEnabled()) {
                $syncedToCloud = Cache::remember('backup_cloud_exists_' . $file, 120, function () use ($file) {
                    try {
                        CloudStorageService::configureDisk();
                        return Storage::disk('s3')->exists('backups/' . $file);
                    } catch (\Throwable) {
                        return false;
                    }
                });
            }

            $backups[] = [
                'filename' => $file,
                'type' => $isFull ? 'full' : 'database',
                'extension' => $ext,
                'size' => $size,
                'formatted_size' => self::formatBytes($size),
                'created_at' => Carbon::createFromTimestamp($mtime)->toISOString(),
                'created_at_human' => Carbon::createFromTimestamp($mtime)->diffForHumans(),
                'timestamp' => $mtime,
                'synced_to_cloud' => $syncedToCloud,
            ];
        }

        usort($backups, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Create a new backup.
     */
    public static function createBackup(string $type = 'full', ?string $notes = null, bool $syncToCloud = false): array
    {
        self::getBackupDirectory();
        $timestamp = date('Y-m-d_His');
        /** @var User|null $user */
        $user = Auth::user();

        $driver = config('database.default');
        $isSqlite = ($driver === 'sqlite');
        $dbExt = $isSqlite ? 'sqlite' : 'sql';

        $autoCloudSync = $syncToCloud || (bool) Setting::get('backup_auto_cloud_sync', false);

        if ($type === 'database') {
            $filename = "backup-db-{$timestamp}.{$dbExt}";
            $destPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;

            self::exportDatabase($destPath);

            $size = (int) filesize($destPath);

            ActivityLogger::log(
                'backup_created',
                "Created database backup '{$filename}' (" . self::formatBytes($size) . ")",
                null,
                $user,
                ['type' => 'database', 'filename' => $filename, 'size' => $size, 'notes' => $notes]
            );

            if ($autoCloudSync && CloudStorageService::isCloudEnabled()) {
                try {
                    self::syncToCloud($filename);
                } catch (\Throwable) {
                    // non-blocking cloud sync failure on backup creation
                }
            }

            return [
                'filename' => $filename,
                'type' => 'database',
                'size' => $size,
                'formatted_size' => self::formatBytes($size),
            ];
        }

        // Full system backup (Database + storage/app/public)
        $filename = "backup-full-{$timestamp}.zip";
        $zipPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
        $tempDbName = "temp-db-{$timestamp}.{$dbExt}";
        $tempDbPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $tempDbName;

        self::exportDatabase($tempDbPath);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tempDbPath);
            throw new \RuntimeException('Unable to create backup zip archive.');
        }

        // Add database archive
        $zip->addFile($tempDbPath, $isSqlite ? 'database.sqlite' : 'database.sql');

        // Add manifest metadata
        $manifest = [
            'app_name' => config('app.name', 'EduPulse'),
            'version' => '1.0.0',
            'type' => 'full',
            'created_at' => now()->toISOString(),
            'created_by' => $user?->name ?? 'System',
            'notes' => $notes,
            'db_driver' => $driver,
        ];
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));

        // Add storage files
        $publicStorage = storage_path('app/public');
        if (file_exists($publicStorage) && is_dir($publicStorage)) {
            self::addFolderToZip($publicStorage, $zip, 'storage');
        }

        $zip->close();
        @unlink($tempDbPath);

        $size = (int) filesize($zipPath);

        ActivityLogger::log(
            'backup_created',
            "Created full system backup '{$filename}' (" . self::formatBytes($size) . ")",
            null,
            $user,
            ['type' => 'full', 'filename' => $filename, 'size' => $size, 'notes' => $notes]
        );

        if ($autoCloudSync && CloudStorageService::isCloudEnabled()) {
            try {
                self::syncToCloud($filename);
            } catch (\Throwable) {
                // non-blocking cloud sync failure on backup creation
            }
        }

        return [
            'filename' => $filename,
            'type' => 'full',
            'size' => $size,
            'formatted_size' => self::formatBytes($size),
        ];
    }

    /**
     * Restore from a backup file.
     */
    public static function restore(string $filename): array
    {
        $sanitized = basename($filename);
        $fullPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $sanitized;

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            throw new \InvalidArgumentException("Backup file '{$sanitized}' does not exist.");
        }

        /** @var User|null $user */
        $user = Auth::user();
        $driver = config('database.default');
        $isSqlite = ($driver === 'sqlite');

        // 1. Create a safety snapshot of the current live database first
        $safetyTimestamp = date('Y-m-d_His');
        $safetySnapshotExt = $isSqlite ? 'sqlite' : 'sql';
        $safetySnapshotName = "pre-restore-safety-{$safetyTimestamp}.{$safetySnapshotExt}";
        $safetySnapshotPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $safetySnapshotName;
        self::exportDatabase($safetySnapshotPath);

        $ext = strtolower(pathinfo($sanitized, PATHINFO_EXTENSION));

        if ($ext === 'sqlite') {
            if ($isSqlite) {
                $liveDb = config('database.connections.sqlite.database');
                if ($liveDb !== ':memory:') {
                    DB::disconnect();
                    copy($fullPath, $liveDb);
                    DB::reconnect();
                }
            } else {
                throw new \InvalidArgumentException("Cannot restore a SQLite snapshot into a {$driver} database.");
            }
        } elseif ($ext === 'sql') {
            self::executeSqlFile($fullPath);
        } elseif ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($fullPath) !== true) {
                throw new \RuntimeException('Failed to open backup zip file for restoration.');
            }

            // Restore database from zip
            if ($isSqlite && $zip->locateName('database.sqlite') !== false) {
                $liveDb = config('database.connections.sqlite.database');
                if ($liveDb !== ':memory:') {
                    DB::disconnect();
                    $zipDbContent = $zip->getFromName('database.sqlite');
                    if ($zipDbContent !== false) {
                        file_put_contents($liveDb, $zipDbContent);
                    }
                    DB::reconnect();
                }
            } elseif ($zip->locateName('database.sql') !== false) {
                $tempSqlPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . "temp-restore-{$safetyTimestamp}.sql";
                file_put_contents($tempSqlPath, $zip->getFromName('database.sql'));
                self::executeSqlFile($tempSqlPath);
                @unlink($tempSqlPath);
            }

            // Extract storage files
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (str_starts_with($entryName, 'storage/')) {
                    $relativePath = substr($entryName, strlen('storage/'));
                    if (str_ends_with($entryName, '/')) {
                        @mkdir(storage_path('app/public/' . $relativePath), 0755, true);
                    } else {
                        $dirName = dirname(storage_path('app/public/' . $relativePath));
                        if (! file_exists($dirName)) {
                            @mkdir($dirName, 0755, true);
                        }
                        $content = $zip->getFromIndex($i);
                        if ($content !== false) {
                            file_put_contents(storage_path('app/public/' . $relativePath), $content);
                        }
                    }
                }
            }

            $zip->close();
        }

        // Run migrations and clear cached files
        $liveDb = config('database.connections.sqlite.database');
        if (! ($isSqlite && $liveDb === ':memory:')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('optimize:clear');
            } catch (\Throwable) {
                // silent migration/cache recovery
            }
        }

        ActivityLogger::log(
            'backup_restored',
            "Restored system from backup '{$sanitized}' (safety snapshot saved as '{$safetySnapshotName}')",
            null,
            $user,
            ['restored_file' => $sanitized, 'safety_snapshot' => $safetySnapshotName]
        );

        return [
            'success' => true,
            'message' => "Successfully restored system from '{$sanitized}'. A pre-restore safety copy was preserved.",
            'safety_snapshot' => $safetySnapshotName,
        ];
    }

    /**
     * Upload an external backup file and optionally restore it.
     */
    public static function upload(UploadedFile $file, bool $andRestore = false): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['zip', 'sqlite', 'sql'])) {
            throw new \InvalidArgumentException('Only .zip, .sqlite, and .sql backup files are supported.');
        }

        $timestamp = date('Y-m-d_His');
        $safeName = 'uploaded-' . ($ext === 'zip' ? 'full' : 'db') . "-{$timestamp}.{$ext}";
        $destPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $safeName;

        $file->move(self::getBackupDirectory(), $safeName);

        if ($andRestore) {
            return self::restore($safeName);
        }

        /** @var User|null $user */
        $user = Auth::user();
        ActivityLogger::log(
            'backup_uploaded',
            "Uploaded external backup '{$safeName}' (" . self::formatBytes(filesize($destPath)) . ")",
            null,
            $user,
            ['filename' => $safeName]
        );

        return [
            'success' => true,
            'filename' => $safeName,
            'message' => "Backup file '{$safeName}' uploaded successfully.",
        ];
    }

    /**
     * Synchronize a local backup archive to remote S3 or Cloudflare R2 cloud storage.
     */
    public static function syncToCloud(string $filename): array
    {
        $sanitized = basename($filename);
        $fullPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $sanitized;

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            throw new \InvalidArgumentException("Backup file '{$sanitized}' does not exist on local disk.");
        }

        if (! CloudStorageService::isCloudEnabled()) {
            throw new \RuntimeException('Cloud storage (S3 / Cloudflare R2) is not configured or enabled.');
        }

        CloudStorageService::configureDisk();

        $driver = CloudStorageService::getDriver();
        $driverName = $driver === 'r2' ? 'Cloudflare R2' : 'Amazon S3';
        $cloudKey = 'backups/' . $sanitized;

        $stream = fopen($fullPath, 'r');
        if (! $stream) {
            throw new \RuntimeException("Unable to open backup file '{$sanitized}' for streaming.");
        }

        try {
            $uploaded = Storage::disk('s3')->put($cloudKey, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (! $uploaded) {
                throw new \RuntimeException("Failed writing archive to {$driverName} bucket.");
            }

            Cache::put('backup_cloud_exists_' . $sanitized, true, 86400);

            /** @var User|null $user */
            $user = Auth::user();
            ActivityLogger::log(
                'backup_cloud_synced',
                "Synced backup '{$sanitized}' to {$driverName} cloud storage",
                null,
                $user,
                ['filename' => $sanitized, 'cloud_driver' => $driver, 'cloud_path' => $cloudKey]
            );

            return [
                'success' => true,
                'filename' => $sanitized,
                'cloud_path' => $cloudKey,
                'driver' => $driver,
                'message' => "Successfully synced '{$sanitized}' to {$driverName} bucket.",
            ];
        } catch (\Throwable $e) {
            if (is_resource($stream)) {
                fclose($stream);
            }
            throw $e;
        }
    }

    /**
     * Download a backup file.
     */
    public static function download(string $filename): BinaryFileResponse
    {
        $sanitized = basename($filename);
        $fullPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $sanitized;

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            abort(404, 'Backup file not found.');
        }

        /** @var User|null $user */
        $user = Auth::user();
        ActivityLogger::log(
            'backup_downloaded',
            "Downloaded backup archive '{$sanitized}'",
            null,
            $user,
            ['filename' => $sanitized]
        );

        return response()->download($fullPath, $sanitized);
    }

    /**
     * Delete a backup file.
     */
    public static function delete(string $filename): bool
    {
        $sanitized = basename($filename);
        $fullPath = self::getBackupDirectory() . DIRECTORY_SEPARATOR . $sanitized;

        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
            Cache::forget('backup_cloud_exists_' . $sanitized);

            if (CloudStorageService::isCloudEnabled()) {
                try {
                    CloudStorageService::configureDisk();
                    Storage::disk('s3')->delete('backups/' . $sanitized);
                } catch (\Throwable) {
                    // best-effort remote cleanup
                }
            }

            /** @var User|null $user */
            $user = Auth::user();
            ActivityLogger::log(
                'backup_deleted',
                "Deleted backup file '{$sanitized}'",
                null,
                $user,
                ['filename' => $sanitized]
            );

            return true;
        }

        return false;
    }

    /**
     * Get backup statistics and system disk info.
     */
    public static function getStats(): array
    {
        $backups = self::listBackups();
        $totalSize = array_sum(array_column($backups, 'size'));

        $driver = config('database.default');
        $dbSize = 0;

        if ($driver === 'sqlite') {
            $liveDb = config('database.connections.sqlite.database');
            $dbSize = ($liveDb !== ':memory:' && file_exists($liveDb)) ? (int) filesize($liveDb) : 0;
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                $dbName = config("database.connections.{$driver}.database") ?? DB::getDatabaseName();
                $result = DB::selectOne("
                    SELECT SUM(data_length + index_length) AS size
                    FROM information_schema.TABLES
                    WHERE table_schema = ?
                ", [$dbName]);
                $dbSize = (int) ($result?->size ?? 0);
            } catch (\Throwable) {
                $dbSize = 0;
            }
        }

        $publicStorage = storage_path('app/public');
        $storageSize = self::getDirectorySize($publicStorage);

        $cloudDriver = CloudStorageService::getDriver();
        $cloudDriverName = $cloudDriver === 'r2' ? 'Cloudflare R2' : ($cloudDriver === 's3' ? 'Amazon S3' : 'Local');

        return [
            'total_backups' => count($backups),
            'total_backup_size' => $totalSize,
            'total_backup_size_formatted' => self::formatBytes($totalSize),
            'last_backup_at' => $backups[0]['created_at'] ?? null,
            'last_backup_human' => $backups[0]['created_at_human'] ?? 'Never',
            'live_db_size' => $dbSize,
            'live_db_size_formatted' => self::formatBytes($dbSize),
            'storage_size' => $storageSize,
            'storage_size_formatted' => self::formatBytes($storageSize),
            'cloud_enabled' => CloudStorageService::isCloudEnabled(),
            'cloud_driver' => $cloudDriverName,
            'cloud_bucket' => CloudStorageService::getBucket(),
            'cloud_auto_sync' => (bool) Setting::get('backup_auto_cloud_sync', false),
        ];
    }

    /**
     * Export database snapshot based on active driver.
     */
    public static function exportDatabase(string $destinationPath): void
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            self::vacuumSqliteDatabase($destinationPath);
        } else {
            self::exportSqlDatabase($destinationPath);
        }
    }

    /**
     * Use SQLite's VACUUM INTO for atomic, non-locking, defragmented snapshots.
     */
    protected static function vacuumSqliteDatabase(string $destinationPath): void
    {
        @unlink($destinationPath);

        try {
            DB::statement('VACUUM INTO ' . DB::getPdo()->quote($destinationPath));
        } catch (\Throwable) {
            // Fallback: file copy
            $liveDb = config('database.connections.sqlite.database');
            if ($liveDb !== ':memory:' && file_exists($liveDb)) {
                copy($liveDb, $destinationPath);
            } else {
                touch($destinationPath);
            }
        }
    }

    /**
     * Export standard SQL dump for MySQL/MariaDB/Postgres.
     */
    protected static function exportSqlDatabase(string $destinationPath): void
    {
        @unlink($destinationPath);

        $pdo = DB::connection()->getPdo();
        $handle = fopen($destinationPath, 'w');
        if (! $handle) {
            throw new \RuntimeException("Unable to open {$destinationPath} for writing.");
        }

        fwrite($handle, "-- EduPulse / ElyTrack Database Backup\n");
        fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::connection()->getSchemaBuilder()->getTableListing();

        foreach ($tables as $rawTable) {
            $table = str_replace(['main.', '`'], '', $rawTable);

            // Export CREATE TABLE if supported
            try {
                $createStmt = DB::selectOne("SHOW CREATE TABLE `{$table}`");
                if ($createStmt) {
                    $createArray = (array) $createStmt;
                    $createSql = $createArray['Create Table'] ?? array_values($createArray)[1] ?? null;
                    if ($createSql) {
                        fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
                        fwrite($handle, $createSql . ";\n\n");
                    }
                }
            } catch (\Throwable) {
                // Not supported on this driver
            }

            // Stream rows and write INSERT INTO statements
            try {
                DB::table($table)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $table, $pdo) {
                    if ($rows->isEmpty()) return;

                    $firstRow = (array) $rows[0];
                    $columns = array_keys($firstRow);
                    $colList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

                    foreach ($rows as $row) {
                        $rowArr = (array) $row;
                        $values = [];
                        foreach ($columns as $col) {
                            $val = $rowArr[$col] ?? null;
                            if (is_null($val)) {
                                $values[] = 'NULL';
                            } elseif (is_numeric($val) && ! is_string($val)) {
                                $values[] = $val;
                            } else {
                                $values[] = $pdo->quote((string) $val);
                            }
                        }
                        $valList = implode(', ', $values);
                        fwrite($handle, "INSERT INTO `{$table}` ({$colList}) VALUES ({$valList});\n");
                    }
                });
            } catch (\Throwable) {
                // Table might be empty or virtual
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
    }

    /**
     * Execute SQL statements from a file.
     */
    public static function executeSqlFile(string $filePath): void
    {
        if (! file_exists($filePath)) return;

        $content = file_get_contents($filePath);
        if (trim($content) === '') return;

        $driver = config('database.default');
        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            } catch (\Throwable) {}
        }

        DB::unprepared($content);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Throwable) {}
        }
    }

    /**
     * Recursively add a folder to a ZipArchive.
     */
    protected static function addFolderToZip(string $folder, ZipArchive $zip, string $zipPath = ''): void
    {
        $handle = opendir($folder);
        if (! $handle) return;

        while (false !== ($entry = readdir($handle))) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $folder . DIRECTORY_SEPARATOR . $entry;
            $localZipPath = ($zipPath !== '' ? $zipPath . '/' : '') . $entry;

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($localZipPath);
                self::addFolderToZip($fullPath, $zip, $localZipPath);
            } elseif (is_file($fullPath)) {
                $zip->addFile($fullPath, $localZipPath);
            }
        }

        closedir($handle);
    }

    /**
     * Calculate directory size.
     */
    protected static function getDirectorySize(string $directory): int
    {
        if (! file_exists($directory) || ! is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Format bytes to readable human strings.
     */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 0) . ' KB';
        }

        return $bytes . ' B';
    }
}
