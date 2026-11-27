<?php

namespace App\Services;

use App\Models\StudentPoint;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class StreakService
{
    /**
     * Return the active streak and XP multiplier for a student.
     *
     * When an award is about to be recorded, includeAsOfDate should be true
     * so that the activity being awarded counts as today's learning activity.
     */
    public static function info(User|int $student, ?CarbonInterface $asOf = null, bool $includeAsOfDate = false): array
    {
        $user = $student instanceof User ? $student : User::find($student);
        $date = ($asOf ?? now())->copy()->startOfDay();
        $dates = static::activityDates($user?->id, $date, $includeAsOfDate);
        $streak = static::countStreak($dates, $date, (bool) ($user?->streak_freeze_active ?? false));
        $multiplier = static::multiplier($streak);

        return [
            'streak' => $streak,
            'multiplier' => $multiplier,
            'multiplier_label' => "{$multiplier}x",
            'freeze_count' => (int) ($user?->streak_freeze_count ?? 2),
            'freeze_active' => (bool) ($user?->streak_freeze_active ?? false),
        ];
    }

    public static function multiplierForReward(User|int $student, ?CarbonInterface $asOf = null): float
    {
        return (float) static::info($student, $asOf, true)['multiplier'];
    }

    private static function activityDates(?int $studentId, CarbonInterface $date, bool $includeAsOfDate): array
    {
        if (! $studentId) {
            return [];
        }

        $dates = StudentPoint::query()
            ->where('student_id', $studentId)
            ->where('created_at', '<=', $date->copy()->endOfDay())
            ->pluck('created_at')
            ->map(fn ($createdAt) => Carbon::parse($createdAt)->format('Y-m-d'))
            ->unique()
            ->all();

        if ($includeAsOfDate) {
            $dates[] = $date->format('Y-m-d');
        }

        return array_fill_keys($dates, true);
    }

    private static function countStreak(array $dates, CarbonInterface $date, bool $freezeActive): int
    {
        $streak = 0;
        $cursor = $date->copy();

        // A streak may remain active for today when the student has activity
        // yesterday, or when a freeze is protecting the missed day.
        if (! isset($dates[$cursor->format('Y-m-d')])) {
            $yesterday = $cursor->copy()->subDay();
            if (isset($dates[$yesterday->format('Y-m-d')]) || $freezeActive) {
                $cursor = $yesterday;
            }
        }

        while (true) {
            $dateKey = $cursor->format('Y-m-d');
            if (isset($dates[$dateKey])) {
                $streak++;
                $cursor = $cursor->subDay();
            } elseif ($freezeActive && $streak > 0) {
                // Match the dashboard's existing freeze behavior: preserve the
                // current streak instead of resetting it on one missed day.
                break;
            } else {
                break;
            }
        }

        return max(1, $streak);
    }

    private static function multiplier(int $streak): float
    {
        return match (true) {
            $streak >= 7 => 2.0,
            $streak >= 5 => 1.5,
            $streak >= 3 => 1.2,
            default => 1.0,
        };
    }
}
