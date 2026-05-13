<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateBackupEncryptionKey extends Command
{
    protected $signature   = 'backup:generate-key';
    protected $description = 'Generate a new 32-byte AES-256 key for BACKUP_ENCRYPTION_KEY.';

    public function handle(): int
    {
        $key = base64_encode(random_bytes(32));

        $this->line('');
        $this->info('Generated BACKUP_ENCRYPTION_KEY:');
        $this->line('');
        $this->line("  BACKUP_ENCRYPTION_KEY={$key}");
        $this->line('');
        $this->comment('Add the above line to your .env file before running the application.');
        $this->warn('Keep this key safe — losing it means you cannot decrypt your backups!');

        return self::SUCCESS;
    }
}
