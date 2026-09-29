#!/bin/bash
DOMAIN="$1"
ADMIN_EMAIL="$2"
PROJECT_PATH="/var/www/gozar"
PHP_VERSION="8.3"
PHP_FPM_SOCK="/run/php/php${PHP_VERSION}-fpm.sock"

if [ -z "$DOMAIN" ]; then
    exit 1
fi

# Configure Nginx for Domain
cat << EOF_NGINX > /etc/nginx/sites-available/gozar
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
        fastcgi_pass unix:$PHP_FPM_SOCK;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. {
        deny all;
    }
}
EOF_NGINX

ln -sf /etc/nginx/sites-available/gozar /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/gozarnet 2>/dev/null || true
rm -f /etc/nginx/sites-enabled/default 2>/dev/null || true
nginx -t && systemctl reload nginx

# Request SSL via Certbot if email provided
if [ ! -z "$ADMIN_EMAIL" ]; then
    certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $ADMIN_EMAIL || true
fi

# Set Telegram Webhook
cd $PROJECT_PATH
sudo -u www-data php artisan telegrambot:set-webhook || true
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize

# Close Setup Wizard port from public firewall
ufw delete allow 8000/tcp > /dev/null 2>&1 || true
