<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if (! $user->isStudent()) {
            // Allow teachers and superadmins to view, test, and preview student game rooms
            if ($request->is('student/games*')) {
                return $next($request);
            }

            if ($request->expectsJson() || ! $request->isMethodSafe()) {
                abort(403, 'This area is reserved for student accounts.');
            }

            return redirect()->route('teacher.dashboard');
        }

        return $next($request);
    }
}
