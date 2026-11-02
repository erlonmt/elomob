#!/bin/sh
set -eu
if [ ! -f .env ]; then cp .env.example .env; fi
mkdir -p storage/app storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
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
php artisan config:clear --no-interaction
php artisan migrate --force --no-interaction
if [ "${SEED_DEMO_DATA:-true}" = "true" ]; then
  php artisan db:seed --force --no-interaction
fi
# The built-in PHP server answers one request at a time unless it forks workers.
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload --no-interaction
