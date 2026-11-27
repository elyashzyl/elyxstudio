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
        if (! Schema::hasTable('class_todos')) {
            Schema::create('class_todos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('category')->default('general');
                $table->foreignId('target_grade_level_id')->nullable()->constrained('grade_levels')->nullOnDelete();
                $table->foreignId('target_section_id')->nullable()->constrained('sections')->nullOnDelete();
                $table->timestamp('due_date')->nullable();
                $table->string('link_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('class_todo_completions')) {
            Schema::create('class_todo_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_todo_id')->constrained('class_todos')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('completed_at')->useCurrent();
                $table->timestamps();

                $table->unique(['class_todo_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('teaching_material_reads')) {
            Schema::create('teaching_material_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('teaching_material_id')->constrained('teaching_materials')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('read_at')->useCurrent();
                $table->timestamps();

                $table->unique(['teaching_material_id', 'user_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_material_reads');
        Schema::dropIfExists('class_todo_completions');
        Schema::dropIfExists('class_todos');
    }
};
