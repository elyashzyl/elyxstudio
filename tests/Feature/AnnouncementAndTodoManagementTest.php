<?php

use App\Models\Announcement;
use App\Models\ClassTodo;
use App\Models\ClassTodoCompletion;
use App\Models\GradeLevel;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Section;
use App\Models\TeachingMaterial;
use App\Models\TeachingMaterialRead;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('teacher can view announcements index and publish announcement', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $section = Section::create(['name' => 'Emerald', 'grade_level_id' => $grade->id]);
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get(route('teacher.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Announcements')
            ->has('announcements')
            ->has('gradeLevels')
            ->has('sections')
        );

    $this->actingAs($teacher)
        ->post(route('teacher.announcements.store'), [
            'title' => 'Midterm Review Sessions',
            'content' => 'Review sessions will take place next Monday at Room 204.',
            'category' => 'academic',
            'is_pinned' => true,
            'target_grade_level_id' => $grade->id,
            'target_section_id' => $section->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('announcements', [
        'title' => 'Midterm Review Sessions',
        'is_pinned' => true,
        'user_id' => $teacher->id,
    ]);
});

test('teacher can toggle pin and delete announcement', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $announcement = Announcement::create([
        'user_id' => $teacher->id,
        'title' => 'School Fair Notice',
        'content' => 'Booth registrations open today.',
        'category' => 'event',
        'is_pinned' => false,
    ]);

    $this->actingAs($teacher)
        ->patch(route('teacher.announcements.toggle-pin', $announcement))
        ->assertRedirect();

    expect($announcement->fresh()->is_pinned)->toBeTrue();

    $this->actingAs($teacher)
        ->delete(route('teacher.announcements.destroy', $announcement))
        ->assertRedirect();

    $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
});

test('teacher can publish section announcement and it is scoped to that section', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $sectionA = Section::create(['name' => 'Diamond', 'grade_level_id' => $grade->id]);
    $sectionB = Section::create(['name' => 'Emerald', 'grade_level_id' => $grade->id]);

    $teacher = User::factory()->create(['role' => 'teacher']);
    $studentA = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
        'section_id' => $sectionA->id,
    ]);
    $studentB = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
        'section_id' => $sectionB->id,
    ]);

    // Teacher publishes section announcement without explicitly passing grade_level_id
    $this->actingAs($teacher)
        ->post(route('teacher.announcements.store'), [
            'title' => 'Diamond Clean-up Schedule',
            'content' => 'Section Diamond officers please remain after class.',
            'category' => 'reminder',
            'target_section_id' => $sectionA->id,
        ])
        ->assertRedirect();

    $announcement = Announcement::where('title', 'Diamond Clean-up Schedule')->first();
    expect($announcement)->not->toBeNull();
    expect($announcement->target_section_id)->toBe($sectionA->id);
    expect($announcement->target_grade_level_id)->toBe($grade->id);

    // Student in Diamond sees the announcement with section info
    $this->actingAs($studentA)
        ->get(route('student.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Announcements')
            ->has('announcements.data', 1)
            ->where('announcements.data.0.title', 'Diamond Clean-up Schedule')
            ->where('announcements.data.0.section.name', 'Diamond')
        );

    // Student in Emerald in the same grade does not see Diamond section announcement
    $this->actingAs($studentB)
        ->get(route('student.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Announcements')
            ->has('announcements.data', 0)
        );
});

test('student can browse targeted announcements on dedicated page', function () {
    $grade = GradeLevel::create(['name' => 'Grade 9']);
    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
    ]);
    $teacher = User::factory()->create(['role' => 'teacher']);

    Announcement::create([
        'user_id' => $teacher->id,
        'title' => 'Grade 9 General Assembly',
        'content' => 'Welcome to the new school year!',
        'category' => 'general',
        'target_grade_level_id' => $grade->id,
        'is_pinned' => true,
    ]);

    $this->actingAs($student)
        ->get(route('student.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Announcements')
            ->has('announcements.data', 1)
            ->where('announcements.data.0.title', 'Grade 9 General Assembly')
            ->where('announcements.data.0.is_pinned', true)
        );
});

