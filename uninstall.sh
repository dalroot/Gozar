#!/bin/bash

# ==================================================================================
# ===          GozarNet Complete & Clean Uninstaller for Ubuntu Server           ===
# ==================================================================================

set -e

GREEN='[0;32m'
YELLOW='[1;33m'
RED='[0;31m'
BOLD='[1m'
NC='[0m'

PROJECT_PATH="/var/www/gozarnet"

echo -e "${RED}=====================================================================${NC}"
echo -e "${BOLD}${RED}              ⚠️ GozarNet Uninstaller ⚠️                             ${NC}"
echo -e "${RED}=====================================================================${NC}"
echo -e "${YELLOW}WARNING: This action is irreversible. It will delete all files, configs and database.${NC}"
echo

read -p "🌐 Enter site domain to remove Nginx/SSL configuration: " DOMAIN
read -p "🗃 Enter MySQL Database name to drop: " DB_NAME
read -p "👤 Enter MySQL Username to drop: " DB_USER
echo

read -p "Are you sure you want to permanently remove GozarNet? (y/n): " CONFIRMATION
if [[ "$CONFIRMATION" != "y" && "$CONFIRMATION" != "Y" ]]; then
    echo -e "${YELLOW}Operation cancelled.${NC}"
    exit 0
fi

# 1. Stop services
echo -e "${YELLOW}[1/4] Stopping workers and web services...${NC}"
sudo supervisorctl stop gozarnet-worker:* 2>/dev/null || true
sudo systemctl stop nginx 2>/dev/null || true

# 2. Remove configurations
echo -e "${YELLOW}[2/4] Removing Nginx & Supervisor configuration files...${NC}"
sudo rm -f /etc/nginx/sites-available/gozarnet /etc/nginx/sites-enabled/gozarnet
sudo rm -f /etc/supervisor/conf.d/gozarnet-worker.conf
sudo supervisorctl reread 2>/dev/null || true
sudo supervisorctl update 2>/dev/null || true
sudo systemctl start nginx 2>/dev/null || true

# 3. Remove project files
echo -e "${YELLOW}[3/4] Removing project files from $PROJECT_PATH...${NC}"
if [ -d "$PROJECT_PATH" ]; then
    sudo rm -rf "$PROJECT_PATH"
    echo -e "${GREEN}Project directory removed.${NC}"
fi

# 4. Drop database
echo -e "${YELLOW}[4/4] Dropping MySQL database and user...${NC}"
if [ ! -z "$DB_NAME" ]; then
    sudo mysql -e "DROP DATABASE IF EXISTS \`$DB_NAME\`;" 2>/dev/null || true
fi
if [ ! -z "$DB_USER" ]; then
    sudo mysql -e "DROP USER IF EXISTS '$DB_USER'@'localhost';" 2>/dev/null || true
fi

echo
echo -e "${GREEN}=====================================================================${NC}"
echo -e "${BOLD}${GREEN}   ✅ GozarNet has been completely removed from the system.          ${NC}"
echo -e "${GREEN}=====================================================================${NC}"
