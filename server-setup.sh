#!/bin/bash

# =============================================================================
# GLYPH SERVER SETUP SCRIPT
# =============================================================================
# Automated setup for DigitalOcean Droplet (Ubuntu 22.04 LTS)
# Based on: docs/digitalocean-deployment/04-SERVER-SETUP.md
#
# Usage:
#   curl -O https://raw.githubusercontent.com/syameer-io/glyph-gaming-platform/dev/server-setup.sh
#   chmod +x server-setup.sh
#   ./server-setup.sh
#
# =============================================================================

set -e  # Exit on error

# =============================================================================
# COLORS AND LOGGING
# =============================================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_step() {
    echo ""
    echo -e "${CYAN}========================================${NC}"
    echo -e "${CYAN}$1${NC}"
    echo -e "${CYAN}========================================${NC}"
}

# =============================================================================
# PRE-FLIGHT CHECKS
# =============================================================================
log_step "GLYPH SERVER SETUP - Pre-flight Checks"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    log_error "Please run as root (use sudo or login as root)"
    exit 1
fi

# Check if Ubuntu
if ! grep -q "Ubuntu" /etc/os-release 2>/dev/null; then
    log_warning "This script is designed for Ubuntu. Proceed with caution."
fi

log_success "Pre-flight checks passed"
echo ""
echo "This script will install:"
echo "  - PHP 8.2 with Laravel extensions"
echo "  - MySQL 8.0"
echo "  - nginx"
echo "  - Composer"
echo "  - Node.js 20.x"
echo "  - Supervisor"
echo "  - Git"
echo "  - Certbot"
echo "  - 2GB Swap space"
echo ""
read -p "Press ENTER to continue or Ctrl+C to cancel..."

# =============================================================================
# STEP 1: SYSTEM UPDATE & TIMEZONE
# =============================================================================
log_step "Step 1/12: System Update & Timezone"

log_info "Updating system packages..."
apt update && apt upgrade -y

log_info "Setting timezone to Asia/Kuala_Lumpur..."
timedatectl set-timezone Asia/Kuala_Lumpur

log_success "System updated. Current time: $(date)"

# =============================================================================
# STEP 2: FIREWALL CONFIGURATION
# =============================================================================
log_step "Step 2/12: Firewall Configuration"

log_info "Configuring UFW firewall..."

# Allow SSH first (critical!)
ufw allow OpenSSH

# Allow HTTP and HTTPS
ufw allow 80
ufw allow 443

# Enable firewall (auto-confirm)
echo "y" | ufw enable

log_success "Firewall configured"
ufw status

# =============================================================================
# STEP 3: PHP 8.2 INSTALLATION
# =============================================================================
log_step "Step 3/12: PHP 8.2 Installation"

log_info "Adding PHP repository..."
add-apt-repository ppa:ondrej/php -y
apt update

log_info "Installing PHP 8.2 with extensions..."
apt install -y php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-redis

log_success "PHP installed: $(php -v | head -n 1)"

# =============================================================================
# STEP 4: MYSQL 8.0 INSTALLATION
# =============================================================================
log_step "Step 4/12: MySQL 8.0 Installation"

log_info "Installing MySQL Server..."
apt install -y mysql-server

log_info "Starting MySQL service..."
systemctl start mysql
systemctl enable mysql

log_success "MySQL installed: $(mysql --version)"
log_warning "IMPORTANT: Run 'mysql_secure_installation' manually after this script!"
log_warning "Then run the database setup SQL to create the glyph database and user."

# =============================================================================
# STEP 5: NGINX INSTALLATION
# =============================================================================
log_step "Step 5/12: nginx Installation"

log_info "Installing nginx..."
apt install -y nginx

log_info "Starting nginx service..."
systemctl start nginx
systemctl enable nginx

log_success "nginx installed: $(nginx -v 2>&1)"

# =============================================================================
# STEP 6: COMPOSER INSTALLATION
# =============================================================================
log_step "Step 6/12: Composer Installation"

log_info "Downloading Composer..."
curl -sS https://getcomposer.org/installer -o composer-setup.php

log_info "Installing Composer globally..."
php composer-setup.php --install-dir=/usr/local/bin --filename=composer

log_info "Cleaning up..."
rm composer-setup.php

log_success "Composer installed: $(composer --version)"

