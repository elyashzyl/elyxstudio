<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Helpers\PointsHelper;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\GameProgress;
use App\Models\GameRoom;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\Section;
use App\Models\Setting;
use App\Models\StudentPoint;
use App\Models\Subject;
use App\Models\User;
use App\Services\ActivityAiGraderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TeacherController extends Controller
{
    public function dashboard(): Response
    {
        $teacher = Auth::user();

        if ($teacher->isSuperadmin()) {
            $quizIds = Quiz::pluck('id');
            $seatworkIds = Seatwork::pluck('id');
            $examIds = Exam::pluck('id');
            $practicalIds = Practical::pluck('id');
            $studentIds = User::where('role', 'student')->pluck('id');
        } else {
            $quizIds = $teacher->quizzes()->pluck('id');
            $seatworkIds = $teacher->seatworks()->pluck('id');
            $examIds = $teacher->exams()->pluck('id');
            $practicalIds = $teacher->practicals()->pluck('id');
            $studentIds = $teacher->students()->pluck('users.id');
        }

        $studentCount = $studentIds->count();
        $totalActivities = $quizIds->count() + $seatworkIds->count() + $examIds->count() + $practicalIds->count();

        // Submitted attempts
        $quizAttempts = QuizAttempt::whereIn('quiz_id', $quizIds)->where('status', 'submitted')
            ->with('student:id,name', 'quiz:id,title')->get(['id', 'quiz_id', 'student_id', 'score', 'total_points', 'submitted_at']);
        $seatworkAttempts = SeatworkAttempt::whereIn('seatwork_id', $seatworkIds)->where('status', 'submitted')
            ->with('student:id,name', 'seatwork:id,title')->get(['id', 'seatwork_id', 'student_id', 'score', 'total_points', 'submitted_at', 'is_recorded']);
        $examAttempts = ExamAttempt::whereIn('exam_id', $examIds)->where('status', 'submitted')
            ->with('student:id,name', 'exam:id,title,max_score')->get(['id', 'exam_id', 'student_id', 'total_score', 'submitted_at']);
        $practicalAttempts = PracticalAttempt::whereIn('practical_id', $practicalIds)->where('status', 'submitted')->whereNotNull('total_score')
            ->with('student:id,name', 'practical:id,title,max_score')->get(['id', 'practical_id', 'student_id', 'total_score', 'submitted_at']);

        $totalSubmissions = $quizAttempts->count() + $seatworkAttempts->count() + $examAttempts->count() + $practicalAttempts->count();

        // Official recorded seatwork attempts for grade averages
        $officialSeatworkAttempts = $seatworkAttempts->where('is_recorded', true);
        if ($officialSeatworkAttempts->isEmpty()) {
            $officialSeatworkAttempts = $seatworkAttempts;
        }

        // Averages
        $quizAvg = $this->calcAvg($quizAttempts, 'score', 'total_points');
        $seatworkAvg = $this->calcAvg($officialSeatworkAttempts, 'score', 'total_points');
        $examAvg = $this->calcAvg($examAttempts, 'total_score', 'exam.max_score');
        $practicalAvg = $this->calcAvg($practicalAttempts, 'total_score', 'practical.max_score');

        $allScore = 0;
        $allMax = 0;
        foreach ($quizAttempts as $a) {
            $allScore += $a->score;
            $allMax += $a->total_points;
        }
        foreach ($officialSeatworkAttempts as $a) {
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

        // Completion rates
        $quizSubmitted = $quizAttempts->pluck('student_id')->unique()->count();
        $seatworkSubmitted = $seatworkAttempts->pluck('student_id')->unique()->count();
        $examSubmitted = $examAttempts->pluck('student_id')->unique()->count();
        $practicalSubmitted = $practicalAttempts->pluck('student_id')->unique()->count();

        // Recent activity
        $recent = collect();
        foreach ($quizAttempts as $a) {
            $pct = $a->total_points > 0 ? round(($a->score / $a->total_points) * 100, 1) : 0;
            $recent->push(['type' => 'Quiz', 'title' => $a->quiz->title, 'student' => $a->student->name, 'score' => $a->score, 'total' => $a->total_points, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        foreach ($seatworkAttempts as $a) {
            $pct = $a->total_points > 0 ? round(($a->score / $a->total_points) * 100, 1) : 0;
            $recent->push(['type' => 'Seatwork', 'title' => $a->seatwork->title, 'student' => $a->student->name, 'score' => $a->score, 'total' => $a->total_points, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        foreach ($examAttempts as $a) {
            $max = $a->exam->max_score ?? 0;
            $pct = $max > 0 ? round(($a->total_score / $max) * 100, 1) : 0;
            $recent->push(['type' => 'Exam', 'title' => $a->exam->title, 'student' => $a->student->name, 'score' => $a->total_score, 'total' => $max, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        foreach ($practicalAttempts as $a) {
            $max = $a->practical->max_score ?? 0;
            $pct = $max > 0 ? round(($a->total_score / $max) * 100, 1) : 0;
            $recent->push(['type' => 'Practical', 'title' => $a->practical->title, 'student' => $a->student->name, 'score' => $a->total_score, 'total' => $max, 'pct' => $pct, 'submitted_at' => $a->submitted_at?->toDateTimeString()]);
        }
        $recent = $recent->sortByDesc('submitted_at')->take(10)->values();

        // Weekly trends (last 8 weeks): submissions + average score.
        $allScored = collect()
            ->concat($quizAttempts->map(fn ($a) => ['pct' => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0, 'at' => $a->submitted_at]))
            ->concat($seatworkAttempts->map(fn ($a) => ['pct' => $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0, 'at' => $a->submitted_at]))
            ->concat($examAttempts->map(function ($a) {
                $max = $a->exam->max_score ?? 0;

                return ['pct' => $max > 0 ? ($a->total_score / $max) * 100 : 0, 'at' => $a->submitted_at];
            }))
            ->concat($practicalAttempts->map(function ($a) {
                $max = $a->practical->max_score ?? 0;

                return ['pct' => $max > 0 ? ($a->total_score / $max) * 100 : 0, 'at' => $a->submitted_at];
            }));
        $weeklyTrends = [];
        for ($i = 7; $i >= 0; $i--) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = (clone $start)->endOfWeek();
            $inWeek = $allScored->filter(fn ($r) => $r['at'] && $r['at']->between($start, $end));
            $weeklyTrends[] = [
                'label' => $start->format('M j'),
                'submissions' => $inWeek->count(),
                'average' => $inWeek->count() ? round($inWeek->avg('pct'), 1) : 0,
            ];
        }

        $topStudents = User::where('role', 'student')->whereIn('id', $studentIds)
            ->orderByDesc('total_points')->take(3)->get(['id', 'name', 'total_points', 'profile_photo_updated_at'])
            ->values()->map(fn ($u, $i) => [
                'id' => $u->id,
                'name' => $u->name,
                'avatar' => $u->avatar,
                'total_points' => (int) $u->total_points,
                'rank' => $i + 1,
            ]);

        // Student Pass Rates & Tier Distribution
        $passingThreshold = (float) Setting::get('xp_passing_percentage', 60);
        $totalGraded = $allScored->count();
        $passedCount = $allScored->filter(fn ($r) => $r['pct'] >= $passingThreshold)->count();
        $failedCount = $totalGraded - $passedCount;
        $passRate = $totalGraded > 0 ? round(($passedCount / $totalGraded) * 100, 1) : 0;

        $masteryCount = $allScored->filter(fn ($r) => $r['pct'] >= 85)->count();
        $proficientCount = $allScored->filter(fn ($r) => $r['pct'] >= 70 && $r['pct'] < 85)->count();
        $developingCount = $allScored->filter(fn ($r) => $r['pct'] >= 50 && $r['pct'] < 70)->count();
        $beginningCount = $allScored->filter(fn ($r) => $r['pct'] < 50)->count();

        $passRateMetrics = [
            'passRate' => $passRate,
            'passedCount' => $passedCount,
            'failedCount' => $failedCount,
            'totalGraded' => $totalGraded,
            'passingThreshold' => $passingThreshold,
            'masteryCount' => $masteryCount,
            'proficientCount' => $proficientCount,
            'developingCount' => $developingCount,
            'beginningCount' => $beginningCount,
            'tiers' => [
                ['label' => 'Mastery (85-100%)', 'count' => $masteryCount, 'pct' => $totalGraded ? round(($masteryCount / $totalGraded) * 100, 1) : 0, 'color' => '#10B981'],
                ['label' => 'Proficient (70-84%)', 'count' => $proficientCount, 'pct' => $totalGraded ? round(($proficientCount / $totalGraded) * 100, 1) : 0, 'color' => '#3B82F6'],
                ['label' => 'Developing (50-69%)', 'count' => $developingCount, 'pct' => $totalGraded ? round(($developingCount / $totalGraded) * 100, 1) : 0, 'color' => '#F59E0B'],
                ['label' => 'Beginning (<50%)', 'count' => $beginningCount, 'pct' => $totalGraded ? round(($beginningCount / $totalGraded) * 100, 1) : 0, 'color' => '#EF4444'],
            ],
        ];

        // Activity Completion & Engagement Trends
        $expectedSubmissions = $studentCount > 0 ? ($totalActivities * $studentCount) : 0;
        $overallCompletionRate = $expectedSubmissions > 0 ? min(100, round(($totalSubmissions / $expectedSubmissions) * 100, 1)) : 0;

        $completionMetrics = [
            'overallCompletionRate' => $overallCompletionRate,
            'expectedSubmissions' => $expectedSubmissions,
            'actualSubmissions' => $totalSubmissions,
            'byType' => [
                [
                    'type' => 'Quiz',
                    'activities' => $quizIds->count(),
                    'submissions' => $quizAttempts->count(),
                    'students' => $quizSubmitted,
                    'rate' => ($studentCount * $quizIds->count()) > 0 ? min(100, round(($quizAttempts->count() / ($studentCount * $quizIds->count())) * 100, 1)) : 0,
                    'color' => '#3B82F6',
                ],
                [
                    'type' => 'Seatwork',
                    'activities' => $seatworkIds->count(),
                    'submissions' => $seatworkAttempts->count(),
                    'students' => $seatworkSubmitted,
                    'rate' => ($studentCount * $seatworkIds->count()) > 0 ? min(100, round(($seatworkAttempts->count() / ($studentCount * $seatworkIds->count())) * 100, 1)) : 0,
                    'color' => '#10B981',
                ],
                [
                    'type' => 'Exam',
                    'activities' => $examIds->count(),
                    'submissions' => $examAttempts->count(),
                    'students' => $examSubmitted,
                    'rate' => ($studentCount * $examIds->count()) > 0 ? min(100, round(($examAttempts->count() / ($studentCount * $examIds->count())) * 100, 1)) : 0,
                    'color' => '#FBBF24',
                ],
                [
                    'type' => 'Practical',
                    'activities' => $practicalIds->count(),
                    'submissions' => $practicalAttempts->count(),
                    'students' => $practicalSubmitted,
                    'rate' => ($studentCount * $practicalIds->count()) > 0 ? min(100, round(($practicalAttempts->count() / ($studentCount * $practicalIds->count())) * 100, 1)) : 0,
                    'color' => '#8B5CF6',
                ],
            ],
        ];

        return Inertia::render('Teacher/Dashboard', [
            'totalSubmissions' => $totalSubmissions,
            'totalActivities' => $totalActivities,
            'studentCount' => $studentCount,
            'overallAvg' => $overallAvg,
            'typeAverages' => ['Quiz' => $quizAvg, 'Exam' => $examAvg, 'Seatwork' => $seatworkAvg, 'Practical' => $practicalAvg],
            'typeSubmissions' => ['Quiz' => $quizAttempts->count(), 'Exam' => $examAttempts->count(), 'Seatwork' => $seatworkAttempts->count(), 'Practical' => $practicalAttempts->count()],
            'recentActivity' => $recent,
            'weeklyTrends' => $weeklyTrends,
            'topStudents' => $topStudents,
            'passRateMetrics' => $passRateMetrics,
            'completionMetrics' => $completionMetrics,
            'isSuperadmin' => $teacher->isSuperadmin(),
        ]);
    }

    private function calcAvg($attempts, string $scoreField, ?string $maxField): float
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

    public function teachers(): Response
    {
        $teacher = Auth::user();
        abort_unless($teacher->isSuperadmin(), 403);

        $teachers = User::where('role', 'teacher')
            ->with(['subjects:id,name', 'gradeLevel:id,name', 'section:id,name'])
            ->withCount('students')
            ->withCount('quizzes')
            ->withCount('exams')
            ->withCount('seatworks')
            ->withCount('practicals')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'gender',
                'bio',
                'grade',
                'grade_level_id',
                'section_id',
                'messenger_enabled',
                'allow_classmate_messaging',
                'profile_photo_updated_at',
                'created_at',
            ]);

        return Inertia::render('Teacher/Teachers', [
            'teachers' => $teachers,
            'subjects' => Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'gradeLevels' => GradeLevel::orderBy('display_order')->orderBy('name')->get(['id', 'name']),
            'sections' => Section::orderBy('name')->get(['id', 'name', 'grade_level_id']),
        ]);
    }

    public function teacherShow(User $user): Response
    {
        $teacher = Auth::user();
        abort_unless($teacher->isSuperadmin(), 403);
        abort_unless($user->isTeacher(), 404);

        $studentIds = $user->students()->pluck('users.id');
        $students = User::whereIn('id', $studentIds)
            ->with('section:id,name', 'gradeLevel:id,name')
            ->orderBy('grade')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'grade', 'grade_level_id', 'section_id', 'profile_photo_updated_at', 'created_at']);

        $user->load(['subjects:id,name', 'gradeLevel:id,name', 'section:id,name']);

        return Inertia::render('Teacher/TeacherShow', [
            'teacher' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'bio' => $user->bio,
                'grade' => $user->grade,
                'grade_level_id' => $user->grade_level_id,
                'section_id' => $user->section_id,
                'grade_level' => $user->gradeLevel ? ['id' => $user->gradeLevel->id, 'name' => $user->gradeLevel->name] : null,
                'section' => $user->section ? ['id' => $user->section->id, 'name' => $user->section->name] : null,
                'messenger_enabled' => (bool) ($user->messenger_enabled ?? true),
                'allow_classmate_messaging' => (bool) ($user->allow_classmate_messaging ?? true),
                'subjects' => $user->subjects()->get(['subjects.id', 'subjects.name']),
                'created_at' => $user->created_at,
            ],
            'students' => $students,
        ]);
    }

    public function teacherUpdate(Request $request, User $user): RedirectResponse
    {
        $teacher = Auth::user();
        abort_unless($teacher->isSuperadmin(), 403);
        abort_unless($user->isTeacher(), 404);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'gender' => ['nullable', 'string', 'in:Male,Female'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
            'messenger_enabled' => ['nullable', 'boolean'],
            'allow_classmate_messaging' => ['nullable', 'boolean'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (array_key_exists('grade_level_id', $validated)) {
            $validated['grade'] = $validated['grade_level_id']
                ? GradeLevel::where('id', $validated['grade_level_id'])->value('name')
                : null;
        }

        if ($request->has('messenger_enabled')) {
            $validated['messenger_enabled'] = $request->boolean('messenger_enabled');
        }
        if ($request->has('allow_classmate_messaging')) {
            $validated['allow_classmate_messaging'] = $request->boolean('allow_classmate_messaging');
        }

        if (empty($validated['grade_level_id'])) {
            $validated['grade_level_id'] = null;
            $validated['grade'] = null;
            $validated['section_id'] = null;
        } elseif (!empty($validated['section_id'])) {
            $validSection = Section::where('id', $validated['section_id'])
                ->where('grade_level_id', $validated['grade_level_id'])
                ->exists();
            if (! $validSection) {
                $validated['section_id'] = null;
            }
        }

        $user->update($validated);

        if ($request->has('subject_ids')) {
            $user->subjects()->sync($validated['subject_ids'] ?? []);
        }

        return back()->with('success', 'Teacher updated successfully.');
    }

    public function exportScores(Request $request): StreamedResponse
    {
        $teacher = Auth::user();
        abort_unless($teacher->isTeacher() || $teacher->isSuperadmin(), 403);

        $studentId = $request->query('student_id');
        $grade = $request->query('grade');
        $section = $request->query('section');

        if ($teacher->isSuperadmin()) {
            $query = User::where('role', 'student')->with('section:id,name');
        } else {
            $assignedIds = $teacher->students()->pluck('users.id');
            $query = User::whereIn('id', $assignedIds)->with('section:id,name');
        }

        if ($studentId) {
            $query->where('id', $studentId);
        }

        if ($grade) {
            $query->where('grade', $grade);
        }

        if ($section) {
            $query->where(function ($q) use ($section) {
                $q->whereHas('section', fn ($sq) => $sq->where('name', $section))
                    ->orWhere('section_id', $section);
            });
        }

        $students = $query->orderBy('grade')->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $quizAttempts = QuizAttempt::whereIn('student_id', $studentIds)
            ->with(['quiz:id,title', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->get();

        $seatworkAttempts = SeatworkAttempt::whereIn('student_id', $studentIds)
            ->with(['seatwork:id,title', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->where(function ($q) {
                $q->where('is_recorded', true)->orWhereNull('is_recorded');
            })
            ->get();

        $examAttempts = ExamAttempt::whereIn('student_id', $studentIds)
            ->with(['exam:id,title,max_score', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->get();

        $practicalAttempts = PracticalAttempt::whereIn('student_id', $studentIds)
            ->with(['practical:id,title,max_score', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->whereNotNull('total_score')
            ->get();

        $sectionsMap = Section::pluck('name', 'id');

        $rows = collect();

        foreach ($quizAttempts as $a) {
            $rows->push([
                'student_name' => $a->student?->name ?? 'Unknown',
                'student_email' => $a->student?->email ?? '',
                'grade' => $a->student?->grade ?? '',
                'section' => $sectionsMap[$a->student?->section_id] ?? '',
                'type' => 'Quiz',
                'title' => $a->quiz?->title ?? 'Quiz',
                'score' => $a->score,
                'total' => $a->total_points,
                'percentage' => $a->total_points ? round(($a->score / $a->total_points) * 100, 1) : 0,
                'status' => $a->status,
                'date' => $a->submitted_at?->format('Y-m-d H:i') ?? $a->created_at?->format('Y-m-d H:i'),
            ]);
        }

        foreach ($seatworkAttempts as $a) {
            $rows->push([
                'student_name' => $a->student?->name ?? 'Unknown',
                'student_email' => $a->student?->email ?? '',
                'grade' => $a->student?->grade ?? '',
                'section' => $sectionsMap[$a->student?->section_id] ?? '',
                'type' => 'Seatwork',
                'title' => $a->seatwork?->title ?? 'Seatwork',
                'score' => $a->score,
                'total' => $a->total_points,
                'percentage' => $a->total_points ? round(($a->score / $a->total_points) * 100, 1) : 0,
                'status' => $a->status,
                'date' => $a->submitted_at?->format('Y-m-d H:i') ?? $a->created_at?->format('Y-m-d H:i'),
            ]);
        }

        foreach ($examAttempts as $a) {
            $max = $a->exam?->max_score ?? 0;
            $rows->push([
                'student_name' => $a->student?->name ?? 'Unknown',
                'student_email' => $a->student?->email ?? '',
                'grade' => $a->student?->grade ?? '',
                'section' => $sectionsMap[$a->student?->section_id] ?? '',
                'type' => 'Exam',
                'title' => $a->exam?->title ?? 'Exam',
                'score' => $a->total_score,
                'total' => $max,
                'percentage' => $max ? round(($a->total_score / $max) * 100, 1) : 0,
                'status' => $a->status,
                'date' => $a->submitted_at?->format('Y-m-d H:i') ?? $a->created_at?->format('Y-m-d H:i'),
            ]);
        }

        foreach ($practicalAttempts as $a) {
            $max = $a->practical?->max_score ?? 0;
            $rows->push([
                'student_name' => $a->student?->name ?? 'Unknown',
                'student_email' => $a->student?->email ?? '',
                'grade' => $a->student?->grade ?? '',
                'section' => $sectionsMap[$a->student?->section_id] ?? '',
                'type' => 'Practical',
                'title' => $a->practical?->title ?? 'Practical',
                'score' => $a->total_score,
                'total' => $max,
                'percentage' => $max ? round(($a->total_score / $max) * 100, 1) : 0,
                'status' => $a->status,
                'date' => $a->submitted_at?->format('Y-m-d H:i') ?? $a->created_at?->format('Y-m-d H:i'),
            ]);
        }

        $sorted = $rows->sortBy([
            ['student_name', 'asc'],
            ['date', 'desc'],
        ]);

        $filename = 'edupulse-scores-'.now()->format('Y-m-d-His').'.csv';
        if ($studentId && $students->count() === 1) {
            $cleanName = Str::slug($students->first()->name);
            $filename = "edupulse-scores-{$cleanName}-".now()->format('Y-m-d').'.csv';
        }

        return response()->streamDownload(function () use ($sorted) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Student Name',
                'Email',
                'Grade',
                'Section',
                'Activity Type',
                'Activity Title',
                'Score',
                'Total Points',
                'Percentage (%)',
                'Status',
                'Date',
            ]);

            foreach ($sorted as $row) {
                fputcsv($output, [
                    $row['student_name'],
                    $row['student_email'],
                    $row['grade'],
                    $row['section'],
                    $row['type'],
                    $row['title'],
                    $row['score'],
                    $row['total'],
                    $row['percentage'],
                    $row['status'],
                    $row['date'],
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function exportReportCardPdf(Request $request): HttpResponse
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $teacher = Auth::user();
        abort_unless($teacher->isTeacher() || $teacher->isSuperadmin(), 403);

        $studentId = $request->query('student_id');
        $grade = $request->query('grade');
        $section = $request->query('section');

        if ($teacher->isSuperadmin()) {
            $query = User::where('role', 'student')->with('section:id,name');
        } else {
            $assignedIds = $teacher->students()->pluck('users.id');
            $query = User::whereIn('id', $assignedIds)->with('section:id,name');
        }

        if ($studentId) {
            $query->where('id', $studentId);
        }

        if ($grade) {
            $query->where('grade', $grade);
        }

        if ($section) {
            $query->where(function ($q) use ($section) {
                $q->whereHas('section', fn ($sq) => $sq->where('name', $section))
                    ->orWhere('section_id', $section);
            });
        }

        $students = $query->orderBy('grade')->orderBy('name')->get();
        if ($students->isEmpty()) {
            abort(404, 'No student records found matching the selection.');
        }

        abort_if(
            $students->count() > 60,
            422,
            'Too many student records selected for a single PDF export ('.$students->count().'). Please filter by grade or section.'
        );

        $studentIds = $students->pluck('id');

        $quizAttempts = QuizAttempt::whereIn('student_id', $studentIds)
            ->with(['quiz:id,title', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->get();

        $seatworkAttempts = SeatworkAttempt::whereIn('student_id', $studentIds)
            ->with(['seatwork:id,title', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->where(function ($q) {
                $q->where('is_recorded', true)->orWhereNull('is_recorded');
            })
            ->get();

        $examAttempts = ExamAttempt::whereIn('student_id', $studentIds)
            ->with(['exam:id,title,max_score', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->get();

        $practicalAttempts = PracticalAttempt::whereIn('student_id', $studentIds)
            ->with(['practical:id,title,max_score', 'student:id,name,email,grade,section_id'])
            ->where('status', 'submitted')
            ->whereNotNull('total_score')
            ->get();

        $sectionsMap = Section::pluck('name', 'id');

        $reports = [];

        foreach ($students as $stu) {
            $stuQuiz = $quizAttempts->where('student_id', $stu->id);
            $stuSeatwork = $seatworkAttempts->where('student_id', $stu->id);
            $stuExam = $examAttempts->where('student_id', $stu->id);
            $stuPractical = $practicalAttempts->where('student_id', $stu->id);

            $activities = collect();
            $totScore = 0;
            $totMax = 0;

            foreach ($stuQuiz as $a) {
                $score = (float) $a->score;
                $max = (float) $a->total_points;
                $pct = $max > 0 ? round(($score / $max) * 100, 1) : 0;
                $totScore += $score;
                $totMax += $max;
                $activities->push([
                    'type' => 'Quiz',
                    'title' => $a->quiz?->title ?? 'Quiz',
                    'score' => $score,
                    'total' => $max,
                    'percentage' => $pct,
                    'date' => $a->submitted_at?->format('Y-m-d') ?? $a->created_at?->format('Y-m-d'),
                ]);
            }

            foreach ($stuSeatwork as $a) {
                $score = (float) $a->score;
                $max = (float) $a->total_points;
                $pct = $max > 0 ? round(($score / $max) * 100, 1) : 0;
                $totScore += $score;
                $totMax += $max;
                $activities->push([
                    'type' => 'Seatwork',
                    'title' => $a->seatwork?->title ?? 'Seatwork',
                    'score' => $score,
                    'total' => $max,
                    'percentage' => $pct,
                    'date' => $a->submitted_at?->format('Y-m-d') ?? $a->created_at?->format('Y-m-d'),
                ]);
            }

            foreach ($stuExam as $a) {
                $score = (float) $a->total_score;
                $max = (float) ($a->exam?->max_score ?? 0);
                $pct = $max > 0 ? round(($score / $max) * 100, 1) : 0;
                $totScore += $score;
                $totMax += $max;
                $activities->push([
                    'type' => 'Exam',
                    'title' => $a->exam?->title ?? 'Exam',
                    'score' => $score,
                    'total' => $max,
                    'percentage' => $pct,
                    'date' => $a->submitted_at?->format('Y-m-d') ?? $a->created_at?->format('Y-m-d'),
                ]);
            }

            foreach ($stuPractical as $a) {
                $score = (float) $a->total_score;
                $max = (float) ($a->practical?->max_score ?? 0);
                $pct = $max > 0 ? round(($score / $max) * 100, 1) : 0;
                $totScore += $score;
                $totMax += $max;
                $activities->push([
                    'type' => 'Practical',
                    'title' => $a->practical?->title ?? 'Practical',
                    'score' => $score,
                    'total' => $max,
                    'percentage' => $pct,
                    'date' => $a->submitted_at?->format('Y-m-d') ?? $a->created_at?->format('Y-m-d'),
                ]);
            }

            $overallPct = $totMax > 0 ? round(($totScore / $totMax) * 100, 1) : 0;

            $rating = match (true) {
                $overallPct >= 90 => ['letter' => 'A', 'rating' => 'Outstanding', 'color' => '#059669'],
                $overallPct >= 80 => ['letter' => 'B', 'rating' => 'Very Satisfactory', 'color' => '#2563eb'],
                $overallPct >= 75 => ['letter' => 'C+', 'rating' => 'Satisfactory', 'color' => '#0284c7'],
                $overallPct >= 60 => ['letter' => 'C', 'rating' => 'Fair', 'color' => '#d97706'],
                default => ['letter' => 'D', 'rating' => 'Needs Improvement', 'color' => '#dc2626'],
            };

            $categories = [
                [
                    'type' => 'Quizzes',
                    'count' => $stuQuiz->count(),
                    'score' => (float) $stuQuiz->sum('score'),
                    'total' => (float) $stuQuiz->sum('total_points'),
                    'percentage' => $stuQuiz->sum('total_points') > 0 ? round(($stuQuiz->sum('score') / $stuQuiz->sum('total_points')) * 100, 1) : 0,
                ],
                [
                    'type' => 'Seatworks',
                    'count' => $stuSeatwork->count(),
                    'score' => (float) $stuSeatwork->sum('score'),
                    'total' => (float) $stuSeatwork->sum('total_points'),
                    'percentage' => $stuSeatwork->sum('total_points') > 0 ? round(($stuSeatwork->sum('score') / $stuSeatwork->sum('total_points')) * 100, 1) : 0,
                ],
                [
                    'type' => 'Examinations',
                    'count' => $stuExam->count(),
                    'score' => (float) $stuExam->sum('total_score'),
                    'total' => (float) $stuExam->sum(fn ($e) => $e->exam?->max_score ?? 0),
                    'percentage' => $stuExam->sum(fn ($e) => $e->exam?->max_score ?? 0) > 0 ? round(($stuExam->sum('total_score') / $stuExam->sum(fn ($e) => $e->exam?->max_score ?? 0)) * 100, 1) : 0,
                ],
                [
                    'type' => 'Practical Assessments',
                    'count' => $stuPractical->count(),
                    'score' => (float) $stuPractical->sum('total_score'),
                    'total' => (float) $stuPractical->sum(fn ($p) => $p->practical?->max_score ?? 0),
                    'percentage' => $stuPractical->sum(fn ($p) => $p->practical?->max_score ?? 0) > 0 ? round(($stuPractical->sum('total_score') / $stuPractical->sum(fn ($p) => $p->practical?->max_score ?? 0)) * 100, 1) : 0,
                ],
            ];

            $activityList = $activities->sortByDesc('date')->values();
            if ($students->count() > 1 && $activityList->count() > 40) {
                $activityList = $activityList->take(40);
            }

            $reports[] = [
                'student' => $stu,
                'section' => $sectionsMap[$stu->section_id] ?? '',
                'teacher_name' => $teacher->name,
                'overall_percentage' => $overallPct,
                'total_score' => $totScore,
                'total_max' => $totMax,
                'grade_rating' => $rating,
                'categories' => $categories,
                'activities' => $activityList->all(),
            ];
        }

        $branding = [
            'school_name' => Setting::getSchoolName(),
            'school_short' => Setting::getSchoolShort(),
            'school_id' => Setting::getSchoolId(),
            'school_address' => Setting::getSchoolAddress(),
            'support_email' => Setting::getSupportEmail(),
            'app_name' => Setting::getAppName(),
            'generated_at' => now()->format('F j, Y g:i A'),
        ];

        $pdf = Pdf::loadView('exports.report_card_pdf', [
            'reports' => $reports,
            'branding' => $branding,
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'Helvetica');

        $filename = 'report_card_'.now()->format('Ymd_His').'.pdf';
        if ($studentId && $students->count() === 1) {
            $slug = Str::slug($students->first()->name);
            $filename = "report_card_{$slug}_".now()->format('Ymd').'.pdf';
        } elseif ($grade) {
            $slugGrade = Str::slug($grade);
            $filename = "report_cards_{$slugGrade}_".now()->format('Ymd').'.pdf';
        }

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function studentScores(User $user): JsonResponse
    {
        $teacher = Auth::user();
        $isAssigned = $teacher->isSuperadmin() || $teacher->students()->where('users.id', $user->id)->exists();
        abort_unless($isAssigned, 403);
        abort_unless($user->isStudent(), 404);

        $rows = collect();

        $quizAttempts = QuizAttempt::where('student_id', $user->id)
            ->with('quiz:id,title')
            ->orderByDesc('created_at')
            ->get(['id', 'quiz_id', 'score', 'total_points', 'status', 'created_at']);
        foreach ($quizAttempts as $a) {
            $rows->push(['id' => 'quiz-'.$a->id, 'type' => 'Quiz', 'title' => $a->quiz?->title ?? 'Quiz', 'score' => $a->score, 'total' => $a->total_points, 'status' => $a->status, 'at' => $a->created_at]);
        }

        $seatworkAttempts = SeatworkAttempt::where('student_id', $user->id)
            ->with('seatwork:id,title')
            ->orderByDesc('created_at')
            ->get(['id', 'seatwork_id', 'score', 'total_points', 'status', 'created_at', 'attempt_number', 'is_recorded']);
        foreach ($seatworkAttempts as $a) {
            $rows->push(['id' => 'seatwork-'.$a->id, 'type' => 'Seatwork', 'title' => $a->seatwork?->title ?? 'Seatwork', 'score' => $a->score, 'total' => $a->total_points, 'status' => $a->status, 'at' => $a->created_at, 'attempt_number' => $a->attempt_number, 'is_recorded' => (bool) $a->is_recorded]);
        }

        $examAttempts = ExamAttempt::where('student_id', $user->id)
            ->with('exam:id,title,max_score')
            ->orderByDesc('created_at')
            ->get(['id', 'exam_id', 'total_score', 'status', 'submitted_at', 'created_at']);
        foreach ($examAttempts as $a) {
            $rows->push(['id' => 'exam-'.$a->id, 'type' => 'Exam', 'title' => $a->exam?->title ?? 'Exam', 'score' => $a->total_score, 'total' => $a->exam->max_score ?? 0, 'status' => $a->status, 'at' => $a->submitted_at ?? $a->created_at]);
        }

        $practicalAttempts = PracticalAttempt::where('student_id', $user->id)
            ->with('practical:id,title,max_score')
            ->orderByDesc('created_at')
            ->get(['id', 'practical_id', 'total_score', 'status', 'submitted_at', 'created_at']);
        foreach ($practicalAttempts as $a) {
            $rows->push(['id' => 'practical-'.$a->id, 'type' => 'Practical', 'title' => $a->practical?->title ?? 'Practical', 'score' => $a->total_score, 'total' => $a->practical->max_score ?? 0, 'status' => $a->status, 'at' => $a->submitted_at ?? $a->created_at]);
        }

        $attempts = $rows->sortByDesc('at')->values();

        return response()->json(['attempts' => $attempts]);
    }

    public function studentPoints(User $user): JsonResponse
    {
        $teacher = Auth::user();
        $isAssigned = $teacher->isSuperadmin() || $teacher->students()->where('users.id', $user->id)->exists();
        abort_unless($isAssigned, 403);
        abort_unless($user->isStudent(), 404);

        $points = StudentPoint::where('student_id', $user->id)
            ->orderByDesc('created_at')
            ->get(['id', 'activity_type', 'activity_id', 'points', 'score', 'total', 'reason', 'created_at']);

        return response()->json([
            'total_points' => (int) $user->total_points,
            'points' => $points,
        ]);
    }

    public function studentSummary(User $user): JsonResponse
    {
        $teacher = Auth::user();
        $isAssigned = $teacher->isSuperadmin() || $teacher->students()->where('users.id', $user->id)->exists();
        abort_unless($isAssigned, 403);
        abort_unless($user->isStudent(), 404);

        if ($teacher->isSuperadmin()) {
            $quizIds = Quiz::where('is_published', true)->pluck('id');
            $seatworkIds = Seatwork::where('is_published', true)->pluck('id');
            $examIds = Exam::where('is_published', true)->pluck('id');
            $practicalIds = Practical::where('is_published', true)->pluck('id');
        } else {
            $quizIds = $teacher->quizzes()->where('is_published', true)->pluck('quizzes.id');
            $seatworkIds = $teacher->seatworks()->where('is_published', true)->pluck('seatworks.id');
            $examIds = $teacher->exams()->where('is_published', true)->pluck('exams.id');
            $practicalIds = $teacher->practicals()->where('is_published', true)->pluck('practicals.id');
        }

        $scored = collect();
        $done = 0;
        $qAttempts = QuizAttempt::where('student_id', $user->id)->whereIn('quiz_id', $quizIds)->where('status', 'submitted')->with('quiz:id,title')->get(['quiz_id', 'score', 'total_points']);
        $done += $qAttempts->pluck('quiz_id')->unique()->count();
        foreach ($qAttempts as $a) {
            $pct = $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0;
            $scored->push(['type' => 'Quiz', 'title' => $a->quiz?->title ?? 'Quiz', 'pct' => round($pct, 1), 'score' => $a->score, 'total' => $a->total_points]);
        }
        $sAttempts = SeatworkAttempt::where('student_id', $user->id)
            ->whereIn('seatwork_id', $seatworkIds)
            ->where('status', 'submitted')
            ->where(function ($q) {
                $q->where('is_recorded', true)->orWhereNull('is_recorded');
            })
            ->with('seatwork:id,title')
            ->get(['seatwork_id', 'score', 'total_points']);
        $done += $sAttempts->pluck('seatwork_id')->unique()->count();
        foreach ($sAttempts as $a) {
            $pct = $a->total_points > 0 ? ($a->score / $a->total_points) * 100 : 0;
            $scored->push(['type' => 'Seatwork', 'title' => $a->seatwork?->title ?? 'Seatwork', 'pct' => round($pct, 1), 'score' => $a->score, 'total' => $a->total_points]);
        }
        $eAttempts = ExamAttempt::where('student_id', $user->id)->whereIn('exam_id', $examIds)->where('status', 'submitted')->with('exam:id,title,max_score')->get(['exam_id', 'total_score']);
        $done += $eAttempts->pluck('exam_id')->unique()->count();
        foreach ($eAttempts as $a) {
            $max = $a->exam->max_score ?? 0;
            $pct = $max > 0 ? ($a->total_score / $max) * 100 : 0;
            $scored->push(['type' => 'Exam', 'title' => $a->exam?->title ?? 'Exam', 'pct' => round($pct, 1), 'score' => $a->total_score, 'total' => $max]);
        }
        $pAttempts = PracticalAttempt::where('student_id', $user->id)->whereIn('practical_id', $practicalIds)->where('status', 'submitted')->whereNotNull('total_score')->with('practical:id,title,max_score')->get(['practical_id', 'total_score']);
        $done += $pAttempts->pluck('practical_id')->unique()->count();
        foreach ($pAttempts as $a) {
            $max = $a->practical->max_score ?? 0;
            $pct = $max > 0 ? ($a->total_score / $max) * 100 : 0;
            $scored->push(['type' => 'Practical', 'title' => $a->practical?->title ?? 'Practical', 'pct' => round($pct, 1), 'score' => $a->total_score, 'total' => $max]);
        }

        $total = $quizIds->count() + $seatworkIds->count() + $examIds->count() + $practicalIds->count();

        return response()->json([
            'avg' => $scored->count() ? round($scored->avg('pct'), 1) : 0,
            'completion' => $total > 0 ? round(min(100, ($done / $total) * 100), 1) : 0,
            'done' => $done,
            'total' => $total,
            'top' => $scored->sortByDesc('pct')->take(3)->values(),
        ]);
    }

    public function studentUpdate(Request $request, User $user): RedirectResponse
    {
        $teacher = Auth::user();
        $isAssigned = $teacher->isSuperadmin() || $teacher->students()->where('users.id', $user->id)->exists();
        abort_unless($isAssigned, 403);
        abort_unless($user->isStudent(), 404);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'gender' => ['nullable', 'string', 'in:Male,Female'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'messenger_enabled' => ['nullable', 'boolean'],
            'allow_classmate_messaging' => ['nullable', 'boolean'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (array_key_exists('grade_level_id', $validated)) {
            $validated['grade'] = $validated['grade_level_id']
                ? GradeLevel::where('id', $validated['grade_level_id'])->value('name')
                : null;
        }

        if ($request->has('messenger_enabled')) {
            $validated['messenger_enabled'] = $request->boolean('messenger_enabled');
        }
        if ($request->has('allow_classmate_messaging')) {
            $validated['allow_classmate_messaging'] = $request->boolean('allow_classmate_messaging');
        }

        if (array_key_exists('grade_level_id', $validated)) {
            if (! array_key_exists('section_id', $validated)) {
                $validated['section_id'] = null;
            }

            if (empty($validated['grade_level_id'])) {
                $validated['grade_level_id'] = null;
                $validated['grade'] = null;
                $validated['section_id'] = null;
            } elseif (!empty($validated['section_id'])) {
                $validSection = Section::where('id', $validated['section_id'])
                    ->where('grade_level_id', $validated['grade_level_id'])
                    ->exists();
                if (! $validSection) {
                    $validated['section_id'] = null;
                }
            }
        }

        $user->update($validated);

        return back()->with('success', 'Student updated successfully.');
    }

    public function studentRemove(User $user): RedirectResponse
    {
        $teacher = Auth::user();
        $isAssigned = $teacher->isSuperadmin() || $teacher->students()->where('users.id', $user->id)->exists();
        abort_unless($isAssigned, 403);
        abort_unless($user->isStudent(), 404);

        $user->teachers()->detach($teacher->id);

        return back()->with('success', 'Student removed from class.');
    }

    public function bulkStudentRemove(Request $request): RedirectResponse
    {
        $teacher = Auth::user();

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $count = 0;
        foreach (User::whereIn('id', $data['ids'])->get() as $user) {
            if (! $user->isStudent()) {
                continue;
            }
            $isAssigned = $teacher->isSuperadmin() || $teacher->students()->where('users.id', $user->id)->exists();
            if (! $isAssigned) {
                continue;
            }
            $user->teachers()->detach($teacher->id);
            $count++;
        }

        return back()->with('success', "Removed {$count} student(s) from class.");
    }

    public function toggleMessenger(): RedirectResponse
    {
        $user = Auth::user();
        $user->messenger_enabled = ! $user->messenger_enabled;
        $user->save();

        return redirect()->back();
    }

    public function toggleClassmateMessaging(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isTeacher(), 403);
        $user->allow_classmate_messaging = ! ($user->allow_classmate_messaging ?? true);
        $user->save();

        return redirect()->back()->with(
            'success',
            $user->allow_classmate_messaging
                ? 'Classmate messaging enabled for your students.'
                : 'Classmate messaging disabled. Your students can only message teachers.'
        );
    }

    public function addXp(Request $request, User $user): JsonResponse
    {
        $teacher = Auth::user();
        $isAssigned = $teacher->isSuperadmin() || $teacher->students()->where('users.id', $user->id)->exists();
        abort_unless($isAssigned, 403);
        abort_unless($user->isStudent(), 404);

        $data = $request->validate([
            'xp' => ['required', 'integer', 'min:-1000', 'max:1000', 'not_in:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['xp'] < 0) {
            $delta = max(-$user->total_points, $data['xp']);
        } else {
            $delta = $data['xp'];
        }

        \DB::transaction(function () use ($user, $delta, $data, $teacher) {
            $user->increment('total_points', $delta);

            if ($delta !== 0) {
                StudentPoint::create([
                    'student_id' => $user->id,
                    'activity_type' => $delta > 0 ? 'Manual' : 'Deduction',
                    'activity_id' => 0,
                    'points' => $delta,
                    'score' => abs($delta),
                    'total' => abs($delta),
                    'reason' => $data['reason'] ?? ($delta > 0 ? 'Manually awarded by '.$teacher->name : 'Manually deducted by '.$teacher->name),
                ]);
            }
        });

        return response()->json(['ok' => true, 'total_points' => $user->fresh()->total_points]);
    }

    public function students(): Response
    {
        $teacher = Auth::user();

        $studentColumns = [
            'id',
            'name',
            'email',
            'gender',
            'bio',
            'grade',
            'grade_level_id',
            'section_id',
            'messenger_enabled',
            'allow_classmate_messaging',
            'total_points',
            'profile_photo_updated_at',
            'created_at',
        ];

        $studentsQuery = User::where('role', 'student')
            ->with([
                'section:id,name,grade_level_id',
                'gradeLevel:id,name',
                'teachers:id,name',
            ])
            ->withCount(['quizAttempts as completed_quizzes_count' => function ($query) {
                $query->where('status', 'submitted');
            }])
            ->orderBy('grade')
            ->orderBy('name');

        if (! $teacher->isSuperadmin()) {
            $studentsQuery->whereIn('id', $teacher->students()->pluck('users.id'));
        }

        $students = $studentsQuery->get($studentColumns);

        return Inertia::render('Teacher/Students', [
            'students' => $students,
            'gradeLevels' => GradeLevel::orderBy('display_order')->orderBy('name')->get(['id', 'name']),
            'sections' => Section::orderBy('name')->get(['id', 'name', 'grade_level_id']),
            'isSuperadmin' => $teacher->isSuperadmin(),
        ]);
    }

    public function results(): Response
    {
        $teacher = Auth::user();

        $studentIds = $teacher->isSuperadmin()
            ? null
            : $teacher->students()->pluck('users.id');

        $baseQuery = fn ($q) => $studentIds === null ? $q : $q->whereIn('student_id', $studentIds);

        $quizAttempts = $baseQuery(QuizAttempt::with(['quiz:id,title,teacher_id', 'quiz.teacher:id,name', 'student:id,name,grade,grade_level_id,section_id,gender'])
            ->where('status', 'submitted'))
            ->latest('submitted_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'activity_id' => $a->quiz_id,
                'type' => 'Quiz',
                'teacher_id' => $a->quiz?->teacher_id,
                'teacher_name' => $a->quiz?->teacher?->name,
                'student_name' => $a->student?->name ?? 'Unknown Student',
                'student_grade' => $a->student?->grade ?? '—',
                'student_gender' => $a->student?->gender,
                'grade_level_id' => $a->student?->grade_level_id,
                'section_id' => $a->student?->section_id,
                'title' => $a->quiz?->title ?? 'Quiz',
                'score' => $a->score,
                'total' => $a->total_points,
                'percentage' => $a->total_points ? round(($a->score / $a->total_points) * 100) : 0,
                'submitted_at' => $a->submitted_at,
            ]);

        $seatworkAttempts = $baseQuery(SeatworkAttempt::with(['seatwork:id,title,teacher_id', 'seatwork.teacher:id,name', 'student:id,name,grade,grade_level_id,section_id,gender'])
            ->where('status', 'submitted'))
            ->latest('submitted_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'activity_id' => $a->seatwork_id,
                'type' => 'Seatwork',
                'teacher_id' => $a->seatwork?->teacher_id,
                'teacher_name' => $a->seatwork?->teacher?->name,
                'student_name' => $a->student?->name ?? 'Unknown Student',
                'student_grade' => $a->student?->grade ?? '—',
                'student_gender' => $a->student?->gender,
                'grade_level_id' => $a->student?->grade_level_id,
                'section_id' => $a->student?->section_id,
                'title' => $a->seatwork?->title ?? 'Seatwork',
                'score' => $a->score,
                'total' => $a->total_points,
                'percentage' => $a->total_points ? round(($a->score / $a->total_points) * 100) : 0,
                'submitted_at' => $a->submitted_at,
                'attempt_number' => $a->attempt_number,
                'is_recorded' => (bool) $a->is_recorded,
            ]);

        $practicalAttempts = $baseQuery(PracticalAttempt::with(['practical:id,title,teacher_id,max_score', 'practical.teacher:id,name', 'student:id,name,grade,grade_level_id,section_id,gender'])
            ->where('status', 'submitted'))
            ->latest('submitted_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'activity_id' => $a->practical_id,
                'type' => 'Practical',
                'teacher_id' => $a->practical?->teacher_id,
                'teacher_name' => $a->practical?->teacher?->name,
                'student_name' => $a->student?->name ?? 'Unknown Student',
                'student_grade' => $a->student?->grade ?? '—',
                'student_gender' => $a->student?->gender,
                'grade_level_id' => $a->student?->grade_level_id,
                'section_id' => $a->student?->section_id,
                'title' => $a->practical?->title ?? 'Practical',
                'score' => $a->total_score,
                'total' => $a->practical?->max_score,
                'percentage' => $a->practical?->max_score ? round(($a->total_score / $a->practical->max_score) * 100) : 0,
                'submitted_at' => $a->submitted_at,
            ]);

        $examAttempts = $baseQuery(ExamAttempt::with(['exam:id,title,teacher_id,max_score', 'exam.teacher:id,name', 'student:id,name,grade,grade_level_id,section_id,gender'])
            ->where('status', 'submitted'))
            ->latest('submitted_at')
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'activity_id' => $a->exam_id,
                'type' => 'Exam',
                'teacher_id' => $a->exam?->teacher_id,
                'teacher_name' => $a->exam?->teacher?->name,
                'student_name' => $a->student?->name ?? 'Unknown Student',
                'student_grade' => $a->student?->grade ?? '—',
                'student_gender' => $a->student?->gender,
                'grade_level_id' => $a->student?->grade_level_id,
                'section_id' => $a->student?->section_id,
                'title' => $a->exam?->title ?? 'Exam',
                'score' => $a->total_score,
                'total' => $a->exam?->max_score,
                'percentage' => $a->exam?->max_score ? round(($a->total_score / $a->exam->max_score) * 100) : 0,
                'submitted_at' => $a->submitted_at,
            ]);

        $gameQuery = GameProgress::with(['game.teacher:id,name', 'game.cards', 'user:id,name,grade,grade_level_id,section_id,gender'])
            ->where('completed', true);
        if ($studentIds !== null) {
            $gameQuery->where(function ($q) use ($studentIds, $teacher) {
                $q->whereIn('user_id', $studentIds)
                    ->orWhereHas('game', fn ($g) => $g->where('teacher_id', $teacher->id));
            });
        }
        $gameAttempts = $gameQuery->latest('updated_at')
            ->get()
            ->map(function ($gp) {
                $total = $gp->game?->cards?->count() ?: max(1, $gp->done_count);
                $correctCount = DB::table('game_correct_answers')
                    ->where('user_id', $gp->user_id)
                    ->where('game_id', $gp->game_id)
                    ->count();
                $score = max((int) $gp->score, $correctCount);

                return [
                    'id' => $gp->id,
                    'activity_id' => $gp->game_id,
                    'type' => 'Game',
                    'teacher_id' => $gp->game?->teacher_id,
                    'teacher_name' => $gp->game?->teacher?->name,
                    'student_name' => $gp->user?->name ?? 'Unknown Student',
                    'student_grade' => $gp->user?->grade ?? '—',
                    'student_gender' => $gp->user?->gender,
                    'grade_level_id' => $gp->user?->grade_level_id,
                    'section_id' => $gp->user?->section_id,
                    'title' => $gp->game?->title ?? 'Learning Game',
                    'score' => $score,
                    'total' => $total,
                    'percentage' => $total ? round(($score / $total) * 100) : 0,
                    'submitted_at' => $gp->updated_at,
                ];
            });

        $liveRoomQuery = GameRoom::with([
            'game:id,title,teacher_id',
            'teacher:id,name',
            'participants.user:id,name,grade,grade_level_id,section_id',
        ])
            ->where('status', 'finished');

        if (! $teacher->isSuperadmin()) {
            $liveRoomQuery->where('teacher_id', $teacher->id);
        }

        $liveRooms = $liveRoomQuery->latest('updated_at')->get();

        $liveAttempts = collect();
        foreach ($liveRooms as $room) {
            $sorted = $room->participants->sortByDesc('score')->sortByDesc('streak')->values();
            $totalParticipants = $sorted->count();

            foreach ($sorted as $idx => $p) {
                if ($studentIds !== null && ! $studentIds->contains($p->user_id) && (int) $room->teacher_id !== (int) $teacher->id) {
                    continue;
                }

                $liveAttempts->push([
                    'id' => $p->id,
                    'activity_id' => $room->id,
                    'type' => 'Game',
                    'is_live' => true,
                    'teacher_id' => $room->teacher_id,
                    'teacher_name' => $room->teacher?->name,
                    'student_name' => $p->user?->name ?? 'Unknown Student',
                    'student_grade' => $p->user?->grade ?? '—',
                    'grade_level_id' => $p->user?->grade_level_id,
                    'section_id' => $p->user?->section_id,
                    'title' => ($room->game?->title ?? 'Live Match Arena').' · PIN '.$room->pin,
                    'score' => (int) $p->score,
                    'total' => $totalParticipants,
                    'percentage' => $totalParticipants > 0 ? round((($totalParticipants - $idx) / $totalParticipants) * 100) : 100,
                    'rank' => $idx + 1,
                    'room_pin' => $room->pin,
                    'submitted_at' => $p->updated_at ?? $room->updated_at,
                ]);
            }
        }

        $all = collect($quizAttempts)
            ->concat($seatworkAttempts)
            ->concat($practicalAttempts)
            ->concat($examAttempts)
            ->concat($gameAttempts)
            ->concat($liveAttempts)
            ->sortByDesc('submitted_at')
            ->values();

        $gradeLevels = GradeLevel::orderBy('display_order')->get(['id', 'name', 'display_order']);

        $sections = Section::orderBy('name')->get(['id', 'name', 'grade_level_id']);

        $data = [
            'activities' => $all,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
            'teachersList' => [],
        ];

        if ($teacher->isSuperadmin()) {
            $data['teachersList'] = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']);
        }

        return Inertia::render('Teacher/Results', $data);
    }

    public function leaderboard(): Response
    {
        $teacher = Auth::user();

        $studentIds = $teacher->isSuperadmin()
            ? null
            : $teacher->students()->pluck('users.id');

        $students = User::where('role', 'student')
            ->when($studentIds, fn ($q) => $q->whereIn('id', $studentIds))
            ->with('section:id,name', 'gradeLevel:id,name')
            ->orderByDesc('total_points')
            ->get(['id', 'name', 'grade', 'grade_level_id', 'section_id', 'total_points', 'profile_photo_updated_at', 'updated_at']);

        $weekPts = StudentPoint::whereIn('student_id', $students->pluck('id'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('student_id')
            ->selectRaw('student_id, SUM(points) as pts')
            ->pluck('pts', 'student_id');

        $entries = $students->map(function ($student) use ($weekPts) {
            return [
                'id' => $student->id,
                'name' => $student->name,
                'avatar' => $student->avatar,
                'grade' => $student->grade,
                'grade_level_name' => $student->gradeLevel?->name,
                'section_name' => $student->section?->name,
                'section_id' => $student->section_id,
                'grade_level_id' => $student->grade_level_id,
                'total_points' => (int) $student->total_points,
                'week_points' => (int) ($weekPts[$student->id] ?? 0),
            ];
        });

        $groups = $entries->groupBy('section_id')->map(function ($sectionEntries, $sectionId) {
            $sorted = $sectionEntries->sortByDesc('total_points')->values();

            return [
                'section_id' => (int) $sectionId,
                'section_name' => $sorted->first()['section_name'] ?? 'Unknown',
                'grade_level_name' => $sorted->first()['grade_level_name'] ?? '',
                'entries' => $sorted->map(fn ($e, $i) => array_merge($e, ['rank' => $i + 1])),
            ];
        })->values();

        $sections = Section::whereIn('id', $students->pluck('section_id')->filter())
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id']);

        $gradeLevels = $students->pluck('gradeLevel')
            ->filter()
            ->unique('id')
            ->sortBy('display_order')
            ->values();

        return Inertia::render('Teacher/Leaderboard', [
            'entries' => $entries->sortByDesc('total_points')->values(),
            'groups' => $groups,
            'sections' => $sections,
            'gradeLevels' => $gradeLevels,
        ]);
    }

    public function destroyAttempt(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:Quiz,Seatwork,Practical,Exam,Game'],
            'id' => ['required', 'integer'],
        ]);

        $teacher = Auth::user();

        if ($data['type'] === 'Game') {
            $progress = GameProgress::with('game')->findOrFail($data['id']);
            abort_unless($teacher->isSuperadmin() || $progress->game?->teacher_id === $teacher->id, 403);

            DB::table('game_correct_answers')
                ->where('user_id', $progress->user_id)
                ->where('game_id', $progress->game_id)
                ->delete();

            $pointsQuery = StudentPoint::where('student_id', $progress->user_id)
                ->where('activity_type', ActivityType::Game->value)
                ->where('activity_id', $progress->game_id);

            $deductedXp = (int) $pointsQuery->sum('points');
            $pointsQuery->delete();

            $studentCurrent = (int) DB::table('users')->where('id', $progress->user_id)->value('total_points');
            $sumFromPoints = (int) StudentPoint::where('student_id', $progress->user_id)->sum('points');
            $newTotal = max(0, $studentCurrent - $deductedXp);
            if ($studentCurrent === ($sumFromPoints + $deductedXp)) {
                $newTotal = $sumFromPoints;
            }

            DB::table('users')->where('id', $progress->user_id)->update(['total_points' => $newTotal]);

            $progress->delete();

            $msg = $deductedXp > 0
                ? "Game attempt deleted. {$deductedXp} XP deducted. Student can replay."
                : 'Game attempt deleted. Student can replay.';

            return redirect()->back()->with('success', $msg);
        }

        $modelMap = [
            'Quiz' => QuizAttempt::class,
            'Seatwork' => SeatworkAttempt::class,
            'Practical' => PracticalAttempt::class,
            'Exam' => ExamAttempt::class,
        ];

        $teacher = Auth::user();
        $modelClass = $modelMap[$data['type']];
        $attempt = $modelClass::findOrFail($data['id']);

        $activityRel = strtolower($data['type']);
        $attempt->load($activityRel);
        $attemptActivity = $attempt->$activityRel;
        abort_unless($teacher->isSuperadmin() || $attemptActivity?->teacher_id === $teacher->id, 403);

        if ($attempt instanceof PracticalAttempt) {
            $attempt->scores()->delete();
        }

        $attempt->delete();

        return redirect()->back()->with('success', 'Attempt deleted. Student can retake.');
    }

    public function destroyAttemptsBulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', 'in:Quiz,Seatwork,Practical,Exam,Game'],
            'items.*.id' => ['required', 'integer'],
        ]);

        $teacher = Auth::user();
        $count = 0;

        $modelMap = [
            'Quiz' => QuizAttempt::class,
            'Seatwork' => SeatworkAttempt::class,
            'Practical' => PracticalAttempt::class,
            'Exam' => ExamAttempt::class,
        ];

        foreach ($data['items'] as $item) {
            $type = $item['type'];
            $id = (int) $item['id'];

            try {
                if ($type === 'Game') {
                    $progress = GameProgress::with('game')->find($id);
                    if (! $progress || (! $teacher->isSuperadmin() && $progress->game?->teacher_id !== $teacher->id)) {
                        continue;
                    }

                    DB::table('game_correct_answers')
                        ->where('user_id', $progress->user_id)
                        ->where('game_id', $progress->game_id)
                        ->delete();

                    $pointsQuery = StudentPoint::where('student_id', $progress->user_id)
                        ->where('activity_type', 'game')
                        ->where('activity_id', $progress->game_id);
                    $deductedXp = (int) $pointsQuery->sum('points');
                    $pointsQuery->delete();

                    $studentCurrent = (int) DB::table('users')->where('id', $progress->user_id)->value('total_points');
                    $sumFromPoints = (int) StudentPoint::where('student_id', $progress->user_id)->sum('points');
                    $newTotal = max(0, $studentCurrent - $deductedXp);
                    if ($studentCurrent === ($sumFromPoints + $deductedXp)) {
                        $newTotal = $sumFromPoints;
                    }
                    DB::table('users')->where('id', $progress->user_id)->update(['total_points' => $newTotal]);
                    $progress->delete();
                    $count++;
                } elseif (isset($modelMap[$type])) {
                    $modelClass = $modelMap[$type];
                    $attempt = $modelClass::find($id);
                    if (! $attempt) {
                        continue;
                    }
                    $activityRel = strtolower($type);
                    $attempt->load($activityRel);
                    $attemptActivity = $attempt->$activityRel;
                    if (! $teacher->isSuperadmin() && $attemptActivity?->teacher_id !== $teacher->id) {
                        continue;
                    }

                    if ($attempt instanceof PracticalAttempt) {
                        $attempt->scores()->delete();
                    }
                    $attempt->delete();
                    $count++;
                }
            } catch (\Throwable $e) {
                // Continue with next attempt
            }
        }

        return redirect()->back()->with('success', "{$count} attempt(s) deleted. Students can retake.");
    }

    public function autoRecheckBulk(Request $request): RedirectResponse
    {
        @set_time_limit(180);

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', 'in:Quiz,Seatwork,Practical,Exam'],
            'items.*.id' => ['required', 'integer'],
        ]);

        $teacher = Auth::user();
        $count = 0;

        foreach ($data['items'] as $item) {
            $type = $item['type'];
            $id = (int) $item['id'];

            try {
                if ($type === 'Quiz') {
                    $attempt = QuizAttempt::with('quiz')->find($id);
                    if (! $attempt || ! $attempt->quiz) {
                        continue;
                    }
                    if (! $teacher->isSuperadmin() && $attempt->quiz->teacher_id !== $teacher->id) {
                        continue;
                    }

                    $result = ActivityAiGraderService::gradeQuizAttempt($attempt);
                    DB::transaction(function () use ($attempt, $result) {
                        foreach ($result['answers'] as $ansData) {
                            $attempt->answers()->where('id', $ansData['id'])->update(['is_correct' => $ansData['is_correct']]);
                        }
                        $attempt->update(['score' => $result['total_score']]);
                    });
                    PointsHelper::award($attempt->student_id, 'Quiz', $attempt->quiz_id, $result['total_score'], $attempt->quiz->totalPoints());
                    $count++;
                } elseif ($type === 'Seatwork') {
                    $attempt = SeatworkAttempt::with('seatwork')->find($id);
                    if (! $attempt || ! $attempt->seatwork) {
                        continue;
                    }
                    if (! $teacher->isSuperadmin() && $attempt->seatwork->teacher_id !== $teacher->id) {
                        continue;
                    }

                    $result = ActivityAiGraderService::gradeSeatworkAttempt($attempt);
                    DB::transaction(function () use ($attempt, $result) {
                        foreach ($result['answers'] as $ansData) {
                            $attempt->answers()->where('id', $ansData['id'])->update([
                                'is_correct' => $ansData['is_correct'],
                                'feedback' => $ansData['feedback'] ?? null,
                                'points_awarded' => $ansData['points_awarded'] ?? null,
                            ]);
                        }
                        $attempt->update(['score' => $result['total_score']]);
                    });
                    $recordedAttempt = $attempt->seatwork->recalculateRecordedAttempts($attempt->student_id) ?: $attempt;
                    PointsHelper::award($attempt->student_id, ActivityType::Seatwork->value, $attempt->seatwork_id, $recordedAttempt->score, $attempt->seatwork->totalPoints());
                    $count++;
                } elseif ($type === 'Practical') {
                    $attempt = PracticalAttempt::with('practical.criteria')->find($id);
                    if (! $attempt || ! $attempt->practical) {
                        continue;
                    }
                    if (! $teacher->isSuperadmin() && $attempt->practical->teacher_id !== $teacher->id) {
                        continue;
                    }

                    $result = ActivityAiGraderService::gradePracticalAttempt($attempt);
                    $totalScore = $result['total_score'] ?? 0;
                    DB::transaction(function () use ($attempt, $result, $totalScore) {
                        foreach ($attempt->practical->criteria as $criterion) {
                            $score = min((int) ($result['scores'][$criterion->id] ?? 0), $criterion->max_points);
                            $attempt->scores()->updateOrCreate(
                                ['criterion_id' => $criterion->id],
                                ['score' => $score, 'comment' => $result['comments'][$criterion->id] ?? '']
                            );
                        }
                        $attempt->update(['total_score' => $totalScore]);
                    });
                    PointsHelper::award($attempt->student_id, ActivityType::Practical->value, $attempt->practical_id, $totalScore, $attempt->practical->max_score ?? 0);
                    $count++;
                } elseif ($type === 'Exam') {
                    $attempt = ExamAttempt::with('exam.sections.criteria', 'answers')->find($id);
                    if (! $attempt || ! $attempt->exam) {
                        continue;
                    }
                    if (! $teacher->isSuperadmin() && $attempt->exam->teacher_id !== $teacher->id) {
                        continue;
                    }

                    $result = ActivityAiGraderService::gradeExamPracticalAttempt($attempt);
                    $totalPractical = 0;
                    DB::transaction(function () use ($attempt, $result, &$totalPractical) {
                        foreach ($attempt->exam->sections as $section) {
                            if ($section->section_type !== 'practical') {
                                continue;
                            }
                            foreach ($section->criteria as $criterion) {
                                $score = min((int) ($result['scores'][$criterion->id] ?? 0), $criterion->max_points);
                                $totalPractical += $score;
                                $attempt->sectionScores()->updateOrCreate(
                                    ['exam_criterion_id' => $criterion->id],
                                    [
                                        'exam_section_id' => $section->id,
                                        'score' => $score,
                                        'comment' => $result['comments'][$criterion->id] ?? '',
                                    ]
                                );
                            }
                        }
                        $autoScore = (int) $attempt->answers()->where('is_correct', true)->sum('points_earned');
                        $attempt->update(['total_score' => $autoScore + $totalPractical]);
                    });
                    $totalScore = (int) $attempt->fresh()->total_score;
                    PointsHelper::award($attempt->student_id, ActivityType::Exam->value, $attempt->exam_id, $totalScore, $attempt->exam->max_score ?? 0);
                    $count++;
                }
            } catch (\Throwable $e) {
                // Continue with next attempt on error
            }
        }

        return back()->with('success', "AI successfully evaluated {$count} attempt(s).");
    }
}
