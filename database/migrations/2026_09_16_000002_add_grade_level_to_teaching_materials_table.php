<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('teaching_materials', 'grade_level_id')) {
            Schema::table('teaching_materials', function (Blueprint $table) {
                $table->foreignId('grade_level_id')->nullable()->after('subject')->constrained('grade_levels')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('teaching_materials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('grade_level_id');
        });
    }
};
