<?php

use App\Models\GradeLevel;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\SecureFileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->gradeLevel = GradeLevel::create(['name' => 'Grade 10']);

    $this->admin = User::factory()->create([
        'role' => 'superadmin',
        'email_verified_at' => now(),
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->otherTeacher = User::factory()->create([
        'role' => 'teacher',
        'email_verified_at' => now(),
    ]);

    $this->student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $this->gradeLevel->id,
        'email_verified_at' => now(),
    ]);
});

test('student can view materials index', function () {
    $this->actingAs($this->student);

    $response = $this->get('/student/materials');

    $response->assertOk();
});

test('visiting slash materials redirects student to student materials', function () {
    $this->actingAs($this->student);

    $response = $this->get('/materials');

    $response->assertRedirect(route('student.materials'));
});

test('visiting slash materials redirects teacher to teacher materials', function () {
    $this->actingAs($this->teacher);

    $response = $this->get('/materials');

    $response->assertRedirect(route('teacher.materials.index'));
});

test('material preview returns 404 if file does not exist on disk', function () {
    $this->actingAs($this->student);

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Missing File Lesson',
        'subject' => 'Science',
        'file_path' => 'teaching-materials/non_existent_file.pdf',
        'file_name' => 'non_existent_file.pdf',
        'file_size' => 12345,
        'file_type' => 'pdf',
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $response = $this->get('/materials/' . $material->id . '/preview');

    $response->assertNotFound();
});

test('material preview succeeds when file exists on disk', function () {
    $this->actingAs($this->student);

    $fakeFile = UploadedFile::fake()->create('test-presentation.pdf', 100, 'application/pdf');
    $storedPath = SecureFileService::store($fakeFile, 'teaching-materials');

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Real Lesson',
        'subject' => 'Science',
        'file_path' => $storedPath,
        'file_name' => 'test-presentation.pdf',
        'file_size' => 102400,
        'file_type' => 'pdf',
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $response = $this->get('/materials/' . $material->id . '/preview');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Disposition', 'inline; filename=test-presentation.pdf');

    SecureFileService::delete($storedPath);
});

test('material download succeeds when file exists on disk', function () {
    $this->actingAs($this->student);

    $fakeFile = UploadedFile::fake()->create('download-lesson.pdf', 50, 'application/pdf');
    $storedPath = SecureFileService::store($fakeFile, 'teaching-materials');

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Downloadable Lesson',
        'subject' => 'English',
        'file_path' => $storedPath,
        'file_name' => 'download-lesson.pdf',
        'file_size' => 51200,
        'file_type' => 'pdf',
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $response = $this->get('/materials/' . $material->id . '/download');

    $response->assertOk();

    SecureFileService::delete($storedPath);
});

test('material download returns error flash when file does not exist on disk', function () {
    $this->actingAs($this->student);

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Missing Download Lesson',
        'subject' => 'English',
        'file_path' => 'teaching-materials/ghost-file.pdf',
        'file_name' => 'ghost-file.pdf',
        'file_size' => 51200,
        'file_type' => 'pdf',
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $response = $this->get('/materials/' . $material->id . '/download');

    $response->assertSessionHas('error');

    $response->assertRedirect();
});

test('admin can assign material to a teacher during upload', function () {
    $this->actingAs($this->admin);

    $fakeFile = UploadedFile::fake()->create('algebra.pdf', 200, 'application/pdf');

    $response = $this->post('/teacher/materials', [
        'title' => 'Algebra 101',
        'subject' => 'Math',
        'description' => 'Intro to algebra',
        'file' => $fakeFile,
        'grade_level_id' => $this->gradeLevel->id,
        'teacher_id' => $this->teacher->id,
    ]);

    $response->assertRedirect();

    $material = TeachingMaterial::where('title', 'Algebra 101')->first();
    expect($material)->not->toBeNull();
    expect($material->teacher_id)->toBe($this->teacher->id);

    $this->actingAs($this->teacher);
    $teacherView = $this->get('/teacher/materials');
    $teacherView->assertOk();
    $teacherView->assertSee('Algebra 101');

    SecureFileService::delete($material->file_path);
});

