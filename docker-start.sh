#!/bin/bash
set -e
php artisan config:clear
php artisan config:cache
php artisan view:clear
php artisan migrate --force
# Change Apache port to 8080
sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf
sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf
apache2-foreground