<?php

namespace App\Console\Commands;

use App\Models\BackupSchedule;
use App\Services\BackupService;
use Illuminate\Console\Command;

class RunScheduledBackups extends Command
{
    protected $signature   = 'backup:run-scheduled';
    protected $description = 'Check all active backup schedules and run any that are due.';

    public function __construct(private readonly BackupService $backupService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $schedules = BackupSchedule::with('databaseConnection')
            ->whereHas('databaseConnection', fn ($q) => $q->where('status', 'active'))
            ->get();

        $ran   = 0;
        $fails = 0;

        foreach ($schedules as $schedule) {
            if (! $schedule->isDue()) {
                continue;
            }

            $this->line("  → Backing up: {$schedule->databaseConnection->alias} "
                . "(every {$schedule->frequency_hours}h, keep {$schedule->retentionLabel()})");

            $run = $this->backupService->runBackup($schedule);

            if ($run->status === 'success') {
                $this->info("    ✓ Success ({$run->size_bytes} bytes)");
                $ran++;
            } else {
                $this->error("    ✗ Failed: {$run->error_message}");
                $fails++;
            }
        }

        $this->info("Scheduled backup run complete. Ran: {$ran}, Failed: {$fails}.");

        return $fails > 0 ? self::FAILURE : self::SUCCESS;
    }
}
