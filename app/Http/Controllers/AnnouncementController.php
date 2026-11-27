<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function teacherIndex(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = Announcement::with(['author:id,name,role', 'gradeLevel:id,name', 'section:id,name'])
            ->orderByDesc('is_pinned')
            ->latest();

        $announcements = $query->paginate(25)->through(fn ($a) => [
            'id' => $a->id,
            'title' => $a->title,
            'content' => $a->content,
            'category' => $a->category ?: 'general',
            'is_pinned' => (bool) $a->is_pinned,
            'target_role' => $a->target_role ?: 'student',
            'target_grade_level_id' => $a->target_grade_level_id,
            'target_section_id' => $a->target_section_id,
            'grade_level' => $a->gradeLevel ? ['id' => $a->gradeLevel->id, 'name' => $a->gradeLevel->name] : null,
            'section' => $a->section ? ['id' => $a->section->id, 'name' => $a->section->name] : null,
            'author_name' => $a->author?->name ?? 'EduPulse Admin',
            'author_id' => $a->user_id,
            'can_manage' => true,
            'created_at' => $a->created_at?->toIso8601String(),
            'formatted_date' => $a->created_at?->format('M d, Y g:i A'),
            'expires_at' => $a->expires_at?->format('Y-m-d\TH:i'),
        ]);

        $gradeLevels = GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']);
        $sections = Section::where('is_active', true)->orderBy('name')->get(['id', 'name', 'grade_level_id']);

        return Inertia::render('Teacher/Announcements', [
            'announcements' => $announcements,
            'gradeLevels' => $gradeLevels,
            'sections' => $sections,
        ]);
    }

    public function studentIndex(Request $request): Response
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();

        $announcements = Announcement::with(['author:id,name,role', 'gradeLevel:id,name', 'section:id,name'])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('target_role')
                  ->orWhere('target_role', 'student')
                  ->orWhere('target_role', 'all');
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('target_grade_level_id')
                  ->orWhere('target_grade_level_id', $student->grade_level_id);
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('target_section_id')
                  ->orWhere('target_section_id', $student->section_id);
            })
            ->orderByDesc('is_pinned')
            ->latest()
            ->paginate(12)
            ->through(fn ($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'category' => $a->category ?: 'general',
                'is_pinned' => (bool) $a->is_pinned,
                'target_grade_level_id' => $a->target_grade_level_id,
                'target_section_id' => $a->target_section_id,
                'grade_level' => $a->gradeLevel ? ['id' => $a->gradeLevel->id, 'name' => $a->gradeLevel->name] : null,
                'section' => $a->section ? ['id' => $a->section->id, 'name' => $a->section->name] : null,
                'author_name' => $a->author?->name ?? 'EduPulse Administration',
                'author_role' => $a->author?->role ?? 'Teacher',
                'created_at' => $a->created_at?->toIso8601String(),
                'formatted_date' => $a->created_at ? $a->created_at->format('M d, Y') : '',
                'time_ago' => $a->created_at ? $a->created_at->diffForHumans() : '',
            ]);

        return Inertia::render('Student/Announcements', [
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string|in:general,academic,reminder,urgent,event',
            'is_pinned' => 'nullable|boolean',
            'target_grade_level_id' => 'nullable|exists:grade_levels,id',
            'target_section_id' => 'nullable|exists:sections,id',
            'expires_at' => 'nullable|date',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['target_role'] = 'student';
        $validated['category'] = $validated['category'] ?? 'general';
        $validated['is_pinned'] = (bool) ($validated['is_pinned'] ?? false);
        $validated['target_grade_level_id'] = $validated['target_grade_level_id'] ?? null;
        $validated['target_section_id'] = $validated['target_section_id'] ?? null;

        if (! empty($validated['target_section_id'])) {
            $section = Section::find($validated['target_section_id']);
            if ($section) {
                $validated['target_grade_level_id'] = $section->grade_level_id;
            }
        }

        Announcement::create($validated);

        return back()->with('success', 'Announcement published successfully.');
    }

    public function togglePin(Announcement $announcement): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isTeacher() || $user->isSuperadmin(), 403, 'Unauthorized.');

        $announcement->update([
            'is_pinned' => ! $announcement->is_pinned,
        ]);

        return back()->with('success', $announcement->is_pinned ? 'Announcement pinned.' : 'Announcement unpinned.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isTeacher() || $user->isSuperadmin(), 403, 'Unauthorized.');

        $announcement->delete();

        return back()->with('success', 'Announcement removed.');
    }
}
