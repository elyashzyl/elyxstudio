<?php

namespace App\Helpers;

use App\Models\Setting;

class LevelHelper
{
    public static function xpPerLevel(): int
    {
        $setting = Setting::get('xp_per_level');
        if ($setting !== null && is_numeric($setting) && (int) $setting > 0) {
            return (int) $setting;
        }

        return (int) config('gamification.xp_per_level', 100);
    }

    public static function level(int $points): int
    {
        return intdiv(max(0, $points), self::xpPerLevel()) + 1;
    }

    public static function progress(int $points): int
    {
        return max(0, $points) % self::xpPerLevel();
    }

    public static function pointsForLevel(int $level): int
    {
        return max(0, $level - 1) * self::xpPerLevel();
    }

    public static function title(int $level): string
    {
        $titles = config('gamification.level_titles', []);
        $fallback = config('gamification.level_title_fallback', 'Champion');

        return $titles[$level - 1] ?? $fallback;
    }

    public static function titleForPoints(int $points): string
    {
        return self::title(self::level($points));
    }

    /**
     * Get raw level rewards merged with any custom overrides stored in settings.
     *
     * @return array<int, array{name: string, description: string}>
     */
    public static function rawLevelRewards(): array
    {
        $defaults = (array) config('gamification.level_rewards', []);
        $customRaw = Setting::get('level_rewards_custom');

        if ($customRaw) {
            $custom = is_string($customRaw) ? json_decode($customRaw, true) : $customRaw;
            if (is_array($custom)) {
                foreach ($custom as $level => $reward) {
                    $lvl = (int) $level;
                    if ($lvl >= 1 && is_array($reward) && !empty($reward['name'])) {
                        // Overrides only rename/redescribe; keep the effect type and params.
                        $base = is_array($defaults[$lvl] ?? null) ? $defaults[$lvl] : [];
                        $defaults[$lvl] = array_merge($base, [
                            'name' => (string) $reward['name'],
                            'description' => (string) ($reward['description'] ?? ''),
                        ]);
                    }
                }
            }
        }

        ksort($defaults);

        return $defaults;
    }

    /**
     * All level rewards with calculated XP required and rank titles.
     *
     * @return array<int, array{level: int, title: string, xp_required: int, name: string, description: string}>
     */
    public static function allLevelRewards(): array
    {
        $raw = self::rawLevelRewards();
        $xpPerLevel = self::xpPerLevel();
        $titles = config('gamification.level_titles', []);
        $fallback = config('gamification.level_title_fallback', 'Champion');

        $result = [];
        foreach ($raw as $level => $reward) {
            $lvl = (int) $level;
            $result[] = [
                'level' => $lvl,
                'title' => $titles[$lvl - 1] ?? $fallback,
                'xp_required' => max(0, $lvl - 1) * $xpPerLevel,
                'name' => is_array($reward) ? ($reward['name'] ?? "Level {$lvl} Reward") : (string) $reward,
                'description' => is_array($reward) ? ($reward['description'] ?? '') : '',
            ];
        }

        usort($result, fn ($a, $b) => $a['level'] <=> $b['level']);

        return $result;
    }

    /**
     * Only rewards for levels the student has actually reached are returned,
     * so future rewards stay secret on the client.
     *
     * @return array<int, array{level: int, title: string, description: string}>
     */
    public static function unlockedRewards(int $points): array
    {
        $rewards = self::rawLevelRewards();
        $current = self::level($points);
        $result = [];

        foreach ($rewards as $level => $reward) {
            if ((int) $level > $current) {
                break;
            }

            $result[] = [
                'level' => (int) $level,
                'title' => is_array($reward) ? ($reward['name'] ?? "Level {$level} Reward") : (string) $reward,
                'description' => is_array($reward) ? ($reward['description'] ?? '') : '',
            ];
        }

        return $result;
    }
}