<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        $isTeacher = $user->isTeacher() || $user->isSuperadmin();
        $isSeller = $user->isSeller();
        $isBuyer = $user->isBuyer();
        $defaultTarget = match (true) {
            $isTeacher => route('teacher.dashboard'),
            $user->isSeller() => route('seller.dashboard'),
            $user->isBuyer() => route('account'),
            default => route('student.dashboard'),
        };
        $target = $defaultTarget;

        $intended = session()->pull('url.intended');
        if ($intended && ! str_contains($intended, '/login') && rtrim($intended, '/') !== rtrim(url('/'), '/')) {
            $path = parse_url($intended, PHP_URL_PATH) ?? '';

            $isInvalidTarget = str_contains($path, '/notifications')
                || str_contains($path, '/api/')
                || str_contains($path, '/sync-timer')
                || str_contains($path, '/broadcasting')
                || str_contains($path, '/read')
                || str_contains($path, '/download')
                || str_contains($path, '/preview')
                || str_ends_with($path, '/answer')
                || str_ends_with($path, '/progress');

            if (! $isInvalidTarget) {
                if ($isTeacher) {
                    if (! str_starts_with($path, '/student') && ! str_starts_with($path, '/account') && ! str_starts_with($path, '/seller') && ! str_starts_with($path, '/admin')) {
                        $target = $intended;
                    }
                } elseif ($isSeller) {
                    if (str_starts_with($path, '/seller')) {
                        $target = $intended;
                    }
                } elseif ($isBuyer) {
                    if (str_starts_with($path, '/account')) {
                        $target = $intended;
                    }
                } else {
                    if (! str_starts_with($path, '/teacher') && ! str_starts_with($path, '/account') && ! str_starts_with($path, '/seller') && ! str_starts_with($path, '/admin')) {
                        $target = $intended;
                    }
                }
            }
        }

        if ($request->wantsJson() && ! $request->acceptsHtml() && ! $request->header('X-Inertia')) {
            return response()->json([
                'two_factor' => false,
                'redirect' => $target,
            ]);
        }

        if ($request->header('X-Inertia')) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }
}
