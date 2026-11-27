<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Enums\AttemptStatus;
use App\Helpers\LevelHelper;
use App\Helpers\PointsHelper;
use App\Models\Announcement;
use App\Models\ClassTodo;
use App\Models\ClassTodoCompletion;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GameProgress;
use App\Models\GameRoom;
use App\Models\GameRoomParticipant;
use App\Models\Poll;
use App\Models\PollVote;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\StudentPoint;
use App\Models\TeachingMaterial;
use App\Models\TeachingMaterialRead;
use App\Models\User;
use App\Models\UserDismissedTodo;
use App\Services\ActivityLogger;
use App\Services\AnswerMatcherService;
use App\Services\RewardService;
use App\Services\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function dashboard(): Response
    {

        $student = Auth::user();
        $this->autoSubmitClosedAttempts($student);

        // Daily login XP bonus — once per calendar day.
        $today = now()->startOfDay();
        if (! $student->last_login_at || $student->last_login_at->startOfDay()->lt($today)) {
            PointsHelper::awardFixed(
                $student->id,
                ActivityType::Login->value,
                (int) $today->format('Ymd'),
                (int) config('gamification.login_bonus'),
                'Daily login bonus',
            );
            $student->last_login_at = now();
            $student->save();
            $student->refresh();
        }

        // Repair totals left stale by older direct-award code before ranking.
        PointsHelper::syncTotal($student->id);
        $student->refresh();

        $teacherIds = $this->getAccessibleTeacherIds($student);

        $quizBase = Quiz::whereIn('teacher_id', $teacherIds)->where('is_published', true);
        $quizBase->where(function ($q) use ($student) {
            if ($student->grade_level_id) {
                $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
            } elseif ($student->grade) {
                $q->where('grade', $student->grade);
            }
        });

        $quizIds = $quizBase->pluck('id');
        $examIds = Exam::whereIn('teacher_id', $teacherIds)->where('is_published', true)
            ->has('sections')->pluck('id');
        $seatworkIds = Seatwork::whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->where(function ($q) use ($student) {
                if ($student->grade_level_id) {
                    $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
                } elseif ($student->grade) {
                    $q->where('grade', $student->grade);
                }
            })
            ->pluck('id');
        $practicalIds = Practical::whereIn('teacher_id', $teacherIds)->where('is_published', true)
            ->where(function ($q) use ($student) {
                if ($student->grade_level_id) {
                    $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
                } elseif ($student->grade) {
                    $q->where('grade', $student->grade);
                }
            })
            ->pluck('id');

        $quizTotal = $quizIds->count();
        $examTotal = $examIds->count();
        $seatworkTotal = $seatworkIds->count();
        $practicalTotal = $practicalIds->count();

        $quizAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)
            ->where('student_id', $student->id)->where('status', 'submitted')
            ->with('quiz:id,title')->get(['id', 'quiz_id', 'score', 'total_points', 'submitted_at']);
        $seatworkAttempts = SeatworkAttempt::whereIn('seatwork_id', $seatworkIds)
            ->where('student_id', $student->id)->where('status', 'submitted')
            ->where(function ($q) {
                $q->where('is_recorded', true)->orWhereNull('is_recorded');
            })
            ->with('seatwork:id,title')->get(['id', 'seatwork_id', 'score', 'total_points', 'submitted_at']);
        $examAttempts = ExamAttempt::whereIn('exam_id', $examIds)
            ->where('student_id', $student->id)->where('status', 'submitted')
            ->with('exam:id,title,max_score')->get(['id', 'exam_id', 'total_score', 'submitted_at']);
        $practicalAttempts = PracticalAttempt::whereIn('practical_id', $practicalIds)
            ->where('student_id', $student->id)->where('status', 'submitted')->whereNotNull('total_score')
            ->with('practical:id,title,max_score')->get(['id', 'practical_id', 'total_score', 'submitted_at']);

        $quizSubmitted = $quizAttempts->pluck('quiz_id')->unique()->count();
        $examSubmitted = $examAttempts->pluck('exam_id')->unique()->count();
        $seatworkSubmitted = $seatworkAttempts->pluck('seatwork_id')->unique()->count();
        $practicalSubmitted = $practicalAttempts->pluck('practical_id')->unique()->count();

        $quizAvg = static::calcAvg($quizAttempts, 'score', 'total_points');
        $seatworkAvg = static::calcAvg($seatworkAttempts, 'score', 'total_points');
        $examAvg = static::calcAvg($examAttempts, 'total_score', 'exam.max_score');
        $practicalAvg = static::calcAvg($practicalAttempts, 'total_score', 'practical.max_score');

        $allScore = 0;
        $allMax = 0;
        foreach ($quizAttempts as $a) {
            $allScore += $a->score;
            $allMax += $a->total_points;
        }
        foreach ($seatworkAttempts as $a) {
            $allScore += $a->score;
            $allMax += $a->total_points;
        }
        foreach ($examAttempts as $a) {
            $allScore += $a->total_score;
            $allMax += ($a->exam->max_score ?? 0);
        }
        foreach ($practicalAttempts as $a) {
            $allScore += $a->total_score;
            $allMax += ($a->practical->max_score ?? 0);
        }
        $overallAvg = $allMax > 0 ? round(($allScore / $allMax) * 100, 1) : 0;

        $pendingCount = max(0, ($quizTotal - $quizSubmitted) + ($examTotal - $examSubmitted)
            + ($seatworkTotal - $seatworkSubmitted) + ($practicalTotal - $practicalSubmitted));

        $recent = collect();
        foreach ($quizAttempts as $a) {
            $pct = $a->total_points > 0 ? round(($a->score / $a->total_points) * 100, 1) : 0;
            $recent->push(['type' => 'Quiz', 'title' => $a->quiz->title, 'score' => $a->score, 'total' => $a->total_points, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        foreach ($seatworkAttempts as $a) {
            $pct = $a->total_points > 0 ? round(($a->score / $a->total_points) * 100, 1) : 0;
            $recent->push(['type' => 'Seatwork', 'title' => $a->seatwork->title, 'score' => $a->score, 'total' => $a->total_points, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        foreach ($examAttempts as $a) {
            $max = $a->exam->max_score ?? 0;
            $pct = $max > 0 ? round(($a->total_score / $max) * 100, 1) : 0;
            $recent->push(['type' => 'Exam', 'title' => $a->exam->title, 'score' => $a->total_score, 'total' => $max, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        foreach ($practicalAttempts as $a) {
            $max = $a->practical->max_score ?? 0;
            $pct = $max > 0 ? round(($a->total_score / $max) * 100, 1) : 0;
            $recent->push(['type' => 'Practical', 'title' => $a->practical->title, 'score' => $a->total_score, 'total' => $max, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        $recent = $recent->sortByDesc('submitted_at')->take(5)->values();

        $board = User::where('role', 'student')
            ->where('section_id', $student->section_id)
            ->orderByDesc('total_points')
            ->get(['id', 'name', 'total_points', 'profile_photo_updated_at']);
        $myRank = max(1, $board->search(fn ($u) => $u->id === $student->id) + 1);
        $leaderboardMini = [
            'rank' => $myRank,
            'of' => $board->count(),
            'top' => $board->take(3)->values()->map(fn ($u, $i) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar' => $u->avatar,
                'total_points' => (int) $u->total_points,
                'rank' => $i + 1,
                'me' => $u->id === $student->id,
            ]),
        ];

        $submittedQuizIds = $quizAttempts->pluck('quiz_id')->unique()->all();
        $submittedSeatworkIds = $seatworkAttempts->pluck('seatwork_id')->unique()->all();
        $submittedExamIds = $examAttempts->pluck('exam_id')->unique()->all();
        $submittedPracticalIds = $practicalAttempts->pluck('practical_id')->unique()->all();

        $openQuizzes = Quiz::whereIn('id', $quizIds)
            ->whereNotIn('id', $submittedQuizIds)
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>', now());
            })
            ->get(['id', 'title', 'closes_at', 'time_limit_minutes']);

        $openSeatworks = Seatwork::whereIn('id', $seatworkIds)
            ->whereNotIn('id', $submittedSeatworkIds)
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>', now());
            })
            ->get(['id', 'title', 'closes_at', 'time_limit_minutes']);

        $openExams = Exam::whereIn('id', $examIds)
            ->whereNotIn('id', $submittedExamIds)
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>', now());
            })
            ->get(['id', 'title', 'closes_at', 'time_limit_minutes']);

        $openPracticals = Practical::whereIn('id', $practicalIds)
            ->whereNotIn('id', $submittedPracticalIds)
            ->where(function ($q) {
                $q->whereNull('closes_at')->orWhere('closes_at', '>', now());
            })
            ->get(['id', 'title', 'closes_at', 'time_limit_minutes']);

        $todos = collect();
        foreach ($openQuizzes as $q) {
            $isUrgent = $q->closes_at ? $q->closes_at->isToday() || $q->closes_at->isTomorrow() : false;
            $todos->push([
                'id' => 'quiz_'.$q->id,
                'dismiss_key' => 'quiz_'.$q->id,
                'activity_id' => $q->id,
                'title' => $q->title,
                'type' => 'Quiz',
                'href' => "/student/quizzes/{$q->id}/take",
                'closes_at' => $q->closes_at?->toIso8601String(),
                'due_text' => $q->closes_at ? $q->closes_at->diffForHumans() : 'No deadline',
                'due_date' => $q->closes_at ? $q->closes_at->format('M d, g:i A') : null,
                'time_limit' => $q->time_limit_minutes,
                'is_urgent' => $isUrgent,
            ]);
        }
        foreach ($openSeatworks as $s) {
            $isUrgent = $s->closes_at ? $s->closes_at->isToday() || $s->closes_at->isTomorrow() : false;
            $todos->push([
                'id' => 'seatwork_'.$s->id,
                'dismiss_key' => 'seatwork_'.$s->id,
                'activity_id' => $s->id,
                'title' => $s->title,
                'type' => 'Seatwork',
                'href' => "/student/seatworks/{$s->id}/take",
                'closes_at' => $s->closes_at?->toIso8601String(),
                'due_text' => $s->closes_at ? $s->closes_at->diffForHumans() : 'No deadline',
                'due_date' => $s->closes_at ? $s->closes_at->format('M d, g:i A') : null,
                'time_limit' => $s->time_limit_minutes,
                'is_urgent' => $isUrgent,
            ]);
        }
        foreach ($openExams as $e) {
            $isUrgent = $e->closes_at ? $e->closes_at->isToday() || $e->closes_at->isTomorrow() : false;
            $todos->push([
                'id' => 'exam_'.$e->id,
                'dismiss_key' => 'exam_'.$e->id,
                'activity_id' => $e->id,
                'title' => $e->title,
                'type' => 'Exam',
                'href' => "/student/exams/{$e->id}/take",
                'closes_at' => $e->closes_at?->toIso8601String(),
                'due_text' => $e->closes_at ? $e->closes_at->diffForHumans() : 'No deadline',
                'due_date' => $e->closes_at ? $e->closes_at->format('M d, g:i A') : null,
                'time_limit' => $e->time_limit_minutes,
                'is_urgent' => $isUrgent,
            ]);
        }
        foreach ($openPracticals as $p) {
            $isUrgent = $p->closes_at ? $p->closes_at->isToday() || $p->closes_at->isTomorrow() : false;
            $todos->push([
                'id' => 'practical_'.$p->id,
                'dismiss_key' => 'practical_'.$p->id,
                'activity_id' => $p->id,
                'title' => $p->title,
                'type' => 'Practical',
                'href' => "/student/practicals/{$p->id}/take",
                'closes_at' => $p->closes_at?->toIso8601String(),
                'due_text' => $p->closes_at ? $p->closes_at->diffForHumans() : 'No deadline',
                'due_date' => $p->closes_at ? $p->closes_at->format('M d, g:i A') : null,
                'time_limit' => $p->time_limit_minutes,
                'is_urgent' => $isUrgent,
            ]);
        }

        // Unread Teaching Materials
        $readMaterialIds = TeachingMaterialRead::where('user_id', $student->id)->pluck('teaching_material_id')->all();
        $unreadMaterials = TeachingMaterial::query()
            ->where(function ($q) use ($student) {
                $q->whereNull('grade_level_id');
                if ($student->grade_level_id) {
                    $q->orWhere('grade_level_id', $student->grade_level_id);
                }
            })
            ->whereNotIn('id', $readMaterialIds)
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'subject', 'file_type', 'created_at']);

        foreach ($unreadMaterials as $m) {
            $todos->push([
                'id' => 'material_'.$m->id,
                'dismiss_key' => 'material_'.$m->id,
                'activity_id' => $m->id,
                'title' => $m->title.($m->subject ? " ({$m->subject})" : ''),
                'type' => 'Material',
                'href' => route('materials.preview', ['material' => $m->id], false),
                'toggle_url' => route('student.materials.toggle-read', ['material' => $m->id], false),
                'closes_at' => null,
                'due_text' => 'Reading material',
                'due_date' => null,
                'time_limit' => null,
                'is_urgent' => false,
            ]);
        }

        // Pending Class To-Dos
        $completedTodoIds = ClassTodoCompletion::where('user_id', $student->id)->pluck('class_todo_id')->all();
        $openClassTodos = ClassTodo::query()
            ->where('is_active', true)
            ->where(function ($q) use ($student) {
                $q->whereNull('target_grade_level_id')
                    ->orWhere('target_grade_level_id', $student->grade_level_id);
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('target_section_id')
                    ->orWhere('target_section_id', $student->section_id);
            })
            ->whereNotIn('id', $completedTodoIds)
            ->latest()
            ->take(5)
            ->get();

        foreach ($openClassTodos as $ct) {
            $isUrgent = $ct->due_date ? ($ct->due_date->isPast() || $ct->due_date->isToday() || $ct->due_date->isTomorrow()) : false;
            $todos->push([
                'id' => 'task_'.$ct->id,
                'dismiss_key' => 'task_'.$ct->id,
                'activity_id' => $ct->id,
                'title' => $ct->title,
                'type' => 'Task',
                'href' => $ct->link_url ?: '/student/todos',
                'toggle_url' => route('student.todos.toggle', ['todo' => $ct->id], false),
                'closes_at' => $ct->due_date?->toIso8601String(),
                'due_text' => $ct->due_date ? $ct->due_date->diffForHumans() : 'Class task',
                'due_date' => $ct->due_date ? $ct->due_date->format('M d, g:i A') : null,
                'time_limit' => null,
                'is_urgent' => $isUrgent,
            ]);
        }

        $dismissedKeys = UserDismissedTodo::where('user_id', $student->id)->pluck('todo_key')->all();

        $todos = $todos->reject(fn ($item) => in_array($item['id'], $dismissedKeys, true))
            ->sortBy(function ($item) {
                return $item['closes_at'] ?? '9999-12-31';
            })->values();

        $announcements = Announcement::query()
            ->with(['author:id,name,role', 'gradeLevel:id,name', 'section:id,name'])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('target_role')
                    ->orWhere('target_role', 'student')
                    ->orWhere('target_role', 'all');
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('target_grade_level_id')
                    ->orWhere('target_grade_level_id', $student->grade_level_id);
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('target_section_id')
                    ->orWhere('target_section_id', $student->section_id);
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->take(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'category' => $a->category ?: 'general',
                'is_pinned' => (bool) $a->is_pinned,
                'target_grade_level_id' => $a->target_grade_level_id,
                'target_section_id' => $a->target_section_id,
                'grade_level' => $a->gradeLevel ? ['id' => $a->gradeLevel->id, 'name' => $a->gradeLevel->name] : null,
                'section' => $a->section ? ['id' => $a->section->id, 'name' => $a->section->name] : null,
                'author_name' => $a->author?->name ?? 'EduPulse Administration',
                'author_role' => $a->author?->role ?? 'Teacher',
                'created_at' => $a->created_at?->toIso8601String(),
                'date_formatted' => $a->created_at ? $a->created_at->format('M d, Y') : '',
                'time_ago' => $a->created_at ? $a->created_at->diffForHumans() : '',
            ]);

        $countdownAssessments = collect();
        foreach ($openExams as $e) {
            if ($e->closes_at && $e->closes_at->isFuture()) {
                $sec = (int) now()->diffInSeconds($e->closes_at, false);
                $countdownAssessments->push([
                    'id' => 'exam_'.$e->id,
                    'title' => $e->title,
                    'type' => 'Exam',
                    'href' => "/student/exams/{$e->id}/take",
                    'closes_at' => $e->closes_at->toIso8601String(),
                    'due_date' => $e->closes_at->format('M d, g:i A'),
                    'seconds_left' => $sec,
                    'days_left' => (int) floor($sec / 86400),
                    'hours_left' => (int) floor(($sec % 86400) / 3600),
                    'minutes_left' => (int) floor(($sec % 3600) / 60),
                    'urgency' => $sec < 86400 ? 'urgent' : ($sec < 259200 ? 'warning' : 'normal'),
                    'time_limit' => $e->time_limit_minutes,
                ]);
            }
        }
        foreach ($openQuizzes as $q) {
            if ($q->closes_at && $q->closes_at->isFuture()) {
                $sec = (int) now()->diffInSeconds($q->closes_at, false);
                $countdownAssessments->push([
                    'id' => 'quiz_'.$q->id,
                    'title' => $q->title,
                    'type' => 'Quiz',
                    'href' => "/student/quizzes/{$q->id}/take",
                    'closes_at' => $q->closes_at->toIso8601String(),
                    'due_date' => $q->closes_at->format('M d, g:i A'),
                    'seconds_left' => $sec,
                    'days_left' => (int) floor($sec / 86400),
                    'hours_left' => (int) floor(($sec % 86400) / 3600),
                    'minutes_left' => (int) floor(($sec % 3600) / 60),
                    'urgency' => $sec < 86400 ? 'urgent' : ($sec < 259200 ? 'warning' : 'normal'),
                    'time_limit' => $q->time_limit_minutes,
                ]);
            }
        }
        foreach ($openSeatworks as $s) {
            if ($s->closes_at && $s->closes_at->isFuture()) {
                $sec = (int) now()->diffInSeconds($s->closes_at, false);
                $countdownAssessments->push([
                    'id' => 'seatwork_'.$s->id,
                    'title' => $s->title,
                    'type' => 'Seatwork',
                    'href' => "/student/seatworks/{$s->id}/take",
                    'closes_at' => $s->closes_at->toIso8601String(),
                    'due_date' => $s->closes_at->format('M d, g:i A'),
                    'seconds_left' => $sec,
                    'days_left' => (int) floor($sec / 86400),
                    'hours_left' => (int) floor(($sec % 86400) / 3600),
                    'minutes_left' => (int) floor(($sec % 3600) / 60),
                    'urgency' => $sec < 86400 ? 'urgent' : ($sec < 259200 ? 'warning' : 'normal'),
                    'time_limit' => $s->time_limit_minutes,
                ]);
            }
        }
        foreach ($openPracticals as $p) {
            if ($p->closes_at && $p->closes_at->isFuture()) {
                $sec = (int) now()->diffInSeconds($p->closes_at, false);
                $countdownAssessments->push([
                    'id' => 'practical_'.$p->id,
                    'title' => $p->title,
                    'type' => 'Practical',
                    'href' => "/student/practicals/{$p->id}/take",
                    'closes_at' => $p->closes_at->toIso8601String(),
                    'due_date' => $p->closes_at->format('M d, g:i A'),
                    'seconds_left' => $sec,
                    'days_left' => (int) floor($sec / 86400),
                    'hours_left' => (int) floor(($sec % 86400) / 3600),
                    'minutes_left' => (int) floor(($sec % 3600) / 60),
                    'urgency' => $sec < 86400 ? 'urgent' : ($sec < 259200 ? 'warning' : 'normal'),
                    'time_limit' => $p->time_limit_minutes,
                ]);
            }
        }
        $assessmentsCountdown = $countdownAssessments->sortBy('seconds_left')->values()->take(5);

        // Daily streak and XP multiplier use the same source of truth as rewards.
        $streakInfo = StreakService::info($student);
        $streakCount = $streakInfo['streak'];

        // Achievement Badges
        $hasCompletedGame = GameProgress::where('user_id', $student->id)->where('completed', true)->exists();
        $totalSubmissions = $quizSubmitted + $examSubmitted + $seatworkSubmitted + $practicalSubmitted;
        $hasPerfectScore = $recent->contains(fn ($r) => $r['pct'] >= 100);
        $hasHighScore = $recent->contains(fn ($r) => $r['pct'] >= 80);
        $isPodium = $myRank <= 3 && $board->count() >= 1;

        $badges = [
            [
                'id' => 'first_flight',
                'name' => 'First Flight',
                'desc' => 'Complete and submit your first activity on EduPulse',
                'category' => 'Milestone',
                'unlocked' => $totalSubmissions >= 1,
                'reward_xp' => 25,
                'icon' => 'Rocket',
            ],
            [
                'id' => 'speed_demon',
                'name' => 'Speed Demon',
                'desc' => 'Score 80% or higher on any quiz, seatwork, or exam',
                'category' => 'Accuracy',
                'unlocked' => $hasHighScore,
                'reward_xp' => 50,
                'icon' => 'Zap',
            ],
            [
                'id' => 'flawless',
                'name' => 'Flawless Quiz',
                'desc' => 'Score a perfect 100% on any assessment or activity',
                'category' => 'Mastery',
                'unlocked' => $hasPerfectScore,
                'reward_xp' => 100,
                'icon' => 'Award',
            ],
            [
                'id' => 'podium_champion',
                'name' => 'Podium Champion',
                'desc' => 'Reach the top 3 on your section leaderboard',
                'category' => 'Ranking',
                'unlocked' => $isPodium,
                'reward_xp' => 75,
                'icon' => 'Trophy',
            ],
            [
                'id' => 'streak_master',
                'name' => 'Streak Master',
                'desc' => 'Achieve an active daily learning streak of 3+ days',
                'category' => 'Dedication',
                'unlocked' => $streakCount >= 3,
                'reward_xp' => 50,
                'icon' => 'Flame',
            ],
            [
                'id' => 'arcade_wizard',
                'name' => 'Arcade Wizard',
                'desc' => 'Complete any educational game session',
                'category' => 'Arcade',
                'unlocked' => $hasCompletedGame,
                'reward_xp' => 40,
                'icon' => 'Gamepad2',
            ],
        ];

        // Active Classroom Poll
        $activePollModel = Poll::where('is_active', true)
            ->where(function ($q) use ($student) {
                $q->whereNull('target_grade_level_id')
                    ->orWhere('target_grade_level_id', $student->grade_level_id);
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('target_section_id')
                    ->orWhere('target_section_id', $student->section_id);
            })
            ->with('votes')
            ->latest()
            ->first();

        $activePoll = null;
        if ($activePollModel) {
            $totalVotes = $activePollModel->votes->count();
            $counts = $activePollModel->votes->groupBy('option_index')->map->count();
            $userVote = $activePollModel->votes->where('user_id', $student->id)->first()?->option_index;

            $optionsData = [];
            foreach ($activePollModel->options as $idx => $text) {
                $vCount = $counts[$idx] ?? 0;
                $pct = $totalVotes > 0 ? round(($vCount / $totalVotes) * 100) : 0;
                $optionsData[] = [
                    'index' => $idx,
                    'text' => $text,
                    'votes' => $vCount,
                    'pct' => $pct,
                    'is_selected' => $userVote === $idx,
                ];
            }

            $activePoll = [
                'id' => $activePollModel->id,
                'question' => $activePollModel->question,
                'total_votes' => $totalVotes,
                'user_voted' => $userVote !== null,
                'user_vote_index' => $userVote,
                'options' => $optionsData,
            ];
        }

        $activeLiveRoomsQuery = GameRoom::with([
            'game:id,title,type,subject,grade',
            'teacher:id,name',
        ])
            ->withCount('participants')
            ->whereIn('status', ['waiting', 'active'])
            ->accessibleToStudent($student);

        if ($teacherIds->isNotEmpty()) {
            $activeLiveRoomsQuery->whereIn('teacher_id', $teacherIds);
        }

        $activeLiveRooms = $activeLiveRoomsQuery
            ->latest()
            ->get(['id', 'game_id', 'teacher_id', 'pin', 'status', 'created_at']);

        return Inertia::render('Student/Dashboard', [
            'overallAvg' => $overallAvg,
            'typeAverages' => ['Quiz' => $quizAvg, 'Exam' => $examAvg, 'Seatwork' => $seatworkAvg, 'Practical' => $practicalAvg],
            'pendingCount' => $pendingCount,
            'recentResults' => $recent,
            'leaderboardMini' => $leaderboardMini,
            'announcements' => $announcements,
            'todos' => $todos,
            'assessmentsCountdown' => $assessmentsCountdown,
            'streakInfo' => $streakInfo,
            'badges' => $badges,
            'activePoll' => $activePoll,
            'activeLiveRooms' => $activeLiveRooms,
            'stats' => [
                'quizzes' => ['total' => $quizTotal, 'submitted' => $quizSubmitted],
                'exams' => ['total' => $examTotal, 'submitted' => $examSubmitted],
                'seatworks' => ['total' => $seatworkTotal, 'submitted' => $seatworkSubmitted],
                'practicals' => ['total' => $practicalTotal, 'submitted' => $practicalSubmitted],
            ],
        ]);
    }

    public function freezeStreak(Request $request): RedirectResponse
    {
        $student = Auth::user();

        if ($student->streak_freeze_active) {
            $student->streak_freeze_active = false;
            $student->streak_freeze_count = min(5, ((int) ($student->streak_freeze_count ?? 0)) + 1);
            $student->save();

            return back()->with('success', 'Streak freeze shield deactivated. Shield returned to inventory.');
        }

        if (((int) ($student->streak_freeze_count ?? 0)) <= 0) {
            return back()->with('error', 'No streak freeze shields available.');
        }

        $student->streak_freeze_active = true;
        $student->streak_freeze_count = max(0, ((int) ($student->streak_freeze_count ?? 0)) - 1);
        $student->save();

        return back()->with('success', 'Streak freeze shield activated! Your streak is protected from reset today.');
    }

    public function votePoll(Request $request, Poll $poll): RedirectResponse
    {
        $data = $request->validate([
            'option_index' => ['required', 'integer', 'min:0'],
        ]);

        $student = Auth::user();
        $isAccessible = $poll->is_active
            && (! $poll->target_grade_level_id || $poll->target_grade_level_id === $student->grade_level_id)
            && (! $poll->target_section_id || $poll->target_section_id === $student->section_id);

        if (! $isAccessible) {
            abort(403, 'This poll is not available to your account.');
        }

        if (! isset($poll->options[$data['option_index']])) {
            return back()->with('error', 'Invalid poll option.');
        }

        PollVote::updateOrCreate(
            ['poll_id' => $poll->id, 'user_id' => Auth::id()],
            ['option_index' => $data['option_index']]
        );

        return back()->with('success', 'Your vote has been recorded!');
    }

    private static function calcAvg($attempts, string $scoreField, ?string $maxField): float
    {
        $totalScore = 0;
        $totalMax = 0;
        foreach ($attempts as $a) {
            $score = (float) ($a->{$scoreField} ?? 0);
            $max = $maxField ? (float) (str_contains($maxField, '.') ? data_get($a, $maxField) : ($a->{$maxField} ?? 0)) : 0;
            if ($max > 0) {
                $totalScore += $score;
                $totalMax += $max;
            }
        }

        return $totalMax > 0 ? round(($totalScore / $totalMax) * 100, 1) : 0;
    }

    public function take(Quiz $quiz): Response|RedirectResponse
    {
        $student = Auth::user();
        $this->authorizeAccess($quiz, $student);

        abort_if($quiz->isClosed(), 403, 'This quiz has closed.');

        $existing = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->latest('id')
            ->first();

        if ($existing && $existing->status === AttemptStatus::Submitted->value) {
            return redirect()->route('student.quizzes.result', ['attempt' => $existing->id])
                ->with('info', 'You have already submitted this quiz.');
        }

        if (! $existing) {
            $existing = QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'student_id' => $student->id,
                'status' => AttemptStatus::InProgress->value,
                'started_at' => now(),
                'total_points' => $quiz->totalPoints(),
            ]);
        }

        $quiz->load(['questions' => function ($query) {
            $query->inRandomOrder();
        }, 'questions.options' => function ($query) {
            $query->inRandomOrder();
        }, 'questions.matchingPairs']);

        // Never leak the answer key to the client.
        $quiz->questions->each(fn ($q) => $q->options->each->makeHidden('is_correct'));

        $timeWarps = RewardService::availableOfType($student, 'time_warp');
        $compasses = RewardService::availableOfType($student, 'compass');
        $shieldProtect = RewardService::armedShieldProtection($student);

        return Inertia::render('Student/Quiz/Take', [
            'quiz' => $quiz,
            'startedAt' => $existing->started_at,
            'availableRewards' => [
                'time_warp' => count($timeWarps) > 0,
                'time_warp_count' => count($timeWarps),
                'time_warp_seconds' => $timeWarps ? (int) ($timeWarps[0]['seconds'] ?? 30) : null,
                'brain_shield' => $shieldProtect > 0,
                'shield_protect' => $shieldProtect,
                'star_compass' => count($compasses) > 0,
                'compass_count' => count($compasses),
                'compass_eliminate' => $compasses ? (int) ($compasses[0]['eliminate'] ?? 2) : null,
            ],
        ]);
    }

    public function submit(Request $request, Quiz $quiz): RedirectResponse
    {
        $student = Auth::user();
        abort_if($quiz->isClosed(), 403, 'This quiz has closed.');
        $this->authorizeAccess($quiz, $student);

        $data = $request->validate([
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable'],
        ]);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->latest('id')
            ->firstOrFail();

        if ($attempt->status === AttemptStatus::Submitted->value) {
            return redirect()->route('student.quizzes.result', ['attempt' => $attempt->id], 303)->with('info', 'This quiz was already submitted.');
        }

        $quiz->load('questions.options', 'questions.matchingPairs');

        $score = 0;
        $forgiven = [];
        $shieldProtect = RewardService::armedShieldProtection($student);

        DB::transaction(function () use ($quiz, $data, $attempt, &$score, &$forgiven, $shieldProtect) {
            $answers = $data['answers'] ?? [];
            foreach ($quiz->questions as $question) {
                $userAnswer = $answers[$question->id] ?? null;

                $isCorrect = false;
                $pointsAwarded = 0;
                $answerText = null;
                $selectedOptionId = null;
                $matchingAnswers = null;
                $feedback = null;

                if ($question->type === 'identification') {
                    $answerText = is_string($userAnswer) ? trim($userAnswer) : (is_numeric($userAnswer) ? (string) $userAnswer : '');
                    $evaluation = AnswerMatcherService::evaluateIdentificationWithAi($question->options, $answerText, $question->question_text);
                    $isCorrect = (bool) ($evaluation['is_correct'] ?? false);
                    $pointsAwarded = $isCorrect ? $question->points : 0;
                    $feedback = $evaluation['feedback'] ?? ($isCorrect ? 'Correct answer.' : 'Incorrect answer.');
                } elseif ($question->type === 'essay') {
                    $answerText = is_string($userAnswer) ? trim($userAnswer) : '';
                    $grade = EssayGraderService::grade($question->question_text, $question->criteria, (int) $question->points, $answerText);
                    $isCorrect = (bool) ($grade['is_correct'] ?? false);
                    $pointsAwarded = (int) ($grade['score'] ?? 0);
                    $feedback = $grade['feedback'] ?? null;
                } elseif ($question->type === 'enumeration') {
                    $answerText = is_array($userAnswer)
                        ? implode("\n", array_filter($userAnswer, fn ($item) => trim((string) $item) !== ''))
                        : (string) ($userAnswer ?? '');
                    $eval = AnswerMatcherService::evaluateEnumerationWithAi($question->options, $answerText, $question->question_text, (int) $question->points);
                    $isCorrect = (bool) ($eval['is_correct'] ?? false);
                    $pointsAwarded = (int) ($eval['points_awarded'] ?? 0);
                    $feedback = $eval['feedback'] ?? null;
                } elseif ($question->type === 'matching') {
                    $matchingAnswers = is_array($userAnswer) ? $userAnswer : [];
                    $pairs = $question->matchingPairs;
                    $correctPairs = 0;
                    foreach ($pairs as $p) {
                        if (isset($matchingAnswers[$p->id]) && trim((string) $matchingAnswers[$p->id]) === trim((string) $p->right_text)) {
                            $correctPairs++;
                        }
                    }
                    $totalPairs = max(1, $pairs->count());
                    $isCorrect = ($correctPairs === $totalPairs);
                    $pointsAwarded = (int) round(($correctPairs / $totalPairs) * $question->points);
                    $feedback = "{$correctPairs}/{$totalPairs} pairs correctly matched.";
                } else {
                    $selectedOptionId = is_numeric($userAnswer) ? (int) $userAnswer : null;
                    if ($selectedOptionId === null && is_string($userAnswer) && trim($userAnswer) !== '') {
                        $trimmed = trim($userAnswer);
                        if (preg_match('/^[A-Za-z]$/', $trimmed)) {
                            $letterIndex = ord(strtoupper($trimmed)) - ord('A');
                            $sortedOptions = $question->options->sortBy('order')->values();
                            if (isset($sortedOptions[$letterIndex])) {
                                $selectedOptionId = $sortedOptions[$letterIndex]->id;
                            }
                        }
                        if ($selectedOptionId === null) {
                            $matchedOption = $question->options->first(function ($opt) use ($trimmed) {
                                return AnswerMatcherService::isNearMatch($opt->option_text, $trimmed);
                            });
                            if ($matchedOption) {
                                $selectedOptionId = $matchedOption->id;
                            }
                        }
                    }
                    $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
                    $isCorrect = (bool) ($selectedOption?->is_correct);
                    $pointsAwarded = $isCorrect ? $question->points : 0;
                    $feedback = $isCorrect ? 'Correct option selected.' : 'Incorrect option.';
                }

                if (! $isCorrect && count($forgiven) < $shieldProtect && $question->points > 0) {
                    $isCorrect = true;
                    $pointsAwarded = $question->points;
                    $forgiven[] = $question->id;
                }

                $score += $pointsAwarded;

                DB::table('quiz_answers')->updateOrInsert(
                    ['quiz_attempt_id' => $attempt->id, 'question_id' => $question->id],
                    [
                        'quiz_option_id' => $selectedOptionId,
                        'answer_text' => $answerText !== '' ? $answerText : null,
                        'matching_answers' => $matchingAnswers ? json_encode($matchingAnswers) : null,
                        'is_correct' => $isCorrect,
                        'feedback' => $feedback,
                        'points_awarded' => $pointsAwarded,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            DB::table('quiz_attempts')->where('id', $attempt->id)->update([
                'score' => $score,
                'total_points' => $quiz->totalPoints(),
                'status' => AttemptStatus::Submitted->value,
                'submitted_at' => now(),
            ]);
        });

        $shieldApplied = count($forgiven) > 0;
        if ($shieldApplied) {
            RewardService::consumeShields($student, count($forgiven), [
                'quiz_id' => $quiz->id,
                'question_ids' => $forgiven,
            ]);
        }

        PointsHelper::award($student->id, 'Quiz', $quiz->id, $score, $quiz->totalPoints());

        ActivityLogger::log(
            'quiz_submitted',
            "Student {$student->name} submitted quiz '{$quiz->title}' (Score: {$score}/{$quiz->totalPoints()})",
            $quiz,
            $student,
            ['score' => $score, 'total' => $quiz->totalPoints(), 'shield_applied' => $shieldApplied, 'shield_forgiven' => count($forgiven)]
        );

        $forgivenCount = count($forgiven);
        $successMsg = $shieldApplied
            ? "Quiz submitted! 🛡️ Your shield forgave {$forgivenCount} wrong answer" . ($forgivenCount === 1 ? '' : 's') . ' (full points awarded)!'
            : 'Quiz submitted successfully.';

        return redirect()->route('student.quizzes.result', ['attempt' => $attempt->id], 303)
            ->with('success', $successMsg);
    }

    public function result(QuizAttempt $attempt): Response
    {
        $student = Auth::user();

        if ($attempt->student_id !== $student->id) {
            abort(403);
        }

        $attempt->load(['quiz.questions' => function ($q) {
            $q->orderBy('order');
        }, 'quiz.questions.options', 'quiz.questions.matchingPairs', 'answers.selectedOption']);

        return Inertia::render('Student/Quiz/Result', [
            'attempt' => $attempt,
        ]);
    }

    public function quizzes(): Response
    {
        $student = Auth::user();
        $this->autoSubmitClosedAttempts($student);
        $teacherIds = $this->getAccessibleTeacherIds($student);

        $quizzes = Quiz::query()
            ->whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->where(function ($q) use ($student) {
                $q->where(fn ($q2) => $q2->whereNull('closes_at')->orWhere('closes_at', '>', now()))
                    ->orWhereHas('attempts', fn ($a) => $a->where('student_id', $student->id)
                        ->where('status', AttemptStatus::Submitted->value));
            });

        $quizzes->where(function ($q) use ($student) {
            if ($student->grade_level_id) {
                $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
            } elseif ($student->grade) {
                $q->where('grade', $student->grade);
            }
        });

        $quizzes = $quizzes->withCount('questions')
            ->with(['attempts' => function ($query) use ($student) {
                $query->where('student_id', $student->id);
            }])
            ->latest()
            ->get()
            ->map(function ($quiz) {
                $attempt = $quiz->attempts->first();

                return [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'description' => $quiz->description,
                    'questions_count' => $quiz->questions_count,
                    'time_limit_minutes' => $quiz->time_limit_minutes,
                    'status' => $attempt ? $attempt->status : AttemptStatus::NotStarted->value,
                    'score' => $attempt?->score,
                    'total_points' => $attempt?->total_points,
                    'attempt_id' => $attempt?->id,
                ];
            });

        return Inertia::render('Student/Quizzes/Index', ['quizzes' => $quizzes]);
    }

    public function seatworks(): Response
    {
        $student = Auth::user();
        $this->autoSubmitClosedAttempts($student);
        $teacherIds = $this->getAccessibleTeacherIds($student);

        $seatworks = Seatwork::whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->where(function ($q) use ($student) {
                if ($student->grade_level_id) {
                    $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
                } elseif ($student->grade) {
                    $q->where('grade', $student->grade);
                }
            })
            ->with(['attempts' => function ($query) use ($student) {
                $query->where('student_id', $student->id)->orderBy('attempt_number', 'asc');
            }])
            ->withCount('questions')
            ->latest()
            ->get()
            ->map(function ($seatwork) {
                $submittedAttempts = $seatwork->attempts->where('status', AttemptStatus::Submitted->value);
                $submittedCount = $submittedAttempts->count();
                $inProgressAttempt = $seatwork->attempts->firstWhere('status', AttemptStatus::InProgress->value);
                $recordedAttempt = $seatwork->attempts->firstWhere('is_recorded', true)
                    ?: $submittedAttempts->sortByDesc('attempt_number')->first();
                $resultAttempt = $recordedAttempt ?: $submittedAttempts->last();

                $isClosed = $seatwork->isClosed();
                $maxAttempts = $seatwork->max_attempts ?? 1;
                $canRetake = ! $isClosed && ($maxAttempts === 0 || $submittedCount < $maxAttempts);

                $status = $inProgressAttempt
                    ? AttemptStatus::InProgress->value
                    : ($submittedCount > 0
                        ? AttemptStatus::Submitted->value
                        : ($isClosed ? AttemptStatus::Closed->value : AttemptStatus::NotStarted->value));

                return [
                    'id' => $seatwork->id,
                    'title' => $seatwork->title,
                    'questions_count' => $seatwork->questions_count,
                    'time_limit_minutes' => $seatwork->time_limit_minutes,
                    'max_attempts' => $maxAttempts,
                    'grading_policy' => $seatwork->grading_policy ?? 'first',
                    'submitted_attempts_count' => $submittedCount,
                    'can_retake' => $canRetake,
                    'is_closed' => $isClosed,
                    'status' => $status,
                    'score' => $recordedAttempt?->score,
                    'total_points' => $recordedAttempt?->total_points,
                    'attempt_id' => $resultAttempt?->id ?: $inProgressAttempt?->id,
                    'result_attempt_id' => $resultAttempt?->id,
                ];
            });

        return Inertia::render('Student/Seatworks/Index', ['seatworks' => $seatworks]);
    }

    public function practicals(): Response
    {
        $student = Auth::user();
        $this->autoSubmitClosedAttempts($student);
        $teacherIds = $this->getAccessibleTeacherIds($student);

        $practicals = Practical::whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->where(function ($q) use ($student) {
                if ($student->grade_level_id) {
                    $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
                } elseif ($student->grade) {
                    $q->where('grade', $student->grade);
                }
            })
            ->with(['attempts' => function ($query) use ($student) {
                $query->where('student_id', $student->id);
            }])
            ->withCount('criteria')
            ->latest()
            ->get()
            ->map(function ($practical) {
                $submittedAttempts = $practical->attempts->where('status', AttemptStatus::Submitted->value)->count();
                $inProgressAttempt = $practical->attempts->where('status', AttemptStatus::InProgress->value)->first();
                $bestAttempt = $practical->attempts->where('status', AttemptStatus::Submitted->value)->sortByDesc('total_score')->first();
                $displayAttempt = $bestAttempt ?? $inProgressAttempt;
                $closed = $practical->isClosed();
                $status = $displayAttempt ? $displayAttempt->status : AttemptStatus::NotStarted->value;
                if ($closed && $status !== AttemptStatus::Submitted->value) {
                    $status = AttemptStatus::Closed->value;
                }

                return [
                    'id' => $practical->id,
                    'title' => $practical->title,
                    'criteria_count' => $practical->criteria_count,
                    'time_limit_minutes' => $practical->time_limit_minutes,
                    'max_score' => $practical->max_score,
                    'max_attempts' => $practical->max_attempts,
                    'attempts_count' => $submittedAttempts,
                    'closed' => $closed,
                    'status' => $status,
                    'score' => $bestAttempt?->total_score,
                    'attempt_id' => $bestAttempt?->id ?? $inProgressAttempt?->id,
                    'closed_at' => $bestAttempt?->closed_at,
                ];
            });

        $homeworkPassCount = count(\App\Services\RewardService::availableOfType($student, 'homework_pass'));

        return Inertia::render('Student/Practicals/Index', [
            'practicals' => $practicals,
            'hasHomeworkPass' => $homeworkPassCount > 0,
            'homeworkPassCount' => $homeworkPassCount,
        ]);
    }

    public function exams(): Response
    {
        $student = Auth::user();
        $this->autoSubmitClosedAttempts($student);
        $teacherIds = $this->getAccessibleTeacherIds($student);

        $exams = Exam::whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()))
            ->with(['attempts' => function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->orderByRaw(
                        'CASE WHEN status = ? THEN 0 ELSE 1 END',
                        [AttemptStatus::InProgress->value],
                    )
                    ->latest('id');
            }])
            ->withCount('sections')
            ->latest()
            ->get()
            ->map(function ($exam) {
                $attempt = $exam->attempts->first();

                return [
                    'id' => $exam->id,
                    'title' => $exam->title,
                    'instructions' => $exam->instructions,
                    'sections_count' => $exam->sections_count,
                    'time_limit_minutes' => $exam->time_limit_minutes,
                    'max_score' => $exam->max_score,
                    'status' => $attempt ? $attempt->status : AttemptStatus::NotStarted->value,
                    'score' => $attempt?->total_score,
                    'attempt_id' => $attempt?->id,
                ];
            })
            ->filter(fn ($e) => $e['sections_count'] > 0)
            ->values();

        return Inertia::render('Student/Exams/Index', ['exams' => $exams]);
    }

    public function results(): Response
    {
        $student = Auth::user();
        $this->autoSubmitClosedAttempts($student);

        $quizAttempts = QuizAttempt::with('quiz:id,title,grade')
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::Submitted->value)
            ->latest('submitted_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'type' => 'Quiz',
                'activity_id' => $a->quiz_id,
                'title' => $a->quiz->title,
                'score' => $a->score,
                'total' => $a->total_points,
                'percentage' => $a->total_points ? round(($a->score / $a->total_points) * 100) : 0,
                'submitted_at' => $a->submitted_at,
                'resultUrl' => route('student.quizzes.result', $a->id),
            ]);

        $seatworkAttempts = SeatworkAttempt::with('seatwork:id,title')
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::Submitted->value)
            ->latest('submitted_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'type' => 'Seatwork',
                'activity_id' => $a->seatwork_id,
                'title' => $a->seatwork->title,
                'score' => $a->score,
                'total' => $a->total_points,
                'percentage' => $a->total_points ? round(($a->score / $a->total_points) * 100) : 0,
                'submitted_at' => $a->submitted_at,
                'attempt_number' => $a->attempt_number,
                'is_recorded' => (bool) $a->is_recorded,
                'resultUrl' => route('student.seatworks.result', $a->id),
            ]);

        $practicalAttempts = PracticalAttempt::with('practical.criteria')
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::Submitted->value)
            ->latest('submitted_at')
            ->get()
            ->groupBy('practical_id')
            ->map(fn ($group) => $group->sortByDesc('total_score')->first())
            ->map(fn ($a) => [
                'id' => $a->id,
                'type' => 'Practical',
                'activity_id' => $a->practical_id,
                'title' => $a->practical->title,
                'score' => $a->total_score,
                'total' => $a->practical->criteria->sum('max_points'),
                'percentage' => $a->practical->criteria->sum('max_points') ? round(($a->total_score / $a->practical->criteria->sum('max_points')) * 100) : 0,
                'submitted_at' => $a->submitted_at,
                'resultUrl' => route('student.practicals.result', $a->id),
            ]);

        $examAttempts = ExamAttempt::with('exam:id,title,max_score')
            ->where('student_id', $student->id)
            ->where('status', AttemptStatus::Submitted->value)
            ->latest('submitted_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'type' => 'Exam',
                'activity_id' => $a->exam_id,
                'title' => $a->exam->title,
                'score' => $a->total_score,
                'total' => $a->exam->max_score,
                'percentage' => $a->exam->max_score ? round(($a->total_score / $a->exam->max_score) * 100) : 0,
                'submitted_at' => $a->submitted_at,
                'resultUrl' => route('student.exams.result', $a->id),
            ]);

        $gameAttempts = GameProgress::with(['game.cards'])
            ->where('user_id', $student->id)
            ->where('completed', true)
            ->latest('updated_at')
            ->get()
            ->map(function ($gp) use ($student) {
                $total = $gp->game?->cards?->count() ?: max(1, $gp->done_count);
                $correctCount = DB::table('game_correct_answers')
                    ->where('user_id', $student->id)
                    ->where('game_id', $gp->game_id)
                    ->count();
                $score = max((int) $gp->score, $correctCount);

                return [
                    'id' => $gp->id,
                    'type' => 'Game',
                    'activity_id' => $gp->game_id,
                    'title' => $gp->game?->title ?? 'Learning Game',
                    'score' => $score,
                    'total' => $total,
                    'percentage' => $total ? round(($score / $total) * 100) : 0,
                    'submitted_at' => $gp->updated_at,
                    'resultUrl' => route('student.games'),
                ];
            });

        $liveParticipants = GameRoomParticipant::with([
            'gameRoom.game:id,title,subject,grade',
            'gameRoom.teacher:id,name',
            'gameRoom.participants:id,game_room_id,user_id,score,streak',
        ])
            ->where('user_id', $student->id)
            ->whereHas('gameRoom', fn ($q) => $q->where('status', 'finished'))
            ->latest('updated_at')
            ->get();

        $liveMatchAttempts = $liveParticipants->map(function ($grp) {
            $room = $grp->gameRoom;
            $sorted = $room->participants->sortByDesc('score')->sortByDesc('streak')->values();
            $rankIndex = $sorted->search(fn ($p) => (int) $p->user_id === (int) $grp->user_id);
            $rankNumber = $rankIndex !== false ? ($rankIndex + 1) : 1;
            $totalParticipants = $room->participants->count();

            return [
                'id' => $grp->id,
                'type' => 'Game',
                'is_live' => true,
                'activity_id' => $room->id,
                'room_pin' => $room->pin,
                'title' => ($room->game?->title ?? 'Live Arena Match').' · PIN '.$room->pin,
                'score' => (int) $grp->score,
                'total' => $totalParticipants,
                'percentage' => $totalParticipants > 0 ? round((($totalParticipants - $rankNumber + 1) / $totalParticipants) * 100) : 100,
                'rank' => $rankNumber,
                'teacher_name' => $room->teacher?->name,
                'submitted_at' => $grp->updated_at ?? $room->updated_at,
                'resultUrl' => route('student.games.rooms.play', $room->pin),
            ];
        });

        $all = collect($quizAttempts)
            ->concat($seatworkAttempts)
            ->concat($practicalAttempts)
            ->concat($examAttempts)
            ->concat($gameAttempts)
            ->concat($liveMatchAttempts)
            ->map(fn ($row) => array_merge($row, [
                'status' => AttemptStatus::Submitted->value,
                'takeUrl' => null,
            ]))
            ->values();

        $submittedKeys = $all->mapWithKeys(function ($row) {
            $activityId = $row['activity_id'] ?? null;

            return $activityId !== null ? [$row['type'].':'.$activityId => true] : [];
        });

        $pending = $this->pendingActivities($student, $submittedKeys);

        $all = $all->concat($pending)
            ->sortByDesc(fn ($row) => $row['submitted_at'] ?? '')
            ->values();

        return Inertia::render('Student/Results', [
            'activities' => $all,
        ]);
    }

    /**
     * Finalize in-progress attempts on activities that have already closed,
     * across all four types. No answers are stored server-side until the
     * student presses submit, so a closed attempt is submitted with a zero
     * score instead of lingering as pending forever (take/submit both
     * reject closed activities, so these could otherwise never resolve).
     */
    private function autoSubmitClosedAttempts($student): void
    {
        $submitted = AttemptStatus::Submitted->value;
        $now = now();

        $closedQuizIds = Quiz::where('is_published', true)
            ->whereNotNull('closes_at')->where('closes_at', '<=', $now)->pluck('id');
        foreach (QuizAttempt::where('student_id', $student->id)->whereIn('quiz_id', $closedQuizIds)->where('status', '!=', $submitted)->with('quiz:id')->get() as $attempt) {
            $total = $attempt->quiz?->totalPoints() ?? 0;
            $attempt->update(['score' => 0, 'total_points' => $total, 'status' => $submitted, 'submitted_at' => $now]);
            PointsHelper::award($student->id, ActivityType::Quiz->value, $attempt->quiz_id, 0, $total);
        }

        $closedSeatworkIds = Seatwork::where('is_published', true)
            ->whereNotNull('closes_at')->where('closes_at', '<=', $now)->pluck('id');
        foreach (SeatworkAttempt::where('student_id', $student->id)->whereIn('seatwork_id', $closedSeatworkIds)->where('status', '!=', $submitted)->with('seatwork:id')->get() as $attempt) {
            $total = $attempt->seatwork?->totalPoints() ?? 0;
            $attempt->update(['score' => 0, 'total_points' => $total, 'status' => $submitted, 'submitted_at' => $now]);
            PointsHelper::award($student->id, ActivityType::Seatwork->value, $attempt->seatwork_id, 0, $total);
        }

        $closedExamIds = Exam::where('is_published', true)
            ->whereNotNull('closes_at')->where('closes_at', '<=', $now)->pluck('id');
        ExamAttempt::where('student_id', $student->id)->whereIn('exam_id', $closedExamIds)->where('status', '!=', $submitted)
            ->update(['total_score' => 0, 'status' => $submitted, 'submitted_at' => $now]);

        $closedPracticalIds = Practical::where('is_published', true)
            ->whereNotNull('closes_at')->where('closes_at', '<=', $now)->pluck('id');
        PracticalAttempt::where('student_id', $student->id)->whereIn('practical_id', $closedPracticalIds)->where('status', '!=', $submitted)
            ->update(['status' => $submitted, 'submitted_at' => $now]);
    }

    /**
     * Use or toggle an unlocked level reward.
     */
    public function useReward(Request $request, int $level): RedirectResponse|JsonResponse
    {
        $student = Auth::user();

        $maxLevel = count(LevelHelper::rawLevelRewards());
        abort_unless($level >= 1 && $level <= max(1, $maxLevel), 404);
        abort_unless(LevelHelper::level((int) $student->total_points) >= $level, 403, 'Reward not unlocked yet.');

        $def = RewardService::definition($level);
        $type = $def['type'] ?? null;

        if ($type !== 'badge' && RewardService::isUsed($student, $level)) {
            return back()->with('info', 'That reward has already been used.');
        }

        $res = match ($type) {
            'booster' => RewardService::toggleBooster($student, $level),
            'shield' => RewardService::toggleShield($student, $level),
            'badge' => RewardService::equipBadge($student, (string) ($def['badge_key'] ?? '')),
            'xp_grant' => RewardService::claimXpGrant($student, $level),
            'streak_freeze' => RewardService::claimStreakFreeze($student, $level),
            default => null,
        };

        if ($res !== null) {
            if ($request->wantsJson()) {
                return response()->json($res, $res['ok'] ? 200 : 422);
            }

            return back()->with($res['ok'] ? 'success' : 'error', $res['message']);
        }

        $info = match ($type) {
            'homework_pass' => "{$def['name']} is ready! Click \"Use Pass\" on any pending quiz, seatwork, performance task, or task.",
            'time_warp' => "{$def['name']} is ready! Click the hourglass button during any timed quiz.",
            'compass' => "{$def['name']} is ready! Click the compass button on a multiple-choice quiz question.",
        };

        if ($type === 'homework_pass') {
            return redirect()->route('student.todos.index')->with('info', $info);
        }

        return back()->with('info', $info);
    }

    public function useHomeworkPass(Request $request): RedirectResponse|JsonResponse
    {
        $student = Auth::user();

        $type = (string) $request->input('type', '');
        $id = (int) $request->input('id', 0);

        // Legacy payloads
        if (! $type && $request->filled('seatwork_id')) {
            $type = 'seatwork';
            $id = (int) $request->input('seatwork_id');
        } elseif (! $type && $request->filled('quiz_id')) {
            $type = 'quiz';
            $id = (int) $request->input('quiz_id');
        } elseif (! $type && $request->filled('todo_id')) {
            $type = 'task';
            $id = (int) $request->input('todo_id');
        } elseif (! $type && ($request->filled('practical_id') || $request->filled('performance_task_id'))) {
            $type = 'practical';
            $id = (int) ($request->input('practical_id') ?: $request->input('performance_task_id'));
        }

        $res = RewardService::applyHomeworkPass($student, $type, $id);

        if ($request->wantsJson()) {
            return response()->json($res, $res['ok'] ? 200 : 422);
        }

        if ($res['ok'] && ! empty($res['redirect_url'])) {
            $prev = (string) $request->header('referer');
            if (str_contains($prev, '/take')) {
                return redirect($res['redirect_url'])->with('success', $res['message']);
            }
        }

        return back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function useTimeWarp(Request $request): JsonResponse|RedirectResponse
    {
        $student = Auth::user();
        $quizId = $request->input('quiz_id') ? (int) $request->input('quiz_id') : null;

        $res = RewardService::applyTimeWarp($student, $quizId);

        if ($request->wantsJson()) {
            return response()->json($res, $res['ok'] ? 200 : 422);
        }

        return back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    public function useStarCompass(Request $request): JsonResponse
    {
        $student = Auth::user();
        $questionId = (int) $request->input('question_id');
        $options = (array) $request->input('options', []);

        $res = RewardService::applyStarCompass($student, $questionId, $options);

        return response()->json($res, $res['ok'] ? 200 : 422);
    }

    public function equipBadge(Request $request): RedirectResponse|JsonResponse
    {
        $student = Auth::user();
        $badge = (string) $request->input('badge');

        $res = RewardService::equipBadge($student, $badge);

        if ($request->wantsJson()) {
            return response()->json($res, $res['ok'] ? 200 : 422);
        }

        return back()->with($res['ok'] ? 'success' : 'error', $res['message']);
    }

    /**
     * Assigned activities the student hasn't submitted yet, across all
     * four types (same visibility rules as the index pages).
     */
    private function pendingActivities($student, $submittedKeys)
    {
        $teacherIds = $this->getAccessibleTeacherIds($student);
        $rows = collect();

        $applyScopes = function ($query, $withClosesAt = true) use ($student) {
            if ($withClosesAt) {
                $query->where(fn ($q) => $q->whereNull('closes_at')->orWhere('closes_at', '>', now()));
            }
            $query->where(function ($q) use ($student) {
                if ($student->grade_level_id) {
                    $q->whereHas('gradeLevels', fn ($sq) => $sq->where('grade_level_id', $student->grade_level_id));
                } elseif ($student->grade) {
                    $q->where('grade', $student->grade);
                }
            });
        };

        $attemptsFor = fn ($query) => $query->where('student_id', $student->id);

        $quizzes = Quiz::query()
            ->whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->tap(fn ($q) => $applyScopes($q))
            ->with(['attempts' => $attemptsFor])
            ->latest()
            ->get();
        foreach ($quizzes as $quiz) {
            if (isset($submittedKeys['Quiz:'.$quiz->id])) {
                continue;
            }
            $attempt = $quiz->attempts->first();
            $rows->push([
                'id' => 'quiz-'.$quiz->id,
                'type' => 'Quiz',
                'activity_id' => $quiz->id,
                'title' => $quiz->title,
                'status' => $attempt ? $attempt->status : AttemptStatus::NotStarted->value,
                'score' => null,
                'total' => null,
                'percentage' => null,
                'submitted_at' => null,
                'resultUrl' => null,
                'takeUrl' => route('student.quizzes.take', $quiz->id),
            ]);
        }

        $seatworks = Seatwork::whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->tap(fn ($q) => $applyScopes($q))
            ->with(['attempts' => $attemptsFor])
            ->latest()
            ->get();
        foreach ($seatworks as $seatwork) {
            if (isset($submittedKeys['Seatwork:'.$seatwork->id])) {
                continue;
            }
            $attempt = $seatwork->attempts->first();
            $rows->push([
                'id' => 'seatwork-'.$seatwork->id,
                'type' => 'Seatwork',
                'activity_id' => $seatwork->id,
                'title' => $seatwork->title,
                'status' => $attempt ? $attempt->status : AttemptStatus::NotStarted->value,
                'score' => null,
                'total' => null,
                'percentage' => null,
                'submitted_at' => null,
                'resultUrl' => null,
                'takeUrl' => route('student.seatworks.take', $seatwork->id),
            ]);
        }

        $practicals = Practical::whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->tap(fn ($q) => $applyScopes($q, false))
            ->with(['attempts' => $attemptsFor])
            ->latest()
            ->get();
        foreach ($practicals as $practical) {
            if (isset($submittedKeys['Practical:'.$practical->id])) {
                continue;
            }
            $inProgress = $practical->attempts
                ->where('status', AttemptStatus::Submitted->value)
                ->isNotEmpty()
                ? null
                : $practical->attempts->where('status', AttemptStatus::InProgress->value)->first();
            $closed = $practical->isClosed();
            $status = $inProgress
                ? AttemptStatus::InProgress->value
                : AttemptStatus::NotStarted->value;
            if ($closed) {
                $status = AttemptStatus::Closed->value;
            }
            $rows->push([
                'id' => 'practical-'.$practical->id,
                'type' => 'Practical',
                'activity_id' => $practical->id,
                'title' => $practical->title,
                'status' => $status,
                'score' => null,
                'total' => null,
                'percentage' => null,
                'submitted_at' => null,
                'resultUrl' => null,
                'takeUrl' => $closed ? null : route('student.practicals.take', $practical->id),
            ]);
        }

        $exams = Exam::whereIn('teacher_id', $teacherIds)
            ->where('is_published', true)
            ->tap(fn ($q) => $applyScopes($q))
            ->with(['attempts' => $attemptsFor])
            ->withCount('sections')
            ->latest()
            ->get()
            ->filter(fn ($e) => $e->sections_count > 0);
        foreach ($exams as $exam) {
            if (isset($submittedKeys['Exam:'.$exam->id])) {
                continue;
            }
            $attempt = $exam->attempts->first();
            $rows->push([
                'id' => 'exam-'.$exam->id,
                'type' => 'Exam',
                'activity_id' => $exam->id,
                'title' => $exam->title,
                'status' => $attempt ? $attempt->status : AttemptStatus::NotStarted->value,
                'score' => null,
                'total' => null,
                'percentage' => null,
                'submitted_at' => null,
                'resultUrl' => null,
                'takeUrl' => route('student.exams.take', $exam->id),
            ]);
        }

        return $rows->sortBy('title')->values();
    }

    private function getAccessibleTeacherIds(User $student)
    {
        $superadminIds = User::where('role', 'superadmin')->pluck('id');

        return $student->teachers()->pluck('users.id')->concat($superadminIds)->unique();
    }

    private function authorizeAccess(Quiz $quiz, $student): void
    {
        $hasGrade = $quiz->gradeLevels()->exists()
            ? ($student->grade_level_id && $quiz->gradeLevels()->where('grade_level_id', $student->grade_level_id)->exists())
            : ($student->grade && $quiz->grade === $student->grade);

        $teacherIds = $this->getAccessibleTeacherIds($student);

        if (
            ! in_array($quiz->teacher_id, $teacherIds->toArray()) ||
            ! $quiz->is_published ||
            ! $hasGrade
        ) {
            abort(403, 'This quiz is not available to your account.');
        }
    }

    public function leaderboard(): Response
    {
        $student = Auth::user();

        $classmates = User::where('role', 'student')
            ->where('section_id', $student->section_id)
            ->orderByDesc('total_points')
            ->get(['id', 'name', 'total_points', 'section_id', 'equipped_badge', 'profile_photo_updated_at', 'updated_at']);

        $weekPts = StudentPoint::whereIn('student_id', $classmates->pluck('id'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(points) as pts')
            ->pluck('pts', 'student_id');

        $entries = $classmates->map(fn ($u, $i) => [
            'id' => $u->id,
            'name' => $u->name,
            'avatar' => $u->avatar,
            'total_points' => (int) $u->total_points,
            'week_points' => (int) ($weekPts[$u->id] ?? 0),
            'equipped_badge' => $u->equipped_badge,
            'rank' => $i + 1,
        ]);

        return Inertia::render('Student/Leaderboard', [
            'entries' => $entries->values(),
            'section_name' => $student->section?->name ?? 'Your Section',
            'student_name' => $student->name,
            'student_total_points' => (int) $student->total_points,
            'student_rank' => $entries->search(fn ($e) => $e['id'] === $student->id) + 1,
        ]);
    }
}
