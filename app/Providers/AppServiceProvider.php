<?php

namespace App\Providers;

use App\Models\User;
use App\Services\ActivityLogger;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        DB::prohibitDestructiveCommands(app()->isProduction());
        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)->mixedCase()->letters()->numbers()->symbols()->uncompromised()
            : null,
        );

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->updateQuietly(['last_login_at' => now()]);
                ActivityLogger::logLogin($event->user);
            }
        });
        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user instanceof User) {
                ActivityLogger::logLogout($event->user);
            }
        });
        Event::listen(Failed::class, fn (Failed $event) => ActivityLogger::logFailedLogin((string) ($event->credentials['email'] ?? 'unknown')));
    }
}
