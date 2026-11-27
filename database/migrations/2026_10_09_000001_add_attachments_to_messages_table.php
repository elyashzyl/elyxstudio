<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (! Schema::hasColumn('messages', 'attachment_path')) {
                    $table->string('attachment_path')->nullable()->after('body');
                }
                if (! Schema::hasColumn('messages', 'attachment_name')) {
                    $table->string('attachment_name')->nullable()->after('attachment_path');
                }
                if (! Schema::hasColumn('messages', 'attachment_type')) {
                    $table->string('attachment_type')->nullable()->after('attachment_name');
                }
                if (! Schema::hasColumn('messages', 'attachment_size')) {
                    $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_type');
                }
                if (! Schema::hasColumn('messages', 'attachment_data')) {
                    $table->longText('attachment_data')->nullable()->after('attachment_size');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                $columns = array_filter(
                    ['attachment_path', 'attachment_name', 'attachment_type', 'attachment_size', 'attachment_data'],
                    fn ($col) => Schema::hasColumn('messages', $col)
                );
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
