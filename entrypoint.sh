#!/bin/sh
set -e

cd /var/www/staygrid

export PORT="${PORT:-80}"
export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"
export DEFAULT_URI="${DEFAULT_URI:-http://localhost}"

echo "Starting container..."

# Ensure .env exists for Symfony
if [ ! -f .env ]; then
  echo "Creating fallback .env file..."
  cat > .env <<EOF
APP_ENV=$APP_ENV
APP_DEBUG=$APP_DEBUG
DEFAULT_URI=$DEFAULT_URI
EOF
fi

# Configure nginx
cp /var/www/staygrid/nginx-main.conf /etc/nginx/nginx.conf

rm -f \
  /etc/nginx/sites-enabled/default \
  /etc/nginx/sites-available/default \
  /etc/nginx/conf.d/default.conf

envsubst '$PORT' < /var/www/staygrid/nginx.conf > /etc/nginx/conf.d/default.conf

# Ensure required Symfony directories exist
mkdir -p var/cache var/log var/sessions
chown -R www-data:www-data var

# Install dependencies only if missing
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

# Wait for MySQL before any Symfony console command can touch Doctrine.
echo "Waiting for database before booting Symfony commands..."

MAX_RETRIES=80
COUNT=0
DB_READY=0

while [ "$COUNT" -lt "$MAX_RETRIES" ]; do
  php -r '
    $url = getenv("DATABASE_URL");
    if (!$url) { exit(1); }
    $p = parse_url($url);
    $host = $p["host"] ?? getenv("MYSQL_HOST");
    $port = $p["port"] ?? (getenv("MYSQL_PORT") ?: 3306);
    $db = isset($p["path"]) ? ltrim($p["path"], "/") : getenv("MYSQL_DATABASE");
    $user = $p["user"] ?? getenv("MYSQL_USER");
    $pass = $p["pass"] ?? getenv("MYSQL_PASSWORD");
    try {
      new PDO("mysql:host={$host};port={$port};dbname={$db}", $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
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
  echo "ERROR: Database not reachable after $((MAX_RETRIES * 3)) seconds. Exiting."
  exit 1
fi

# Start PHP-FPM
echo "Starting PHP-FPM..."
php-fpm -D

# Warm Symfony cache
echo "Clearing and warming Symfony cache..."

php bin/console cache:clear \
  --env=$APP_ENV \
  --no-debug || true

php bin/console cache:warmup \
  --env=$APP_ENV \
  --no-debug || true

# Prepare DB and run migrations after DB is reachable
echo "Preparing database and running Doctrine migrations..."

# Run migrations with retries; allow no migration to succeed
COUNT=0
MIGRATED=0
while [ "$COUNT" -lt "$MAX_RETRIES" ]; do
  if php bin/console doctrine:migrations:sync-metadata-storage --no-interaction >/dev/null 2>&1; then
    :
  fi

  if php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration; then
    MIGRATED=1
    echo "Migrations completed."
    break
  fi

  COUNT=$((COUNT+1))
  echo "Migration attempt failed ($COUNT/$MAX_RETRIES), retrying..."
  sleep 2
done

if [ "$MIGRATED" -ne 1 ]; then
  echo "ERROR: Could not apply migrations after $MAX_RETRIES retries. Exiting to avoid serving broken app."
  exit 1
fi

# Start nginx
echo "Starting Nginx..."
exec nginx -g 'daemon off;'