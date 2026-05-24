#!/bin/sh
set -e

cd /var/www/staygrid

export PORT="${PORT:-80}"
export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"
export DEFAULT_URI="${DEFAULT_URI:-http://localhost}"

echo "Starting container..."

if [ ! -f .env ]; then
  echo "Creating fallback .env file..."
  cat > .env <<EOF
APP_ENV=$APP_ENV
APP_DEBUG=$APP_DEBUG
DEFAULT_URI=$DEFAULT_URI
EOF
fi

cp /var/www/staygrid/nginx-main.conf /etc/nginx/nginx.conf

rm -f \
  /etc/nginx/sites-enabled/default \
  /etc/nginx/sites-available/default \
  /etc/nginx/conf.d/default.conf

envsubst '$PORT' < /var/www/staygrid/nginx.conf > /etc/nginx/conf.d/default.conf

echo "Preparing writable Symfony directories..."

mkdir -p var/cache var/log var/sessions

chmod -R 777 var || true
chown -R www-data:www-data var || true

if [ ! -f vendor/autoload.php ]; then
  echo "vendor/autoload.php missing — running composer install..."

  if command -v composer >/dev/null 2>&1; then
    composer install \
      --no-dev \
      --prefer-dist \
      --no-interaction \
      --no-progress \
      --optimize-autoloader
  else
    echo "ERROR: composer not available."
    exit 1
  fi
fi

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx..."
nginx -g 'daemon off;' &

NGINX_PID=$!

(
  echo "Waiting for database..."

  MAX_RETRIES=80
  COUNT=0
  DB_READY=0

  while [ "$COUNT" -lt "$MAX_RETRIES" ]; do

    php -r '
      $url = getenv("DATABASE_URL");

      if (!$url) {
        exit(1);
      }

      $p = parse_url($url);

      if ($p === false || empty($p["host"])) {
        exit(1);
      }

      $host = $p["host"];
      $port = $p["port"] ?? 3306;
      $db   = ltrim($p["path"], "/");
      $user = $p["user"];
      $pass = $p["pass"];

      try {
        new PDO(
          "mysql:host={$host};port={$port};dbname={$db}",
          $user,
          $pass,
          [PDO::ATTR_TIMEOUT => 3]
        );

        echo "connected";
      } catch (Exception $e) {
        exit(1);
      }
    ' >/dev/null 2>&1 && DB_READY=1 && break || true

    COUNT=$((COUNT+1))

    echo "Database not ready yet ($COUNT/$MAX_RETRIES)..."

    sleep 3
  done

  if [ "$DB_READY" -ne 1 ]; then
    echo "ERROR: Database connection failed."
    exit 1
  fi

  echo "Clearing Symfony cache..."

  rm -rf var/cache/* || true

  php bin/console cache:clear \
    --env=$APP_ENV \
    --no-debug || true

  php bin/console cache:warmup \
    --env=$APP_ENV \
    --no-debug || true

  echo "Syncing Doctrine migration metadata..."

  php bin/console doctrine:migrations:sync-metadata-storage \
    --no-interaction || true

  echo "Running Doctrine migrations..."

  php bin/console doctrine:migrations:migrate \
    --no-interaction \
    --allow-no-migration || true

  echo "Symfony boot completed."

) &

wait "$NGINX_PID"
