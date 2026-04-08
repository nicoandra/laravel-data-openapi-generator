# Use the official PHP 8.2 CLI image
FROM php:8.2-cli

# Install system dependencies (unzip is required for Composer)
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip

# Copy Composer from the official Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Run composer install if you have a composer.json
COPY . .
RUN composer install