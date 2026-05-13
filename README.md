# MultiDB Backup

MultiDB Backup is a Laravel application to manage automatic encrypted PostgreSQL backups from a web dashboard.

It provides:
- First-run admin setup (email + password only).
- Protected login-based dashboard.
- PostgreSQL connection management with encrypted credentials.
- Multiple backup schedules per connection (frequency + retention sets).
- One-click manual snapshot backups.
- Encrypted and compressed ZIP backup files (7-Zip compatible).

## How It Works

1. On the very first run (no users in database), the app shows a registration screen.
2. The first registered user becomes the only admin account.
3. After that, new registrations are blocked and only login is allowed.
4. Inside the dashboard, the admin can:
	- Add PostgreSQL connections.
	- Configure one or more schedule sets for each connection.
	- Pause/resume each connection without page refresh.
	- Trigger Backup Now, which creates a one-off snapshot in a snapshot folder.
5. Scheduled backups run automatically when due.
6. Old backups are cleaned using each schedule retention policy.

## Backup Storage Layout

Backups are saved in local disk and copied to S3.

- Scheduled backups:
  - `storage/app/backups/{connection-alias}/{frequency}_{retention}/...zip`
- Manual Backup Now snapshots:
  - `storage/app/backups/{connection-alias}/snapshot/...zip`

All backup files are password-protected AES-256 encrypted ZIP archives.

## Requirements

- PHP 8.3+
- Composer
- PostgreSQL client tools available in PATH (`pg_dump`)
- PHP ZIP extension enabled (`ZipArchive`)

## Installation

1. Clone the repository.
2. Install PHP dependencies:

	```bash
	composer install
	```

3. Copy environment file:

	```bash
	cp .env.example .env
	```

4. Generate Laravel app key:

	```bash
	php artisan key:generate
	```

5. Configure database settings in `.env` for the app itself (SQLite/MySQL/PostgreSQL).

6. Set backup encryption key in `.env`:

	- Generate a key:

	  ```bash
	  php artisan backup:generate-key
	  ```

	- Copy generated value to:

	  ```env
	  BACKUP_ENCRYPTION_KEY=base64_encoded_32_byte_key
	  ```

7. Configure S3 environment variables in `.env`:

	```env
	AWS_ACCESS_KEY_ID=
	AWS_SECRET_ACCESS_KEY=
	AWS_DEFAULT_REGION=us-east-1
	AWS_BUCKET=
	AWS_URL=
	AWS_ENDPOINT=
	AWS_USE_PATH_STYLE_ENDPOINT=false
	AWS_BACKUP_PREFIX=multidbbackup
	```

8. Run migrations:

	```bash
	php artisan migrate
	```

9. Start local development:

	```bash
	php artisan serve
	```

**Note:** All frontend assets are prebuilt and included. You do NOT need Node.js or npm. The app is ready to use after `composer install` and `php artisan migrate`.

## Scheduler Setup

The app includes an hourly scheduled command:

- `backup:run-scheduled`

For production, configure your server scheduler to run Laravel scheduler every minute:

```bash
php artisan schedule:run
```

Example cron entry (Linux):

```cron
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## Security Notes

- Database credentials are encrypted before storage.
- Backup files are compressed and AES-256 encrypted ZIP archives.
- Keep `BACKUP_ENCRYPTION_KEY` secret and backed up safely.
- If this key is lost, encrypted backups cannot be opened.

## Quick Usage

1. Open app URL.
2. Register first admin (first run only).
3. Login.
4. Add PostgreSQL connection and one or more schedules.
5. Use Backup Now for on-demand snapshot.
6. Let scheduler run periodic backups automatically.
