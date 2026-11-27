<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function index(): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        return Inertia::render('Teacher/Backups', [
            'backups' => BackupService::listBackups(),
            'stats' => BackupService::getStats(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:full,database'],
            'notes' => ['nullable', 'string', 'max:255'],
            'sync_to_cloud' => ['nullable', 'boolean'],
        ]);

        try {
            $syncToCloud = $request->boolean('sync_to_cloud');
            $backup = BackupService::createBackup($validated['type'], $validated['notes'] ?? null, $syncToCloud);

            $msg = "Backup '{$backup['filename']}' ({$backup['formatted_size']}) created successfully.";
            if ($syncToCloud) {
                $msg .= ' Cloud synchronization initiated.';
            }

            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', "Backup failed: {$e->getMessage()}");
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        return BackupService::download($filename);
    }

    public function destroy(string $filename): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $deleted = BackupService::delete($filename);

        if ($deleted) {
            return back()->with('success', "Backup '{$filename}' was deleted.");
        }

        return back()->with('error', "Could not delete backup '{$filename}'.");
    }

    public function sync(string $filename): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        try {
            $result = BackupService::syncToCloud($filename);

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            return back()->with('error', "Cloud sync failed: {$e->getMessage()}");
        }
    }

    public function restore(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $validated = $request->validate([
            'filename' => ['required', 'string'],
        ]);

        try {
            $result = BackupService::restore($validated['filename']);

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            return back()->with('error', "Restore failed: {$e->getMessage()}");
        }
    }

    public function upload(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // 100MB
            'restore_immediately' => ['nullable', 'boolean'],
        ]);

        try {
            $andRestore = $request->boolean('restore_immediately');
            $result = BackupService::upload($request->file('file'), $andRestore);

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            return back()->with('error', "Upload failed: {$e->getMessage()}");
        }
    }
}
