#!/bin/sh
set -e

export PORT="${PORT:-80}"

if [ -f /etc/nginx/templates/default.conf.template ]; then
    envsubst '$PORT' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf
fi

mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/app/public/teaching-materials \
    /var/www/html/storage/app/public/practical-submissions \
    /var/www/html/storage/app/private \
    /var/www/html/storage/app/private/teaching-materials \
    /var/www/html/storage/app/private/practical-submissions \
    /var/www/html/storage/app/private/messenger-attachments \
    /var/www/html/storage/app/private/backups \
    /var/www/html/storage/app/backups \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

SEED_MATERIALS_DIR="/var/www/html/database/seeders/data/teaching-materials"
if [ -d "$SEED_MATERIALS_DIR" ]; then
    for file in "$SEED_MATERIALS_DIR"/*; do
        if [ -f "$file" ]; then
            base=$(basename "$file")
            if [ ! -f "/var/www/html/storage/app/public/teaching-materials/$base" ]; then
                cp "$file" "/var/www/html/storage/app/public/teaching-materials/$base"
            fi
            if [ ! -f "/var/www/html/storage/app/private/teaching-materials/$base" ]; then
                cp "$file" "/var/www/html/storage/app/private/teaching-materials/$base"
            fi
        fi
    done
fi

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
    DB_DIR=$(dirname "$DB_FILE")
    mkdir -p "$DB_DIR"
    if [ ! -f "$DB_FILE" ]; then
        touch "$DB_FILE"
    fi
    chown -R www-data:www-data "$DB_DIR"
    chmod 775 "$DB_DIR"
    chmod 664 "$DB_FILE"
fi

if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    for i in 1 2 3 4 5; do
        if php artisan migrate --force --isolated; then
            echo "Database migrations completed successfully."
            break
        fi
        echo "Database migration attempt $i failed. Retrying in 2 seconds..."
        sleep 2
    done
fi

if [ "${APP_ENV}" = "production" ]; then
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

if [ "$#" -gt 0 ]; then
    exec "$@"
else
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi

