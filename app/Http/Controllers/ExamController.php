<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamSection;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\Quarter;
use App\Models\Quiz;
use App\Models\SchoolYear;
use App\Models\Seatwork;
use App\Models\Section;
use App\Models\Setting;
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

class ExamController extends Controller
{
    // ─── Teacher ───────────────────────────────────────────────────

    public function index(): Response
    {
        $teacher = Auth::user();

        $examQuery = $teacher->isSuperadmin()
            ? Exam::withCount('sections')->withCount('attempts')->with('teacher:id,name', 'gradeLevels:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest()
            : $teacher->exams()->getQuery()->withCount('sections')->withCount('attempts')->with('gradeLevels:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest();
        SchoolYear::applyFilter($examQuery);
        $exams = $examQuery->get();

        $teacherQuizzes = ($teacher->isSuperadmin() ? Quiz::query() : $teacher->quizzes())
            ->select('id', 'title', 'grade')
            ->withCount('questions')
            ->latest()
            ->get();

        $teacherSeatworks = ($teacher->isSuperadmin() ? Seatwork::query() : $teacher->seatworks())
            ->select('id', 'title', 'grade')
            ->withCount('questions')
            ->latest()
            ->get();

        $teacherPracticals = ($teacher->isSuperadmin() ? Practical::query() : $teacher->practicals())
            ->select('id', 'title', 'grade')
            ->withCount('criteria')
            ->latest()
            ->get();

        return Inertia::render('Teacher/Exams/Index', [
            'exams' => $exams,
            'isSuperadmin' => $teacher->isSuperadmin(),
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'gradeLevels' => GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']),
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)->orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name']),
            'defaultSchoolYearId' => SchoolYear::defaultIdForCreate(),
            'teacherQuizzes' => $teacherQuizzes,
            'teacherSeatworks' => $teacherSeatworks,
            'teacherPracticals' => $teacherPracticals,
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
            'teacher_id' => ['nullable', 'exists:users,id'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.section_type' => ['required', 'in:questions,practical'],
            'sections.*.instructions' => ['nullable', 'string'],
            'sections.*.questions' => ['nullable', 'array'],
            'sections.*.questions.*.question_text' => ['required_with:sections.*.questions', 'string'],
            'sections.*.questions.*.type' => ['required_with:sections.*.questions', 'in:multiple_choice,true_false,identification,enumeration,matching,essay'],
            'sections.*.questions.*.points' => ['nullable', 'integer', 'min:1'],
            'sections.*.questions.*.criteria' => ['nullable', 'string'],
            'sections.*.questions.*.options' => ['nullable', 'array'],
            'sections.*.questions.*.options.*.option_text' => ['required_with:sections.*.questions.*.options', 'string'],
            'sections.*.questions.*.options.*.is_correct' => ['nullable', 'boolean'],
            'sections.*.questions.*.correct_answer' => ['nullable', 'string'],
            'sections.*.questions.*.enum_items' => ['nullable', 'array'],
            'sections.*.questions.*.enum_items.*' => ['nullable', 'string'],
            'sections.*.questions.*.matching_pairs' => ['nullable', 'array'],
            'sections.*.questions.*.matching_pairs.*.left_text' => ['required_with:sections.*.questions.*.matching_pairs', 'string'],
            'sections.*.questions.*.matching_pairs.*.right_text' => ['required_with:sections.*.questions.*.matching_pairs', 'string'],
            'sections.*.criteria' => ['nullable', 'array'],
            'sections.*.criteria.*.criterion_name' => ['required_with:sections.*.criteria', 'string', 'max:255'],
            'sections.*.criteria.*.description' => ['nullable', 'string'],
            'sections.*.criteria.*.max_points' => ['required_with:sections.*.criteria', 'integer', 'min:1'],
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

            $exam = Exam::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'grade' => $grade,
                'quarter_id' => $data['quarter_id'] ?? null,
                'school_year_id' => $data['school_year_id'] ?? SchoolYear::defaultIdForCreate(),
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'max_score' => $data['max_score'] ?? (int) Setting::get('exam_max_score', config('gamification.exam_max_score', 100)),
                'is_published' => false,
            ]);

            $exam->gradeLevels()->sync($data['grade_level_ids']);

            foreach ($data['sections'] as $sIndex => $sec) {
                $section = $exam->sections()->create([
                    'section_type' => $sec['section_type'],
                    'title' => $sec['title'],
                    'instructions' => $sec['instructions'] ?? null,
                    'order' => $sIndex,
                ]);

                if ($sec['section_type'] === 'questions' && isset($sec['questions'])) {
                    foreach ($sec['questions'] as $qIndex => $q) {
                        $question = $section->questions()->create([
                            'question_text' => $q['question_text'],
                            'type' => $q['type'],
                            'points' => $q['points'] ?? 1,
                            'criteria' => $q['criteria'] ?? null,
                            'order' => $qIndex,
                        ]);

                        if (in_array($q['type'], ['multiple_choice', 'true_false']) && isset($q['options'])) {
                            foreach ($q['options'] as $oIndex => $opt) {
                                $question->options()->create([
                                    'option_text' => $opt['option_text'],
                                    'is_correct' => $opt['is_correct'] ?? false,
                                    'order' => $oIndex,
                                ]);
                            }
                        }

                        if ($q['type'] === 'identification') {
                            $correctText = $q['correct_answer'] ?? ($q['options'][0]['option_text'] ?? null);
                            if ($correctText !== null && $correctText !== '') {
                                $question->options()->create([
                                    'option_text' => (string) $correctText,
                                    'is_correct' => true,
                                    'order' => 0,
                                ]);
                            }
                        }

                        if ($q['type'] === 'enumeration') {
                            $items = $q['enum_items'] ?? (isset($q['options']) ? array_column($q['options'], 'option_text') : []);
                            foreach ($items as $eIndex => $item) {
                                if (trim((string) $item) === '') continue;
                                $question->options()->create([
                                    'option_text' => trim((string) $item),
                                    'is_correct' => true,
                                    'order' => $eIndex,
                                ]);
                            }
                        }

                        if ($q['type'] === 'matching' && isset($q['matching_pairs'])) {
                            foreach ($q['matching_pairs'] as $mIndex => $pair) {
                                $question->matchingPairs()->create([
                                    'left_text' => $pair['left_text'],
                                    'right_text' => $pair['right_text'],
                                    'order' => $mIndex,
                                ]);
                            }
                        }
                    }
                }

                if ($sec['section_type'] === 'practical' && isset($sec['criteria'])) {
                    foreach ($sec['criteria'] as $cIndex => $criterion) {
                        $section->criteria()->create([
                            'criterion_name' => $criterion['criterion_name'],
                            'description' => $criterion['description'] ?? null,
                            'max_points' => $criterion['max_points'],
                            'order' => $cIndex,
                        ]);
                    }
                }
            }

            $totalMax = 0;
            $exam->load('sections.questions', 'sections.criteria');
            foreach ($exam->sections as $sec) {
                if ($sec->section_type === 'questions') {
                    foreach ($sec->questions as $q) {
                        $totalMax += $q->points;
                    }
                } else {
                    foreach ($sec->criteria as $c) {
                        $totalMax += $c->max_points;
                    }
                }
            }
            $exam->update(['max_score' => $totalMax]);
        });

        return redirect()->route('teacher.exams.index')->with('success', 'Exam created successfully.');
    }

