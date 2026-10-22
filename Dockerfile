FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build \
    && test -f public/build/manifest.json \
    && find public/build/assets -maxdepth 1 -type f -name 'vision_bundle-*.js' -print -quit | grep -q .

FROM php:8.3-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends \
    libcurl4-openssl-dev libpq-dev libonig-dev libzip-dev unzip supervisor \
    tesseract-ocr tesseract-ocr-fra tesseract-ocr-eng tesseract-ocr-tur \
    imagemagick zbar-tools \
    && docker-php-ext-install curl pdo_pgsql pdo_mysql mbstring zip bcmath \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY . .
# RUN_SEED may execute Laravel factories at container startup; Faker must remain available.
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel \
    && printf 'upload_max_filesize=12M\npost_max_size=40M\nmax_file_uploads=10\nmemory_limit=256M\n' > /usr/local/etc/php/conf.d/uploads.ini \
    && chmod +x docker/start.sh
COPY --from=assets /app/public/build ./public/build
RUN test -f public/build/manifest.json \
    && find public/build/assets -maxdepth 1 -type f -name 'vision_bundle-*.js' -print -quit | grep -q .
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr
EXPOSE 10000
CMD ["sh", "docker/start.sh"]
