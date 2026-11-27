<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log an activity in the system.
     */
    public static function log(
        string $action,
        string $description,
        mixed $entity = null,
        ?User $user = null,
        array $properties = []
    ): ?ActivityLog {
        try {
            /** @var User|null $currentUser */
            $currentUser = $user ?? Auth::user();
            $ip = Request::ip();
            $agent = Request::userAgent();

            $entityType = null;
            $entityId = null;

            if ($entity) {
                if (is_object($entity)) {
                    $entityType = class_basename($entity);
                    if (isset($entity->id)) {
                        $entityId = (int) $entity->id;
                    }
                } elseif (is_string($entity)) {
                    $entityType = $entity;
                }
            }

            $userId = ($currentUser && $currentUser->exists) ? $currentUser->id : null;

            return ActivityLog::create([
                'user_id' => $userId,
                'user_name' => $currentUser?->name ?? 'Guest',
                'user_email' => $currentUser?->email,
                'user_role' => $currentUser?->role ?? 'guest',
                'action' => $action,
                'description' => $description,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'ip_address' => $ip,
                'user_agent' => $agent,
                'properties' => ! empty($properties) ? $properties : null,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Log a successful login event.
     */
    public static function logLogin(User $user): void
    {
        $roleTitle = match ($user->role) {
            'superadmin' => 'Administrator',
            'teacher' => 'Teacher',
            default => 'Student',
        };

        self::log(
            action: 'login',
            description: "{$roleTitle} {$user->name} logged in successfully",
            entity: $user,
            user: $user,
            properties: [
                'role' => $user->role,
                'grade' => $user->grade,
            ]
        );
    }

    /**
     * Log a logout event.
     */
    public static function logLogout(User $user): void
    {
        self::log(
            action: 'logout',
            description: "User {$user->name} logged out",
            entity: $user,
            user: $user
        );
    }

    /**
     * Log a failed login attempt.
     */
    public static function logFailedLogin(string $email): void
    {
        self::log(
            action: 'failed_login',
            description: "Failed login attempt for email: {$email}",
            user: null,
            properties: ['email' => $email]
        );
    }
}