test('materials uploaded by admin are visible to teachers', function () {
    $this->actingAs($this->admin);

    $fakeFile = UploadedFile::fake()->create('admin-announcement.pdf', 100, 'application/pdf');
    $storedPath = SecureFileService::store($fakeFile, 'teaching-materials');

    $material = TeachingMaterial::create([
        'teacher_id' => $this->admin->id,
        'title' => 'Admin Curricular Guide',
        'subject' => 'General',
        'file_path' => $storedPath,
        'file_name' => 'admin-announcement.pdf',
        'file_size' => 102400,
        'file_type' => 'pdf',
    ]);

    $this->actingAs($this->teacher);
    $response = $this->get('/teacher/materials');
    $response->assertOk();
    $response->assertSee('Admin Curricular Guide');

    SecureFileService::delete($storedPath);
});

test('admin can reassign material to another teacher', function () {
    $this->actingAs($this->admin);

    $fakeFile = UploadedFile::fake()->create('reassign-doc.pdf', 50, 'application/pdf');
    $storedPath = SecureFileService::store($fakeFile, 'teaching-materials');

    $material = TeachingMaterial::create([
        'teacher_id' => $this->admin->id,
        'title' => 'Transferable Lesson',
        'subject' => 'History',
        'file_path' => $storedPath,
        'file_name' => 'reassign-doc.pdf',
        'file_size' => 51200,
        'file_type' => 'pdf',
    ]);

    $response = $this->patch('/teacher/materials/' . $material->id . '/reassign', [
        'teacher_id' => $this->teacher->id,
    ]);

    $response->assertSessionHas('success');
    expect($material->fresh()->teacher_id)->toBe($this->teacher->id);

    SecureFileService::delete($storedPath);
});

test('seeded teaching material resolves and can be previewed by student', function () {
    $this->actingAs($this->student);

    $seededMaterial = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Constructors',
        'subject' => 'ICT',
        'file_path' => 'teaching-materials/1pgaoOkQPt3YpROHfUUTaXMqzmOGLZlS7STa297s.pptx',
        'file_name' => 'ict-week4-constructors.pptx',
        'file_size' => 71763,
        'file_type' => 'pptx',
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $resolved = SecureFileService::resolve($seededMaterial->file_path);
    expect($resolved)->not->toBeNull();
    expect(file_exists($resolved))->toBeTrue();

    $response = $this->get('/materials/' . $seededMaterial->id . '/preview');
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.presentationml.presentation');
});

test('SecureFileService resolves and self-heals from seed directory if storage copy is missing', function () {
    $relativePath = 'teaching-materials/1pgaoOkQPt3YpROHfUUTaXMqzmOGLZlS7STa297s.pptx';

    $resolved = SecureFileService::resolve($relativePath);
    expect($resolved)->not->toBeNull();
    expect(file_exists($resolved))->toBeTrue();

    // Verify it restored it into storage/app/public
    $publicPath = storage_path('app/public/' . $relativePath);
    expect(file_exists($publicPath))->toBeTrue();
});

