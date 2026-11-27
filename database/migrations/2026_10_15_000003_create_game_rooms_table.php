<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('pin', 8)->unique();
            $table->string('status')->default('waiting'); // waiting, active, finished
            $table->unsignedInteger('current_card_index')->default(0);
            $table->json('state')->nullable();
            $table->timestamps();
        });

        Schema::create('game_room_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained('game_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('score')->default(0);
            $table->integer('streak')->default(0);
            $table->integer('last_answered_card_index')->nullable();
            $table->timestamp('last_answered_at')->nullable();
            $table->timestamps();

            $table->unique(['game_room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_room_participants');
        Schema::dropIfExists('game_rooms');
    }
};
