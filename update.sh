#!/bin/bash

# ==================================================================================
# ===          GozarNet Safe & Automated Updater for Ubuntu Server               ===
# ===          https://github.com/dalroot/GozarNet                               ===
# ==================================================================================

set -e

GREEN='[0;32m'
YELLOW='[1;33m'
CYAN='[0;36m'
RED='[0;31m'
BOLD='[1m'
NC='[0m'

PROJECT_PATH="${PWD}"
WEB_USER="www-data"

echo -e "${CYAN}=====================================================================${NC}"
echo -e "${BOLD}${CYAN}               🔄 GozarNet System Updater 🔄                         ${NC}"
echo -e "${CYAN}=====================================================================${NC}"

if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Error: .env file not found in current directory! Please run inside the project root.${NC}"
    exit 1
fi

echo

# Step 1: Backup .env & Maintenance mode
echo -e "${YELLOW}[1/6] 🛡 Creating .env backup & entering maintenance mode...${NC}"
sudo cp .env .env.bak.$(date +%Y-%m-%d_%H-%M-%S)
sudo -u $WEB_USER php artisan down || true

# Step 2: Fetch latest codes
echo -e "${YELLOW}[2/6] ⬇️ Fetching latest updates from GitHub repository...${NC}"
sudo git fetch origin
sudo git reset --hard origin/main

# Step 3: Permissions
echo -e "${YELLOW}[3/6] 🔐 Resetting proper file permissions...${NC}"
sudo chown -R $WEB_USER:$WEB_USER .
sudo chmod -R 775 storage bootstrap/cache

# Step 4: Composer dependencies
echo -e "${YELLOW}[4/6] 🧰 Updating PHP Composer packages...${NC}"
sudo -u $WEB_USER composer install --no-dev --optimize-autoloader

# Step 5: Frontend Build
echo -e "${YELLOW}[5/6] 📦 Rebuilding frontend assets (NPM)...${NC}"
NPM_CACHE_DIR="/var/www/.npm"
sudo mkdir -p $NPM_CACHE_DIR
sudo chown -R $WEB_USER:$WEB_USER $NPM_CACHE_DIR
sudo -u $WEB_USER npm install --cache $NPM_CACHE_DIR --legacy-peer-deps
sudo -u $WEB_USER npm run build

# Step 6: Database & Optimization
echo -e "${YELLOW}[6/6] ⚡️ Running migrations & clearing caches...${NC}"
sudo -u $WEB_USER php artisan migrate --force
sudo -u $WEB_USER php artisan optimize:clear
sudo -u $WEB_USER php artisan optimize
sudo -u $WEB_USER php artisan up || true

echo
echo -e "${GREEN}=====================================================================${NC}"
echo -e "${BOLD}${GREEN}   ✅ GozarNet has been successfully updated to the latest version!   ${NC}"
echo -e "${GREEN}=====================================================================${NC}"
