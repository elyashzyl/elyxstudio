<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('exam_questions') && ! Schema::hasColumn('exam_questions', 'criteria')) {
            Schema::table('exam_questions', function (Blueprint $table) {
                $table->text('criteria')->nullable()->after('points');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('exam_questions') && Schema::hasColumn('exam_questions', 'criteria')) {
            Schema::table('exam_questions', function (Blueprint $table) {
                $table->dropColumn('criteria');
            });
        }
    }
};
