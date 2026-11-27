<?php

namespace App\Services\AiManager;

use App\Models\Conversation;
use App\Models\Exam;
use App\Models\GradeLevel;
use App\Models\Message;
use App\Models\Practical;
use App\Models\Quiz;
use App\Models\Seatwork;
use App\Models\Section;
use App\Models\Setting;
use App\Models\TeachingMaterial;
use App\Models\User;
use DomainException;

class GuardrailPolicy
{
    public const FORBIDDEN_ACTIONS = [
        'delete',
        'destroy',
        'drop',
        'wipe',
        'truncate',
        'migrate:fresh',
        'migrate:reset',
        'db:wipe',
    ];

    public const PROTECTED_MODELS = [
        User::class,
        GradeLevel::class,
        Section::class,
        Quiz::class,
        Seatwork::class,
        Practical::class,
        Exam::class,
        TeachingMaterial::class,
        Message::class,
        Conversation::class,
        Setting::class,
    ];

    public const PROTECTED_SETTINGS = [
        'support_email',
        'payment_email',
    ];

    /**
     * Assert that the requested action is not on the forbidden blacklist.
     *
     * @throws DomainException
     */
    public static function assertActionAllowed(string $action, ?string $targetClass = null): void
    {
        $normalized = strtolower(trim($action));

        foreach (self::FORBIDDEN_ACTIONS as $forbidden) {
            if ($normalized === $forbidden || str_starts_with($normalized, $forbidden . ':')) {
                throw new DomainException("AI Manager Guardrail Violation: Action '{$action}' is strictly forbidden.");
            }
        }

        if ($targetClass !== null && ($normalized === 'delete' || str_contains($normalized, 'delete'))) {
            foreach (self::PROTECTED_MODELS as $protected) {
                if ($targetClass === $protected || is_subclass_of($targetClass, $protected)) {
                    throw new DomainException("AI Manager Guardrail Violation: Deleting records on '{$targetClass}' is strictly prohibited.");
                }
            }
        }
    }

    /**
     * Assert that a setting key is not protected from AI manager mutation.
     *
     * @throws DomainException
     */
    public static function assertSettingMutable(string $key): void
    {
        if (in_array(strtolower(trim($key)), self::PROTECTED_SETTINGS, true)) {
            throw new DomainException("AI Manager Guardrail Violation: Setting '{$key}' is protected and read-only.");
        }
    }

    /**
     * Verify if an auto-grade action meets the minimum confidence floor.
     */
    public static function canAutoApproveScore(float $confidence, float $threshold = 0.85): bool
    {
        return $confidence >= $threshold;
    }

    /**
     * Check if current manager mode allows autonomous database writes.
     */
    public static function allowsAutonomousMutation(string $mode): bool
    {
        return strtolower(trim($mode)) === 'autonomous';
    }

    /**
     * Check if mode requires human approval.
     */
    public static function allowsProposalOnly(string $mode): bool
    {
        return strtolower(trim($mode)) === 'approval';
    }

    /**
     * Check if mode is advisory (read-only watchdog).
     */
    public static function isAdvisoryOnly(string $mode): bool
    {
        return strtolower(trim($mode)) === 'advisory';
    }
}
