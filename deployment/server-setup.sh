#!/usr/bin/env bash
# ============================================================
# EdLibya Server Setup Script (Ubuntu 22.04+ VPS)
# ============================================================
# Run once on a fresh server:
#   curl -sSL https://raw.githubusercontent.com/edlibya/deploy/main/server-setup.sh | bash
# ============================================================

set -euo pipefail

echo "=========================================="
echo "  EdLibya Server Setup"
echo "=========================================="

# System updates
echo "[1/8] Updating system packages..."
sudo apt update && sudo apt upgrade -y

# PHP 8.2 + extensions
echo "[2/8] Installing PHP 8.2..."
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis \
    php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd \
    php8.2-bcmath php8.2-intl php8.2-dom

# Composer
echo "[3/8] Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# MySQL
echo "[4/8] Installing MySQL..."
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql
echo ""
echo "  ⚠️  Run 'sudo mysql_secure_installation' after setup"
echo "  Then create DB:"
echo "    CREATE DATABASE edlibya_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "    CREATE USER 'edlibya_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';"
echo "    GRANT ALL PRIVILEGES ON edlibya_production.* TO 'edlibya_user'@'localhost';"
echo "    FLUSH PRIVILEGES;"
echo ""

# Redis
echo "[5/8] Installing Redis..."
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Nginx
echo "[6/8] Installing Nginx..."
sudo apt install -y nginx
sudo systemctl enable nginx

# SSL via Certbot
echo "[7/8] Installing Certbot..."
sudo apt install -y certbot python3-certbot-nginx
echo "  Run: sudo certbot --nginx -d api.edlibya.ly"

# Application directory
echo "[8/8] Setting up application directory..."
sudo mkdir -p /var/www/edlibya
sudo chown -R www-data:www-data /var/www/edlibya
sudo mkdir -p /var/log/edlibya

echo ""
echo "=========================================="
echo "  ✅ Server setup complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "  1. Clone the repo to /var/www/edlibya/"
echo "  2. Copy .env.production to .env and fill values"
echo "  3. Run: php artisan key:generate"
echo "  4. Run: php artisan migrate --force"
echo "  5. Copy nginx config and enable site"
echo "  6. Run: sudo certbot --nginx -d api.edlibya.ly"
echo "  7. Copy and enable queue worker systemd service"
echo "  8. Run: bash deployment/deploy.sh"
