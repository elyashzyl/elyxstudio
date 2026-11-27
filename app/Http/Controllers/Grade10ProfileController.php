<?php

namespace App\Http\Controllers;

use App\Models\Grade10Profile;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Grade10ProfileController extends Controller
{
    /**
     * Display the Grade 10 student questionnaire form.
     */
    public function studentShow(): Response
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isGrade10()) {
            abort(403, 'This questionnaire is exclusively for Grade 10 students.');
        }

        $profile = Grade10Profile::where('user_id', $user->id)->first();

        return Inertia::render('Student/Grade10Profile/Index', [
            'profile' => $profile ? [
                'id' => $profile->id,
                'full_name' => $profile->full_name,
                'chinese_name' => $profile->chinese_name,
                'birthday' => $profile->birthday,
                'hometown' => $profile->hometown,
                'ambition' => $profile->ambition,
                'motto' => $profile->motto,
                'message' => $profile->message,
                'social_media' => $profile->social_media,
                'photo_url' => $profile->getPhotoUrl(),
                'submitted_at' => $profile->submitted_at?->format('F j, Y g:i A'),
            ] : null,
            'default_name' => $user->name,
        ]);
    }

    /**
     * Store or update the student's Grade 10 profile.
     */
    public function studentStore(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isGrade10()) {
            abort(403, 'This questionnaire is exclusively for Grade 10 students.');
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'chinese_name' => ['nullable', 'string', 'max:255'],
            'birthday' => ['required', 'string', 'max:100'],
            'hometown' => ['required', 'string', 'max:255'],
            'ambition' => ['required', 'string', 'max:255'],
            'motto' => ['required', 'string', 'max:500'],
            'message' => ['required', 'string', 'max:1500'],
            'social_media' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $existing = Grade10Profile::where('user_id', $user->id)->first();
        $photoPath = $existing?->photo_path;

        if ($request->boolean('remove_photo')) {
            if ($photoPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($photoPath);
            }
            $photoPath = null;
        }

        if ($request->hasFile('photo')) {
            if ($photoPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($photoPath);
            }
            $photoPath = $request->file('photo')->store('grade10-photos', 'public');
        }

        $fieldsToSave = collect($validated)->except(['photo', 'remove_photo'])->all();

        Grade10Profile::updateOrCreate(
            ['user_id' => $user->id],
            [
                ...$fieldsToSave,
                'photo_path' => $photoPath,
                'submitted_at' => now(),
            ]
        );

        return redirect()->route('student.grade10.profile')->with('success', 'Your Grade 10 profile has been submitted successfully.');
    }

    /**
     * Display the teacher/superadmin view of all Grade 10 profiles.
     */
    public function teacherIndex(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isTeacher(), 403);

        $profiles = Grade10Profile::with(['user.section', 'user.gradeLevel'])
            ->latest('submitted_at')
            ->get()
            ->map(fn ($p, $idx) => [
                'no' => $idx + 1,
                'id' => $p->id,
                'student_name' => $p->user?->name ?? 'Unknown',
                'section' => $p->user?->section?->name ?? 'Unassigned',
                'full_name' => $p->full_name,
                'chinese_name' => $p->chinese_name ?: '—',
                'birthday' => $p->birthday,
                'hometown' => $p->hometown,
                'ambition' => $p->ambition,
                'motto' => $p->motto,
                'message' => $p->message,
                'social_media' => $p->social_media ?: '—',
                'photo_url' => $p->getPhotoUrl(),
                'submitted_at' => $p->submitted_at?->format('M j, Y g:i A') ?? '—',
            ]);

        // Find Grade 10 students who have not yet submitted
        $pendingStudents = User::where('role', 'student')
            ->where(function ($q) {
                $q->whereHas('gradeLevel', fn ($gq) => $gq->whereRaw('LOWER(name) LIKE ?', ['%10%']))
                    ->orWhereRaw('LOWER(grade) LIKE ?', ['%10%']);
            })
            ->whereDoesntHave('grade10Profile')
            ->with('section')
            ->get(['id', 'name', 'section_id'])
            ->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'section' => $s->section?->name ?? 'Unassigned',
            ]);

        return Inertia::render('Teacher/Grade10Profiles/Index', [
            'profiles' => $profiles,
            'pendingStudents' => $pendingStudents,
        ]);
    }

    /**
     * Export all Grade 10 profiles in the requested format (pdf, excel, word, csv).
     */
    public function export(Request $request, string $format): HttpResponse|StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user->isTeacher(), 403);

        $profiles = Grade10Profile::with(['user.section'])
            ->orderBy('full_name')
            ->get();

        $format = strtolower($format);

        return match ($format) {
            'pdf' => $this->exportPdf($profiles),
            'word', 'doc' => $this->exportWord($profiles),
            'csv' => $this->exportCsv($profiles),
            default => $this->exportExcel($profiles),
        };
    }

    protected function exportPdf($profiles): HttpResponse
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        $pdf = Pdf::loadView('exports.grade10_profiles_pdf', [
            'profiles' => $profiles,
            'generatedAt' => now()->format('F j, Y g:i A'),
        ])
        ->setPaper('a4', 'landscape')
        ->setOption('isHtml5ParserEnabled', true)
        ->setOption('isRemoteEnabled', false)
        ->setOption('defaultFont', 'Helvetica');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="grade_10_profiles_' . date('Y_m_d') . '.pdf"',
        ]);
    }

    protected function exportExcel($profiles): HttpResponse
    {
        $html = view('exports.grade10_profiles_excel', [
            'profiles' => $profiles,
            'generatedAt' => now()->format('F j, Y g:i A'),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="grade_10_profiles_' . date('Y_m_d') . '.xls"',
        ]);
    }

    protected function exportWord($profiles): HttpResponse
    {
        $html = view('exports.grade10_profiles_word', [
            'profiles' => $profiles,
            'generatedAt' => now()->format('F j, Y g:i A'),
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="grade_10_profiles_' . date('Y_m_d') . '.doc"',
        ]);
    }

    protected function exportCsv($profiles): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="grade_10_profiles_' . date('Y_m_d') . '.csv"',
        ];

        return response()->stream(function () use ($profiles) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens special characters and Chinese names properly
            fwrite($handle, "\xEF\xBB\xBF");

            // Exact column headers requested:
            fputcsv($handle, [
                '#',
                'FULL NAME (Last Name, First Name, Middle Name)',
                'CHINESE NAME (Chinese Class Students)',
                'BIRTHDAY (Month, Day, Year)',
                'HOMETOWN',
                'AMBITION',
                'MOTTO',
                'MESSAGE (3-5 sentences only)',
                'SOCIAL MEDIA ACCOUNT (Optional)',
            ]);

            foreach ($profiles as $idx => $p) {
                fputcsv($handle, [
                    $idx + 1,
                    $p->full_name,
                    $p->chinese_name ?? '',
                    $p->birthday,
                    $p->hometown,
                    $p->ambition,
                    $p->motto,
                    $p->message,
                    $p->social_media ?? '',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
