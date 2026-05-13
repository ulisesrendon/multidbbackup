<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class EncryptionService
{
    private ?string $key = null;

    private ?string $rawKey = null;

    /**
     * Lazily resolve and validate the encryption key.
     */
    private function key(): string
    {
        if ($this->key !== null) {
            return $this->key;
        }

        $rawKey = $this->rawKey();

        if (empty($rawKey)) {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY is not set in your .env file.');
        }

        $decoded = base64_decode($rawKey, strict: true);

        if ($decoded === false || strlen($decoded) !== 32) {
            throw new RuntimeException(
                'BACKUP_ENCRYPTION_KEY must be a base64-encoded 32-byte key. '
                . 'Generate one with: php artisan backup:generate-key'
            );
        }

        return $this->key = $decoded;
    }

    /**
     * Resolve the raw BACKUP_ENCRYPTION_KEY from config.
     */
    private function rawKey(): string
    {
        if ($this->rawKey !== null) {
            return $this->rawKey;
        }

        $rawKey = (string) config('app.backup_encryption_key');

        if ($rawKey === '') {
            throw new RuntimeException('BACKUP_ENCRYPTION_KEY is not set in your .env file.');
        }

        return $this->rawKey = $rawKey;
    }

    /**
     * Password used for ZIP encryption. This value must be kept secret.
     */
    public function zipPassword(): string
    {
        return $this->rawKey();
    }

    /**
     * Encrypt a string value using AES-256-GCM.
     * Returns a base64-encoded string containing IV + auth tag + ciphertext.
     */
    public function encrypt(string $value): string
    {
        $iv = random_bytes(12); // 96-bit IV recommended for GCM
        $tag = '';

        $ciphertext = openssl_encrypt(
            $value,
            'aes-256-gcm',
                $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        // Pack: 12 bytes IV | 16 bytes tag | ciphertext
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a value previously encrypted with encrypt().
     */
    public function decrypt(string $value): string
    {
        $data = base64_decode($value, strict: true);

        if ($data === false || strlen($data) < 28) {
            throw new RuntimeException('Invalid encrypted data: malformed payload.');
        }

        $iv         = substr($data, 0, 12);
        $tag        = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
                $this->key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed: authentication tag mismatch or corrupted data.');
        }

        return $plaintext;
    }

    /**
     * Encrypt the contents of a file and write the result to $outputPath.
     */
    public function encryptFile(string $inputPath, string $outputPath): void
    {
        $content = file_get_contents($inputPath);

        if ($content === false) {
            throw new RuntimeException("Could not read file: {$inputPath}");
        }

        file_put_contents($outputPath, $this->encrypt($content));
    }

    /**
     * Create a compressed, AES-256 encrypted ZIP from a source file.
     */
    public function createPasswordProtectedZip(string $sourcePath, string $zipPath, string $entryName): void
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive extension is required to create encrypted ZIP backups.');
        }

        $zip = new ZipArchive();

        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException('Failed to create ZIP archive.');
        }

        if (! $zip->setPassword($this->zipPassword())) {
            $zip->close();
            throw new RuntimeException('Failed to set ZIP password.');
        }

        if (! $zip->addFile($sourcePath, $entryName)) {
            $zip->close();
            throw new RuntimeException('Failed to add dump file into ZIP archive.');
        }

        $zip->setCompressionName($entryName, ZipArchive::CM_DEFLATE, 9);

        if (! $zip->setEncryptionName($entryName, ZipArchive::EM_AES_256)) {
            $zip->close();
            throw new RuntimeException('Failed to apply AES-256 encryption to ZIP entry.');
        }

        $zip->close();
    }
}
