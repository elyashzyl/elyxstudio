<?php

namespace App\Http\Controllers;

use App\Models\AiManagerLog;
use App\Services\ActivityLogger;
use App\Services\AiManager\AiSystemManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AiManagerController extends Controller
{
    public function index(Request $request, AiSystemManagerService $service): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $statusFilter = $request->query('status');
        $eventFilter = $request->query('event_type');

        $query = AiManagerLog::query()->latest('created_at');

        if ($statusFilter && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($eventFilter && $eventFilter !== 'all') {
            $query->where('event_type', $eventFilter);
        }

        $logs = $query->paginate(25)->withQueryString();

        // Transform logs to include can_revert attribute
        $logs->through(function (AiManagerLog $log) {
            return [
                'id' => $log->id,
                'event_type' => $log->event_type,
                'status' => $log->status,
                'target_type' => $log->target_type ? class_basename($log->target_type) : null,
                'target_id' => $log->target_id,
                'confidence' => $log->confidence,
                'summary' => $log->summary,
                'payload' => $log->payload,
                'can_revert' => $log->canBeReverted(),
                'reverted_at' => $log->reverted_at?->toIso8601String(),
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        });

        return Inertia::render('Teacher/AiManagerLogs', [
            'logs' => $logs,
            'filters' => [
                'status' => $statusFilter ?? 'all',
                'event_type' => $eventFilter ?? 'all',
            ],
            'status' => $service->getStatus(),
        ]);
    }

    public function runInspection(Request $request, AiSystemManagerService $service): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        @set_time_limit(180);

        $result = $service->runInspection(true, "Superadmin ({$user->name})");

        ActivityLogger::log(
            'ai_manager_manual_run',
            "Manually executed AI Manager inspection: {$result['summary']}",
            null,
            $user
        );

        return back()->with('success', $result['summary']);
    }

    public function revert(Request $request, AiManagerLog $log): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        if (! $log->canBeReverted()) {
            return back()->with('error', 'This AI action cannot be reverted or has already been rolled back.');
        }

        $reverted = $log->revert($user);

        if ($reverted) {
            return back()->with('success', "Action #{$log->id} successfully reverted to previous state.");
        }

        return back()->with('error', 'Failed to revert AI action. Target record may no longer exist.');
    }
}
