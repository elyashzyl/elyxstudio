<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'key';

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            $setting = static::query()->find($key);

            return $setting ? $setting->value : $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value],
        );
    }

    public static function isMessengerSystemEnabled(): bool
    {
        return static::get('messenger_system_enabled') !== 'false';
    }

    public static function isClassmateMessagingEnabled(): bool
    {
        return static::get('classmate_messaging_enabled') !== 'false';
    }

    public static function getSchoolName(): string
    {
        return (string) static::get('school_name', env('SCHOOL_NAME', config('app.name', 'EduPulse')));
    }

    public static function getSchoolShort(): string
    {
        return (string) static::get('school_short', env('SCHOOL_SHORT', 'EduPulse'));
    }

    public static function getSchoolId(): string
    {
        return (string) static::get('school_id', env('SCHOOL_ID', ''));
    }

    public static function getSchoolAddress(): string
    {
        return (string) static::get('school_address', env('SCHOOL_ADDRESS', ''));
    }

    public static function getSupportEmail(): string
    {
        return (string) static::get('support_email', env('SUPPORT_EMAIL', 'ely.ashzyl@gmail.com'));
    }

    public static function getPaymentEmail(): string
    {
        return (string) static::get('payment_email', env('PAYMENT_EMAIL', 'ely.ashzyl@gmail.com'));
    }

    public static function getAppName(): string
    {
        return (string) static::get('app_name', env('APP_NAME', config('app.name', 'EduPulse')));
    }

    public static function isRegistrationEnabled(): bool
    {
        return static::get('allow_registration', env('ALLOW_REGISTRATION', false) ? 'true' : 'false') === 'true';
    }

    public static function isMaintenanceMode(): bool
    {
        return static::get('maintenance_mode', 'false') === 'true';
    }

    public static function getMaintenanceMessage(): string
    {
        return (string) static::get('maintenance_message', 'System is undergoing brief maintenance.');
    }
}