test('teacher can assign class to-do task with completion tracking', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $section = Section::create(['name' => 'Topaz', 'grade_level_id' => $grade->id]);
    $teacher = User::factory()->create(['role' => 'teacher']);

    $this->actingAs($teacher)
        ->get(route('teacher.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Todos')
            ->has('todos')
            ->has('gradeLevels')
            ->has('sections')
        );

    $this->actingAs($teacher)
        ->post(route('teacher.todos.store'), [
            'title' => 'Read Chapters 3 and 4',
            'description' => 'Complete the study guide questions at the end of the chapter.',
            'category' => 'reading',
            'target_grade_level_id' => $grade->id,
            'target_section_id' => $section->id,
            'due_date' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('class_todos', [
        'title' => 'Read Chapters 3 and 4',
        'teacher_id' => $teacher->id,
        'category' => 'reading',
    ]);
});

test('student can toggle class task completion and material read state', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
    ]);
    $teacher = User::factory()->create(['role' => 'teacher']);

    // Create class to-do
    $todo = ClassTodo::create([
        'teacher_id' => $teacher->id,
        'title' => 'Submit Group Research Proposal',
        'target_grade_level_id' => $grade->id,
        'is_active' => true,
    ]);

    // Create teaching material
    $material = TeachingMaterial::create([
        'teacher_id' => $teacher->id,
        'title' => 'Module 1 - Cellular Biology',
        'subject' => 'Science',
        'file_name' => 'module1.pdf',
        'file_path' => 'materials/module1.pdf',
        'file_type' => 'pdf',
        'grade_level_id' => $grade->id,
    ]);

    // Student views todos page
    $this->actingAs($student)
        ->get(route('student.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Todos')
            ->has('pendingTodos', 2)
            ->where('stats.pending', 2)
        );

    // Student toggles task as completed
    $this->actingAs($student)
        ->post(route('student.todos.toggle', $todo))
        ->assertRedirect();

    expect(ClassTodoCompletion::where('class_todo_id', $todo->id)->where('user_id', $student->id)->exists())->toBeTrue();

    // Student marks material as read
    $this->actingAs($student)
        ->post(route('student.materials.toggle-read', $material))
        ->assertRedirect();

    expect(TeachingMaterialRead::where('teaching_material_id', $material->id)->where('user_id', $student->id)->exists())->toBeTrue();

    // Student visits todos page again: both items now completed
    $this->actingAs($student)
        ->get(route('student.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Todos')
            ->has('pendingTodos', 0)
            ->has('completedTodos', 2)
            ->where('stats.completed', 2)
        );
});

test('teacher can view and delete seeded or unowned announcements and tasks', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);

    $author = User::factory()->create(['role' => 'teacher']);

    // Seeded announcement with null author or different user
    $announcement = Announcement::create([
        'user_id' => $author->id,
        'title' => 'Seeded School Notice',
        'content' => 'Notice for all users.',
        'category' => 'general',
    ]);

    // Seeded task with different teacher
    $todo = ClassTodo::create([
        'teacher_id' => $author->id,
        'title' => 'Seeded Class Assignment',
        'description' => 'Assignment details.',
        'category' => 'project',
        'is_active' => true,
    ]);

    // Teacher can see announcement with can_manage true
    $this->actingAs($teacher)
        ->get(route('teacher.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Announcements')
            ->has('announcements.data', fn (Assert $data) => $data
                ->where('0.title', 'Seeded School Notice')
                ->where('0.can_manage', true)
                ->etc()
            )
        );

    // Teacher can delete seeded announcement
    $this->actingAs($teacher)
        ->delete(route('teacher.announcements.destroy', $announcement))
        ->assertRedirect();

    $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);

    // Teacher can see task with can_manage true
    $this->actingAs($teacher)
        ->get(route('teacher.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Todos')
            ->has('todos.data', fn (Assert $data) => $data
                ->where('0.title', 'Seeded Class Assignment')
                ->where('0.can_manage', true)
                ->etc()
            )
        );

    // Teacher can delete seeded task
    $this->actingAs($teacher)
        ->delete(route('teacher.todos.destroy', $todo))
        ->assertRedirect();

    $this->assertDatabaseMissing('class_todos', ['id' => $todo->id]);
});

