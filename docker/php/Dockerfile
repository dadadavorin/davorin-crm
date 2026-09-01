FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        git \
        unzip \
        nodejs \
        npm \
        postgresql-dev \
        $PHPIZE_DEPS \
        linux-headers \
    && docker-php-ext-install pdo_pgsql pgsql bcmath \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS linux-headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000 5173

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
