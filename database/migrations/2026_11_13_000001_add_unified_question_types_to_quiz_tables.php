<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_matching_pairs')) {
            Schema::create('quiz_matching_pairs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
                $table->string('left_text');
                $table->string('right_text');
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('questions') && ! Schema::hasColumn('questions', 'criteria')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->text('criteria')->nullable()->after('points');
            });
        }

        if (Schema::hasTable('quiz_answers')) {
            Schema::table('quiz_answers', function (Blueprint $table) {
                if (! Schema::hasColumn('quiz_answers', 'matching_answers')) {
                    $table->json('matching_answers')->nullable()->after('answer_text');
                }
                if (! Schema::hasColumn('quiz_answers', 'feedback')) {
                    $table->text('feedback')->nullable()->after('is_correct');
                }
                if (! Schema::hasColumn('quiz_answers', 'points_awarded')) {
                    $table->integer('points_awarded')->nullable()->after('feedback');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_matching_pairs');

        if (Schema::hasTable('questions') && Schema::hasColumn('questions', 'criteria')) {
            Schema::table('questions', function (Blueprint $table) {
                $table->dropColumn('criteria');
            });
        }

        if (Schema::hasTable('quiz_answers')) {
            Schema::table('quiz_answers', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('quiz_answers', 'matching_answers')) $cols[] = 'matching_answers';
                if (Schema::hasColumn('quiz_answers', 'feedback')) $cols[] = 'feedback';
                if (Schema::hasColumn('quiz_answers', 'points_awarded')) $cols[] = 'points_awarded';
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
