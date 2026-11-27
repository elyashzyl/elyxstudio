<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RunBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run {--type=full : The backup type (full or database)} {--notes= : Optional notes for this backup} {--sync-to-cloud : Automatically sync archive to configured S3 or Cloudflare R2 bucket}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new EduPulse database or full system backup';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = in_array($this->option('type'), ['database', 'full']) ? $this->option('type') : 'full';
        $notes = $this->option('notes') ?: 'Scheduled or CLI backup';

        $this->info("Creating {$type} backup...");

        try {
            $syncToCloud = (bool) $this->option('sync-to-cloud');
            $backup = BackupService::createBackup($type, $notes, $syncToCloud);
            $this->info("Backup created successfully: {$backup['filename']} ({$backup['formatted_size']})");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Backup failed: {$e->getMessage()}");

            return Command::FAILURE;
        }
    }
}
