<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\ErrorLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ErrorLogController extends Controller
{
    public function index(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $selectedFile = $request->query('file');
        $level = $request->query('level', 'all');
        $search = $request->query('search', '');

        $files = ErrorLogService::getLogFiles();
        $entries = ErrorLogService::getEntries($selectedFile, 100, $level, $search);
        $stats = ErrorLogService::getStats($selectedFile);

        return Inertia::render('Teacher/ErrorLogs', [
            'entries' => $entries,
            'files' => $files,
            'stats' => $stats,
            'filters' => [
                'file' => $selectedFile ?: ($files[0]['name'] ?? 'laravel.log'),
                'level' => $level,
                'search' => $search,
            ],
        ]);
    }

    public function clear(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $file = $request->input('file');
        ErrorLogService::clear($file);

        ActivityLogger::log(
            action: 'clear_error_logs',
            description: "Cleared system error log (" . ($file ?: 'laravel.log') . ")",
            user: $user,
            properties: ['file' => $file ?: 'laravel.log']
        );

        return back()->with('success', 'Error log file has been cleared.');
    }

    public function download(Request $request): BinaryFileResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $file = $request->query('file');

        return ErrorLogService::download($file);
    }

    public function generateTest(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        ErrorLogService::generateTestLogs($user->name);

        ActivityLogger::log(
            action: 'test_error_logs',
            description: "Generated test diagnostic log entries",
            user: $user
        );

        return back()->with('success', 'Test diagnostic log entries written to laravel.log.');
    }
}
