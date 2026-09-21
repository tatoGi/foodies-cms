#!/bin/bash
set -e # Exit immediately if a command exits with a non-zero status.

# --- Configuration ---
# PLEASE FILL IN THESE VALUES
REPO_URL="<your-repository-url>" # e.g., git@github.com:user/repo.git
LARAVEL_DIR="/var/www/larave_cms_block"
API_DOMAIN="api.yourdomain.com"
FRONTEND_DOMAIN="yourdomain.com"
DB_DATABASE="your_db_name"
DB_USERNAME="your_db_user"
DB_PASSWORD="your_db_password"

# --- Script ---

function setup_server() {
    echo "--- 1. Setting up server requirements ---"

    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
      echo "Please run the server setup part of this script as root or with sudo."
      exit 1
    fi

    echo "Updating package list..."
    apt update

    echo "Installing Nginx, MySQL, Git..."
    apt install -y nginx mysql-server git

    echo "Adding repository for PHP 8.4..."
    add-apt-repository ppa:ondrej/php -y
    apt update

    echo "Installing PHP 8.4 and extensions..."
    apt install -y php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml php8.4-zip php8.4-curl php8.4-gd php8.4-bcmath

    echo "Installing Composer..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    php -r "unlink('composer-setup.php');"

    echo "Installing Node.js v20 and npm..."
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y nodejs

    echo "Installing Supervisor..."
    apt install -y supervisor

    echo "Server setup complete."
}

function deploy_laravel() {
    echo "--- 2. Deploying Laravel Backend ---"

    if [ -d "$LARAVEL_DIR" ]; then
        echo "Laravel directory already exists. Skipping clone."
    else
        echo "Cloning repository..."
        sudo git clone "$REPO_URL" "$LARAVEL_DIR"
    fi

    cd "$LARAVEL_DIR"

    echo "Installing Composer dependencies..."
    sudo composer install --optimize-autoloader --no-dev

    echo "Installing NPM dependencies..."
    sudo npm install

    echo "Configuring environment..."
    if [ -f ".env" ]; then
        echo ".env file already exists. Please ensure it is configured correctly."
    else
        sudo cp .env.example .env
        echo "Created .env file. Please edit it with your production settings."
        # Using sed to set some values. User should review this.
        sudo sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
        sudo sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
        sudo sed -i "s|^APP_URL=.*|APP_URL=https://$API_DOMAIN|" .env
        sudo sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_DATABASE|" .env
        sudo sed -i "s|^DB_USERNAME=.*|DB_USERNAME=$DB_USERNAME|" .env
        sudo sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env
        sudo sed -i "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=database|" .env
    fi

    sudo php artisan key:generate

    echo "Running migrations and linking storage..."
    sudo php artisan migrate --force
    sudo php artisan storage:link

    echo "Building assets and optimizing..."
    sudo npm run build
    sudo php artisan config:cache
    sudo php artisan route:cache
    sudo php artisan view:cache

    echo "Setting permissions..."
    sudo chown -R www-data:www-data "$LARAVEL_DIR"
    sudo chmod -R 775 "$LARAVEL_DIR/storage"
    sudo chmod -R 775 "$LARAVEL_DIR/bootstrap/cache"

    echo "Laravel deployment complete."
}

function configure_nginx() {
    echo "--- Configuring Nginx ---"

    if [ "$EUID" -ne 0 ]; then
      echo "Please run this part of the script as root or with sudo."
      exit 1
    fi

    echo "Creating Nginx config for API: $API_DOMAIN"
    cat > /etc/nginx/sites-available/$API_DOMAIN <<EOF
server {
    listen 80;
    server_name $API_DOMAIN;
    root $LARAVEL_DIR/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

    echo "Creating Nginx config for Frontend: $FRONTEND_DOMAIN"
    cat > /etc/nginx/sites-available/$FRONTEND_DOMAIN <<EOF
server {
    listen 80;
    server_name $FRONTEND_DOMAIN;

    location / {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_cache_bypass \$http_upgrade;
    }
}
EOF

    echo "Enabling sites..."
    ln -sf /etc/nginx/sites-available/$API_DOMAIN /etc/nginx/sites-enabled/
    ln -sf /etc/nginx/sites-available/$FRONTEND_DOMAIN /etc/nginx/sites-enabled/

    echo "Restarting Nginx..."
    systemctl restart nginx

    echo "Nginx configuration complete."
}

function deploy_nextjs() {
    echo "--- 3. Deploying Next.js Frontend ---"
    cd "$LARAVEL_DIR/frontend/newhome"

    echo "Installing dependencies..."
    sudo npm ci

    echo "Building Next.js app..."
    sudo npm run build

    echo "Next.js deployment complete. Supervisor will start it."
}

function configure_supervisor() {
    echo "--- Configuring Supervisor ---"

    if [ "$EUID" -ne 0 ]; then
      echo "Please run this part of the script as root or with sudo."
      exit 1
    fi

    echo "Creating Supervisor config for Laravel Worker"
    cat > /etc/supervisor/conf.d/laravel-worker.conf <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $LARAVEL_DIR/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=$LARAVEL_DIR/storage/logs/worker.log
EOF

    echo "Creating Supervisor config for Next.js Frontend"
    cat > /etc/supervisor/conf.d/nextjs-frontend.conf <<EOF
[program:nextjs-frontend]
command=npm start
directory=$LARAVEL_DIR/frontend/newhome
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=$LARAVEL_DIR/storage/logs/frontend.log
environment=NODE_ENV="production"
EOF

    echo "Updating Supervisor..."
    supervisorctl reread
    supervisorctl update
    echo "Starting Supervisor processes..."
    supervisorctl start laravel-worker:*
    supervisorctl start nextjs-frontend

    echo "Supervisor configuration complete."
}

case "$1" in
    setup)
        setup_server
        ;;
    deploy)
        deploy_laravel
        deploy_nextjs
        ;;
    configure)
        configure_nginx
        configure_supervisor
        ;;
    all)
        setup_server
        deploy_laravel
        deploy_nextjs
        configure_nginx
        configure_supervisor
        ;;
    *)
        echo "Usage: $0 {setup|deploy|configure|all}"
        echo "  setup:      Run initial server software installation (requires root)."
        echo "  deploy:     Deploy application code (requires root/sudo)."
        echo "  configure:  Configure Nginx and Supervisor (requires root)."
        echo "  all:        Run all steps (requires root)."
        exit 1
esac

echo "--- Deployment script finished ---"
echo "Verification steps:"
echo " - Your Laravel admin panel should be accessible at http://$API_DOMAIN/admin"
echo " - Your Next.js frontend should be accessible at http://$FRONTEND_DOMAIN"
echo " - Check process status with: supervisorctl status"
echo " - Remember to set up SSL with Let's Encrypt for both domains for a secure production environment."
