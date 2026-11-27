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
        if (Schema::hasTable('grade10_profiles')) {
            Schema::table('grade10_profiles', function (Blueprint $table) {
                if (! Schema::hasColumn('grade10_profiles', 'photo_path')) {
                    $table->string('photo_path')->nullable()->after('social_media');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('grade10_profiles') && Schema::hasColumn('grade10_profiles', 'photo_path')) {
            Schema::table('grade10_profiles', function (Blueprint $table) {
                $table->dropColumn('photo_path');
            });
        }
    }
};
