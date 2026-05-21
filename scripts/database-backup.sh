#!/usr/bin/env bash
# BNHS LMS — automated MySQL backup (Linux/macOS)
set -euo pipefail
cd "$(dirname "$0")/.."
php artisan db:backup
echo "Backups stored in storage/app/backups"
