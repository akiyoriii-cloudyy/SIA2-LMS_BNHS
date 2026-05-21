# BNHS LMS — restore MySQL from backup (Windows / XAMPP)
# Usage: .\scripts\database-restore.ps1 -BackupFile "storage\app\backups\lms_bnhs_20260521_120000.sql"

param(
    [Parameter(Mandatory = $true)]
    [string]$BackupFile
)

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

if (-not (Test-Path $BackupFile)) {
    Write-Host "File not found: $BackupFile" -ForegroundColor Red
    exit 1
}

Write-Host "Restoring from $BackupFile ..." -ForegroundColor Yellow
php artisan db:restore $BackupFile

if ($LASTEXITCODE -ne 0) {
    Write-Host "Restore failed." -ForegroundColor Red
    exit 1
}

Write-Host "Restore complete." -ForegroundColor Green
