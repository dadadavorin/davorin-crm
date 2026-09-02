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

if ! grep -q '^APP_KEY=base64' .env.testing; then
    php artisan key:generate --force --env=testing
fi

# Pest's RefreshDatabase drops and rebuilds whatever database DB_DATABASE
# points at. .env.testing names a separate database so a local `composer
# check` can't wipe the one this same entrypoint just seeded below; Postgres
# has no `CREATE DATABASE IF NOT EXISTS`, so check first.
TEST_DB_HOST=$(grep '^DB_HOST=' .env.testing | cut -d= -f2) \
TEST_DB_PORT=$(grep '^DB_PORT=' .env.testing | cut -d= -f2) \
TEST_DB_USERNAME=$(grep '^DB_USERNAME=' .env.testing | cut -d= -f2) \
TEST_DB_PASSWORD=$(grep '^DB_PASSWORD=' .env.testing | cut -d= -f2) \
TEST_DB_DATABASE=$(grep '^DB_DATABASE=' .env.testing | cut -d= -f2) \
php -r '
$dsn = sprintf("pgsql:host=%s;port=%s;dbname=postgres", getenv("TEST_DB_HOST"), getenv("TEST_DB_PORT"));
$pdo = new PDO($dsn, getenv("TEST_DB_USERNAME"), getenv("TEST_DB_PASSWORD"));
$db = getenv("TEST_DB_DATABASE");
$exists = (bool) $pdo->query("SELECT 1 FROM pg_database WHERE datname = ".$pdo->quote($db))->fetchColumn();
if (! $exists) {
    $pdo->exec("CREATE DATABASE \"$db\"");
}
'

# Dev only. A fresh volume otherwise leaves the schema missing and every
# request fails until someone migrates by hand; it's a no-op once current.
# Production migrates as a pre-deploy step instead, so replicas never race.
php artisan migrate --force

# A no-op once a user exists (DatabaseSeeder::run() checks), so a fresh
# volume gets the demo data without a later restart trying to re-seed it.
php artisan db:seed --force

# Everything above runs as root, including composer regenerating
# bootstrap/cache/*.php — but php-fpm's workers run as www-data (see
# www.conf). A host bind mount that actually enforces Unix permissions
# (any real Linux host; Docker Desktop's mount layer is lenient enough
# not to show this) leaves www-data unable to write storage/logs,
# storage/framework/{cache,sessions,views} or bootstrap/cache, and every
# request 500s before it can do anything.
chown -R www-data:www-data storage bootstrap/cache

# Vite runs backgrounded so php-fpm (the process Docker sends signals to,
# via "exec") can own the container's foreground and exit code.
npm run dev -- --host &

exec "$@"
