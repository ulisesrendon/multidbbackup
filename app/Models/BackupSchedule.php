<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BackupSchedule extends Model
{
    protected $fillable = [
        'database_connection_id',
        'frequency_hours',
        'retention_amount',
        'retention_unit',
        'last_backup_at',
    ];

    protected function casts(): array
    {
        return [
            'last_backup_at' => 'datetime',
        ];
    }

    public function databaseConnection(): BelongsTo
    {
        return $this->belongsTo(DatabaseConnection::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(BackupRun::class);
    }

    /**
     * Determine whether this schedule is due for a backup right now.
     * A schedule is due when there has been no backup yet, or when
     * the frequency interval has elapsed since the last backup.
     */
    public function isDue(): bool
    {
        if ($this->last_backup_at === null) {
            return true;
        }

        return now()->gte($this->nextBackupAt());
    }

    /**
     * Return the datetime of the next scheduled backup.
     */
    public function nextBackupAt(): Carbon
    {
        if ($this->last_backup_at === null) {
            return now();
        }

        return match ((int) $this->frequency_hours) {
            1   => $this->last_backup_at->copy()->addHour(),
            4   => $this->last_backup_at->copy()->addHours(4),
            12  => $this->last_backup_at->copy()->addHours(12),
            24  => $this->last_backup_at->copy()->addDay(),
            48  => $this->last_backup_at->copy()->addDays(2),
            168 => $this->last_backup_at->copy()->addWeek(),
            200 => $this->last_backup_at->copy()->addMonth(),
            default => $this->last_backup_at->copy()->addHours(max(1, (int) $this->frequency_hours)),
        };
    }

    /**
     * Human-readable label for schedule frequency.
     */
    public function frequencyLabel(): string
    {
        return match ((int) $this->frequency_hours) {
            1   => 'Every Hour',
            4   => 'Every 4 Hours',
            12  => 'Every 12 Hours',
            24  => 'Every Day',
            48  => 'Every 2 Days',
            168 => 'Every Week',
            200 => 'Every Month',
            default => 'Every ' . $this->frequency_hours . ' Hours',
        };
    }

    /**
     * Folder-safe frequency segment for backup path names.
     */
    public function frequencyPathSegment(): string
    {
        return match ((int) $this->frequency_hours) {
            1   => '1h',
            4   => '4h',
            12  => '12h',
            24  => '1d',
            48  => '2d',
            168 => '1w',
            200 => '1m',
            default => $this->frequency_hours . 'h',
        };
    }

    /**
     * Return the cutoff datetime; backups older than this should be removed.
     */
    public function retentionCutoff(): Carbon
    {
        return match ($this->retention_unit) {
            'hours'  => now()->subHours($this->retention_amount),
            'days'   => now()->subDays($this->retention_amount),
            'weeks'  => now()->subWeeks($this->retention_amount),
            'months' => now()->subMonths($this->retention_amount),
            'years'  => now()->subYears($this->retention_amount),
        };
    }

    /**
     * Human-readable label for the retention period.
     */
    public function retentionLabel(): string
    {
        $unit = match ($this->retention_unit) {
            'hours'  => $this->retention_amount === 1 ? 'hour'  : 'hours',
            'days'   => $this->retention_amount === 1 ? 'day'   : 'days',
            'weeks'  => $this->retention_amount === 1 ? 'week'  : 'weeks',
            'months' => $this->retention_amount === 1 ? 'month' : 'months',
            'years'  => $this->retention_amount === 1 ? 'year'  : 'years',
        };

        return $this->retention_amount . ' ' . $unit;
    }

    /**
     * Storage sub-path for this schedule's backup files.
     * Format: {alias}/{freq}h_{amount}{unit_initial}
     */
    public function storagePath(): string
    {
        $alias       = $this->databaseConnection->alias;
        $unitInitial = substr($this->retention_unit, 0, 1); // h, d, w, m, y
        $retStr      = $this->retention_amount . $unitInitial;

        return "backups/{$alias}/{$this->frequencyPathSegment()}_{$retStr}";
    }
}
