#!/usr/bin/env bash
set -e

PROJECT_DIR="/home/aonem/Desktop/APP/Esprit-5twin4-TechForGood-main"
cd "$PROJECT_DIR"

# 1. Install system dependencies (PHP, Composer, MariaDB) if missing
if ! command -v php >/dev/null 2>&1; then
  sudo apt update
  sudo apt install -y php php-cli php-mbstring php-xml php-curl php-zip php-mysql php-bcmath php-gd
fi

if ! command -v composer >/dev/null 2>&1; then
  sudo apt install -y composer
fi

if ! command -v mariadbd >/dev/null 2>&1 && ! command -v mysqld >/dev/null 2>&1; then
  sudo apt install -y mariadb-server
fi

# 2. Start MariaDB and create the database
sudo service mariadb start 2>/dev/null || sudo service mysql start 2>/dev/null || true
sudo mysql -u root -e "CREATE DATABASE IF NOT EXISTS textilecycle;"

# 3. PHP dependencies + env setup
composer install
[ -f .env ] || cp .env.example .env
php artisan key:generate --ansi
php artisan migrate --force

# 4. JS dependencies
npm install

# 5. Run the app (server + queue worker + logs + vite dev server)
composer run dev
