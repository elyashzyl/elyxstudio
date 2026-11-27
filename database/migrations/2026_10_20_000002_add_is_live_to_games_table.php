<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('games', 'is_live')) {
            Schema::table('games', function (Blueprint $table) {
                $table->boolean('is_live')->default(false)->after('hidden');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'is_live')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropColumn('is_live');
            });
        }
    }
};
