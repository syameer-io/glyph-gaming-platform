# Server Setup - DigitalOcean Droplet

This guide walks you through creating and configuring your DigitalOcean Droplet.

## Step 1: Create the Droplet

1. Go to https://cloud.digitalocean.com
2. Click **"Create"** → **"Droplets"**
3. Configure as follows:

### Droplet Configuration

| Setting | Value |
|---------|-------|
| **Region** | Singapore (SGP1) |
| **Image** | Ubuntu 22.04 (LTS) x64 |
| **Size** | Basic → Regular → $12/mo (2GB RAM, 1 vCPU, 50GB SSD) |
| **Authentication** | SSH Keys (recommended) or Password |
| **Hostname** | `glyph-production` |
| **Backups** | Optional ($2.40/mo) - can enable later |

4. Click **"Create Droplet"**
5. **Copy your Droplet IP address** - you'll need this for all following steps

## Step 2: Connect to Your Droplet

### Using SSH (Linux/Mac/Windows Terminal)

```bash
ssh root@YOUR_DROPLET_IP
```

### Using PuTTY (Windows)

1. Open PuTTY
2. Enter your Droplet IP in "Host Name"
3. Click "Open"
4. Login as `root`

## Step 3: Initial Server Configuration

Run these commands after connecting:

```bash
# Update system packages
apt update && apt upgrade -y

# Set timezone to Malaysia
timedatectl set-timezone Asia/Kuala_Lumpur

# Verify timezone
date
```

## Step 4: Configure Firewall

```bash
# Allow SSH (important - do this first!)
ufw allow OpenSSH

# Allow HTTP traffic
ufw allow 80

# Allow HTTPS (for future SSL)
ufw allow 443

# Enable firewall
ufw enable

# Verify firewall status
ufw status
```

Expected output:
```
Status: active

To                         Action      From
--                         ------      ----
OpenSSH                    ALLOW       Anywhere
80                         ALLOW       Anywhere
443                        ALLOW       Anywhere
OpenSSH (v6)               ALLOW       Anywhere (v6)
80 (v6)                    ALLOW       Anywhere (v6)
443 (v6)                   ALLOW       Anywhere (v6)
```

## Step 5: Install PHP 8.2

```bash
# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP 8.2 with required extensions
apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-redis

# Verify PHP installation
php -v
```

Expected output:
```
PHP 8.2.x (cli) ...
```

## Step 6: Install MySQL 8.0

```bash
# Install MySQL
apt install -y mysql-server

# Secure MySQL installation
mysql_secure_installation
```

When prompted:
- **VALIDATE PASSWORD component**: Press `n` (or `y` for stricter passwords)
- **Remove anonymous users**: `y`
- **Disallow root login remotely**: `y`
- **Remove test database**: `y`
- **Reload privilege tables**: `y`

## Step 7: Create Database and User

```bash
# Login to MySQL
mysql -u root
```

Run these SQL commands:

```sql
-- Create database
CREATE DATABASE glyph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create user (replace YOUR_STRONG_PASSWORD)
CREATE USER 'glyph_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';

-- Grant privileges
GRANT ALL PRIVILEGES ON glyph.* TO 'glyph_user'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Verify user
SELECT User, Host FROM mysql.user WHERE User = 'glyph_user';

-- Exit MySQL
EXIT;
```

**Save your database password somewhere secure!**

## Step 8: Install nginx

```bash
# Install nginx
apt install -y nginx

# Verify nginx is running
systemctl status nginx
```

You should now be able to visit `http://YOUR_DROPLET_IP` and see the nginx welcome page.

## Step 9: Install Composer

```bash
# Download Composer installer
curl -sS https://getcomposer.org/installer -o composer-setup.php

# Install Composer globally
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

# Remove installer
rm composer-setup.php

# Verify installation
composer --version
```

## Step 10: Install Node.js (Optional - for rebuilds)

```bash
# Add NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -

# Install Node.js
apt install -y nodejs

# Verify installation
node -v
npm -v
```

## Step 11: Install Supervisor

```bash
# Install Supervisor
apt install -y supervisor

# Enable Supervisor on boot
systemctl enable supervisor

# Create log directory
mkdir -p /var/log/supervisor

# Verify installation
supervisorctl version
```

## Step 12: Install Git

```bash
# Install Git
apt install -y git

# Verify installation
git --version
```

## Step 13: Install Certbot (for future SSL)

```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx
```

## Step 14: Create Web Directory

```bash
# Create directory for application
mkdir -p /var/www/glyph

# Set ownership to www-data (nginx user)
chown -R www-data:www-data /var/www/glyph
```

## Step 15: Add Swap Space (Recommended for 2GB Droplet)

```bash
# Create 2GB swap file
fallocate -l 2G /swapfile

# Set permissions
chmod 600 /swapfile

# Set up swap
mkswap /swapfile
swapon /swapfile

# Make permanent
echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab

# Verify swap
free -h
```

## Verification Checklist

Run these commands to verify everything is installed:

```bash
# Check PHP
php -v

# Check MySQL
mysql --version

# Check nginx
nginx -v

# Check Composer
composer --version

# Check Node.js
node -v

# Check Supervisor
supervisorctl version

# Check Git
git --version

# Check firewall
ufw status

# Check swap
free -h
```

## Server Information Summary

After completing this setup, note these details:

| Item | Value |
|------|-------|
| **Droplet IP** | YOUR_DROPLET_IP |
| **SSH Command** | `ssh root@YOUR_DROPLET_IP` |
| **Web Directory** | `/var/www/glyph` |
| **PHP Version** | 8.2 |
| **MySQL User** | `glyph_user` |
| **MySQL Database** | `glyph` |
| **MySQL Password** | (saved securely) |

## Next Step

Proceed to [05-DEPLOY-APPLICATION.md](./05-DEPLOY-APPLICATION.md) to deploy the Laravel application.
