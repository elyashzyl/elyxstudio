<?php

use App\Models\Grade10Profile;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->grade10 = GradeLevel::create(['name' => 'Grade 10']);
    $this->grade9 = GradeLevel::create(['name' => 'Grade 9']);

    $this->section10 = Section::create([
        'name' => 'St. Thomas',
        'grade_level_id' => $this->grade10->id,
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->studentG10 = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->grade10->id,
        'section_id' => $this->section10->id,
        'email_verified_at' => now(),
    ]);

    $this->studentG9 = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 9',
        'grade_level_id' => $this->grade9->id,
        'email_verified_at' => now(),
    ]);
});

test('grade 10 student can view profile questionnaire', function () {
    $response = $this->actingAs($this->studentG10)
        ->get(route('student.grade10.profile'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Student/Grade10Profile/Index'));
});

test('non-grade 10 student cannot view profile questionnaire', function () {
    $response = $this->actingAs($this->studentG9)
        ->get(route('student.grade10.profile'));

    $response->assertForbidden();
});

test('grade 10 student can submit profile questionnaire successfully', function () {
    $payload = [
        'full_name' => 'Dela Cruz, Juan Protacio',
        'chinese_name' => '陈大明',
        'birthday' => 'June 19, 2008',
        'hometown' => 'Calamba, Laguna',
        'ambition' => 'Physician & Novelist',
        'motto' => 'To know oneself is true wisdom.',
        'message' => 'Thank you teachers and batchmates for four unforgettable years of junior high school. Keep chasing your dreams with passion and integrity. May we all find success and fulfillment.',
        'social_media' => '@juandelacruz',
    ];

    $response = $this->actingAs($this->studentG10)
        ->post(route('student.grade10.profile.store'), $payload);

    $response->assertRedirect(route('student.grade10.profile'));

    $this->assertDatabaseHas('grade10_profiles', [
        'user_id' => $this->studentG10->id,
        'full_name' => 'Dela Cruz, Juan Protacio',
        'chinese_name' => '陈大明',
        'birthday' => 'June 19, 2008',
        'hometown' => 'Calamba, Laguna',
        'ambition' => 'Physician & Novelist',
    ]);
});

test('grade 10 student can upload, update, and remove yearbook portrait photo', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $photo = \Illuminate\Http\UploadedFile::fake()->image('grad_portrait.jpg', 600, 600);

    $payload = [
        'full_name' => 'Santos, Maria Clara',
        'birthday' => 'May 12, 2009',
        'hometown' => 'San Diego, Manila',
        'ambition' => 'Architect',
        'motto' => 'Build with purpose.',
        'message' => 'Sentence one. Sentence two. Sentence three.',
        'photo' => $photo,
    ];

    $response = $this->actingAs($this->studentG10)
        ->post(route('student.grade10.profile.store'), $payload);

    $response->assertRedirect(route('student.grade10.profile'));

    $profile = Grade10Profile::where('user_id', $this->studentG10->id)->first();
    expect($profile)->not->toBeNull();
    expect($profile->photo_path)->not->toBeNull();
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($profile->photo_path);
    expect($profile->getPhotoUrl())->not->toBeNull();
    expect($profile->getPhotoBase64())->toContain('data:image');

    // Test removing photo
    $removePayload = [
        'full_name' => 'Santos, Maria Clara',
        'birthday' => 'May 12, 2009',
        'hometown' => 'San Diego, Manila',
        'ambition' => 'Architect',
        'motto' => 'Build with purpose.',
        'message' => 'Sentence one. Sentence two. Sentence three.',
        'remove_photo' => true,
    ];

    $res2 = $this->actingAs($this->studentG10)
        ->post(route('student.grade10.profile.store'), $removePayload);
    $res2->assertRedirect();
    $profile->refresh();
    expect($profile->photo_path)->toBeNull();
});

test('updating grade 10 profile text without re-uploading photo preserves existing portrait', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $photo = \Illuminate\Http\UploadedFile::fake()->image('grad_portrait.jpg', 600, 800);

    $this->actingAs($this->studentG10)
        ->post(route('student.grade10.profile.store'), [
            'full_name' => 'Santos, Maria Clara',
            'birthday' => 'May 12, 2009',
            'hometown' => 'San Diego, Manila',
            'ambition' => 'Architect',
            'motto' => 'Build with purpose.',
            'message' => 'Sentence one. Sentence two. Sentence three.',
            'photo' => $photo,
        ]);

    $profile = Grade10Profile::where('user_id', $this->studentG10->id)->first();
    $initialPhotoPath = $profile->photo_path;
    expect($initialPhotoPath)->not->toBeNull();

    // Now update ambition without uploading or removing photo
    $updatePayload = [
        'full_name' => 'Santos, Maria Clara',
        'birthday' => 'May 12, 2009',
        'hometown' => 'San Diego, Manila',
        'ambition' => 'Civil Engineer',
        'motto' => 'Build with purpose.',
        'message' => 'Sentence one. Sentence two. Sentence three.',
        'remove_photo' => false,
    ];

    $this->actingAs($this->studentG10)
        ->post(route('student.grade10.profile.store'), $updatePayload)
        ->assertRedirect(route('student.grade10.profile'));

    $profile->refresh();
    expect($profile->ambition)->toBe('Civil Engineer');
    expect($profile->photo_path)->toBe($initialPhotoPath);
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($initialPhotoPath);
});

