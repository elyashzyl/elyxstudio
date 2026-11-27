<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
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

        $target = match (true) {
            ! $user => route('login'),
            $user->isTeacher() || $user->isSuperadmin() => route('teacher.dashboard'),
            $user->isSeller() => route('seller.dashboard'),
            $user->isBuyer() => route('account'),
            default => route('student.dashboard'),
        };

        if ($request->wantsJson() && ! $request->acceptsHtml() && ! $request->header('X-Inertia')) {
            return response()->json([
                'redirect' => $target,
            ]);
        }

        if ($request->header('X-Inertia')) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }
}
