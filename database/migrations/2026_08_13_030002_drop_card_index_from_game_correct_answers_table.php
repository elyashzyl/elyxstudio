<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('game_correct_answers', 'card_index')) {
            try {
                Schema::table('game_correct_answers', function (Blueprint $table) {
                    $table->dropColumn('card_index');
                });
            } catch (Throwable) {
            }
        }
    }

    public function down(): void
    {
        Schema::table('game_correct_answers', function (Blueprint $table) {
            $table->unsignedInteger('card_index')->default(0);
        });
    }
};
