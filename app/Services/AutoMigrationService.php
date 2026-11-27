<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AutoMigrationService
{
    /**
     * Automatic startup may only apply pending migrations. It must never reset,
     * drop, refresh, or seed application data.
     */
    private const SAFE_MIGRATION_COMMAND = 'migrate';

    private const SAFE_MIGRATION_OPTIONS = [
        '--force' => true,
        '--isolated' => true,
    ];

    /**
     * Check for unapplied migrations and execute them automatically.
     * Caches the result based on a fingerprint of migration files so
     * repeated requests have near-zero overhead.
     *
     * @param  bool  $force  Whether to bypass the testing guard
     */
    public static function runIfNeeded(bool $force = false): bool
    {
        // Tests control their own database lifecycle and should not mutate the schema implicitly.
        if (! $force && app()->environment('testing')) {
            return false;
        }

        // Do not call migrate from inside a migration-related Artisan command.
        // Normal CLI commands (queue workers, schedulers, deploy checks, etc.) still
        // trigger this service so pending migrations are applied automatically.
        if (! $force && app()->runningInConsole() && self::runningMigrationCommand()) {
            return false;
        }

        try {
            $migrationFiles = glob(database_path('migrations/*.php')) ?: [];
            if (empty($migrationFiles)) {
                return false;
            }

            // Fingerprint based on filenames and modification timestamps
            $fingerprint = md5(implode('|', array_map(
                fn ($f) => basename($f) . ':' . (@filemtime($f) ?: 0),
                $migrationFiles
            )));

            $cacheKey = 'edupulse_automigrate_' . $fingerprint;

            if (! $force && Cache::has($cacheKey)) {
                return false;
            }

            // Ensure database connection is reachable before proceeding
            DB::connection()->getPdo();

            /** @var \Illuminate\Database\Migrations\Migrator $migrator */
            $migrator = app('migrator');

            if (! $migrator->repositoryExists()) {
                Artisan::call('migrate:install');
            }

            $allFiles = $migrator->getMigrationFiles([database_path('migrations')]);
            $ran = $migrator->getRepository()->getRan();
            $pending = array_diff(array_keys($allFiles), $ran);

            if (! empty($pending)) {
                $exitCode = Artisan::call(
                    self::SAFE_MIGRATION_COMMAND,
                    self::SAFE_MIGRATION_OPTIONS,
                );

                if ($exitCode !== 0) {
                    return false;
                }
            }

            Cache::forever($cacheKey, true);

            return ! empty($pending);
        } catch (\Throwable $e) {
            // Log without breaking request pipeline if database is temporarily starting up
            report($e);

            return false;
        }
    }

    private static function runningMigrationCommand(): bool
    {
        $command = $_SERVER['argv'][1] ?? null;

        return is_string($command)
            && ($command === 'migrate' || str_starts_with($command, 'migrate:'));
    }
}
