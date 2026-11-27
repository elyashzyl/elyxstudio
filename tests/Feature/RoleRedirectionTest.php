<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleRedirectionTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->student = User::factory()->create(['role' => 'student', 'grade' => '8']);
    }

    public function test_student_accessing_teacher_area_is_redirected_to_student_dashboard(): void
    {
        $response = $this->actingAs($this->student)
            ->get('/teacher/dashboard');

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_teacher_accessing_student_area_is_redirected_to_teacher_dashboard(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get('/student/dashboard');

        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_student_accessing_settings_is_redirected_to_student_profile(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');
        $response->assertRedirect(route('student.profile'));

        $response = $this->actingAs($this->student)->get('/settings/profile');
        $response->assertRedirect(route('student.profile'));

        $response = $this->actingAs($this->student)->get('/settings/security');
        $response->assertRedirect(route('student.profile'));
    }

    public function test_teacher_accessing_settings_is_redirected_to_teacher_profile(): void
    {
        $response = $this->actingAs($this->teacher)->get('/settings');
        $response->assertRedirect(route('teacher.profile'));

        $response = $this->actingAs($this->teacher)->get('/settings/profile');
        $response->assertRedirect(route('teacher.profile'));

        $response = $this->actingAs($this->teacher)->get('/settings/security');
        $response->assertRedirect(route('teacher.profile'));
    }

    public function test_login_redirects_student_to_student_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->student->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_login_redirects_teacher_to_teacher_dashboard(): void
    {
        $response = $this->post('/login', [
            'email' => $this->teacher->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_student_login_ignores_teacher_intended_url(): void
    {
        // Stale session intended URL from teacher
        session(['url.intended' => 'http://localhost/teacher/students']);

        $response = $this->post('/login', [
            'email' => $this->student->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_teacher_login_ignores_student_intended_url(): void
    {
        // Stale session intended URL from student
        session(['url.intended' => 'http://localhost/student/exams']);

        $response = $this->post('/login', [
            'email' => $this->teacher->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_logout_clears_intended_url_and_redirects_to_login(): void
    {
        session(['url.intended' => 'http://localhost/teacher/students']);

        $response = $this->actingAs($this->teacher)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertNull(session('url.intended'));
    }

    public function test_unauthenticated_user_accessing_protected_route_is_redirected_to_login(): void
    {
        $response = $this->get('/teacher/dashboard');
        $response->assertRedirect(route('login'));

        $response = $this->get('/student/dashboard');
        $response->assertRedirect(route('login'));
    }

    public function test_login_ignores_notifications_intended_url(): void
    {
        // Polling background fetch caused url.intended to be /teacher/notifications
        session(['url.intended' => 'http://localhost/teacher/notifications']);

        $response = $this->post('/login', [
            'email' => $this->teacher->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));

        $this->post('/logout');

        // For student
        session(['url.intended' => 'http://localhost/student/notifications']);

        $response = $this->post('/login', [
            'email' => $this->student->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('student.dashboard'));
    }

    public function test_login_ignores_api_intended_url(): void
    {
        session(['url.intended' => 'http://localhost/api/attendance']);

        $response = $this->post('/login', [
            'email' => $this->teacher->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_browser_visiting_notifications_redirects_to_dashboard(): void
    {
        // Browser navigation accepting HTML
        $response = $this->actingAs($this->teacher)
            ->withHeaders(['Accept' => 'text/html,application/xhtml+xml'])
            ->get('/teacher/notifications');

        $response->assertRedirect(route('teacher.dashboard'));
    }
}
