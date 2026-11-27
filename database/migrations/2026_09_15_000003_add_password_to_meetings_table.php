<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('meetings', 'password')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->string('password')->nullable()->after('room');
            });
        }
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
