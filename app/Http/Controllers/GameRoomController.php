<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Enums\GameType;
use App\Events\GameRoomAnswered;
use App\Events\GameRoomUpdated;
use App\Models\Game;
use App\Models\GameRoom;
use App\Models\GameRoomParticipant;
use App\Models\GradeLevel;
use App\Models\StudentPoint;
use App\Models\Subject;
use App\Models\TeachingMaterial;
use App\Helpers\PointsHelper;
use App\Services\AnswerMatcherService;
use App\Services\MaterialTextExtractor;
use App\Services\QuizGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GameRoomController extends Controller
{
    /**
     * Teacher: Dedicated Live Game creation page.
     */
    public function teacherLiveCreate(Request $request): Response
    {
        $user = Auth::user();
        abort_unless($user->isTeacher() || $user->isSuperadmin(), 403);

        $gradeLevels = GradeLevel::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name']);

        $materials = TeachingMaterial::query()
            ->when(! $user->isSuperadmin(), fn ($q) => $q->where('teacher_id', $user->id))
            ->with(['gradeLevel:id,name', 'subjectRecord:id,name'])
            ->orderByDesc('id')
            ->get(['id', 'title', 'subject', 'subject_id', 'file_name', 'file_type', 'grade_level_id']);

        $initialMaterialId = $request->query('material_id');

        return Inertia::render('Teacher/Games/LiveCreate', [
            'gradeLevels' => $gradeLevels,
            'materials' => $materials,
            'subjects' => Subject::where('is_active', true)
                ->when(! $user->isSuperadmin(), fn ($q) => $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id)))
                ->orderBy('name')->get(['id', 'name']),
            'initialMaterialId' => $initialMaterialId ? (int) $initialMaterialId : null,
            'availableTypes' => [
                ['value' => 'speedquiz', 'label' => 'Speed Quiz', 'desc' => 'Fast-paced multiple choice showdown'],
                ['value' => 'quiz', 'label' => 'Classic Quiz', 'desc' => '4-option question arena'],
                ['value' => 'truefalse', 'label' => 'True or False', 'desc' => 'Rapid binary knowledge check'],
                ['value' => 'fourpics', 'label' => '4 Pics 1 Word', 'desc' => 'Visual clue solving competition'],
                ['value' => 'flashcard', 'label' => 'Flashcard Match', 'desc' => 'Terminology and identification'],
                ['value' => 'crossword', 'label' => 'Crossword Arena', 'desc' => 'Vocabulary clue racing'],
            ],
        ]);
    }

    /**
     * Teacher: Generate live arena cards using AI generator.
     */
    public function generateAiQuestions(Request $request): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user->isTeacher() || $user->isSuperadmin(), 403);

        $request->validate([
            'prompt' => ['nullable', 'string', 'max:10000'],
            'material_id' => ['nullable', 'integer', 'exists:teaching_materials,id'],
            'material_ids' => ['nullable', 'array', 'max:15'],
            'material_ids.*' => ['integer', 'exists:teaching_materials,id'],
            'count' => ['nullable', 'integer', 'min:3', 'max:25'],
            'game_type' => ['nullable', 'string', 'in:' . GameType::validationList()],
        ]);

        $rawMaterialIds = $request->input('material_ids');
        if (! is_array($rawMaterialIds) || empty($rawMaterialIds)) {
            $single = $request->input('material_id');
            $rawMaterialIds = $single ? [(int) $single] : [];
        }

        $materialIds = array_values(array_unique(array_filter(array_map('intval', $rawMaterialIds))));
        $materials = collect();
        $text = '';

        if (! empty($materialIds)) {
            $materials = TeachingMaterial::with('gradeLevel')
                ->whereIn('id', $materialIds)
                ->get();

            if (! $user->isSuperadmin()) {
                foreach ($materials as $mat) {
                    if ($mat->teacher_id !== $user->id) {
                        abort(403, 'Unauthorized access to teaching material #' . $mat->id);
                    }
                }
            }

            $compiledSections = [];
            $failedMaterials = [];

            foreach ($materials as $mat) {
                $extracted = MaterialTextExtractor::extract($mat);
                if (isset($extracted['error'])) {
                    $failedMaterials[] = $mat->title ?: "Material #{$mat->id}";
                    continue;
                }

                $matText = trim((string) ($extracted['text'] ?? ''));
                if ($matText !== '') {
                    $sectionHeader = "=== Material: " . ($mat->title ?: 'Notes') . ($mat->subject ? " ({$mat->subject})" : '') . " ===";
                    $compiledSections[] = "{$sectionHeader}\n{$matText}";
                }
            }

            if (empty($compiledSections) && ! empty($materials)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Could not extract readable text from selected materials: ' . implode(', ', $failedMaterials),
                ], 422);
            }

            $text = implode("\n\n", $compiledSections);
        }

        $userPrompt = trim((string) $request->input('prompt', ''));
        if ($userPrompt !== '') {
            $text = $text !== '' ? $text . "\n\nAdditional teacher instructions:\n" . $userPrompt : $userPrompt;
        }

        if (trim($text) === '') {
            return response()->json([
                'ok' => false,
                'message' => 'Please provide a topic prompt or select an uploaded teaching material with readable content.',
            ], 422);
        }

        $count = (int) ($request->input('count', 5));
        $gameType = (string) $request->input('game_type', 'speedquiz');

        $result = QuizGeneratorService::generateFor('game', $text, $count, [], $gameType);
        $items = $result['items'] ?? [];

        if (empty($items) && $userPrompt !== '') {
            // If short prompt or offline heuristic needs more complete statements, expand into topic sentences
            $expanded = "The topic {$userPrompt} introduces foundational concepts and core principles. Studying {$userPrompt} develops essential understanding and subject vocabulary. Students analyze how {$userPrompt} operates in practical scenarios. Mastering {$userPrompt} requires accurate identification of key definitions.";
            $fallback = QuizGeneratorService::generateFor('game', $expanded, $count, [], $gameType);
            $items = $fallback['items'] ?? [];
        }

        if (empty($items)) {
            return response()->json([
                'ok' => false,
                'message' => 'Could not generate questions from this material or topic. Please try another material or provide more details.',
            ], 422);
        }

        $colors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4'];
        $formatted = array_map(function ($item, $idx) use ($colors) {
            return [
                'question' => $item['question'] ?? 'Question',
                'answer' => $item['answer'] ?? '',
                'options' => $item['options'] ?? [],
                'color' => $colors[$idx % count($colors)],
                'order' => $idx,
            ];
        }, $items, array_keys($items));

        $primaryMaterial = $materials->first();

        return response()->json([
            'ok' => true,
            'provider' => $result['provider'] ?? 'heuristic',
            'cards' => $formatted,
            'count' => count($formatted),
            'materials' => $materials->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'subject' => $m->subjectRecord?->name ?? $m->subject,
                'subject_id' => $m->subject_id,
                'grade_level_id' => $m->grade_level_id,
                'grade_name' => $m->gradeLevel?->name,
            ])->values(),
            'material' => $primaryMaterial ? [
                'id' => $primaryMaterial->id,
                'title' => $primaryMaterial->title,
                'subject' => $primaryMaterial->subjectRecord?->name ?? $primaryMaterial->subject,
                'subject_id' => $primaryMaterial->subject_id,
                'grade_level_id' => $primaryMaterial->grade_level_id,
                'grade_name' => $primaryMaterial->gradeLevel?->name,
            ] : null,
        ]);
    }

    /**
     * Teacher: Store custom live game and immediately launch live arena room.
     */
    public function teacherStoreLiveGame(Request $request): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isTeacher() || $user->isSuperadmin(), 403);

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
            'grade' => ['nullable', 'string', 'max:50'],
            'grade_level_id' => ['nullable', 'exists:grade_levels,id'],
            'type' => ['required', 'string', 'in:' . GameType::validationList()],
            'xp_reward' => ['nullable', 'integer', 'min:10', 'max:500'],
            'description' => ['nullable', 'string', 'max:1000'],
            'launch' => ['nullable', 'boolean'],
            'cards' => ['required', 'array', 'min:1'],
            'cards.*.question' => ['required', 'string'],
            'cards.*.answer' => ['required', 'string'],
            'cards.*.options' => ['nullable', 'array'],
            'cards.*.color' => ['nullable', 'string'],
        ]);

        if (empty($data['grade']) && !empty($data['grade_level_id'])) {
            $data['grade'] = GradeLevel::where('id', $data['grade_level_id'])->value('name');
        }

        $subject = ! empty($data['subject_id'])
            ? Subject::whereKey($data['subject_id'])->where('is_active', true)->firstOrFail()
            : null;
        if (! $subject && ! empty($data['subject'])) {
            $name = trim((string) $data['subject']);
            $subject = Subject::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if (! $subject) {
                $subject = Subject::create(['name' => $name, 'is_active' => true]);
            }
            if (! $subject->teachers()->whereKey($user->id)->exists() && ! $user->isSuperadmin()) {
                $subject->teachers()->syncWithoutDetaching([$user->id]);
            }
        }
        abort_unless($user->isSuperadmin() || ! $subject || $subject->teachers()->whereKey($user->id)->exists(), 422, 'The selected subject is not assigned to you.');

        $game = Game::create([
            'teacher_id' => $user->id,
            'title' => $data['title'],
            'subject' => $subject?->name ?? trim((string) ($data['subject'] ?? 'General')),
            'subject_id' => $subject?->id,
            'grade' => $data['grade'] ?? 'All Grades',
            'type' => $data['type'],
            'xp_reward' => (int) ($data['xp_reward'] ?? 50),
            'description' => $data['description'] ?? 'Live Match Arena',
            'hidden' => true,
            'is_live' => true,
        ]);

        foreach ($data['cards'] as $index => $c) {
            $game->cards()->create([
                'question' => $c['question'],
                'answer' => $c['answer'],
                'options' => $c['options'] ?? null,
                'color' => $c['color'] ?? '#10b981',
                'order' => $index,
            ]);
        }

        $shouldLaunch = $request->boolean('launch', true);

        if (! $shouldLaunch) {
            return redirect()->route('teacher.games.index')
                ->with('success', 'Live Arena match "' . $game->title . '" saved! You can host it whenever you want from the Live Arena tab.');
        }

        // Close any previous waiting rooms by this teacher with identical game title
        GameRoom::where('teacher_id', $user->id)
            ->where('status', 'waiting')
            ->whereHas('game', fn ($q) => $q->where('title', $data['title']))
            ->delete();

        do {
            $pin = (string) random_int(100000, 999999);
        } while (GameRoom::where('pin', $pin)->exists());

        $room = GameRoom::create([
            'game_id' => $game->id,
            'teacher_id' => $user->id,
            'pin' => $pin,
            'status' => 'waiting',
            'current_card_index' => 0,
            'state' => [
                'first_solvers' => [],
                'randomize_player_order' => true,
            ],
        ]);

        return redirect()->route('teacher.games.rooms.host', $room->pin)
            ->with('success', 'Live Match Arena created! Share PIN ' . $pin . ' with your students.');
    }

    /**
     * Teacher: Create a new live game room from an existing game.
     */
    public function teacherCreate(Request $request, Game $game): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isTeacher() || $user->isSuperadmin(), 403);

        // Close any previous waiting rooms for this game by this teacher
        GameRoom::where('game_id', $game->id)
            ->where('teacher_id', $user->id)
            ->whereIn('status', ['waiting', 'active'])
            ->delete();

        do {
            $pin = (string) random_int(100000, 999999);
        } while (GameRoom::where('pin', $pin)->exists());

        $room = GameRoom::create([
            'game_id' => $game->id,
            'teacher_id' => $user->id,
            'pin' => $pin,
            'status' => 'waiting',
            'current_card_index' => 0,
            'state' => [
                'first_solvers' => [],
                'randomize_player_order' => true,
            ],
        ]);

        return redirect()->route('teacher.games.rooms.host', $room->pin)
            ->with('success', 'Game room created! Share the PIN with your students.');
    }

    /**
     * Teacher: Host dashboard view.
     */
    public function teacherHost(GameRoom $room): Response
    {
        $user = Auth::user();
        abort_unless((int) $room->teacher_id === (int) $user->id || $user->isSuperadmin(), 403);

        $room->load(['game.cards' => fn ($q) => $q->orderBy('order'), 'participants.user']);

        return Inertia::render('Teacher/Games/LiveHost', [
            'room' => $this->formatRoomForHost($room),
        ]);
    }

    /**
     * Teacher: Start the live room.
     */
    public function teacherStart(GameRoom $room): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_unless((int) $room->teacher_id === (int) $user->id || $user->isSuperadmin(), 403);

        $room->update([
            'status' => 'active',
            'current_card_index' => 0,
        ]);

        $payload = $this->buildRoomPayload($room);
        event(new GameRoomUpdated($room->pin, $payload));

        if (request()->wantsJson()) {
            return response()->json(['ok' => true, 'room' => $payload]);
        }

        return back();
    }

    /**
     * Teacher: Advance to the next question.
     */
    public function teacherNext(GameRoom $room): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_unless((int) $room->teacher_id === (int) $user->id || $user->isSuperadmin(), 403);

        $room->load('game.cards');
        $total = $room->game->cards->count();

        if ($room->current_card_index + 1 >= $total) {
            return $this->teacherEnd($room);
        }

        $room->increment('current_card_index');

        $payload = $this->buildRoomPayload($room);
        event(new GameRoomUpdated($room->pin, $payload));

        if (request()->wantsJson()) {
            return response()->json(['ok' => true, 'room' => $payload]);
        }

        return back();
    }

    /**
     * Teacher: End the live room and award podium XP.
     */
    public function teacherEnd(GameRoom $room): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_unless((int) $room->teacher_id === (int) $user->id || $user->isSuperadmin(), 403);

        $room->update(['status' => 'finished']);

        // Award points to participants
        $this->awardPodiumPoints($room);

        $payload = $this->buildRoomPayload($room);
        event(new GameRoomUpdated($room->pin, $payload));

        if (request()->wantsJson()) {
            return response()->json(['ok' => true, 'room' => $payload]);
        }

        return back();
    }

    /**
     * Teacher: Delete / dismiss a live game room.
     */
    public function destroy(GameRoom $room): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        abort_unless((int) $room->teacher_id === (int) $user->id || $user->isSuperadmin(), 403);

        $pin = $room->pin;
        $room->delete();

        event(new GameRoomUpdated($pin, ['status' => 'finished', 'deleted' => true]));

        if (request()->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Live Arena room deleted successfully.']);
        }

        return redirect()->route('teacher.games.index')
            ->with('success', 'Live Match Arena (PIN: ' . $pin . ') deleted successfully.');
    }

    /**
     * Student: Join room via PIN.
     */
    public function studentJoin(Request $request): RedirectResponse
    {
        $request->validate([
            'pin' => ['required', 'string'],
        ]);

        $pin = trim($request->input('pin'));
        $room = GameRoom::where('pin', $pin)->first();

        if (! $room) {
            return back()->withErrors(['pin' => 'Game room not found. Check the PIN and try again.']);
        }

        if ($room->status === 'finished') {
            return redirect()->route('student.games.rooms.play', $room->pin);
        }

        $user = Auth::user();
        $room->loadMissing('game');

        if ($user->isStudent() && ! $room->isAccessibleToStudent($user)) {
            $gradeTarget = $room->game?->grade ?: 'another grade';
            return back()->withErrors(['pin' => 'This live match arena is for ' . $gradeTarget . ' students only.']);
        }

        $room->participants()->firstOrCreate([
            'user_id' => $user->id,
        ]);

        return redirect()->route('student.games.rooms.play', $room->pin);
    }

    /**
     * Student: Live match player view.
     */
    public function studentPlay(GameRoom $room): Response|RedirectResponse
    {
        $user = Auth::user();
        $room->loadMissing('game');

        if ($user->isStudent() && ! $room->isAccessibleToStudent($user)) {
            $gradeTarget = $room->game?->grade ?: 'another grade';
            return redirect()->route('student.games')
                ->withErrors(['error' => 'This live match arena is for ' . $gradeTarget . ' students only.']);
        }

        $room->load(['game.cards' => fn ($q) => $q->orderBy('order')]);

        $participant = $room->participants()->where('user_id', $user->id)->first();
        if (! $participant && $room->status !== 'finished') {
            $participant = $room->participants()->create([
                'user_id' => $user->id,
            ]);
        }

        $state = $room->state ?? [];
        $firstSolver = $state['first_solvers'][$room->current_card_index] ?? null;
        $this->ensurePlayerCardOrder($room, $participant, $room->game->cards, $user->isStudent());

        return Inertia::render('Student/Games/LiveRoom', [
            'room' => [
                'id' => $room->id,
                'pin' => $room->pin,
                'status' => $room->status,
                'current_card_index' => $room->current_card_index,
                'total_cards' => $room->game->cards->count(),
                'first_solver' => $firstSolver,
            ],
            'game' => [
                'id' => $room->game->id,
                'title' => $room->game->title,
                'type' => $room->game->type,
                'subject' => $room->game->subject,
                'grade' => $room->game->grade,
            ],
            'card' => $this->sanitizeCardForStudent($room, $participant, $user->isStudent()),
            'participant' => [
                'id' => $participant?->id ?? 0,
                'score' => (int) ($participant?->score ?? 0),
                'streak' => (int) ($participant?->streak ?? 0),
                'last_answered_card_index' => $participant?->last_answered_card_index,
            ],
            'leaderboard' => $this->getLeaderboard($room),
        ]);
    }

    /**
     * Fallback poll endpoint for real-time room status & sync.
     */
    public function roomStatus(GameRoom $room): JsonResponse
    {
        $user = Auth::user();
        $room->loadMissing('game');

        if ($user && $user->isStudent() && ! $room->isAccessibleToStudent($user)) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized grade.'], 403);
        }

        $room->load(['game.cards' => fn ($q) => $q->orderBy('order')]);

        $state = $room->state ?? [];
        $firstSolver = $state['first_solvers'][$room->current_card_index] ?? null;

        $participantModel = null;
        $participant = null;
        if ($user) {
            $participantModel = $room->participants()->where('user_id', $user->id)->first();
            if ($participantModel) {
                $this->ensurePlayerCardOrder($room, $participantModel, $room->game->cards, $user->isStudent());
                $participant = [
                    'id' => $participantModel->id,
                    'score' => $participantModel->score,
                    'streak' => $participantModel->streak,
                    'last_answered_card_index' => $participantModel->last_answered_card_index,
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'status' => $room->status,
            'current_card_index' => $room->current_card_index,
            'total_cards' => $room->game->cards->count(),
            'card' => $user && ($user->isTeacher() || $user->isSuperadmin())
                ? $this->formatRoomForHost($room)['current_card']
                : $this->sanitizeCardForStudent($room, $participantModel, (bool) $user?->isStudent()),
            'first_solver' => $firstSolver,
            'participant' => $participant,
            'leaderboard' => $this->getLeaderboard($room),
        ]);
    }

    /**
     * Student: Submit answer for current card with first-to-answer reward.
     */
    public function studentAnswer(Request $request, GameRoom $room): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user->isStudent() || $user->isTeacher() || $user->isSuperadmin(), 403);

        $room->loadMissing('game');
        if ($user->isStudent()) {
            abort_unless($room->isAccessibleToStudent($user), 403, 'This live match arena is for ' . ($room->game?->grade ?? 'another grade') . ' students only.');
        }

        $request->validate([
            'card_index' => ['required', 'integer'],
            'answer' => ['nullable'],
        ]);

        $cardIndex = (int) $request->input('card_index');
        $studentAnswer = $request->input('answer');

        return DB::transaction(function () use ($room, $user, $cardIndex, $studentAnswer) {
            /** @var GameRoom $lockedRoom */
            $lockedRoom = GameRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedRoom->status === 'active', 400, 'Game is not currently active.');

            if ($cardIndex !== (int) $lockedRoom->current_card_index) {
                return response()->json([
                    'ok' => false,
                    'message' => 'This question is no longer active.',
                ], 422);
            }

            /** @var GameRoomParticipant $participant */
            $participant = $lockedRoom->participants()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            if ($participant->last_answered_card_index === $cardIndex) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Already submitted an answer for this question.',
                ], 422);
            }

            $cards = $lockedRoom->game->cards()->orderBy('order')->get();
            $cardOrder = $this->ensurePlayerCardOrder($lockedRoom, $participant, $cards, $user->isStudent());
            $cardId = $cardOrder[$cardIndex] ?? null;
            $card = $cards->first(fn ($candidate) => (int) $candidate->id === (int) $cardId);

            if (! $card) {
                return response()->json(['ok' => false, 'message' => 'Card not found.'], 404);
            }

            $isCorrect = $this->evaluateAnswer($card, $studentAnswer);

            $state = $lockedRoom->state ?? [];
            $firstSolvers = $state['first_solvers'] ?? [];
            $isFirstSolver = false;
            $firstBonus = 0;
            $firstSolverName = $firstSolvers[$cardIndex]['user_name'] ?? null;

            if ($isCorrect) {
                $basePoints = 1000;
                $streakBonus = (int) $participant->streak * 100;

                // Award first-to-answer bonus if nobody answered correctly yet
                if (! isset($firstSolvers[$cardIndex])) {
                    $isFirstSolver = true;
                    $firstBonus = 500;
                    $firstSolverName = $user->name;
                    $firstSolvers[$cardIndex] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'bonus' => $firstBonus,
                        'answered_at' => now()->toIso8601String(),
                    ];
                    $state['first_solvers'] = $firstSolvers;
                    $lockedRoom->update(['state' => $state]);
                }

                $pointsEarned = $basePoints + $streakBonus + $firstBonus;
                $participant->increment('score', $pointsEarned);
                $participant->increment('streak', 1);
            } else {
                $participant->update(['streak' => 0]);
                $pointsEarned = 0;
            }

            $participant->update([
                'last_answered_card_index' => $cardIndex,
                'last_answered_at' => now(),
            ]);

            $leaderboard = $this->getLeaderboard($lockedRoom);

            // Broadcast to host and room participants
            event(new GameRoomAnswered($lockedRoom->pin, [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'correct' => $isCorrect,
                'is_first_solver' => $isFirstSolver,
                'first_solver_name' => $firstSolverName,
                'first_bonus' => $firstBonus,
                'points_earned' => $pointsEarned,
                'score' => $participant->fresh()->score,
                'streak' => $participant->fresh()->streak,
                'leaderboard' => $leaderboard,
            ]));

            return response()->json([
                'ok' => true,
                'correct' => $isCorrect,
                'is_first_solver' => $isFirstSolver,
                'first_solver_name' => $firstSolverName,
                'first_bonus' => $firstBonus,
                'points_earned' => $pointsEarned,
                'total_score' => $participant->fresh()->score,
                'streak' => $participant->fresh()->streak,
                'correct_answer' => $card->answer,
            ]);
        });
    }

    /**
     * Evaluate student answer against card.
     */
    private function evaluateAnswer($card, $studentAnswer): bool
    {
        if ($studentAnswer === null || $studentAnswer === '') {
            return false;
        }

        $expected = (string) $card->answer;

        // If array of answers or multiple choice
        if (is_array($studentAnswer)) {
            $studentAnswer = implode(', ', $studentAnswer);
        }

        // Exact match check
        if (trim(strtolower((string) $studentAnswer)) === trim(strtolower($expected))) {
            return true;
        }

        // AnswerMatcherService lenient evaluation
        return AnswerMatcherService::isNearMatch($expected, (string) $studentAnswer, (string) $card->question);
    }

    /**
     * Format room details for host view.
     */
    private function formatRoomForHost(GameRoom $room): array
    {
        $cards = $room->game->cards;
        $currentCard = $cards->get($room->current_card_index);

        return [
            'id' => $room->id,
            'pin' => $room->pin,
            'status' => $room->status,
            'current_card_index' => $room->current_card_index,
            'total_cards' => $cards->count(),
            'game' => [
                'id' => $room->game->id,
                'title' => $room->game->title,
                'type' => $room->game->type,
                'subject' => $room->game->subject,
                'grade' => $room->game->grade,
                'xp_reward' => $room->game->xp_reward,
            ],
            'current_card' => $currentCard ? [
                'id' => $currentCard->id,
                'question' => $currentCard->question,
                'answer' => $currentCard->answer,
                'options' => $this->getCardOptions($room, $currentCard),
                'color' => $currentCard->color,
                'order' => $currentCard->order,
            ] : null,
            'participants' => $room->participants->map(fn ($p) => [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'name' => $p->user->name ?? 'Student',
                'avatar' => $p->user->avatar_url ?? null,
                'score' => $p->score,
                'streak' => $p->streak,
                'last_answered_card_index' => $p->last_answered_card_index,
            ]),
            'first_solver' => ($room->state['first_solvers'] ?? [])[$room->current_card_index] ?? null,
            'leaderboard' => $this->getLeaderboard($room),
        ];
    }

    /**
     * Build real-time broadcast payload for game room update.
     */
    private function buildRoomPayload(GameRoom $room): array
    {
        $room->load(['game.cards' => fn ($q) => $q->orderBy('order'), 'participants.user']);
        $cards = $room->game->cards;
        $currentCard = $cards->get($room->current_card_index);

        return [
            'status' => $room->status,
            'current_card_index' => $room->current_card_index,
            'total_cards' => $cards->count(),
            'card' => $currentCard ? [
                'id' => $currentCard->id,
                'question' => $currentCard->question,
                'options' => $this->getCardOptions($room, $currentCard),
                'color' => $currentCard->color,
                'order' => $currentCard->order,
            ] : null,
            'first_solver' => ($room->state['first_solvers'] ?? [])[$room->current_card_index] ?? null,
            'leaderboard' => $this->getLeaderboard($room),
        ];
    }

    /**
     * Get sorted room leaderboard.
     */
    private function getLeaderboard(GameRoom $room): array
    {
        return $room->participants()
            ->with('user:id,name')
            ->orderByDesc('score')
            ->orderByDesc('streak')
            ->get()
            ->map(function ($p, $idx) {
                return [
                    'rank' => $idx + 1,
                    'user_id' => $p->user_id,
                    'name' => $p->user->name ?? 'Student',
                    'score' => $p->score,
                    'streak' => $p->streak,
                ];
            })
            ->toArray();
    }

    /**
     * Sanitize current card for student (do not expose answer).
     */
    private function sanitizeCardForStudent(
        GameRoom $room,
        ?GameRoomParticipant $participant = null,
        bool $randomize = false,
    ): ?array
    {
        $cards = $room->game->cards;
        $cardOrder = $participant
            ? $this->ensurePlayerCardOrder($room, $participant, $cards, $randomize)
            : $cards->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $currentCardId = $cardOrder[$room->current_card_index] ?? null;
        $currentCard = $cards->first(fn ($card) => (int) $card->id === (int) $currentCardId);

        if (! $currentCard) {
            return null;
        }

        return [
            'id' => $currentCard->id,
            'question' => $currentCard->question,
            'options' => $this->getCardOptions($room, $currentCard),
            'color' => $currentCard->color,
            'order' => $currentCard->order,
        ];
    }

    /**
     * Return a stable card order for a participant in a Live Arena room.
     *
     * The host still advances one shared round index, while each student sees
     * the card at that round in their own shuffled order.
     */
    private function ensurePlayerCardOrder(GameRoom $room, ?GameRoomParticipant $participant, $cards, bool $randomize): array
    {
        $cardIds = $cards->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        if (! $participant || ! $randomize || count($cardIds) < 2) {
            return $cardIds;
        }

        $state = $room->state ?? [];
        if (! $room->game->is_live && ! ($state['randomize_player_order'] ?? false)) {
            return $cardIds;
        }
        $orders = $state['player_orders'] ?? [];
        $key = (string) $participant->user_id;
        $existingOrder = $orders[$key] ?? null;

        if (is_array($existingOrder)) {
            $existingInts = array_map('intval', array_values($existingOrder));
            $sortedExisting = $existingInts;
            $sortedCardIds = $cardIds;
            sort($sortedExisting);
            sort($sortedCardIds);

            if ($sortedExisting === $sortedCardIds) {
                return $existingInts;
            }
        }

        $usedOrders = collect($orders)
            ->filter(fn ($order) => is_array($order))
            ->map(fn (array $order) => array_map('intval', array_values($order)))
            ->values()
            ->all();
        $shuffled = $cardIds;
        $attempts = 0;
        if (empty($usedOrders)) {
            do {
                shuffle($shuffled);
                $attempts++;
            } while ($attempts < 30 && $shuffled === $cardIds);
        } else {
            do {
                shuffle($shuffled);
                $attempts++;
            } while ($attempts < 30 && in_array($shuffled, $usedOrders, true));
        }

        if (in_array($shuffled, $usedOrders, true)) {
            foreach (range(0, count($cardIds) - 1) as $offset) {
                $candidate = array_merge(
                    array_slice($cardIds, $offset),
                    array_slice($cardIds, 0, $offset),
                );

                if (! in_array($candidate, $usedOrders, true)) {
                    $shuffled = $candidate;
                    break;
                }
            }
        }

        if (in_array($shuffled, $usedOrders, true)) {
            $candidate = array_reverse($cardIds);
            if (! in_array($candidate, $usedOrders, true)) {
                $shuffled = $candidate;
            }
        }

        $state['player_orders'][$key] = $shuffled;
        $room->state = $state;
        $room->save();

        return $shuffled;
    }

    /**
     * Award XP / Student Points to podium winners.
     */
    private function awardPodiumPoints(GameRoom $room): void
    {
        $participants = $room->participants()
            ->where('score', '>', 0)
            ->orderByDesc('score')
            ->orderByDesc('streak')
            ->get();

        // The configured reward is the actual 1st-place reward. Lower-ranked
        // participants receive a smaller share, without imposing a hardcoded
        // 50 XP minimum or doubling the teacher's configured value.
        $baseXp = max(1, (int) ($room->game->xp_reward ?? 50));

        foreach ($participants as $idx => $p) {
            $rank = $idx + 1;
            $xp = match ($rank) {
                1 => $baseXp,
                2 => max(1, (int) round($baseXp * 0.75)),
                3 => max(1, (int) round($baseXp * 0.6)),
                default => max(1, (int) round($baseXp * 0.4)),
            };

            // Older live-room rows used the game ID. Keep recognizing those
            // rows, while new rows use a room-specific identity so a live
            // reward cannot overwrite the student's normal game reward.
            $reason = 'Live Room PIN ' . $room->pin;
            $alreadyAwarded = StudentPoint::where('student_id', $p->user_id)
                ->where('activity_type', ActivityType::Game->value)
                ->where('reason', $reason)
                ->exists();

            if (! $alreadyAwarded) {
                $liveActivityId = 1_000_000_000 + (int) $room->id;
                PointsHelper::awardFixed(
                    $p->user_id,
                    ActivityType::Game->value,
                    $liveActivityId,
                    $xp,
                    $reason,
                    max(0, (int) $p->score),
                    max(1, (int) ($room->game->cards()->count() * 1000)),
                );
            }
        }
    }

    /**
     * Get deterministically shuffled options for a card in a given room.
     * Ensures all students and the host view the identical randomized option order.
     */
    private function getCardOptions(GameRoom $room, $card): ?array
    {
        if (! $card || empty($card->options) || ! is_array($card->options)) {
            return null;
        }

        $options = array_values($card->options);
        if (count($options) <= 1) {
            return $options;
        }

        // Keep canonical order for standard True / False cards
        $lower = array_map(fn ($o) => strtolower(trim((string) $o)), $options);
        if (count($options) === 2 && in_array('true', $lower, true) && in_array('false', $lower, true)) {
            return $options;
        }

        $seedKey = $room->pin . '-' . $card->id . '-' . $card->order;
        usort($options, function ($a, $b) use ($seedKey) {
            return strcmp(
                hash('sha256', $seedKey . ':' . (string) $a),
                hash('sha256', $seedKey . ':' . (string) $b)
            );
        });

        return array_values($options);
    }
}
