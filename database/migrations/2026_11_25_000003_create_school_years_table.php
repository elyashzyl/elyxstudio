<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_years')) {
            Schema::create('school_years', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->text('description')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_current')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        $now = now();
        $defaultSchoolYear = DB::table('school_years')->where('name', '2026-2027')->first();

        if (! $defaultSchoolYear) {
            $hasAnyCurrent = DB::table('school_years')->where('is_current', true)->exists();
            $defaultId = DB::table('school_years')->insertGetId([
                'name' => '2026-2027',
                'description' => 'Academic School Year 2026-2027',
                'start_date' => '2026-06-01',
                'end_date' => '2027-05-31',
                'is_current' => ! $hasAnyCurrent,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $defaultId = $defaultSchoolYear->id;
        }

        $tables = [
            'teaching_materials',
            'games',
            'quizzes',
            'seatworks',
            'practicals',
            'exams',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'school_year_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('school_year_id')->nullable()->constrained('school_years')->nullOnDelete();
                });
            }

            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'school_year_id')) {
                DB::table($tableName)
                    ->whereNull('school_year_id')
                    ->update(['school_year_id' => $defaultId]);
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'exams',
            'practicals',
            'seatworks',
            'quizzes',
            'games',
            'teaching_materials',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'school_year_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['school_year_id']);
                    $table->dropColumn('school_year_id');
                });
            }
        }

        Schema::dropIfExists('school_years');
    }
};
