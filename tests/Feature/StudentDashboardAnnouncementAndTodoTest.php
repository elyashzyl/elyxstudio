<?php

use App\Models\Announcement;
use App\Models\GradeLevel;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Seatwork;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

test('student dashboard renders announcements and todos', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $section = Section::create(['name' => 'Diamond', 'grade_level_id' => $grade->id]);

    $teacher = User::factory()->create([
        'role' => 'teacher',
    ]);

    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
    ]);

    $student->teachers()->attach($teacher->id);

    // Create an announcement
    $announcement = Announcement::create([
        'user_id' => $teacher->id,
        'title' => 'Quarter 2 Practical Orientation',
        'content' => 'Please review the schedule for Quarter 2 assessments.',
        'category' => 'academic',
        'is_pinned' => true,
        'target_role' => 'student',
    ]);

    // Create a published quiz accessible to student
    $quiz = Quiz::create([
        'teacher_id' => $teacher->id,
        'title' => 'Basics of Vector Art',
        'grade' => 'Grade 10',
        'is_published' => true,
        'closes_at' => now()->addDays(2),
    ]);
    $quiz->gradeLevels()->attach($grade->id);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->has('announcements', 1)
            ->where('announcements.0.title', 'Quarter 2 Practical Orientation')
            ->where('announcements.0.is_pinned', true)
            ->has('todos')
            ->where('todos.0.title', 'Basics of Vector Art')
            ->where('todos.0.type', 'Quiz')
        );
});

test('completed quiz is removed from student todos', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $section = Section::create(['name' => 'Ruby', 'grade_level_id' => $grade->id]);

    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
    ]);
    $student->teachers()->attach($teacher->id);

    $quiz = Quiz::create([
        'teacher_id' => $teacher->id,
        'title' => 'Sample Completed Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quiz->gradeLevels()->attach($grade->id);

    // Create submitted attempt
    QuizAttempt::create([
        'quiz_id' => $quiz->id,
        'student_id' => $student->id,
        'score' => 10,
        'total_points' => 10,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->where('todos', fn ($todos) => ! collect($todos)->contains('title', 'Sample Completed Quiz'))
        );
});

test('teacher can store and destroy announcements', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $response = $this->actingAs($teacher)
        ->post(route('teacher.announcements.store'), [
            'title' => 'Emergency Campus Closure',
            'content' => 'Classes are suspended due to heavy rain.',
            'category' => 'urgent',
            'is_pinned' => true,
        ]);

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('announcements', [
        'title' => 'Emergency Campus Closure',
        'category' => 'urgent',
        'is_pinned' => true,
    ]);

    $announcement = Announcement::where('title', 'Emergency Campus Closure')->first();

    $deleteResponse = $this->actingAs($teacher)
        ->delete(route('teacher.announcements.destroy', $announcement->id));

    $deleteResponse->assertSessionHas('success');

    $this->assertDatabaseMissing('announcements', [
        'id' => $announcement->id,
    ]);
});

test('student dashboard does not auto-seed polls when polls table is empty', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
    ]);

    expect(\App\Models\Poll::count())->toBe(0);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->where('activePoll', null)
        );

    expect(\App\Models\Poll::count())->toBe(0);
});

test('reading material and completing class task updates dashboard todos', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
    ]);
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student->teachers()->attach($teacher->id);

    // Create material
    $material = \App\Models\TeachingMaterial::create([
        'teacher_id' => $teacher->id,
        'title' => 'Important Lecture Slides',
        'subject' => 'Math',
        'file_name' => 'slides.pdf',
        'file_path' => 'materials/slides.pdf',
        'file_type' => 'pdf',
        'grade_level_id' => $grade->id,
    ]);

    // Create class todo
    $todo = \App\Models\ClassTodo::create([
        'teacher_id' => $teacher->id,
        'title' => 'Submit Math Worksheet',
        'target_grade_level_id' => $grade->id,
        'is_active' => true,
    ]);

    // Dashboard shows both as pending todos
    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->where('todos', fn ($todos) =>
                collect($todos)->contains('type', 'Material') &&
                collect($todos)->contains('type', 'Task')
            )
        );

    // Mark material as read and task as done
    $this->actingAs($student)->post(route('student.materials.toggle-read', $material))->assertRedirect();
    $this->actingAs($student)->post(route('student.todos.toggle', $todo))->assertRedirect();

    // Dashboard no longer lists them in pending todos
    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->where('todos', fn ($todos) =>
                ! collect($todos)->contains('title', fn ($t) => str_contains($t, 'Important Lecture Slides')) &&
                ! collect($todos)->contains('title', 'Submit Math Worksheet')
            )
        );
});

test('student with section can view student todos index with published exam', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $section = Section::create(['name' => 'Ruby', 'grade_level_id' => $grade->id]);
    $teacher = User::factory()->create(['role' => 'teacher']);

    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
        'section_id' => $section->id,
    ]);
    $student->teachers()->attach($teacher->id);

    $exam = \App\Models\Exam::create([
        'teacher_id' => $teacher->id,
        'title' => 'Quarterly Science Exam',
        'grade' => 'Grade 10',
        'is_published' => true,
        'closes_at' => now()->addDays(2),
    ]);
    $exam->gradeLevels()->attach($grade->id);

    // Create an exam question section
    \App\Models\ExamSection::create([
        'exam_id' => $exam->id,
        'title' => 'Part 1: Multiple Choice',
        'section_type' => 'questions',
        'order' => 0,
    ]);

    // Student visits todos index without QueryException on exam_sections.section_id
    $this->actingAs($student)
        ->get(route('student.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Todos')
            ->has('pendingTodos', fn (Assert $todos) => $todos
                ->where('0.title', 'Quarterly Science Exam')
                ->where('0.type', 'Exam')
                ->etc()
            )
        );
});
