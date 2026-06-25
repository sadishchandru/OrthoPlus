FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Configure GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install only the necessary PHP extensions (others are built-in)
RUN docker-php-ext-install pdo_mysql gd bcmath

# Patient file-upload limits (25 MB files, many docs per save)
COPY uploads.ini /usr/local/etc/php/conf.d/zz-orthoplus-uploads.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html