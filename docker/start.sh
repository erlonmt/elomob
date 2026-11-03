#!/bin/sh
set -eu
# "setup" (the Compose service of the same name) migrates the database and loads the demonstration data, once per
# start, before any API container; "serve" (the default) prepares the application and starts PHP-FPM.
# As root, the script hands the storage volume (root-owned when an older image created it) and .env to www-data and
# runs itself again as www-data. PHP-FPM's master process keeps root only to open the logs and the port; the
# workers that answer requests run as www-data (pool "www").
mode="${1:-serve}"
if [ "$(id -u)" = "0" ]; then
  if [ ! -f .env ]; then cp .env.example .env; fi
  mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
  chown -R www-data:www-data storage bootstrap/cache .env
  setpriv --reuid=www-data --regid=www-data --init-groups "$0" "$mode"
  if [ "$mode" = "serve" ]; then exec php-fpm; fi
  exit 0
fi
if [ "$mode" = "setup" ]; then
  php artisan migrate --force --no-interaction
  if [ "${SEED_DEMO_DATA:-true}" = "true" ]; then
    php artisan db:seed --force --no-interaction
  fi
  exit 0
fi
if [ -z "${APP_KEY:-}" ]; then
  if [ -s storage/app/app-key ]; then
    APP_KEY="$(cat storage/app/app-key)"
  else
    APP_KEY="$(php artisan key:generate --show --no-interaction)"
  fi
fi
export APP_KEY
php -r '
$key = getenv("APP_KEY");
$decoded = str_starts_with($key, "base64:") ? base64_decode(substr($key, 7), true) : $key;
if ($decoded === false || strlen($decoded) !== 32) { fwrite(STDERR, "Invalid application key.\n"); exit(1); }
$environment = file_get_contents(".env");
$line = "APP_KEY=".$key;
$environment = preg_match("/^APP_KEY=.*$/m", $environment) ? preg_replace("/^APP_KEY=.*$/m", $line, $environment) : $environment."\n".$line."\n";
file_put_contents(".env", $environment);
file_put_contents("storage/app/app-key", $key."\n");
chmod("storage/app/app-key", 0600);
'
php artisan optimize:clear --no-interaction
# Configuration, routes and events are read once and cached, as in production (the API has no views).
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan event:cache --no-interaction
