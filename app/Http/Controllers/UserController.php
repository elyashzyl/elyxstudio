<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UserController extends Controller
{
    public function index(): Response
    {
        $this->authorizeAdmin();

        return Inertia::render('Commerce/Users', [
            'users' => User::query()
                ->orderByRaw("CASE role WHEN 'superadmin' THEN 1 WHEN 'seller' THEN 2 ELSE 3 END")
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'gender', 'bio', 'profile_photo_updated_at', 'created_at']),
            'basePath' => '/admin/users',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();
        $validated = $request->validate($this->userRules());
        $validated['password'] = Hash::make($validated['password']);
        $validated['email_verified_at'] = now();

        User::create($validated);

        return back()->with('success', 'ELYX account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $admin = $this->authorizeAdmin();
        $rules = $this->userRules($user);
        $rules['password'] = ['nullable', 'string', 'min:8'];
        $validated = $request->validate($rules);

        if ($user->is($admin) && ($validated['role'] ?? $user->role) !== 'superadmin') {
            abort(422, 'You cannot remove administrator access from your active account.');
        }

        if (filled($validated['password'] ?? null)) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        $user->update($validated);

        return back()->with('success', 'Account updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $admin = $this->authorizeAdmin();
        abort_if($user->is($admin), 403, 'You cannot delete your own account.');
        $user->delete();

        return back()->with('success', 'Account deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $admin = $this->authorizeAdmin();
        $ids = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ])['ids'];
        $count = User::query()->whereIn('id', $ids)->where('id', '!=', $admin->id)->delete();

        return back()->with('success', "Deleted {$count} account(s).");
    }

    public function impersonate(User $user): SymfonyResponse
    {
        $admin = $this->authorizeAdmin();
        abort_if($user->is($admin), 403, 'You are already logged in as this account.');
        abort_if($user->isSuperadmin(), 403, 'Administrator accounts cannot be impersonated.');

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->saveQuietly();
        }

        session(['impersonated_by' => $admin->id]);
        Auth::login($user);

        return Inertia::location(match (true) {
            $user->isSeller() => route('seller.dashboard'),
            default => route('account'),
        });
    }

    public function leaveImpersonation(): SymfonyResponse
    {
        $originalId = session('impersonated_by');
        abort_unless($originalId, 403, 'No impersonation session is active.');

        $admin = User::query()->whereKey($originalId)->where('role', 'superadmin')->firstOrFail();
        Auth::login($admin);
        session()->forget('impersonated_by');

        return response('', 409, ['X-Inertia-Location' => route('admin.commerce.index')]);
    }

    /** @return array<string, array<int, mixed>> */
    private function userRules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(Role::values())],
            'gender' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    private function authorizeAdmin(): User
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user?->isSuperadmin()) {
            return $user;
        }

        $originalId = session('impersonated_by');
        $admin = $originalId
            ? User::query()->whereKey($originalId)->where('role', 'superadmin')->first()
            : null;
        abort_unless($admin, 403);

        return $admin;
    }
}
