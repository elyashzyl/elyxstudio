<?php

namespace App\Console\Commands;

use App\Enums\ActivityType;
use App\Models\StudentPoint;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveFarmedGameXp extends Command
{
    protected $signature = 'games:remove-farmed-xp {--dry-run : Show what would be removed without deleting}';

    protected $description = 'Remove per-card Game XP so that game XP only reflects at completion';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $cardToGame = DB::table('game_cards')->pluck('game_id', 'id');

        $points = StudentPoint::where('activity_type', ActivityType::Game->value)
            ->get(['id', 'student_id', 'activity_id', 'points']);

        $idsToDelete = [];
        $deductions = [];

        foreach ($points as $point) {
            if ($cardToGame->has($point->activity_id)) {
                $idsToDelete[] = $point->id;
                $deductions[$point->student_id] = ($deductions[$point->student_id] ?? 0) + (int) $point->points;
            }
        }

        if (empty($idsToDelete)) {
            $this->info('No per-card Game XP found.');

            return Command::SUCCESS;
        }

        $total = array_sum($deductions);

        $this->info('Found ' . count($idsToDelete) . ' per-card game point row(s) worth ' . $total . ' XP across ' . count($deductions) . ' user(s).');

        foreach ($deductions as $userId => $sum) {
            $user = User::find($userId);
            $current = $user ? (int) $user->total_points : 0;
            $after = max(0, $current - $sum);
            $this->line("  user#{$userId}  -{$sum} XP  (total {$current} -> {$after})");
        }

        if ($dryRun) {
            $this->line('Dry run – nothing was changed.');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($idsToDelete, $deductions) {
            StudentPoint::whereIn('id', $idsToDelete)->delete();

            foreach ($deductions as $userId => $sum) {
                $user = User::find($userId);

                if (! $user) {
                    continue;
                }

                $user->total_points = max(0, (int) $user->total_points - $sum);
                $user->save();
            }
        });

        $this->info('Removed and totals adjusted.');

        return Command::SUCCESS;
    }
}