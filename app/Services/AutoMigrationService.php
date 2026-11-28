<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AutoMigrationService
{
    private const SAFE_MIGRATION_COMMAND = 'migrate';

    private const SAFE_MIGRATION_OPTIONS = ['--force' => true, '--isolated' => true];

    public static function runIfNeeded(bool $force = false): bool
    {
        if (! $force && app()->environment('testing')) {
            return false;
        }
        if (! $force && app()->runningInConsole() && self::runningMigrationCommand()) {
            return false;
        }

        try {
            $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
            if ($migrationFiles === []) {
                return false;
            }

            $fingerprint = md5(implode('|', array_map(
                fn ($file) => basename($file).':'.(@filemtime($file) ?: 0),
                $migrationFiles,
            )));
            $cacheKey = 'elyx_automigrate_'.$fingerprint;

            if (! $force && Cache::has($cacheKey)) {
                return false;
            }

            DB::connection()->getPdo();
            $migrator = app('migrator');
            if (! $migrator->repositoryExists()) {
                Artisan::call('migrate:install');
            }

            $allFiles = $migrator->getMigrationFiles([database_path('migrations')]);
            $pending = array_diff(array_keys($allFiles), $migrator->getRepository()->getRan());
            if ($pending !== [] && Artisan::call(self::SAFE_MIGRATION_COMMAND, self::SAFE_MIGRATION_OPTIONS) !== 0) {
                return false;
            }

            Cache::forever($cacheKey, true);

            return $pending !== [];
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    private static function runningMigrationCommand(): bool
    {
        $command = $_SERVER['argv'][1] ?? null;

        return is_string($command) && ($command === 'migrate' || str_starts_with($command, 'migrate:'));
    }
}
