<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('games', 'auto_reopen')) {
            Schema::table('games', function (Blueprint $table) {
                $table->boolean('auto_reopen')->default(false)->after('is_live');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'auto_reopen')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropColumn('auto_reopen');
            });
        }
    }
};
