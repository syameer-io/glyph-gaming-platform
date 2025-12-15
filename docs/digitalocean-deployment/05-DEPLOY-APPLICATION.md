# Deploy Application

This guide walks you through deploying the Glyph Laravel application to your DigitalOcean Droplet.

## Prerequisites

Before starting, ensure you have:
- [ ] Completed server setup ([04-SERVER-SETUP.md](./04-SERVER-SETUP.md))
- [ ] Your Droplet IP address
- [ ] Database credentials (glyph_user password)
- [ ] Production environment values ready (from [03-LOCAL-PREPARATION.md](./03-LOCAL-PREPARATION.md))

## Step 1: Clone the Repository

SSH into your Droplet:
```bash
ssh root@YOUR_DROPLET_IP
```

Clone the application:
```bash
cd /var/www

# Option A: Clone from GitHub (recommended)
git clone https://github.com/YOUR_USERNAME/socialgaminghub.git glyph

# Option B: If repository is private, use SSH or token
# git clone https://YOUR_TOKEN@github.com/YOUR_USERNAME/socialgaminghub.git glyph
```

## Step 2: Set Directory Permissions

```bash
cd /var/www/glyph

# Set ownership
chown -R www-data:www-data /var/www/glyph

# Set directory permissions
chmod -R 755 /var/www/glyph

# Set writable permissions for storage and cache
chmod -R 775 /var/www/glyph/storage
chmod -R 775 /var/www/glyph/bootstrap/cache

# Verify permissions
ls -la
```

## Step 3: Install PHP Dependencies

```bash
cd /var/www/glyph

# Install production dependencies (no dev packages)
composer install --optimize-autoloader --no-dev

# This may take a few minutes...
```

## Step 4: Configure Environment

```bash
# Copy example environment file
cp .env.example .env

# Edit environment file
nano .env
```

Update the `.env` file with your production values:

```env
APP_NAME="Glyph"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://YOUR_DROPLET_IP
APP_TIMEZONE=Asia/Kuala_Lumpur

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=glyph
DB_USERNAME=glyph_user
DB_PASSWORD=YOUR_DATABASE_PASSWORD

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=false
CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=glyph_app
REVERB_APP_KEY=YOUR_RANDOM_KEY
REVERB_APP_SECRET=YOUR_RANDOM_SECRET
REVERB_HOST=YOUR_DROPLET_IP
REVERB_PORT=80
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT=80
VITE_REVERB_SCHEME=http

STEAM_API_KEY=YOUR_STEAM_API_KEY
STEAM_CALLBACK_URL="${APP_URL}/auth/steam/callback"

AGORA_APP_ID=YOUR_AGORA_APP_ID
AGORA_APP_CERTIFICATE=YOUR_AGORA_CERTIFICATE
AGORA_TOKEN_EXPIRY=3600

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=YOUR_GMAIL_APP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

LOG_CHANNEL=daily
LOG_LEVEL=warning
```

Save and exit: `Ctrl+X`, then `Y`, then `Enter`

## Step 5: Generate Application Key

```bash
php artisan key:generate
```

This updates the `APP_KEY` in your `.env` file.

## Step 6: Run Database Migrations

```bash
# Run migrations
php artisan migrate --force

# Optionally seed initial data
# php artisan db:seed --force
```

## Step 7: Create Storage Link

```bash
php artisan storage:link
```

## Step 8: Cache Configuration

```bash
# Cache config for better performance
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize
```

## Step 9: Configure nginx

```bash
# Create nginx site configuration
nano /etc/nginx/sites-available/glyph
```

Paste this configuration:

```nginx
server {
    listen 80;
    server_name _;
    root /var/www/glyph/public;
    index index.php;

    client_max_body_size 10M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
    }

    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.env {
        deny all;
    }
}
```

Save and exit.

