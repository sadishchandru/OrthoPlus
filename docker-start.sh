#!/bin/bash
set -e

echo "=== Step 1: Config clear ==="
php artisan config:clear
php artisan config:cache

echo "=== Step 2: Test DB connection ==="
php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB OK';" 2>&1

echo "=== Step 3: Run migrations ==="
php artisan migrate --force --verbose 2>&1
echo "Migration exit code: $?"

echo "=== Step 4: Seed database — all seeders, non-fatal, idempotent ==="
# DatabaseSeeder calls Ortho + TreatmentCatalog + Exercise + IndianMedicines.
php artisan db:seed --force 2>&1 || echo "Seeding skipped/failed — continuing boot"

echo "=== Step 4b: Storage symlink (serve patient file uploads) ==="
php artisan storage:link 2>&1 || echo "storage:link exists/failed — continuing"

echo "=== Step 5: Fix Apache port ==="
sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf

echo "=== Step 6: Start Apache ==="
apache2-foreground