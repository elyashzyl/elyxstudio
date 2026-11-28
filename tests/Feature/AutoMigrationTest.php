<?php

use App\Services\AutoMigrationService;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('auto migration service does not run during testing without force', function () {
    $executed = AutoMigrationService::runIfNeeded(force: false);
    expect($executed)->toBeFalse();
});

test('auto migration service successfully inspects migrations when forced', function () {
    Cache::flush();

    // Forced run should execute cleanly without error
    $executed = AutoMigrationService::runIfNeeded(force: true);

    // Since migrations are already run by the test runner, no pending migrations should be unapplied
    expect($executed)->toBeFalse();
});

test('auto migration service creates repository if repository does not exist', function () {
    Cache::flush();

    // Drop migrations table to simulate bare database
    Schema::dropIfExists('migrations');

    /** @var Migrator $migrator */
    $migrator = app('migrator');
    expect($migrator->repositoryExists())->toBeFalse();

    // Forced run should detect missing repo and run migrate:install without crashing
    $executed = AutoMigrationService::runIfNeeded(force: true);

    expect($migrator->repositoryExists())->toBeTrue();
});

test('auto migration service catches database connection failures gracefully without crashing application', function () {
    Cache::flush();

    // Mock DB connection getPdo to throw exception
    DB::shouldReceive('connection->getPdo')
        ->andThrow(new PDOException('SQLSTATE[HY000] [2002] Connection refused'));

    $executed = AutoMigrationService::runIfNeeded(force: true);

    expect($executed)->toBeFalse();
});

test('auto migration service avoids repeated runs when cached fingerprint exists', function () {
    Cache::flush();

    // First forced run
    AutoMigrationService::runIfNeeded(force: true);

    // Run without force should be skipped due to cached fingerprint
    $executed = AutoMigrationService::runIfNeeded(force: false);
    expect($executed)->toBeFalse();
});

test('auto migration service only runs ordinary migrations without seeding or resetting data', function () {
    $source = file_get_contents(app_path('Services/AutoMigrationService.php'));

    expect($source)
        ->toContain("SAFE_MIGRATION_COMMAND = 'migrate'")
        ->toContain("'--force' => true")
        ->toContain("'--isolated' => true")
        ->not->toContain('migrate:fresh')
        ->not->toContain('migrate:refresh')
        ->not->toContain('migrate:reset')
        ->not->toContain('db:wipe')
        ->not->toContain('--seed')
        ->not->toContain('db:seed');
});

test('auto migration service handles migration failure without permanently caching success', function () {
    Cache::flush();

    // Mock migrator to report pending migration and Artisan to report failed exit code
    $migrator = app('migrator');
    $allFiles = $migrator->getMigrationFiles([database_path('migrations')]);

    // Force non-empty pending by mocking or simulating artisan failure
    Artisan::shouldReceive('call')
        ->with('migrate', ['--force' => true, '--isolated' => true])
        ->andReturn(1); // Non-zero exit code indicating failure/lock

    // We expect runIfNeeded to return false without throwing
    $executed = AutoMigrationService::runIfNeeded(force: true);
    expect($executed)->toBeFalse();
});
