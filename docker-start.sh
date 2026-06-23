#!/bin/bash
set -e

echo "=== Clearing config ==="
php artisan config:clear
php artisan config:cache
php artisan view:clear

echo "=== Running migrations ==="
php artisan migrate --force 2>&1
echo "=== Migration done ==="

echo "=== Seeding database ==="
php artisan db:seed --force 2>&1
echo "=== Seeding done ==="

# Change Apache port to 8080
sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf

echo "=== Starting Apache ==="
apache2-foreground