<?php

namespace App\Http\Controllers;

use App\Models\DatabaseConnection;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backupService) {}

    /**
     * Immediately run a one-off snapshot backup for a given connection.
     */
    public function runNow(DatabaseConnection $connection): JsonResponse
    {
        if (! $connection->isActive()) {
            return response()->json(['error' => 'Connection is paused.'], 422);
        }

        $run = $this->backupService->runSnapshotBackup($connection);
        $ok = $run->status === 'success';

        return response()->json([
            'success' => $ok,
            'result' => [
                'status' => $run->status,
                'path' => $run->local_path,
                'message' => $ok
                    ? 'Snapshot backup completed successfully.'
                    : ($run->error_message ?? 'Snapshot backup failed.'),
            ],
        ], $ok ? 200 : 500);
    }
}
