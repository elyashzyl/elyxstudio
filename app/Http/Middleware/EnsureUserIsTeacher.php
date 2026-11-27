<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTeacher
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isTeacher()) {
            if ($request->expectsJson() || ! $request->isMethodSafe()) {
                abort(403, 'This area is reserved for teacher accounts.');
            }

            return redirect()->route('student.dashboard');
        }

        return $next($request);
    }
}
