<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public static function log(
        string $action,
        string $description,
        mixed $entity = null,
        ?User $user = null,
        array $properties = [],
    ): void {
        Log::info('ELYX activity', [
            'action' => $action,
            'description' => $description,
            'entity' => is_object($entity) ? class_basename($entity) : $entity,
            'entity_id' => is_object($entity) && isset($entity->id) ? $entity->id : null,
            'user_id' => $user?->id,
            'user_role' => $user?->role,
            'properties' => $properties,
        ]);
    }

    public static function logLogin(User $user): void
    {
        self::log('login', "{$user->name} logged in successfully", $user, $user, [
            'role' => $user->role,
        ]);
    }

    public static function logLogout(User $user): void
    {
        self::log('logout', "User {$user->name} logged out", $user, $user);
    }

    public static function logFailedLogin(string $email): void
    {
        self::log('failed_login', "Failed login attempt for email: {$email}", null, null, [
            'email' => $email,
        ]);
    }
}
