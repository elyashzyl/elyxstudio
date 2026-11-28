<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\Commerce\CartService;
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
            'name' => config('app.name', 'ELYX Studio'),
            'auth' => [
                'user' => $user ? [
                    ...$user->toArray(),
                    'is_superadmin' => $user->isSuperadmin(),
                    'is_buyer' => $user->isBuyer(),
                    'is_seller' => $user->isSeller(),
                    'is_commerce_admin' => $user->isCommerceAdmin(),
                ] : null,
            ],
            'impersonated_by' => session('impersonated_by'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'info' => fn () => $request->session()->get('info'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'support_email' => Setting::getSupportEmail(),
            'payment_email' => Setting::getPaymentEmail(),
            'cart' => fn () => collect(app(CartService::class)->summary())
                ->only(['count', 'subtotal', 'total'])
                ->all(),
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
