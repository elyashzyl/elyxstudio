<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $query = $this->buildQuery($request);

        $logs = $query->paginate(50)->withQueryString();

        return Inertia::render('Teacher/ActivityLogs', [
            'logs' => $logs,
            'filters' => [
                'search' => $request->get('search', ''),
                'category' => $request->get('category', 'all'),
                'role' => $request->get('role', 'all'),
                'date' => $request->get('date', 'all'),
            ],
            'stats' => $this->getStats(),
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $sinceId = $request->get('since_id');

        $query = $this->buildQuery($request);

        if ($sinceId) {
            $query->where('id', '>', (int) $sinceId);
        }

        $newLogs = $query->limit(30)->get();

        return response()->json([
            'logs' => $newLogs,
            'stats' => $this->getStats(),
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $range = $request->input('range', '30_days');

        if ($range === 'all') {
            ActivityLog::truncate();
            $message = 'All activity logs have been cleared.';
        } else {
            $date = match ($range) {
                '7_days' => now()->subDays(7),
                '90_days' => now()->subDays(90),
                default => now()->subDays(30),
            };

            $count = ActivityLog::where('created_at', '<', $date)->delete();
            $message = "Cleared {$count} log records older than {$range}.";
        }

        return back()->with('success', $message);
    }

    protected function buildQuery(Request $request)
    {
        $query = ActivityLog::with('user:id,profile_photo_updated_at')->latest('id');

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('user_email', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $category = $request->get('category', 'all');
        if ($category !== 'all') {
            match ($category) {
                'auth' => $query->whereIn('action', ['login', 'logout', 'failed_login']),
                'submissions' => $query->where('action', 'like', '%_submitted'),
                'materials' => $query->where('action', 'like', 'material_%'),
                'content' => $query->where(fn ($q) => $q->where('action', 'like', '%_created')->orWhere('action', 'like', '%_published')),
                'admin' => $query->where(fn ($q) => $q->where('action', 'like', 'user_%')->orWhere('action', 'like', 'impersonate_%')),
                default => null,
            };
        }

        $role = $request->get('role', 'all');
        if ($role !== 'all') {
            $query->where('user_role', $role);
        }

        $dateFilter = $request->get('date', 'all');
        if ($dateFilter !== 'all') {
            match ($dateFilter) {
                'today' => $query->whereDate('created_at', Carbon::today()),
                '7d' => $query->where('created_at', '>=', now()->subDays(7)),
                '30d' => $query->where('created_at', '>=', now()->subDays(30)),
                default => null,
            };
        }

        return $query;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();

        return [
            'total_today' => ActivityLog::whereDate('created_at', $today)->count(),
            'logins_today' => ActivityLog::where('action', 'login')->whereDate('created_at', $today)->count(),
            'submissions_today' => ActivityLog::where('action', 'like', '%_submitted')->whereDate('created_at', $today)->count(),
            'unique_users_today' => ActivityLog::whereDate('created_at', $today)
                ->whereNotNull('user_id')
                ->distinct('user_id')
                ->count('user_id'),
            'total_count' => ActivityLog::count(),
        ];
    }
}
