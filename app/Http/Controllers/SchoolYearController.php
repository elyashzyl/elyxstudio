<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\Game;
use App\Models\Practical;
use App\Models\Quiz;
use App\Models\SchoolYear;
use App\Models\Seatwork;
use App\Models\TeachingMaterial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = Auth::user();
        $isSuperadmin = $user->isSuperadmin();

        $schoolYears = SchoolYear::withCount([
            'teachingMaterials' => fn ($q) => $isSuperadmin ? $q : $q->where('teacher_id', $user->id),
            'games' => fn ($q) => $isSuperadmin ? $q : $q->where('teacher_id', $user->id),
            'quizzes' => fn ($q) => $isSuperadmin ? $q : $q->where('teacher_id', $user->id),
            'seatworks' => fn ($q) => $isSuperadmin ? $q : $q->where('teacher_id', $user->id),
            'practicals' => fn ($q) => $isSuperadmin ? $q : $q->where('teacher_id', $user->id),
            'exams' => fn ($q) => $isSuperadmin ? $q : $q->where('teacher_id', $user->id),
        ])
            ->orderByDesc('is_current')
            ->orderByDesc('name')
            ->get();

        $transferableItems = $this->getTransferableItems($user);

        return Inertia::render('Teacher/SchoolYears/Index', [
            'schoolYears' => $schoolYears,
            'transferableItems' => $transferableItems,
            'currentSchoolYearId' => SchoolYear::currentId(),
            'selectedSchoolYearId' => SchoolYear::selectedFilter(),
            'isSuperadmin' => $isSuperadmin,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:school_years,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $isCurrent = (bool) ($data['is_current'] ?? false);
        if ($isCurrent) {
            SchoolYear::query()->update(['is_current' => false]);
        }

        $schoolYear = SchoolYear::create([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_current' => $isCurrent,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if ($isCurrent) {
            session(['selected_school_year_id' => $schoolYear->id]);
        }

        return redirect()->route('teacher.school-years.index')
            ->with('success', "School Year {$schoolYear->name} created successfully.");
    }

    public function update(Request $request, SchoolYear $schoolYear): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:school_years,name,' . $schoolYear->id],
            'description' => ['nullable', 'string', 'max:500'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $isCurrent = (bool) ($data['is_current'] ?? $schoolYear->is_current);
        if ($isCurrent) {
            SchoolYear::where('id', '!=', $schoolYear->id)->update(['is_current' => false]);
            session(['selected_school_year_id' => $schoolYear->id]);
        }

        $schoolYear->update([
            'name' => trim($data['name']),
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_current' => $isCurrent,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return redirect()->route('teacher.school-years.index')
            ->with('success', "School Year {$schoolYear->name} updated successfully.");
    }

    public function setCurrent(SchoolYear $schoolYear): RedirectResponse
    {
        SchoolYear::where('id', '!=', $schoolYear->id)->update(['is_current' => false]);
        $schoolYear->update([
            'is_current' => true,
            'is_active' => true,
        ]);

        session(['selected_school_year_id' => $schoolYear->id]);

        return back()->with('success', "School Year {$schoolYear->name} is now set as the active school year.");
    }

    public function switchView(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_year_id' => ['required'],
        ]);

        $val = $data['school_year_id'];
        if ($val === 'all') {
            session(['selected_school_year_id' => 'all']);

            return back()->with('success', 'Now viewing all school years.');
        }

        $schoolYear = SchoolYear::findOrFail((int) $val);
        session(['selected_school_year_id' => $schoolYear->id]);

        return back()->with('success', "Switched view to School Year {$schoolYear->name}.");
    }

    public function transferContent(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $isSuperadmin = $user->isSuperadmin();

        $data = $request->validate([
            'mode' => ['required', 'string', 'in:all,selected'],
            'source_school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'target_school_year_id' => ['required', 'integer', 'exists:school_years,id'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'in:materials,games,quizzes,seatworks,practicals,exams'],
            'items' => ['nullable', 'array'],
            'items.*.type' => ['required_with:items', 'string', 'in:material,game,quiz,seatwork,practical,exam'],
            'items.*.id' => ['required_with:items', 'integer'],
        ]);

        $targetSchoolYear = SchoolYear::findOrFail((int) $data['target_school_year_id']);
        $movedCount = 0;

        $scopeOwner = function ($query) use ($user, $isSuperadmin) {
            if (! $isSuperadmin) {
                $query->where('teacher_id', $user->id);
            }

            return $query;
        };

        if ($data['mode'] === 'all') {
            $request->validate([
                'source_school_year_id' => ['required', 'integer', 'exists:school_years,id', 'different:target_school_year_id'],
            ]);

            $sourceId = (int) $data['source_school_year_id'];
            $categories = ! empty($data['categories'])
                ? $data['categories']
                : ['materials', 'games', 'quizzes', 'seatworks', 'practicals', 'exams'];

            $categoryMap = [
                'materials' => TeachingMaterial::class,
                'games' => Game::class,
                'quizzes' => Quiz::class,
                'seatworks' => Seatwork::class,
                'practicals' => Practical::class,
                'exams' => Exam::class,
            ];

            foreach ($categories as $cat) {
                if (! isset($categoryMap[$cat])) {
                    continue;
                }
                $modelClass = $categoryMap[$cat];
                $query = $scopeOwner($modelClass::where('school_year_id', $sourceId));
                $movedCount += $query->update(['school_year_id' => $targetSchoolYear->id]);
            }
        } else {
            $items = collect($data['items'] ?? []);
            $typeMap = [
                'material' => TeachingMaterial::class,
                'game' => Game::class,
                'quiz' => Quiz::class,
                'seatwork' => Seatwork::class,
                'practical' => Practical::class,
                'exam' => Exam::class,
            ];

            foreach ($items->groupBy('type') as $type => $group) {
                if (! isset($typeMap[$type])) {
                    continue;
                }
                $ids = $group->pluck('id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
                if (empty($ids)) {
                    continue;
                }
                $modelClass = $typeMap[$type];
                $query = $scopeOwner($modelClass::whereIn('id', $ids));
                $movedCount += $query->update(['school_year_id' => $targetSchoolYear->id]);
            }
        }

        return back()->with('success', "Moved {$movedCount} item(s) to School Year {$targetSchoolYear->name}.");
    }

    public function destroy(SchoolYear $schoolYear): RedirectResponse
    {
        if ($schoolYear->is_current && SchoolYear::count() > 1) {
            $fallback = SchoolYear::where('id', '!=', $schoolYear->id)->orderByDesc('name')->first();
            if ($fallback) {
                $fallback->update(['is_current' => true]);
            }
        }

        $schoolYear->delete();

        return redirect()->route('teacher.school-years.index')
            ->with('success', 'School Year deleted successfully.');
    }

    private function getTransferableItems(User $user): array
    {
        $isSuperadmin = $user->isSuperadmin();
        $scope = fn ($q) => $isSuperadmin ? $q : $q->where('teacher_id', $user->id);

        $items = [];

        $materials = $scope(TeachingMaterial::with('teacher:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest())
            ->get(['id', 'title', 'subject', 'file_type', 'teacher_id', 'quarter_id', 'school_year_id']);
        foreach ($materials as $m) {
            $items[] = [
                'key' => 'material-' . $m->id,
                'id' => $m->id,
                'type' => 'material',
                'type_label' => 'Material',
                'title' => $m->title,
                'meta' => trim(($m->subject ?: 'General') . ' · ' . strtoupper((string) $m->file_type)),
                'quarter_name' => $m->quarter?->code ?: $m->quarter?->name,
                'teacher_name' => $m->teacher?->name,
                'school_year_id' => $m->school_year_id,
                'school_year_name' => $m->schoolYear?->name ?? 'Unassigned',
            ];
        }

        $games = $scope(Game::with('teacher:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest())
            ->get(['id', 'title', 'subject', 'type', 'teacher_id', 'quarter_id', 'school_year_id']);
        foreach ($games as $g) {
            $items[] = [
                'key' => 'game-' . $g->id,
                'id' => $g->id,
                'type' => 'game',
                'type_label' => 'Game',
                'title' => $g->title,
                'meta' => trim(($g->subject ?: 'General') . ' · ' . ucfirst((string) $g->type)),
                'quarter_name' => $g->quarter?->code ?: $g->quarter?->name,
                'teacher_name' => $g->teacher?->name,
                'school_year_id' => $g->school_year_id,
                'school_year_name' => $g->schoolYear?->name ?? 'Unassigned',
            ];
        }

        $quizzes = $scope(Quiz::with('teacher:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest())
            ->get(['id', 'title', 'grade', 'teacher_id', 'quarter_id', 'school_year_id']);
        foreach ($quizzes as $q) {
            $items[] = [
                'key' => 'quiz-' . $q->id,
                'id' => $q->id,
                'type' => 'quiz',
                'type_label' => 'Quiz',
                'title' => $q->title,
                'meta' => $q->grade ?: 'All Grades',
                'quarter_name' => $q->quarter?->code ?: $q->quarter?->name,
                'teacher_name' => $q->teacher?->name,
                'school_year_id' => $q->school_year_id,
                'school_year_name' => $q->schoolYear?->name ?? 'Unassigned',
            ];
        }

        $seatworks = $scope(Seatwork::with('teacher:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest())
            ->get(['id', 'title', 'grade', 'teacher_id', 'quarter_id', 'school_year_id']);
        foreach ($seatworks as $s) {
            $items[] = [
                'key' => 'seatwork-' . $s->id,
                'id' => $s->id,
                'type' => 'seatwork',
                'type_label' => 'Seatwork',
                'title' => $s->title,
                'meta' => $s->grade ?: 'All Grades',
                'quarter_name' => $s->quarter?->code ?: $s->quarter?->name,
                'teacher_name' => $s->teacher?->name,
                'school_year_id' => $s->school_year_id,
                'school_year_name' => $s->schoolYear?->name ?? 'Unassigned',
            ];
        }

        $practicals = $scope(Practical::with('teacher:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest())
            ->get(['id', 'title', 'grade', 'teacher_id', 'quarter_id', 'school_year_id']);
        foreach ($practicals as $p) {
            $items[] = [
                'key' => 'practical-' . $p->id,
                'id' => $p->id,
                'type' => 'practical',
                'type_label' => 'Practical',
                'title' => $p->title,
                'meta' => $p->grade ?: 'All Grades',
                'quarter_name' => $p->quarter?->code ?: $p->quarter?->name,
                'teacher_name' => $p->teacher?->name,
                'school_year_id' => $p->school_year_id,
                'school_year_name' => $p->schoolYear?->name ?? 'Unassigned',
            ];
        }

        $exams = $scope(Exam::with('teacher:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest())
            ->get(['id', 'title', 'grade', 'teacher_id', 'quarter_id', 'school_year_id']);
        foreach ($exams as $e) {
            $items[] = [
                'key' => 'exam-' . $e->id,
                'id' => $e->id,
                'type' => 'exam',
                'type_label' => 'Exam',
                'title' => $e->title,
                'meta' => $e->grade ?: 'All Grades',
                'quarter_name' => $e->quarter?->code ?: $e->quarter?->name,
                'teacher_name' => $e->teacher?->name,
                'school_year_id' => $e->school_year_id,
                'school_year_name' => $e->schoolYear?->name ?? 'Unassigned',
            ];
        }

        return $items;
    }
}
