# Database backup/restore (XAMPP / MySQL)

These scripts use your `.env` database settings (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

## Backup

From the project root:

```powershell
pwsh -File scripts/db-backup.ps1
```

Optional output file:

```powershell
pwsh -File scripts/db-backup.ps1 -OutFile backups/lms_bnhs_latest.sql
```

## Restore

```powershell
pwsh -File scripts/db-restore.ps1 -SqlFile backups/lms_bnhs_latest.sql
```

Notes:
- If XAMPP is installed, the scripts prefer `C:\xampp\mysql\bin\mysqldump.exe` and `C:\xampp\mysql\bin\mysql.exe`.
- Otherwise, they fall back to `mysqldump` / `mysql` from your `PATH`.

