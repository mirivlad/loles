#!/bin/sh
set -eu

: "${APP_KEY:?APP_KEY must be configured and kept across updates}"
: "${DB_PASSWORD:?DB_PASSWORD must be configured}"

cd /var/www/html

mkdir -p storage/app/public storage/framework/cache/data \
    storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# A freshly deployed MariaDB can take longer than the container health check.
n=0
until php -r '
try {
    new PDO(
        "mysql:host=" . getenv("DB_HOST") . ";port=" . (getenv("DB_PORT") ?: "3306") . ";dbname=" . getenv("DB_DATABASE"),
        getenv("DB_USERNAME"),
        getenv("DB_PASSWORD"),
        [PDO::ATTR_TIMEOUT => 2]
    );
} catch (Throwable $e) {
    exit(1);
}
'; do
    n=$((n + 1))
    if [ "$n" -ge 30 ]; then
        echo "LoLeS: database did not become available" >&2
        exit 1
    fi
    sleep 2
done

su -s /bin/sh www-data -c 'php artisan migrate --force --no-interaction'
su -s /bin/sh www-data -c 'php artisan db:seed --class=RoleSeeder --force --no-interaction'

# Demo data is explicitly opt-in. Never install fixed-password demo accounts.
if [ "${LOLES_DEMO_DATA:-false}" = "true" ]; then
    su -s /bin/sh www-data -c 'php artisan loles:demo-data --force --no-interaction'
fi

if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    php artisan storage:link --no-interaction
fi

exec docker-php-entrypoint "$@"
