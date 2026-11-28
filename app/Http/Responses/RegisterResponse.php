<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        $user = Auth::user();
        $target = match (true) {
            ! $user => route('login'),
            $user->isSeller() => route('seller.dashboard'),
            default => route('account'),
        };

        if ($request->wantsJson() && ! $request->acceptsHtml() && ! $request->header('X-Inertia')) {
            return response()->json(['redirect' => $target]);
        }

        return $request->header('X-Inertia')
            ? Inertia::location($target)
            : redirect()->to($target);
    }
}
