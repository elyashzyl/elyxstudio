<?php

use App\Helpers\PointsHelper;
use App\Models\Exam;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\Seatwork;
use App\Models\Setting;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email' => 'superadmin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->teacher = User::factory()->create([
        'role' => 'teacher',
        'email' => 'teacher@example.com',
        'email_verified_at' => now(),
    ]);

    $this->grade8 = GradeLevel::create([
        'name' => 'Grade 8',
        'code' => 'G8',
        'display_order' => 1,
        'is_active' => true,
    ]);

    $this->grade10 = GradeLevel::create([
        'name' => 'Grade 10',
        'code' => 'G10',
        'display_order' => 2,
        'is_active' => true,
    ]);

    $this->studentGrade8 = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 8',
        'grade_level_id' => $this->grade8->id,
        'email_verified_at' => now(),
    ]);

    $this->studentGrade10 = User::factory()->create([
        'role' => 'student',
        'grade' => 'Grade 10',
        'grade_level_id' => $this->grade10->id,
        'email_verified_at' => now(),
    ]);
});

test('system settings update supports payment_email and support_email and shares them via inertia', function () {
    $this->actingAs($this->superadmin);

    $response = $this->put('/teacher/system-settings', [
        'app_name' => 'EduPulse Global',
        'support_email' => 'ely.ashzyl@gmail.com',
        'payment_email' => 'ely.ashzyl@gmail.com',
    ]);

    $response->assertSessionHas('success');
    expect(Setting::get('support_email'))->toBe('ely.ashzyl@gmail.com');
    expect(Setting::get('payment_email'))->toBe('ely.ashzyl@gmail.com');
    expect(Setting::getSupportEmail())->toBe('ely.ashzyl@gmail.com');
    expect(Setting::getPaymentEmail())->toBe('ely.ashzyl@gmail.com');

    // Test sharing to Inertia for guests on Welcome page
    auth()->logout();
    $page = $this->get('/');
    $page->assertOk();
    $page->assertInertia(fn ($assert) => $assert
        ->where('support_email', 'ely.ashzyl@gmail.com')
        ->where('payment_email', 'ely.ashzyl@gmail.com')
    );
});

test('points helper calculate reads dynamic thresholds from database settings', function () {
    // Default: 80% => 10, 60% => 5, 1% => 1
    expect(PointsHelper::calculate(85))->toBe(10);
    expect(PointsHelper::calculate(65))->toBe(5);
    expect(PointsHelper::calculate(20))->toBe(1);

    // Custom settings from database
    Setting::set('xp_mastery_percentage', 90);
    Setting::set('xp_passing_percentage', 70);
    Setting::set('xp_award_mastery', 25);
    Setting::set('xp_award_pass', 15);
    Setting::set('xp_award_attempt', 3);

    expect(PointsHelper::calculate(95))->toBe(25);
    expect(PointsHelper::calculate(85))->toBe(15); // 85% is now passing, not mastery
    expect(PointsHelper::calculate(72))->toBe(15);
    expect(PointsHelper::calculate(50))->toBe(3);
    expect(PointsHelper::calculate(0))->toBe(0);
});

test('student cannot take practical assigned to another grade level', function () {
    $practical = Practical::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Grade 10 InDesign Practical',
        'grade' => 'Grade 10',
        'max_score' => 100,
        'max_attempts' => 3,
        'is_published' => true,
    ]);
    $practical->gradeLevels()->sync([$this->grade10->id]);

    // Student from Grade 8 is forbidden
    $this->actingAs($this->studentGrade8);
    $response = $this->get(route('student.practicals.take', $practical->id));
    $response->assertStatus(403);

    // Student from Grade 10 is permitted
    $this->actingAs($this->studentGrade10);
    $response = $this->get(route('student.practicals.take', $practical->id));
    $response->assertOk();
});

test('student cannot take exam assigned to another grade level', function () {
    $exam = Exam::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Grade 10 Midterm Exam',
        'grade' => 'Grade 10',
        'max_score' => 100,
        'is_published' => true,
    ]);
    $exam->gradeLevels()->sync([$this->grade10->id]);

    // Student from Grade 8 is forbidden
    $this->actingAs($this->studentGrade8);
    $response = $this->get(route('student.exams.take', $exam->id));
    $response->assertStatus(403);

    // Student from Grade 10 is permitted
    $this->actingAs($this->studentGrade10);
    $response = $this->get(route('student.exams.take', $exam->id));
    $response->assertOk();
});

test('student cannot preview material assigned to another grade level', function () {
    $material = TeachingMaterial::create([
        'teacher_id' => $this->teacher->id,
        'title' => 'Grade 10 InDesign Slides',
        'subject' => 'ICT',
        'file_path' => 'teaching-materials/dummy.pdf',
        'file_name' => 'dummy.pdf',
        'file_type' => 'pdf',
        'grade_level_id' => $this->grade10->id,
    ]);

    // Student from Grade 8 is forbidden
    $this->actingAs($this->studentGrade8);
    $response = $this->get(route('materials.preview', $material->id));
    $response->assertStatus(403);
});
