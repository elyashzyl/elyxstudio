<?php

namespace App\Console\Commands;

use App\Services\AiManager\AiSystemManagerService;
use Illuminate\Console\Command;

class AiManagerTickCommand extends Command
{
    protected $signature = 'edupulse:ai-manager-tick {--force : Force inspection run even if AI Manager toggle is OFF}';

    protected $description = 'Execute EduPulse autonomous AI supervisor inspection and routine maintenance tasks';

    public function handle(AiSystemManagerService $manager): int
    {
        $force = (bool) $this->option('force');

        $result = $manager->runInspection($force, 'artisan');

        if ($result['skipped']) {
            $this->comment($result['summary']);
            return Command::SUCCESS;
        }

        $this->info($result['summary']);

        return Command::SUCCESS;
    }
}
