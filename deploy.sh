#!/usr/bin/env bash
# Run on the Hostinger server (via SSH, manually or from the deploy GitHub
# Action) to roll out a new release. See docs/04-DEPLOYMENT-HOSTINGER.md §5.
set -e

APP_DIR="${APP_DIR:-$HOME/app}"
PHP="${PHP_BIN:-php}"

cd "$APP_DIR"

$PHP artisan down --render="errors::503" --retry=60 || true

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction

# Frontend assets are built in CI (GitHub Actions, reliable Node) and synced
# to public/build/ as part of the deploy step BEFORE this script runs — see
# docs/04-DEPLOYMENT-HOSTINGER.md's "Node is unreliable on shared hosting"
# note. This script deliberately never runs `npm run build` on the server.

$PHP artisan migrate --force

$PHP artisan optimize:clear
$PHP artisan optimize

$PHP artisan queue:restart
$PHP artisan up

echo "Deployed: $(git rev-parse --short HEAD)"
