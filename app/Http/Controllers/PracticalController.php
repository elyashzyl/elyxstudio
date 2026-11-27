<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\Quarter;
use App\Models\SchoolYear;
use App\Models\PracticalAttempt;
use App\Models\PracticalScore;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Helpers\PointsHelper;
use App\Services\ActivityAiGraderService;
use App\Services\SecureFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PracticalController extends Controller
{
    public function index(): Response
    {
        $teacher = Auth::user();

        $practicalQuery = $teacher->isSuperadmin()
            ? Practical::withCount('criteria')->withCount('attempts')->with('teacher:id,name', 'gradeLevels:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest()
            : $teacher->practicals()->getQuery()->withCount('criteria')->withCount('attempts')->with('gradeLevels:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest();
        SchoolYear::applyFilter($practicalQuery);
        $practicals = $practicalQuery->get();

        $gradeLevels = GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Teacher/Practicals/Index', [
            'practicals' => $practicals,
            'isSuperadmin' => $teacher->isSuperadmin(),
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'gradeLevels' => $gradeLevels,
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)->orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name']),
            'defaultSchoolYearId' => SchoolYear::defaultIdForCreate(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->has('grade_level_id') && ! $request->has('grade_level_ids')) {
            $request->merge(['grade_level_ids' => [$request->input('grade_level_id')]]);
        } elseif ($request->has('grade_level_ids') && ! is_array($request->input('grade_level_ids'))) {
            $val = $request->input('grade_level_ids');
            $request->merge(['grade_level_ids' => is_string($val) ? array_filter(explode(',', $val)) : array_filter([$val])]);
        } elseif (! $request->has('grade_level_ids') && $request->filled('grade')) {
            $glId = GradeLevel::where('name', $request->input('grade'))->value('id');
            if ($glId) {
                $request->merge(['grade_level_ids' => [$glId]]);
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'instructions' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_score' => ['nullable', 'integer', 'min:1'],
            'xp_reward' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.criterion_name' => ['required', 'string', 'max:255'],
            'criteria.*.description' => ['nullable', 'string'],
            'criteria.*.max_points' => ['required', 'integer', 'min:1'],
        ], [
            'grade_level_ids.required' => 'The grade level field is required.',
            'grade_level_ids.min' => 'Please select at least one grade level.',
        ], [
            'grade_level_ids' => 'grade level',
        ]);

        DB::transaction(function () use ($data) {
            $teacherId = Auth::user()->isSuperadmin() && $data['teacher_id'] ? $data['teacher_id'] : Auth::id();

            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])
                ->orderBy('display_order')
                ->value('name');

            $practical = Practical::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'grade' => $grade,
                'quarter_id' => $data['quarter_id'] ?? null,
                'school_year_id' => $data['school_year_id'] ?? SchoolYear::defaultIdForCreate(),
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'max_score' => $data['max_score'] ?? (int) Setting::get('practical_max_score', config('gamification.practical_max_score', 100)),
                'xp_reward' => $data['xp_reward'] ?? (int) config('gamification.practical_xp_reward', 10),
                'max_attempts' => $data['max_attempts'] ?? (int) Setting::get('practical_max_attempts', config('gamification.practical_max_attempts', 3)),
                'is_published' => false,
            ]);

            $practical->gradeLevels()->sync($data['grade_level_ids']);

            foreach ($data['criteria'] as $cIndex => $criterion) {
                $practical->criteria()->create([
                    'criterion_name' => $criterion['criterion_name'],
                    'description' => $criterion['description'] ?? null,
                    'max_points' => $criterion['max_points'],
                    'order' => $cIndex,
                ]);
            }
        });

        return redirect()->route('teacher.practicals.index')->with('success', 'Practical created successfully.');
    }

    public function edit(Practical $practical): Response
    {
        $this->authorizeOwner($practical);
        $practical->load('criteria', 'gradeLevels:id,name');

        $teacher = Auth::user();
        $gradeLevels = GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Teacher/Practicals/Edit', [
            'practical' => $practical,
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'gradeLevels' => $gradeLevels,
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)->orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name']),
            'defaultSchoolYearId' => SchoolYear::defaultIdForCreate(),
        ]);
    }

    public function update(Request $request, Practical $practical): RedirectResponse
    {
        $this->authorizeOwner($practical);

        if ($request->has('grade_level_id') && ! $request->has('grade_level_ids')) {
            $request->merge(['grade_level_ids' => [$request->input('grade_level_id')]]);
        } elseif ($request->has('grade_level_ids') && ! is_array($request->input('grade_level_ids'))) {
            $val = $request->input('grade_level_ids');
            $request->merge(['grade_level_ids' => is_string($val) ? array_filter(explode(',', $val)) : array_filter([$val])]);
        } elseif (! $request->has('grade_level_ids') && $request->filled('grade')) {
            $glId = GradeLevel::where('name', $request->input('grade'))->value('id');
            if ($glId) {
                $request->merge(['grade_level_ids' => [$glId]]);
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'instructions' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_score' => ['nullable', 'integer', 'min:1'],
            'xp_reward' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.criterion_name' => ['required', 'string', 'max:255'],
            'criteria.*.description' => ['nullable', 'string'],
            'criteria.*.max_points' => ['required', 'integer', 'min:1'],
        ], [
            'grade_level_ids.required' => 'The grade level field is required.',
            'grade_level_ids.min' => 'Please select at least one grade level.',
        ], [
            'grade_level_ids' => 'grade level',
        ]);

        DB::transaction(function () use ($data, $practical) {
            $updateData = [
                'title' => $data['title'],
                'quarter_id' => array_key_exists('quarter_id', $data) ? $data['quarter_id'] : $practical->quarter_id,
                'school_year_id' => array_key_exists('school_year_id', $data) ? $data['school_year_id'] : $practical->school_year_id,
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'max_score' => $data['max_score'] ?? (int) config('gamification.practical_max_score'),
                'xp_reward' => $data['xp_reward'] ?? (int) config('gamification.practical_xp_reward', 10),
                'max_attempts' => $data['max_attempts'] ?? (int) config('gamification.practical_max_attempts'),
            ];

            if (Auth::user()->isSuperadmin() && $data['teacher_id']) {
                $updateData['teacher_id'] = $data['teacher_id'];
            }

            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])
                ->orderBy('display_order')
                ->value('name');
            $practical->grade = $grade;

            $practical->update($updateData);
            $practical->gradeLevels()->sync($data['grade_level_ids']);

            $existingCriteria = $practical->criteria()->orderBy('order')->get();
            $keepIds = [];

            foreach ($data['criteria'] as $cIndex => $criterion) {
                if (isset($existingCriteria[$cIndex])) {
                    $existingCriteria[$cIndex]->update([
                        'criterion_name' => $criterion['criterion_name'],
                        'description' => $criterion['description'] ?? null,
                        'max_points' => $criterion['max_points'],
                        'order' => $cIndex,
                    ]);
                    $keepIds[] = $existingCriteria[$cIndex]->id;
                } else {
                    $new = $practical->criteria()->create([
                        'criterion_name' => $criterion['criterion_name'],
                        'description' => $criterion['description'] ?? null,
                        'max_points' => $criterion['max_points'],
                        'order' => $cIndex,
                    ]);
                    $keepIds[] = $new->id;
                }
            }

            $toDelete = $practical->criteria()->whereNotIn('id', $keepIds)->get();
            if ($toDelete->isNotEmpty()) {
                $deleteIds = $toDelete->pluck('id');
                PracticalScore::whereIn('criterion_id', $deleteIds)->delete();
                $practical->criteria()->whereIn('id', $deleteIds)->delete();
            }
        });

        return redirect()->route('teacher.practicals.show', $practical)->with('success', 'Practical updated successfully.');
    }

    public function show(Practical $practical): Response
    {
        $this->authorizeOwner($practical);
        $practical->load('criteria', 'gradeLevels:id,name');

        $teacher = Auth::user();

        $attempts = $practical->attempts()
            ->with('student:id,name,grade,section_id,gender', 'student.section:id,name', 'scores')
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->get()
            ->map(function ($a) use ($practical) {
                $attemptNum = PracticalAttempt::where('practical_id', $practical->id)
                    ->where('student_id', $a->student_id)
                    ->where('id', '<=', $a->id)
                    ->count();
                $a->attempt_number = $attemptNum;
                return $a;
            });

        $gradeLevelIds = $practical->gradeLevels->pluck('id')->all();
        $sections = Section::where('is_active', true)
            ->when(! empty($gradeLevelIds), fn ($q) => $q->whereIn('grade_level_id', $gradeLevelIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Teacher/Practicals/Show', [
            'practical' => $practical,
            'attempts' => $attempts,
            'sections' => $sections,
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function publish(Practical $practical): RedirectResponse
    {
        $this->authorizeOwner($practical);
        if ($practical->is_published) {
            $practical->update(['is_published' => false, 'closes_at' => null]);
            $msg = 'Practical unpublished.';
        } else {
            $practical->update(['is_published' => true, 'closes_at' => now()->addDay()]);
            $msg = 'Practical published. It will close in 24 hours.';
        }
        return back()->with('success', $msg);
    }

    public function reopen(Practical $practical): RedirectResponse
    {
        $this->authorizeOwner($practical);
        $practical->update(['closes_at' => now()->addDay()]);
        return back()->with('success', 'Practical reopened. It will close in 24 hours.');
    }

    public function closeNow(Practical $practical): RedirectResponse
    {
        $this->authorizeOwner($practical);
        $practical->update(['closes_at' => now()->subSecond()]);
        return back()->with('success', 'Practical closed immediately.');
    }

    public function closeAttempt(Practical $practical, PracticalAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($practical);
        abort_if($attempt->practical_id !== $practical->id, 404);
        $attempt->update(['closed_at' => now()]);
        return back()->with('success', 'Attempt closed for this student.');
    }

    public function destroy(Practical $practical): RedirectResponse
    {
        $this->authorizeOwner($practical);
        $practical->delete();
        return redirect()->route('teacher.practicals.index')->with('success', 'Practical deleted.');
    }

    public function destroyAttempt(Practical $practical, PracticalAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($practical);
        abort_if($attempt->practical_id !== $practical->id, 404);
        $attempt->scores()->delete();
        $attempt->delete();
        return redirect()->back()->with('success', 'Attempt deleted. Student can retake the practical.');
    }

    public function take(Practical $practical): Response|RedirectResponse
    {
        $student = Auth::user();
        abort_unless($practical->is_published, 403);
        abort_if($practical->isClosed(), 403, 'This practical has closed.');
        $this->authorizeGrade($practical, $student);

        $submittedCount = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $student->id)
            ->where('status', \App\Enums\AttemptStatus::Submitted->value)->count();

        if ($submittedCount >= $practical->max_attempts) {
            $latest = PracticalAttempt::where('practical_id', $practical->id)
                ->where('student_id', $student->id)
                ->latest()
                ->first();
            if ($latest) {
                return redirect()->route('student.practicals.result', ['attempt' => $latest->id])
                    ->with('info', 'You have reached the maximum number of attempts for this practical.');
            }
            return redirect()->route('student.dashboard')
                ->with('info', 'You have reached the maximum number of attempts for this practical.');
        }

        $hasClosed = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $student->id)
            ->whereNotNull('closed_at')->exists();

        abort_if($hasClosed, 403, 'Your submission has been closed by the teacher.');

        $existing = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $student->id)
            ->where('status', \App\Enums\AttemptStatus::InProgress->value)
            ->first();

        if (!$existing) {
            $existing = PracticalAttempt::create([
                'practical_id' => $practical->id, 'student_id' => $student->id,
                'status' => \App\Enums\AttemptStatus::InProgress->value, 'started_at' => now(),
            ]);
        }

        $practical->load('criteria');

        $totalAttempts = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $student->id)->count();

        $homeworkPassCount = count(\App\Services\RewardService::availableOfType($student, 'homework_pass'));

        return Inertia::render('Student/Practical/Take', [
            'practical' => $practical,
            'startedAt' => $existing->started_at,
            'deadlineAt' => $existing->started_at?->copy()->addMinutes($practical->time_limit_minutes)->timestamp * 1000,
            'attemptNumber' => $totalAttempts,
            'hasHomeworkPass' => $homeworkPassCount > 0,
            'homeworkPassCount' => $homeworkPassCount,
        ]);
    }

    public function submit(Request $request, Practical $practical): RedirectResponse
    {
        $student = Auth::user();
        abort_unless($practical->is_published, 403);
        abort_if($practical->isClosed(), 403, 'This practical has closed.');
        $this->authorizeGrade($practical, $student);

        $attempt = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $student->id)
            ->latest()
            ->firstOrFail();

        if ($attempt->status === \App\Enums\AttemptStatus::Submitted->value) {
            return redirect()->route('student.practicals.result', ['attempt' => $attempt->id], 303)
                ->with('info', 'This practical was already submitted.');
        }

        $data = $request->validate([
            'submission_text' => ['nullable', 'string'],
            'submission_file' => ['nullable', 'file', 'mimes:png,jpg,jpeg,gif,webp,bmp'],
        ]);

        $filePath = null;
        if ($request->hasFile('submission_file')) {
            $filePath = SecureFileService::store($request->file('submission_file'), 'practical-submissions');
        }

        $attempt->update([
            'submission_text' => $data['submission_text'] ?? null,
            'submission_file' => $filePath,
            'status' => \App\Enums\AttemptStatus::Submitted->value,
            'submitted_at' => now(),
        ]);

        \App\Services\ActivityLogger::log(
            'practical_submitted',
            "Student {$student->name} submitted practical '{$practical->title}'",
            $practical,
            $student
        );

        return redirect()->route('student.practicals.result', ['attempt' => $attempt->id], 303)
            ->with('success', 'Practical submitted.');
    }

    public function result(PracticalAttempt $attempt): Response
    {
        $student = Auth::user();
        abort_if($attempt->student_id !== $student->id, 403);
        $attempt->load(['practical.criteria', 'scores']);

        return Inertia::render('Student/Practical/Result', ['attempt' => $attempt]);
    }

    public function close(PracticalAttempt $attempt): RedirectResponse
    {
        $student = Auth::user();
        abort_if($attempt->student_id !== $student->id, 403);
        abort_if($attempt->status !== \App\Enums\AttemptStatus::Submitted->value, 403);
        abort_if($attempt->closed_at, 403);

        $attempt->update(['closed_at' => now()]);

        return redirect()->route('student.practicals.result', ['attempt' => $attempt->id])
            ->with('success', 'Submission closed. You can no longer retake this practical.');
    }

    public function grade(Practical $practical, PracticalAttempt $attempt): Response
    {
        $this->authorizeOwner($practical);
        abort_if($attempt->practical_id !== $practical->id, 404);

        $attempt->load(['student:id,name,grade', 'practical.criteria', 'scores.criterion']);
        $practical->load('criteria');

        $attempts = $practical->attempts()
            ->with('student:id,name,grade')
            ->where(fn ($q) => $q->whereIn('status', ['submitted', 'closed'])->orWhere('id', $attempt->id))
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->latest('id')
            ->get(['id', 'practical_id', 'student_id', 'total_score', 'submitted_at']);

        return Inertia::render('Teacher/Practicals/Grade', [
            'practical' => $practical,
            'attempt' => $attempt,
            'attempts' => $attempts,
            'returnTo' => request('return_to'),
        ]);
    }

    public function gradeUpdate(Request $request, Practical $practical, PracticalAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($practical);
        abort_if($attempt->practical_id !== $practical->id, 404);

        $data = $request->validate([
            'scores' => ['present', 'array'],
            'scores.*' => ['nullable', 'integer', 'min:0'],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string', 'max:1000'],
            'next_attempt_id' => ['nullable', 'integer'],
            'save_and_next' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string'],
        ]);

        $practical->load('criteria');
        $totalScore = 0;

        DB::transaction(function () use ($data, $practical, $attempt, &$totalScore) {
            foreach ($practical->criteria as $criterion) {
                $score = min((int) ($data['scores'][$criterion->id] ?? 0), $criterion->max_points);
                $totalScore += $score;

                $attempt->scores()->updateOrCreate(
                    ['criterion_id' => $criterion->id],
                    ['score' => $score, 'comment' => $data['comments'][$criterion->id] ?? '']
                );
            }

            $attempt->update(['total_score' => $totalScore]);
        });

        PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Practical->value, $practical->id, $totalScore, $practical->max_score ?? 0);

        $returnTo = $request->input('return_to');
        $nextAttemptId = $request->input('next_attempt_id');

        if (! $nextAttemptId && $request->boolean('save_and_next')) {
            $allAttempts = $practical->attempts()
                ->where(fn ($q) => $q->whereIn('status', ['submitted', 'closed'])->orWhere('id', $attempt->id))
                ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
                ->latest('id')
                ->get(['id']);
            $currIdx = $allAttempts->search(fn ($a) => (int) $a->id === (int) $attempt->id);
            if ($currIdx !== false && $currIdx < $allAttempts->count() - 1) {
                $nextAttemptId = $allAttempts[$currIdx + 1]->id;
            }
        }

        $cleanAnchor = function (?string $url, $targetId): ?string {
            if (! $url) return null;
            if (preg_match('/#(result-attempt-[a-zA-Z]+-|result-attempt-|attempt-)\d+/', $url)) {
                return preg_replace_callback(
                    '/#(result-attempt-[a-zA-Z]+-|result-attempt-|attempt-)\d+.*$/',
                    fn ($m) => '#' . $m[1] . $targetId,
                    $url
                );
            }
            if (str_contains($url, '/results')) {
                return preg_replace('/#.*$/', '', $url) . '#result-attempt-' . $targetId;
            }
            return preg_replace('/#.*$/', '', $url) . '#attempt-' . $targetId;
        };

        if ($nextAttemptId) {
            $nextAttempt = $practical->attempts()->where('id', $nextAttemptId)->first();
            if ($nextAttempt) {
                $cleanReturnTo = $cleanAnchor($returnTo, $nextAttempt->id);
                return redirect()->route('teacher.practicals.grade', [
                    'practical' => $practical,
                    'attempt' => $nextAttempt,
                    'return_to' => $cleanReturnTo,
                ], 303)->with('success', 'Grades saved for ' . ($attempt->student->name ?? 'student') . '. Switched to next attempt.');
            }
        }

        if ($returnTo && (str_starts_with($returnTo, '/teacher/') || str_starts_with($returnTo, '/student/'))) {
            $finalReturn = $cleanAnchor($returnTo, $attempt->id);
            return redirect($finalReturn, 303)->with('saved', true)->with('success', 'Grades saved successfully.');
        }

        return redirect()->to(route('teacher.practicals.show', $practical) . '#attempt-' . $attempt->id, 303)
            ->with('saved', true);
    }

    public function recheck(Practical $practical, PracticalAttempt $attempt): Response
    {
        $this->authorizeOwner($practical);
        abort_if($attempt->practical_id !== $practical->id, 404);

        $attempt->load(['student:id,name,grade', 'practical.criteria', 'scores.criterion']);
        $practical->load('criteria');

        $attempts = $practical->attempts()
            ->with('student:id,name,grade')
            ->where(fn ($q) => $q->whereIn('status', ['submitted', 'closed'])->orWhere('id', $attempt->id))
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->latest('id')
            ->get(['id', 'practical_id', 'student_id', 'total_score', 'submitted_at']);

        return Inertia::render('Teacher/Practicals/Recheck', [
            'practical' => $practical,
            'attempt' => $attempt,
            'attempts' => $attempts,
            'returnTo' => request('return_to'),
        ]);
    }

    public function recheckUpdate(Request $request, Practical $practical, PracticalAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($practical);
        abort_if($attempt->practical_id !== $practical->id, 404);

        $data = $request->validate([
            'scores' => ['present', 'array'],
            'scores.*' => ['nullable', 'integer', 'min:0'],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string', 'max:1000'],
            'next_attempt_id' => ['nullable', 'integer'],
            'save_and_next' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string'],
        ]);

        $practical->load('criteria');
        $totalScore = 0;

        DB::transaction(function () use ($data, $practical, $attempt, &$totalScore) {
            foreach ($practical->criteria as $criterion) {
                $score = min((int) ($data['scores'][$criterion->id] ?? 0), $criterion->max_points);
                $totalScore += $score;

                $attempt->scores()->updateOrCreate(
                    ['criterion_id' => $criterion->id],
                    ['score' => $score, 'comment' => $data['comments'][$criterion->id] ?? '']
                );
            }
            $attempt->update(['total_score' => $totalScore]);
        });

        PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Practical->value, $practical->id, $totalScore, $practical->max_score ?? 0);

        $returnTo = $request->input('return_to');
        $nextAttemptId = $request->input('next_attempt_id');

        if (! $nextAttemptId && $request->boolean('save_and_next')) {
            $allAttempts = $practical->attempts()
                ->where(fn ($q) => $q->whereIn('status', ['submitted', 'closed'])->orWhere('id', $attempt->id))
                ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
                ->latest('id')
                ->get(['id']);
            $currIdx = $allAttempts->search(fn ($a) => (int) $a->id === (int) $attempt->id);
            if ($currIdx !== false && $currIdx < $allAttempts->count() - 1) {
                $nextAttemptId = $allAttempts[$currIdx + 1]->id;
            }
        }

        $cleanAnchor = function (?string $url, $targetId): ?string {
            if (! $url) return null;
            if (preg_match('/#(result-attempt-[a-zA-Z]+-|result-attempt-|attempt-)\d+/', $url)) {
                return preg_replace_callback(
                    '/#(result-attempt-[a-zA-Z]+-|result-attempt-|attempt-)\d+.*$/',
                    fn ($m) => '#' . $m[1] . $targetId,
                    $url
                );
            }
            if (str_contains($url, '/results')) {
                return preg_replace('/#.*$/', '', $url) . '#result-attempt-' . $targetId;
            }
            return preg_replace('/#.*$/', '', $url) . '#attempt-' . $targetId;
        };

        if ($nextAttemptId) {
            $nextAttempt = $practical->attempts()->where('id', $nextAttemptId)->first();
            if ($nextAttempt) {
                $cleanReturnTo = $cleanAnchor($returnTo, $nextAttempt->id);
                return redirect()->route('teacher.practicals.recheck', [
                    'practical' => $practical,
                    'attempt' => $nextAttempt,
                    'return_to' => $cleanReturnTo,
                ], 303)->with('success', 'Grades saved for ' . ($attempt->student->name ?? 'student') . '. Switched to next attempt.');
            }
        }

        if ($returnTo && (str_starts_with($returnTo, '/teacher/') || str_starts_with($returnTo, '/student/'))) {
            $finalReturn = $cleanAnchor($returnTo, $attempt->id);
            return redirect($finalReturn, 303)->with('saved', true)->with('success', 'Grades saved successfully.');
        }

        return redirect()->to(route('teacher.practicals.show', $practical) . '#attempt-' . $attempt->id, 303)
            ->with('saved', true);
    }

    public function aiGrade(Practical $practical, PracticalAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($practical);

        $attempt->load(['practical.criteria', 'student']);
        $result = ActivityAiGraderService::gradePracticalAttempt($attempt);

        return response()->json($result);
    }

    public function autoRecheck(Practical $practical, PracticalAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($practical);
        abort_if($attempt->practical_id !== $practical->id, 404);

        $attempt->load(['practical.criteria', 'student']);
        $result = ActivityAiGraderService::gradePracticalAttempt($attempt);
        $totalScore = $result['total_score'] ?? 0;

        DB::transaction(function () use ($practical, $attempt, $result, $totalScore) {
            foreach ($practical->criteria as $criterion) {
                $score = min((int) ($result['scores'][$criterion->id] ?? 0), $criterion->max_points);
                $attempt->scores()->updateOrCreate(
                    ['criterion_id' => $criterion->id],
                    ['score' => $score, 'comment' => $result['comments'][$criterion->id] ?? '']
                );
            }
            $attempt->update(['total_score' => $totalScore]);
        });

        PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Practical->value, $practical->id, $totalScore, $practical->max_score ?? 0);

        return back()->with('success', 'Practical AI-graded successfully based on criteria.');
    }

    public function aiGradeAll(Practical $practical): RedirectResponse
    {
        $this->authorizeOwner($practical);
        @set_time_limit(180);

        $practical->load('criteria');
        $attempts = $practical->attempts()
            ->whereIn('status', ['submitted', 'closed'])
            ->get();

        $count = 0;
        foreach ($attempts as $attempt) {
            $attempt->load(['practical.criteria', 'student']);
            $result = ActivityAiGraderService::gradePracticalAttempt($attempt);
            $totalScore = $result['total_score'] ?? 0;

            DB::transaction(function () use ($practical, $attempt, $result, $totalScore) {
                foreach ($practical->criteria as $criterion) {
                    $score = min((int) ($result['scores'][$criterion->id] ?? 0), $criterion->max_points);
                    $attempt->scores()->updateOrCreate(
                        ['criterion_id' => $criterion->id],
                        ['score' => $score, 'comment' => $result['comments'][$criterion->id] ?? '']
                    );
                }
                $attempt->update(['total_score' => $totalScore]);
            });

            PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Practical->value, $practical->id, $totalScore, $practical->max_score ?? 0);
            $count++;
        }

        return back()->with('success', "AI successfully graded {$count} practical attempt(s).");
    }

    public function reassign(Request $request, Practical $practical): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperadmin(), 403);

        $data = $request->validate(['teacher_id' => ['required', 'exists:users,id']]);
        $practical->update(['teacher_id' => $data['teacher_id']]);

        return back()->with('success', 'Practical reassigned to teacher.');
    }

    private function authorizeOwner(Practical $practical): void
    {
        if (Auth::user()->isSuperadmin()) return;
        abort_if($practical->teacher_id !== Auth::id(), 403);
    }

    private function authorizeGrade(Practical $practical, $student): void
    {
        if ($student->isTeacher() || $student->isSuperadmin()) return;

        $hasGrade = $practical->gradeLevels()->exists()
            ? ($student->grade_level_id && $practical->gradeLevels()->where('grade_level_id', $student->grade_level_id)->exists())
            : ($student->grade && $practical->grade === $student->grade);

        abort_unless($hasGrade, 403, 'This practical is not available to your account.');
    }
}
