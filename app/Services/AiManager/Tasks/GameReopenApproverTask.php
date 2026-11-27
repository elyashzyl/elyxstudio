<?php

namespace App\Services\AiManager\Tasks;

use App\Enums\ActivityType;
use App\Models\AiManagerLog;
use App\Models\Game;
use App\Models\GameProgress;
use App\Models\GameReopenRequest;
use App\Models\StudentPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GameReopenApproverTask
{
    /**
     * Inspect and process pending student game reopen requests.
     *
     * @return array{processed: int, approved: int, flagged: int, skipped: int}
     */
    public function execute(string $mode, float $confidenceThreshold): array
    {
        $stats = [
            'processed' => 0,
            'approved' => 0,
            'flagged' => 0,
            'skipped' => 0,
        ];

        $pending = GameReopenRequest::with(['game', 'student'])
            ->where('status', 'pending')
            ->latest()
            ->limit(25)
            ->get();

        foreach ($pending as $req) {
            $stats['processed']++;
            $game = $req->game;
            $student = $req->student;

            if (! $game || ! $student) {
                $stats['skipped']++;
                continue;
            }

            // Respect teacher's auto_reopen setting: if disabled on the game, do not auto-approve!
            if (! $game->auto_reopen) {
                $stats['skipped']++;
                AiManagerLog::create([
                    'event_type' => 'game_reopen',
                    'status' => 'flagged_for_review',
                    'target_type' => GameReopenRequest::class,
                    'target_id' => $req->id,
                    'confidence' => 0.90,
                    'summary' => "AI Supervisor: Retake request for {$student->name} on '{$game->title}' left for teacher approval (Auto-Reopen is turned OFF by teacher).",
                    'payload' => [
                        'game_id' => $game->id,
                        'student_id' => $student->id,
                        'reason' => $req->reason,
                        'auto_reopen' => false,
                    ],
                ]);
                continue;
            }

            if ($mode === 'advisory') {
                $stats['flagged']++;
                AiManagerLog::create([
                    'event_type' => 'game_reopen',
                    'status' => 'flagged_for_review',
                    'target_type' => GameReopenRequest::class,
                    'target_id' => $req->id,
                    'confidence' => 0.85,
                    'summary' => "AI Supervisor (Advisory): Student {$student->name} requested retake for '{$game->title}'. Pending teacher review.",
                    'payload' => [
                        'game_id' => $game->id,
                        'student_id' => $student->id,
                        'reason' => $req->reason,
                    ],
                ]);
                continue;
            }

            // In 'approval' and 'autonomous' modes, execute the reopen!
            try {
                $deductedXp = $this->performReopen($game, $student);

                $req->update([
                    'status' => 'approved',
                    'teacher_id' => $game->teacher_id,
                    'reviewed_at' => now(),
                ]);

                AiManagerLog::create([
                    'event_type' => 'game_reopen',
                    'status' => 'success',
                    'target_type' => GameReopenRequest::class,
                    'target_id' => $req->id,
                    'confidence' => 0.98,
                    'summary' => "AI Supervisor automatically approved reopen request for {$student->name} on '{$game->title}' ({$deductedXp} XP deducted for fresh attempt).",
                    'payload' => [
                        'game_id' => $game->id,
                        'game_title' => $game->title,
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'deducted_xp' => $deductedXp,
                        'reason' => $req->reason,
                        'approved_by' => 'ai_supervisor',
                    ],
                ]);

                $stats['approved']++;
            } catch (\Throwable $e) {
                Log::error("AI Supervisor failed to approve game reopen {$req->id}: {$e->getMessage()}");
                $stats['skipped']++;
            }
        }

        return $stats;
    }

    public function performReopen(Game $game, User $student): int
    {
        $deductedXp = 0;

        DB::transaction(function () use ($game, $student, &$deductedXp) {
            GameProgress::where('user_id', $student->id)
                ->where('game_id', $game->id)
                ->delete();

            DB::table('game_correct_answers')
                ->where('user_id', $student->id)
                ->where('game_id', $game->id)
                ->delete();

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
        });

        return $deductedXp;
    }
}
