<?php

namespace App\Http\Controllers;

use App\Models\GradeLevel;
use App\Models\Poll;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PollController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();
        $query = Poll::query()
            ->with(['teacher:id,name', 'gradeLevel:id,name', 'section:id,name'])
            ->with(['votes:id,poll_id,option_index'])
            ->latest();

        if (! $user->isSuperadmin()) {
            $query->where('teacher_id', $user->id);
        }

        $polls = $query->paginate(12)->through(function (Poll $poll) {
            $totalVotes = $poll->votes->count();
            $voteCounts = $poll->votes->groupBy('option_index')->map->count();

            return [
                'id' => $poll->id,
                'question' => $poll->question,
                'options' => collect($poll->options ?? [])->values()->map(function ($option, $index) use ($voteCounts, $totalVotes) {
                    $votes = (int) ($voteCounts[$index] ?? 0);

                    return [
                        'text' => $option,
                        'votes' => $votes,
                        'percentage' => $totalVotes > 0 ? round(($votes / $totalVotes) * 100) : 0,
                    ];
                })->all(),
                'is_active' => (bool) $poll->is_active,
                'total_votes' => $totalVotes,
                'grade_level' => $poll->gradeLevel ? [
                    'id' => $poll->gradeLevel->id,
                    'name' => $poll->gradeLevel->name,
                ] : null,
                'section' => $poll->section ? [
                    'id' => $poll->section->id,
                    'name' => $poll->section->name,
                ] : null,
                'teacher_name' => $poll->teacher?->name ?? 'EduPulse Administration',
                'created_at' => $poll->created_at?->format('M d, Y g:i A'),
            ];
        });

        return Inertia::render('Teacher/Polls', [
            'polls' => $polls,
            'gradeLevels' => GradeLevel::where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name']),
            'sections' => Section::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'grade_level_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_array($request->input('options'))) {
            $request->merge([
                'options' => array_map(
                    static fn ($option) => is_string($option) ? trim($option) : $option,
                    $request->input('options'),
                ),
            ]);
        }

        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'options' => ['required', 'array', 'min:2', 'max:10'],
            'options.*' => ['required', 'string', 'min:1', 'max:255'],
            'target_grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'target_section_id' => ['nullable', 'integer', 'exists:sections,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Poll::create([
            'teacher_id' => Auth::id(),
            'question' => trim($data['question']),
            'options' => array_values($data['options']),
            'target_grade_level_id' => $data['target_grade_level_id'] ?? null,
            'target_section_id' => $data['target_section_id'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', 'Classroom poll created successfully.');
    }

    public function toggleActive(Poll $poll): RedirectResponse
    {
        $this->authorizePoll($poll);
        $poll->update(['is_active' => ! $poll->is_active]);

        return back()->with('success', $poll->is_active ? 'Poll activated.' : 'Poll deactivated.');
    }

    public function destroy(Poll $poll): RedirectResponse
    {
        $this->authorizePoll($poll);
        $poll->delete();

        return back()->with('success', 'Poll deleted.');
    }

    private function authorizePoll(Poll $poll): void
    {
        $user = Auth::user();
        abort_unless($user->isSuperadmin() || $poll->teacher_id === $user->id, 403, 'Unauthorized.');
    }
}
