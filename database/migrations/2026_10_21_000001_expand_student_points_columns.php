<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_points', function (Blueprint $table) {
            $table->unsignedInteger('points')->change();
            $table->unsignedInteger('score')->nullable()->change();
            $table->unsignedInteger('total')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_points', function (Blueprint $table) {
            $table->smallInteger('points')->unsigned()->change();
            $table->smallInteger('score')->unsigned()->nullable()->change();
            $table->smallInteger('total')->unsigned()->nullable()->change();
        });
    }
};
