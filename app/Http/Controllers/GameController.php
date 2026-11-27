<?php

namespace App\Http\Controllers;

use App\Enums\ActivityType;
use App\Models\AiManagerLog;
use App\Models\Game;
use App\Models\GameProgress;
use App\Models\GameReopenRequest;
use App\Models\GameRoom;
use App\Models\GradeLevel;
use App\Models\Quarter;
use App\Models\SchoolYear;
use App\Models\Setting;
use App\Models\StudentPoint;
use App\Models\Subject;
use App\Models\User;
use App\Helpers\PointsHelper;
use App\Services\AiConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function studentGames(): Response
    {
        $student = Auth::user();
        $studentGamesQuery = Game::with('cards')
            ->with(['progress' => fn ($q) => $q->where('user_id', $student->id)])
            ->where('hidden', false)
            ->where('is_live', false)
            ->accessibleToStudent($student)
            ->latest();
        SchoolYear::applyFilter($studentGamesQuery, SchoolYear::currentId());
        $games = $studentGamesQuery->get();

        $gamePoints = StudentPoint::where('student_id', $student->id)
            ->where('activity_type', ActivityType::Game->value)
            ->pluck('points', 'activity_id');

        $correctAnswers = DB::table('game_correct_answers')
            ->where('user_id', $student->id)
            ->get(['game_id', 'card_id'])
            ->groupBy('game_id')
            ->map(fn ($group) => $group->pluck('card_id')->all());

        $reopenRequests = GameReopenRequest::where('user_id', $student->id)
            ->where('status', 'pending')
            ->pluck('id', 'game_id');

        $games->each(function ($game) use ($gamePoints, $correctAnswers, $reopenRequests) {
            $isPending = isset($reopenRequests[$game->id]);
            $game->reopen_pending = $isPending;
            if ($game->progress) {
                $game->progress->earned_xp = $gamePoints[$game->id] ?? null;
                $game->progress->correct_card_ids = $correctAnswers[$game->id] ?? [];
                $game->progress->reopen_pending = $isPending;
            }
        });

        $teacherIds = $student->teachers()->pluck('users.id');
        if ($student->isTeacher()) {
            $teacherIds = $teacherIds->concat([$student->id]);
        }
        $teacherIds = $teacherIds->concat(
            User::where('role', 'superadmin')->pluck('id')
        )->unique()->filter()->values();

        $activeRoomsQuery = GameRoom::with([
                'game:id,title,type,subject,grade',
                'teacher:id,name',
            ])
            ->withCount('participants')
            ->whereIn('status', ['waiting', 'active'])
            ->accessibleToStudent($student);

        if ($teacherIds->isNotEmpty()) {
            $activeRoomsQuery->whereIn('teacher_id', $teacherIds);
        }

        $activeRooms = $activeRoomsQuery
            ->latest()
            ->get(['id', 'game_id', 'teacher_id', 'pin', 'status', 'created_at']);

        $finishedRoomsQuery = GameRoom::with([
                'game:id,title,type,subject,grade',
                'teacher:id,name',
                'participants.user:id,name,grade',
            ])
            ->withCount('participants')
            ->where('status', 'finished')
            ->where(function ($q) use ($student, $teacherIds) {
                $q->whereHas('participants', fn ($p) => $p->where('user_id', $student->id))
                    ->orWhere(function ($accessibleQ) use ($student, $teacherIds) {
                        $accessibleQ->accessibleToStudent($student);
                        if ($teacherIds->isNotEmpty()) {
                            $accessibleQ->whereIn('teacher_id', $teacherIds);
                        }
                    });
            })
            ->latest()
            ->take(8);

        $recentFinishedRooms = $finishedRoomsQuery->get()->map(function ($room) use ($student) {
            $sorted = $room->participants
                ->sortByDesc('score')
                ->sortByDesc('streak')
                ->values();

            $myRank = null;
            $myScore = 0;
            $myStreak = 0;
            $hasParticipated = false;

            $top10 = $sorted->slice(0, 10)->map(function ($p, $idx) use ($student, &$myRank, &$myScore, &$myStreak, &$hasParticipated) {
                $isMe = (int) $p->user_id === (int) $student->id;
                if ($isMe) {
                    $myRank = $idx + 1;
                    $myScore = (int) $p->score;
                    $myStreak = (int) $p->streak;
                    $hasParticipated = true;
                }
                return [
                    'rank' => $idx + 1,
                    'user_id' => $p->user_id,
                    'name' => $p->user->name ?? 'Student',
                    'grade' => $p->user->grade ?? '—',
                    'score' => (int) $p->score,
                    'streak' => (int) $p->streak,
                    'is_me' => $isMe,
                ];
            })->values();

            if (! $hasParticipated) {
                $myIndex = $sorted->search(fn ($p) => (int) $p->user_id === (int) $student->id);
                if ($myIndex !== false) {
                    $myRank = $myIndex + 1;
                    $p = $sorted[$myIndex];
                    $myScore = (int) $p->score;
                    $myStreak = (int) $p->streak;
                    $hasParticipated = true;
                }
            }

            return [
                'id' => $room->id,
                'pin' => $room->pin,
                'game_id' => $room->game_id,
                'title' => $room->game?->title ?? 'Live Match',
                'subject' => $room->game?->subject ?? 'Arcade',
                'grade' => $room->game?->grade ?? 'All',
                'teacher_name' => $room->teacher?->name ?? 'Teacher',
                'total_participants' => $room->participants_count ?? $room->participants->count(),
                'has_participated' => $hasParticipated,
                'my_rank' => $myRank,
                'my_score' => $myScore,
                'my_streak' => $myStreak,
                'winner' => $top10->first(),
                'leaderboard' => $top10,
                'created_at' => $room->created_at?->format('M d, Y h:i A'),
                'ended_at' => $room->updated_at?->format('M d, Y h:i A'),
            ];
        });

        return Inertia::render('Student/Games', [
            'games' => $games,
            'activeRooms' => $activeRooms,
            'recentFinishedRooms' => $recentFinishedRooms,
            'hintCost' => max(1, (int) Setting::get('hint_xp_cost', 2)),
        ]);
    }

    public function saveProgress(Request $request, Game $game): JsonResponse
    {
        if ($game->is_live) {
            return response()->json([
                'ok' => false,
                'message' => 'Live Arena games can only be played in a live match session hosted by your teacher.',
            ], 403);
        }

        $student = Auth::user();

        $existingProgress = GameProgress::where('user_id', $student->id)
            ->where('game_id', $game->id)
            ->first();

        if ($existingProgress && $existingProgress->completed) {
            $existingPoint = StudentPoint::where('student_id', $student->id)
                ->where('activity_type', ActivityType::Game->value)
                ->where('activity_id', $game->id)
                ->value('points');
            $existingProgress->earned_xp = $existingPoint ?? 0;
            $existingProgress->correct_card_ids = DB::table('game_correct_answers')
                ->where('user_id', $student->id)
                ->where('game_id', $game->id)
                ->pluck('card_id')
                ->all();

            return response()->json([
                'ok' => true,
                'progress' => $existingProgress,
                'awarded' => false,
                'xp' => $existingProgress->earned_xp,
            ]);
        }

        $data = $request->validate([
            'current_card' => ['nullable', 'integer', 'min:0'],
            'done_count' => ['nullable', 'integer', 'min:0'],
            'score' => ['nullable', 'integer', 'min:0'],
            'completed' => ['nullable', 'boolean'],
            'card_order' => ['nullable', 'string', 'max:2000'],
            'graded' => ['nullable', 'array'],
        ]);

        $game->loadCount('cards');

        $completed = !empty($data['completed']);
        if (!$completed && !in_array($game->type, ['memorymatch', 'dragdrop', 'ordering', 'crossword', 'wordsearch', 'codepuzzle'], true)) {
            // 'flashcard' tracks the current card index (0-based), so reaching the
            // last card means the whole deck was seen.
            $threshold = $game->type === 'flashcard' ? ((int) $game->cards_count - 1) : (int) $game->cards_count;
            $completed = (int) ($data['done_count'] ?? 0) >= $threshold;
        }

        $correctCount = (int) DB::table('game_correct_answers')
            ->where('user_id', $student->id)
            ->where('game_id', $game->id)
            ->count();

        $score = max((int) ($data['score'] ?? 0), $correctCount);
        if ($existingProgress) {
            $score = max($score, (int) $existingProgress->score);
        }

        $progress = GameProgress::updateOrCreate(
            ['user_id' => $student->id, 'game_id' => $game->id],
            [
                'current_card' => $data['current_card'] ?? 0,
                'done_count' => max((int) ($data['done_count'] ?? 0), $score),
                'score' => $score,
                'completed' => $completed,
                'card_order' => $data['card_order'] ?? null,
                'graded' => isset($data['graded']) ? json_encode($data['graded']) : null,
            ]
        );

        $awarded = false;
        $xp = 0;
        if ($completed) {
            $existing = StudentPoint::where('student_id', $student->id)
                ->where('activity_type', ActivityType::Game->value)
                ->where('activity_id', $game->id)
                ->first();

            if (!$existing) {
                $correctCount = (int) DB::table('game_correct_answers')
                    ->where('user_id', $student->id)
                    ->where('game_id', $game->id)
                    ->count();

                if (!empty($data['score']) && $data['score'] > $correctCount) {
                    $correctCount = (int) $data['score'];
                }

                $basePoints = (int) round($correctCount * (int) $game->xp_reward / max(1, (int) $game->cards_count));
                $points = PointsHelper::awardFixed(
                    $student->id,
                    ActivityType::Game->value,
                    $game->id,
                    $basePoints,
                    'Completed game: ' . $game->title,
                    $correctCount,
                    $game->cards_count,
                );
                $awarded = true;
                $xp = $points;
            } else {
                $xp = $existing->points;
            }
        }

        if ($completed) {
            $progress->update(['completed' => true]);
        }

        $progress->earned_xp = $xp;
        $progress->correct_card_ids = DB::table('game_correct_answers')
            ->where('user_id', $student->id)
            ->where('game_id', $game->id)
            ->pluck('card_id')
            ->all();

        return response()->json(['ok' => true, 'progress' => $progress, 'awarded' => $awarded, 'xp' => $xp]);
    }

    public function correctAnswer(Request $request, Game $game): JsonResponse
    {
        if ($game->is_live) {
            return response()->json([
                'ok' => false,
                'message' => 'Live Arena games can only be played in a live match session hosted by your teacher.',
            ], 403);
        }

        $student = Auth::user();

        $existingProgress = GameProgress::where('user_id', $student->id)
            ->where('game_id', $game->id)
            ->first();

        if ($existingProgress && $existingProgress->completed) {
            return response()->json(['ok' => false, 'message' => 'This game has already been completed and cannot be retaken.'], 403);
        }

        $data = $request->validate([
            'card_id' => ['required', 'integer', 'min:1'],
        ]);

        $card = $game->cards()->where('id', $data['card_id'])->first();
        if (!$card) {
            return response()->json(['ok' => false, 'message' => 'Card not found.'], 422);
        }

        $already = \Illuminate\Support\Facades\DB::table('game_correct_answers')
            ->where('user_id', $student->id)
            ->where('game_id', $game->id)
            ->where('card_id', $card->id)
            ->exists();

        $awarded = false;
        $pendingXp = (int) config('gamification.correct_answer_xp');
        if (!$already) {
            \Illuminate\Support\Facades\DB::table('game_correct_answers')->insert([
                'user_id' => $student->id,
                'game_id' => $game->id,
                'card_id' => $card->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $correctCount = \Illuminate\Support\Facades\DB::table('game_correct_answers')
                ->where('user_id', $student->id)
                ->where('game_id', $game->id)
                ->count();

            GameProgress::where('user_id', $student->id)
                ->where('game_id', $game->id)
                ->where('score', '<', $correctCount)
                ->update(['score' => $correctCount]);
        }

        return response()->json(['ok' => true, 'awarded' => $awarded, 'pending' => $pendingXp]);
    }

    public function useHint(Request $request, Game $game): JsonResponse
    {
        if ($game->is_live) {
            return response()->json([
                'ok' => false,
                'message' => 'Hints cannot be used in Live Arena games.',
            ], 403);
        }

        $student = Auth::user();
        $hintCost = max(1, (int) Setting::get('hint_xp_cost', 2));

        $progress = GameProgress::firstOrCreate(
            ['user_id' => $student->id, 'game_id' => $game->id],
            ['current_card' => 0, 'done_count' => 0, 'score' => 0, 'hints_used' => 0]
        );

        $progress->increment('hints_used');

        $newTotal = PointsHelper::deduct(
            $student->id,
            $hintCost,
            ActivityType::Deduction->value,
            $game->id,
            "Used hint in game: {$game->title} (-{$hintCost} XP)"
        );

        return response()->json([
            'ok' => true,
            'cost' => $hintCost,
            'hints_used' => (int) $progress->hints_used,
            'total_points' => $newTotal,
            'message' => "Hint used: -{$hintCost} XP deducted.",
        ]);
    }

    public function index(): Response
    {
        $teacher = Auth::user();
        $query = Game::with(['cards', 'teacher:id,name', 'subjectRecord:id,name', 'quarter:id,name,code', 'schoolYear:id,name'])
            ->withCount([
                'progresses as players_count' => fn ($q) => $q->where('completed', true),
                'reopenRequests as pending_reopen_requests_count' => fn ($q) => $q->where('status', 'pending'),
                'rooms as live_rooms_count',
            ])
            ->latest();
        SchoolYear::applyFilter($query);

        if (! $teacher->isSuperadmin()) {
            $query->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                    ->orWhereHas('teacher', fn ($t) => $t->where('role', 'superadmin'));
            });
        }

        $games = $query->get()->map(function ($g) {
            if ($g->is_live || ($g->live_rooms_count ?? 0) > 0) {
                $liveParticipantsCount = DB::table('game_room_participants')
                    ->join('game_rooms', 'game_rooms.id', '=', 'game_room_participants.game_room_id')
                    ->where('game_rooms.game_id', $g->id)
                    ->distinct('game_room_participants.user_id')
                    ->count('game_room_participants.user_id');
                $g->players_count = max((int) $g->players_count, $liveParticipantsCount);
            }
            return $g;
        });

        $activeRoomsQuery = GameRoom::with(['game:id,title,type,subject,grade', 'teacher:id,name'])
            ->withCount('participants')
            ->whereIn('status', ['waiting', 'active'])
            ->latest();

        if (! $teacher->isSuperadmin()) {
            $activeRoomsQuery->where('teacher_id', $teacher->id);
        }

        $activeRooms = $activeRoomsQuery->get(['id', 'game_id', 'teacher_id', 'pin', 'status', 'current_card_index', 'created_at']);

        $finishedRoomsQuery = GameRoom::with([
                'game:id,title,type,subject,grade',
                'teacher:id,name',
                'participants.user:id,name,grade,email',
            ])
            ->withCount('participants')
            ->where('status', 'finished')
            ->latest();

        if (! $teacher->isSuperadmin()) {
            $finishedRoomsQuery->where('teacher_id', $teacher->id);
        }

        $recentFinishedRooms = $finishedRoomsQuery->take(10)->get()->map(function ($room) {
            $sorted = $room->participants
                ->sortByDesc('score')
                ->sortByDesc('streak')
                ->values();

            $top10 = $sorted->slice(0, 10)->map(function ($p, $idx) {
                return [
                    'rank' => $idx + 1,
                    'user_id' => $p->user_id,
                    'name' => $p->user->name ?? 'Student',
                    'grade' => $p->user->grade ?? '—',
                    'email' => $p->user->email,
                    'score' => (int) $p->score,
                    'streak' => (int) $p->streak,
                ];
            })->values();

            return [
                'id' => $room->id,
                'game_id' => $room->game_id,
                'pin' => $room->pin,
                'status' => $room->status,
                'title' => $room->game?->title ?? 'Live Match',
                'subject' => $room->game?->subject ?? 'Arcade',
                'grade' => $room->game?->grade ?? 'All',
                'teacher_name' => $room->teacher?->name,
                'total_participants' => $room->participants_count ?? $room->participants->count(),
                'winner' => $top10->first(),
                'leaderboard' => $top10,
                'created_at' => $room->created_at?->format('M d, Y h:i A'),
                'ended_at' => $room->updated_at?->format('M d, Y h:i A'),
            ];
        });

        return Inertia::render('Teacher/Games', [
            'games' => $games,
            'activeRooms' => $activeRooms,
            'recentFinishedRooms' => $recentFinishedRooms,
            'isSuperadmin' => $teacher->isSuperadmin(),
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'subjects' => $this->availableSubjects($teacher),
            'gradeLevels' => GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']),
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
        ]);
    }

    public function players(Game $game): JsonResponse
    {
        $this->authorizeOwner($game);

        $cardsCount = $game->cards()->count();

        $progresses = GameProgress::with(['user:id,name,grade,email,grade_level_id,section_id'])
            ->where('game_id', $game->id)
            ->latest('updated_at')
            ->get();

        $progressUserIds = $progresses->pluck('user_id');
        $pendingUserIds = GameReopenRequest::where('game_id', $game->id)
            ->where('status', 'pending')
            ->pluck('user_id');
        $allUserIds = $progressUserIds->merge($pendingUserIds)->unique();

        $points = StudentPoint::whereIn('student_id', $allUserIds)
            ->where('activity_type', ActivityType::Game->value)
            ->where('activity_id', $game->id)
            ->pluck('points', 'student_id');

        $allCorrectAnswers = DB::table('game_correct_answers')
            ->where('game_id', $game->id)
            ->whereIn('user_id', $progressUserIds)
            ->get(['user_id', 'card_id'])
            ->groupBy('user_id')
            ->map(fn ($group) => $group->pluck('card_id')->all());

        $players = $progresses->map(function ($p) use ($cardsCount, $points, $allCorrectAnswers) {
            $userCorrectIds = $allCorrectAnswers[$p->user_id] ?? [];
            $score = max((int) $p->score, count($userCorrectIds));
            $pct = $cardsCount > 0 ? round(($score / $cardsCount) * 100) : 0;
            return [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'student_name' => $p->user?->name ?? 'Unknown Student',
                'student_grade' => $p->user?->grade ?? '—',
                'student_email' => $p->user?->email,
                'score' => $score,
                'total' => $cardsCount,
                'percentage' => $pct,
                'done_count' => max((int) $p->done_count, $score),
                'completed' => (bool) $p->completed,
                'card_order' => $p->card_order,
                'graded' => $p->graded ? json_decode($p->graded, true) : null,
                'earned_xp' => $points[$p->user_id] ?? 0,
                'correct_card_ids' => $userCorrectIds,
                'completed_at' => $p->updated_at?->format('M d, Y h:i A'),
                'raw_date' => $p->updated_at?->toIso8601String(),
            ];
        });

        $pendingRequests = GameReopenRequest::where('game_id', $game->id)
            ->where('status', 'pending')
            ->with('student:id,name,email,grade')
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'student_name' => $r->student?->name ?? 'Student',
                'student_grade' => $r->student?->grade ?? '—',
                'student_email' => $r->student?->email,
                'reason' => $r->reason,
                'earned_xp' => (int) ($points[$r->user_id] ?? 0),
                'created_at' => $r->created_at?->format('M d, Y h:i A'),
            ]);

        $liveRooms = GameRoom::where('game_id', $game->id)
            ->with(['participants.user:id,name,grade,email'])
            ->latest()
            ->get()
            ->map(function ($room) {
                $sorted = $room->participants
                    ->sortByDesc('score')
                    ->sortByDesc('streak')
                    ->values();

                $top10 = $sorted->slice(0, 10)->map(function ($p, $idx) {
                    return [
                        'rank' => $idx + 1,
                        'user_id' => $p->user_id,
                        'name' => $p->user->name ?? 'Student',
                        'grade' => $p->user->grade ?? '—',
                        'email' => $p->user->email,
                        'score' => $p->score,
                        'streak' => $p->streak,
                    ];
                })->values();

                return [
                    'id' => $room->id,
                    'pin' => $room->pin,
                    'status' => $room->status,
                    'total_participants' => $room->participants->count(),
                    'created_at' => $room->created_at?->format('M d, Y h:i A'),
                    'leaderboard' => $top10,
                ];
            });

        return response()->json([
            'game' => [
                'id' => $game->id,
                'title' => $game->title,
                'subject' => $game->subject,
                'type' => $game->type,
                'is_live' => (bool) $game->is_live,
                'xp_reward' => $game->xp_reward,
                'cards_count' => $cardsCount,
                'cards' => $game->cards()->get(['id', 'question', 'answer', 'order']),
            ],
            'players' => $players,
            'pending_requests' => $pendingRequests,
            'live_rooms' => $liveRooms,
        ]);
    }

    public function performReopen(Game $game, User $student, ?int $teacherId = null): int
    {
        $deductedXp = 0;

        DB::transaction(function () use ($game, $student, $teacherId, &$deductedXp) {
            // 1. Delete GameProgress record
            GameProgress::where('user_id', $student->id)
                ->where('game_id', $game->id)
                ->delete();

            // 2. Delete correct answers tracking
            DB::table('game_correct_answers')
                ->where('user_id', $student->id)
                ->where('game_id', $game->id)
                ->delete();

            // 3. Remove StudentPoint and update student total points so student can re-earn XP
            $pointsQuery = StudentPoint::where('student_id', $student->id)
                ->where('activity_type', ActivityType::Game->value)
                ->where('activity_id', $game->id);

            $deductedXp = (int) $pointsQuery->sum('points');
            $pointsQuery->delete();

            $studentCurrent = (int) DB::table('users')->where('id', $student->id)->value('total_points');
            $sumFromPoints = (int) StudentPoint::where('student_id', $student->id)->sum('points');
            $newTotal = max(0, $studentCurrent - $deductedXp);
            if ($studentCurrent === ($sumFromPoints + $deductedXp)) {
                $newTotal = $sumFromPoints;
            }

            DB::table('users')->where('id', $student->id)->update(['total_points' => $newTotal]);
            $student->update(['total_points' => $newTotal]);

            // 4. Also approve any pending reopen requests for this student and game
            GameReopenRequest::where('game_id', $game->id)
                ->where('user_id', $student->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'teacher_id' => $teacherId ?? $game->teacher_id,
                    'reviewed_at' => now(),
                ]);
        });

        return $deductedXp;
    }

    public function reopenForStudent(Request $request, Game $game, User $student): RedirectResponse|JsonResponse
    {
        $this->authorizeOwner($game);

        $deductedXp = $this->performReopen($game, $student, Auth::id());
        $newTotal = (int) DB::table('users')->where('id', $student->id)->value('total_points');

        $message = $deductedXp > 0
            ? "Game '{$game->title}' reopened for {$student->name}. {$deductedXp} XP deducted. Student can now replay."
            : "Game '{$game->title}' reopened for {$student->name}. Student can now replay.";

        if ($request->wantsJson() || $request->header('X-Inertia') === null) {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'deducted_xp' => $deductedXp,
                'new_total_points' => $newTotal,
            ]);
        }

        return back()->with('success', $message);
    }

    public function toggleAutoReopen(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $this->authorizeOwner($game);

        $game->update([
            'auto_reopen' => ! $game->auto_reopen,
        ]);

        $status = $game->auto_reopen ? 'enabled' : 'disabled';
        $message = "Auto-approve for reopen requests has been {$status} for '{$game->title}'.";

        if ($request->wantsJson() || $request->header('X-Inertia') === null) {
            return response()->json([
                'ok' => true,
                'auto_reopen' => (bool) $game->auto_reopen,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Student: Request teacher to reopen a completed game.
     */
    public function requestReopen(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user->isStudent(), 403);

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $aiSupervisorActive = AiConfigService::isManagerEnabled()
            && AiConfigService::isManagerGameReopenEnabled()
            && AiConfigService::getManagerMode() !== 'advisory';

        // Auto-approval requires the teacher's game-level auto_reopen setting to be enabled
        $shouldAutoApprove = (bool) $game->auto_reopen;

        if ($shouldAutoApprove) {
            $deductedXp = $this->performReopen($game, $user, $game->teacher_id);

            $approvedBy = $aiSupervisorActive ? 'ai_supervisor' : 'auto_toggle';

            $reopenRequest = GameReopenRequest::create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'teacher_id' => $game->teacher_id,
                'reason' => $request->input('reason') ?? ($aiSupervisorActive ? 'Approved by AI Supervisor' : 'Auto-approved'),
                'status' => 'approved',
                'reviewed_at' => now(),
            ]);

            if ($aiSupervisorActive) {
                AiManagerLog::create([
                    'event_type' => 'game_reopen',
                    'status' => 'success',
                    'target_type' => GameReopenRequest::class,
                    'target_id' => $reopenRequest->id,
                    'confidence' => 0.98,
                    'summary' => "AI Supervisor approved game reopen for {$user->name} on '{$game->title}' ({$deductedXp} XP deducted for fresh attempt).",
                    'payload' => [
                        'game_id' => $game->id,
                        'student_id' => $user->id,
                        'deducted_xp' => $deductedXp,
                        'approved_by' => 'ai_supervisor',
                    ],
                ]);
            }

            $message = $deductedXp > 0
                ? "Game '{$game->title}' automatically reopened! {$deductedXp} XP deducted. You can replay now!"
                : "Game '{$game->title}' automatically reopened! You can replay now!";

            if ($request->wantsJson() || $request->header('X-Inertia') === null) {
                return response()->json([
                    'ok' => true,
                    'auto_approved' => true,
                    'approved_by' => $approvedBy,
                    'message' => $message,
                    'request' => $reopenRequest,
                    'deducted_xp' => $deductedXp,
                ]);
            }

            return back()->with('success', $message);
        }

        $existingPending = GameReopenRequest::where('game_id', $game->id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'ok' => false,
                'message' => 'You already have a pending reopen request for this game.',
            ], 422);
        }

        $reopenRequest = GameReopenRequest::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'reason' => $request->input('reason'),
            'status' => 'pending',
        ]);

        $message = "Reopen request submitted to your teacher for '{$game->title}'.";

        if ($request->wantsJson() || $request->header('X-Inertia') === null) {
            return response()->json([
                'ok' => true,
                'auto_approved' => false,
                'message' => $message,
                'request' => $reopenRequest,
            ]);
        }

        return back()->with('success', $message);
    }

    /**
     * Teacher: Reject a student's reopen request.
     */
    public function rejectReopenRequest(Request $request, Game $game, GameReopenRequest $reopenRequest): JsonResponse|RedirectResponse
    {
        $this->authorizeOwner($game);
        abort_unless($reopenRequest->game_id === $game->id, 404);

        $reopenRequest->update([
            'status' => 'rejected',
            'teacher_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $message = "Reopen request rejected.";

        if ($request->wantsJson() || $request->header('X-Inertia') === null) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $request->filled('grade')) {
            if ($request->filled('grade_level_id')) {
                $request->merge(['grade' => GradeLevel::where('id', $request->input('grade_level_id'))->value('name')]);
            } elseif ($request->filled('grade_level_ids')) {
                $ids = is_array($request->input('grade_level_ids')) ? $request->input('grade_level_ids') : explode(',', (string) $request->input('grade_level_ids'));
                $request->merge(['grade' => GradeLevel::whereIn('id', array_filter($ids))->orderBy('display_order')->value('name')]);
            }
        }

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
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'description' => ['nullable', 'string'],
            'xp_reward' => ['required', 'integer', 'min:1', 'max:100'],
            'type' => ['required', 'in:' . \App\Enums\GameType::validationList()],
            'hidden' => ['nullable', 'boolean'],
            'auto_reopen' => ['nullable', 'boolean'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'cards' => ['required', 'array', 'min:1'],
            'cards.*.question' => ['nullable', 'string'],
            'cards.*.answer' => ['required', 'string'],
            'cards.*.color' => ['nullable', 'string', 'max:20'],
            'cards.*.options' => ['nullable', 'array'],
        ]);

        $teacherId = Auth::user()->isSuperadmin() && !empty($data['teacher_id']) ? (int) $data['teacher_id'] : Auth::id();
        $subject = $this->resolveSubject($data['subject_id'] ?? null, $data['subject'] ?? null, $teacherId);

        $game = Game::create([
            'teacher_id' => $teacherId,
            'title' => $data['title'],
            'subject' => $subject['name'],
            'subject_id' => $subject['id'],
            'grade' => $data['grade'],
            'quarter_id' => $data['quarter_id'] ?? null,
            'description' => $data['description'] ?? '',
            'xp_reward' => $data['xp_reward'],
            'type' => $data['type'],
            'hidden' => (bool) ($data['hidden'] ?? false),
            'auto_reopen' => (bool) ($data['auto_reopen'] ?? false),
        ]);

        foreach ($data['cards'] as $i => $card) {
            $defaultQuestion = match ($data['type']) {
                'fourpics' => '4 Pics 1 Word',
                'wordsearch' => 'Word Search',
                'crossword' => 'Crossword Clue',
                'codepuzzle' => 'Code Step',
                default => 'Question',
            };
            $question = !empty(trim($card['question'] ?? ''))
                ? $card['question']
                : $defaultQuestion;

            $game->cards()->create([
                'question' => $question,
                'answer' => $card['answer'],
                'color' => $card['color'] ?? null,
                'order' => $i,
                'options' => $card['options'] ?? null,
            ]);
        }

        return back()->with('success', 'Game created.');
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $this->authorizeOwner($game);

        if (! $request->filled('grade')) {
            if ($request->filled('grade_level_id')) {
                $request->merge(['grade' => GradeLevel::where('id', $request->input('grade_level_id'))->value('name')]);
            } elseif ($request->filled('grade_level_ids')) {
                $ids = is_array($request->input('grade_level_ids')) ? $request->input('grade_level_ids') : explode(',', (string) $request->input('grade_level_ids'));
                $request->merge(['grade' => GradeLevel::whereIn('id', array_filter($ids))->orderBy('display_order')->value('name')]);
            }
        }

        if (! $request->filled('subject_id') && $request->filled('subject')) {
            $existing = Subject::whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $request->input('subject')))])->first();
            if ($existing) {
                $request->merge(['subject_id' => $existing->id]);
            }
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'grade' => ['nullable', 'string', 'max:50'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'description' => ['nullable', 'string'],
            'xp_reward' => ['required', 'integer', 'min:1', 'max:100'],
            'type' => ['required', 'in:' . \App\Enums\GameType::validationList()],
            'auto_reopen' => ['nullable', 'boolean'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'cards' => ['required', 'array', 'min:1'],
            'cards.*.question' => ['nullable', 'string'],
            'cards.*.answer' => ['required', 'string'],
            'cards.*.color' => ['nullable', 'string', 'max:20'],
            'cards.*.options' => ['nullable', 'array'],
        ]);

        $teacherId = Auth::user()->isSuperadmin() && array_key_exists('teacher_id', $data) && $data['teacher_id'] ? (int) $data['teacher_id'] : $game->teacher_id;
        $subject = $this->resolveSubject($data['subject_id'] ?? null, $data['subject'] ?? null, $teacherId);

        $updateData = [
            'title' => $data['title'],
            'subject' => $subject['name'],
            'subject_id' => $subject['id'],
            'grade' => $data['grade'],
            'quarter_id' => array_key_exists('quarter_id', $data) ? $data['quarter_id'] : $game->quarter_id,
            'description' => $data['description'] ?? '',
            'xp_reward' => $data['xp_reward'],
            'type' => $data['type'],
        ];

        if (array_key_exists('auto_reopen', $data)) {
            $updateData['auto_reopen'] = (bool) $data['auto_reopen'];
        }

        if (Auth::user()->isSuperadmin() && array_key_exists('teacher_id', $data)) {
            $updateData['teacher_id'] = $data['teacher_id'] ?: Auth::id();
        }

        $game->update($updateData);

        $game->cards()->delete();
        foreach ($data['cards'] as $i => $card) {
            $defaultQuestion = match ($data['type']) {
                'fourpics' => '4 Pics 1 Word',
                'wordsearch' => 'Word Search',
                'crossword' => 'Crossword Clue',
                'codepuzzle' => 'Code Step',
                default => 'Question',
            };
            $question = !empty(trim($card['question'] ?? ''))
                ? $card['question']
                : $defaultQuestion;

            $game->cards()->create([
                'question' => $question,
                'answer' => $card['answer'],
                'color' => $card['color'] ?? null,
                'order' => $i,
                'options' => $card['options'] ?? null,
            ]);
        }

        return back()->with('success', 'Game updated.');
    }

    public function toggleHidden(Game $game): RedirectResponse
    {
        $this->authorizeOwner($game);
        $game->update(['hidden' => !$game->hidden]);
        return back();
    }

    public function bulkAutoReopen(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:games,id'],
            'auto_reopen' => ['required', 'boolean'],
        ]);

        $query = Game::whereIn('id', $data['ids']);
        if (! Auth::user()->isSuperadmin()) {
            $query->where('teacher_id', Auth::id());
        }

        $updated = $query->update([
            'auto_reopen' => (bool) $data['auto_reopen'],
        ]);
        $status = $data['auto_reopen'] ? 'enabled' : 'disabled';
        $message = "Auto-approve for reopen requests has been {$status} for {$updated} game(s).";

        if ($request->wantsJson() || $request->header('X-Inertia') === null) {
            return response()->json([
                'ok' => true,
                'auto_reopen' => (bool) $data['auto_reopen'],
                'updated' => $updated,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function bulkVisibility(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:games,id'],
            'hidden' => ['required', 'boolean'],
        ]);

        $count = 0;
        foreach (Game::whereIn('id', $data['ids'])->get() as $game) {
            if (!Auth::user()->isSuperadmin() && $game->teacher_id !== Auth::id()) {
                continue;
            }
            $game->update(['hidden' => $data['hidden']]);
            $count++;
        }

        return back()->with('success', $data['hidden'] ? "Hid {$count} game(s)." : "Unhid {$count} game(s).");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:games,id'],
        ]);

        $count = 0;
        foreach (Game::whereIn('id', $data['ids'])->get() as $game) {
            if (!Auth::user()->isSuperadmin() && $game->teacher_id !== Auth::id()) {
                continue;
            }
            $game->delete();
            $count++;
        }

        return redirect()->route('teacher.games.index')->with('success', "Deleted {$count} game(s).");
    }

    public function destroy(Game $game): RedirectResponse
    {
        $this->authorizeOwner($game);
        $game->delete();
        return redirect()->route('teacher.games.index')->with('success', 'Game deleted.');
    }

    public function reassign(Request $request, Game $game): RedirectResponse
    {
        abort_unless(Auth::user()->isSuperadmin(), 403);

        $data = $request->validate(['teacher_id' => ['required', 'exists:users,id']]);
        $game->update(['teacher_id' => $data['teacher_id']]);

        return back()->with('success', 'Game reassigned to teacher.');
    }

    private function availableSubjects(User $user)
    {
        return Subject::query()
            ->where('is_active', true)
            ->when(! $user->isSuperadmin(), fn ($q) => $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id)))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resolveSubject(?int $subjectId, ?string $legacyName, int $teacherId): array
    {
        if ($subjectId) {
            $subject = Subject::whereKey($subjectId)->where('is_active', true)->firstOrFail();
            abort_unless(($teacherId === (int) Auth::id() && Auth::user()->isSuperadmin()) || $subject->teachers()->whereKey($teacherId)->exists(), 422, 'The selected subject is not assigned to this teacher.');
            return ['id' => $subject->id, 'name' => $subject->name];
        }

        $name = trim((string) $legacyName);
        abort_if($name === '', 422, 'Please select a subject.');
        $subject = Subject::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($subject) {
            if (! $subject->teachers()->whereKey($teacherId)->exists() && ! Auth::user()->isSuperadmin()) {
                $subject->teachers()->syncWithoutDetaching([$teacherId]);
            }
            return ['id' => $subject->id, 'name' => $subject->name];
        }

        $subject = Subject::create(['name' => $name, 'is_active' => true]);
        $subject->teachers()->syncWithoutDetaching([$teacherId]);
        return ['id' => $subject->id, 'name' => $subject->name];
    }

    private function authorizeOwner(Game $game): void
    {
        if (Auth::user()->isSuperadmin()) return;
        abort_if($game->teacher_id !== Auth::id(), 403);
    }
}
