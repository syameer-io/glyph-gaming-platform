# Security Hardening

This guide covers essential security measures for your production deployment.

## 1. Rotate All Credentials

**Critical**: Before going live, generate new credentials for all external services.

### Steam API Key
1. Go to https://steamcommunity.com/dev/apikey
2. Register a new domain/IP
3. Generate a new API key
4. Update `STEAM_API_KEY` in `.env`

### Agora.io Credentials
1. Go to https://console.agora.io
2. Navigate to your project
3. If compromised, create a new project or regenerate credentials
4. Update `AGORA_APP_ID` and `AGORA_APP_CERTIFICATE` in `.env`

### Gmail App Password
1. Go to https://myaccount.google.com/apppasswords
2. Generate a new App Password
3. Revoke the old one
4. Update `MAIL_PASSWORD` in `.env`

### Telegram Bot Token (if using)
1. Message @BotFather on Telegram
2. Use `/revoke` to get a new token
3. Update `TELEGRAM_BOT_TOKEN` in `.env`

### Laravel Reverb Keys
Generate new random keys:
```bash
# Generate new keys
openssl rand -base64 24  # For REVERB_APP_KEY
openssl rand -base64 24  # For REVERB_APP_SECRET
```

Update both `REVERB_APP_KEY` and `REVERB_APP_SECRET` in `.env`.

After rotating credentials:
```bash
php artisan config:cache
```

## 2. Restrict WebSocket Origins

Edit the Reverb configuration to only allow connections from your IP:

```bash
nano /var/www/glyph/config/reverb.php
```

Find and update `allowed_origins`:
```php
'allowed_origins' => [
    env('APP_URL'),
    // Add your IP if needed
    'http://YOUR_DROPLET_IP',
],
```

Then clear cache:
```bash
php artisan config:cache
supervisorctl restart glyph:glyph-reverb
```

## 3. SSH Security

### Disable Root Password Login

Edit SSH config:
```bash
nano /etc/ssh/sshd_config
```

Find and update:
```
PermitRootLogin prohibit-password  # Only allow SSH key
PasswordAuthentication no           # Disable password auth (if using SSH keys)
```

Restart SSH:
```bash
systemctl restart sshd
```

### Create Non-Root User (Recommended)

```bash
# Create user
adduser deploy

# Add to sudo group
usermod -aG sudo deploy

# Copy SSH keys to new user
mkdir -p /home/deploy/.ssh
cp ~/.ssh/authorized_keys /home/deploy/.ssh/
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Test login with new user before disabling root
ssh deploy@YOUR_DROPLET_IP
```

## 4. Fail2ban (Brute Force Protection)

```bash
# Install fail2ban
apt install -y fail2ban

# Create local config
cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local

# Edit config
nano /etc/fail2ban/jail.local
```

Update SSH settings:
```ini
[sshd]
enabled = true
port = ssh
filter = sshd
logpath = /var/log/auth.log
maxretry = 5
bantime = 3600
findtime = 600
```

Start fail2ban:
```bash
systemctl enable fail2ban
systemctl start fail2ban

# Check status
fail2ban-client status sshd
```

## 5. Automatic Security Updates

```bash
# Install unattended-upgrades
apt install -y unattended-upgrades

# Enable automatic updates
dpkg-reconfigure -plow unattended-upgrades
```

## 6. Database Backup Script

Create backup script:
```bash
mkdir -p /home/backup
nano /home/backup/backup-glyph.sh
```

Add this content:
```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/home/backup/glyph"
DB_NAME="glyph"
DB_USER="glyph_user"
DB_PASS="YOUR_DATABASE_PASSWORD"
RETENTION_DAYS=7

# Create backup directory
mkdir -p $BACKUP_DIR

# Create timestamp
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$TIMESTAMP.sql.gz

# Backup uploaded files
tar -czf $BACKUP_DIR/storage_$TIMESTAMP.tar.gz -C /var/www/glyph/storage app/public

# Delete old backups
find $BACKUP_DIR -type f -mtime +$RETENTION_DAYS -delete

# Log completion
echo "Backup completed: $TIMESTAMP" >> /home/backup/backup.log
```

Make executable and schedule:
```bash
chmod +x /home/backup/backup-glyph.sh

# Add to crontab (daily at 3 AM)
crontab -e
```

Add:
```
0 3 * * * /home/backup/backup-glyph.sh
```

## 7. File Permissions Audit

Ensure correct permissions:
```bash
cd /var/www/glyph

# Set ownership
chown -R www-data:www-data .

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make storage and cache writable
chmod -R 775 storage bootstrap/cache

# Protect sensitive files
chmod 600 .env
```

## 8. Nginx Security Headers

Your nginx config already includes basic security headers. Verify they're active:
```bash
curl -I http://YOUR_DROPLET_IP
```

Should include:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
```

## 9. Rate Limiting

Laravel's built-in rate limiting is already configured. Verify it's active:

```bash
cat /var/www/glyph/routes/api.php | grep throttle
```

## 10. Monitoring

### Check Disk Space
```bash
df -h
```

### Check Memory Usage
```bash
free -h
```

### Check CPU Load
```bash
top -bn1 | head -5
```

### Check Running Processes
```bash
supervisorctl status
```

### Set Up Disk Space Alert (Optional)

Create monitoring script:
```bash
nano /home/backup/check-disk.sh
```

```bash
#!/bin/bash
THRESHOLD=80
USAGE=$(df / | grep / | awk '{ print $5 }' | sed 's/%//g')

if [ $USAGE -gt $THRESHOLD ]; then
    echo "Disk usage is ${USAGE}% on $(hostname)" | mail -s "Disk Alert" your@email.com
fi
```

## 11. DigitalOcean Droplet Backups

Enable automated backups in DigitalOcean dashboard:
1. Go to your Droplet
2. Click "Backups"
3. Enable backups ($2.40/month)

This creates weekly automated snapshots.

## Security Checklist

Before going live:

### Credentials
- [ ] Steam API key rotated
- [ ] Agora credentials rotated
- [ ] Gmail App Password rotated
- [ ] Telegram bot token rotated (if using)
- [ ] REVERB_APP_KEY and SECRET regenerated
- [ ] Database password is strong

### Access
- [ ] SSH key authentication only (no passwords)
- [ ] Firewall enabled (ufw)
- [ ] Fail2ban configured
- [ ] Non-root user created (optional)

### Application
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] LOG_LEVEL=warning
- [ ] WebSocket origins restricted
- [ ] .env file permission is 600

### Backups
- [ ] Database backup script created
- [ ] Backup cron job scheduled
- [ ] DigitalOcean backups enabled (optional)

### Monitoring
- [ ] Laravel logs being written
- [ ] Supervisor processes running
- [ ] Disk space adequate

## Next Step

Proceed to [07-MAINTENANCE.md](./07-MAINTENANCE.md) for ongoing maintenance commands and procedures.
