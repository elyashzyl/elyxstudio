<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quarters')) {
            Schema::create('quarters', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('code', 20)->nullable()->unique();
                $table->text('description')->nullable();
                $table->integer('display_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();
            });
        }

        // Seed standard four quarters if empty
        if (DB::table('quarters')->count() === 0) {
            $now = now();
            DB::table('quarters')->insert([
                [
                    'name' => '1st Quarter',
                    'code' => 'Q1',
                    'description' => 'First academic quarter and grading period.',
                    'display_order' => 1,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => '2nd Quarter',
                    'code' => 'Q2',
                    'description' => 'Second academic quarter and grading period.',
                    'display_order' => 2,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => '3rd Quarter',
                    'code' => 'Q3',
                    'description' => 'Third academic quarter and grading period.',
                    'display_order' => 3,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => '4th Quarter',
                    'code' => 'Q4',
                    'description' => 'Fourth academic quarter and grading period.',
                    'display_order' => 4,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        // Add nullable quarter_id to teaching_materials
        if (! Schema::hasColumn('teaching_materials', 'quarter_id')) {
            Schema::table('teaching_materials', function (Blueprint $table) {
                $table->foreignId('quarter_id')->nullable()->constrained('quarters')->nullOnDelete();
            });
        }

        // Add nullable quarter_id to games
        if (! Schema::hasColumn('games', 'quarter_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->foreignId('quarter_id')->nullable()->constrained('quarters')->nullOnDelete();
            });
        }

        // Add nullable quarter_id to quizzes
        if (! Schema::hasColumn('quizzes', 'quarter_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->foreignId('quarter_id')->nullable()->constrained('quarters')->nullOnDelete();
            });
        }

        // Add nullable quarter_id to seatworks
        if (! Schema::hasColumn('seatworks', 'quarter_id')) {
            Schema::table('seatworks', function (Blueprint $table) {
                $table->foreignId('quarter_id')->nullable()->constrained('quarters')->nullOnDelete();
            });
        }

        // Add nullable quarter_id to practicals
        if (! Schema::hasColumn('practicals', 'quarter_id')) {
            Schema::table('practicals', function (Blueprint $table) {
                $table->foreignId('quarter_id')->nullable()->constrained('quarters')->nullOnDelete();
            });
        }

        // Add nullable quarter_id to exams
        if (! Schema::hasColumn('exams', 'quarter_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->foreignId('quarter_id')->nullable()->constrained('quarters')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('exams', 'quarter_id')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->dropForeign(['quarter_id']);
                $table->dropColumn('quarter_id');
            });
        }

        if (Schema::hasColumn('practicals', 'quarter_id')) {
            Schema::table('practicals', function (Blueprint $table) {
                $table->dropForeign(['quarter_id']);
                $table->dropColumn('quarter_id');
            });
        }

        if (Schema::hasColumn('seatworks', 'quarter_id')) {
            Schema::table('seatworks', function (Blueprint $table) {
                $table->dropForeign(['quarter_id']);
                $table->dropColumn('quarter_id');
            });
        }

        if (Schema::hasColumn('quizzes', 'quarter_id')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropForeign(['quarter_id']);
                $table->dropColumn('quarter_id');
            });
        }

        if (Schema::hasColumn('games', 'quarter_id')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropForeign(['quarter_id']);
                $table->dropColumn('quarter_id');
            });
        }

        if (Schema::hasColumn('teaching_materials', 'quarter_id')) {
            Schema::table('teaching_materials', function (Blueprint $table) {
                $table->dropForeign(['quarter_id']);
                $table->dropColumn('quarter_id');
            });
        }

        Schema::dropIfExists('quarters');
    }
};
