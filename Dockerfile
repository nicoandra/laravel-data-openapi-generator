# Use development image
FROM thecodingmachine/php:8.4-v5-cli-node20 AS base

USER root

# Set working directory
WORKDIR /var/www/html

ENV PHP_EXTENSION_PCOV=1
ENV PHP_EXTENSION_XDEBUG=1
ENV PHP_INI_XDEBUG__START_WITH_REQUEST=yes
ENV PHP_INI_XDEBUG__MODE=debug
ENV PHP_INI_XDEBUG__CLIENT_PORT=9003


FROM base AS dev
# Run composer install if you have a composer.json
COPY . .
RUN composer install