# =============================================================================
# STEP 7: NODE.JS INSTALLATION
# =============================================================================
log_step "Step 7/12: Node.js 20.x Installation"

log_info "Adding NodeSource repository..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -

log_info "Installing Node.js..."
apt install -y nodejs

log_success "Node.js installed: $(node -v)"
log_success "npm installed: $(npm -v)"

# =============================================================================
# STEP 8: SUPERVISOR INSTALLATION
# =============================================================================
log_step "Step 8/12: Supervisor Installation"

log_info "Installing Supervisor..."
apt install -y supervisor

log_info "Enabling Supervisor service..."
systemctl enable supervisor
systemctl start supervisor

log_info "Creating log directory..."
mkdir -p /var/log/supervisor

log_success "Supervisor installed: $(supervisorctl version)"

# =============================================================================
# STEP 9: GIT INSTALLATION
# =============================================================================
log_step "Step 9/12: Git Installation"

log_info "Installing Git..."
apt install -y git

log_success "Git installed: $(git --version)"

# =============================================================================
# STEP 10: CERTBOT INSTALLATION
# =============================================================================
log_step "Step 10/12: Certbot Installation"

log_info "Installing Certbot for SSL certificates..."
apt install -y certbot python3-certbot-nginx

log_success "Certbot installed (for future SSL setup)"

# =============================================================================
# STEP 11: WEB DIRECTORY SETUP
# =============================================================================
log_step "Step 11/12: Web Directory Setup"

log_info "Creating web directory /var/www/glyph..."
mkdir -p /var/www/glyph

log_info "Setting ownership to www-data..."
chown -R www-data:www-data /var/www/glyph

log_success "Web directory created"
ls -la /var/www/

# =============================================================================
# STEP 12: SWAP SPACE SETUP
# =============================================================================
log_step "Step 12/12: Swap Space Setup"

if [ -f /swapfile ]; then
    log_warning "Swap file already exists, skipping..."
else
    log_info "Creating 2GB swap file..."
    fallocate -l 2G /swapfile

    log_info "Setting swap permissions..."
    chmod 600 /swapfile

    log_info "Setting up swap..."
    mkswap /swapfile
    swapon /swapfile

    log_info "Making swap permanent..."
    echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab

    log_success "Swap configured"
fi

free -h

# =============================================================================
# VERIFICATION SUMMARY
# =============================================================================
log_step "SETUP COMPLETE - Verification Summary"

echo ""
echo "Installed Software:"
echo "-------------------"
echo "PHP:        $(php -v | head -n 1)"
echo "MySQL:      $(mysql --version)"
echo "nginx:      $(nginx -v 2>&1)"
echo "Composer:   $(composer --version 2>/dev/null | head -n 1)"
echo "Node.js:    $(node -v)"
echo "npm:        $(npm -v)"
echo "Supervisor: $(supervisorctl version)"
echo "Git:        $(git --version)"
echo ""
echo "System Status:"
echo "--------------"
echo "Timezone:   $(timedatectl | grep 'Time zone' | awk '{print $3}')"
echo "Firewall:   $(ufw status | head -n 1)"
echo "Swap:       $(free -h | grep Swap | awk '{print $2}')"
echo ""

# =============================================================================
# NEXT STEPS
# =============================================================================
echo -e "${CYAN}========================================${NC}"
echo -e "${CYAN}NEXT STEPS${NC}"
echo -e "${CYAN}========================================${NC}"
echo ""
echo "1. SECURE MYSQL (Important!):"
echo "   mysql_secure_installation"
echo ""
echo "2. CREATE DATABASE AND USER:"
echo "   mysql -u root"
echo "   Then run these SQL commands:"
echo ""
echo "   CREATE DATABASE glyph CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "   CREATE USER 'glyph_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';"
echo "   GRANT ALL PRIVILEGES ON glyph.* TO 'glyph_user'@'localhost';"
echo "   FLUSH PRIVILEGES;"
echo "   EXIT;"
echo ""
echo "3. VERIFY NGINX:"
echo "   Visit http://YOUR_DROPLET_IP in a browser"
echo "   You should see the nginx welcome page"
echo ""
echo "4. PROCEED TO APPLICATION DEPLOYMENT:"
echo "   Follow 05-DEPLOY-APPLICATION.md"
echo ""
log_success "Server setup script completed!"
