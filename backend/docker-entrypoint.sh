#!/bin/sh
set -eu

cd /var/www

if [ ! -f .env ]; then
  if [ -f .env.docker ]; then
    cp .env.docker .env
  else
    cp .env.example .env
  fi
fi

set -a
. ./.env
set +a

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force --no-interaction
fi

php artisan config:clear || true

if [ "${DB_CONNECTION:-}" = "mysql" ] && [ -n "${DB_HOST:-}" ]; then
  echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."

  if php -r '
    $host = getenv("DB_HOST") ?: "mysql";
    $port = getenv("DB_PORT") ?: "3306";
    $database = getenv("DB_DATABASE") ?: "";
    $username = getenv("DB_USERNAME") ?: "";
    $password = getenv("DB_PASSWORD") ?: "";
    $attempts = 30;

    while ($attempts-- > 0) {
        try {
            new PDO(
                "mysql:host={$host};port={$port};dbname={$database}",
                $username,
                $password,
                [PDO::ATTR_TIMEOUT => 2]
            );
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, "MySQL not ready yet: {$exception->getMessage()}\n");
            sleep(2);
        }
    }

    exit(1);
  '; then
    php artisan migrate --force --no-interaction
  else
    echo "MySQL is unreachable; skipping migrations and starting the server anyway."
  fi
fi

exec php artisan serve --host=0.0.0.0 --port=8000
