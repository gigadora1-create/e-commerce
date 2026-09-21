#!/bin/sh
set -e

cd /var/www/html

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [ ! -L public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

if [ -f "docker/production-migrations.txt" ]; then
    # Keep the approved migration list under version control. Running every
    # pending migration is unsafe because this application has legacy migrations.
    while IFS= read -r migration_path || [ -n "$migration_path" ]; do
        case "$migration_path" in
            ''|'#'*) continue ;;
        esac

        php artisan migrate --path="$migration_path" --force
    done < docker/production-migrations.txt
elif [ -n "${DEPLOY_MIGRATION_PATHS:-}" ]; then
    # Backward compatibility for deployments created before the manifest.
    printf '%s' "$DEPLOY_MIGRATION_PATHS" | tr ',' '\n' | while IFS= read -r migration_path; do
        [ -n "$migration_path" ] || continue
        php artisan migrate --path="$migration_path" --force
    done
elif [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${LARAVEL_OPTIMIZE:-true}" = "true" ]; then
    php artisan optimize:clear >/dev/null 2>&1 || true
    php artisan config:cache >/dev/null 2>&1 || true
    php artisan route:cache >/dev/null 2>&1 || true
    php artisan view:cache >/dev/null 2>&1 || true
fi

exec "$@"
