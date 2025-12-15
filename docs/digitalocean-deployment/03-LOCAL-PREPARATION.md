# Local Preparation

Complete these steps on your local machine before creating the DigitalOcean Droplet.

## Step 1: Update .env.example

Add the following missing environment variables to `.env.example`:

**File**: `C:\laragon\www\socialgaminghub\.env.example`

Add these sections after the existing content:

```env
# =============================================================================
# PRODUCTION ENVIRONMENT VARIABLES
# =============================================================================

# Steam Integration
STEAM_API_KEY=
STEAM_CALLBACK_URL="${APP_URL}/auth/steam/callback"

# Agora Voice Chat
AGORA_APP_ID=
AGORA_APP_CERTIFICATE=
AGORA_TOKEN_EXPIRY=3600

# Telegram (Optional)
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_SECRET=

# Laravel Reverb WebSocket
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
REVERB_HOST=
REVERB_PORT=80
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Vite (Frontend) - These reference the Reverb variables
VITE_APP_NAME="${APP_NAME}"
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# Session Security (set to true when using SSL)
SESSION_SECURE_COOKIE=false
```

## Step 2: Create nginx Configuration

Create this file: `C:\laragon\www\socialgaminghub\nginx-production.conf`

```nginx
# Glyph Production nginx Configuration
# For IP-based access without SSL

server {
    listen 80;
    server_name _;  # Accepts any hostname/IP
    root /var/www/glyph/public;
    index index.php;

    # Increase max upload size for avatars, etc.
    client_max_body_size 10M;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml application/javascript;

    # Laravel application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # WebSocket proxy for Laravel Reverb
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

    # Static assets caching
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to sensitive Laravel files
    location ~ /\.env {
        deny all;
    }

    location ~ /composer\.(json|lock)$ {
        deny all;
    }
}
```

## Step 3: Create Supervisor Configuration

Create this file: `C:\laragon\www\socialgaminghub\supervisor-production.conf`

```ini
# Glyph Supervisor Configuration
# Place this at: /etc/supervisor/conf.d/glyph.conf

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

## Step 4: Build Frontend Assets

Run these commands in your project directory:

```bash
# Navigate to project
cd C:\laragon\www\socialgaminghub

# Install dependencies (if not already installed)
npm install

# Build production assets
npm run build
```

This creates optimized assets in `public/build/`.

## Step 5: Verify Files

After completing the steps above, verify these files exist:

```
socialgaminghub/
├── .env.example                  # Updated with production variables
├── nginx-production.conf         # NEW: nginx configuration
├── supervisor-production.conf    # NEW: Supervisor configuration
└── public/
    └── build/                    # Production assets from npm run build
        ├── manifest.json
        └── assets/
            ├── app-*.css
            └── app-*.js
```

## Step 6: Commit Changes (Optional)

If you want to commit these preparation files:

```bash
git add .env.example nginx-production.conf supervisor-production.conf
git commit -m "Add production deployment configuration files"
git push origin main
```

## Step 7: Prepare Production .env Values

Before deploying, prepare these values (don't commit them!):

### Generate Random Keys

```bash
# Generate REVERB_APP_KEY (32 characters)
openssl rand -base64 24

# Generate REVERB_APP_SECRET (32 characters)
openssl rand -base64 24

# Generate strong database password
openssl rand -base64 16
```

### External Service Credentials

| Service | Where to Get |
|---------|--------------|
| Steam API Key | https://steamcommunity.com/dev/apikey |
| Agora App ID | https://console.agora.io |
| Agora Certificate | https://console.agora.io |
| Gmail App Password | https://myaccount.google.com/apppasswords |
| Telegram Bot Token | @BotFather on Telegram |

### Template Production .env

Save this somewhere secure (NOT in the repository):

```env
APP_NAME="Glyph"
APP_ENV=production
APP_KEY=  # Will be generated on server
APP_DEBUG=false
APP_URL=http://YOUR_DROPLET_IP
APP_TIMEZONE=Asia/Kuala_Lumpur

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=glyph
DB_USERNAME=glyph_user
DB_PASSWORD=YOUR_STRONG_PASSWORD

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

STEAM_API_KEY=YOUR_NEW_STEAM_KEY
STEAM_CALLBACK_URL="${APP_URL}/auth/steam/callback"

AGORA_APP_ID=YOUR_AGORA_APP_ID
AGORA_APP_CERTIFICATE=YOUR_AGORA_CERTIFICATE
AGORA_TOKEN_EXPIRY=3600

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=YOUR_APP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"

LOG_CHANNEL=daily
LOG_LEVEL=warning
```

## Next Step

Once local preparation is complete, proceed to [04-SERVER-SETUP.md](./04-SERVER-SETUP.md) to create and configure your DigitalOcean Droplet.
