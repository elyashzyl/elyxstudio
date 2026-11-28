<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Laravel\Fortify\Actions\AttemptToAuthenticate;
use Laravel\Fortify\Actions\EnsureLoginIsNotThrottled;
use Laravel\Fortify\Actions\PrepareAuthenticatedSession;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponse::class, \App\Http\Responses\LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponse::class, \App\Http\Responses\TwoFactorLoginResponse::class);
        $this->app->singleton(RegisterResponse::class, \App\Http\Responses\RegisterResponse::class);
        $this->app->singleton(LogoutResponse::class, \App\Http\Responses\LogoutResponse::class);
    }

    public function boot(): void
    {
        $this->configureActions();
        $this->configureViews();
        $this->configureRateLimiting();

        Fortify::authenticateThrough(function (Request $request) {
            return array_values(array_filter([
                config('fortify.limiters.login') ? EnsureLoginIsNotThrottled::class : null,
                Features::enabled(Features::twoFactorAuthentication()) ? RedirectIfTwoFactorAuthenticatable::class : null,
                AttemptToAuthenticate::class,
                PrepareAuthenticatedSession::class,
            ]));
        });
    }

    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    private function configureViews(): void
    {
        Fortify::loginView(fn (Request $request) => Inertia::render('auth/Login', [
            'canResetPassword' => Features::enabled(Features::resetPasswords()),
            'canRegister' => Features::enabled(Features::registration()),
            'status' => $request->session()->get('status'),
        ]));

        if (Features::enabled(Features::registration())) {
            Fortify::registerView(fn () => Inertia::render('auth/Register', [
                'passwordRules' => Password::defaults()->toPasswordRulesString(),
            ]));
        }

        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('auth/ResetPassword', [
            'email' => $request->email,
            'token' => $request->route('token'),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
        ]));

        Fortify::requestPasswordResetLinkView(fn (Request $request) => Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::verifyEmailView(fn (Request $request) => Inertia::render('auth/VerifyEmail', [
            'status' => $request->session()->get('status'),
        ]));

        Fortify::twoFactorChallengeView(fn () => Inertia::render('auth/TwoFactorChallenge'));
        Fortify::confirmPasswordView(fn () => Inertia::render('auth/ConfirmPassword'));
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($key);
        });
        RateLimiter::for('passkeys', fn (Request $request) => Limit::perMinute(10)->by(
            ($request->input('credential.id') ?: $request->session()->getId()).'|'.$request->ip(),
        ));
        RateLimiter::for('commerce-cart', fn (Request $request) => [
            Limit::perMinute(60)->by($request->session()->getId()),
            Limit::perMinute(120)->by($request->ip()),
        ]);
        RateLimiter::for('commerce-checkout', fn (Request $request) => [
            Limit::perMinute(10)->by($request->session()->getId()),
            Limit::perMinute(30)->by($request->ip()),
        ]);
    }
}
