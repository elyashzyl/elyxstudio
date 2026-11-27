<?php

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Ensure clean backups directory for test isolation
    $backupDir = BackupService::getBackupDirectory();
    File::ensureDirectoryExists($backupDir);
});

afterEach(function () {
    // Clean up test generated backup files with 'test-' prefix if any
});

test('unauthenticated users are redirected from backups page', function () {
    $response = $this->get('/teacher/backups');
    $response->assertRedirect('/login');
});

test('regular teachers cannot access backups page', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $response = $this->actingAs($teacher)->get('/teacher/backups');
    $response->assertForbidden();
});

test('students cannot access backups page', function () {
    $student = User::factory()->create(['role' => 'student']);

    $response = $this->actingAs($student)->get('/teacher/backups');
    $response->assertRedirect(route('student.dashboard'));
});

test('superadmins can view the backups page', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->get('/teacher/backups');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/Backups')
        ->has('backups')
        ->has('stats')
    );
});

test('superadmins can create a database backup', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->post('/teacher/backups', [
        'type' => 'database',
        'notes' => 'Automated test db backup',
    ]);

    $response->assertSessionHas('success');

    $backups = BackupService::listBackups();
    expect(count($backups))->toBeGreaterThan(0);

    $latest = $backups[0];
    expect($latest['type'])->toBe('database');
    expect($latest['extension'])->toBe('sqlite');

    // Verify activity log was recorded
    $log = ActivityLog::where('action', 'backup_created')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->user_id)->toBe($superadmin->id);
    expect($log->properties['notes'])->toBe('Automated test db backup');
});

test('superadmins can create a full system backup', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->post('/teacher/backups', [
        'type' => 'full',
        'notes' => 'Automated test full backup',
    ]);

    $response->assertSessionHas('success');

    $backups = BackupService::listBackups();
    $fullBackups = array_filter($backups, fn ($b) => $b['type'] === 'full');
    expect(count($fullBackups))->toBeGreaterThan(0);
});

test('superadmins can download a backup archive', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $backup = BackupService::createBackup('database', 'Test download');
    $filename = $backup['filename'];

    $response = $this->actingAs($superadmin)->get("/teacher/backups/{$filename}/download");
    $response->assertOk();
    $response->assertHeader('content-disposition');
});

test('superadmins can upload an external backup', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $fakeBackupContent = "SQLite format 3\x00test";
    $file = UploadedFile::fake()->createWithContent('external-snapshot.sqlite', $fakeBackupContent);

    $response = $this->actingAs($superadmin)->post('/teacher/backups/upload', [
        'file' => $file,
    ]);

    $response->assertSessionHas('success');

    $log = ActivityLog::where('action', 'backup_uploaded')->latest()->first();
    expect($log)->not->toBeNull();
});

test('superadmins can restore a backup with automatic safety snapshot', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $backup = BackupService::createBackup('database', 'Test restore pre');
    $filename = $backup['filename'];

    $response = $this->actingAs($superadmin)->post('/teacher/backups/restore', [
        'filename' => $filename,
    ]);

    $response->assertSessionHas('success');

    // Confirm restoration logged
    $log = ActivityLog::where('action', 'backup_restored')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->properties['safety_snapshot'])->toContain('pre-restore-safety-');

    // Confirm safety snapshot file was created
    $safetyPath = BackupService::getBackupDirectory() . DIRECTORY_SEPARATOR . $log->properties['safety_snapshot'];
    expect(file_exists($safetyPath))->toBeTrue();
});

test('superadmins can delete a backup', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $backup = BackupService::createBackup('database', 'To be deleted');
    $filename = $backup['filename'];
    $path = BackupService::getBackupDirectory() . DIRECTORY_SEPARATOR . $filename;
    expect(file_exists($path))->toBeTrue();

    $response = $this->actingAs($superadmin)->delete("/teacher/backups/{$filename}");
    $response->assertSessionHas('success');

    expect(file_exists($path))->toBeFalse();

    $log = ActivityLog::where('action', 'backup_deleted')->latest()->first();
    expect($log)->not->toBeNull();
});

test('artisan backup command executes successfully', function () {
    $exitCode = Artisan::call('backup:run', ['--type' => 'database']);
    expect($exitCode)->toBe(0);

    $exitCode = Artisan::call('backup:run', ['--type' => 'full']);
    expect($exitCode)->toBe(0);
});

test('superadmins can sync a backup archive to S3 cloud storage', function () {
    \Illuminate\Support\Facades\Storage::fake('s3');
    \App\Models\Setting::set('storage_driver', 's3');
    \App\Models\Setting::set('storage_s3_key', 'test-key');
    \App\Models\Setting::set('storage_s3_secret', 'test-secret');
    \App\Models\Setting::set('storage_s3_bucket', 'edupulse-backups');
    \App\Models\Setting::set('storage_s3_region', 'us-east-1');

    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $backup = BackupService::createBackup('database', 'Test cloud sync');
    $filename = $backup['filename'];

    $response = $this->actingAs($superadmin)->post("/teacher/backups/{$filename}/sync");
    $response->assertSessionHas('success');

    expect(\Illuminate\Support\Facades\Storage::disk('s3')->exists('backups/' . $filename))->toBeTrue();

    // Verify activity log
    $log = ActivityLog::where('action', 'backup_cloud_synced')->latest()->first();
    expect($log)->not->toBeNull();
    expect($log->properties['filename'])->toBe($filename);
});

test('backup creation with sync_to_cloud automatically syncs archive to cloud', function () {
    \Illuminate\Support\Facades\Storage::fake('s3');
    \App\Models\Setting::set('storage_driver', 'r2');
    \App\Models\Setting::set('storage_s3_key', 'r2-key');
    \App\Models\Setting::set('storage_s3_secret', 'r2-secret');
    \App\Models\Setting::set('storage_s3_bucket', 'edupulse-r2-backups');

    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->post('/teacher/backups', [
        'type' => 'database',
        'notes' => 'Auto cloud sync backup',
        'sync_to_cloud' => true,
    ]);

    $response->assertSessionHas('success');

    $backups = BackupService::listBackups();
    expect(count($backups))->toBeGreaterThan(0);
    $latest = $backups[0];

    expect(\Illuminate\Support\Facades\Storage::disk('s3')->exists('backups/' . $latest['filename']))->toBeTrue();
});
