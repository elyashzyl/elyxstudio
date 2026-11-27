<?php

namespace App\Http\Controllers;

use App\Models\PracticalAttempt;
use App\Services\SecureFileService;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class PracticalFileController extends Controller
{
    public function show(PracticalAttempt $attempt): BinaryFileResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $attempt->loadMissing('practical');

        $canView = $user->isSuperadmin()
            || ($user->isStudent() && $attempt->student_id === $user->id)
            || ($user->isTeacher() && $attempt->practical?->teacher_id === $user->id);

        abort_unless($canView, 403);

        $filePath = SecureFileService::resolve($attempt->submission_file);
        abort_unless($filePath !== null, 404, 'The submission file could not be found.');

        $filename = SecureFileService::safeFilename(
            pathinfo($filePath, PATHINFO_BASENAME),
            'submission.' . strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION)),
        );

        return response()->file($filePath, [
            'Content-Type' => mime_content_type($filePath) ?: 'application/octet-stream',
            'Content-Disposition' => ResponseHeaderBag::makeDisposition('inline', $filename),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
