FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libpq-dev \
    libzip-dev nodejs npm \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy all files
COPY . .

# PHP upload limits (patient files up to 25 MB). Without this, live PHP defaults
# (upload_max_filesize=2M) silently drop larger uploads → /patients/{id}/files 422.
COPY docker/laravel/uploads.ini /usr/local/etc/php/conf.d/zz-orthoplus-uploads.ini

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node dependencies and build Vite assets
RUN npm install && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/public/build \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Apache config — point to public folder
RUN sed -i 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Enable .htaccess
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

COPY docker-start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 8080
CMD ["/usr/local/bin/start.sh"]