<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('student_teacher')) {
            Schema::create('student_teacher', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['student_id', 'teacher_id']);
            });
        }

        if (Schema::hasColumn('users', 'teacher_id')) {
            $now = now();
            DB::table('users')
                ->whereNotNull('teacher_id')
                ->select('id as student_id', 'teacher_id')
                ->get()
                ->each(function ($user) use ($now) {
                    DB::table('student_teacher')->insertOrIgnore([
                        'student_id' => $user->student_id,
                        'teacher_id' => $user->teacher_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_teacher');
    }
};
