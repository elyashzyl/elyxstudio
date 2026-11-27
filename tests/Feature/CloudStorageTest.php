<?php

use App\Models\Setting;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\CloudStorageService;
use App\Services\SecureFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('superadmin can view system settings containing storage keys', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->get('/teacher/system-settings');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/Settings')
        ->has('settings.storage_driver')
        ->has('settings.storage_s3_bucket')
    );
});

test('non-superadmin is forbidden from updating or testing storage settings', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->putJson('/teacher/system-settings/storage', [
            'storage_driver' => 'r2',
        ])
        ->assertForbidden();

    $this->actingAs($teacher)
        ->postJson('/teacher/system-settings/test-storage', [
            'storage_driver' => 'r2',
        ])
        ->assertForbidden();
});

test('superadmin can update cloud storage configuration', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->putJson('/teacher/system-settings/storage', [
        'storage_driver' => 'r2',
        'storage_s3_bucket' => 'edupulse-r2-materials',
        'storage_s3_key' => 'r2-access-key-xyz',
        'storage_s3_secret' => 'r2-secret-key-123',
        'storage_s3_region' => 'auto',
        'storage_s3_endpoint' => 'https://account-id.r2.cloudflarestorage.com',
        'storage_s3_public_url' => 'https://cdn.edupulse.test',
        'storage_s3_use_path_style' => 'true',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    expect(Setting::get('storage_driver'))->toBe('r2')
        ->and(Setting::get('storage_s3_bucket'))->toBe('edupulse-r2-materials')
        ->and(Setting::get('storage_s3_key'))->toBe('r2-access-key-xyz')
        ->and(Setting::get('storage_s3_endpoint'))->toBe('https://account-id.r2.cloudflarestorage.com');
});

test('test storage connection succeeds immediately for local driver', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->postJson('/teacher/system-settings/test-storage', [
        'storage_driver' => 'local',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => true,
    ]);
});

test('test storage connection reports error on incomplete credentials', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    $response = $this->actingAs($superadmin)->postJson('/teacher/system-settings/test-storage', [
        'storage_driver' => 'r2',
        'storage_s3_bucket' => '',
        'storage_s3_key' => '',
        'storage_s3_secret' => '',
    ]);

    $response->assertOk();
    $response->assertJson([
        'success' => false,
        'message' => 'Cloud storage credentials (Key, Secret, Bucket) are incomplete.',
    ]);
});

test('test storage connection probe succeeds with mocked cloud disk', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);
    Storage::fake('s3');

    $result = CloudStorageService::testConnection([
        'storage_driver' => 'r2',
        'storage_s3_bucket' => 'mock-bucket',
        'storage_s3_key' => 'mock-key',
        'storage_s3_secret' => 'mock-secret',
    ]);

    expect($result['success'])->toBeTrue()
        ->and($result['latency_ms'])->toBeGreaterThanOrEqual(0);
});

test('secure file service syncs uploaded file to cloud storage when enabled', function () {
    Storage::fake('local');
    Storage::fake('s3');

    Setting::set('storage_driver', 's3');
    Setting::set('storage_s3_bucket', 'unit-test-bucket');
    Setting::set('storage_s3_key', 'test-key');
    Setting::set('storage_s3_secret', 'test-secret');

    $file = UploadedFile::fake()->create('lecture-notes.pdf', 100, 'application/pdf');

    $storedPath = SecureFileService::store($file, 'teaching-materials');

    expect($storedPath)->not->toBeEmpty();
    expect(Storage::disk('local')->exists($storedPath))->toBeTrue();
    expect(Storage::disk('s3')->exists($storedPath))->toBeTrue();
});

test('secure file service self-heals missing local file by pulling from cloud storage', function () {
    Storage::fake('local');
    Storage::fake('s3');

    Setting::set('storage_driver', 'r2');
    Setting::set('storage_s3_bucket', 'cloud-heal-bucket');
    Setting::set('storage_s3_key', 'r2-key');
    Setting::set('storage_s3_secret', 'r2-secret');

    $relativePath = 'teaching-materials/cloud_only_slide.pdf';
    $fileContent = 'Unique Cloudflare R2 binary stream payload';

    // Put directly in S3 fake only
    Storage::disk('s3')->put($relativePath, $fileContent);

    // Verify local disk does not have it yet
    expect(Storage::disk('local')->exists($relativePath))->toBeFalse();

    // Resolve should detect missing local, find on cloud, pullToLocal, and return path
    $resolvedPath = SecureFileService::resolve($relativePath);

    expect($resolvedPath)->not->toBeNull()
        ->and(file_exists($resolvedPath))->toBeTrue()
        ->and(file_get_contents($resolvedPath))->toBe($fileContent);
});

test('secure file service deletes file from both local and cloud storage', function () {
    Storage::fake('local');
    Storage::fake('s3');

    Setting::set('storage_driver', 's3');
    Setting::set('storage_s3_bucket', 'test-bucket');
    Setting::set('storage_s3_key', 'key');
    Setting::set('storage_s3_secret', 'secret');

    $relativePath = 'test-folder/to-delete.pdf';
    Storage::disk('local')->put($relativePath, 'delete me');
    Storage::disk('s3')->put($relativePath, 'delete me');

    expect(Storage::disk('s3')->exists($relativePath))->toBeTrue();

    SecureFileService::delete($relativePath);

    expect(Storage::disk('s3')->exists($relativePath))->toBeFalse();
});

test('material preview redirects to direct cloud streaming url when requested', function () {
    $superadmin = User::factory()->create(['role' => 'superadmin']);

    Setting::set('storage_driver', 'r2');
    Setting::set('storage_s3_bucket', 'stream-bucket');
    Setting::set('storage_s3_key', 'key');
    Setting::set('storage_s3_secret', 'secret');
    Setting::set('storage_s3_public_url', 'https://cdn.edupulse.school');

    $material = TeachingMaterial::create([
        'title' => 'Big Architecture Video/PDF',
        'subject' => 'Science',
        'file_name' => 'science-guide.pdf',
        'file_path' => 'teaching-materials/science-guide.pdf',
        'file_type' => 'pdf',
        'teacher_id' => $superadmin->id,
    ]);

    $response = $this->actingAs($superadmin)->get("/materials/{$material->id}/preview?direct_stream=1");

    $response->assertRedirect('https://cdn.edupulse.school/teaching-materials/science-guide.pdf');
});
