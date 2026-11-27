<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subject_teacher')) {
            Schema::create('subject_teacher', function (Blueprint $table) {
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['subject_id', 'teacher_id']);
            });
        }

        if (! Schema::hasColumn('games', 'subject_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->after('subject')->constrained('subjects')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('teaching_materials', 'subject_id')) {
            Schema::table('teaching_materials', function (Blueprint $table) {
                $table->foreignId('subject_id')->nullable()->after('subject')->constrained('subjects')->nullOnDelete();
            });
        }

        $subjectIds = [];
        $legacySubjects = DB::table('games')
            ->whereNotNull('subject')
            ->where('subject', '<>', '')
            ->pluck('subject')
            ->merge(
                DB::table('teaching_materials')
                    ->whereNotNull('subject')
                    ->where('subject', '<>', '')
                    ->pluck('subject')
            )
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        foreach ($legacySubjects as $name) {
            $subject = DB::table('subjects')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if (! $subject) {
                $subjectId = DB::table('subjects')->insertGetId([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $subjectId = $subject->id;
            }

            $subjectIds[mb_strtolower($name)] = $subjectId;
        }

        foreach (DB::table('games')->whereNotNull('subject')->where('subject', '<>', '')->get(['id', 'subject', 'teacher_id']) as $game) {
            $subjectId = $subjectIds[mb_strtolower(trim((string) $game->subject))] ?? null;
            if ($subjectId) {
                DB::table('games')->where('id', $game->id)->update(['subject_id' => $subjectId]);
                DB::table('subject_teacher')->updateOrInsert(
                    ['subject_id' => $subjectId, 'teacher_id' => $game->teacher_id],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        foreach (DB::table('teaching_materials')->whereNotNull('subject')->where('subject', '<>', '')->get(['id', 'subject', 'teacher_id']) as $material) {
            $subjectId = $subjectIds[mb_strtolower(trim((string) $material->subject))] ?? null;
            if ($subjectId) {
                DB::table('teaching_materials')->where('id', $material->id)->update(['subject_id' => $subjectId]);
                DB::table('subject_teacher')->updateOrInsert(
                    ['subject_id' => $subjectId, 'teacher_id' => $material->teacher_id],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('teaching_materials', 'subject_id')) {
            Schema::table('teaching_materials', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
                $table->dropColumn('subject_id');
            });
        }

        if (Schema::hasColumn('games', 'subject_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropForeign(['subject_id']);
                $table->dropColumn('subject_id');
            });
        }

        Schema::dropIfExists('subject_teacher');
        Schema::dropIfExists('subjects');
    }
};