    public function edit(Exam $exam): Response
    {
        $this->authorizeOwner($exam);
        $exam->load([
            'sections.questions.options',
            'sections.questions.matchingPairs',
            'sections.criteria',
            'gradeLevels:id,name',
        ]);

        $teacher = Auth::user();

        return Inertia::render('Teacher/Exams/Edit', [
            'exam' => $exam,
            'teachers' => $teacher->isSuperadmin()
                ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name'])
                : [],
            'gradeLevels' => GradeLevel::where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'quarters' => Quarter::where('is_active', true)
                ->orderBy('display_order')
                ->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)
                ->orderByDesc('is_current')
                ->orderByDesc('name')
                ->get(['id', 'name']),
            'defaultSchoolYearId' => SchoolYear::defaultIdForCreate(),
        ]);
    }

    public function update(Request $request, Exam $exam): RedirectResponse
    {
        $this->authorizeOwner($exam);

        if ($request->has('grade_level_id') && ! $request->has('grade_level_ids')) {
            $request->merge(['grade_level_ids' => [$request->input('grade_level_id')]]);
        } elseif ($request->has('grade_level_ids') && ! is_array($request->input('grade_level_ids'))) {
            $value = $request->input('grade_level_ids');
            $request->merge([
                'grade_level_ids' => is_string($value)
                    ? array_filter(explode(',', $value))
                    : array_filter([$value]),
            ]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'instructions' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.id' => ['nullable', 'exists:exam_sections,id'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.section_type' => ['required', 'in:questions,practical'],
            'sections.*.instructions' => ['nullable', 'string'],
            'sections.*.questions' => ['nullable', 'array'],
            'sections.*.questions.*.id' => ['nullable', 'exists:exam_questions,id'],
            'sections.*.questions.*.question_text' => ['required', 'string'],
            'sections.*.questions.*.type' => ['required', 'in:multiple_choice,true_false,identification,enumeration,matching,essay'],
            'sections.*.questions.*.points' => ['required', 'integer', 'min:1'],
            'sections.*.questions.*.criteria' => ['nullable', 'string'],
            'sections.*.questions.*.correct_answer' => ['nullable', 'string'],
            'sections.*.questions.*.option_ids' => ['nullable', 'array'],
            'sections.*.questions.*.option_ids.*' => ['nullable', 'exists:exam_options,id'],
            'sections.*.questions.*.enum_items' => ['nullable', 'array'],
            'sections.*.questions.*.enum_items.*' => ['nullable', 'string'],
            'sections.*.questions.*.options' => ['nullable', 'array'],
            'sections.*.questions.*.options.*.id' => ['nullable', 'exists:exam_options,id'],
            'sections.*.questions.*.options.*.option_text' => ['required_with:sections.*.questions.*.options', 'string'],
            'sections.*.questions.*.options.*.is_correct' => ['nullable', 'boolean'],
            'sections.*.questions.*.matching_pairs' => ['nullable', 'array'],
            'sections.*.questions.*.matching_pairs.*.id' => ['nullable', 'exists:exam_matching_pairs,id'],
            'sections.*.questions.*.matching_pairs.*.left_text' => ['required_with:sections.*.questions.*.matching_pairs', 'string'],
            'sections.*.questions.*.matching_pairs.*.right_text' => ['required_with:sections.*.questions.*.matching_pairs', 'string'],
            'sections.*.criteria' => ['nullable', 'array'],
            'sections.*.criteria.*.id' => ['nullable', 'exists:exam_criteria,id'],
            'sections.*.criteria.*.criterion_name' => ['required_with:sections.*.criteria', 'string', 'max:255'],
            'sections.*.criteria.*.description' => ['nullable', 'string'],
            'sections.*.criteria.*.max_points' => ['required_with:sections.*.criteria', 'integer', 'min:1'],
        ], [
            'grade_level_ids.required' => 'The grade level field is required.',
            'grade_level_ids.min' => 'Please select at least one grade level.',
        ], [
            'grade_level_ids' => 'grade level',
        ]);

        DB::transaction(function () use ($data, $exam) {
            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])
                ->orderBy('display_order')
                ->value('name');

            $updateData = [
                'title' => $data['title'],
                'grade' => $grade,
                'quarter_id' => array_key_exists('quarter_id', $data) ? $data['quarter_id'] : $exam->quarter_id,
                'school_year_id' => array_key_exists('school_year_id', $data) ? $data['school_year_id'] : $exam->school_year_id,
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
            ];

            if (Auth::user()->isSuperadmin() && ! empty($data['teacher_id'])) {
                $updateData['teacher_id'] = $data['teacher_id'];
            }

            $exam->update($updateData);
            $exam->gradeLevels()->sync($data['grade_level_ids']);

            $existingSections = $exam->sections()->get();
            $submittedSectionIds = [];

            foreach ($data['sections'] as $sectionIndex => $sectionData) {
                $section = ! empty($sectionData['id'])
                    ? $existingSections->firstWhere('id', $sectionData['id'])
                    : null;

                abort_unless(empty($sectionData['id']) || $section, 422, 'Invalid exam section.');

                if ($section) {
                    $section->update([
                        'section_type' => $sectionData['section_type'],
                        'title' => $sectionData['title'],
                        'instructions' => $sectionData['instructions'] ?? null,
                        'order' => $sectionIndex,
                    ]);
                } else {
                    $section = $exam->sections()->create([
                        'section_type' => $sectionData['section_type'],
                        'title' => $sectionData['title'],
                        'instructions' => $sectionData['instructions'] ?? null,
                        'order' => $sectionIndex,
                    ]);
                }

                $submittedSectionIds[] = $section->id;

                if ($section->section_type === 'questions') {
                    $section->criteria()->delete();
                    $existingQuestions = $section->questions()->get();
                    $submittedQuestionIds = [];

                    foreach ($sectionData['questions'] ?? [] as $questionIndex => $questionData) {
                        $question = ! empty($questionData['id'])
                            ? $existingQuestions->firstWhere('id', $questionData['id'])
                            : null;

                        abort_unless(empty($questionData['id']) || $question, 422, 'Invalid exam question.');

                        if ($question) {
                            $question->update([
                                'question_text' => $questionData['question_text'],
                                'type' => $questionData['type'],
                                'points' => $questionData['points'],
                                'criteria' => $questionData['criteria'] ?? null,
                                'order' => $questionIndex,
                            ]);
                        } else {
                            $question = $section->questions()->create([
                                'question_text' => $questionData['question_text'],
                                'type' => $questionData['type'],
                                'points' => $questionData['points'],
                                'criteria' => $questionData['criteria'] ?? null,
                                'order' => $questionIndex,
                            ]);
                        }

                        $submittedQuestionIds[] = $question->id;

                        if ($questionData['type'] === 'matching') {
                            $existingPairs = $question->matchingPairs()->get();
                            $submittedPairIds = [];
                            foreach ($questionData['matching_pairs'] ?? [] as $pairIndex => $pairData) {
                                $pair = ! empty($pairData['id'])
                                    ? $existingPairs->firstWhere('id', $pairData['id'])
                                    : null;
                                abort_unless(empty($pairData['id']) || $pair, 422, 'Invalid matching pair.');
                                if ($pair) {
                                    $pair->update([
                                        'left_text' => $pairData['left_text'],
                                        'right_text' => $pairData['right_text'],
                                        'order' => $pairIndex,
                                    ]);
                                } else {
                                    $pair = $question->matchingPairs()->create([
                                        'left_text' => $pairData['left_text'],
                                        'right_text' => $pairData['right_text'],
                                        'order' => $pairIndex,
                                    ]);
                                }
                                $submittedPairIds[] = $pair->id;
                            }
                            if ($submittedPairIds) {
                                $question->matchingPairs()->whereNotIn('id', $submittedPairIds)->delete();
                            } else {
                                $question->matchingPairs()->delete();
                            }
                        } else {
                            $question->matchingPairs()->delete();
                        }

                        $optionPayload = match ($questionData['type']) {
                            'identification' => [[
                                'id' => $questionData['options'][0]['id'] ?? null,
                                'option_text' => $questionData['correct_answer'] ?? ($questionData['options'][0]['option_text'] ?? ''),
                                'is_correct' => true,
                            ]],
                            'enumeration' => collect($questionData['enum_items'] ?? [])
                                ->values()
                                ->filter(fn ($item) => trim((string) $item) !== '')
                                ->map(fn ($item, $index) => [
                                    'id' => $questionData['option_ids'][$index] ?? null,
                                    'option_text' => trim((string) $item),
                                    'is_correct' => true,
                                ])->all(),
                            'multiple_choice', 'true_false' => $questionData['options'] ?? [],
                            default => [],
                        };

                        $existingOptions = $question->options()->get();
                        $submittedOptionIds = [];
                        foreach ($optionPayload as $optionIndex => $optionData) {
                            if (trim((string) ($optionData['option_text'] ?? '')) === '') continue;
                            $option = ! empty($optionData['id'])
                                ? $existingOptions->firstWhere('id', $optionData['id'])
                                : null;
                            abort_unless(empty($optionData['id']) || $option, 422, 'Invalid exam option.');
                            $fields = [
                                'option_text' => $optionData['option_text'],
                                'is_correct' => (bool) ($optionData['is_correct'] ?? false),
                                'order' => $optionIndex,
                            ];
                            if ($option) {
                                $option->update($fields);
                            } else {
                                $option = $question->options()->create($fields);
                            }
                            $submittedOptionIds[] = $option->id;
                        }
                        if ($submittedOptionIds) {
                            $question->options()->whereNotIn('id', $submittedOptionIds)->delete();
                        } else {
                            $question->options()->delete();
                        }
                    }

                    if ($submittedQuestionIds) {
                        $section->questions()->whereNotIn('id', $submittedQuestionIds)->delete();
                    } else {
                        $section->questions()->delete();
                    }
                } else {
                    $section->questions()->delete();
                    $existingCriteria = $section->criteria()->get();
                    $submittedCriterionIds = [];

                    foreach ($sectionData['criteria'] ?? [] as $criterionIndex => $criterionData) {
                        $criterion = ! empty($criterionData['id'])
                            ? $existingCriteria->firstWhere('id', $criterionData['id'])
                            : null;
                        abort_unless(empty($criterionData['id']) || $criterion, 422, 'Invalid exam criterion.');
                        $fields = [
                            'criterion_name' => $criterionData['criterion_name'],
                            'description' => $criterionData['description'] ?? null,
                            'max_points' => $criterionData['max_points'],
                            'order' => $criterionIndex,
                        ];
                        if ($criterion) {
                            $criterion->update($fields);
                        } else {
                            $criterion = $section->criteria()->create($fields);
                        }
                        $submittedCriterionIds[] = $criterion->id;
                    }

                    if ($submittedCriterionIds) {
                        $section->criteria()->whereNotIn('id', $submittedCriterionIds)->delete();
                    } else {
                        $section->criteria()->delete();
                    }
                }
            }

            if ($submittedSectionIds) {
                $exam->sections()->whereNotIn('id', $submittedSectionIds)->delete();
            } else {
                $exam->sections()->delete();
            }

            $totalMax = 0;
            $exam->load('sections.questions', 'sections.criteria');
            foreach ($exam->sections as $section) {
                if ($section->section_type === 'questions') {
                    $totalMax += $section->questions->sum('points');
                } else {
                    $totalMax += $section->criteria->sum('max_points');
                }
            }
            $exam->update(['max_score' => $totalMax]);
        });

        return redirect()->route('teacher.exams.show', $exam)->with('success', 'Exam updated successfully.');
    }

    public function compileActivities(Request $request): JsonResponse
    {
        $teacher = Auth::user();

        $data = $request->validate([
            'quiz_ids' => ['nullable', 'array'],
            'quiz_ids.*' => ['integer'],
            'seatwork_ids' => ['nullable', 'array'],
            'seatwork_ids.*' => ['integer'],
            'practical_ids' => ['nullable', 'array'],
            'practical_ids.*' => ['integer'],
            'rephrase' => ['nullable', 'boolean'],
        ]);

        $quizIds = $data['quiz_ids'] ?? [];
        $seatworkIds = $data['seatwork_ids'] ?? [];
        $practicalIds = $data['practical_ids'] ?? [];
        $shouldRephrase = (bool) ($data['rephrase'] ?? true);

        if (empty($quizIds) && empty($seatworkIds) && empty($practicalIds)) {
            return response()->json(['ok' => false, 'message' => 'Please select at least one activity to compile.'], 422);
        }

        $quizzes = Quiz::whereIn('id', $quizIds)
            ->when(! $teacher->isSuperadmin(), fn ($q) => $q->where('teacher_id', $teacher->id))
            ->with(['questions.options', 'questions.matchingPairs', 'gradeLevels:id,name'])
            ->get();

        $seatworks = Seatwork::whereIn('id', $seatworkIds)
            ->when(! $teacher->isSuperadmin(), fn ($q) => $q->where('teacher_id', $teacher->id))
            ->with(['questions.options', 'questions.matchingPairs', 'gradeLevels:id,name'])
            ->get();

        $practicals = Practical::whereIn('id', $practicalIds)
            ->when(! $teacher->isSuperadmin(), fn ($q) => $q->where('teacher_id', $teacher->id))
            ->with(['criteria', 'gradeLevels:id,name'])
            ->get();

        $sections = [];
        $gradeLevelIds = [];
        $totalMinutes = 0;

        foreach ($quizzes as $quiz) {
            foreach ($quiz->gradeLevels as $gl) {
                $gradeLevelIds[] = $gl->id;
            }
            if ($quiz->time_limit_minutes) {
                $totalMinutes += $quiz->time_limit_minutes;
            }

            $questions = [];
            foreach ($quiz->questions as $q) {
                $options = [];
                $matchingPairs = [];
                $correctAnswer = null;
                $enumItems = [];

                if ($q->type === 'identification') {
                    $correctAnswer = $q->options->firstWhere('is_correct', true)?->option_text ?? ($q->options->first()?->option_text ?? '');
                } elseif ($q->type === 'enumeration') {
                    $enumItems = $q->options->pluck('option_text')->all();
                } elseif ($q->type === 'matching') {
                    $matchingPairs = $q->matchingPairs->map(fn ($mp) => [
                        'left_text' => $mp->left_text,
                        'right_text' => $mp->right_text,
                    ])->all();
                } else {
                    $options = $q->options->map(fn ($opt) => [
                        'option_text' => $opt->option_text,
                        'is_correct' => (bool) $opt->is_correct,
                    ])->all();
                }

                $questions[] = [
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'points' => (int) $q->points,
                    'criteria' => $q->criteria,
                    'correct_answer' => $correctAnswer,
                    'enum_items' => $enumItems,
                    'options' => $options,
                    'matching_pairs' => $matchingPairs,
                ];
            }

            if (! empty($questions)) {
                $sections[] = [
                    'title' => 'Part ' . (count($sections) + 1) . ": {$quiz->title}",
                    'section_type' => 'questions',
                    'instructions' => "Answer all questions compiled from {$quiz->title}.",
                    'questions' => $questions,
                    'criteria' => [],
                ];
            }
        }

        foreach ($seatworks as $seatwork) {
            foreach ($seatwork->gradeLevels as $gl) {
                $gradeLevelIds[] = $gl->id;
            }
            if ($seatwork->time_limit_minutes) {
                $totalMinutes += $seatwork->time_limit_minutes;
            }

            $questions = [];
            foreach ($seatwork->questions as $q) {
                $options = [];
                $matchingPairs = [];
                $correctAnswer = null;
                $enumItems = [];

                if ($q->type === 'identification') {
                    $correctAnswer = $q->options->firstWhere('is_correct', true)?->option_text ?? ($q->options->first()?->option_text ?? '');
                } elseif ($q->type === 'enumeration') {
                    $enumItems = $q->options->pluck('option_text')->all();
                } elseif ($q->type === 'matching') {
                    $matchingPairs = $q->matchingPairs->map(fn ($mp) => [
                        'left_text' => $mp->left_text,
                        'right_text' => $mp->right_text,
                    ])->all();
                } else {
                    $options = $q->options->map(fn ($opt) => [
                        'option_text' => $opt->option_text,
                        'is_correct' => (bool) $opt->is_correct,
                    ])->all();
                }

                $questions[] = [
                    'question_text' => $q->question_text,
                    'type' => $q->type,
                    'points' => (int) $q->points,
                    'criteria' => $q->criteria,
                    'correct_answer' => $correctAnswer,
                    'enum_items' => $enumItems,
                    'options' => $options,
                    'matching_pairs' => $matchingPairs,
                ];
            }

            if (! empty($questions)) {
                $sections[] = [
                    'title' => 'Part ' . (count($sections) + 1) . ": {$seatwork->title}",
                    'section_type' => 'questions',
                    'instructions' => $seatwork->instructions ?: "Answer all questions compiled from {$seatwork->title}.",
                    'questions' => $questions,
                    'criteria' => [],
                ];
            }
        }

        foreach ($practicals as $practical) {
            foreach ($practical->gradeLevels as $gl) {
                $gradeLevelIds[] = $gl->id;
            }
            if ($practical->time_limit_minutes) {
                $totalMinutes += $practical->time_limit_minutes;
            }

            $criteria = $practical->criteria->map(fn ($c) => [
                'criterion_name' => $c->criterion_name,
                'description' => $c->description,
                'max_points' => (int) $c->max_points,
            ])->all();

            if (! empty($criteria)) {
                $sections[] = [
                    'title' => 'Part ' . (count($sections) + 1) . ": {$practical->title} (Practical)",
                    'section_type' => 'practical',
                    'instructions' => $practical->instructions ?: 'Complete the practical task according to the scoring rubric.',
                    'questions' => [],
                    'criteria' => $criteria,
                ];
            }
        }

        $rephrased = false;
        if ($shouldRephrase && ! empty($sections)) {
            $payloadToRephrase = [];
            foreach ($sections as $sIdx => $sec) {
                if ($sec['section_type'] === 'questions') {
                    foreach ($sec['questions'] as $qIdx => $q) {
                        $payloadToRephrase[] = [
                            'section_index' => $sIdx,
                            'question_index' => $qIdx,
                            'question_text' => $q['question_text'],
                            'type' => $q['type'],
                            'options' => $q['options'],
                            'correct_answer' => $q['correct_answer'],
                            'enum_items' => $q['enum_items'],
                            'matching_pairs' => $q['matching_pairs'],
                        ];
                    }
                }
            }

            if (! empty($payloadToRephrase)) {
                $systemPrompt = "You are an expert assessment and exam designer.\n"
                    . "Your task is to rephrase exam questions imported from previous quizzes and seatworks into fresh, clear exam questions.\n"
                    . "CRITICAL INSTRUCTIONS:\n"
                    . "1. Rephrase the `question_text` so it tests the exact same concept/knowledge with fresh wording so students cannot rely solely on memorized phrasing.\n"
                    . "2. PRESERVE the `type`, the correctness of correct options, correct answers, enumeration items, and matching pairs.\n"
                    . "3. Output MUST be ONLY a valid JSON array matching the exact count and order of the input items, with no markdown, backticks, or comments.\n"
                    . "Each object must have: section_index, question_index, question_text, and optionally refreshed options (keeping is_correct accurate).";

                $userPrompt = "Please rephrase these questions for the exam:\n" . json_encode($payloadToRephrase, JSON_PRETTY_PRINT);

                try {
                    $aiResponse = ActivityAiGraderService::callAi($systemPrompt, $userPrompt, 4096);
                    if ($aiResponse) {
                        $cleanJson = trim($aiResponse);
                        if (str_starts_with($cleanJson, '```')) {
                            $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', $cleanJson);
                            $cleanJson = preg_replace('/\s*```$/', '', $cleanJson);
                        }
                        $parsed = json_decode($cleanJson, true);
                        if (is_array($parsed)) {
                            foreach ($parsed as $item) {
                                $sI = $item['section_index'] ?? null;
                                $qI = $item['question_index'] ?? null;
                                if ($sI !== null && $qI !== null && isset($sections[$sI]['questions'][$qI])) {
                                    if (! empty($item['question_text'])) {
                                        $sections[$sI]['questions'][$qI]['question_text'] = $item['question_text'];
                                    }
                                    if (! empty($item['options']) && is_array($item['options'])) {
                                        $sections[$sI]['questions'][$qI]['options'] = $item['options'];
                                    }
                                }
                            }
                            $rephrased = true;
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('AI rephrasing during exam compilation failed: ' . $e->getMessage());
                }
            }
        }

        $gradeLevelIds = array_values(array_unique(array_filter($gradeLevelIds)));

        return response()->json([
            'ok' => true,
            'sections' => $sections,
            'grade_level_ids' => $gradeLevelIds,
            'suggested_time_limit' => $totalMinutes > 0 ? min(180, max(30, $totalMinutes)) : 60,
            'rephrased' => $rephrased,
            'message' => $rephrased
                ? 'Successfully compiled and rephrased questions with AI.'
                : 'Successfully compiled questions from selected activities.',
        ]);
    }

    public function show(Exam $exam): Response
    {
        $this->authorizeOwner($exam);
        $exam->load(['sections.questions.options', 'sections.questions.matchingPairs', 'sections.criteria', 'gradeLevels:id,name']);

        $teacher = Auth::user();

        $attempts = $exam->attempts()
            ->with('student:id,name,grade,section_id,gender', 'student.section:id,name')
            ->where('status', \App\Enums\AttemptStatus::Submitted->value)
            ->orderByDesc('total_score')
            ->get();

        $gradeLevelIds = $exam->gradeLevels->pluck('id')->all();
        $sections = Section::where('is_active', true)
            ->when(! empty($gradeLevelIds), fn ($q) => $q->whereIn('grade_level_id', $gradeLevelIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Teacher/Exams/Show', [
            'exam' => $exam,
            'attempts' => $attempts,
            'sections' => $sections,
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
        ]);
    }

    public function publish(Exam $exam): RedirectResponse
    {
        $this->authorizeOwner($exam);
        if ($exam->is_published) {
            $exam->update(['is_published' => false]);
            event(new \App\Events\ExamSessionClosed($exam->id, 'This exam has been unpublished by the teacher.'));
            $msg = 'Exam unpublished.';
        } else {
            $exam->update(['is_published' => true, 'closes_at' => now()->addDay()]);
            $msg = 'Exam published. It will close in 24 hours.';
        }
        return back()->with('success', $msg);
    }

    public function reopen(Exam $exam): RedirectResponse
    {
        $this->authorizeOwner($exam);
        $exam->update(['closes_at' => now()->addDay()]);
        return back()->with('success', 'Exam reopened. It will close in 24 hours.');
    }

    public function closeNow(Exam $exam): RedirectResponse
    {
        $this->authorizeOwner($exam);
        $exam->update(['closes_at' => now()->subSecond()]);
        event(new \App\Events\ExamSessionClosed($exam->id, 'The teacher has closed this exam session immediately.'));
        return back()->with('success', 'Exam closed immediately.');
    }

    public function destroy(Exam $exam): RedirectResponse
    {
        $this->authorizeOwner($exam);
        $exam->delete();
        return redirect()->route('teacher.exams.index')->with('success', 'Exam deleted.');
    }

    // ─── Student ───────────────────────────────────────────────────

    public function take(Exam $exam): Response|RedirectResponse
    {
        abort_unless($exam->is_published, 403);
        abort_if($exam->isClosed(), 403, 'This exam has closed.');

        $student = Auth::user();
        $this->authorizeGrade($exam, $student);
        $existing = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->where('status', \App\Enums\AttemptStatus::InProgress->value)
            ->latest('id')
            ->first();

        if (! $existing) {
            $existing = ExamAttempt::where('exam_id', $exam->id)
                ->where('student_id', $student->id)
                ->latest('id')
                ->first();
        }

        if ($existing && $existing->status === \App\Enums\AttemptStatus::Submitted->value) {
            return redirect()->route('student.exams.result', ['attempt' => $existing->id])
                ->with('info', 'Already submitted.');
        }

        if (!$existing) {
            $existing = ExamAttempt::create([
                'exam_id' => $exam->id,
                'student_id' => $student->id,
                'status' => \App\Enums\AttemptStatus::InProgress->value,
                'started_at' => now(),
            ]);
        }

        $exam->load(['sections' => function ($q) {
            $q->orderBy('order');
        }, 'sections.questions' => function ($q) {
            $q->orderBy('order');
        }, 'sections.questions.options' => function ($q) {
            $q->orderBy('order');
        }, 'sections.questions.matchingPairs' => function ($q) {
            $q->orderBy('order');
        }, 'sections.criteria' => function ($q) {
            $q->orderBy('order');
        }]);

        foreach ($exam->sections as $sec) {
            if ($sec->section_type === 'questions') {
                $shuffled = $sec->questions->shuffle();
                foreach ($shuffled as $q) {
                    if (in_array($q->type, ['multiple_choice', 'true_false'])) {
                        $q->setRelation('options', $q->options->shuffle());
                    }
                    if ($q->type === 'matching') {
                        $q->setRelation('matchingPairs', $q->matchingPairs->shuffle());
                    }
                    $q->makeHidden(['options' => fn ($o) => $o->makeHidden('is_correct')]);
                }
                $sec->setRelation('questions', $shuffled);
            }
        }

        return Inertia::render('Student/Exam/Take', [
            'exam' => $exam,
            'attempt' => [
                'id' => $existing->id,
                'exam_id' => $existing->exam_id,
                'status' => $existing->status,
                'started_at' => $existing->started_at,
            ],
            'startedAt' => $existing->started_at,
        ]);
    }

    public function syncTimer(Request $request, ExamAttempt $attempt): JsonResponse
    {
        $user = Auth::user();
        abort_unless((int) $attempt->student_id === (int) $user->id || $user->isTeacher() || $user->isSuperadmin(), 403);

        $exam = $attempt->exam;
        $remaining = null;
        $isExpired = false;

        if ($exam->isClosed() || !$exam->is_published) {
            $isExpired = true;
            $remaining = 0;
        } elseif ($exam->time_limit_minutes && $attempt->started_at) {
            $deadline = $attempt->started_at->copy()->addMinutes((int) $exam->time_limit_minutes);
            if (now()->greaterThanOrEqualTo($deadline)) {
                $isExpired = true;
                $remaining = 0;
            } else {
                $remaining = max(0, (int) now()->diffInSeconds($deadline));
            }
        }

        if ($isExpired && $attempt->status !== \App\Enums\AttemptStatus::Submitted->value) {
            event(new \App\Events\ExamTimerExpired($exam->id, $attempt->id, 'Exam time has expired.'));
        }

        return response()->json([
            'ok' => true,
            'attempt_id' => $attempt->id,
            'remaining_seconds' => $remaining,
            'is_expired' => $isExpired,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function submit(Request $request, Exam $exam): RedirectResponse
    {
        $student = Auth::user();
        $this->authorizeGrade($exam, $student);

        $request->validate([
            'attempt_id' => ['nullable', 'integer'],
            'answers' => ['nullable', 'array'],
        ]);

        $attemptQuery = ExamAttempt::where('exam_id', $exam->id)
            ->where('student_id', $student->id);

        if ($request->filled('attempt_id')) {
            $attemptQuery->whereKey((int) $request->input('attempt_id'));
        } else {
            $attemptQuery
                ->orderByRaw(
                    'CASE WHEN status = ? THEN 0 ELSE 1 END',
                    [\App\Enums\AttemptStatus::InProgress->value],
                )
                ->latest('id');
        }

        $attempt = $attemptQuery->firstOrFail();

        if ($attempt->status === \App\Enums\AttemptStatus::Submitted->value) {
            return redirect()->route('student.exams.result', ['attempt' => $attempt->id], 303)
                ->with('info', 'Already submitted.');
        }

        $exam->load('sections.questions.options', 'sections.questions.matchingPairs', 'sections.criteria');

        $totalScore = 0;

        DB::transaction(function () use ($request, $exam, $attempt, &$totalScore) {
            foreach ($exam->sections as $section) {
                if ($section->section_type === 'questions') {
                    foreach ($section->questions as $question) {
                        $answerData = $this->gradeQuestion($question, $request->input('answers.' . $question->id));

                        if (isset($answerData['points_earned'])) {
                            $totalScore += (int) $answerData['points_earned'];
                        } elseif ($answerData['is_correct']) {
                            $answerData['points_earned'] = $question->points;
                            $totalScore += $question->points;
                        } else {
                            $answerData['points_earned'] = 0;
                        }

                        // Query Builder (not Eloquent) for reliability under
                        // SQLite: repeated Eloquent inserts in one request
                        // intermittently fail to open the database file.
                        DB::table('exam_answers')->updateOrInsert(
                            ['exam_attempt_id' => $attempt->id, 'exam_question_id' => $question->id],
                            [
                                'answer_text' => $answerData['answer_text'] ?? null,
                                'selected_option_id' => $answerData['selected_option_id'] ?? null,
                                'matching_answers' => isset($answerData['matching_answers']) && is_array($answerData['matching_answers'])
                                    ? json_encode($answerData['matching_answers'])
                                    : ($answerData['matching_answers'] ?? null),
                                'is_correct' => $answerData['is_correct'] ?? false,
                                'points_earned' => $answerData['points_earned'] ?? 0,
                                'created_at' => now()->toDateTimeString(),
                                'updated_at' => now()->toDateTimeString(),
                            ]
                        );
                    }
                }

                if ($section->section_type === 'practical') {
                    foreach ($section->criteria as $criterion) {
                        DB::table('exam_section_scores')->updateOrInsert(
                            ['exam_attempt_id' => $attempt->id, 'exam_criterion_id' => $criterion->id],
                            [
                                'exam_section_id' => $section->id,
                                'score' => 0,
                                'comment' => null,
                                'created_at' => now()->toDateTimeString(),
                                'updated_at' => now()->toDateTimeString(),
                            ]
                        );
                    }
                }
            }

            $attempt->update([
                'total_score' => $totalScore,
                'status' => \App\Enums\AttemptStatus::Submitted->value,
                'submitted_at' => now(),
            ]);
        });

        \App\Services\ActivityLogger::log(
            'exam_submitted',
            "Student {$student->name} submitted exam '{$exam->title}' (Score: {$totalScore}/{$exam->max_score})",
            $exam,
            $student,
            ['score' => $totalScore, 'total' => $exam->max_score]
        );

        return redirect()->route('student.exams.result', ['attempt' => $attempt->id], 303)
            ->with('success', 'Exam submitted.');
    }

    public function result(ExamAttempt $attempt): Response
    {
        $student = Auth::user();
        abort_if($attempt->student_id !== $student->id, 403);

        $attempt->load([
            'exam.sections.questions' => fn ($q) => $q->orderBy('order'),
            'exam.sections.questions.options' => fn ($q) => $q->orderBy('order'),
            'exam.sections.questions.matchingPairs' => fn ($q) => $q->orderBy('order'),
            'exam.sections.criteria' => fn ($q) => $q->orderBy('order'),
            'answers' => fn ($q) => $q->with('question', 'selectedOption'),
            'sectionScores' => fn ($q) => $q->with('criterion'),
        ]);

        return Inertia::render('Student/Exam/Result', ['attempt' => $attempt]);
    }

    public function grade(Request $request, ExamAttempt $attempt): RedirectResponse
    {
        $exam = $attempt->exam;
        $this->authorizeOwner($exam);

        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*' => ['integer', 'min:0'],
            'comments' => ['nullable', 'array'],
            'comments.*' => ['nullable', 'string'],
            'next_attempt_id' => ['nullable', 'integer'],
            'save_and_next' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string'],
        ]);

        $exam->load('sections.criteria');
        $totalPractical = 0;
        $autoScore = 0;

        DB::transaction(function () use ($data, $exam, $attempt, &$totalPractical, &$autoScore) {
            foreach ($exam->sections as $section) {
                if ($section->section_type !== 'practical') continue;

                foreach ($section->criteria as $criterion) {
                    $score = min((int) ($data['scores'][$criterion->id] ?? 0), $criterion->max_points);
                    $totalPractical += $score;

                    $attempt->sectionScores()->updateOrCreate(
                        ['exam_criterion_id' => $criterion->id],
                        [
                            'exam_section_id' => $section->id,
                            'score' => $score,
                            'comment' => $data['comments'][$criterion->id] ?? '',
                        ]
                    );
                }
            }

            $autoScore = (int) $attempt->answers()->where('is_correct', true)->sum('points_earned');
            $attempt->update(['total_score' => $autoScore + $totalPractical]);
        });

        $totalScore = $autoScore + $totalPractical;
        PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Exam->value, $exam->id, $totalScore, $exam->max_score ?? 0);

        $nextAttemptId = $data['next_attempt_id'] ?? null;
        if (! $nextAttemptId && ! empty($data['save_and_next'])) {
            $allAttempts = $exam->attempts()
                ->where('status', \App\Enums\AttemptStatus::Submitted->value)
                ->orderByDesc('total_score')
                ->get(['id']);
            $currIdx = $allAttempts->search(fn ($a) => (int) $a->id === (int) $attempt->id);
            if ($currIdx !== false && $currIdx < $allAttempts->count() - 1) {
                $nextAttemptId = $allAttempts[$currIdx + 1]->id;
            }
        }

        $studentName = $attempt->student->name ?? 'student';
        if ($nextAttemptId) {
            $nextAttempt = $exam->attempts()
                ->where('id', $nextAttemptId)
                ->where('status', \App\Enums\AttemptStatus::Submitted->value)
                ->exists();

            if ($nextAttempt) {
                $nextUrl = route('teacher.exams.attempts', ['exam' => $exam]);
                if (! empty($data['return_to'])) {
                    $nextUrl .= '?return_to=' . rawurlencode($data['return_to']);
                }

                return redirect()->to($nextUrl . '#attempt-' . (int) $nextAttemptId)
                    ->with('success', "Practical scores saved for {$studentName}. Switched to next student.")
                    ->with('next_attempt_id', (int) $nextAttemptId);
            }
        }

        return back()->with('success', "Practical scores saved for {$studentName}.");
    }

    public function aiGrade(ExamAttempt $attempt): JsonResponse
    {
        $exam = $attempt->exam;
        $this->authorizeOwner($exam);

        $attempt->load(['exam.sections.criteria', 'answers']);
        $result = ActivityAiGraderService::gradeExamPracticalAttempt($attempt);

        return response()->json($result);
    }

    public function autoRecheck(ExamAttempt $attempt): RedirectResponse
    {
        $exam = $attempt->exam;
        $this->authorizeOwner($exam);

        $result = ActivityAiGraderService::gradeExamAttempt($attempt);

        DB::transaction(function () use ($exam, $attempt, $result) {
            foreach ($exam->sections as $section) {
                if ($section->section_type !== 'practical') continue;

                foreach ($section->criteria as $criterion) {
                    $score = min((int) ($result['scores'][$criterion->id] ?? 0), $criterion->max_points);

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

            foreach ($result['answers'] as $ansData) {
                $attempt->answers()->where('id', $ansData['id'])->update([
                    'is_correct' => $ansData['is_correct'],
                    'points_earned' => $ansData['points_earned'],
                ]);
            }

            $attempt->update(['total_score' => $result['total_score']]);
        });

        $totalScore = (int) $attempt->fresh()->total_score;
        PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Exam->value, $exam->id, $totalScore, $exam->max_score ?? 0);

        return back()->with('success', 'Exam questions and practical criteria AI-graded successfully.');
    }

    public function aiGradeAll(Exam $exam): RedirectResponse
    {
        $this->authorizeOwner($exam);
        @set_time_limit(180);

        $exam->load('sections.criteria', 'sections.questions');
        $attempts = $exam->attempts()
            ->where('status', \App\Enums\AttemptStatus::Submitted->value)
            ->get();

        $count = 0;
        foreach ($attempts as $attempt) {
            $result = ActivityAiGraderService::gradeExamAttempt($attempt);

            DB::transaction(function () use ($exam, $attempt, $result) {
                foreach ($exam->sections as $section) {
                    if ($section->section_type !== 'practical') continue;

                    foreach ($section->criteria as $criterion) {
                        $score = min((int) ($result['scores'][$criterion->id] ?? 0), $criterion->max_points);

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

                foreach ($result['answers'] as $ansData) {
                    $attempt->answers()->where('id', $ansData['id'])->update([
                        'is_correct' => $ansData['is_correct'],
                        'points_earned' => $ansData['points_earned'],
                    ]);
                }

                $attempt->update(['total_score' => $result['total_score']]);
            });

            $totalScore = (int) $attempt->fresh()->total_score;
            PointsHelper::award($attempt->student_id, \App\Enums\ActivityType::Exam->value, $exam->id, $totalScore, $exam->max_score ?? 0);
            $count++;
        }

        return back()->with('success', "AI successfully graded and checked {$count} exam attempt(s).");
    }

    public function attempts(Exam $exam): Response
    {
        $this->authorizeOwner($exam);
        $exam->load('sections.criteria');

        $attempts = $exam->attempts()
            ->with('student:id,name,grade', 'sectionScores.criterion', 'answers.question')
            ->where('status', \App\Enums\AttemptStatus::Submitted->value)
            ->orderByDesc('total_score')
            ->get();

        return Inertia::render('Teacher/Exams/Grade', [
            'exam' => $exam,
            'attempts' => $attempts,
            'returnTo' => request('return_to'),
        ]);
    }

    public function reassign(Request $request, Exam $exam): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperadmin(), 403);

        $data = $request->validate(['teacher_id' => ['required', 'exists:users,id']]);
        $exam->update(['teacher_id' => $data['teacher_id']]);

        return back()->with('success', 'Exam reassigned to teacher.');
    }

    // ─── Internal helpers ──────────────────────────────────────────

    private function authorizeOwner(Exam $exam): void
    {
        if (Auth::user()->isSuperadmin()) return;
        abort_if($exam->teacher_id !== Auth::id(), 403);
    }

    private function authorizeGrade(Exam $exam, $student): void
    {
        if ($student->isTeacher() || $student->isSuperadmin()) return;

        $hasGrade = $exam->gradeLevels()->exists()
            ? ($student->grade_level_id && $exam->gradeLevels()->where('grade_level_id', $student->grade_level_id)->exists())
            : ($student->grade && $exam->grade === $student->grade);

        abort_unless($hasGrade, 403, 'This exam is not available to your account.');
    }

    private function gradeQuestion($question, $answer): array
    {
        $data = ['exam_question_id' => $question->id, 'is_correct' => false];

        return match ($question->type) {
            'identification' => [
                'answer_text' => (string) ($answer ?? ''),
                'is_correct' => $this->gradeIdentification($question, $answer),
            ],
            'enumeration' => (function () use ($question, $answer) {
                $ansText = is_array($answer)
                    ? implode("\n", array_filter($answer, fn ($i) => trim((string) $i) !== ''))
                    : (string) ($answer ?? '');
                $eval = AnswerMatcherService::evaluateEnumerationWithAi(
                    $question->options,
                    $ansText,
                    $question->question_text ?? '',
                    (int) $question->points
                );
                return [
                    'answer_text' => $ansText,
                    'is_correct' => $eval['is_correct'],
                    'points_earned' => (int) ($eval['points_awarded'] ?? ($eval['is_correct'] ? $question->points : 0)),
                ];
            })(),
            'true_false', 'multiple_choice' => (function () use ($question, $answer) {
                $optionId = $this->resolveOptionId($question, $answer);
                return [
                    'selected_option_id' => $optionId,
                    'is_correct' => $this->gradeOption($question, $optionId),
                ];
            })(),
            'matching' => [
                'matching_answers' => $answer,
                'is_correct' => $this->gradeMatching($question, $answer),
            ],
            default => ['answer_text' => (string) ($answer ?? ''), 'is_correct' => false],
        };
    }

    private function gradeIdentification($question, $answer): bool
    {
        $options = $question->options && $question->options->isNotEmpty()
            ? $question->options
            : [($question->options->first()?->option_text ?? $question->question_text)];

        $evaluation = AnswerMatcherService::evaluateIdentificationWithAi(
            $options,
            (string) ($answer ?? ''),
            $question->question_text ?? ''
        );

        return (bool) ($evaluation['is_correct'] ?? false);
    }

    private function resolveOptionId($question, $answer): ?int
    {
        if ($answer === null || $answer === '') {
            return null;
        }
        if (is_numeric($answer)) {
            return (int) $answer;
        }
        if (is_string($answer)) {
            $trimmed = trim($answer);
            if (preg_match('/^[A-Za-z]$/', $trimmed)) {
                $idx = ord(strtoupper($trimmed)) - ord('A');
                $sorted = $question->options->sortBy('order')->values();
                if (isset($sorted[$idx])) {
                    return $sorted[$idx]->id;
                }
            }
            $matched = $question->options->first(function ($opt) use ($trimmed) {
                return AnswerMatcherService::isNearMatch($opt->option_text, $trimmed);
            });
            if ($matched) {
                return $matched->id;
            }
        }
        return null;
    }

    private function gradeOption($question, $optionId): bool
    {
        if (!$optionId) return false;
        $option = $question->options->firstWhere('id', $optionId);
        return $option && $option->is_correct;
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
