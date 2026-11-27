<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'streak_freeze_count')) {
                $table->unsignedInteger('streak_freeze_count')->default(2)->after('last_login_at');
            }
            if (!Schema::hasColumn('users', 'streak_freeze_active')) {
                $table->boolean('streak_freeze_active')->default(false)->after('streak_freeze_count');
            }
        });

        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('question');
            $table->json('options');
            $table->foreignId('target_grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
            $table->foreignId('target_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('polls')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('option_index');
            $table->timestamps();

            $table->unique(['poll_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poll_votes');
        Schema::dropIfExists('polls');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'streak_freeze_active')) {
                $table->dropColumn('streak_freeze_active');
            }
            if (Schema::hasColumn('users', 'streak_freeze_count')) {
                $table->dropColumn('streak_freeze_count');
            }
        });
    }
};
