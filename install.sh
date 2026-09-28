#!/bin/bash

# ==================================================================================
# ===         Gozar Automated Installer for Ubuntu 22.04 / 24.04 LTS          ===
# ===         https://github.com/dalroot/Gozar                                ===
# ==================================================================================

set -e

# ANSI Color Codes
GREEN='[0;32m'
YELLOW='[1;33m'
CYAN='[0;36m'
BLUE='[0;34m'
RED='[0;31m'
BOLD='[1m'
NC='[0m'

PROJECT_PATH="/var/www/gozar"
GITHUB_REPO="https://github.com/dalroot/Gozar.git"
PHP_VERSION="8.3"

clear
echo -e "${CYAN}=====================================================================${NC}"
echo -e "${BOLD}${CYAN}            ⚡️ Gozar - Automated Installation Script ⚡️         ${NC}"
echo -e "${CYAN}=====================================================================${NC}"
echo

# --- User Inputs ---
read -p "🌐 Enter your Domain or Subdomain (e.g. panel.example.com): " DOMAIN
DOMAIN=$(echo $DOMAIN | sed 's|http[s]*://||g' | sed 's|/.*||g')

if [ -z "$DOMAIN" ]; then
    echo -e "${RED}❌ Error: Domain cannot be empty!${NC}"
    exit 1
fi

read -p "🗃 Enter Database Name [default: gozarnet]: " DB_NAME
DB_NAME=${DB_NAME:-gozarnet}

read -p "👤 Enter Database Username [default: gozarnet]: " DB_USER
DB_USER=${DB_USER:-gozarnet}

while true; do
    read -s -p "🔑 Enter Database Password: " DB_PASS
    echo
    [ ! -z "$DB_PASS" ] && break
    echo -e "${RED}❌ Error: Database password cannot be empty.${NC}"
done

read -p "🤖 Enter Telegram Bot Token (optional, can be set later in Admin Panel): " BOT_TOKEN
read -p "✉️ Enter Admin Email (for Let's Encrypt SSL certificate): " ADMIN_EMAIL
echo

# --- Step 1: System Packages ---
echo -e "${YELLOW}[1/11] 📦 Installing essential system packages...${NC}"
export DEBIAN_FRONTEND=noninteractive
sudo apt-get update -y
sudo apt-get install -y git curl unzip software-properties-common gpg nginx mysql-server redis-server supervisor ufw certbot python3-certbot-nginx

# --- Step 2: Node.js LTS ---
echo -e "${YELLOW}[2/11] 📦 Setting up Node.js LTS...${NC}"
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
sudo apt-get install -y nodejs build-essential

# --- Step 3: PHP 8.3 & Extensions ---
echo -e "${YELLOW}[3/11] ☕ Installing PHP ${PHP_VERSION} and required extensions...${NC}"
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update -y
sudo apt-get install -y     php${PHP_VERSION} php${PHP_VERSION}-fpm php${PHP_VERSION}-cli     php${PHP_VERSION}-mysql php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml     php${PHP_VERSION}-curl php${PHP_VERSION}-zip php${PHP_VERSION}-bcmath     php${PHP_VERSION}-intl php${PHP_VERSION}-gd php${PHP_VERSION}-dom     php${PHP_VERSION}-redis

PHP_INI_PATH="/etc/php/${PHP_VERSION}/fpm/php.ini"
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 25M/' $PHP_INI_PATH
sudo sed -i 's/post_max_size = .*/post_max_size = 30M/' $PHP_INI_PATH
sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' $PHP_INI_PATH

# Composer
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}   Installing Composer...${NC}"
    php${PHP_VERSION} -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php${PHP_VERSION} composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

# Enable Services
sudo systemctl enable --now php${PHP_VERSION}-fpm nginx mysql redis-server supervisor

# Firewall rules
sudo ufw allow 'OpenSSH' > /dev/null 2>&1 || true
sudo ufw allow 'Nginx Full' > /dev/null 2>&1 || true
echo "y" | sudo ufw enable > /dev/null 2>&1 || true

# --- Step 4: Clone Repository ---
echo -e "${YELLOW}[4/11] ⬇️ Downloading Gozar repository...${NC}"
sudo rm -rf "$PROJECT_PATH"
sudo git clone $GITHUB_REPO $PROJECT_PATH
sudo chown -R www-data:www-data $PROJECT_PATH
cd $PROJECT_PATH

