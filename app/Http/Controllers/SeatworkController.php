<?php

namespace App\Http\Controllers;

use App\Models\Seatwork;
use App\Models\SeatworkAnswer;
use App\Models\SeatworkAttempt;
use App\Models\GradeLevel;
use App\Models\Quarter;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\User;
use App\Helpers\PointsHelper;
use App\Services\ActivityAiGraderService;
use App\Services\AnswerMatcherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SeatworkController extends Controller
{
    // ---- Teacher methods ----

    public function index(): Response
    {
        $teacher = Auth::user();

        $seatworkQuery = $teacher->isSuperadmin()
            ? Seatwork::withCount('questions')->withCount('attempts')->with('teacher:id,name', 'gradeLevels:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest()
            : $teacher->seatworks()->getQuery()->withCount('questions')->withCount('attempts')->with('gradeLevels:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest();
        SchoolYear::applyFilter($seatworkQuery);
        $seatworks = $seatworkQuery->get();

        $gradeLevels = GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']);
        $allSections = Section::where('is_active', true)->orderBy('name')->get(['id', 'name', 'grade_level_id']);

        return Inertia::render('Teacher/Seatworks/Index', [
            'seatworks' => $seatworks,
            'gradeLevels' => $gradeLevels,
            'allSections' => $allSections,
            'isSuperadmin' => $teacher->isSuperadmin(),
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
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
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'grading_policy' => ['nullable', 'string', 'in:first,second,third,highest,latest,manual'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:identification,enumeration,true_false,multiple_choice,matching,essay'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.criteria' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.enum_items' => ['nullable', 'array'],
            'questions.*.enum_items.*' => ['nullable', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.option_text' => ['nullable', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
            'questions.*.matching_pairs' => ['nullable', 'array'],
            'questions.*.matching_pairs.*.left_text' => ['nullable', 'string'],
            'questions.*.matching_pairs.*.right_text' => ['nullable', 'string'],
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

            $seatwork = Seatwork::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'grade' => $grade,
                'quarter_id' => $data['quarter_id'] ?? null,
                'school_year_id' => $data['school_year_id'] ?? SchoolYear::defaultIdForCreate(),
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'max_attempts' => $data['max_attempts'] ?? 1,
                'grading_policy' => $data['grading_policy'] ?? 'first',
                'is_published' => false,
            ]);

            \App\Services\ActivityLogger::log(
                'seatwork_created',
                "Created seatwork '{$seatwork->title}'",
                $seatwork
            );

            $seatwork->gradeLevels()->sync($data['grade_level_ids']);

            foreach ($data['questions'] as $qIndex => $question) {
                $created = $seatwork->questions()->create([
                    'question_text' => $question['question_text'],
                    'type' => $question['type'],
                    'points' => $question['points'],
                    'criteria' => $question['criteria'] ?? null,
                    'order' => $qIndex,
                ]);

                if ($question['type'] === 'identification') {
                    $created->options()->create([
                        'option_text' => $question['correct_answer'] ?? '',
                        'is_correct' => true,
                        'order' => 0,
                    ]);
                }

                if ($question['type'] === 'enumeration') {
                    foreach ($question['enum_items'] ?? [] as $eIndex => $item) {
                        if (trim($item ?? '') === '') continue;
                        $created->options()->create([
                            'option_text' => $item,
                            'is_correct' => true,
                            'order' => $eIndex,
                        ]);
                    }
                }

                if (in_array($question['type'], ['true_false', 'multiple_choice'])) {
                    foreach ($question['options'] ?? [] as $oIndex => $option) {
                        if (!isset($option['option_text']) || trim($option['option_text']) === '') continue;
                        $created->options()->create([
                            'option_text' => $option['option_text'],
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                            'order' => $oIndex,
                        ]);
                    }
                }

                if ($question['type'] === 'matching') {
                    foreach ($question['matching_pairs'] ?? [] as $mIndex => $pair) {
                        if (empty($pair['left_text']) && empty($pair['right_text'])) continue;
                        $created->matchingPairs()->create([
                            'left_text' => $pair['left_text'] ?? '',
                            'right_text' => $pair['right_text'] ?? '',
                            'order' => $mIndex,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('teacher.seatworks.index')->with('success', 'Seatwork created successfully.');
    }

    public function edit(Seatwork $seatwork): Response
    {
        $this->authorizeOwner($seatwork);
        $seatwork->load(['questions.options', 'questions.matchingPairs', 'gradeLevels:id,name']);

        $teacher = Auth::user();
        $gradeLevels = GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Teacher/Seatworks/Edit', [
            'seatwork' => $seatwork,
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'gradeLevels' => $gradeLevels,
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)->orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name']),
            'defaultSchoolYearId' => SchoolYear::defaultIdForCreate(),
        ]);
    }

    public function update(Request $request, Seatwork $seatwork): RedirectResponse
    {
        $this->authorizeOwner($seatwork);

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
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'grading_policy' => ['nullable', 'string', 'in:first,second,third,highest,latest,manual'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:identification,enumeration,true_false,multiple_choice,matching,essay'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.criteria' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.enum_items' => ['nullable', 'array'],
            'questions.*.enum_items.*' => ['nullable', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.option_text' => ['nullable', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
            'questions.*.matching_pairs' => ['nullable', 'array'],
            'questions.*.matching_pairs.*.left_text' => ['nullable', 'string'],
            'questions.*.matching_pairs.*.right_text' => ['nullable', 'string'],
        ], [
            'grade_level_ids.required' => 'The grade level field is required.',
            'grade_level_ids.min' => 'Please select at least one grade level.',
        ], [
            'grade_level_ids' => 'grade level',
        ]);

        $policyChanged = array_key_exists('grading_policy', $data) && $seatwork->grading_policy !== ($data['grading_policy'] ?? 'first');

        DB::transaction(function () use ($data, $seatwork) {
            $updateData = [
                'title' => $data['title'],
                'quarter_id' => array_key_exists('quarter_id', $data) ? $data['quarter_id'] : $seatwork->quarter_id,
                'school_year_id' => array_key_exists('school_year_id', $data) ? $data['school_year_id'] : $seatwork->school_year_id,
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'max_attempts' => $data['max_attempts'] ?? 1,
                'grading_policy' => $data['grading_policy'] ?? 'first',
            ];

            if (Auth::user()->isSuperadmin() && $data['teacher_id']) {
                $updateData['teacher_id'] = $data['teacher_id'];
            }

            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])
                ->orderBy('display_order')
                ->value('name');
            $seatwork->grade = $grade;

            $seatwork->update($updateData);
            $seatwork->gradeLevels()->sync($data['grade_level_ids']);

            $seatwork->questions()->delete();

            foreach ($data['questions'] as $qIndex => $question) {
                $created = $seatwork->questions()->create([
                    'question_text' => $question['question_text'],
                    'type' => $question['type'],
                    'points' => $question['points'],
                    'criteria' => $question['criteria'] ?? null,
                    'order' => $qIndex,
                ]);

                if ($question['type'] === 'identification') {
                    $created->options()->create([
                        'option_text' => $question['correct_answer'] ?? '',
                        'is_correct' => true,
                        'order' => 0,
                    ]);
                }

                if ($question['type'] === 'enumeration') {
                    foreach ($question['enum_items'] ?? [] as $eIndex => $item) {
                        if (trim($item ?? '') === '') continue;
                        $created->options()->create([
                            'option_text' => $item,
                            'is_correct' => true,
                            'order' => $eIndex,
                        ]);
                    }
                }

                if (in_array($question['type'], ['true_false', 'multiple_choice'])) {
                    foreach ($question['options'] ?? [] as $oIndex => $option) {
                        if (!isset($option['option_text']) || trim($option['option_text']) === '') continue;
                        $created->options()->create([
                            'option_text' => $option['option_text'],
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                            'order' => $oIndex,
                        ]);
                    }
                }

                if ($question['type'] === 'matching') {
                    foreach ($question['matching_pairs'] ?? [] as $mIndex => $pair) {
                        if (empty($pair['left_text']) && empty($pair['right_text'])) continue;
                        $created->matchingPairs()->create([
                            'left_text' => $pair['left_text'] ?? '',
                            'right_text' => $pair['right_text'] ?? '',
                            'order' => $mIndex,
                        ]);
                    }
                }
            }
        });

        if ($policyChanged) {
            $seatwork->recalculateAllStudentsRecordedAttempts();
        }

        return redirect()->route('teacher.seatworks.show', $seatwork)->with('success', 'Seatwork updated successfully.');
    }

    public function show(Seatwork $seatwork): Response
    {
        $this->authorizeOwner($seatwork);
        $seatwork->load(['questions.options', 'questions.matchingPairs', 'gradeLevels:id,name']);
        $teacher = Auth::user();
        $attempts = $seatwork->attempts()
            ->with('student:id,name,grade,section_id,gender', 'student.section:id,name')
            ->orderByRaw("CASE WHEN status = 'submitted' THEN 0 ELSE 1 END")
            ->orderBy('attempt_number', 'asc')
            ->get();

        $gradeLevelIds = $seatwork->gradeLevels->pluck('id')->all();
        $sections = Section::where('is_active', true)
            ->when(!empty($gradeLevelIds), fn ($q) => $q->whereIn('grade_level_id', $gradeLevelIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Teacher/Seatworks/Show', [
            'seatwork' => $seatwork,
            'attempts' => $attempts,
            'sections' => $sections,
            'totalPoints' => $seatwork->totalPoints(),
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function publish(Seatwork $seatwork): RedirectResponse
    {
        $this->authorizeOwner($seatwork);
        if ($seatwork->is_published) {
            $seatwork->update(['is_published' => false, 'closes_at' => null]);
            $msg = 'Seatwork unpublished.';
        } else {
            $seatwork->update(['is_published' => true, 'closes_at' => now()->addDay()]);
            $msg = 'Seatwork published. It will close in 24 hours.';
        }
        return back()->with('success', $msg);
    }

    public function reopen(Seatwork $seatwork): RedirectResponse
    {
        $this->authorizeOwner($seatwork);
        $seatwork->update(['closes_at' => now()->addDay()]);
        return back()->with('success', 'Seatwork reopened. It will close in 24 hours.');
    }

    public function closeNow(Seatwork $seatwork): RedirectResponse
    {
        $this->authorizeOwner($seatwork);
        $seatwork->update(['closes_at' => now()->subSecond()]);
        return back()->with('success', 'Seatwork closed immediately.');
    }

    public function destroy(Seatwork $seatwork): RedirectResponse
    {
        $this->authorizeOwner($seatwork);
        $seatwork->delete();
        return redirect()->route('teacher.seatworks.index')->with('success', 'Seatwork deleted.');
    }

    // ---- Student methods ----

    public function take(Seatwork $seatwork): Response|RedirectResponse
    {
        $student = Auth::user();
        abort_unless($seatwork->is_published, 403);
        $this->authorizeGrade($seatwork, $student);

        // Check for an in-progress attempt first
        $existing = SeatworkAttempt::where('seatwork_id', $seatwork->id)
            ->where('student_id', $student->id)
            ->where('status', \App\Enums\AttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if ($seatwork->isClosed()) {
            $latestSubmitted = SeatworkAttempt::where('seatwork_id', $seatwork->id)
                ->where('student_id', $student->id)
                ->where('status', \App\Enums\AttemptStatus::Submitted->value)
                ->latest('id')
                ->first();

            if ($latestSubmitted) {
                return redirect()->route('student.seatworks.result', ['attempt' => $latestSubmitted->id])
                    ->with('info', 'This seatwork has closed.');
            }

            abort(403, 'This seatwork has closed.');
        }

        if (! $existing) {
            $submittedCount = SeatworkAttempt::where('seatwork_id', $seatwork->id)
                ->where('student_id', $student->id)
                ->where('status', \App\Enums\AttemptStatus::Submitted->value)
                ->count();

            $maxAttempts = $seatwork->max_attempts ?? 1;

            if ($maxAttempts > 0 && $submittedCount >= $maxAttempts) {
                $recorded = SeatworkAttempt::where('seatwork_id', $seatwork->id)
                    ->where('student_id', $student->id)
                    ->where('is_recorded', true)
                    ->first()
                    ?: SeatworkAttempt::where('seatwork_id', $seatwork->id)
                        ->where('student_id', $student->id)
                        ->latest('id')
                        ->first();

                if ($recorded) {
                    return redirect()->route('student.seatworks.result', ['attempt' => $recorded->id])
                        ->with('info', 'You have reached the maximum number of attempts for this seatwork.');
                }

                return redirect()->route('student.seatworks.index')
                    ->with('info', 'You have reached the maximum number of attempts for this seatwork.');
            }

            $existing = SeatworkAttempt::create([
                'seatwork_id' => $seatwork->id,
                'student_id' => $student->id,
                'attempt_number' => $submittedCount + 1,
                'status' => \App\Enums\AttemptStatus::InProgress->value,
                'started_at' => now(),
                'total_points' => $seatwork->totalPoints(),
                'is_recorded' => $submittedCount === 0,
            ]);
        }

        $seatwork->load(['questions' => fn($q) => $q->inRandomOrder(), 'questions.options' => fn($o) => $o->inRandomOrder(), 'questions.matchingPairs']);

        return Inertia::render('Student/Seatwork/Take', [
            'seatwork' => $seatwork,
            'startedAt' => $existing->started_at,
            'attemptNumber' => $existing->attempt_number,
        ]);
    }

    public function submit(Request $request, Seatwork $seatwork): RedirectResponse
    {
        $student = Auth::user();
        abort_unless($seatwork->is_published, 403);
        abort_if($seatwork->isClosed(), 403, 'This seatwork has closed.');
        $this->authorizeGrade($seatwork, $student);

        $attempt = SeatworkAttempt::where('seatwork_id', $seatwork->id)
            ->where('student_id', $student->id)
            ->where('status', \App\Enums\AttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if (! $attempt) {
            $attempt = SeatworkAttempt::where('seatwork_id', $seatwork->id)
                ->where('student_id', $student->id)
                ->latest('id')
                ->firstOrFail();
        }

        if ($attempt->status === \App\Enums\AttemptStatus::Submitted->value) {
            return redirect()->route('student.seatworks.result', ['attempt' => $attempt->id], 303)->with('info', 'Already submitted.');
        }

        $seatwork->load('questions.options', 'questions.matchingPairs');

        $score = retry(
            [100, 200, 400, 800, 1600],
            function () use ($request, $seatwork, $attempt) {
                $points = 0;

                DB::transaction(function () use ($request, $seatwork, $attempt, &$points) {
                    foreach ($seatwork->questions as $question) {
                        $answerData = $this->gradeQuestion($question, $request->input('answers.' . $question->id));
                        if (isset($answerData['points_awarded'])) {
                            $points += (int) $answerData['points_awarded'];
                        } elseif ($answerData['is_correct']) {
                            $points += $question->points;
                        }

                        // Query Builder (not Eloquent) for reliability under
                        // SQLite: repeated Eloquent inserts in one request
                        // intermittently fail to open the database file.
                        DB::table('seatwork_answers')->updateOrInsert(
                            ['attempt_id' => $attempt->id, 'question_id' => $question->id],
                            [
                                'answer_text' => $answerData['answer_text'] ?? null,
                                'selected_option_id' => $answerData['selected_option_id'] ?? null,
                                'matching_answers' => isset($answerData['matching_answers']) && is_array($answerData['matching_answers'])
                                    ? json_encode($answerData['matching_answers'])
                                    : ($answerData['matching_answers'] ?? null),
                                'is_correct' => $answerData['is_correct'] ?? false,
                                'feedback' => $answerData['feedback'] ?? null,
                                'points_awarded' => $answerData['points_awarded'] ?? null,
                                'created_at' => now()->toDateTimeString(),
                                'updated_at' => now()->toDateTimeString(),
                            ]
                        );
                    }

                    $attempt->update(['score' => $points, 'total_points' => $seatwork->totalPoints(), 'status' => \App\Enums\AttemptStatus::Submitted->value, 'submitted_at' => now()]);
                });

                return $points;
            },
            when: fn ($e) => $e instanceof \Illuminate\Database\QueryException
                && in_array((int) $e->getCode(), [5, 6, 14], true),
        );

        // Apply grading policy to select the recorded attempt
        $recordedAttempt = $seatwork->recalculateRecordedAttempts($student->id) ?: $attempt;

        PointsHelper::award($student->id, \App\Enums\ActivityType::Seatwork->value, $seatwork->id, $recordedAttempt->score, $seatwork->totalPoints());

        \App\Services\ActivityLogger::log(
            'seatwork_submitted',
            "Student {$student->name} submitted seatwork '{$seatwork->title}' (Attempt #{$attempt->attempt_number}, Score: {$score}/{$seatwork->totalPoints()})",
            $seatwork,
            $student,
            ['score' => $score, 'recorded_score' => $recordedAttempt->score, 'attempt_number' => $attempt->attempt_number, 'total' => $seatwork->totalPoints()]
        );

        return redirect()->route('student.seatworks.result', ['attempt' => $attempt->id], 303)->with('success', 'Seatwork submitted.');
    }

    public function result(SeatworkAttempt $attempt): Response
    {
        $student = Auth::user();
        abort_if($attempt->student_id !== $student->id, 403);
        $attempt->load(['seatwork.questions.options', 'seatwork.questions.matchingPairs', 'answers.selectedOption']);

        $allAttempts = SeatworkAttempt::where('seatwork_id', $attempt->seatwork_id)
            ->where('student_id', $student->id)
            ->where('status', \App\Enums\AttemptStatus::Submitted->value)
            ->orderBy('attempt_number', 'asc')
            ->get(['id', 'attempt_number', 'score', 'total_points', 'is_recorded', 'submitted_at']);

        $maxAttempts = $attempt->seatwork->max_attempts ?? 1;
        $canRetake = ! $attempt->seatwork->isClosed() && ($maxAttempts === 0 || $allAttempts->count() < $maxAttempts);

        return Inertia::render('Student/Seatwork/Result', [
            'attempt' => $attempt,
            'allAttempts' => $allAttempts,
            'canRetake' => $canRetake,
            'maxAttempts' => $maxAttempts,
            'gradingPolicy' => $attempt->seatwork->grading_policy ?? 'first',
        ]);
    }

    // ---- Helpers ----

    /**
     * Teacher: explicitly select an attempt as the official recorded grade.
     */
    public function setRecordedAttempt(Seatwork $seatwork, SeatworkAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($seatwork);
        abort_unless((int) $attempt->seatwork_id === (int) $seatwork->id, 404);
        abort_unless($attempt->status === \App\Enums\AttemptStatus::Submitted->value, 422, 'Cannot record an unsubmitted attempt.');

        DB::transaction(function () use ($seatwork, $attempt) {
            // Unset other attempts for this student on this seatwork
            SeatworkAttempt::where('seatwork_id', $seatwork->id)
                ->where('student_id', $attempt->student_id)
                ->update(['is_recorded' => false]);

            $attempt->update(['is_recorded' => true]);

            // Set seatwork policy to manual so automatic recalculation won't revert teacher's manual decision
            $seatwork->update(['grading_policy' => 'manual']);
        });

        PointsHelper::award(
            $attempt->student_id,
            \App\Enums\ActivityType::Seatwork->value,
            $seatwork->id,
            $attempt->score,
            $seatwork->totalPoints(),
            null,
            true
        );

        $studentName = $attempt->student?->name ?? 'Student';

        return back()->with('success', "Attempt #{$attempt->attempt_number} set as official recorded grade for {$studentName}.");
    }

    public function reassign(Request $request, Seatwork $seatwork): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperadmin(), 403);

        $data = $request->validate(['teacher_id' => ['required', 'exists:users,id']]);
        $seatwork->update(['teacher_id' => $data['teacher_id']]);

        return back()->with('success', 'Seatwork reassigned to teacher.');
    }

    public function autoRecheck(Seatwork $seatwork, SeatworkAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($seatwork);

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

        $recordedAttempt = $seatwork->recalculateRecordedAttempts($attempt->student_id) ?: $attempt;

        PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Seatwork->value, $seatwork->id, $recordedAttempt->score, $seatwork->totalPoints());

        return back()->with('success', 'Seatwork AI-checked successfully based on criteria and answers.');
    }

    public function autoRecheckAll(Seatwork $seatwork): RedirectResponse
    {
        $this->authorizeOwner($seatwork);
        @set_time_limit(180);

        $attempts = $seatwork->attempts()
            ->whereIn('status', ['submitted', 'closed'])
            ->get();

        $count = 0;
        foreach ($attempts as $attempt) {
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

            PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Seatwork->value, $seatwork->id, $result['total_score'], $seatwork->totalPoints());
            $count++;
        }

        return back()->with('success', "AI successfully checked {$count} seatwork attempt(s).");
    }

    public function aiCheck(Seatwork $seatwork, SeatworkAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($seatwork);

        $result = ActivityAiGraderService::gradeSeatworkAttempt($attempt);

        return response()->json($result);
    }

    public function recheck(Seatwork $seatwork, SeatworkAttempt $attempt): Response
    {
        $this->authorizeOwner($seatwork);

        $attempt->load(['student:id,name,grade', 'answers.question', 'answers.selectedOption']);
        $seatwork->load('questions.options', 'questions.matchingPairs');

        $attempts = $seatwork->attempts()
            ->with('student:id,name,grade')
            ->where(fn ($q) => $q->whereIn('status', ['submitted', 'closed'])->orWhere('id', $attempt->id))
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->latest('id')
            ->get(['id', 'seatwork_id', 'student_id', 'score', 'total_points', 'submitted_at']);

        return Inertia::render('Teacher/Seatworks/Recheck', [
            'seatwork' => $seatwork,
            'attempt' => $attempt,
            'attempts' => $attempts,
            'returnTo' => request('return_to'),
        ]);
    }

    public function recheckUpdate(Request $request, Seatwork $seatwork, SeatworkAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($seatwork);

        $data = $request->validate([
            'answers' => ['present', 'array'],
            'answers.*.id' => ['required', 'exists:seatwork_answers,id'],
            'answers.*.is_correct' => ['required', 'boolean'],
            'answers.*.feedback' => ['nullable', 'string'],
            'answers.*.points_awarded' => ['nullable', 'numeric'],
            'next_attempt_id' => ['nullable', 'integer'],
            'save_and_next' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string'],
        ]);

        $score = 0;

        DB::transaction(function () use ($data, $attempt, &$score) {
            foreach ($data['answers'] as $answerData) {
                $payload = ['is_correct' => $answerData['is_correct']];
                if (array_key_exists('feedback', $answerData)) {
                    $payload['feedback'] = $answerData['feedback'];
                }
                if (array_key_exists('points_awarded', $answerData)) {
                    $payload['points_awarded'] = $answerData['points_awarded'] !== null
                        ? (int) round((float) $answerData['points_awarded'])
                        : null;
                }

                SeatworkAnswer::where('id', $answerData['id'])
                    ->where('attempt_id', $attempt->id)
                    ->update($payload);
            }

            $answers = SeatworkAnswer::where('attempt_id', $attempt->id)
                ->with('question')
                ->get();

            foreach ($answers as $ans) {
                if ($ans->question?->type === 'essay' && $ans->points_awarded !== null) {
                    $score += (int) $ans->points_awarded;
                } elseif ($ans->is_correct) {
                    $score += (int) ($ans->question?->points ?? 0);
                }
            }

            $attempt->update(['score' => $score]);
        });

        PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Seatwork->value, $seatwork->id, $score, $seatwork->totalPoints());

        $returnTo = $request->input('return_to');
        $nextAttemptId = $request->input('next_attempt_id');

        if (! $nextAttemptId && $request->boolean('save_and_next')) {
            $allAttempts = $seatwork->attempts()
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
            $nextAttempt = $seatwork->attempts()->where('id', $nextAttemptId)->first();
            if ($nextAttempt) {
                $cleanReturnTo = $cleanAnchor($returnTo, $nextAttempt->id);
                return redirect()->route('teacher.seatworks.recheck', [
                    'seatwork' => $seatwork,
                    'attempt' => $nextAttempt,
                    'return_to' => $cleanReturnTo,
                ])->with('success', 'Seatwork rechecked for ' . ($attempt->student->name ?? 'student') . '. Switched to next attempt.');
            }
        }

        if ($returnTo && (str_starts_with($returnTo, '/teacher/') || str_starts_with($returnTo, '/student/'))) {
            $finalReturn = $cleanAnchor($returnTo, $attempt->id);
            return redirect($finalReturn)->with('success', 'Seatwork rechecked successfully.');
        }

        return redirect()->to(route('teacher.seatworks.show', $seatwork) . '#attempt-' . $attempt->id)->with('success', 'Seatwork rechecked successfully.');
    }

    private function authorizeOwner(Seatwork $seatwork): void
    {
        if (Auth::user()->isSuperadmin()) return;
        abort_if($seatwork->teacher_id !== Auth::id(), 403);
    }

    private function authorizeGrade(Seatwork $seatwork, $student): void
    {
        if ($student->isTeacher() || $student->isSuperadmin()) return;

        $hasGrade = $seatwork->gradeLevels()->exists()
            ? ($student->grade_level_id && $seatwork->gradeLevels()->where('grade_level_id', $student->grade_level_id)->exists())
            : ($student->grade && $seatwork->grade === $student->grade);

        abort_unless($hasGrade, 403, 'This seatwork is not available to your account.');
    }

    private function gradeQuestion($question, $answer): array
    {
        $data = ['question_id' => $question->id, 'is_correct' => false];

        return match ($question->type) {
            'identification' => (function () use ($question, $answer) {
                $answerText = (string) ($answer ?? '');
                $isCorrect = AnswerMatcherService::matchesAny(
                    $question->options,
                    $answerText,
                    $question->question_text
                );

                return [
                    'answer_text' => $answerText,
                    'is_correct' => $isCorrect,
                    'feedback' => $isCorrect ? 'Correct answer.' : 'Incorrect answer.',
                ];
            })(),
            'enumeration' => (function () use ($question, $answer) {
                $answerText = is_array($answer)
                    ? implode("\n", array_filter($answer, fn ($item) => trim((string) $item) !== ''))
                    : (string) ($answer ?? '');
                $expectedItems = $question->options
                    ->pluck('option_text')
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $studentItems = [];

                foreach (preg_split('/[\\r\\n]+/', trim($answerText)) as $line) {
                    foreach (preg_split('/(?<=\\w)[,;]+(?=\\s*\\w)/', $line) as $item) {
                        $item = trim((string) preg_replace('/^\\s*(?:[0-9]+[.)\\-:]|[-*•])\\s*/', '', trim($item)));
                        if ($item !== '') {
                            $studentItems[] = $item;
                        }
                    }
                }

                $matchedExpected = [];
                foreach ($studentItems as $studentItem) {
                    foreach ($expectedItems as $index => $expectedItem) {
                        if (! in_array($index, $matchedExpected, true)
                            && AnswerMatcherService::isNearMatch($expectedItem, $studentItem, $question->question_text)) {
                            $matchedExpected[] = $index;
                            break;
                        }
                    }
                }

                $totalExpected = count($expectedItems);
                $matchCount = count($matchedExpected);
                $pointsAwarded = $totalExpected > 0
                    ? (int) round(($matchCount / $totalExpected) * (int) $question->points)
                    : 0;
                $isCorrect = $totalExpected > 0 && (
                    $matchCount === $totalExpected
                    || ($matchCount > 0 && $matchCount >= ceil($totalExpected / 2))
                );

                return [
                    'answer_text' => $answerText,
                    'is_correct' => $isCorrect,
                    'points_awarded' => $pointsAwarded,
                    'feedback' => "{$matchCount}/{$totalExpected} items identified.",
                ];
            })(),
            'true_false', 'multiple_choice' => [
                'selected_option_id' => $answer,
                'is_correct' => $question->options->firstWhere('id', $answer)?->is_correct ?? false,
            ],
            'matching' => [
                'matching_answers' => $answer,
                'is_correct' => $this->gradeMatching($question, $answer),
            ],
            'essay' => [
                'answer_text' => (string) ($answer ?? ''),
                'feedback' => 'Awaiting teacher review.',
                'points_awarded' => 0,
                'is_correct' => false,
            ],
            default => ['answer_text' => (string) ($answer ?? ''), 'is_correct' => false],
        };
    }

    private function gradeMatching($question, $answers): bool
    {
        if (!is_array($answers)) return false;
        $pairs = $question->matchingPairs;
        foreach ($pairs as $pair) {
            $studentRight = $answers[$pair->id] ?? null;
            if ($studentRight !== $pair->right_text) return false;
        }
        return true;
    }
}
