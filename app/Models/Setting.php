<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    protected $keyType = 'string';

    protected $primaryKey = 'key';

    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            if (! Schema::hasTable('settings')) {
                return $default;
            }

            return static::query()->find($key)?->value ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], [
            'value' => is_bool($value) ? ($value ? 'true' : 'false') : (string) $value,
        ]);
    }

    public static function getSupportEmail(): string
    {
        return (string) static::get('support_email', env('SUPPORT_EMAIL', 'hello@elyx.studio'));
    }

    public static function getPaymentEmail(): string
    {
        return (string) static::get('payment_email', env('PAYMENT_EMAIL', 'hello@elyx.studio'));
    }

    public static function getAppName(): string
    {
        return (string) static::get('app_name', env('APP_NAME', config('app.name', 'ELYX Studio')));
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
        return (string) static::get('maintenance_message', 'The studio is undergoing brief maintenance.');
    }
}