# --- Step 5: MySQL Database Setup ---
echo -e "${YELLOW}[5/11] 🗄 Configuring MySQL Database...${NC}"
sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
sudo mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# --- Step 6: Configure Environment (.env) ---
echo -e "${YELLOW}[6/11] ⚙️ Configuring .env file...${NC}"
sudo -u www-data cp .env.example .env
sudo sed -i "s|APP_NAME=.*|APP_NAME=Gozar|" .env
sudo sed -i "s|DB_CONNECTION=.*|DB_CONNECTION=mysql|" .env
sudo sed -i "s|DB_DATABASE=.*|DB_DATABASE=$DB_NAME|" .env
sudo sed -i "s|DB_USERNAME=.*|DB_USERNAME=$DB_USER|" .env
sudo sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASS|" .env
sudo sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|" .env
sudo sed -i "s|APP_ENV=.*|APP_ENV=production|" .env
sudo sed -i "s|QUEUE_CONNECTION=.*|QUEUE_CONNECTION=redis|" .env
if [ ! -z "$BOT_TOKEN" ]; then
    sudo sed -i "s|TELEGRAM_BOT_TOKEN=.*|TELEGRAM_BOT_TOKEN=$BOT_TOKEN|" .env
fi

# --- Step 7: Composer & NPM Build ---
echo -e "${YELLOW}[7/11] 🧰 Installing Composer dependencies...${NC}"
sudo -u www-data composer install --no-dev --optimize-autoloader

echo -e "${YELLOW}[8/11] 📦 Building frontend assets (NPM)...${NC}"
NPM_CACHE_DIR="/var/www/.npm"
sudo mkdir -p $NPM_CACHE_DIR
sudo chown -R www-data:www-data $NPM_CACHE_DIR
sudo chown -R www-data:www-data $PROJECT_PATH
sudo -u www-data npm install --cache $NPM_CACHE_DIR --legacy-peer-deps
sudo -u www-data npm run build

# Laravel setup
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --seed --force
sudo -u www-data php artisan storage:link

# --- Step 8: Nginx Web Server ---
echo -e "${YELLOW}[9/11] 🌐 Configuring Nginx web server...${NC}"
PHP_FPM_SOCK_PATH="/run/php/php${PHP_VERSION}-fpm.sock"

sudo tee /etc/nginx/sites-available/gozarnet >/dev/null <<EOF_NGINX
server {
    listen 80;
    server_name $DOMAIN;
    root $PROJECT_PATH/public;

    client_max_body_size 25M;
    index index.php;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:$PHP_FPM_SOCK_PATH;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
EOF_NGINX

sudo ln -sf /etc/nginx/sites-available/gozarnet /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx

# --- Step 9: Supervisor Queue Worker ---
echo -e "${YELLOW}[10/11] ⚙️ Configuring Background Queue Worker...${NC}"
sudo tee /etc/supervisor/conf.d/gozarnet-worker.conf >/dev/null <<EOF_SUP
[program:gozarnet-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_PATH/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/gozarnet-worker.log
EOF_SUP

sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all

# Setup Crontab Schedule
(crontab -l 2>/dev/null | grep -v 'schedule:run'; echo "* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# Optimization
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize

# --- Step 10: SSL Certificate ---
if [ ! -z "$ADMIN_EMAIL" ]; then
    echo -e "${YELLOW}[11/11] 🔒 Generating SSL Certificate (Let's Encrypt)...${NC}"
    sudo certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $ADMIN_EMAIL || true
fi

# Webhook Setup
if [ ! -z "$BOT_TOKEN" ]; then
    echo -e "${YELLOW}🤖 Setting up Telegram Webhook...${NC}"
    sudo -u www-data php artisan telegrambot:set-webhook || true
fi

echo
echo -e "${GREEN}=====================================================================${NC}"
echo -e "${BOLD}${GREEN}   🎉 Gozar has been successfully installed and configured! 🎉   ${NC}"
echo -e "${GREEN}=====================================================================${NC}"
echo -e "🌐 Web URL: ${CYAN}https://$DOMAIN${NC}"
echo -e "🔑 Admin Panel: ${CYAN}https://$DOMAIN/admin${NC}"
echo
echo -e "   - Default Email: ${YELLOW}admin@example.com${NC}"
echo -e "   - Default Password: ${YELLOW}password${NC}"
echo
echo -e "${RED}⚠️ IMPORTANT: Please change your admin password immediately after login!${NC}"
echo -e "${GREEN}=====================================================================${NC}"
