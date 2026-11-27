<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(): Response
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        return Inertia::render('Teacher/Subjects', [
            'subjects' => Subject::with(['teachers:id,name'])
                ->withCount(['games', 'materials'])
                ->orderBy('name')
                ->get(),
            'teachers' => User::where('role', 'teacher')
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $data = $request->validate([
            'name' => [
                            'required',
                            'string',
                            'max:100',
                            Rule::unique('subjects', 'name')->where(fn ($query) => $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $request->input('name')))])),
                        ],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $subject = Subject::create([
            'name' => trim($data['name']),
            'is_active' => true,
        ]);
        $subject->teachers()->sync($this->teacherIds($data['teacher_ids'] ?? []));

        return back()->with('success', 'Subject created and assigned.');
    }

    public function update(Request $request, Subject $subject): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $data = $request->validate([
            'name' => [
                            'required',
                            'string',
                            'max:100',
                            Rule::unique('subjects', 'name')->ignore($subject->id)->where(fn ($query) => $query->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $request->input('name')))])),
                        ],
            'is_active' => ['required', 'boolean'],
            'teacher_ids' => ['nullable', 'array'],
            'teacher_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $subject->update([
            'name' => trim($data['name']),
            'is_active' => (bool) $data['is_active'],
        ]);
        $subject->teachers()->sync($this->teacherIds($data['teacher_ids'] ?? []));

        return back()->with('success', 'Subject updated.');
    }

    public function destroy(Subject $subject): RedirectResponse
    {
        abort_unless(auth()->user()->isSuperadmin(), 403);

        $subject->delete();

        return back()->with('success', 'Subject deleted. Existing materials and games were kept.');
    }

    private function teacherIds(array $ids): array
    {
        return User::where('role', 'teacher')
            ->whereIn('id', array_map('intval', $ids))
            ->pluck('id')
            ->all();
    }
}
