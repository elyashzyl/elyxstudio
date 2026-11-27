<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->text('content');
                $table->string('category')->default('general');
                $table->string('badge_color')->nullable();
                $table->string('target_role')->nullable();
                $table->foreignId('target_grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
                $table->foreignId('target_section_id')->nullable()->constrained('sections')->nullOnDelete();
                $table->boolean('is_pinned')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
