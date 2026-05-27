# BNHS LMS — automated MySQL backup (Windows / XAMPP)
# Usage: .\scripts\database-backup.ps1

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

Write-Host "Running Laravel db:backup..." -ForegroundColor Cyan
php artisan db:backup

if ($LASTEXITCODE -ne 0) {
    Write-Host "Backup failed. Ensure XAMPP MySQL is running and .env DB_* values are set." -ForegroundColor Red
    exit 1
}

Write-Host "Done. Files are in storage/app/backups" -ForegroundColor Green
