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

# Determine DB connection info from DATABASE_URL or fallback envs
DB_HOST="${DB_HOST:-}"
DB_PORT="${DB_PORT:-}"
DB_USER="${DB_USER:-}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-}"

if [ -n "$DATABASE_URL" ]; then
  # extract user:pass and host:port/db from DATABASE_URL like mysql://user:pass@host:port/dbname
  CREDS=$(echo "$DATABASE_URL" | sed -E 's#.*//([^@]+)@.*#\1#' 2>/dev/null || true)
  HOSTPORT=$(echo "$DATABASE_URL" | sed -E 's#.*@([^/]+).*#\1#' 2>/dev/null || true)
  DB_USER=$(echo "$CREDS" | cut -d':' -f1)
  DB_PASS=$(echo "$CREDS" | cut -d':' -f2-)
  DB_HOST=$(echo "$HOSTPORT" | cut -d':' -f1)
  DB_PORT=$(echo "$HOSTPORT" | cut -d':' -f2)
  DB_NAME=$(echo "$DATABASE_URL" | sed -E 's#.*//[^@]+@[^/]+/([^?]+).*#\1#' 2>/dev/null || true)
fi

DB_HOST=${DB_HOST:-${MYSQL_HOST:-mysql}}
DB_PORT=${DB_PORT:-${MYSQL_PORT:-3306}}
DB_USER=${DB_USER:-${MYSQL_USER:-root}}
DB_PASS=${DB_PASS:-${MYSQL_ROOT_PASSWORD:-}}
DB_NAME=${DB_NAME:-${MYSQL_DATABASE:-staygrid}}

MAX_RETRIES=60
COUNT=0
DB_READY=0

echo "Waiting for database at $DB_HOST:$DB_PORT (user: $DB_USER)"
while [ "$COUNT" -lt "$MAX_RETRIES" ]; do
  php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'), [PDO::ATTR_TIMEOUT => 2]); echo '1'; } catch (Exception \$e) { exit(1); }" \
    DB_HOST="$DB_HOST" DB_PORT="$DB_PORT" DB_USER="$DB_USER" DB_PASS="$DB_PASS" DB_NAME="$DB_NAME" >/dev/null 2>&1 && DB_READY=1 && break || true

  COUNT=$((COUNT+1))
  echo "Database not ready yet ($COUNT/$MAX_RETRIES)..."
  sleep 2
done

if [ "$DB_READY" -ne 1 ]; then
  echo "ERROR: Database at $DB_HOST:$DB_PORT not reachable after $MAX_RETRIES seconds. Exiting."
  exit 1
fi

# Run composer auto-scripts now that DB is reachable
if command -v composer >/dev/null 2>&1; then
  echo "Running composer auto-scripts..."
  composer run-script symfony-cmd --no-interaction || true
fi

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