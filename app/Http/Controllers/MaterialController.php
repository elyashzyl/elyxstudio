<?php

namespace App\Http\Controllers;

use App\Models\TeachingMaterial;
use App\Models\Quarter;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\SecureFileService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MaterialController extends Controller
{
    public function teacherIndex(): Response
    {
        /** @var \App\Models\User $teacher */
        $teacher = Auth::user();
        $query = TeachingMaterial::with('teacher:id,name', 'gradeLevel:id,name', 'subjectRecord:id,name', 'quarter:id,name,code', 'schoolYear:id,name')->latest();
        SchoolYear::applyFilter($query);

        if (! $teacher->isSuperadmin()) {
            $query->where(function ($q) use ($teacher) {
                $q->where('teacher_id', $teacher->id)
                    ->orWhereHas('teacher', fn ($t) => $t->where('role', 'superadmin'));
            });
        }

        return Inertia::render('Teacher/Materials', [
            'materials' => $this->mapMaterials($query->get()),
            'gradeLevels' => \App\Models\GradeLevel::where('is_active', true)->orderBy('display_order')->orderBy('name')->get(['id', 'name']),
            'allSections' => \App\Models\Section::where('is_active', true)->orderBy('name')->get(['id', 'name', 'grade_level_id']),
            'isSuperadmin' => $teacher->isSuperadmin(),
            'currentUserId' => $teacher->id,
            'teachers' => $teacher->isSuperadmin() ? User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']) : [],
            'subjects' => $this->availableSubjects($teacher),
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
            'schoolYears' => SchoolYear::where('is_active', true)->orderByDesc('is_current')->orderByDesc('name')->get(['id', 'name', 'is_current']),
            'selectedSchoolYearId' => SchoolYear::selectedFilter(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'description' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:pdf,ppt,pptx', 'max:20480'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        $file = $request->file('file');

        $fileData = null;
        $realPath = $file->getRealPath();
        if ($realPath && file_exists($realPath)) {
            $content = @file_get_contents($realPath);
            if ($content !== false) {
                $fileData = base64_encode($content);
            }
        }

        $path = SecureFileService::store($file, 'teaching-materials');

        if ($fileData === null) {
            $resolvedPath = SecureFileService::resolve($path);
            if ($resolvedPath && file_exists($resolvedPath)) {
                $content = @file_get_contents($resolvedPath);
                if ($content !== false) {
                    $fileData = base64_encode($content);
                }
            }
        }

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $teacherId = $currentUser->isSuperadmin() && !empty($data['teacher_id']) ? (int) $data['teacher_id'] : Auth::id();
        $subject = $this->resolveSubject($data['subject_id'] ?? null, $data['subject'] ?? null, $teacherId, $currentUser);

        $material = TeachingMaterial::create([
            'teacher_id' => $teacherId,
            'title' => $data['title'],
            'subject' => $subject['name'],
            'subject_id' => $subject['id'],
            'description' => $data['description'] ?? null,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'file_type' => strtolower($file->getClientOriginalExtension()),
            'grade_level_id' => $data['grade_level_id'] ?? null,
            'quarter_id' => $data['quarter_id'] ?? null,
            'school_year_id' => $data['school_year_id'] ?? SchoolYear::defaultIdForCreate(),
            'file_data' => $fileData,
        ]);

        \App\Services\ActivityLogger::log(
            'material_uploaded',
            "Uploaded teaching material '{$material->title}' ({$material->file_name})",
            $material
        );

        return redirect()->back()->with('success', 'Material uploaded.');
    }

    public function update(Request $request, TeachingMaterial $material): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin() || $material->teacher_id === $user->id, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:100'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'description' => ['nullable', 'string'],
            'grade_level_id' => ['nullable', 'integer', 'exists:grade_levels,id'],
            'quarter_id' => ['nullable', 'integer', 'exists:quarters,id'],
            'school_year_id' => ['nullable', 'integer', 'exists:school_years,id'],
            'teacher_id' => ['nullable', 'exists:users,id'],
            'file' => ['nullable', 'file', 'mimes:pdf,ppt,pptx', 'max:20480'],
        ]);

        $teacherId = $user->isSuperadmin() && ! empty($data['teacher_id']) ? (int) $data['teacher_id'] : $material->teacher_id;
        $subject = $this->resolveSubject($data['subject_id'] ?? null, $data['subject'] ?? null, $teacherId, $user);

        $updateData = [
            'title' => $data['title'],
            'subject' => $subject['name'],
            'subject_id' => $subject['id'],
            'description' => $data['description'] ?? null,
            'grade_level_id' => $data['grade_level_id'] ?? null,
            'quarter_id' => $data['quarter_id'] ?? null,
        ];

        if (array_key_exists('school_year_id', $data) && ! empty($data['school_year_id'])) {
            $updateData['school_year_id'] = (int) $data['school_year_id'];
        }

        if ($user->isSuperadmin() && ! empty($data['teacher_id'])) {
            $updateData['teacher_id'] = $data['teacher_id'];
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileData = null;
            $realPath = $file->getRealPath();
            if ($realPath && file_exists($realPath)) {
                $content = @file_get_contents($realPath);
                if ($content !== false) {
                    $fileData = base64_encode($content);
                }
            }

            SecureFileService::delete($material->file_path);

            $path = SecureFileService::store($file, 'teaching-materials');

            if ($fileData === null) {
                $resolvedPath = SecureFileService::resolve($path);
                if ($resolvedPath && file_exists($resolvedPath)) {
                    $content = @file_get_contents($resolvedPath);
                    if ($content !== false) {
                        $fileData = base64_encode($content);
                    }
                }
            }

            $updateData['file_path'] = $path;
            $updateData['file_name'] = $file->getClientOriginalName();
            $updateData['file_size'] = $file->getSize();
            $updateData['file_type'] = strtolower($file->getClientOriginalExtension());
            $updateData['file_data'] = $fileData;
        }

        $material->update($updateData);

        \App\Services\ActivityLogger::log(
            'material_updated',
            "Updated teaching material '{$material->title}'",
            $material,
            $user
        );

        return redirect()->back()->with('success', 'Material updated.');
    }

    public function destroy(TeachingMaterial $material): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin() || $material->teacher_id === $user->id, 403);

        SecureFileService::delete($material->file_path);
        $material->delete();

        return redirect()->route('teacher.materials.index')->with('success', 'Material deleted.');
    }

    public function reassign(Request $request, TeachingMaterial $material): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($user->isSuperadmin(), 403);

        $data = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
        ]);

        $material->update(['teacher_id' => $data['teacher_id']]);

        return back()->with('success', 'Material reassigned to teacher.');
    }

    public function generateQuiz(Request $request, TeachingMaterial $material): \Illuminate\Http\JsonResponse
    {
        @set_time_limit(120);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($this->canAccessMaterial($user, $material), 403);

        $data = $request->validate([
            'activity' => ['nullable', 'string', 'in:quiz,seatwork,practical,exam,game'],
            'count' => ['nullable', 'integer', 'min:1', 'max:50'],
            'types' => ['nullable', 'array'],
            'types.*' => ['in:multiple_choice,true_false,identification,enumeration,matching,essay'],
            'game_type' => ['nullable', 'string', 'in:' . \App\Enums\GameType::validationList()],
            'mode' => ['nullable', 'string', 'in:auto,extract,generate'],
        ]);

        $extracted = \App\Services\MaterialTextExtractor::extract($material);

        if (isset($extracted['error'])) {
            return response()->json(['ok' => false, 'message' => $extracted['error']], 422);
        }

        if (trim($extracted['text'] ?? '') === '') {
            return response()->json(['ok' => false, 'message' => 'No readable text found in this file.'], 422);
        }

        $activity = $data['activity'] ?? 'quiz';
        $count = (int) ($data['count'] ?? 5);
        $types = $data['types'] ?? [];

        if ($activity === 'game') {
            $result = \App\Services\QuizGeneratorService::generateFor(
                'game',
                $extracted['text'],
                $count,
                $types,
                $data['game_type'] ?? 'flashcard'
            );
            $items = $result['items'] ?? [];
            $provider = $result['provider'] ?? 'none';
            $title = '';
            $instructions = '';
            $timeLimit = null;
        } else {
            $docResult = \App\Services\QuizGeneratorService::parseOrGenerateDocument(
                $activity,
                $extracted['text'],
                $count,
                $types,
                $data['mode'] ?? 'generate'
            );
            $items = $docResult['items'] ?? [];
            $provider = $docResult['provider'] ?? 'none';
            $title = $docResult['title'] ?? '';
            $instructions = $docResult['instructions'] ?? '';
            $timeLimit = $docResult['time_limit_minutes'] ?? null;
        }

        if (empty($items)) {
            return response()->json(['ok' => false, 'message' => 'Could not generate content from this material. Try a file with more text.'], 422);
        }

        return response()->json([
            'ok' => true,
            'provider' => $provider,
            'activity' => $activity,
            'title' => $title,
            'instructions' => $instructions,
            'time_limit_minutes' => $timeLimit,
            'items' => $items,
            'questions' => in_array($activity, ['quiz', 'seatwork'], true) ? $items : [],
        ]);
    }

    public function preview(TeachingMaterial $material): BinaryFileResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($this->canAccessMaterial($user, $material), 403);

        if (request()->boolean('direct_stream') || request()->query('stream') === '1') {
            $streamUrl = SecureFileService::getStreamingUrl($material->file_path);
            if ($streamUrl) {
                return redirect()->away($streamUrl);
            }
        }

        $filePath = $material->getResolvedFilePath();

        if (! $filePath || ! file_exists($filePath)) {
            $streamUrl = SecureFileService::getStreamingUrl($material->file_path);
            if ($streamUrl) {
                return redirect()->away($streamUrl);
            }

            abort(404, 'The requested file could not be found on the server.');
        }

        $mimeType = match (strtolower($material->file_type)) {
            'pdf' => 'application/pdf',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            default => mime_content_type($filePath) ?: 'application/octet-stream',
        };

        $filename = SecureFileService::safeFilename(
            $material->file_name,
            'material.' . strtolower($material->file_type ?: 'file'),
        );

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => \Symfony\Component\HttpFoundation\HeaderUtils::makeDisposition('inline', $filename),
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function download(TeachingMaterial $material): BinaryFileResponse|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        abort_unless($this->canAccessMaterial($user, $material), 403);

        $filePath = $material->getResolvedFilePath();

        if (! $filePath || ! file_exists($filePath)) {
            return back()->with('error', 'The file could not be found on the server.');
        }

        \App\Services\ActivityLogger::log(
            'material_downloaded',
            "Downloaded teaching material '{$material->title}'",
            $material,
            $user
        );

        return response()->download(
            $filePath,
            SecureFileService::safeFilename(
                $material->file_name,
                'material.' . strtolower($material->file_type ?: 'file'),
            ),
            [
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    protected function canAccessMaterial(User $user, TeachingMaterial $material): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        if ($user->isStudent()) {
            return $material->grade_level_id === null
                || ($user->grade_level_id && $material->grade_level_id === $user->grade_level_id);
        }

        if ($user->isTeacher()) {
            return $material->teacher_id === $user->id
                || ($material->teacher && $material->teacher->role === 'superadmin');
        }

        return false;
    }

    protected function resolveFilePath(?string $relativePath, ?TeachingMaterial $material = null): ?string
    {
        if ($material) {
            return $material->getResolvedFilePath();
        }

        return SecureFileService::resolve($relativePath);
    }

    public function studentIndex(): Response
    {
        /** @var \App\Models\User $student */
        $student = Auth::user();

        $studentQuery = TeachingMaterial::with('teacher:id,name', 'gradeLevel:id,name', 'subjectRecord:id,name', 'quarter:id,name,code', 'schoolYear:id,name')
            ->latest()
            ->where(function ($q) use ($student) {
                $q->whereNull('grade_level_id');
                if ($student->grade_level_id) {
                    $q->orWhere('grade_level_id', $student->grade_level_id);
                }
            });
        SchoolYear::applyFilter($studentQuery, SchoolYear::currentId());
        $materials = $studentQuery->get();

        $readMap = \App\Models\TeachingMaterialRead::where('user_id', $student->id)
            ->pluck('read_at', 'teaching_material_id');

        $mapped = $materials->map(function (TeachingMaterial $m) use ($readMap) {
            $isRead = isset($readMap[$m->id]);
            return [
                'id' => $m->id,
                'title' => $m->title,
                'subject' => $m->subjectRecord?->name ?? $m->subject,
                'subject_id' => $m->subject_id,
                'description' => $m->description,
                'file_name' => $m->file_name,
                'file_type' => $m->file_type,
                'file_size' => $this->fileSize($m),
                'url' => route('materials.preview', ['material' => $m->id], false),
                'created_at' => $m->created_at?->toDateTimeString(),
                'teacher_id' => $m->teacher_id,
                'teacher_name' => $m->teacher?->name,
                'grade_level_id' => $m->grade_level_id,
                'grade_level_name' => $m->gradeLevel?->name,
                'quarter_id' => $m->quarter_id,
                'quarter_name' => $m->quarter?->name,
                'quarter_code' => $m->quarter?->code,
                'is_read' => $isRead,
                'read_at' => $isRead ? $readMap[$m->id]?->toIso8601String() : null,
            ];
        })->all();

        return Inertia::render('Student/Materials', [
            'materials' => $mapped,
            'quarters' => Quarter::where('is_active', true)->orderBy('display_order')->get(['id', 'name', 'code']),
        ]);
    }

    private function availableSubjects(User $user)
    {
        return Subject::query()
            ->where('is_active', true)
            ->when(! $user->isSuperadmin(), fn ($q) => $q->whereHas('teachers', fn ($t) => $t->where('users.id', $user->id)))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function resolveSubject(?int $subjectId, ?string $legacyName, int $teacherId, User $user): array
    {
        if ($subjectId) {
            $subject = Subject::whereKey($subjectId)->where('is_active', true)->firstOrFail();
            abort_unless(($teacherId === (int) $user->id && $user->isSuperadmin()) || $subject->teachers()->whereKey($teacherId)->exists(), 422, 'The selected subject is not assigned to this teacher.');
            return ['id' => $subject->id, 'name' => $subject->name];
        }

        $name = trim((string) $legacyName);
        if ($name === '') {
            $name = 'General';
        }
        $subject = Subject::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($subject) {
            if (! $subject->teachers()->whereKey($teacherId)->exists() && ! $user->isSuperadmin()) {
                $subject->teachers()->syncWithoutDetaching([$teacherId]);
            }
            return ['id' => $subject->id, 'name' => $subject->name];
        }

        $subject = Subject::create(['name' => $name, 'is_active' => true]);
        $subject->teachers()->syncWithoutDetaching([$teacherId]);
        return ['id' => $subject->id, 'name' => $subject->name];
    }

    private function fileSize(TeachingMaterial $material): ?int
    {
        $filePath = $material->getResolvedFilePath();
        if ($filePath && is_readable($filePath)) {
            $size = filesize($filePath);
            if ($size !== false) {
                return (int) $size;
            }
        }

        return $material->file_size !== null ? (int) $material->file_size : null;
    }

    private function mapMaterials($materials): array
    {
        return $materials->map(fn (TeachingMaterial $m) => [
            'id' => $m->id,
            'title' => $m->title,
            'subject' => $m->subjectRecord?->name ?? $m->subject,
            'subject_id' => $m->subject_id,
            'description' => $m->description,
            'file_name' => $m->file_name,
            'file_type' => $m->file_type,
            'file_size' => $this->fileSize($m),
            'url' => route('materials.preview', ['material' => $m->id], false),
            'created_at' => $m->created_at?->toDateTimeString(),
            'teacher_id' => $m->teacher_id,
            'teacher_name' => $m->teacher?->name,
            'grade_level_id' => $m->grade_level_id,
            'grade_level_name' => $m->gradeLevel?->name,
            'quarter_id' => $m->quarter_id,
            'quarter_name' => $m->quarter?->name,
            'quarter_code' => $m->quarter?->code,
            'school_year_id' => $m->school_year_id,
            'school_year_name' => $m->schoolYear?->name,
        ])->all();
    }
}