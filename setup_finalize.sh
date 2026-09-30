#!/bin/bash
DOMAIN="$1"
ADMIN_EMAIL="$2"
PROJECT_PATH="/var/www/gozar"

if [ -z "$DOMAIN" ]; then
    exit 1
fi

# Detect PHP-FPM socket dynamically
PHP_FPM_SOCK=$(find /run/php/ -name "php*-fpm.sock" 2>/dev/null | head -n 1)
if [ -z "$PHP_FPM_SOCK" ]; then
    PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "8.3")
    PHP_FPM_SOCK="/run/php/php${PHP_VERSION}-fpm.sock"
fi

# Check if Nginx uses stream SNI multiplexing (e.g. coexisting with X-UI / V2Ray)
HAS_STREAM=0
if grep -q "stream {" /etc/nginx/nginx.conf 2>/dev/null; then
    HAS_STREAM=1
fi

# Existing wildcard SSL certificates check (e.g. from X-UI or certbot)
EXISTING_SSL_CERT=""
EXISTING_SSL_KEY=""
if [ -f "/etc/x-ui/certs/fullchain.pem" ] && [ -f "/etc/x-ui/certs/privkey.pem" ]; then
    EXISTING_SSL_CERT="/etc/x-ui/certs/fullchain.pem"
    EXISTING_SSL_KEY="/etc/x-ui/certs/privkey.pem"
elif [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    EXISTING_SSL_CERT="/etc/letsencrypt/live/$DOMAIN/fullchain.pem"
    EXISTING_SSL_KEY="/etc/letsencrypt/live/$DOMAIN/privkey.pem"
fi

if [ "$HAS_STREAM" -eq 1 ]; then
    # Add domain to stream map if not present
    if ! grep -q "$DOMAIN" /etc/nginx/nginx.conf; then
        sed -i "s/map \$ssl_preread_server_name \$backend {/map \$ssl_preread_server_name \$backend\n        $DOMAIN    127.0.0.1:9443;/" /etc/nginx/nginx.conf
    fi

    # Write Gozar config to conf.d
    cat << EOF_NGINX > /etc/nginx/conf.d/gozar.conf
server {
    listen 80;
    server_name $DOMAIN;
    return 301 https://\$host\$request_uri;
}

server {
    listen 127.0.0.1:9443 ssl;
    http2 on;
    server_name $DOMAIN;
    root $PROJECT_PATH/public;

    ssl_certificate ${EXISTING_SSL_CERT:-/etc/x-ui/certs/fullchain.pem};
    ssl_certificate_key ${EXISTING_SSL_KEY:-/etc/x-ui/certs/privkey.pem};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    client_max_body_size 50M;
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

else
    # Standard Nginx standalone configuration
    cat << EOF_NGINX > /etc/nginx/sites-available/gozar
server {
    listen 80;
    server_name $DOMAIN;
    root $PROJECT_PATH/public;

    client_max_body_size 50M;
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

    # Request SSL via Certbot if email provided and no existing cert
    if [ ! -z "$ADMIN_EMAIL" ] && [ -z "$EXISTING_SSL_CERT" ]; then
        certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $ADMIN_EMAIL || true
    fi
fi

nginx -t && systemctl reload nginx

# Set Telegram Webhook
cd $PROJECT_PATH
sudo -u www-data php artisan telegrambot:set-webhook || true
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan optimize

# Close Setup Wizard port from public firewall
(sleep 5 && fuser -k 8000/tcp > /dev/null 2>&1 && ufw delete allow 8000/tcp > /dev/null 2>&1) &

