# syntax=docker/dockerfile:1
#
# Production image: installs backend dependencies without dev packages,
# builds the SPA-free Inertia frontend, and serves both from one container
# on the injected PORT.
#
# The frontend build needs PHP, not just Node: `npm run build` runs the
# Wayfinder Vite plugin, which shells out to `php artisan wayfinder:generate`
# to regenerate resources/js/{routes,actions,wayfinder} from the real route
# list before Vite ever bundles anything (see routes/console.php and
# CI's frontend job). That rules out two independent build stages — the
# build stage below is PHP-first with Node layered on, mirroring the local
# dev image (docker/php/Dockerfile) rather than the split node/php stages a
# separate-SPA project would use. See ADR-0006.

FROM php:8.4-cli-alpine AS build
RUN apk add --no-cache postgresql-dev $PHPIZE_DEPS nodejs npm \
    && docker-php-ext-install pdo_pgsql pgsql bcmath \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

COPY . .
RUN composer run-script post-autoload-dump --no-interaction

# Wayfinder generation below only enumerates routes — it needs a bootable
# app, not a real database, so a throwaway key is fine; nothing here ships.
RUN cp .env.example .env && php artisan key:generate --force

RUN npm ci
RUN npm run build

RUN rm -rf node_modules .env

FROM php:8.4-fpm-alpine AS runtime
RUN apk add --no-cache nginx postgresql-libs \
    && apk add --no-cache --virtual .build-deps postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install pdo_pgsql pgsql bcmath \
    && apk del .build-deps

WORKDIR /var/www/html
COPY --from=build /app ./
RUN chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx/prod.conf.template /etc/nginx/templates/default.conf.template
COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["entrypoint.sh"]
