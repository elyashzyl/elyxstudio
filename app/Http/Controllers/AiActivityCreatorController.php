<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Game;
use App\Models\GradeLevel;
use App\Models\Practical;
use App\Models\Quiz;
use App\Models\Seatwork;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TeachingMaterial;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\MaterialTextExtractor;
use App\Services\QuizGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AiActivityCreatorController extends Controller
{
    /**
     * Display the AI Activity Creator workspace.
     */
    public function create(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $gradeLevels = GradeLevel::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $allSections = Section::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'grade_level_id']);

        $teachers = $user->isSuperadmin()
            ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name'])
            : [];

        $initialActivity = $request->query('activity', 'quiz');
        if (! in_array($initialActivity, ['quiz', 'seatwork', 'practical'], true)) {
            $initialActivity = 'quiz';
        }

        $initialMaterial = null;
        if ($materialId = $request->query('material_id')) {
            $material = TeachingMaterial::find($materialId);
            if ($material && ($user->isSuperadmin() || (int) $material->teacher_id === (int) $user->id || $material->teacher_id === null)) {
                $extracted = MaterialTextExtractor::extract($material);
                $initialMaterial = [
                    'id' => $material->id,
                    'title' => $material->title,
                    'subject_id' => $material->subject_id,
                    'subject' => $material->subject,
                    'text' => $extracted['text'] ?? '',
                ];
            }
        }

        return Inertia::render('Teacher/AiCreator/Index', [
            'gradeLevels' => $gradeLevels,
            'allSections' => $allSections,
            'teachers' => $teachers,
            'subjects' => Subject::where('is_active', true)
                ->when(! $user->isSuperadmin(), fn ($q) => $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id)))
                ->orderBy('name')->get(['id', 'name']),
            'isSuperadmin' => $user->isSuperadmin(),
            'initialActivity' => $initialActivity,
            'initialMaterial' => $initialMaterial,
        ]);
    }

    /**
     * Parse an uploaded file or pasted text using AI or heuristic analysis.
     */
    public function parse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'file' => ['nullable', 'file', 'max:25600'],
            'raw_text' => ['nullable', 'string'],
            'activity' => ['required', 'string', 'in:quiz,seatwork,practical,exam,game'],
            'game_type' => ['nullable', 'string', 'in:' . \App\Enums\GameType::validationList()],
            'count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:multiple_choice,true_false,identification,enumeration,matching,essay'],
            'mode' => ['nullable', 'string', 'in:auto,extract,generate'],
        ]);

        $text = '';
        $filename = null;

        if ($request->hasFile('file')) {
            $extracted = MaterialTextExtractor::extractFromFile($request->file('file'));
            if (isset($extracted['error'])) {
                return response()->json(['ok' => false, 'message' => $extracted['error']], 422);
            }
            $text = $extracted['text'] ?? '';
            $filename = $extracted['filename'] ?? null;
        } elseif (! empty($data['raw_text'])) {
            $text = trim($data['raw_text']);
        }

        if (trim($text) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please upload a document (PDF, Word, PPT, TXT) or paste text to parse.',
            ], 422);
        }

        $activity = $data['activity'];
        $count = (int) ($data['count'] ?? 10);
        $types = $data['types'] ?? [];
        $mode = $data['mode'] ?? 'auto';

        if ($activity === 'game') {
            $genResult = QuizGeneratorService::generateFor(
                'game',
                $text,
                $count,
                $types,
                $data['game_type'] ?? 'flashcard'
            );
            $items = $genResult['items'] ?? [];
            $result = [
                'ok' => !empty($items),
                'provider' => $genResult['provider'] ?? 'heuristic',
                'detected_mode' => 'generated_from_content',
                'title' => QuizGeneratorService::extractHeuristicTitle($text, 'game'),
                'instructions' => 'Play the game and complete all cards.',
                'time_limit_minutes' => null,
                'max_score' => count($items) * 5,
                'activity' => 'game',
                'game_type' => $data['game_type'] ?? 'flashcard',
                'items' => $items,
            ];
        } else {
            $result = QuizGeneratorService::parseOrGenerateDocument($activity, $text, $count, $types, $mode);
        }

        if (empty($result['items'])) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not detect or generate questions from this content. Please check the file or paste more detailed text.',
            ], 422);
        }

        return response()->json(array_merge($result, [
            'source_filename' => $filename,
        ]));
    }

    /**
     * Store the AI-populated activity directly into the system.
     */
    public function store(Request $request): RedirectResponse
    {
        $activity = $request->input('activity', 'quiz');

        return match ($activity) {
            'seatwork' => $this->storeSeatwork($request),
            'practical' => $this->storePractical($request),
            'exam' => $this->storeExam($request),
            'game' => $this->storeGame($request),
            default => $this->storeQuiz($request),
        };
    }

    protected function storeQuiz(Request $request): RedirectResponse
    {
        $this->normalizeGradeLevelIds($request);

        if ($request->has('questions') && is_array($request->input('questions'))) {
            $qs = $request->input('questions');
            foreach ($qs as $k => $q) {
                if (($q['type'] ?? '') === 'identification' && empty($q['options']) && ! empty($q['correct_answer'])) {
                    $qs[$k]['options'] = [
                        ['option_text' => (string) $q['correct_answer'], 'is_correct' => true],
                    ];
                }
            }
            $request->merge(['questions' => $qs]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'section_ids' => ['required', 'array', 'min:1'],
            'section_ids.*' => ['exists:sections,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'action' => ['nullable', 'string', 'in:save,publish,save_and_edit'],
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
        ]);

        $quiz = DB::transaction(function () use ($data) {
            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])->orderBy('display_order')->value('name');
            $teacherId = Auth::user()->isSuperadmin() && ! empty($data['teacher_id']) ? $data['teacher_id'] : Auth::id();
            $isPublished = ($data['action'] ?? '') === 'publish';

            $quiz = Quiz::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'grade' => $grade,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'is_published' => $isPublished,
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
                } elseif (in_array($question['type'], ['true_false', 'multiple_choice'], true)) {
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

            ActivityLogger::log('quiz_created', "Created quiz via AI: '{$quiz->title}'", $quiz);

            return $quiz;
        });

        if (in_array($data['action'] ?? '', ['save', 'save_and_edit'], true)) {
            $message = ($data['action'] ?? '') === 'save_and_edit'
                ? "Quiz '{$quiz->title}' saved. You can create another activity."
                : "Quiz '{$quiz->title}' saved as a draft.";

            return redirect()->route('teacher.ai-creator.create')->with('success', $message);
        }

        return redirect()->route('teacher.quizzes.index')->with('success', "Quiz '{$quiz->title}' published successfully via AI.");
    }

    protected function storeSeatwork(Request $request): RedirectResponse
    {
        $this->normalizeGradeLevelIds($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'action' => ['nullable', 'string', 'in:save,publish,save_and_edit'],
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
        ]);

        $seatwork = DB::transaction(function () use ($data) {
            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])->orderBy('display_order')->value('name');
            $teacherId = Auth::user()->isSuperadmin() && ! empty($data['teacher_id']) ? $data['teacher_id'] : Auth::id();
            $isPublished = ($data['action'] ?? '') === 'publish';

            $seatwork = Seatwork::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'grade' => $grade,
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'is_published' => $isPublished,
            ]);

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

                if (in_array($question['type'], ['true_false', 'multiple_choice'], true)) {
                    foreach ($question['options'] ?? [] as $oIndex => $option) {
                        $created->options()->create([
                            'option_text' => $option['option_text'] ?? '',
                            'is_correct' => (bool) ($option['is_correct'] ?? false),
                            'order' => $oIndex,
                        ]);
                    }
                }

                if ($question['type'] === 'matching') {
                    foreach ($question['matching_pairs'] ?? [] as $mIndex => $pair) {
                        if (trim($pair['left_text'] ?? '') === '' || trim($pair['right_text'] ?? '') === '') continue;
                        $created->matchingPairs()->create([
                            'left_text' => $pair['left_text'],
                            'right_text' => $pair['right_text'],
                            'order' => $mIndex,
                        ]);
                    }
                }
            }

            ActivityLogger::log('seatwork_created', "Created seatwork via AI: '{$seatwork->title}'", $seatwork);

            return $seatwork;
        });

        if (in_array($data['action'] ?? '', ['save', 'save_and_edit'], true)) {
            $message = ($data['action'] ?? '') === 'save_and_edit'
                ? "Seatwork '{$seatwork->title}' saved. You can create another activity."
                : "Seatwork '{$seatwork->title}' saved as a draft.";

            return redirect()->route('teacher.ai-creator.create')->with('success', $message);
        }

        return redirect()->route('teacher.seatworks.index')->with('success', "Seatwork '{$seatwork->title}' published successfully via AI.");
    }

    protected function storePractical(Request $request): RedirectResponse
    {
        $this->normalizeGradeLevelIds($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'max_score' => ['nullable', 'integer', 'min:1'],
            'xp_reward' => ['nullable', 'integer', 'min:1', 'max:500'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:10'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'action' => ['nullable', 'string', 'in:save,publish,save_and_edit'],
            'criteria' => ['required', 'array', 'min:1'],
            'criteria.*.criterion_name' => ['required', 'string', 'max:255'],
            'criteria.*.description' => ['nullable', 'string'],
            'criteria.*.max_points' => ['required', 'integer', 'min:1'],
        ]);

        $practical = DB::transaction(function () use ($data) {
            $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])->orderBy('display_order')->value('name');
            $teacherId = Auth::user()->isSuperadmin() && ! empty($data['teacher_id']) ? $data['teacher_id'] : Auth::id();
            $isPublished = ($data['action'] ?? '') === 'publish';

            $practical = Practical::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'grade' => $grade,
                'instructions' => $data['instructions'] ?? null,
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'max_score' => $data['max_score'] ?? (int) config('gamification.practical_max_score', 100),
                'xp_reward' => $data['xp_reward'] ?? (int) config('gamification.practical_xp_reward', 10),
                'max_attempts' => $data['max_attempts'] ?? (int) config('gamification.practical_max_attempts', 3),
                'is_published' => $isPublished,
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

            ActivityLogger::log('practical_created', "Created practical via AI: '{$practical->title}'", $practical);

            return $practical;
        });

        if (in_array($data['action'] ?? '', ['save', 'save_and_edit'], true)) {
            $message = ($data['action'] ?? '') === 'save_and_edit'
                ? "Practical '{$practical->title}' saved. You can create another activity."
                : "Practical '{$practical->title}' saved as a draft.";

            return redirect()->route('teacher.ai-creator.create')->with('success', $message);
        }

        return redirect()->route('teacher.practicals.index')->with('success', "Practical '{$practical->title}' published successfully via AI.");
    }

    protected function storeExam(Request $request): RedirectResponse
    {
        $this->normalizeGradeLevelIds($request);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'instructions' => ['nullable', 'string'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'sections' => ['nullable', 'array'],
            'questions' => ['nullable', 'array'],
            'criteria' => ['nullable', 'array'],
            'action' => ['nullable', 'string', 'in:save,save_draft,save_and_publish,save_and_edit'],
        ]);

        $teacherId = Auth::user()->isSuperadmin() && !empty($data['teacher_id']) ? $data['teacher_id'] : Auth::id();
        $isPublished = ($data['action'] ?? '') === 'save_and_publish';

        $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])
            ->orderBy('display_order')
            ->value('name') ?? 'All Grades';

        $exam = DB::transaction(function () use ($data, $teacherId, $grade, $isPublished) {
            $exam = Exam::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'grade' => $grade,
                'instructions' => $data['instructions'] ?? '',
                'time_limit_minutes' => $data['time_limit_minutes'] ?? null,
                'is_published' => $isPublished,
            ]);

            $exam->gradeLevels()->sync($data['grade_level_ids']);

            // If questions provided at root level, package into a default questions section
            $questions = $data['questions'] ?? [];
            if (!empty($questions)) {
                $section = $exam->sections()->create([
                    'title' => 'Part I: Questions',
                    'section_type' => 'questions',
                    'order' => 0,
                    'instructions' => 'Answer all questions carefully.',
                ]);

                foreach ($questions as $qIndex => $q) {
                    $qType = $q['type'] ?? 'multiple_choice';
                    $question = $section->questions()->create([
                        'question_text' => $q['question_text'],
                        'type' => $qType,
                        'points' => (int) ($q['points'] ?? 1),
                        'order' => $qIndex,
                    ]);

                    if ($qType === 'multiple_choice' || $qType === 'true_false') {
                        $opts = $q['options'] ?? [];
                        foreach ($opts as $oIndex => $opt) {
                            $question->options()->create([
                                'option_text' => $opt['option_text'] ?? "Option {$oIndex}",
                                'is_correct' => (bool) ($opt['is_correct'] ?? false),
                                'order' => $oIndex,
                            ]);
                        }
                    } elseif ($qType === 'identification') {
                        $correct = $q['correct_answer'] ?? ($q['options'][0]['option_text'] ?? $q['question_text']);
                        $question->options()->create([
                            'option_text' => $correct,
                            'is_correct' => true,
                            'order' => 0,
                        ]);
                    } elseif ($qType === 'enumeration') {
                        $items = $q['enum_items'] ?? (isset($q['options']) ? array_column($q['options'], 'option_text') : []);
                        foreach ($items as $eIndex => $item) {
                            $question->options()->create([
                                'option_text' => (string) $item,
                                'is_correct' => true,
                                'order' => $eIndex,
                            ]);
                        }
                    } elseif ($qType === 'matching') {
                        $pairs = $q['matching_pairs'] ?? [];
                        foreach ($pairs as $pIndex => $pair) {
                            $question->matchingPairs()->create([
                                'left_text' => $pair['left_text'] ?? '',
                                'right_text' => $pair['right_text'] ?? '',
                                'order' => $pIndex,
                            ]);
                        }
                    }
                }
            }

            // If criteria provided, package into a practical section
            $criteria = $data['criteria'] ?? [];
            if (!empty($criteria)) {
                $pSection = $exam->sections()->create([
                    'title' => 'Part II: Practical Assessment',
                    'section_type' => 'practical',
                    'order' => 1,
                    'instructions' => 'Perform the required tasks demonstrated below.',
                ]);

                foreach ($criteria as $cIndex => $crit) {
                    $pSection->criteria()->create([
                        'criterion_name' => $crit['criterion_name'],
                        'description' => $crit['description'] ?? '',
                        'max_points' => (int) ($crit['max_points'] ?? 25),
                        'order' => $cIndex,
                    ]);
                }
            }

            return $exam;
        });

        if (in_array($data['action'] ?? '', ['save', 'save_draft', 'save_and_edit'], true)) {
            $message = ($data['action'] ?? '') === 'save_and_edit'
                ? "Exam '{$exam->title}' saved. You can create another activity."
                : "Exam '{$exam->title}' saved as a draft.";

            return redirect()->route('teacher.ai-creator.create')->with('success', $message);
        }

        return redirect()->route('teacher.exams.index')->with('success', "Exam '{$exam->title}' published successfully via AI.");
    }

    protected function storeGame(Request $request): RedirectResponse
    {
        $this->normalizeGradeLevelIds($request);

        if (! $request->filled('subject_id') && $request->filled('subject')) {
            $existing = Subject::whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $request->input('subject')))])->first();
            if ($existing) {
                $request->merge(['subject_id' => $existing->id]);
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'required_without:subject', 'integer', 'exists:subjects,id'],
            'grade_level_ids' => ['required', 'array', 'min:1'],
            'grade_level_ids.*' => ['exists:grade_levels,id'],
            'game_type' => ['required', 'string', 'in:' . \App\Enums\GameType::validationList()],
            'description' => ['nullable', 'string'],
            'xp_reward' => ['nullable', 'integer', 'min:5', 'max:500'],
            'cards' => ['required', 'array', 'min:1'],
            'cards.*.question' => ['nullable', 'string'],
            'cards.*.answer' => ['required', 'string'],
            'cards.*.options' => ['nullable', 'array'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'action' => ['nullable', 'string', 'in:save,publish,save_and_edit'],
        ]);

        $teacherId = Auth::user()->isSuperadmin() && !empty($data['teacher_id']) ? $data['teacher_id'] : Auth::id();
        $grade = GradeLevel::whereIn('id', $data['grade_level_ids'])->orderBy('display_order')->value('name') ?? 'All Grades';

        $subject = ! empty($data['subject_id']) ? Subject::whereKey($data['subject_id'])->where('is_active', true)->firstOrFail() : null;
        abort_unless(Auth::user()->isSuperadmin() || ! $subject || $subject->teachers()->whereKey($teacherId)->exists(), 422, 'The selected subject is not assigned to this teacher.');

        $game = DB::transaction(function () use ($data, $teacherId, $grade, $subject) {
            $game = Game::create([
                'teacher_id' => $teacherId,
                'title' => $data['title'],
                'subject' => $subject?->name ?? ($data['subject'] ?? 'General'),
                'subject_id' => $subject?->id,
                'grade' => $grade,
                'description' => $data['description'] ?? '',
                'xp_reward' => (int) ($data['xp_reward'] ?? 25),
                'type' => $data['game_type'],
                'hidden' => false,
            ]);

            foreach ($data['cards'] as $i => $card) {
                $game->cards()->create([
                    'question' => !empty(trim((string) ($card['question'] ?? ''))) ? $card['question'] : 'Game Card',
                    'answer' => $card['answer'],
                    'color' => null,
                    'order' => $i,
                    'options' => $card['options'] ?? null,
                ]);
            }

            return $game;
        });

        if (in_array($request->input('action'), ['save', 'save_and_edit'], true)) {
            $message = $request->input('action') === 'save_and_edit'
                ? "Game '{$game->title}' saved. You can create another activity."
                : "Game '{$game->title}' saved as a draft.";

            return redirect()->route('teacher.ai-creator.create')->with('success', $message);
        }

        return redirect()->route('teacher.games.index')->with('success', "Game '{$game->title}' published successfully via AI.");
    }

    protected function normalizeGradeLevelIds(Request $request): void
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
    }
}
