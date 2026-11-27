<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('seatworks')) {
            Schema::table('seatworks', function (Blueprint $table) {
                if (! Schema::hasColumn('seatworks', 'max_attempts')) {
                    $table->integer('max_attempts')->default(1)->nullable()->after('time_limit_minutes');
                }
                if (! Schema::hasColumn('seatworks', 'grading_policy')) {
                    $table->string('grading_policy', 32)->default('first')->after('max_attempts');
                }
            });
        }

        if (Schema::hasTable('seatwork_attempts')) {
            Schema::table('seatwork_attempts', function (Blueprint $table) {
                if (! Schema::hasColumn('seatwork_attempts', 'attempt_number')) {
                    $table->integer('attempt_number')->default(1)->after('student_id');
                }
                if (! Schema::hasColumn('seatwork_attempts', 'is_recorded')) {
                    $table->boolean('is_recorded')->default(true)->after('total_points');
                }
            });

            // Backfill existing attempts so each is recorded attempt #1
            DB::table('seatwork_attempts')
                ->whereNull('attempt_number')
                ->orWhere('attempt_number', 0)
                ->update(['attempt_number' => 1]);

            DB::table('seatwork_attempts')
                ->whereNull('is_recorded')
                ->update(['is_recorded' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('seatwork_attempts')) {
            Schema::table('seatwork_attempts', function (Blueprint $table) {
                if (Schema::hasColumn('seatwork_attempts', 'is_recorded')) {
                    $table->dropColumn('is_recorded');
                }
                if (Schema::hasColumn('seatwork_attempts', 'attempt_number')) {
                    $table->dropColumn('attempt_number');
                }
            });
        }

        if (Schema::hasTable('seatworks')) {
            Schema::table('seatworks', function (Blueprint $table) {
                if (Schema::hasColumn('seatworks', 'grading_policy')) {
                    $table->dropColumn('grading_policy');
                }
                if (Schema::hasColumn('seatworks', 'max_attempts')) {
                    $table->dropColumn('max_attempts');
                }
            });
        }
    }
};
