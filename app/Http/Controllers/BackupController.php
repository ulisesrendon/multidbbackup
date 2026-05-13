<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backupService) {}

    /**
     * Immediately run all schedules for a given connection.
     */
    public function runNow(DatabaseConnection $connection): JsonResponse
    {
        if (! $connection->isActive()) {
            return response()->json(['error' => 'Connection is paused.'], 422);
        }

        $results = [];

        foreach ($connection->schedules as $schedule) {
            $run = $this->backupService->runBackup($schedule);

            $results[] = [
                'schedule_id' => $schedule->id,
                'status'      => $run->status,
                'message'     => $run->status === 'success'
                    ? 'Backup completed successfully.'
                    : ($run->error_message ?? 'Backup failed.'),
            ];
        }

        $allSucceeded = collect($results)->every(fn ($r) => $r['status'] === 'success');

        return response()->json([
            'success' => $allSucceeded,
            'results' => $results,
        ], $allSucceeded ? 200 : 500);
    }
}
