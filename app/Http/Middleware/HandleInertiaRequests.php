<?php

namespace App\Http\Middleware;

use App\Models\SchoolYear;
use App\Services\Commerce\CartService;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'is_superadmin' => $user->isSuperadmin(),
                    'is_buyer' => $user->isBuyer(),
                    'is_seller' => $user->isSeller(),
                    'is_commerce_admin' => $user->isCommerceAdmin(),
                    'is_grade_10' => $user->isGrade10(),
                    'can_message_classmates' => $user->isStudent() ? $user->canMessageClassmates() : true,
                    'allow_classmate_messaging' => (bool) ($user->allow_classmate_messaging ?? true),
                ] : null,
            ],
            'impersonated_by' => session('impersonated_by'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'info' => fn () => $request->session()->get('info'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'xp_per_level' => \App\Helpers\LevelHelper::xpPerLevel(),
            'messenger_system_enabled' => Setting::isMessengerSystemEnabled(),
            'classmate_messaging_system_enabled' => Setting::isClassmateMessagingEnabled(),
            'support_email' => Setting::getSupportEmail(),
            'cart' => fn () => collect(app(CartService::class)->summary())->only(['count', 'subtotal', 'total'])->all(),
            'payment_email' => Setting::getPaymentEmail(),
            'school_years' => fn () => \Illuminate\Support\Facades\Schema::hasTable('school_years')
                ? SchoolYear::orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name', 'is_current', 'is_active'])
                : [],
            'current_school_year' => fn () => SchoolYear::current()?->only(['id', 'name', 'is_current']),
            'selected_school_year_id' => fn () => SchoolYear::selectedFilter(),
            'broadcasting' => [
                'driver' => config('broadcasting.default'),
                'reverb' => [
                    'key' => config('broadcasting.connections.reverb.key'),
                    'host' => config('broadcasting.connections.reverb.options.host'),
                    'port' => config('broadcasting.connections.reverb.options.port'),
                    'scheme' => config('broadcasting.connections.reverb.options.scheme'),
                ],
            ],
        ];
    }
}