test('teacher can view grade 10 profiles index with submissions and pending lists', function () {
    Grade10Profile::create([
        'user_id' => $this->studentG10->id,
        'full_name' => 'Dela Cruz, Juan Protacio',
        'chinese_name' => '陈大明',
        'birthday' => 'June 19, 2008',
        'hometown' => 'Calamba, Laguna',
        'ambition' => 'Physician',
        'motto' => 'Ad astra per aspera',
        'message' => 'Sentence one here. Sentence two here. Sentence three here.',
        'social_media' => '@juan',
        'submitted_at' => now(),
    ]);

    // Another student in Grade 10 who has not submitted
    $pendingStudent = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->grade10->id,
        'section_id' => $this->section10->id,
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($this->teacher)
        ->get(route('teacher.grade10.profiles.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Teacher/Grade10Profiles/Index')
        ->has('profiles', 1)
        ->has('pendingStudents', 1)
    );
});

test('teacher can export grade 10 profiles in all formats', function () {
    Grade10Profile::create([
        'user_id' => $this->studentG10->id,
        'full_name' => 'Dela Cruz, Juan Protacio',
        'chinese_name' => '陈大明',
        'birthday' => 'June 19, 2008',
        'hometown' => 'Calamba, Laguna',
        'ambition' => 'Physician',
        'motto' => 'Ad astra per aspera',
        'message' => 'Sentence one here. Sentence two here. Sentence three here.',
        'social_media' => '@juan',
        'submitted_at' => now(),
    ]);

    // Test PDF export
    $pdfRes = $this->actingAs($this->teacher)
        ->get(route('teacher.grade10.profiles.export', 'pdf'));
    $pdfRes->assertOk();
    $pdfRes->assertHeader('Content-Type', 'application/pdf');

    // Test Excel export
    $excelRes = $this->actingAs($this->teacher)
        ->get(route('teacher.grade10.profiles.export', 'excel'));
    $excelRes->assertOk();
    $excelRes->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
    expect($excelRes->getContent())->toContain('GRADE 10 PROFILES &amp; YEARBOOK RECORDS');

    // Test Word export
    $wordRes = $this->actingAs($this->teacher)
        ->get(route('teacher.grade10.profiles.export', 'word'));
    $wordRes->assertOk();
    $wordRes->assertHeader('Content-Type', 'application/msword; charset=UTF-8');
    expect($wordRes->getContent())->toContain('GRADE 10 PROFILES &amp; YEARBOOK RECORDS');

    // Test CSV export (streamed)
    $csvRes = $this->actingAs($this->teacher)
        ->get(route('teacher.grade10.profiles.export', 'csv'));
    $csvRes->assertOk();
    $csvRes->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    $content = $csvRes->streamedContent();
    // Check UTF-8 BOM
    expect(str_starts_with($content, "\xEF\xBB\xBF"))->toBeTrue();
    // Check columns
    expect($content)->toContain('FULL NAME (Last Name, First Name, Middle Name)');
    expect($content)->toContain('CHINESE NAME (Chinese Class Students)');
    expect($content)->toContain('BIRTHDAY (Month, Day, Year)');
    expect($content)->toContain('HOMETOWN');
    expect($content)->toContain('AMBITION');
    expect($content)->toContain('MOTTO');
    expect($content)->toContain('MESSAGE (3-5 sentences only)');
    expect($content)->toContain('SOCIAL MEDIA ACCOUNT (Optional)');
    expect($content)->toContain('Dela Cruz, Juan Protacio');
    expect($content)->toContain('陈大明');
});
