<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seatwork_questions') && ! Schema::hasColumn('seatwork_questions', 'criteria')) {
            Schema::table('seatwork_questions', function (Blueprint $table) {
                $table->text('criteria')->nullable();
            });
        }

        if (Schema::hasTable('seatwork_answers')) {
            Schema::table('seatwork_answers', function (Blueprint $table) {
                if (! Schema::hasColumn('seatwork_answers', 'feedback')) {
                    $table->text('feedback')->nullable();
                }
                if (! Schema::hasColumn('seatwork_answers', 'points_awarded')) {
                    $table->integer('points_awarded')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seatwork_questions') && Schema::hasColumn('seatwork_questions', 'criteria')) {
            Schema::table('seatwork_questions', function (Blueprint $table) {
                $table->dropColumn('criteria');
            });
        }

        if (Schema::hasTable('seatwork_answers')) {
            Schema::table('seatwork_answers', function (Blueprint $table) {
                if (Schema::hasColumn('seatwork_answers', 'feedback')) {
                    $table->dropColumn('feedback');
                }
                if (Schema::hasColumn('seatwork_answers', 'points_awarded')) {
                    $table->dropColumn('points_awarded');
                }
            });
        }
    }
};
