#!/bin/sh
set -eu

# Railway's pre-deploy command (migrations) runs this same image with an
# overridden command; when one is given, run it instead of serving.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

: "${PORT:=8080}"

sed "s/__PORT__/${PORT}/g" /etc/nginx/templates/default.conf.template \
    > /etc/nginx/http.d/default.conf

# php-fpm and the scheduler run backgrounded so nginx (the process Docker
# sends signals to) can own the container's foreground and exit code.
#
# `schedule:work` is the container's cron: there is no system cron in this
# image, and a second Railway service just to run `quotes:expire` once a
# day would break the one-service, one-origin shape the whole deployment is
# built around (see ADR-0007). It sleeps until each due minute and dispatches
# whatever routes/console.php has scheduled — today, only the daily
# `quotes:expire` run.
php-fpm --nodaemonize &
php artisan schedule:work &

exec nginx -g 'daemon off;'
