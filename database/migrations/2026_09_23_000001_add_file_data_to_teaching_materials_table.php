<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teaching_materials') && ! Schema::hasColumn('teaching_materials', 'file_data')) {
            Schema::table('teaching_materials', function (Blueprint $table) {
                $table->longText('file_data')->nullable()->after('file_type');
            });
        }

        // Backfill file_data for any existing records whose file exists in storage or seed
        if (Schema::hasTable('teaching_materials')) {
            $materials = DB::table('teaching_materials')->whereNull('file_data')->get();
            foreach ($materials as $m) {
                $filename = basename(str_replace(['/', '\\'], '/', (string) $m->file_path));
                $candidates = [
                    storage_path('app/public/teaching-materials/' . $filename),
                    storage_path('app/private/teaching-materials/' . $filename),
                    database_path('seeders/data/teaching-materials/' . $filename),
                ];

                foreach ($candidates as $candidate) {
                    if (is_file($candidate)) {
                        $content = @file_get_contents($candidate);
                        if ($content !== false && $content !== '') {
                            DB::table('teaching_materials')
                                ->where('id', $m->id)
                                ->update(['file_data' => base64_encode($content)]);
                            break;
                        }
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('teaching_materials') && Schema::hasColumn('teaching_materials', 'file_data')) {
            Schema::table('teaching_materials', function (Blueprint $table) {
                $table->dropColumn('file_data');
            });
        }
    }
};
