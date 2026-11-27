<?php

namespace App\Helpers;

use App\Models\Practical;
use App\Models\Setting;
use App\Models\StudentPoint;
use App\Models\User;
use App\Services\StreakService;

class PointsHelper
{
    public static function calculate(float $percentage, ?int $maxReward = null): int
    {
        $masteryPct = (float) Setting::get('xp_mastery_percentage', 80);
        $passingPct = (float) Setting::get('xp_passing_percentage', 60);
        $masteryAward = $maxReward !== null
            ? max(1, $maxReward)
            : (int) Setting::get('xp_award_mastery', 10);
        $passingAward = $maxReward !== null
            ? max(1, (int) round($masteryAward * 0.5))
            : (int) Setting::get('xp_award_pass', 5);
        $attemptAward = $maxReward !== null
            ? max(1, (int) round($masteryAward * 0.1))
            : (int) Setting::get('xp_award_attempt', 1);

        if ($percentage >= $masteryPct) {
            return $masteryAward;
        }

        if ($percentage >= $passingPct) {
            return $passingAward;
        }

        if ($percentage >= 1) {
            return $attemptAward;
        }

        return 0;
    }

    /**
     * Award score-based XP for an activity, keeping only the student's best score.
     */
    public static function award(
        int $studentId,
        string $activityType,
        int $activityId,
        int $score,
        int $total,
        ?string $reason = null,
        bool $force = false,
        ?int $maxReward = null,
    ): int {
        if ($maxReward === null && $activityType === 'Practical') {
            $maxReward = Practical::whereKey($activityId)->value('xp_reward');
        }

        $existing = StudentPoint::where('student_id', $studentId)
            ->where('activity_type', $activityType)
            ->where('activity_id', $activityId)
            ->first();

        if (! $force && $existing && $existing->score !== null && $score <= $existing->score) {
            return (int) $existing->points;
        }

        $percentage = $total > 0 ? ($score / $total) * 100 : 0;
        $points = static::applyBonuses($studentId, static::calculate($percentage, $maxReward), $activityType, $activityId, $reason, $percentage, $score, $total);

        return static::persist(
            $studentId,
            $activityType,
            $activityId,
            $points,
            $score,
            $total,
            $reason ?? "{$activityType}: {$score}/{$total} (" . round($percentage) . "%)",
            $force,
        );
    }

    /**
     * Award a fixed XP amount while applying the student's active streak multiplier.
     * The activity ID must identify one awardable event (for example, a date or room).
     */
    public static function awardFixed(
        int $studentId,
        string $activityType,
        int $activityId,
        int $basePoints,
        ?string $reason = null,
        ?int $score = null,
        ?int $total = null,
        bool $force = false,
    ): int {
        $existing = StudentPoint::where('student_id', $studentId)
            ->where('activity_type', $activityType)
            ->where('activity_id', $activityId)
            ->first();

        if (! $force && $existing) {
            return (int) $existing->points;
        }

        $points = static::applyBonuses(
            $studentId,
            max(0, $basePoints),
            $activityType,
            $activityId,
            $reason,
            null,
            $score,
            $total,
        );

        return static::persist(
            $studentId,
            $activityType,
            $activityId,
            $points,
            $score ?? $basePoints,
            $total ?? $basePoints,
            $reason,
            $force,
        );
    }

    public static function deduct(
        int $studentId,
        int $amount,
        string $activityType = 'Deduction',
        int $activityId = 0,
        ?string $reason = null,
    ): int {
        $amount = abs($amount);
        if ($amount === 0) {
            return static::syncTotal($studentId);
        }

        StudentPoint::create([
            'student_id' => $studentId,
            'activity_type' => $activityType,
            'activity_id' => $activityId,
            'points' => -$amount,
            'score' => 0,
            'total' => $amount,
            'reason' => $reason ?? "XP deduction: -{$amount} XP",
        ]);

        return static::syncTotal($studentId);
    }

    public static function syncTotal(int $studentId): int
    {
        $total = (int) StudentPoint::where('student_id', $studentId)->sum('points');
        $clamped = max(0, $total);
        User::whereKey($studentId)->update(['total_points' => $clamped]);

        return $clamped;
    }

    private static function applyBonuses(
        int $studentId,
        int $basePoints,
        string $activityType,
        int $activityId,
        ?string &$reason,
        ?float $percentage,
        ?int $score,
        ?int $total,
    ): int {
        $points = $basePoints;

        if ($points > 0) {
            $multiplier = StreakService::multiplierForReward($studentId);
            $points = (int) round($points * $multiplier);

            if ($multiplier > 1) {
                $reason = ($reason ?? "{$activityType}: {$score}/{$total}") . " [{$multiplier}x Streak XP]";
            }
        }

        $user = User::find($studentId);
        $boostable = in_array($activityType, \App\Services\RewardService::BOOSTABLE_ACTIVITIES, true);

        if ($user && $boostable && $points > 0) {
            $booster = \App\Services\RewardService::consumeBooster($user, $activityType, $activityId);
            if ($booster) {
                [$boostMultiplier, $boosterName] = $booster;
                $points = (int) round($points * $boostMultiplier);
                $label = rtrim(rtrim(number_format($boostMultiplier, 2, '.', ''), '0'), '.');
                $reason = ($reason ?? "{$activityType}: {$score}/{$total}") . " [{$label}× {$boosterName}]";
            }

            $badgePct = \App\Services\RewardService::badgeBonusPct($user->equipped_badge);
            if ($badgePct > 0) {
                $bonus = max(1, (int) round($points * $badgePct / 100));
                $points += $bonus;
                $reason = ($reason ?? "{$activityType}: {$score}/{$total}") . " [+{$badgePct}% Badge]";
            }
        }

        return $points;
    }

    private static function persist(
        int $studentId,
        string $activityType,
        int $activityId,
        int $points,
        ?int $score,
        ?int $total,
        ?string $reason,
        bool $force,
    ): int {
        StudentPoint::updateOrCreate(
            [
                'student_id' => $studentId,
                'activity_type' => $activityType,
                'activity_id' => $activityId,
            ],
            [
                'points' => $points,
                'score' => $score,
                'total' => $total,
                'reason' => $reason,
            ]
        );

        static::syncTotal($studentId);

        return $points;
    }
}
