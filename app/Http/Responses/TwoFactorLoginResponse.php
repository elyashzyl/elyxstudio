<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $target = match (true) {
            $user->isSuperadmin() => route('admin.commerce.index'),
            $user->isSeller() => route('seller.dashboard'),
            default => route('account'),
        };

        $intended = session()->pull('url.intended');
        $path = is_string($intended) ? (parse_url($intended, PHP_URL_PATH) ?? '') : '';
        if ($intended && match (true) {
            $user->isSuperadmin() => str_starts_with($path, '/admin'),
            $user->isSeller() => str_starts_with($path, '/seller'),
            default => str_starts_with($path, '/account'),
        }) {
            $target = $intended;
        }

        if ($request->wantsJson() && ! $request->acceptsHtml() && ! $request->header('X-Inertia')) {
            return response()->json(['two_factor' => false, 'redirect' => $target]);
        }

        return $request->header('X-Inertia')
            ? Inertia::location($target)
            : redirect()->to($target);
    }
}
