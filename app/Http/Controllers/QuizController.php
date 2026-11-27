<?php

namespace App\Http\Controllers;

use App\Helpers\PointsHelper;
use App\Models\GradeLevel;
use App\Models\Quarter;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\SchoolYear;
use App\Models\Section;
use App\Models\User;
use App\Services\ActivityAiGraderService;
use App\Services\QuizGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class QuizController extends Controller
{
    public function index(): Response
    {
        $teacher = Auth::user();

        if ($teacher->isSuperadmin()) {
            $quizQuery = Quiz::withCount('questions')
                ->withCount('attempts')
                ->with('gradeLevels:id,name', 'sections:id,name', 'teacher:id,name', 'quarter:id,name,code', 'schoolYear:id,name')
                ->latest();
        } else {
            $quizQuery = $teacher->quizzes()->getQuery()
                ->withCount('questions')
                ->withCount('attempts')
                ->with('gradeLevels:id,name', 'sections:id,name', 'quarter:id,name,code', 'schoolYear:id,name')
                ->latest();
        }
        SchoolYear::applyFilter($quizQuery);
        $quizzes = $quizQuery->get();

        $gradeLevels = GradeLevel::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $allSections = Section::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id']);

        return Inertia::render('Teacher/Quizzes/Index', [
            'quizzes' => $quizzes,
            'gradeLevels' => $gradeLevels,
            'allSections' => $allSections,
            'isSuperadmin' => $teacher->isSuperadmin(),
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)->orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name']),
            'defaultSchoolYearId' => SchoolYear::defaultIdForCreate(),
        ]);
    }

    public function create(): Response
    {
        $teacher = Auth::user();

        $sections = $teacher->students()
            ->select('grade', 'section')
            ->distinct()
            ->orderBy('grade')
            ->orderBy('section')
            ->get();

        $gradeLevels = GradeLevel::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $allSections = Section::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id']);

        return Inertia::render('Teacher/Quizzes/Create', [
            'sections' => $sections,
            'gradeLevels' => $gradeLevels,
            'allSections' => $allSections,
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
            'description' => ['nullable', 'string'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['exists:sections,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,identification,enumeration,matching,essay'],
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
            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])->orderBy('display_order')->value('name');

            $teacherId = Auth::user()->isSuperadmin() && $data['teacher_id'] ? $data['teacher_id'] : Auth::id();

            $quiz = Quiz::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'grade' => $grade,
                'quarter_id' => $data['quarter_id'] ?? null,
                'school_year_id' => $data['school_year_id'] ?? SchoolYear::defaultIdForCreate(),
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'is_published' => false,
            ]);

            $quiz->gradeLevels()->sync($data['grade_level_ids']);
            $quiz->sections()->sync($data['section_ids']);

            foreach ($data['questions'] as $qIndex => $question) {
                $createdQuestion = $quiz->questions()->create([
                    'question_text' => $question['question_text'],
                    'type' => $question['type'],
                    'points' => $question['points'],
                    'criteria' => $question['criteria'] ?? null,
                    'order' => $qIndex,
                ]);

                if ($question['type'] === 'identification') {
                    $createdQuestion->options()->create([
                        'option_text' => $question['correct_answer'] ?? ($question['options'][0]['option_text'] ?? ''),
                        'is_correct' => true,
                        'order' => 0,
                    ]);
                } elseif ($question['type'] === 'enumeration') {
                    foreach ($question['enum_items'] ?? [] as $eIndex => $item) {
                        if (trim((string) $item) === '') continue;
                        $createdQuestion->options()->create([
                            'option_text' => $item,
                            'is_correct' => true,
                            'order' => $eIndex,
                        ]);
                    }
                } elseif (in_array($question['type'], ['true_false', 'multiple_choice'])) {
                    foreach ($question['options'] ?? [] as $oIndex => $option) {
                        if (! isset($option['option_text']) || trim((string) $option['option_text']) === '') continue;
                        $createdQuestion->options()->create([
                            'option_text' => $option['option_text'],
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                            'order' => $oIndex,
                        ]);
                    }
                } elseif ($question['type'] === 'matching') {
                    foreach ($question['matching_pairs'] ?? [] as $mIndex => $pair) {
                        if (empty($pair['left_text']) && empty($pair['right_text'])) continue;
                        $createdQuestion->matchingPairs()->create([
                            'left_text' => $pair['left_text'] ?? '',
                            'right_text' => $pair['right_text'] ?? '',
                            'order' => $mIndex,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('teacher.quizzes.index')->with('success', 'Quiz created successfully.');
    }

    public function edit(Quiz $quiz): Response
    {
        $this->authorizeOwner($quiz);

        $quiz->load(['questions.options', 'questions.matchingPairs', 'gradeLevels', 'sections']);

        $teacher = Auth::user();

        $gradeLevels = GradeLevel::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $allSections = Section::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id']);

        return Inertia::render('Teacher/Quizzes/Edit', [
            'quiz' => $quiz,
            'gradeLevels' => $gradeLevels,
            'allSections' => $allSections,
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)->orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name']),
            'defaultSchoolYearId' => SchoolYear::defaultIdForCreate(),
        ]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);

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
            'description' => ['nullable', 'string'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['exists:sections,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.id' => ['nullable', 'exists:questions,id'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.type' => ['required', 'in:multiple_choice,true_false,identification,enumeration,matching,essay'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.criteria' => ['nullable', 'string'],
            'questions.*.correct_answer' => ['nullable', 'string'],
            'questions.*.enum_items' => ['nullable', 'array'],
            'questions.*.enum_items.*' => ['nullable', 'string'],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.id' => ['nullable', 'exists:quiz_options,id'],
            'questions.*.options.*.option_text' => ['nullable', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
            'questions.*.matching_pairs' => ['nullable', 'array'],
            'questions.*.matching_pairs.*.id' => ['nullable', 'exists:quiz_matching_pairs,id'],
            'questions.*.matching_pairs.*.left_text' => ['nullable', 'string'],
            'questions.*.matching_pairs.*.right_text' => ['nullable', 'string'],
        ], [
            'grade_level_ids.required' => 'The grade level field is required.',
            'grade_level_ids.min' => 'Please select at least one grade level.',
        ], [
            'grade_level_ids' => 'grade level',
        ]);

        DB::transaction(function () use ($data, $quiz) {
            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])->orderBy('display_order')->value('name');

            $updateData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'grade' => $grade,
                'quarter_id' => array_key_exists('quarter_id', $data) ? $data['quarter_id'] : $quiz->quarter_id,
                'school_year_id' => array_key_exists('school_year_id', $data) ? $data['school_year_id'] : $quiz->school_year_id,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
            ];

            if (Auth::user()->isSuperadmin() && $data['teacher_id']) {
                $updateData['teacher_id'] = $data['teacher_id'];
            }

            $quiz->update($updateData);

            $quiz->gradeLevels()->sync($data['grade_level_ids']);
            $quiz->sections()->sync($data['section_ids']);

            $existingQuestionIds = $quiz->questions()->pluck('id')->toArray();
            $submittedQuestionIds = [];

            foreach ($data['questions'] as $qIndex => $question) {
                $qFields = [
                    'question_text' => $question['question_text'],
                    'type' => $question['type'],
                    'points' => $question['points'],
                    'criteria' => $question['criteria'] ?? null,
                    'order' => $qIndex,
                ];

                if (! empty($question['id'])) {
                    $submittedQuestionIds[] = $question['id'];
                    $createdQuestion = $quiz->questions()->where('id', $question['id'])->first();

                    if ($createdQuestion) {
                        $createdQuestion->update($qFields);
                    }
                } else {
                    $createdQuestion = $quiz->questions()->create($qFields);
                    $submittedQuestionIds[] = $createdQuestion->id;
                }

                if (! $createdQuestion) {
                    continue;
                }

                if ($question['type'] === 'identification') {
                    $createdQuestion->options()->delete();
                    $createdQuestion->matchingPairs()->delete();
                    $createdQuestion->options()->create([
                        'option_text' => $question['correct_answer'] ?? ($question['options'][0]['option_text'] ?? ''),
                        'is_correct' => true,
                        'order' => 0,
                    ]);
                } elseif ($question['type'] === 'enumeration') {
                    $createdQuestion->options()->delete();
                    $createdQuestion->matchingPairs()->delete();
                    foreach ($question['enum_items'] ?? [] as $eIndex => $item) {
                        if (trim((string) $item) === '') continue;
                        $createdQuestion->options()->create([
                            'option_text' => $item,
                            'is_correct' => true,
                            'order' => $eIndex,
                        ]);
                    }
                } elseif ($question['type'] === 'matching') {
                    $createdQuestion->options()->delete();
                    $existingPairIds = $createdQuestion->matchingPairs()->pluck('id')->toArray();
                    $submittedPairIds = [];

                    foreach ($question['matching_pairs'] ?? [] as $mIndex => $pair) {
                        if (empty($pair['left_text']) && empty($pair['right_text'])) continue;
                        if (! empty($pair['id'])) {
                            $submittedPairIds[] = $pair['id'];
                            $existingPair = $createdQuestion->matchingPairs()->where('id', $pair['id'])->first();
                            if ($existingPair) {
                                $existingPair->update([
                                    'left_text' => $pair['left_text'] ?? '',
                                    'right_text' => $pair['right_text'] ?? '',
                                    'order' => $mIndex,
                                ]);
                            }
                        } else {
                            $createdPair = $createdQuestion->matchingPairs()->create([
                                'left_text' => $pair['left_text'] ?? '',
                                'right_text' => $pair['right_text'] ?? '',
                                'order' => $mIndex,
                            ]);
                            $submittedPairIds[] = $createdPair->id;
                        }
                    }

                    $pairsToDelete = array_diff($existingPairIds, $submittedPairIds);
                    if (! empty($pairsToDelete)) {
                        $createdQuestion->matchingPairs()->whereIn('id', $pairsToDelete)->delete();
                    }
                } elseif (in_array($question['type'], ['true_false', 'multiple_choice'])) {
                    $createdQuestion->matchingPairs()->delete();
                    $existingOptionIds = $createdQuestion->options()->pluck('id')->toArray();
                    $submittedOptionIds = [];

                    foreach ($question['options'] ?? [] as $oIndex => $option) {
                        if (! isset($option['option_text']) || trim((string) $option['option_text']) === '') continue;
                        if (! empty($option['id'])) {
                            $submittedOptionIds[] = $option['id'];
                            $existingOption = $createdQuestion->options()->where('id', $option['id'])->first();

                            if ($existingOption) {
                                $existingOption->update([
                                    'option_text' => $option['option_text'],
                                    'is_correct' => (bool) ($option['is_correct'] ?? false),
                                    'order' => $oIndex,
                                ]);
                            }
                        } else {
                            $newOpt = $createdQuestion->options()->create([
                                'option_text' => $option['option_text'],
                                'is_correct' => (bool) ($option['is_correct'] ?? false),
                                'order' => $oIndex,
                            ]);
                            $submittedOptionIds[] = $newOpt->id;
                        }
                    }

                    $optionsToDelete = array_diff($existingOptionIds, $submittedOptionIds);
                    if (! empty($optionsToDelete)) {
                        $createdQuestion->options()->whereIn('id', $optionsToDelete)->delete();
                    }
                } elseif ($question['type'] === 'essay') {
                    $createdQuestion->options()->delete();
                    $createdQuestion->matchingPairs()->delete();
                }
            }

            $questionsToDelete = array_diff($existingQuestionIds, $submittedQuestionIds);
            if (! empty($questionsToDelete)) {
                $quiz->questions()->whereIn('id', $questionsToDelete)->delete();
            }
        });

        return redirect()->route('teacher.quizzes.show', $quiz)->with('success', 'Quiz updated successfully.');
    }

    public function show(Quiz $quiz): Response
    {
        $this->authorizeOwner($quiz);

        $quiz->load(['questions.options', 'questions.matchingPairs', 'gradeLevels', 'sections']);

        $teacher = Auth::user();

        $attempts = $quiz->attempts()
            ->with('student:id,name,grade,section_id,gender', 'student.section:id,name')
            ->where('status', 'submitted')
            ->orderByDesc('score')
            ->get();

        $gradeLevelIds = $quiz->gradeLevels->pluck('id')->all();
        $sections = Section::where('is_active', true)
            ->when(! empty($gradeLevelIds), fn ($q) => $q->whereIn('grade_level_id', $gradeLevelIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Teacher/Quizzes/Show', [
            'quiz' => $quiz,
            'attempts' => $attempts,
            'sections' => $sections,
            'totalPoints' => $quiz->totalPoints(),
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function autoRecheck(Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($quiz);

        $result = ActivityAiGraderService::gradeQuizAttempt($attempt);

        DB::transaction(function () use ($attempt, $result) {
            foreach ($result['answers'] as $ansData) {
                $attempt->answers()->where('id', $ansData['id'])->update(['is_correct' => $ansData['is_correct']]);
            }
            $attempt->update(['score' => $result['total_score']]);
        });

        PointsHelper::award($attempt->student_id, 'Quiz', $quiz->id, $result['total_score'], $quiz->totalPoints());

        return back()->with('success', 'Quiz AI-checked successfully.');
    }

    public function autoRecheckAll(Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);
        @set_time_limit(180);

        $attempts = $quiz->attempts()
            ->whereIn('status', ['submitted', 'closed'])
            ->get();

        $count = 0;
        foreach ($attempts as $attempt) {
            $result = ActivityAiGraderService::gradeQuizAttempt($attempt);

            DB::transaction(function () use ($attempt, $result) {
                foreach ($result['answers'] as $ansData) {
                    $attempt->answers()->where('id', $ansData['id'])->update(['is_correct' => $ansData['is_correct']]);
                }
                $attempt->update(['score' => $result['total_score']]);
            });

            PointsHelper::award($attempt->student_id, 'Quiz', $quiz->id, $result['total_score'], $quiz->totalPoints());
            $count++;
        }

        return back()->with('success', "AI successfully checked {$count} quiz attempt(s).");
    }

    public function aiCheck(Quiz $quiz, QuizAttempt $attempt): JsonResponse
    {
        $this->authorizeOwner($quiz);

        $result = ActivityAiGraderService::gradeQuizAttempt($attempt);

        return response()->json($result);
    }

    public function recheck(Quiz $quiz, QuizAttempt $attempt): Response
    {
        $this->authorizeOwner($quiz);

        $attempt->load(['student:id,name,grade', 'answers.selectedOption', 'answers.question']);

        $quiz->load(['questions.options', 'questions.matchingPairs']);

        $attempts = $quiz->attempts()
            ->with('student:id,name,grade')
            ->where(fn ($q) => $q->whereIn('status', ['submitted', 'closed'])->orWhere('id', $attempt->id))
            ->orderByRaw('COALESCE(submitted_at, created_at) DESC')
            ->latest('id')
            ->get(['id', 'quiz_id', 'student_id', 'score', 'total_points', 'submitted_at']);

        return Inertia::render('Teacher/Quizzes/Recheck', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'attempts' => $attempts,
            'returnTo' => request('return_to'),
        ]);
    }

    public function recheckUpdate(Request $request, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        $this->authorizeOwner($quiz);

        $data = $request->validate([
            'answers' => ['present', 'array'],
            'answers.*.id' => ['required', 'exists:quiz_answers,id'],
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

                QuizAnswer::where('id', $answerData['id'])
                    ->where('quiz_attempt_id', $attempt->id)
                    ->update($payload);
            }

            $answers = QuizAnswer::where('quiz_attempt_id', $attempt->id)
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

        PointsHelper::award($attempt->student_id, 'Quiz', $quiz->id, $score, $quiz->totalPoints());

        $returnTo = $request->input('return_to');
        $nextAttemptId = $request->input('next_attempt_id');

        if (! $nextAttemptId && $request->boolean('save_and_next')) {
            $allAttempts = $quiz->attempts()
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
            $nextAttempt = $quiz->attempts()->where('id', $nextAttemptId)->first();
            if ($nextAttempt) {
                $cleanReturnTo = $cleanAnchor($returnTo, $nextAttempt->id);
                return redirect()->route('teacher.quizzes.recheck', [
                    'quiz' => $quiz,
                    'attempt' => $nextAttempt,
                    'return_to' => $cleanReturnTo,
                ])->with('success', 'Quiz rechecked for ' . ($attempt->student->name ?? 'student') . '. Switched to next attempt.');
            }
        }

        if ($returnTo && (str_starts_with($returnTo, '/teacher/') || str_starts_with($returnTo, '/student/'))) {
            $finalReturn = $cleanAnchor($returnTo, $attempt->id);
            return redirect($finalReturn)->with('success', 'Quiz rechecked successfully.');
        }

        return redirect()->to(route('teacher.quizzes.show', $quiz) . '#attempt-' . $attempt->id)->with('success', 'Quiz rechecked successfully.');
    }

    public function publish(Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);

        if ($quiz->is_published) {
            $quiz->update(['is_published' => false, 'closes_at' => null]);
            $msg = 'Quiz unpublished.';
        } else {
            $quiz->update(['is_published' => true, 'closes_at' => now()->addDay()]);
            $msg = 'Quiz published. It will close in 24 hours.';
        }

        return back()->with('success', $msg);
    }

    public function reopen(Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);
        $quiz->update(['closes_at' => now()->addDay()]);
        return back()->with('success', 'Quiz reopened. It will close in 24 hours.');
    }

    public function closeNow(Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);
        $quiz->update(['closes_at' => now()->subSecond()]);
        return back()->with('success', 'Quiz closed immediately.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $this->authorizeOwner($quiz);

        $quiz->delete();

        return redirect()->route('teacher.quizzes.index')->with('success', 'Quiz deleted.');
    }

    public function reassign(Request $request, Quiz $quiz): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperadmin(), 403);

        $data = $request->validate(['teacher_id' => ['required', 'exists:users,id']]);
        $quiz->update(['teacher_id' => $data['teacher_id']]);

        return back()->with('success', 'Quiz reassigned to teacher.');
    }

    private function authorizeOwner(Quiz $quiz): void
    {
        if (Auth::user()->isSuperadmin()) {
            return;
        }

        if ($quiz->teacher_id !== Auth::id()) {
            abort(403, 'You do not have access to this quiz.');
        }
    }
}
