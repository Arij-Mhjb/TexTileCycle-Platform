#!/bin/sh
set -eu
: "${APP_KEY:?Set APP_KEY in Render environment variables}"
: "${DB_CONNECTION:?Set DB_CONNECTION to pgsql and configure the database}"
if ! php -r 'exit(extension_loaded("curl") ? 0 : 1);'; then
  echo "PHP cURL extension is required for Cloudinary uploads." >&2
  exit 1
fi
if [ -z "${CLOUDINARY_URL:-}" ] && { [ -z "${CLOUDINARY_CLOUD_NAME:-}" ] || [ -z "${CLOUDINARY_API_KEY:-}" ] || [ -z "${CLOUDINARY_API_SECRET:-}" ]; }; then
  echo "Set CLOUDINARY_URL or the three CLOUDINARY_CLOUD_NAME/API_KEY/API_SECRET variables in Render." >&2
  exit 1
fi
if [ "$DB_CONNECTION" = "sqlite" ]; then
  echo "Use an external persistent database on Render; SQLite data is ephemeral." >&2
  exit 1
fi
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
php artisan config:cache
php artisan migrate --force
if [ "${RUN_SEED:-false}" = "true" ]; then
  echo "RUN_SEED=true: loading demonstration data..."
  php artisan db:seed --force
fi
php artisan view:cache
php artisan storage:link
sed -i "s/Listen 80/Listen ${PORT:-10000}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT:-10000}>/" /etc/apache2/sites-available/000-default.conf
exec /usr/bin/supervisord -c /var/www/html/docker/supervisord.conf
