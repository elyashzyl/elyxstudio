<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Ensure user_id has an index so MySQL does not block dropping the composite unique index (Error 1553)
        try {
            Schema::table('game_correct_answers', function (Blueprint $table) {
                $table->index('user_id');
            });
        } catch (Throwable) {
        }

        // 2. Drop the old composite unique index
        try {
            Schema::table('game_correct_answers', function (Blueprint $table) {
                $table->dropUnique(['user_id', 'game_id', 'card_index']);
            });
        } catch (Throwable) {
        }

        // 3. Add the new card_id column
        if (! Schema::hasColumn('game_correct_answers', 'card_id')) {
            Schema::table('game_correct_answers', function (Blueprint $table) {
                $table->unsignedBigInteger('card_id')->nullable()->after('game_id');
            });
        }

        // 4. Add the new unique index
        try {
            Schema::table('game_correct_answers', function (Blueprint $table) {
                $table->unique(['user_id', 'game_id', 'card_id']);
            });
        } catch (Throwable) {
        }
    }

    public function down(): void
    {
        Schema::table('game_correct_answers', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'game_id', 'card_id']);
            $table->dropColumn('card_id');
            $table->unique(['user_id', 'game_id', 'card_index']);
        });
    }
};
