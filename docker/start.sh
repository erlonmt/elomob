#!/bin/sh
set -eu
# As root, the script hands the storage volume (root-owned when an older image created it) and .env to www-data,
# runs itself again as www-data to prepare the application, then starts PHP-FPM. Its master process keeps root only
# to open the logs and the port; the workers that answer requests run as www-data (pool "www").
if [ "$(id -u)" = "0" ]; then
  if [ ! -f .env ]; then cp .env.example .env; fi
  mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
  chown -R www-data:www-data storage bootstrap/cache .env
  setpriv --reuid=www-data --regid=www-data --init-groups "$0"
  exec php-fpm
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
php artisan migrate --force --no-interaction
if [ "${SEED_DEMO_DATA:-true}" = "true" ]; then
  php artisan db:seed --force --no-interaction
fi
# Configuration, routes and events are read once and cached, as in production (the API has no views).
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan event:cache --no-interaction
