<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;

class EduPulseDatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Tables in dependency order (parents before children).
     */
    protected array $tables = [
        'grade_levels',
        'sections',
        'users',
        'student_teacher',
        'settings',
        'quizzes',
        'quiz_grade_level',
        'quiz_section',
        'questions',
        'quiz_options',
        'quiz_attempts',
        'quiz_answers',
        'seatworks',
        'seatwork_grade_level',
        'seatwork_questions',
        'seatwork_options',
        'seatwork_attempts',
        'practicals',
        'practical_grade_level',
        'practical_criteria',
        'practical_attempts',
        'practical_scores',
        'teaching_materials',
        'games',
        'game_cards',
        'game_progress',
        'game_correct_answers',
        'conversations',
        'conversation_participants',
        'messages',
        'student_points',
        'reward_redemptions',
    ];

    public function run(): void
    {
        $sourceSqlite = env('EDUPULSE_SEED_DB', database_path('seed-source.sqlite'));
        $pdo = null;

        if (is_string($sourceSqlite) && $sourceSqlite !== '' && file_exists($sourceSqlite)) {
            try {
                $pdo = new PDO("sqlite:{$sourceSqlite}");
            } catch (\Throwable) {
                $pdo = null;
            }
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($this->tables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $rows = [];

                if ($pdo) {
                    try {
                        $stmt = $pdo->query("SELECT * FROM \"{$table}\"");
                        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
                    } catch (\Throwable) {
                        $rows = [];
                    }
                }

                if (empty($rows)) {
                    $jsonPath = __DIR__."/data/{$table}.json";
                    if (file_exists($jsonPath)) {
                        $rows = json_decode(file_get_contents($jsonPath), true) ?? [];
                    }
                }

                if (empty($rows)) {
                    continue;
                }

                foreach (array_chunk($rows, 500) as $chunk) {
                    DB::table($table)->insertOrIgnore($chunk);
                }

                if ($this->command) {
                    $this->command->info("Seeded table [{$table}]: ".count($rows).' records.');
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->ensureTeachingMaterialsFiles();
    }

    /**
     * Ensure bundled seed material files exist in active storage locations.
     */
    protected function ensureTeachingMaterialsFiles(): void
    {
        $seedDir = __DIR__.'/data/teaching-materials';
        if (! is_dir($seedDir)) {
            return;
        }

        $destinations = [
            storage_path('app/public/teaching-materials'),
            storage_path('app/private/teaching-materials'),
        ];

        foreach ($destinations as $destDir) {
            if (! is_dir($destDir)) {
                @mkdir($destDir, 0775, true);
            }
        }

        $items = scandir($seedDir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $sourceFile = $seedDir.DIRECTORY_SEPARATOR.$item;
            if (! is_file($sourceFile)) {
                continue;
            }

            foreach ($destinations as $destDir) {
                $targetFile = $destDir.DIRECTORY_SEPARATOR.$item;
                if (! file_exists($targetFile)) {
                    @copy($sourceFile, $targetFile);
                }
            }
        }
    }
}
