<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $target = $this->targetFor($user);
        $intended = session()->pull('url.intended');
        if ($this->isSafeIntendedTarget($intended, $user)) {
            $target = $intended;
        }

        if ($request->wantsJson() && ! $request->acceptsHtml() && ! $request->header('X-Inertia')) {
            return response()->json(['two_factor' => false, 'redirect' => $target]);
        }

        return $request->header('X-Inertia')
            ? Inertia::location($target)
            : redirect()->to($target);
    }

    private function targetFor(object $user): string
    {
        return match (true) {
            $user->isSuperadmin() => route('admin.commerce.index'),
            $user->isSeller() => route('seller.dashboard'),
            default => route('account'),
        };
    }

    private function isSafeIntendedTarget(?string $intended, object $user): bool
    {
        if (! $intended || str_contains($intended, '/login') || rtrim($intended, '/') === rtrim(url('/'), '/')) {
            return false;
        }

        $path = parse_url($intended, PHP_URL_PATH) ?? '';
        if (str_contains($path, '/api/') || str_contains($path, '/broadcasting') || str_contains($path, '/download')) {
            return false;
        }

        return match (true) {
            $user->isSuperadmin() => str_starts_with($path, '/admin'),
            $user->isSeller() => str_starts_with($path, '/seller'),
            default => str_starts_with($path, '/account'),
        };
    }
}
