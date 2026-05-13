<?php

namespace App\Models;

use App\Casts\EncryptedString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DatabaseConnection extends Model
{
    protected $fillable = [
        'alias',
        'host_encrypted',
        'port_encrypted',
        'database_name_encrypted',
        'username_encrypted',
        'password_encrypted',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'host_encrypted'          => EncryptedString::class,
            'port_encrypted'          => EncryptedString::class,
            'database_name_encrypted' => EncryptedString::class,
            'username_encrypted'      => EncryptedString::class,
            'password_encrypted'      => EncryptedString::class,
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(BackupSchedule::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(BackupRun::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
