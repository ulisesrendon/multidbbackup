<?php

namespace App\Services;

use App\Models\BackupRun;
use App\Models\BackupSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\DbDumper\Databases\PostgreSql;

class BackupService
{
    public function __construct(private readonly EncryptionService $encryption) {}

    /**
     * Execute a backup for a given schedule and store locally + on S3.
     */
    public function runBackup(BackupSchedule $schedule): BackupRun
    {
        $connection = $schedule->databaseConnection;

        $run = BackupRun::create([
            'database_connection_id' => $connection->id,
            'backup_schedule_id'     => $schedule->id,
            'status'                 => 'running',
        ]);

        $tempDumpFile = null;
        $tempZipFile = null;

        try {
            // Decrypt credentials (cast does this automatically, but we need raw values for pg_dump)
            $host     = $connection->host_encrypted;
            $port     = (int) $connection->port_encrypted;
            $dbName   = $connection->database_name_encrypted;
            $username = $connection->username_encrypted;
            $password = $connection->password_encrypted;

            // Dump to a secure temp file
            $tempDumpFile = tempnam(sys_get_temp_dir(), 'pgdump_');

            PostgreSql::create()
                ->setHost($host)
                ->setPort($port)
                ->setDbName($dbName)
                ->setUserName($username)
                ->setPassword($password)
                ->dumpToFile($tempDumpFile);

            // Create compressed + AES-encrypted ZIP archive (7-Zip compatible)
            $tempZipFile = tempnam(sys_get_temp_dir(), 'pgzip_');
            $sqlEntryName = now()->format('Y-m-d_H-i-s') . '.sql';
            $this->encryption->createPasswordProtectedZip($tempDumpFile, $tempZipFile, $sqlEntryName);

            // Determine destination paths
            $storagePath = $schedule->storagePath();
            $filename    = now()->format('Y-m-d_H-i-s') . '.zip';
            $localPath   = $storagePath . '/' . $filename;
            $zipContent  = file_get_contents($tempZipFile);

            if ($zipContent === false) {
                throw new \RuntimeException('Unable to read generated ZIP archive from temporary storage.');
            }

            // Store locally
            Storage::disk('backups')->put($localPath, $zipContent);

            // Copy to S3
            $s3Stored = false;
            try {
                Storage::disk('backup-s3')->put(
                    $localPath,
                    Storage::disk('backups')->get($localPath)
                );
                $s3Stored = true;
            } catch (\Throwable $s3Error) {
                Log::warning("S3 upload failed for backup {$localPath}: " . $s3Error->getMessage());
            }

            $size = Storage::disk('backups')->size($localPath);

            $run->update([
                'status'       => 'success',
                'local_path'   => $localPath,
                's3_path'      => $s3Stored ? $localPath : null,
                'size_bytes'   => $size,
                'completed_at' => now(),
            ]);

            $schedule->update(['last_backup_at' => now()]);

            // Enforce retention policy
            $this->cleanupOldBackups($schedule);

        } catch (\Throwable $e) {
            Log::error("Backup failed for schedule #{$schedule->id}: " . $e->getMessage());

            $run->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
        } finally {
            if ($tempDumpFile && file_exists($tempDumpFile)) {
                @unlink($tempDumpFile);
            }

            if ($tempZipFile && file_exists($tempZipFile)) {
                @unlink($tempZipFile);
            }
        }

        return $run->fresh();
    }

    /**
     * Remove backup files (local + S3) that fall outside the retention window.
     */
    public function cleanupOldBackups(BackupSchedule $schedule): void
    {
        $cutoff = $schedule->retentionCutoff();
        $path   = $schedule->storagePath();

        $files = Storage::disk('backups')->files($path);

        foreach ($files as $file) {
            $basename = basename($file, '.zip');

            try {
                $fileDate = Carbon::createFromFormat('Y-m-d_H-i-s', $basename);
            } catch (\Throwable) {
                continue; // Skip files with unexpected names
            }

            if ($fileDate->isBefore($cutoff)) {
                Storage::disk('backups')->delete($file);

                try {
                    Storage::disk('backup-s3')->delete($file);
                } catch (\Throwable) {
                    // Best-effort S3 cleanup
                }

                BackupRun::where('local_path', $file)->delete();
            }
        }
    }
}
