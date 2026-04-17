# Run Laravel migrations from the project root (works from any cwd).
$ErrorActionPreference = "Stop"
$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot
php artisan migrate --force --no-interaction @args
