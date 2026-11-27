<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class CheckMessengerEnabled
{
    public function handle(Request $request, Closure $next)
    {
        if (! Setting::isMessengerSystemEnabled()) {
            abort(404);
        }

        return $next($request);
    }
}
