# Maintenance Guide

Quick reference commands for maintaining your Glyph deployment.

## Quick Reference Commands

### Service Management

```bash
# Restart all services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo supervisorctl restart glyph:*

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo supervisorctl status
```

### Laravel Artisan Commands

```bash
cd /var/www/glyph

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches (after code changes)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue workers (after code changes)
php artisan queue:restart

# Run migrations
php artisan migrate --force

# Check scheduled tasks
php artisan schedule:list
php artisan schedule:run

# Team management
php artisan teams:expire-invitations    # Expire old team invitations

# Lobby management
php artisan lobby:clear-expired         # Manually clear expired lobby links

# Permission management
php artisan permissions:clear-cache     # Clear all permission caches

# Telegram bot testing
php artisan test:telegram               # Test Telegram bot connection
```

### View Logs

```bash
# Laravel application logs
tail -f /var/www/glyph/storage/logs/laravel.log

# Reverb WebSocket logs
tail -f /var/log/supervisor/reverb.log

# Queue worker logs
tail -f /var/log/supervisor/queue.log

# nginx access logs
tail -f /var/log/nginx/access.log

# nginx error logs
tail -f /var/log/nginx/error.log

# MySQL logs
tail -f /var/log/mysql/error.log

# System logs
tail -f /var/log/syslog
```

## Deploying Updates

### Standard Deployment

```bash
cd /var/www/glyph

# Pull latest code
git pull origin main

# Install dependencies (if composer.json changed)
composer install --optimize-autoloader --no-dev

# Run new migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart background processes
supervisorctl restart glyph:*

# Verify deployment
curl -I http://YOUR_DROPLET_IP
```

### If Frontend Assets Changed

```bash
cd /var/www/glyph

# Install Node dependencies
npm install

# Build production assets
npm run build

# Clear view cache
php artisan view:cache
```

## Database Operations

### Backup Database

```bash
# Manual backup
mysqldump -u glyph_user -p glyph | gzip > backup_$(date +%Y%m%d).sql.gz
```

### Restore Database

```bash
# Restore from backup
gunzip < backup_20231201.sql.gz | mysql -u glyph_user -p glyph
```

### Database Shell

```bash
# Access MySQL
mysql -u glyph_user -p glyph

# Run queries
mysql -u glyph_user -p glyph -e "SELECT COUNT(*) FROM users;"
```

## Supervisor Management

```bash
# Check status
supervisorctl status

# Restart all glyph processes
supervisorctl restart glyph:*

# Restart specific process
supervisorctl restart glyph:glyph-reverb
supervisorctl restart glyph:glyph-queue_00

# Stop all processes
supervisorctl stop glyph:*

# Start all processes
supervisorctl start glyph:*

# Reload configuration
supervisorctl reread
supervisorctl update

# View process logs
supervisorctl tail -f glyph:glyph-reverb
supervisorctl tail -f glyph:glyph-queue_00
```

## Server Monitoring

### Check Resources

```bash
# Disk usage
df -h

# Memory usage
free -h

# CPU usage
top -bn1 | head -10

# Process list
ps aux | grep php
ps aux | grep mysql

# Active connections
ss -tuln
```

### Check Application Health

```bash
# Test website response
curl -I http://YOUR_DROPLET_IP

# Check PHP-FPM pool status
systemctl status php8.2-fpm

# Check MySQL connection
mysql -u glyph_user -p -e "SELECT 1"

# Check queue status
cd /var/www/glyph && php artisan queue:work --once
```

## Troubleshooting

### 502 Bad Gateway

```bash
# Check PHP-FPM
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm

# Check nginx error log
tail -20 /var/log/nginx/error.log
```

### 500 Internal Server Error

```bash
# Check Laravel logs
tail -50 /var/www/glyph/storage/logs/laravel.log

# Check permissions
sudo chown -R www-data:www-data /var/www/glyph/storage
sudo chmod -R 775 /var/www/glyph/storage
```

### WebSocket Not Working

```bash
# Check Reverb is running
supervisorctl status glyph:glyph-reverb

# Check Reverb logs
tail -f /var/log/supervisor/reverb.log

# Restart Reverb
supervisorctl restart glyph:glyph-reverb

# Verify port is listening
ss -tuln | grep 8080
```

### Queue Jobs Not Processing

```bash
# Check queue workers
supervisorctl status glyph:glyph-queue_00
supervisorctl status glyph:glyph-queue_01

# Check queue logs
tail -f /var/log/supervisor/queue.log

# Manually process one job
cd /var/www/glyph && php artisan queue:work --once

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all
```

### Database Connection Issues

```bash
# Test connection
mysql -u glyph_user -p glyph -e "SELECT 1"

# Check MySQL is running
sudo systemctl status mysql

# Restart MySQL
sudo systemctl restart mysql

# Check .env settings
cat /var/www/glyph/.env | grep DB_
```

### Disk Space Full

```bash
# Check disk usage
df -h

# Find large files
du -h /var/www/glyph --max-depth=2 | sort -h | tail -20

# Clear Laravel logs
truncate -s 0 /var/www/glyph/storage/logs/laravel.log

# Clear old backups
find /home/backup -type f -mtime +7 -delete
```

## Adding a Domain Later

When you're ready to add a domain:

### 1. Update DNS
Point your domain's A record to your Droplet IP.

### 2. Update nginx

```bash
nano /etc/nginx/sites-available/glyph
```

Change:
```nginx
server_name _;
```
To:
```nginx
server_name yourdomain.com www.yourdomain.com;
```

### 3. Get SSL Certificate

```bash
certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### 4. Update Environment

```bash
nano /var/www/glyph/.env
```

Update:
```env
APP_URL=https://yourdomain.com
SESSION_SECURE_COOKIE=true
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https
VITE_REVERB_HOST=yourdomain.com
VITE_REVERB_PORT=443
VITE_REVERB_SCHEME=https
```

### 5. Rebuild and Cache

```bash
cd /var/www/glyph
npm run build
php artisan config:cache
supervisorctl restart glyph:*
systemctl reload nginx
```

## Scaling Up

### Upgrade Droplet

If you need more resources:

1. Go to DigitalOcean dashboard
2. Select your Droplet
3. Click "Resize"
4. Choose 4GB ($24/month) or larger
5. Select "Resize Droplet"

Note: This requires a brief downtime for restart.

### Add Redis (Optional)

For better performance with high traffic:

```bash
# Install Redis
sudo apt install -y redis-server

# Configure Redis
sudo nano /etc/redis/redis.conf
# Set: supervised systemd

# Restart Redis
sudo systemctl restart redis
sudo systemctl enable redis

# Update .env
nano /var/www/glyph/.env
```

Update:
```env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

Rebuild cache:
```bash
php artisan config:cache
```

## Useful Aliases

Add to `/root/.bashrc` for convenience:

```bash
# Glyph shortcuts
alias glyph='cd /var/www/glyph'
alias artisan='cd /var/www/glyph && php artisan'
alias glogs='tail -f /var/www/glyph/storage/logs/laravel.log'
alias grestart='supervisorctl restart glyph:* && systemctl reload nginx'
alias gdeploy='cd /var/www/glyph && git pull && composer install --no-dev --optimize-autoloader && php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache && supervisorctl restart glyph:*'
```

Apply:
```bash
source ~/.bashrc
```

Then use:
```bash
glyph      # Go to project directory
artisan    # Run artisan commands
glogs      # View Laravel logs
grestart   # Restart all services
gdeploy    # Full deployment
```
