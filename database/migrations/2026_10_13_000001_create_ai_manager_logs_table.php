<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_manager_logs', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->string('status', 50);
            $table->string('target_type', 150)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->text('summary');
            $table->json('payload')->nullable();
            $table->timestamp('reverted_at')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_manager_logs');
    }
};
