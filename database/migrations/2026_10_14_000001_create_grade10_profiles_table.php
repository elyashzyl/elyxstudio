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
        if (! Schema::hasTable('grade10_profiles')) {
            Schema::create('grade10_profiles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->string('full_name');
                $table->string('chinese_name')->nullable();
                $table->string('birthday');
                $table->string('hometown');
                $table->string('ambition');
                $table->text('motto');
                $table->text('message');
                $table->string('social_media')->nullable();
                $table->string('photo_path')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grade10_profiles');
    }
};
