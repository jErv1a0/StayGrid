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

mkdir -p var/cache var/log var/sessions
chmod -R 777 var
chown -R www-data:www-data var

if [ ! -f vendor/autoload.php ]; then
  echo "vendor/autoload.php missing — running composer install..."

  if command -v composer >/dev/null 2>&1; then
    composer install \
      --no-dev \
      --prefer-dist \
      --no-interaction \
      --no-progress \
      --optimize-autoloader \
      --no-scripts
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
  echo "Waiting for database before booting Symfony commands..."

  MAX_RETRIES=80
  COUNT=0
  DB_READY=0

  while [ "$COUNT" -lt "$MAX_RETRIES" ]; do
    php -r '
      $url = getenv("DATABASE_URL") ?: getenv("MYSQL_URL") ?: getenv("MYSQL_DSN");
      if (!$url || str_starts_with($url, "${{")) { exit(1); }
      $p = parse_url($url);
      if ($p === false || empty($p["host"])) { exit(1); }
      $host = $p["host"] ?? getenv("MYSQL_HOST");
      $port = $p["port"] ?? (getenv("MYSQL_PORT") ?: 3306);
      $db = isset($p["path"]) ? ltrim($p["path"], "/") : getenv("MYSQL_DATABASE");
      $user = $p["user"] ?? getenv("MYSQL_USER");
      $pass = $p["pass"] ?? getenv("MYSQL_PASSWORD");

      try {
        new PDO(
          "mysql:host={$host};port={$port};dbname={$db}",
          $user,
          $pass,
          [PDO::ATTR_TIMEOUT => 2]
        );
        echo "1";
      } catch (Exception $e) {
        exit(1);
      }
    ' >/dev/null 2>&1 && DB_READY=1 && break || true

    COUNT=$((COUNT+1))
    echo "Database not ready yet ($COUNT/$MAX_RETRIES)..."
    sleep 3
  done

  if [ "$DB_READY" -ne 1 ]; then
    echo "WARNING: Database not reachable after $((MAX_RETRIES * 3)) seconds."
  fi

  echo "Clearing and warming Symfony cache..."

  php bin/console cache:clear \
    --env=$APP_ENV \
    --no-debug || true

  php bin/console cache:warmup \
    --env=$APP_ENV \
    --no-debug || true

  echo "Skipping automatic Doctrine migrations temporarily..."

  # TEMPORARILY DISABLED AUTO MIGRATIONS
  # This avoids crashes from already-existing tables
  MIGRATED=1
  echo "Migrations skipped."

) &

wait "$NGINX_PID"
