# 09 — Backup & Restore

## 1. What's backed up and how

`spatie/laravel-backup` (config: `config/backup.php`) runs three scheduled commands
(`routes/console.php`):

| Time (server) | Command | Purpose |
|---|---|---|
| 01:30 daily | `backup:clean` | Prunes old backups per the retention strategy below, **before** the new one is created. |
| 01:45 daily | `backup:run` | Dumps the database and zips it (plus app files, per `backup.source.files`), copies the archive to every disk in `backup.destination.disks`. |
| 02:30 daily | `backup:monitor` | Checks each disk in `monitor_backups` for freshness/size and fires a notification if unhealthy. |

Archives land as `db-dumps/<connection>-<database>.sql` inside a zip, on:

- **`local`** — `storage/app/private/<APP_NAME>/*.zip` (always included).
- **`backup-offsite`** — an S3-compatible disk (`config/filesystems.php`), only added to the
  `disks` list once `BACKUP_AWS_BUCKET` is set. Deliberately a separate bucket/credentials from
  the app's own `s3` disk — the backup destination should never be the bucket public assets are
  served from. Provisioning real credentials for this is a Sprint 16 deployment task; until then
  the disk is simply omitted (not attempted-and-ignored) so a missing config can't crash or fail
  the local backup.

**Retention** (`cleanup.default_strategy`): all backups for 7 days, then one/day for 16 days, then
one/week for 8 weeks, then one/month for 4 months, then one/year for 2 years — capped at 5 GB
total. Spatie's default strategy never deletes the newest backup regardless of these rules.

**Notifications**: `BackupHasFailedNotification`, `UnhealthyBackupWasFoundNotification`,
`CleanupHasFailedNotification`, and their "was successful" counterparts all mail
`BACKUP_NOTIFICATION_EMAIL` (falls back to `MAIL_FROM_ADDRESS` if unset — config loads before the
DB-backed org settings exist, so it can't read `org.email`).

## 2. Manual backup

```bash
php artisan backup:run --only-db
```

Drop `--only-db` to include application files per `backup.source.files.include` (whole app
directory, excluding `vendor/`, `node_modules/`, and `storage/framework/` automatically).

## 3. Restore procedure (verified 2026-07-31)

This is the exact procedure that was run end-to-end against a real backup archive to confirm it
works — not a theoretical description. Adjust paths/db driver for your environment; the shape is
the same for MySQL (swap `sqlite3` for `mysql -u... -p... dbname < dump.sql`).

1. **Locate the archive.** `storage/app/private/<APP_NAME>/<timestamp>.zip` on the `local` disk
   (or pull the equivalent object from the `backup-offsite` disk once that's provisioned).

2. **Extract the SQL dump.**
   ```bash
   unzip "storage/app/private/Vision Good Work Global Foundation/<timestamp>.zip" \
       -d /tmp/restore-test
   ```
   This produces `/tmp/restore-test/db-dumps/sqlite-sqlite-database.sql` (name reflects
   `database_dump_filename_base` and the connection name).

3. **Import into a clean database.** Never restore over the live database directly — always prove
   the dump is valid against a throwaway target first.
   ```bash
   touch /tmp/restore-test/restored.sqlite
   sqlite3 /tmp/restore-test/restored.sqlite < /tmp/restore-test/db-dumps/sqlite-sqlite-database.sql
   ```
   (MySQL equivalent: `mysql -u root -p restore_test_db < dump.sql` against an empty database
   created for the test.)

4. **Verify row counts against the source**, not just "the import didn't error":
   ```bash
   sqlite3 /tmp/restore-test/restored.sqlite \
       "SELECT 'users', COUNT(*) FROM users UNION ALL SELECT 'donations', COUNT(*) FROM donations;"
   sqlite3 database/database.sqlite \
       "SELECT 'users', COUNT(*) FROM users UNION ALL SELECT 'donations', COUNT(*) FROM donations;"
   ```
   Counts must match exactly.

5. **Verify the app can actually read through it** (proves the schema/data round-trip works with
   Eloquent, not just raw SQL):
   ```bash
   DB_CONNECTION=sqlite DB_DATABASE=/tmp/restore-test/restored.sqlite php artisan tinker \
       --execute="echo App\Models\User::count().' users, '.App\Models\User::first()->email;"
   ```

6. **Discard the test database and extracted files** once verified — never point production `.env`
   at a test restore target.

### Restoring the live database for real

Only after the steps above have proven the specific archive is valid:

1. Put the app in maintenance mode: `php artisan down`.
2. Take a fresh backup of the *current* (about-to-be-replaced) database, in case the restore needs
   to be reverted.
3. Import the dump into the real database connection (same `mysql`/`sqlite3` command as step 3
   above, targeting the live database).
4. `php artisan up`.
5. Spot-check the application (login, a recent donation, a recent receipt) before considering the
   incident closed.

## 4. Required environment variables

See `.env.example`:

```
BACKUP_AWS_ACCESS_KEY_ID=
BACKUP_AWS_SECRET_ACCESS_KEY=
BACKUP_AWS_DEFAULT_REGION=us-east-1
BACKUP_AWS_BUCKET=
BACKUP_AWS_ENDPOINT=
BACKUP_AWS_USE_PATH_STYLE_ENDPOINT=false
BACKUP_ARCHIVE_PASSWORD=
BACKUP_NOTIFICATION_EMAIL=
```

`BACKUP_ARCHIVE_PASSWORD` is optional — if set, archives are AES-256 encrypted
(`backup.backup.encryption`); if unset, `password` is `null` and archives are unencrypted. Given
the dumps contain donor PII and payment metadata, **set this in production**.
