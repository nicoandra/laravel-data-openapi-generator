# Use development image
FROM thecodingmachine/php:8.2-v5-cli-node20 AS base

USER root

# # Install system dependencies (unzip is required for Composer)
# RUN apt-get update && apt-get install -y \
#     unzip \
#     libzip-dev \
#     && docker-php-ext-install zip

# # Copy Composer from the official Composer image
# COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html


ENV PHP_EXTENSION_XDEBUG=1
ENV PHP_INI_XDEBUG__START_WITH_REQUEST=yes
ENV PHP_INI_XDEBUG__MODE=debug
ENV PHP_INI_XDEBUG__CLIENT_PORT=9003


FROM base AS dev
# Run composer install if you have a composer.json
COPY . .
RUN composer install