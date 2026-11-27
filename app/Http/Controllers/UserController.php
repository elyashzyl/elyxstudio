<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserMergeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class UserController extends Controller
{
    public function index(): Response
    {
        /** @var User $teacher */
        $teacher = Auth::user();
        $impersonatedBy = session('impersonated_by');
        $isSuper = $teacher && ($teacher->isSuperadmin() || ($impersonatedBy && User::find($impersonatedBy)?->isSuperadmin()));
        abort_unless($isSuper, 403);

        $users = User::with('section:id,name', 'gradeLevel:id,name', 'subjects:id,name')
            ->orderBy('role')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'gender', 'grade', 'grade_level_id', 'section_id', 'profile_photo_updated_at', 'created_at']);

        $gradeLevels = GradeLevel::orderBy('name')->get(['id', 'name']);
        $sections = Section::orderBy('name')->get(['id', 'name', 'grade_level_id']);
        $subjects = Subject::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Commerce/Users', [
            'users' => $users,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
            'subjects' => $subjects,
            'basePath' => '/admin/users',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $teacher */
        $teacher = Auth::user();
        abort_unless($teacher->isSuperadmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(['student', 'teacher', 'superadmin', 'buyer', 'seller'])],
            'password' => ['required', 'string', 'min:8'],
            'gender' => ['nullable', 'string', 'in:Male,Female'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $validated['email_verified_at'] = now();
        if (! empty($validated['grade_level_id'])) {
            $validated['grade'] = GradeLevel::where('id', $validated['grade_level_id'])->value('name');
        }

        $newUser = User::create($validated);

        if ($newUser->role === 'teacher' && $request->has('subject_ids')) {
            $newUser->subjects()->sync($request->input('subject_ids', []));
        }

        ActivityLogger::log(
            'user_created',
            "Created {$newUser->role} user '{$newUser->name}' ({$newUser->email})",
            $newUser
        );

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        /** @var User $teacher */
        $teacher = Auth::user();
        abort_unless($teacher->isSuperadmin(), 403);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(['student', 'teacher', 'superadmin', 'buyer', 'seller'])],
            'gender' => ['nullable', 'string', 'in:Male,Female'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'subject_ids' => ['nullable', 'array'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $validated = $request->validate($rules);

        if ($request->filled('password')) {
            $validated['password'] = bcrypt($validated['password']);
        }

        if (array_key_exists('grade_level_id', $validated)) {
            $validated['grade'] = $validated['grade_level_id']
                ? GradeLevel::where('id', $validated['grade_level_id'])->value('name')
                : null;
        }

        $user->update($validated);

        if (($user->role === 'teacher' || ($validated['role'] ?? '') === 'teacher') && $request->has('subject_ids')) {
            $user->subjects()->sync($request->input('subject_ids', []));
        }

        ActivityLogger::log(
            'user_updated',
            "Updated user '{$user->name}' ({$user->email})",
            $user
        );

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        /** @var User $teacher */
        $teacher = Auth::user();
        abort_unless($teacher->isSuperadmin(), 403);
        abort_if($user->id === $teacher->id, 403, 'You cannot delete your own account.');

        $userName = $user->name;
        $userRole = $user->role;
        $user->delete();

        ActivityLogger::log(
            'user_deleted',
            "Deleted {$userRole} user '{$userName}'",
            null,
            null,
            ['name' => $userName, 'role' => $userRole]
        );

        return back()->with('success', 'User deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        /** @var User $teacher */
        $teacher = Auth::user();
        abort_unless($teacher->isSuperadmin(), 403);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $count = 0;
        foreach (User::whereIn('id', $data['ids'])->get() as $user) {
            if ($user->id === $teacher->id) {
                continue;
            }
            $user->delete();
            $count++;
        }

        return back()->with('success', "Deleted {$count} user(s).");
    }

    public function impersonate(User $user): SymfonyResponse
    {
        /** @var User $admin */
        $admin = Auth::user();
        $impersonatorId = session('impersonated_by');
        $isSuper = $admin && ($admin->isSuperadmin() || ($impersonatorId && User::find($impersonatorId)?->isSuperadmin()));

        abort_unless($isSuper, 403, 'Only superadmins can impersonate users.');
        abort_if($user->id === $admin->id, 403, 'You are already logged in as this user.');

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        session(['impersonated_by' => $impersonatorId ?: $admin->id]);
        Auth::login($user);

        ActivityLogger::log(
            'impersonate_start',
            "Superadmin started impersonating '{$user->name}'",
            $user,
            $admin
        );

        $dashboard = $user->isTeacher() || $user->isSuperadmin()
            ? route('teacher.dashboard')
            : ($user->isSeller()
                ? route('seller.dashboard')
                : ($user->isBuyer() ? route('account') : route('student.dashboard')));

        return Inertia::location($dashboard);
    }

    public function leaveImpersonation(): SymfonyResponse
    {
        $originalId = session('impersonated_by');
        if ($originalId) {
            Auth::loginUsingId($originalId);
            session()->forget('impersonated_by');

            ActivityLogger::log(
                'impersonate_end',
                'Superadmin stopped impersonation'
            );
        }

        return Inertia::location(route('teacher.dashboard'));
    }

    public function toggleSystemMessenger(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);
        $enabled = Setting::isMessengerSystemEnabled();
        Setting::updateOrCreate(
            ['key' => 'messenger_system_enabled'],
            ['value' => $enabled ? 'false' : 'true'],
        );

        return back()->with('success', $enabled ? 'System messenger disabled.' : 'System messenger enabled.');
    }

    public function toggleSystemClassmateMessaging(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);
        $enabled = Setting::isClassmateMessagingEnabled();
        Setting::updateOrCreate(
            ['key' => 'classmate_messaging_enabled'],
            ['value' => $enabled ? 'false' : 'true'],
        );

        return back()->with(
            'success',
            $enabled
                ? 'System-wide classmate messaging disabled. Students can only message teachers.'
                : 'System-wide classmate messaging enabled.'
        );
    }

    public function merge(Request $request, UserMergeService $mergeService): RedirectResponse
    {
        /** @var User $admin */
        $admin = Auth::user();
        abort_unless($admin->isSuperadmin(), 403, 'Only superadmins can merge user accounts.');

        $validated = $request->validate([
            'source_id' => ['required', 'integer', 'exists:users,id'],
            'target_id' => ['required', 'integer', 'exists:users,id', 'different:source_id'],
        ]);

        $source = User::findOrFail($validated['source_id']);
        $target = User::findOrFail($validated['target_id']);

        abort_if($source->id === $admin->id, 403, 'You cannot merge the account you are currently logged in with as the source account.');

        try {
            $stats = $mergeService->merge($source, $target);

            return back()->with(
                'success',
                "Successfully merged '{$stats['source_name']}' into '{$stats['target_name']}'. All attempts, messages, XP, and records consolidated."
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to merge user accounts: '.$e->getMessage());
        }
    }
}
