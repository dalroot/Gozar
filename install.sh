#!/bin/bash

# ==================================================================================
# ===            Gozar Web Setup Installer for Ubuntu 22.04 / 24.04 / 26.04      ===
# ===            https://github.com/dalroot/Gozar                                ===
# ==================================================================================

set -e

# ANSI Color Codes
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BLUE='\033[0;34m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m'

PROJECT_PATH="/var/www/gozar"
GITHUB_REPO="https://github.com/dalroot/Gozar.git"
DB_NAME="gozar_db"
DB_USER="gozar_user"
DB_PASS=$(openssl rand -hex 12)
SETUP_TOKEN=$(openssl rand -hex 16)
SETUP_PORT=8000

clear
echo -e "${CYAN}=====================================================================${NC}"
echo -e "${BOLD}${CYAN}               ⚡️ Gozar Engine - Fast Setup Installer ⚡️            ${NC}"
echo -e "${CYAN}=====================================================================${NC}"
echo

# Detect Public Server IP
SERVER_IP=$(curl -4 -s https://api.ipify.org || hostname -I | awk '{print $1}')

# --- Step 1: System Packages & Swap ---
echo -e "${YELLOW}[1/7] 📦 Preparing system packages...${NC}"
export DEBIAN_FRONTEND=noninteractive

# Add 2GB Swap if not present
if [ $(free -m | awk '/^Swap:/ {print $2}') -eq 0 ]; then
    echo -e "${YELLOW}   Configuring 2GB Swap Memory for optimal stability...${NC}"
    fallocate -l 2G /swapfile 2>/dev/null || dd if=/dev/zero of=/swapfile bs=1M count=2048
    chmod 600 /swapfile
    mkswap /swapfile > /dev/null
    swapon /swapfile > /dev/null
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

# Clean up broken / unneeded PPAs for Ubuntu 26.04+ compatibility
rm -f /etc/apt/sources.list.d/*ondrej* 2>/dev/null || true
apt-get update -y
apt-get install -y git curl unzip software-properties-common gpg nginx mysql-server redis-server supervisor ufw certbot python3-certbot-nginx

# --- Step 2: Node.js LTS ---
echo -e "${YELLOW}[2/7] 📦 Installing Node.js LTS...${NC}"
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
    apt-get install -y nodejs build-essential
fi

# --- Step 3: PHP Installation (Auto-detecting available repo or native) ---
echo -e "${YELLOW}[3/7] ☕ Installing PHP and required extensions...${NC}"
UBUNTU_CODENAME=$(lsb_release -cs 2>/dev/null || echo "noble")

if [ "$UBUNTU_CODENAME" = "jammy" ] || [ "$UBUNTU_CODENAME" = "noble" ]; then
    add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
    apt-get update -y
fi

# Try installing PHP 8.3, fallback to default php
if apt-cache show php8.3 > /dev/null 2>&1; then
    PHP_VERSION="8.3"
else
    PHP_VERSION=$(apt-cache show php | grep -m1 "Version:" | awk '{print $2}' | cut -d'.' -f1,2)
    [ -z "$PHP_VERSION" ] && PHP_VERSION="8.3"
fi

apt-get install -y \
    php${PHP_VERSION} php${PHP_VERSION}-fpm php${PHP_VERSION}-cli \
    php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-intl php${PHP_VERSION}-gd php${PHP_VERSION}-dom \
    php${PHP_VERSION}-redis || apt-get install -y php php-fpm php-cli php-mysql php-mbstring php-xml php-curl php-zip php-bcmath php-intl php-gd php-redis

PHP_FPM_SERVICE=$(systemctl list-unit-files | grep -oE "php[0-9.]*-fpm" | head -n 1)
[ -z "$PHP_FPM_SERVICE" ] && PHP_FPM_SERVICE="php8.3-fpm"

# Composer
if ! command -v composer &> /dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# Enable core services
systemctl enable --now $PHP_FPM_SERVICE nginx mysql redis-server supervisor

# Firewall rules
ufw allow 'OpenSSH' > /dev/null 2>&1 || true
ufw allow 'Nginx Full' > /dev/null 2>&1 || true
ufw allow $SETUP_PORT/tcp > /dev/null 2>&1 || true
echo "y" | ufw enable > /dev/null 2>&1 || true

# --- Step 4: Clone / Setup Codebase ---
echo -e "${YELLOW}[4/7] ⬇️ Downloading Gozar repository...${NC}"
rm -rf "$PROJECT_PATH"
git clone $GITHUB_REPO $PROJECT_PATH
chown -R www-data:www-data $PROJECT_PATH
cd $PROJECT_PATH

# --- Step 5: Database Creation ---
echo -e "${YELLOW}[5/7] 🗄 Preparing MySQL Database...${NC}"
mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Create .env
cp .env.example .env
sed -i "s|APP_NAME=.*|APP_NAME=Gozar|" .env
sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USER|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" .env
sed -i "s|APP_URL=.*|APP_URL=http://$SERVER_IP:$SETUP_PORT|" .env
sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env

# --- Step 6: Dependencies & Migrations ---
echo -e "${YELLOW}[6/7] 🧰 Installing dependencies and preparing database...${NC}"
mkdir -p /var/www/.cache/composer
mkdir -p "$PROJECT_PATH/storage/framework/views"
mkdir -p "$PROJECT_PATH/storage/framework/cache/data"
mkdir -p "$PROJECT_PATH/storage/framework/sessions"
mkdir -p "$PROJECT_PATH/storage/logs"
mkdir -p "$PROJECT_PATH/bootstrap/cache"
chown -R www-data:www-data /var/www/.cache "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache"
chmod -R 775 "$PROJECT_PATH/storage" "$PROJECT_PATH/bootstrap/cache"

sudo -u www-data COMPOSER_HOME="/var/www/.cache/composer" composer install --no-dev --optimize-autoloader --ignore-platform-reqs
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --seed --force
sudo -u www-data php artisan storage:link

# Store setup token
echo "$SETUP_TOKEN" > "$PROJECT_PATH/storage/setup_token"
chown www-data:www-data "$PROJECT_PATH/storage/setup_token"
chmod 644 "$PROJECT_PATH/storage/setup_token"
rm -f "$PROJECT_PATH/storage/installed" 2>/dev/null || true

# Setup Supervisor Worker
tee /etc/supervisor/conf.d/gozar-worker.conf >/dev/null <<EOF_SUP
[program:gozar-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_PATH/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/gozar-worker.log
EOF_SUP

supervisorctl reread > /dev/null 2>&1 || true
supervisorctl update > /dev/null 2>&1 || true
supervisorctl start all > /dev/null 2>&1 || true

# Crontab schedule
(crontab -l 2>/dev/null | grep -v 'schedule:run'; echo "* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# --- Step 7: Launch Setup Wizard ---
echo -e "${YELLOW}[7/7] 🚀 Launching Web Setup Wizard...${NC}"
killall -9 php 2>/dev/null || true
nohup sudo -u www-data php -S 0.0.0.0:$SETUP_PORT -t "$PROJECT_PATH/public" "$PROJECT_PATH/server.php" > /tmp/gozar_wizard.log 2>&1 &

echo
echo -e "${GREEN}=====================================================================${NC}"
echo -e "${BOLD}${GREEN}        🎉 Gozar Engine is Ready for Web Configuration! 🎉           ${NC}"
echo -e "${GREEN}=====================================================================${NC}"
echo
echo -e "👉 Please open the following URL in your web browser to finish setup:"
echo
echo -e "   ${BOLD}${CYAN}http://$SERVER_IP:$SETUP_PORT/setup?token=$SETUP_TOKEN${NC}"
echo
echo -e "${YELLOW}ℹ️  All steps (Domain, SSL, Bot Token, Admin Password) will be${NC}"
echo -e "${YELLOW}    configured easily and verified through the Web Wizard!${NC}"
echo -e "${GREEN}=====================================================================${NC}"
echo
