# syntax=docker/dockerfile:1
ARG PHP_VERSION=8.3
ARG NODE_VERSION=22

FROM php:${PHP_VERSION}-fpm-bookworm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx \
    supervisor \
    sqlite3 \
    gettext-base \
    curl \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    bcmath \
    exif \
    gd \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    zip

FROM base AS builder

ARG NODE_VERSION

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY package.json package-lock.json ./
# Vite 8 uses platform-specific native packages. Keep optional dependencies
# and install hooks enabled so the builder does not inherit host npm settings.
RUN npm ci --include=optional --ignore-scripts=false \
    && node -e "import('rolldown').then(() => console.log('Rolldown native binding loaded')).catch((error) => { console.error(error); process.exit(1); })" \
    && node -e "require('@tailwindcss/oxide'); console.log('Tailwind native binding loaded')" \
    && node -e "require('lightningcss'); console.log('Lightning CSS native binding loaded')"

COPY . .

# Generate the route helpers once before Vite starts. The Vite plugin is
# disabled below to avoid running the generator a second time during build.
ENV ALLOW_REGISTRATION=true \
    WAYFINDER_GENERATE=false

RUN composer dump-autoload --optimize --no-dev \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form \
    && npm run build

FROM base AS runner

WORKDIR /var/www/html

RUN rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default

COPY . .
COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public/build ./public/build
COPY --from=builder /var/www/html/resources/js/actions ./resources/js/actions
COPY --from=builder /var/www/html/resources/js/routes ./resources/js/routes
COPY --from=builder /var/www/html/resources/js/wayfinder ./resources/js/wayfinder

COPY docker/nginx.conf /etc/nginx/templates/default.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

ENV PORT=80 \
    APP_ENV=production

EXPOSE 80 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost:${PORT}/up || exit 1

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

