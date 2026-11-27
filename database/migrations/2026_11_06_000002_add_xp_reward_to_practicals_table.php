<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('practicals', 'xp_reward')) {
            Schema::table('practicals', function (Blueprint $table) {
                $table->unsignedInteger('xp_reward')->default(10)->after('max_score');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('practicals', 'xp_reward')) {
            Schema::table('practicals', function (Blueprint $table) {
                $table->dropColumn('xp_reward');
            });
        }
    }
};
