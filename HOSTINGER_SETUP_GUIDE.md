# 🚀 Hostinger VPS Setup Guide for Glyph Gaming Platform

## 📋 **STEP-BY-STEP SETUP AFTER PURCHASING KVM 2 PLAN**

### **Phase 1: Hostinger Panel Configuration**

#### **Step 1: Access Your VPS**
1. **Login to Hostinger hPanel**
   - Go to hostinger.com and login
   - Navigate to "VPS" section
   - Click on your KVM 2 VPS

2. **Get VPS Access Details**
   ```
   📝 Note down these details:
   - Server IP Address: xxx.xxx.xxx.xxx
   - Root Password: (check email or reset in panel)
   - SSH Port: 22 (default)
   ```

#### **Step 2: Initial VPS Setup**
1. **Connect via SSH**
   ```bash
   # From your local terminal
   ssh root@YOUR_VPS_IP
   # Enter the root password when prompted
   ```

2. **Update System**
   ```bash
   # Update package manager
   apt update && apt upgrade -y
   
   # Install essential packages
   apt install -y curl wget git unzip supervisor nginx mysql-server
   ```

#### **Step 3: Install PHP 8.1+**
```bash
# Add PHP repository
add-apt-repository ppa:ondrej/php -y
apt update

# Install PHP and required extensions
apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-xml php8.2-gd php8.2-curl php8.2-mbstring php8.2-zip php8.2-intl php8.2-bcmath

# Install Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

#### **Step 4: Configure MySQL Database**
```bash
# Secure MySQL installation
mysql_secure_installation

# Login to MySQL
mysql -u root -p

# Create database and user
CREATE DATABASE glyph_production;
CREATE USER 'glyph_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON glyph_production.* TO 'glyph_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### **Phase 2: Deploy Your Application**

#### **Step 5: Clone and Setup Application**
```bash
# Navigate to web directory
cd /var/www

# Clone your repository (replace with your actual repo URL)
git clone https://github.com/yourusername/socialgaminghub.git glyph
cd glyph

# Make deployment script executable
chmod +x deploy.sh
chmod +x optimize.sh

# Copy production environment
cp .env.production .env

# Edit environment file with your actual details
nano .env
```

#### **Step 6: Configure .env File**
Update these values in `/var/www/glyph/.env`:
```env
APP_URL=https://yourdomain.com

# Database (use details from Step 4)
DB_DATABASE=glyph_production
DB_USERNAME=glyph_user
DB_PASSWORD=your_secure_password_here

# Reverb WebSocket
REVERB_HOST="yourdomain.com"
REVERB_SCHEME=https
```

#### **Step 7: Install Dependencies and Setup**
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Set proper permissions
chown -R www-data:www-data /var/www/glyph
chmod -R 755 /var/www/glyph/storage
chmod -R 755 /var/www/glyph/bootstrap/cache
```

### **Phase 3: Web Server Configuration**

#### **Step 8: Configure Nginx**
```bash
# Create Nginx site configuration
nano /etc/nginx/sites-available/glyph

# Add this configuration:
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/glyph/public;
    
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}

# Enable the site
ln -s /etc/nginx/sites-available/glyph /etc/nginx/sites-enabled/
rm /etc/nginx/sites-enabled/default

# Test and restart Nginx
nginx -t
systemctl restart nginx
```

#### **Step 9: Setup SSL Certificate**
```bash
# Install Certbot
apt install -y certbot python3-certbot-nginx

# Get SSL certificate (replace with your domain)
certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
certbot renew --dry-run
```

### **Phase 4: Background Services Setup**

#### **Step 10: Configure Supervisor**
```bash
# Copy supervisor configuration
cp /var/www/glyph/supervisor-glyph.conf /etc/supervisor/conf.d/

# Edit the configuration file to replace placeholders
nano /etc/supervisor/conf.d/supervisor-glyph.conf

# Replace these placeholders:
# USERNAME -> your VPS username (usually 'www-data' or 'root')
# YOURDOMAIN.com -> your actual domain

# Create log directory
mkdir -p /var/www/glyph/storage/logs

# Update supervisor and start services
supervisorctl reread
supervisorctl update
supervisorctl start glyph:*
```

#### **Step 11: Final Optimization**
```bash
# Run optimization script
cd /var/www/glyph
./optimize.sh

# Enable services to start on boot
systemctl enable nginx
systemctl enable mysql
systemctl enable supervisor
systemctl enable php8.2-fpm
```

### **Phase 5: Domain Configuration**

#### **Step 12: Point Domain to VPS**
1. **In Your Domain Provider's Panel:**
   ```
   A Record: @ -> YOUR_VPS_IP
   A Record: www -> YOUR_VPS_IP
   ```

2. **Wait for DNS Propagation (up to 24 hours)**

#### **Step 13: Test Your Installation**
1. **Visit your domain in browser**
2. **Test key features:**
   - User registration/login
   - Steam integration
   - Real-time chat
   - Server creation

### **Phase 6: Ongoing Maintenance**

#### **Step 14: Setup Automated Deployments**
```bash
# For future updates, simply run:
cd /var/www/glyph
./deploy.sh

# This will:
# - Pull latest code
# - Update dependencies
# - Run migrations  
# - Optimize caches
# - Restart services
```

#### **Step 15: Monitoring and Logs**
```bash
# Check application logs
tail -f /var/www/glyph/storage/logs/laravel.log

# Check Nginx logs
tail -f /var/log/nginx/error.log

# Check supervisor services
supervisorctl status

# Check system resources
htop
```

---

## 🔧 **TROUBLESHOOTING COMMON ISSUES**

### **Database Connection Failed**
```bash
# Check MySQL is running
systemctl status mysql

# Test database connection
mysql -u glyph_user -p glyph_production
```

### **Nginx 502 Error**
```bash
# Check PHP-FPM is running
systemctl status php8.2-fpm

# Check Nginx error logs
tail -f /var/log/nginx/error.log
```

### **WebSocket Not Working**
```bash
# Check if Reverb is running
supervisorctl status glyph:glyph-reverb

# Check port is open
netstat -tulpn | grep 8080
```

### **Permission Errors**
```bash
# Fix permissions
chown -R www-data:www-data /var/www/glyph
chmod -R 755 /var/www/glyph/storage
chmod -R 755 /var/www/glyph/bootstrap/cache
```

---

## 📞 **SUPPORT CHECKLIST**

Before contacting support, verify:
- [ ] Domain DNS is pointing to VPS IP
- [ ] SSL certificate is installed and valid
- [ ] All services are running (`supervisorctl status`)
- [ ] Database connection works
- [ ] File permissions are correct
- [ ] Logs show specific error messages

---

## 🎉 **CONGRATULATIONS!**

Your Glyph Gaming Platform is now live on Hostinger VPS KVM 2!

**Next Steps:**
- Test all features thoroughly
- Set up regular backups
- Monitor performance and usage
- Deploy new features using `./deploy.sh`

**Your production URLs:**
- Website: https://yourdomain.com
- WebSocket: wss://yourdomain.com:8080
- Admin: https://yourdomain.com/admin (if applicable)

Happy Gaming! 🎮