Enable the site:
```bash
# Enable glyph site
ln -s /etc/nginx/sites-available/glyph /etc/nginx/sites-enabled/

# Remove default site
rm /etc/nginx/sites-enabled/default

# Test nginx configuration
nginx -t

# Reload nginx
systemctl reload nginx
```

## Step 10: Configure Supervisor

```bash
# Create supervisor configuration
nano /etc/supervisor/conf.d/glyph.conf
```

Paste this configuration:

```ini
[program:glyph-reverb]
command=php /var/www/glyph/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www/glyph
user=www-data
autorestart=true
autostart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/reverb.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
stopwaitsecs=3600
stopsignal=SIGTERM

[program:glyph-queue]
command=php /var/www/glyph/artisan queue:work --sleep=3 --tries=3 --timeout=60
directory=/var/www/glyph
user=www-data
numprocs=2
process_name=%(program_name)s_%(process_num)02d
autorestart=true
autostart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/queue.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
stopasgroup=true
killasgroup=true

[group:glyph]
programs=glyph-reverb,glyph-queue
priority=999
```

Save and exit.

Start supervisor processes:
```bash
# Reread configuration
supervisorctl reread

# Update/start processes
supervisorctl update

# Start all glyph processes
supervisorctl start glyph:*

# Check status
supervisorctl status
```

Expected output:
```
glyph:glyph-queue_00     RUNNING   pid 12345, uptime 0:00:05
glyph:glyph-queue_01     RUNNING   pid 12346, uptime 0:00:05
glyph:glyph-reverb       RUNNING   pid 12347, uptime 0:00:05
```

## Step 11: Configure Cron for Scheduler

```bash
# Edit crontab for www-data user
crontab -u www-data -e
```

Add this line at the end:
```
* * * * * cd /var/www/glyph && php artisan schedule:run >> /dev/null 2>&1
```

Save and exit.

## Step 12: Verify Deployment

### Test Website
```bash
# Check if site is responding
curl -I http://YOUR_DROPLET_IP
```

Expected: `HTTP/1.1 200 OK`

### Test in Browser
Visit `http://YOUR_DROPLET_IP` in your browser.

### Test WebSocket
Open browser developer console and check for Reverb connection logs.

### Check Logs
```bash
# Laravel logs
tail -f /var/www/glyph/storage/logs/laravel.log

# Reverb logs
tail -f /var/log/supervisor/reverb.log

# Queue logs
tail -f /var/log/supervisor/queue.log

# nginx access logs
tail -f /var/log/nginx/access.log

# nginx error logs
tail -f /var/log/nginx/error.log
```

### Test Queue
```bash
php artisan queue:work --once
```

### Test Scheduler
```bash
php artisan schedule:run
```

## Troubleshooting

### 502 Bad Gateway
```bash
# Check PHP-FPM is running
systemctl status php8.2-fpm

# Restart PHP-FPM
systemctl restart php8.2-fpm
```

### 500 Internal Server Error
```bash
# Check Laravel logs
tail -50 /var/www/glyph/storage/logs/laravel.log

# Check permissions
chown -R www-data:www-data /var/www/glyph/storage
chmod -R 775 /var/www/glyph/storage
```

### WebSocket Not Connecting
```bash
# Check Reverb is running
supervisorctl status glyph:glyph-reverb

# Check Reverb logs
tail -f /var/log/supervisor/reverb.log

# Restart Reverb
supervisorctl restart glyph:glyph-reverb
```

### Database Connection Error
```bash
# Test MySQL connection
mysql -u glyph_user -p glyph -e "SELECT 1;"

# Check .env database settings
cat /var/www/glyph/.env | grep DB_
```

## Deployment Complete!

Your Glyph application should now be accessible at:
- **Website**: `http://YOUR_DROPLET_IP`
- **WebSocket**: `ws://YOUR_DROPLET_IP/app`

## Next Steps

1. Proceed to [06-SECURITY-HARDENING.md](./06-SECURITY-HARDENING.md) for security best practices
2. Set up regular backups
3. Consider adding a domain name for SSL support
