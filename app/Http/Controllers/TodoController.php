<?php

namespace App\Http\Controllers;

use App\Models\ClassTodo;
use App\Models\ClassTodoCompletion;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\Section;
use App\Models\TeachingMaterial;
use App\Models\TeachingMaterialRead;
use App\Models\User;
use App\Models\UserDismissedTodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class TodoController extends Controller
{
    public function teacherIndex(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Pending teacher grading action items
        $isSuper = $user->isSuperadmin();
        $gradingTasks = collect();
        $dismissedGradingTasks = UserDismissedTodo::where('user_id', $user->id)
            ->pluck('todo_key')
            ->all();

        // Practical submissions needing teacher evaluation
        $practicalQuery = PracticalAttempt::where('status', \App\Enums\AttemptStatus::Submitted->value)->whereNull('total_score');
        if (! $isSuper) {
            $practicalQuery->whereIn('practical_id', $user->practicals()->pluck('id'));
        }
        foreach ($practicalQuery->with('practical:id,title', 'student:id,name')->latest()->limit(15)->get() as $pa) {
            $taskKey = 'practical_' . $pa->id;
            if (in_array($taskKey, $dismissedGradingTasks, true)) {
                continue;
            }
            $gradingTasks->push([
                'id' => $taskKey,
                'task_key' => $taskKey,
                'type' => 'Practical',
                'title' => 'Grade Practical Submission',
                'description' => "{$pa->student->name} submitted {$pa->practical->title}",
                'link' => route('teacher.practicals.grade', ['practical' => $pa->practical_id, 'attempt' => $pa->id]),
                'action_label' => 'Grade Attempt',
                'created_at' => $pa->created_at?->format('M d, g:i A'),
                'is_urgent' => true,
            ]);
        }

        // Exam submissions needing teacher review
        $examQuery = ExamAttempt::where('status', \App\Enums\AttemptStatus::Submitted->value)->whereNull('total_score');
        if (! $isSuper) {
            $examQuery->whereIn('exam_id', $user->exams()->pluck('id'));
        }
        foreach ($examQuery->with('exam:id,title', 'student:id,name')->latest()->limit(15)->get() as $ea) {
            $taskKey = 'exam_' . $ea->id;
            if (in_array($taskKey, $dismissedGradingTasks, true)) {
                continue;
            }
            $gradingTasks->push([
                'id' => $taskKey,
                'task_key' => $taskKey,
                'type' => 'Exam',
                'title' => 'Review Exam Submission',
                'description' => "{$ea->student->name} submitted {$ea->exam->title}",
                'link' => route('teacher.exams.attempts', ['exam' => $ea->exam_id]),
                'action_label' => 'Review Exam',
                'created_at' => $ea->created_at?->format('M d, g:i A'),
                'is_urgent' => true,
            ]);
        }

        $query = ClassTodo::with(['teacher:id,name', 'gradeLevel:id,name', 'section:id,name'])
            ->withCount('completions')
            ->latest();

        $todos = $query->paginate(25)->through(function ($t) use ($user) {
            $eligibleStudentsQuery = User::where('role', 'student');
            if ($t->target_section_id) {
                $eligibleStudentsQuery->where('section_id', $t->target_section_id);
            } elseif ($t->target_grade_level_id) {
                $eligibleStudentsQuery->where('grade_level_id', $t->target_grade_level_id);
            }
            $totalEligible = $eligibleStudentsQuery->count();

            return [
                'id' => $t->id,
                'title' => $t->title,
                'description' => $t->description,
                'category' => $t->category ?: 'general',
                'grade_level' => $t->gradeLevel ? ['id' => $t->gradeLevel->id, 'name' => $t->gradeLevel->name] : null,
                'section' => $t->section ? ['id' => $t->section->id, 'name' => $t->section->name] : null,
                'due_date' => $t->due_date?->toIso8601String(),
                'formatted_due' => $t->due_date ? $t->due_date->format('M d, Y g:i A') : 'No deadline',
                'due_human' => $t->due_date ? $t->due_date->diffForHumans() : null,
                'is_overdue' => $t->due_date ? $t->due_date->isPast() : false,
                'link_url' => $t->link_url,
                'teacher_name' => $t->teacher?->name ?? 'EduPulse Admin',
                'teacher_id' => $t->teacher_id,
                'completions_count' => (int) $t->completions_count,
                'total_eligible' => $totalEligible,
                'can_manage' => true,
                'created_at' => $t->created_at?->format('M d, Y'),
            ];
        });

        $gradeLevels = GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']);
        $sections = Section::where('is_active', true)->orderBy('name')->get(['id', 'name', 'grade_level_id']);

        return Inertia::render('Teacher/Todos', [
            'todos' => $todos,
            'gradingTasks' => $gradingTasks,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'nullable|string|in:general,homework,project,reading,reminder',
            'target_grade_level_id' => 'nullable|exists:grade_levels,id',
            'target_section_id' => 'nullable|exists:sections,id',
            'due_date' => 'nullable|date',
            'link_url' => 'nullable|url|max:255',
        ]);

        $validated['teacher_id'] = Auth::id();
        $validated['category'] = $validated['category'] ?? 'general';
        $validated['is_active'] = true;

        ClassTodo::create($validated);

        return back()->with('success', 'Class To-Do created successfully.');
    }

    public function destroy(ClassTodo $todo): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isTeacher() || $user->isSuperadmin(), 403, 'Unauthorized.');

        $todo->completions()->delete();
        $todo->delete();

        return back()->with('success', 'Class To-Do deleted.');
    }

    public function studentIndex(Request $request): Response
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();

        // 1. Target activities
        $teacherIds = $student->teachers()->pluck('users.id')
            ->concat(User::where('role', 'superadmin')->pluck('id'))
            ->unique();

        $applyGradeScope = function ($query) use ($student) {
            $query->where(function ($q) use ($student) {
                if ($student->grade_level_id) {
                    $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
                } elseif ($student->grade) {
                    $q->where('grade', $student->grade);
                }
            });
        };

        $quizIds = Quiz::whereIn('teacher_id', $teacherIds)->where('is_published', true)->tap($applyGradeScope)->pluck('id');
        $seatworkIds = Seatwork::whereIn('teacher_id', $teacherIds)->where('is_published', true)->tap($applyGradeScope)->pluck('id');
        $examIds = Exam::whereIn('teacher_id', $teacherIds)->where('is_published', true)->tap($applyGradeScope)->pluck('id');
        $practicalIds = Practical::whereIn('teacher_id', $teacherIds)->where('is_published', true)->tap($applyGradeScope)->pluck('id');

        // Attempts
        $quizAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)->where('student_id', $student->id)->where('status', 'submitted')->with('quiz:id,title')->get();
        $seatworkAttempts = SeatworkAttempt::whereIn('seatwork_id', $seatworkIds)->where('student_id', $student->id)->where('status', 'submitted')->with('seatwork:id,title')->get();
        $examAttempts = ExamAttempt::whereIn('exam_id', $examIds)->where('student_id', $student->id)->where('status', 'submitted')->with('exam:id,title')->get();
        $practicalAttempts = PracticalAttempt::whereIn('practical_id', $practicalIds)->where('student_id', $student->id)->where('status', 'submitted')->with('practical:id,title')->get();

        $submittedQuizIds = $quizAttempts->pluck('quiz_id')->unique()->all();
        $submittedSeatworkIds = $seatworkAttempts->pluck('seatwork_id')->unique()->all();
        $submittedExamIds = $examAttempts->pluck('exam_id')->unique()->all();
        $submittedPracticalIds = $practicalAttempts->pluck('practical_id')->unique()->all();

        // Open (pending) activities
        $openQuizzes = Quiz::whereIn('id', $quizIds)->whereNotIn('id', $submittedQuizIds)
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))->get();
        $openSeatworks = Seatwork::whereIn('id', $seatworkIds)->whereNotIn('id', $submittedSeatworkIds)
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))->get();
        $openExams = Exam::whereIn('id', $examIds)->whereNotIn('id', $submittedExamIds)
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))->get();
        $openPracticals = Practical::whereIn('id', $practicalIds)->whereNotIn('id', $submittedPracticalIds)
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))->get();

        // Materials
        $readMaterialIds = TeachingMaterialRead::where('user_id', $student->id)->pluck('teaching_material_id')->all();
        $materials = TeachingMaterial::query()
            ->where(function ($q) use ($student) {
                $q->whereNull('grade_level_id');
                if ($student->grade_level_id) {
                    $q->orWhere('grade_level_id', $student->grade_level_id);
                }
            })
            ->latest()
            ->get();

        // Class To-Dos
        $completedTodoIds = ClassTodoCompletion::where('user_id', $student->id)->pluck('class_todo_id')->all();
        $classTodos = ClassTodo::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('target_grade_level_id')->orWhere('target_grade_level_id', $student->grade_level_id))
            ->where(fn ($q) => $q->whereNull('target_section_id')->orWhere('target_section_id', $student->section_id))
            ->latest()
            ->get();

        $dismissedKeys = UserDismissedTodo::where('user_id', $student->id)
            ->pluck('todo_key')
            ->all();

        $pending = collect();
        $completed = collect();
        $dismissed = collect();

        $pushOrDismiss = function (array $item, bool $isCompleted) use (&$pending, &$completed, &$dismissed, $dismissedKeys) {
            $item['dismiss_key'] = $item['id'];
            $item['dismiss_url'] = route('student.todos.dismiss', [], false);
            $item['restore_url'] = route('student.todos.restore', [], false);

            if (in_array($item['id'], $dismissedKeys, true)) {
                $item['dismissed'] = true;
                $dismissed->push($item);
            } elseif ($isCompleted) {
                $completed->push($item);
            } else {
                $pending->push($item);
            }
        };

        // Pending activities
        foreach ($openQuizzes as $q) {
            $isUrgent = $q->closes_at ? $q->closes_at->isToday() || $q->closes_at->isTomorrow() : false;
            $pushOrDismiss([
                'id' => 'quiz_' . $q->id,
                'source_id' => $q->id,
                'type' => 'Quiz',
                'title' => $q->title,
                'description' => $q->description,
                'href' => "/student/quizzes/{$q->id}/take",
                'action_label' => 'Take Quiz',
                'due_date' => $q->closes_at?->format('M d, g:i A'),
                'due_human' => $q->closes_at?->diffForHumans() ?? 'No deadline',
                'is_urgent' => $isUrgent,
                'time_limit' => $q->time_limit_minutes,
                'completed' => false,
            ], false);
        }
        foreach ($openSeatworks as $s) {
            $isUrgent = $s->closes_at ? $s->closes_at->isToday() || $s->closes_at->isTomorrow() : false;
            $pushOrDismiss([
                'id' => 'seatwork_' . $s->id,
                'source_id' => $s->id,
                'type' => 'Seatwork',
                'title' => $s->title,
                'description' => $s->description,
                'href' => "/student/seatworks/{$s->id}/take",
                'action_label' => 'Start Seatwork',
                'due_date' => $s->closes_at?->format('M d, g:i A'),
                'due_human' => $s->closes_at?->diffForHumans() ?? 'No deadline',
                'is_urgent' => $isUrgent,
                'time_limit' => $s->time_limit_minutes,
                'completed' => false,
            ], false);
        }
        foreach ($openExams as $e) {
            $isUrgent = $e->closes_at ? $e->closes_at->isToday() || $e->closes_at->isTomorrow() : false;
            $pushOrDismiss([
                'id' => 'exam_' . $e->id,
                'source_id' => $e->id,
                'type' => 'Exam',
                'title' => $e->title,
                'description' => $e->description,
                'href' => "/student/exams/{$e->id}/take",
                'action_label' => 'Start Exam',
                'due_date' => $e->closes_at?->format('M d, g:i A'),
                'due_human' => $e->closes_at?->diffForHumans() ?? 'No deadline',
                'is_urgent' => $isUrgent,
                'time_limit' => $e->time_limit_minutes,
                'completed' => false,
            ], false);
        }
        foreach ($openPracticals as $p) {
            $isUrgent = $p->closes_at ? $p->closes_at->isToday() || $p->closes_at->isTomorrow() : false;
            $pushOrDismiss([
                'id' => 'practical_' . $p->id,
                'source_id' => $p->id,
                'type' => 'Practical',
                'title' => $p->title,
                'description' => $p->description,
                'href' => "/student/practicals/{$p->id}/take",
                'action_label' => 'Start Practical',
                'due_date' => $p->closes_at?->format('M d, g:i A'),
                'due_human' => $p->closes_at?->diffForHumans() ?? 'No deadline',
                'is_urgent' => $isUrgent,
                'time_limit' => $p->time_limit_minutes,
                'completed' => false,
            ], false);
        }

        // Materials pending & completed
        foreach ($materials as $m) {
            $isRead = in_array($m->id, $readMaterialIds, true);
            $pushOrDismiss([
                'id' => 'material_' . $m->id,
                'source_id' => $m->id,
                'type' => 'Material',
                'title' => $m->title . ($m->subject ? " ({$m->subject})" : ''),
                'description' => $m->description ?: "Uploaded material ({$m->file_type})",
                'href' => route('materials.preview', $m->id, false),
                'action_label' => 'Read Material',
                'toggle_url' => route('student.materials.toggle-read', $m->id, false),
                'due_date' => null,
                'due_human' => 'Teaching material',
                'is_urgent' => false,
                'time_limit' => null,
                'completed' => $isRead,
            ], $isRead);
        }

        // Class To-Dos pending & completed
        foreach ($classTodos as $ct) {
            $isDone = in_array($ct->id, $completedTodoIds, true);
            $isUrgent = $ct->due_date ? ($ct->due_date->isPast() || $ct->due_date->isToday() || $ct->due_date->isTomorrow()) : false;
            $pushOrDismiss([
                'id' => 'task_' . $ct->id,
                'source_id' => $ct->id,
                'type' => 'Task',
                'title' => $ct->title,
                'description' => $ct->description,
                'href' => $ct->link_url ?: null,
                'action_label' => $ct->link_url ? 'Open Resource' : 'Assigned Task',
                'toggle_url' => route('student.todos.toggle', $ct->id, false),
                'due_date' => $ct->due_date?->format('M d, g:i A'),
                'due_human' => $ct->due_date ? $ct->due_date->diffForHumans() : 'No deadline',
                'is_urgent' => $isUrgent,
                'time_limit' => null,
                'completed' => $isDone,
            ], $isDone);
        }

        // Completed activities
        foreach ($quizAttempts as $qa) {
            $quiz = $qa->quiz;
            if ($quiz) {
                $pushOrDismiss([
                    'id' => 'quiz_done_' . $qa->id,
                    'source_id' => $quiz->id,
                    'type' => 'Quiz',
                    'title' => $quiz->title,
                    'description' => "Submitted with score: {$qa->score}",
                    'href' => "/student/quizzes/{$qa->id}/result",
                    'action_label' => 'View Score',
                    'due_date' => $qa->submitted_at?->format('M d, Y'),
                    'due_human' => $qa->submitted_at ? 'Submitted ' . $qa->submitted_at->diffForHumans() : 'Completed',
                    'is_urgent' => false,
                    'time_limit' => null,
                    'completed' => true,
                ], true);
            }
        }
        foreach ($seatworkAttempts as $sa) {
            $sw = $sa->seatwork;
            if ($sw) {
                $pushOrDismiss([
                    'id' => 'seatwork_done_' . $sa->id,
                    'source_id' => $sw->id,
                    'type' => 'Seatwork',
                    'title' => $sw->title,
                    'description' => "Submitted with score: {$sa->score}",
                    'href' => "/student/results",
                    'action_label' => 'View Result',
                    'due_date' => $sa->submitted_at?->format('M d, Y'),
                    'due_human' => $sa->submitted_at ? 'Submitted ' . $sa->submitted_at->diffForHumans() : 'Completed',
                    'is_urgent' => false,
                    'time_limit' => null,
                    'completed' => true,
                ], true);
            }
        }
        foreach ($examAttempts as $ea) {
            $ex = $ea->exam;
            if ($ex) {
                $pushOrDismiss([
                    'id' => 'exam_done_' . $ea->id,
                    'source_id' => $ex->id,
                    'type' => 'Exam',
                    'title' => $ex->title,
                    'description' => "Submitted with score: {$ea->total_score}",
                    'href' => "/student/exams/{$ea->id}/result",
                    'action_label' => 'View Score',
                    'due_date' => $ea->submitted_at?->format('M d, Y'),
                    'due_human' => $ea->submitted_at ? 'Submitted ' . $ea->submitted_at->diffForHumans() : 'Completed',
                    'is_urgent' => false,
                    'time_limit' => null,
                    'completed' => true,
                ], true);
            }
        }
        foreach ($practicalAttempts as $pa) {
            $pr = $pa->practical;
            if ($pr) {
                $pushOrDismiss([
                    'id' => 'practical_done_' . $pa->id,
                    'source_id' => $pr->id,
                    'type' => 'Practical',
                    'title' => $pr->title,
                    'description' => "Submitted with score: {$pa->total_score}",
                    'href' => "/student/practicals/{$pa->id}/result",
                    'action_label' => 'View Score',
                    'due_date' => $pa->submitted_at?->format('M d, Y'),
                    'due_human' => $pa->submitted_at ? 'Submitted ' . $pa->submitted_at->diffForHumans() : 'Completed',
                    'is_urgent' => false,
                    'time_limit' => null,
                    'completed' => true,
                ], true);
            }
        }

        $totalTasks = $pending->count() + $completed->count();
        $completionRate = $totalTasks > 0 ? round(($completed->count() / $totalTasks) * 100) : 100;
        $dueSoonCount = $pending->where('is_urgent', true)->count();

        $homeworkPassCount = count(\App\Services\RewardService::availableOfType($student, 'homework_pass'));

        return Inertia::render('Student/Todos', [
            'pendingTodos' => $pending->values(),
            'completedTodos' => $completed->values(),
            'dismissedTodos' => $dismissed->values(),
            'hasHomeworkPass' => $homeworkPassCount > 0,
            'homeworkPassCount' => $homeworkPassCount,
            'stats' => [
                'total' => $totalTasks,
                'pending' => $pending->count(),
                'completed' => $completed->count(),
                'dismissed' => $dismissed->count(),
                'due_soon' => $dueSoonCount,
                'rate' => $completionRate,
            ],
        ]);
    }

    public function toggleStudentTodo(ClassTodo $todo): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $existing = ClassTodoCompletion::where('class_todo_id', $todo->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Task marked as pending.');
        }

        ClassTodoCompletion::create([
            'class_todo_id' => $todo->id,
            'user_id' => $user->id,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Task completed! Great job.');
    }

    public function toggleMaterialRead(TeachingMaterial $material): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $existing = TeachingMaterialRead::where('teaching_material_id', $material->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            return back()->with('success', 'Material marked as unread.');
        }

        TeachingMaterialRead::create([
            'teaching_material_id' => $material->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        return back()->with('success', 'Material marked as read.');
    }

    public function dismissGradingTask(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_key' => 'required|string|max:100',
        ]);

        UserDismissedTodo::firstOrCreate([
            'user_id' => Auth::id(),
            'todo_key' => $validated['task_key'],
        ]);

        return back()->with('success', 'Action item dismissed.');
    }

    public function dismissStudentTodo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'todo_key' => 'required|string|max:100',
        ]);

        UserDismissedTodo::firstOrCreate([
            'user_id' => Auth::id(),
            'todo_key' => $validated['todo_key'],
        ]);

        return back()->with('success', 'To-Do item dismissed from your active list.');
    }

    public function restoreStudentTodo(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'todo_key' => 'required|string|max:100',
        ]);

        UserDismissedTodo::where('user_id', Auth::id())
            ->where('todo_key', $validated['todo_key'])
            ->delete();

        return back()->with('success', 'To-Do item restored to your active list.');
    }
}
