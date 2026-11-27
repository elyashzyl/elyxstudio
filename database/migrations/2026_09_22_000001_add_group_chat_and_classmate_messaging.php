<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'allow_classmate_messaging')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('allow_classmate_messaging')->default(true)->after('messenger_enabled');
            });
        }

        if (! Schema::hasColumn('conversations', 'is_group')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->boolean('is_group')->default(false)->after('subject');
            });
        }

        if (! Schema::hasColumn('conversations', 'created_by')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('is_group');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'allow_classmate_messaging')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('allow_classmate_messaging');
            });
        }

        if (Schema::hasColumn('conversations', 'created_by')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }

        if (Schema::hasColumn('conversations', 'is_group')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropColumn('is_group');
            });
        }
    }
};