test('uploading material stores base64 file_data in database and recovers if disk is wiped', function () {
    $this->actingAs($this->admin);

    $fakeFile = UploadedFile::fake()->createWithContent('biology-cells.pdf', '%PDF-1.4 simulated pdf test content');

    $response = $this->post('/teacher/materials', [
        'title' => 'Biology Cells',
        'subject' => 'Science',
        'file' => $fakeFile,
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $response->assertRedirect();

    $material = TeachingMaterial::where('title', 'Biology Cells')->first();
    expect($material)->not->toBeNull();
    expect($material->file_data)->not->toBeNull();
    expect(strlen($material->file_data))->toBeGreaterThan(0);

    // Delete disk copy to simulate ephemeral wipe on redeployment
    SecureFileService::delete($material->file_path);
    $diskCheck = SecureFileService::resolve($material->file_path);
    expect($diskCheck)->toBeNull();

    // Student preview should still succeed by restoring from file_data in database
    $this->actingAs($this->student);
    $previewResponse = $this->get('/materials/' . $material->id . '/preview');
    $previewResponse->assertOk();

    SecureFileService::delete($material->file_path);
});

test('teacher can preview and download materials uploaded by superadmin without 403 error', function () {
    $this->actingAs($this->admin);

    $fakeFile = UploadedFile::fake()->create('school-policy.pdf', 25, 'application/pdf');
    $storedPath = SecureFileService::store($fakeFile, 'teaching-materials');

    $material = TeachingMaterial::create([
        'teacher_id' => $this->admin->id,
        'title' => 'School Policy Handbook',
        'subject' => 'Admin Guidelines',
        'file_path' => $storedPath,
        'file_name' => 'school-policy.pdf',
        'file_size' => 25600,
        'file_type' => 'pdf',
    ]);

    // Teacher tries to preview and download
    $this->actingAs($this->teacher);

    $previewResponse = $this->get('/materials/' . $material->id . '/preview');
    $previewResponse->assertOk();

    $downloadResponse = $this->get('/materials/' . $material->id . '/download');
    $downloadResponse->assertOk();

    SecureFileService::delete($storedPath);
});

test('teacher can update their teaching material and replace file', function () {
    $this->actingAs($this->teacher);

    $originalFile = UploadedFile::fake()->createWithContent('lesson-v1.pdf', 'Content v1');
    $storedPath = SecureFileService::store($originalFile, 'teaching-materials');

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Original Lesson',
        'subject' => 'Science',
        'description' => 'First draft',
        'file_path' => $storedPath,
        'file_name' => 'lesson-v1.pdf',
        'file_size' => 10,
        'file_type' => 'pdf',
        'file_data' => base64_encode('Content v1'),
    ]);

    $newFile = UploadedFile::fake()->createWithContent('lesson-v2.pdf', 'Updated Content v2');

    $response = $this->post('/teacher/materials/' . $material->id, [
        'title' => 'Updated Lesson Title',
        'subject' => 'Advanced Science',
        'description' => 'Revised draft',
        'grade_level_id' => $this->gradeLevel->id,
        'file' => $newFile,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $material->refresh();
    expect($material->title)->toBe('Updated Lesson Title');
    expect($material->subject)->toBe('Advanced Science');
    expect($material->description)->toBe('Revised draft');
    expect($material->file_name)->toBe('lesson-v2.pdf');
    expect(base64_decode($material->file_data))->toBe('Updated Content v2');

    SecureFileService::delete($material->file_path);
});

test('material generate endpoint accepts requested count of 15 and returns 15 items based on material reference', function () {
    $this->actingAs($this->teacher);

    $lessonContent = "Introduction to Computer Networking.\n"
        . "A computer network is a set of computers sharing resources located on or provided by network nodes.\n"
        . "Computers use common communication protocols over digital interconnections to communicate with each other.\n"
        . "The Internet Protocol Suite (TCP/IP) defines the rules for packet formatting and addressing across routers.\n"
        . "Local Area Networks (LAN) connect computers within a limited geographical area such as a school or office.\n"
        . "Wide Area Networks (WAN) cover large geographic distances and typically connect multiple smaller networks.\n"
        . "Routers direct data packets between networks using routing tables to find the fastest path.\n"
        . "Switches operate at layer two of the OSI model to forward frames between devices on the same local segment.\n"
        . "Network switches maintain a MAC address table to direct traffic only to the destination port.\n"
        . "Firewalls monitor and control incoming and outgoing network traffic based on predetermined security rules.\n"
        . "Domain Name System (DNS) translates human-readable domain names into numerical IP addresses.\n"
        . "Dynamic Host Configuration Protocol (DHCP) automatically assigns IP addresses to devices on a network.\n"
        . "Network interface cards provide the physical connection between a computer and a network cable.\n"
        . "Bandwidth is the maximum rate of data transfer across a given path in a network.\n"
        . "Latency measures the delay in milliseconds for data to travel from source to destination.\n"
        . "Packet loss occurs when one or more packets of data traveling across a computer network fail to reach their destination.";

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Computer Networking Fundamentals',
        'subject' => 'ICT 10',
        'description' => 'Comprehensive study guide on computer networking protocols, hardware, and topology.',
        'file_path' => 'teaching-materials/networking-lesson.txt',
        'file_name' => 'networking-lesson.txt',
        'file_size' => strlen($lessonContent),
        'file_type' => 'txt',
        'file_data' => base64_encode($lessonContent),
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    // Request 15 items from the generate endpoint
    $response = $this->postJson('/teacher/materials/' . $material->id . '/generate', [
        'activity' => 'quiz',
        'count' => 15,
        'types' => ['multiple_choice', 'true_false'],
    ]);

    $response->assertOk();
    $data = $response->json();
    expect($data['ok'])->toBeTrue();
    expect($data['items'])->toBeArray();
    expect(count($data['items']))->toBe(15);

    // Also test seatwork with 15 items
    $seatworkResponse = $this->postJson('/teacher/materials/' . $material->id . '/generate', [
        'activity' => 'seatwork',
        'count' => 15,
        'types' => ['identification', 'multiple_choice', 'true_false'],
    ]);

    $seatworkResponse->assertOk();
    $seatworkData = $seatworkResponse->json();
    expect($seatworkData['ok'])->toBeTrue();
    expect(count($seatworkData['items']))->toBe(15);

    SecureFileService::delete($material->file_path);
});

test('MaterialTextExtractor incorporates document metadata and full text as reference context', function () {
    $text = "Robotics and Automation principles in modern manufacturing environments.";
    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Robotics 101',
        'subject' => 'Robotics',
        'description' => 'Hands-on guide to actuators and sensors',
        'file_path' => 'teaching-materials/robotics.txt',
        'file_name' => 'robotics.txt',
        'file_size' => strlen($text),
        'file_type' => 'txt',
        'file_data' => base64_encode($text),
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $extracted = \App\Services\MaterialTextExtractor::extract($material);
    expect($extracted)->toHaveKey('text');
    expect($extracted['text'])->toContain('Material Title: Robotics 101');
    expect($extracted['text'])->toContain('Subject: Robotics');
    expect($extracted['text'])->toContain('Grade Level: Grade 10');
    expect($extracted['text'])->toContain('=== Document Content ===');
    expect($extracted['text'])->toContain('Robotics and Automation');

    SecureFileService::delete($material->file_path);
});

test('material generate endpoint returns title, instructions, and supports identification questions for quiz', function () {
    $this->actingAs($this->teacher);

    $lessonContent = "Graphic Design Essentials.\n"
        . "The Selection Tool with shortcut V allows users to select and move objects on the artboard.\n"
        . "The Direct Selection Tool with shortcut A modifies individual anchor points and vector segments.\n"
        . "The Pen Tool with shortcut P creates precise straight lines and smooth curves using Bezier handles.\n"
        . "The Type Tool with shortcut T creates point type or area type containers for text formatting.\n"
        . "The Rectangle Tool with shortcut M draws square and rectangular shapes.\n";

    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Graphic Design Essentials',
        'subject' => 'ICT',
        'description' => 'Vector graphics concepts',
        'file_path' => 'teaching-materials/design.txt',
        'file_name' => 'design.txt',
        'file_size' => strlen($lessonContent),
        'file_type' => 'txt',
        'file_data' => base64_encode($lessonContent),
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $response = $this->postJson('/teacher/materials/' . $material->id . '/generate', [
        'activity' => 'quiz',
        'count' => 3,
        'types' => ['identification'],
    ]);

    $response->assertOk();
    $data = $response->json();
    expect($data['ok'])->toBeTrue();
    expect($data)->toHaveKey('title');
    expect($data)->toHaveKey('instructions');
    expect($data['items'])->toBeArray();
    expect(count($data['items']))->toBe(3);
    expect($data['items'][0]['type'])->toBe('identification');
    expect($data['items'][0]['options'])->toBeArray();
    expect($data['items'][0]['options'][0]['is_correct'])->toBeTrue();

    SecureFileService::delete($material->file_path);
});

test('ai creator workspace preloads initialMaterial when material_id query param is supplied', function () {
    $this->actingAs($this->teacher);

    $content = "Computer Systems Servicing NC II notes on motherboard architectures.";
    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'CSS NC II Module',
        'subject' => 'CSS',
        'description' => 'Motherboard architectures',
        'file_path' => 'teaching-materials/css.txt',
        'file_name' => 'css.txt',
        'file_size' => strlen($content),
        'file_type' => 'txt',
        'file_data' => base64_encode($content),
        'grade_level_id' => $this->gradeLevel->id,
    ]);

    $response = $this->get('/teacher/ai-creator?material_id=' . $material->id . '&activity=seatwork');

    $response->assertOk();
    $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
        ->component('Teacher/AiCreator/Index')
        ->where('initialActivity', 'seatwork')
        ->has('initialMaterial')
        ->where('initialMaterial.id', $material->id)
        ->where('initialMaterial.title', 'CSS NC II Module')
        ->where('initialMaterial.text', fn ($val) => str_contains($val, 'Computer Systems Servicing NC II'))
    );

    SecureFileService::delete($material->file_path);
});
