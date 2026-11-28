<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCommerceRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $impersonatingSuperadmin = $request->session()->has('impersonated_by')
            && User::query()
                ->whereKey($request->session()->get('impersonated_by'))
                ->where('role', 'superadmin')
                ->exists();

        if ($impersonatingSuperadmin || ($roles === [] ? $user->isCommerceAdmin() : in_array($user->role, $roles, true))) {
            return $next($request);
        }

        if ($roles === [] || $request->expectsJson() || ! $request->isMethodSafe()) {
            abort(403, 'You do not have access to this commerce area.');
        }

        return redirect()->to(match (true) {
            $user->isCommerceAdmin() => route('admin.commerce.index'),
            $user->isSeller() && $roles !== [] => route('seller.dashboard'),
            $user->isBuyer() => route('account'),
            default => route('home'),
        });
    }
}