test('deleting last task or announcement leaves list empty and does not resurrect seeded items', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    ClassTodo::query()->delete();
    Announcement::query()->delete();

    $this->actingAs($teacher)
        ->get(route('teacher.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Todos')
            ->has('todos.data', 0)
        );

    expect(ClassTodo::count())->toBe(0);

    $this->actingAs($teacher)
        ->get(route('teacher.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Announcements')
            ->has('announcements.data', 0)
        );

    expect(Announcement::count())->toBe(0);
});

test('student can dismiss and restore activity todo item from active list and dashboard', function () {
    $grade = GradeLevel::create(['name' => 'Grade 10']);
    $student = User::factory()->create([
        'role' => 'student',
        'grade_level_id' => $grade->id,
    ]);
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student->teachers()->attach($teacher->id);

    $quiz = Quiz::create([
        'teacher_id' => $teacher->id,
        'title' => 'Dismissable Biology Quiz',
        'grade' => 'Grade 10',
        'is_published' => true,
    ]);
    $quiz->gradeLevels()->attach($grade->id);

    // Initial check: quiz is in pending todos and dashboard
    $this->actingAs($student)
        ->get(route('student.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Todos')
            ->has('pendingTodos', 1)
            ->where('pendingTodos.0.title', 'Dismissable Biology Quiz')
        );

    $this->actingAs($student)
        ->get('/student/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->where('todos', fn ($todos) => collect($todos)->contains('title', 'Dismissable Biology Quiz'))
        );

    // Student dismisses the quiz todo
    $this->actingAs($student)
        ->post(route('student.todos.dismiss'), ['todo_key' => 'quiz_' . $quiz->id])
        ->assertRedirect();

    $this->assertDatabaseHas('user_dismissed_todos', [
        'user_id' => $student->id,
        'todo_key' => 'quiz_' . $quiz->id,
    ]);

    // Now it is gone from pending and dashboard, and appears in dismissedTodos
    $this->actingAs($student)
        ->get(route('student.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Todos')
            ->has('pendingTodos', 0)
            ->has('dismissedTodos', 1)
            ->where('dismissedTodos.0.title', 'Dismissable Biology Quiz')
        );

    $this->actingAs($student)
        ->get('/student/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Dashboard')
            ->where('todos', fn ($todos) => ! collect($todos)->contains('title', 'Dismissable Biology Quiz'))
        );

    // Student restores the quiz todo
    $this->actingAs($student)
        ->post(route('student.todos.restore'), ['todo_key' => 'quiz_' . $quiz->id])
        ->assertRedirect();

    $this->assertDatabaseMissing('user_dismissed_todos', [
        'user_id' => $student->id,
        'todo_key' => 'quiz_' . $quiz->id,
    ]);

    // Returned to pending list
    $this->actingAs($student)
        ->get(route('student.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Student/Todos')
            ->has('pendingTodos', 1)
            ->has('dismissedTodos', 0)
        );
});

test('teacher can dismiss submission action items from todos view', function () {
    $teacher = User::factory()->create(['role' => 'teacher']);
    $student = User::factory()->create(['role' => 'student']);

    $practical = \App\Models\Practical::create([
        'teacher_id' => $teacher->id,
        'title' => 'Database Query Design',
        'is_published' => true,
    ]);

    $attempt = \App\Models\PracticalAttempt::create([
        'practical_id' => $practical->id,
        'student_id' => $student->id,
        'status' => \App\Enums\AttemptStatus::Submitted->value,
        'total_score' => null,
    ]);

    $this->actingAs($teacher)
        ->get(route('teacher.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Todos')
            ->has('gradingTasks', 1)
            ->where('gradingTasks.0.id', 'practical_' . $attempt->id)
        );

    $this->actingAs($teacher)
        ->post(route('teacher.todos.dismiss-grading-task'), [
            'task_key' => 'practical_' . $attempt->id,
        ])
        ->assertRedirect();

    $this->actingAs($teacher)
        ->get(route('teacher.todos.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Teacher/Todos')
            ->has('gradingTasks', 0)
        );
});
