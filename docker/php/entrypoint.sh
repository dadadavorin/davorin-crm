#!/bin/sh
set -e

cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ]; then
    npm install
fi

if [ ! -f .env ]; then
    cp .env.example .env
fi

if ! grep -q '^APP_KEY=base64' .env; then
    php artisan key:generate --force
fi

# Dev only. A fresh volume otherwise leaves the schema missing and every
# request fails until someone migrates by hand; it's a no-op once current.
# Production migrates as a pre-deploy step instead, so replicas never race.
php artisan migrate --force

# A no-op once a user exists (DatabaseSeeder::run() checks), so a fresh
# volume gets the demo data without a later restart trying to re-seed it.
php artisan db:seed --force

# Vite runs backgrounded so php-fpm (the process Docker sends signals to,
# via "exec") can own the container's foreground and exit code.
npm run dev -- --host &

exec "$@"
