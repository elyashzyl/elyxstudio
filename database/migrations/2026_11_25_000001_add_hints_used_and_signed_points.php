<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_progress') && ! Schema::hasColumn('game_progress', 'hints_used')) {
            Schema::table('game_progress', function (Blueprint $table) {
                $table->unsignedInteger('hints_used')->default(0)->after('completed');
            });
        }

        if (Schema::hasTable('student_points')) {
            Schema::table('student_points', function (Blueprint $table) {
                $table->integer('points')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('game_progress') && Schema::hasColumn('game_progress', 'hints_used')) {
            Schema::table('game_progress', function (Blueprint $table) {
                $table->dropColumn('hints_used');
            });
        }

        if (Schema::hasTable('student_points')) {
            Schema::table('student_points', function (Blueprint $table) {
                $table->unsignedInteger('points')->change();
            });
        }
    }
};
