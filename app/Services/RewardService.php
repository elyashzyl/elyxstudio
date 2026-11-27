<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\AttemptStatus;
use App\Helpers\LevelHelper;
use App\Helpers\PointsHelper;
use App\Models\ClassTodo;
use App\Models\Practical;
use App\Models\PracticalAttempt;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\RewardRedemption;
use App\Models\Seatwork;
use App\Models\SeatworkAttempt;
use App\Models\StudentPoint;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RewardService
{
    /** Levels whose armed state lives on a legacy users column instead of reward_redemptions. */
    private const LEGACY_BOOSTER_LEVEL = 1;
    private const LEGACY_SHIELD_LEVEL = 4;

    public const BOOSTABLE_ACTIVITIES = ['Quiz', 'Seatwork', 'Exam', 'Practical', 'Game'];

    public const MAX_STREAK_FREEZES = 5;

    // ─── Definitions ───────────────────────────────────────────────

    /**
     * Reward definitions keyed by level, each normalized with a `type`.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $defs = [];
        foreach (LevelHelper::rawLevelRewards() as $level => $reward) {
            $lvl = (int) $level;
            $reward = is_array($reward) ? $reward : ['name' => (string) $reward];
            // Custom levels without an effect still do something useful.
            if (empty($reward['type'])) {
                $reward['type'] = 'xp_grant';
                $reward['xp'] = $reward['xp'] ?? 25;
            }
            $reward['level'] = $lvl;
            $defs[$lvl] = $reward;
        }

        return $defs;
    }

    public static function definition(int $level): ?array
    {
        return self::definitions()[$level] ?? null;
    }

    public static function typeOf(int $level): ?string
    {
        return self::definition($level)['type'] ?? null;
    }

    /**
     * Unlocked, unused levels of a given type, lowest level first.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function availableOfType(User $user, string $type): array
    {
        $currentLevel = LevelHelper::level((int) $user->total_points);
        $used = self::usedLevels($user);

        return array_values(array_filter(
            self::definitions(),
            fn ($d) => $d['type'] === $type && $d['level'] <= $currentLevel && ! in_array($d['level'], $used, true)
        ));
    }

    /** @return array<string, int> badge_key => level */
    public static function badgeLevels(): array
    {
        $map = [];
        foreach (self::definitions() as $d) {
            if ($d['type'] === 'badge' && ! empty($d['badge_key'])) {
                $map[$d['badge_key']] = $d['level'];
            }
        }

        return $map;
    }

    public static function badgeBonusPct(?string $badgeKey): int
    {
        if (! $badgeKey) {
            return 0;
        }

        foreach (self::definitions() as $d) {
            if ($d['type'] === 'badge' && ($d['badge_key'] ?? null) === $badgeKey) {
                return (int) ($d['xp_bonus_pct'] ?? 0);
            }
        }

        return 0;
    }

    public static function effectLabel(array $d): string
    {
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');

        return match ($d['type']) {
            'booster' => isset($d['chance'])
                ? "{$d['chance']}% chance of {$fmt($d['multiplier'])}× XP, else {$fmt($d['fallback_multiplier'] ?? 1)}× (next graded activity)"
                : "{$fmt($d['multiplier'] ?? 2)}× XP on your next graded activity",
            'badge' => '+' . (int) ($d['xp_bonus_pct'] ?? 0) . '% XP on graded activities while equipped',
            'homework_pass' => 'Complete one quiz, seatwork, practical/performance task, or task with full marks',
            'shield' => 'Forgives ' . (int) ($d['protect'] ?? 1) . ' wrong answer' . ((int) ($d['protect'] ?? 1) === 1 ? '' : 's') . ' on your next quiz',
            'time_warp' => '+' . (int) ($d['seconds'] ?? 30) . 's on a timed quiz',
            'compass' => 'Removes up to ' . (int) ($d['eliminate'] ?? 2) . ' wrong choices on one quiz question',
            'xp_grant' => '+' . (int) ($d['xp'] ?? 0) . ' XP instantly',
            'streak_freeze' => '+' . (int) ($d['count'] ?? 1) . ' streak freeze' . ((int) ($d['count'] ?? 1) === 1 ? '' : 's'),
            default => 'Special reward',
        };
    }

    // ─── Redemption state ──────────────────────────────────────────

    public static function canUse(User $user, int $level): bool
    {
        $currentLevel = LevelHelper::level((int) $user->total_points);
        if ($currentLevel < $level) {
            return false;
        }

        return ! self::isUsed($user, $level);
    }

    public static function isUsed(User $user, int $level): bool
    {
        return RewardRedemption::where('user_id', $user->id)
            ->where('level', $level)
            ->where('status', 'used')
            ->exists();
    }

    /** @return array<int, int> */
    private static function usedLevels(User $user): array
    {
        return RewardRedemption::where('user_id', $user->id)
            ->where('status', 'used')
            ->pluck('level')
            ->map(fn ($l) => (int) $l)
            ->all();
    }

    /** @return array<int, int> */
    private static function activeLevels(User $user): array
    {
        return RewardRedemption::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('level')
            ->map(fn ($l) => (int) $l)
            ->all();
    }

    public static function consume(User $user, int $level, ?array $metadata = null): RewardRedemption
    {
        return RewardRedemption::updateOrCreate(
            ['user_id' => $user->id, 'level' => $level],
            [
                'status' => 'used',
                'metadata' => $metadata,
            ]
        );
    }

    private static function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }

    // ─── Boosters ──────────────────────────────────────────────────

    /** The armed booster definition, if any. */
    public static function activeBooster(User $user): ?array
    {
        if ($user->active_xp_booster && self::canUse($user, self::LEGACY_BOOSTER_LEVEL)
            && self::typeOf(self::LEGACY_BOOSTER_LEVEL) === 'booster') {
            return self::definition(self::LEGACY_BOOSTER_LEVEL);
        }

        foreach (self::activeLevels($user) as $lvl) {
            $def = self::definition($lvl);
            if ($def && $def['type'] === 'booster') {
                return $def;
            }
        }

        return null;
    }

    public static function toggleBooster(User $user, int $level): array
    {
        if (self::typeOf($level) !== 'booster' || ! self::canUse($user, $level)) {
            return self::fail('That booster is not available.');
        }

        $current = self::activeBooster($user);

        if ($current && $current['level'] === $level) {
            if ($level === self::LEGACY_BOOSTER_LEVEL) {
                $user->active_xp_booster = false;
                $user->save();
            } else {
                RewardRedemption::where('user_id', $user->id)->where('level', $level)->where('status', 'active')->delete();
            }

            return ['ok' => true, 'active' => false, 'message' => "{$current['name']} deactivated."];
        }

        if ($current) {
            return self::fail("{$current['name']} is already active. Deactivate it first — only one booster can run at a time.");
        }

        if ($level === self::LEGACY_BOOSTER_LEVEL) {
            $user->active_xp_booster = true;
            $user->save();
        } else {
            RewardRedemption::updateOrCreate(
                ['user_id' => $user->id, 'level' => $level],
                ['status' => 'active', 'metadata' => ['activated_at' => now()->toIso8601String()]]
            );
        }

        $def = self::definition($level);

        return ['ok' => true, 'active' => true, 'message' => "{$def['name']} activated! " . self::effectLabel($def) . '.'];
    }

    /** Kept for backwards compatibility. */
    public static function toggleXpBooster(User $user): bool
    {
        return (bool) (self::toggleBooster($user, self::LEGACY_BOOSTER_LEVEL)['active'] ?? false);
    }

    /**
     * Roll the armed booster, consume it and return [multiplier, label].
     *
     * @return array{0: float, 1: string}|null
     */
    public static function consumeBooster(User $user, string $activityType, int $activityId): ?array
    {
        $def = self::activeBooster($user);
        if (! $def) {
            return null;
        }

        $multiplier = (float) ($def['multiplier'] ?? 2);
        if (isset($def['chance']) && random_int(1, 100) > (int) $def['chance']) {
            $multiplier = (float) ($def['fallback_multiplier'] ?? 1);
        }

        if ($def['level'] === self::LEGACY_BOOSTER_LEVEL) {
            $user->active_xp_booster = false;
            $user->save();
        }

        self::consume($user, $def['level'], [
            'activity_type' => $activityType,
            'activity_id' => $activityId,
            'multiplier' => $multiplier,
        ]);

        return [$multiplier, $def['name']];
    }

    // ─── Shields ───────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> armed shields, lowest level first */
    public static function armedShields(User $user): array
    {
        $armed = [];

        if ($user->active_brain_shield && self::canUse($user, self::LEGACY_SHIELD_LEVEL)
            && self::typeOf(self::LEGACY_SHIELD_LEVEL) === 'shield') {
            $armed[] = self::definition(self::LEGACY_SHIELD_LEVEL);
        }

        foreach (self::activeLevels($user) as $lvl) {
            $def = self::definition($lvl);
            if ($def && $def['type'] === 'shield') {
                $armed[] = $def;
            }
        }

        usort($armed, fn ($a, $b) => $a['level'] <=> $b['level']);

        return $armed;
    }

    public static function armedShieldProtection(User $user): int
    {
        return array_sum(array_map(fn ($d) => (int) ($d['protect'] ?? 1), self::armedShields($user)));
    }

    public static function toggleShield(User $user, int $level): array
    {
        if (self::typeOf($level) !== 'shield' || ! self::canUse($user, $level)) {
            return self::fail('That shield is not available.');
        }

        $def = self::definition($level);
        $isArmed = collect(self::armedShields($user))->contains(fn ($d) => $d['level'] === $level);

        if ($level === self::LEGACY_SHIELD_LEVEL) {
            $user->active_brain_shield = ! $isArmed;
            $user->save();
        } elseif ($isArmed) {
            RewardRedemption::where('user_id', $user->id)->where('level', $level)->where('status', 'active')->delete();
        } else {
            RewardRedemption::updateOrCreate(
                ['user_id' => $user->id, 'level' => $level],
                ['status' => 'active', 'metadata' => ['armed_at' => now()->toIso8601String()]]
            );
        }

        return [
            'ok' => true,
            'active' => ! $isArmed,
            'message' => $isArmed
                ? "{$def['name']} disarmed."
                : "{$def['name']} armed! " . self::effectLabel($def) . '.',
        ];
    }

    /** Kept for backwards compatibility. */
    public static function toggleBrainShield(User $user): bool
    {
        return (bool) (self::toggleShield($user, self::LEGACY_SHIELD_LEVEL)['active'] ?? false);
    }

    /**
     * Consume just enough armed shields to cover the forgiven answers.
     *
     * @return array<int, int> levels consumed
     */
    public static function consumeShields(User $user, int $forgiven, array $metadata = []): array
    {
        $consumed = [];
        $covered = 0;

        foreach (self::armedShields($user) as $def) {
            if ($covered >= $forgiven) {
                break;
            }
            $covered += (int) ($def['protect'] ?? 1);
            $consumed[] = $def['level'];

            if ($def['level'] === self::LEGACY_SHIELD_LEVEL) {
                $user->active_brain_shield = false;
                $user->save();
            }

            self::consume($user, $def['level'], $metadata);
        }

        return $consumed;
    }

    // ─── Homework pass ─────────────────────────────────────────────

    /**
     * @param  string  $type  quiz | seatwork | task
     */
    public static function applyHomeworkPass(User $user, string $type, int $id): array
    {
        $passes = self::availableOfType($user, 'homework_pass');
        if (empty($passes)) {
            return self::fail('You have no Homework Pass available.');
        }
        $pass = $passes[0];

        $type = strtolower(trim($type));

        $result = match ($type) {
            'seatwork' => self::passSeatwork($user, $id),
            'quiz' => self::passQuiz($user, $id),
            'task', 'todo' => self::passTask($user, $id),
            'practical', 'performance_task', 'performance-task', 'performance task', 'pt' => self::passPractical($user, $id),
            default => self::fail('Homework Pass only works on quizzes, seatworks, performance tasks, and tasks.'),
        };

        if (! $result['ok']) {
            return $result;
        }

        self::consume($user, $pass['level'], [
            'activity_type' => $type,
            'activity_id' => $id,
            'applied_at' => now()->toIso8601String(),
        ]);

        $remaining = count($passes) - 1;

        return [
            'ok' => true,
            'remaining' => $remaining,
            'message' => "{$pass['name']} applied to '{$result['title']}'! Completed with 100% score."
                . ($remaining > 0 ? " ({$remaining} pass" . ($remaining === 1 ? '' : 'es') . ' left)' : ''),
            'redirect_url' => $result['redirect_url'] ?? null,
        ];
    }

    private static function canAccessActivity(User $user, Model $activity): bool
    {
        if (! $activity->is_published) {
            return false;
        }

        $teacherIds = $user->teachers()->pluck('users.id')
            ->concat(User::where('role', 'superadmin')->pluck('id'))
            ->map(fn ($v) => (int) $v)
            ->all();

        if (! in_array((int) $activity->teacher_id, $teacherIds, true)) {
            return false;
        }

        return $activity->gradeLevels()->exists()
            ? ($user->grade_level_id && $activity->gradeLevels()->where('grade_level_id', $user->grade_level_id)->exists())
            : ($user->grade && $activity->grade === $user->grade);
    }

    private static function passSeatwork(User $user, int $id): array
    {
        $seatwork = Seatwork::with('questions')->find($id);
        if (! $seatwork || ! self::canAccessActivity($user, $seatwork)) {
            return self::fail('That seatwork is not available to you.');
        }
        if ($seatwork->isClosed()) {
            return self::fail('That seatwork has already closed.');
        }

        $submitted = SeatworkAttempt::where('seatwork_id', $seatwork->id)
            ->where('student_id', $user->id)
            ->where('status', AttemptStatus::Submitted->value)
            ->count();
        if ($submitted > 0) {
            return self::fail('You already submitted that seatwork.');
        }

        $totalPoints = $seatwork->totalPoints() ?: 100;

        DB::transaction(function () use ($seatwork, $user, $totalPoints) {
            $attempt = SeatworkAttempt::where('seatwork_id', $seatwork->id)
                ->where('student_id', $user->id)
                ->where('status', AttemptStatus::InProgress->value)
                ->latest('id')
                ->first();

            $attempt ??= new SeatworkAttempt([
                'seatwork_id' => $seatwork->id,
                'student_id' => $user->id,
                'attempt_number' => 1,
                'started_at' => now(),
            ]);

            $attempt->fill([
                'status' => AttemptStatus::Submitted->value,
                'score' => $totalPoints,
                'total_points' => $totalPoints,
                'is_recorded' => true,
                'submitted_at' => now(),
            ])->save();

            foreach ($seatwork->questions as $q) {
                DB::table('seatwork_answers')->updateOrInsert(
                    ['attempt_id' => $attempt->id, 'question_id' => $q->id],
                    [
                        'answer_text' => 'Completed via Homework Pass',
                        'is_correct' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $seatwork->recalculateRecordedAttempts($user->id);

            PointsHelper::award(
                $user->id,
                ActivityType::Seatwork->value,
                $seatwork->id,
                $totalPoints,
                $totalPoints,
                'Seatwork: 100% (Completed via Homework Pass)'
            );
        });

        return ['ok' => true, 'title' => $seatwork->title];
    }

    private static function passQuiz(User $user, int $id): array
    {
        $quiz = Quiz::with('questions.options')->find($id);
        if (! $quiz || ! self::canAccessActivity($user, $quiz)) {
            return self::fail('That quiz is not available to you.');
        }
        if ($quiz->isClosed()) {
            return self::fail('That quiz has already closed.');
        }

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $user->id)
            ->latest('id')
            ->first();
        if ($attempt && $attempt->status === AttemptStatus::Submitted->value) {
            return self::fail('You already submitted that quiz.');
        }

        $totalPoints = (int) $quiz->questions->sum('points');
        if ($totalPoints <= 0) {
            return self::fail('That quiz has no graded questions yet.');
        }

        DB::transaction(function () use ($quiz, $user, $attempt, $totalPoints) {
            $attempt ??= new QuizAttempt([
                'quiz_id' => $quiz->id,
                'student_id' => $user->id,
                'started_at' => now(),
            ]);

            $attempt->fill([
                'status' => AttemptStatus::Submitted->value,
                'score' => $totalPoints,
                'total_points' => $totalPoints,
                'submitted_at' => now(),
            ])->save();

            foreach ($quiz->questions as $q) {
                $correct = $q->options->firstWhere('is_correct', true);
                DB::table('quiz_answers')->updateOrInsert(
                    ['quiz_attempt_id' => $attempt->id, 'question_id' => $q->id],
                    [
                        'quiz_option_id' => $q->type === 'identification' ? null : $correct?->id,
                        'answer_text' => $q->type === 'identification' ? ($correct?->option_text ?? 'Completed via Homework Pass') : null,
                        'is_correct' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            PointsHelper::award(
                $user->id,
                ActivityType::Quiz->value,
                $quiz->id,
                $totalPoints,
                $totalPoints,
                'Quiz: 100% (Completed via Homework Pass)'
            );
        });

        return ['ok' => true, 'title' => $quiz->title];
    }

    private static function passTask(User $user, int $id): array
    {
        $todo = ClassTodo::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('target_grade_level_id')->orWhere('target_grade_level_id', $user->grade_level_id))
            ->where(fn ($q) => $q->whereNull('target_section_id')->orWhere('target_section_id', $user->section_id))
            ->find($id);

        if (! $todo) {
            return self::fail('That task is not available to you.');
        }
        if ($todo->completions()->where('user_id', $user->id)->exists()) {
            return self::fail('That task is already completed.');
        }

        $todo->completions()->create(['user_id' => $user->id, 'completed_at' => now()]);

        return ['ok' => true, 'title' => $todo->title];
    }

    private static function passPractical(User $user, int $id): array
    {
        $practical = Practical::with('criteria')->find($id);
        if (! $practical || ! self::canAccessActivity($user, $practical)) {
            return self::fail('That practical / performance task is not available to you.');
        }
        if ($practical->isClosed()) {
            return self::fail('That practical / performance task has already closed.');
        }

        $submittedCount = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $user->id)
            ->where('status', AttemptStatus::Submitted->value)
            ->count();

        if ($submittedCount >= $practical->max_attempts) {
            return self::fail('You have already reached the maximum attempts for this performance task.');
        }

        $hasClosed = PracticalAttempt::where('practical_id', $practical->id)
            ->where('student_id', $user->id)
            ->whereNotNull('closed_at')
            ->exists();

        if ($hasClosed) {
            return self::fail('Your submission for this performance task has been closed.');
        }

        $totalScore = $practical->criteria->isNotEmpty()
            ? (int) $practical->criteria->sum('max_points')
            : (int) ($practical->max_score ?: 100);

        if ($totalScore <= 0) {
            $totalScore = (int) ($practical->max_score ?: 100);
        }

        if ($submittedCount > 0) {
            $hasFullMarks = PracticalAttempt::where('practical_id', $practical->id)
                ->where('student_id', $user->id)
                ->where('status', AttemptStatus::Submitted->value)
                ->where('total_score', '>=', $totalScore)
                ->exists();
            if ($hasFullMarks) {
                return self::fail('You already completed this performance task with full marks.');
            }
        }

        $attemptId = null;

        DB::transaction(function () use ($practical, $user, $totalScore, &$attemptId) {
            $attempt = PracticalAttempt::where('practical_id', $practical->id)
                ->where('student_id', $user->id)
                ->where('status', AttemptStatus::InProgress->value)
                ->latest('id')
                ->first();

            $attempt ??= new PracticalAttempt([
                'practical_id' => $practical->id,
                'student_id' => $user->id,
                'started_at' => now(),
            ]);

            $attempt->fill([
                'status' => AttemptStatus::Submitted->value,
                'total_score' => $totalScore,
                'submission_text' => 'Completed via Homework Pass',
                'submitted_at' => now(),
            ])->save();

            $attemptId = $attempt->id;

            foreach ($practical->criteria as $criterion) {
                $attempt->scores()->updateOrCreate(
                    ['criterion_id' => $criterion->id],
                    [
                        'score' => $criterion->max_points,
                        'comment' => 'Completed via Homework Pass (100% full marks)',
                    ]
                );
            }

            PointsHelper::award(
                $user->id,
                ActivityType::Practical->value,
                $practical->id,
                $totalScore,
                $practical->max_score ?: $totalScore,
                'Practical: 100% (Completed via Homework Pass)'
            );

            ActivityLogger::log(
                'practical_submitted',
                "Student {$user->name} completed practical '{$practical->title}' via Homework Pass",
                $practical,
                $user
            );
        });

        return [
            'ok' => true,
            'title' => $practical->title,
            'redirect_url' => $attemptId ? route('student.practicals.result', ['attempt' => $attemptId], false) : null,
        ];
    }

    // ─── Time warp ─────────────────────────────────────────────────

    public static function applyTimeWarp(User $user, ?int $quizId = null): array
    {
        $warps = self::availableOfType($user, 'time_warp');
        if (empty($warps)) {
            return self::fail('You have no Time Warp available.');
        }

        $quiz = $quizId ? Quiz::find($quizId) : null;
        if ($quiz && ! $quiz->time_limit_minutes) {
            return self::fail('This quiz has no timer to extend.');
        }

        $warp = $warps[0];
        $seconds = (int) ($warp['seconds'] ?? 30);

        // Shift the persisted start so the extra time survives a page refresh.
        if ($quiz) {
            $attempt = QuizAttempt::where('quiz_id', $quiz->id)
                ->where('student_id', $user->id)
                ->where('status', AttemptStatus::InProgress->value)
                ->latest('id')
                ->first();

            if ($attempt?->started_at) {
                $attempt->started_at = $attempt->started_at->copy()->addSeconds($seconds);
                $attempt->save();
            }
        }

        self::consume($user, $warp['level'], [
            'quiz_id' => $quizId,
            'added_seconds' => $seconds,
            'applied_at' => now()->toIso8601String(),
        ]);

        $next = $warps[1] ?? null;

        return [
            'ok' => true,
            'added_seconds' => $seconds,
            'remaining' => count($warps) - 1,
            'next_seconds' => $next ? (int) ($next['seconds'] ?? 30) : null,
            'message' => "{$warp['name']} activated! +{$seconds} seconds added.",
        ];
    }

    // ─── Compass ───────────────────────────────────────────────────

    public static function applyStarCompass(User $user, int $questionId, array $clientOptions = []): array
    {
        $compasses = self::availableOfType($user, 'compass');
        if (empty($compasses)) {
            return self::fail('You have no Compass available.');
        }

        $question = Question::with('options')->find($questionId);

        if ($question) {
            $inProgress = QuizAttempt::where('quiz_id', $question->quiz_id)
                ->where('student_id', $user->id)
                ->where('status', AttemptStatus::InProgress->value)
                ->exists();
            if (! $inProgress) {
                return self::fail('Start the quiz before using a Compass.');
            }

            $wrongIds = $question->options->where('is_correct', false)->pluck('id')->map(fn ($v) => (int) $v)->all();
        } else {
            // Unknown question: only the client's own data is at stake.
            $wrongIds = [];
            foreach ($clientOptions as $opt) {
                if (! ((bool) ($opt['is_correct'] ?? false)) && isset($opt['id'])) {
                    $wrongIds[] = (int) $opt['id'];
                }
            }
        }

        if (empty($wrongIds)) {
            return self::fail('There are no wrong choices to remove on this question.');
        }

        $compass = $compasses[0];
        // Leave at least one wrong choice standing when possible so the question stays a question.
        $eliminate = min((int) ($compass['eliminate'] ?? 2), max(1, count($wrongIds) - 1));

        shuffle($wrongIds);
        $eliminated = array_slice($wrongIds, 0, $eliminate);

        self::consume($user, $compass['level'], [
            'question_id' => $questionId,
            'eliminated_options' => $eliminated,
            'applied_at' => now()->toIso8601String(),
        ]);

        $next = $compasses[1] ?? null;

        return [
            'ok' => true,
            'eliminated_options' => $eliminated,
            'remaining' => count($compasses) - 1,
            'next_eliminate' => $next ? (int) ($next['eliminate'] ?? 2) : null,
            'message' => "{$compass['name']} revealed: " . count($eliminated) . ' incorrect choice' . (count($eliminated) === 1 ? '' : 's') . ' eliminated!',
        ];
    }

    // ─── Instant rewards ───────────────────────────────────────────

    public static function claimXpGrant(User $user, int $level): array
    {
        $def = self::definition($level);
        if (! $def || $def['type'] !== 'xp_grant' || ! self::canUse($user, $level)) {
            return self::fail('That reward is not available.');
        }

        $xp = max(0, (int) ($def['xp'] ?? 0));

        DB::transaction(function () use ($user, $level, $xp, $def) {
            StudentPoint::updateOrCreate(
                ['student_id' => $user->id, 'activity_type' => ActivityType::Reward->value, 'activity_id' => $level],
                ['points' => $xp, 'score' => $xp, 'total' => $xp, 'reason' => "Level {$level} reward: {$def['name']}"]
            );
            PointsHelper::syncTotal($user->id);
            self::consume($user, $level, ['xp' => $xp, 'claimed_at' => now()->toIso8601String()]);
        });

        $user->refresh();

        return ['ok' => true, 'message' => "{$def['name']} claimed! +{$xp} XP added."];
    }

    public static function claimStreakFreeze(User $user, int $level): array
    {
        $def = self::definition($level);
        if (! $def || $def['type'] !== 'streak_freeze' || ! self::canUse($user, $level)) {
            return self::fail('That reward is not available.');
        }

        $current = (int) ($user->streak_freeze_count ?? 0);
        if ($current >= self::MAX_STREAK_FREEZES) {
            return self::fail('Your streak freeze inventory is full (max ' . self::MAX_STREAK_FREEZES . '). Use one first.');
        }

        $count = max(1, (int) ($def['count'] ?? 1));
        $newCount = min(self::MAX_STREAK_FREEZES, $current + $count);
        $user->streak_freeze_count = $newCount;
        $user->save();

        self::consume($user, $level, ['added' => $newCount - $current, 'claimed_at' => now()->toIso8601String()]);

        return ['ok' => true, 'message' => "{$def['name']} claimed! You now have {$newCount} streak freeze" . ($newCount === 1 ? '' : 's') . '.'];
    }

    // ─── Badges ────────────────────────────────────────────────────

    public static function equipBadge(User $user, string $badge): array
    {
        $badge = trim(strtolower($badge));

        if ($user->equipped_badge === $badge) {
            $user->equipped_badge = null;
            $user->save();

            return [
                'ok' => true,
                'equipped_badge' => null,
                'message' => 'Badge unequipped.',
            ];
        }

        $requiredLevel = self::badgeLevels()[$badge] ?? null;
        if (! $requiredLevel) {
            return ['ok' => false, 'message' => 'Unknown badge type.'];
        }

        $currentLevel = LevelHelper::level((int) $user->total_points);
        if ($currentLevel < $requiredLevel) {
            return [
                'ok' => false,
                'message' => "Level {$requiredLevel} required to equip this badge.",
            ];
        }

        $user->equipped_badge = $badge;
        $user->save();

        $pct = self::badgeBonusPct($badge);

        return [
            'ok' => true,
            'equipped_badge' => $badge,
            'message' => 'Badge equipped!' . ($pct > 0 ? " +{$pct}% XP on graded activities." : ''),
        ];
    }

    // ─── Hub ───────────────────────────────────────────────────────

    public static function getStudentRewardsHub(User $user): array
    {
        $allRewards = LevelHelper::allLevelRewards();
        $defs = self::definitions();
        $currentLevel = LevelHelper::level((int) $user->total_points);
        $redemptions = RewardRedemption::where('user_id', $user->id)
            ->get()
            ->keyBy('level');

        $activeBooster = self::activeBooster($user);
        $armedShieldLevels = array_map(fn ($d) => $d['level'], self::armedShields($user));

        $items = [];
        foreach ($allRewards as $r) {
            $lvl = $r['level'];
            $def = $defs[$lvl] ?? ['type' => 'xp_grant', 'xp' => 25];
            $type = $def['type'];
            $redemption = $redemptions->get($lvl);
            $isUsed = $type !== 'badge' && $redemption && $redemption->status === 'used';
            $badgeKey = $type === 'badge' ? ($def['badge_key'] ?? null) : null;

            $isActive = match ($type) {
                'booster' => $activeBooster && $activeBooster['level'] === $lvl,
                'shield' => in_array($lvl, $armedShieldLevels, true),
                default => false,
            };

            $items[] = [
                'level' => $lvl,
                'title' => $r['name'],
                'description' => $r['description'],
                'type' => $type,
                'effect_label' => self::effectLabel($def),
                'badge_key' => $badgeKey,
                'xp_bonus_pct' => $type === 'badge' ? (int) ($def['xp_bonus_pct'] ?? 0) : null,
                'xp' => $type === 'xp_grant' ? (int) ($def['xp'] ?? 0) : null,
                'count' => $type === 'streak_freeze' ? (int) ($def['count'] ?? 1) : null,
                'unlocked' => $lvl <= $currentLevel,
                'used' => $isUsed,
                'active' => $isActive,
                'equipped' => $badgeKey && $user->equipped_badge === $badgeKey,
                'xp_required' => $r['xp_required'],
            ];
        }

        return [
            'items' => $items,
            'active_xp_booster' => (bool) $user->active_xp_booster,
            'active_brain_shield' => (bool) $user->active_brain_shield,
            'active_booster_level' => $activeBooster['level'] ?? null,
            'equipped_badge' => $user->equipped_badge,
            'streak_freeze_count' => (int) ($user->streak_freeze_count ?? 0),
            'current_level' => $currentLevel,
        ];
    }
}
