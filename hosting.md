# 🚀 Hostinger Laravel Deployment Guide

## Overview

This guide provides complete instructions for deploying the **Glyph Gaming Platform** Laravel application to **Hostinger VPS KVM 2 hosting**. Updated for 2025 with production-ready configurations optimized for VPS deployment.

## 🎯 Quick Decision Matrix

| Feature | Shared Hosting | VPS Hosting |
|---------|---------------|-------------|
| **Basic Laravel** | ✅ Yes | ✅ Yes |
| **Database & Email** | ✅ Yes | ✅ Yes |
| **Real-time Chat** | ❌ No | ✅ Yes |
| **WebSocket/Reverb** | ❌ No | ✅ Yes |
| **Background Queues** | ❌ No | ✅ Yes |
| **SSH Access** | ❌ No | ✅ Yes |
| **Cost** | $1.99+/month | $4.99+/month |

**✅ Selected Plan**: **Hostinger VPS KVM 2** - Optimal for full Glyph platform functionality including real-time features, continuous deployment, and scalable performance.

---

## 📋 Pre-Deployment Checklist

### **🔧 Application Preparation**

1. **Environment Configuration**
   - [ ] Create production `.env` file
   - [ ] Configure database credentials
   - [ ] Set up SMTP email settings
   - [ ] Disable debug mode (`APP_DEBUG=false`)
   - [ ] Set production app environment (`APP_ENV=production`)

2. **Code Optimization**
   - [ ] Run `composer install --no-dev --optimize-autoloader`
   - [ ] Remove development dependencies
   - [ ] Optimize configuration caching
   - [ ] Clear all development caches

3. **Security Review**
   - [ ] Ensure `.env` is in `.gitignore`
   - [ ] Review file permissions
   - [ ] Validate production settings

### **🌐 Hostinger Account Setup**

1. **Hosting Plan Selection**
   - [x] **VPS KVM 2 Plan** (selected): Full Laravel + real-time features + continuous deployment capability

2. **Domain Configuration**
   - [ ] Purchase/configure domain
   - [ ] Point DNS to Hostinger
   - [ ] Enable SSL certificate (automatic)

---

## 🛠️ Deployment Implementation

### **Phase 1: Database Setup**

#### **1.1 Create MySQL Database**
```bash
# In Hostinger hPanel → MySQL Databases
1. Database Name: glyph_production
2. Username: glyph_user
3. Password: [Generate secure password]
4. Host: localhost
```

#### **1.2 Production Environment File**
Create `.env.production`:
```env
APP_NAME="Glyph"
APP_ENV=production
APP_KEY=[Generate with: php artisan key:generate]
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=https://yourdomain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=glyph_production
DB_USERNAME=glyph_user
DB_PASSWORD=your_secure_password

# Email Configuration (Hostinger SMTP)
MAIL_MAILER=smtp
MAIL_HOST=mail.yourdomain.com
MAIL_PORT=587
MAIL_USERNAME=noreply@yourdomain.com
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="Glyph"

# Broadcasting (VPS Only)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=112331
REVERB_APP_KEY=8aiacm3rnhkxamz4utcq
REVERB_APP_SECRET=np6bgviv7aa9fochlvwg
REVERB_HOST="yourdomain.com"
REVERB_PORT=8080
REVERB_SCHEME=https

# Session & Cache
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database

# Steam Integration
STEAM_API_KEY=your_steam_api_key
STEAM_CALLBACK_URL="https://yourdomain.com/auth/steam/callback"
```

### **Phase 2: VPS Deployment**

#### **2.1 Initial Deployment**
```bash
# SSH into VPS
ssh username@your-server-ip

# Navigate to web directory
cd /home/username/domains/yourdomain.com/

# Clone repository
git clone https://github.com/yourusername/socialgaminghub.git
cd socialgaminghub

# Install dependencies
composer install --no-dev --optimize-autoloader

# Set up environment
cp .env.production .env
php artisan key:generate

# Set permissions
chmod -R 755 storage bootstrap/cache
chmod -R 644 .env

# Run migrations
php artisan migrate --force

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### **2.2 Continuous Deployment Setup**
```bash
# Set up automated deployment script
nano deploy.sh

#!/bin/bash
echo "🚀 Starting deployment..."
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
echo "✅ Deployment complete!"

# Make executable
chmod +x deploy.sh

# Usage for future updates:
# ./deploy.sh
```

### **Phase 3: Production Configuration**

#### **3.1 VPS Web Server Configuration**
VPS deployment uses standard Laravel structure - no modifications needed to `public/index.php`:
```bash
# Standard Laravel structure in VPS
/home/username/domains/yourdomain.com/socialgaminghub/
├── public/ (document root points here)
├── app/
├── config/
├── vendor/
├── .env
```

#### **3.2 .htaccess Configuration**
Ensure `.htaccess` in public folder:
```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### **Phase 4: Email Configuration**

#### **4.1 Hostinger Email Setup**
```bash
# Create email account in hPanel
1. Go to Email Accounts
2. Create: noreply@yourdomain.com
3. Set secure password
4. Configure SMTP settings
```

#### **4.2 Test Email Delivery**
```php
# Test email via SSH/VPS
php artisan tinker
Mail::raw('Test email from Glyph', function($message) {
    $message->to('test@example.com')->subject('Glyph Test');
});
```

---

## 🔧 Real-Time Features Setup

### **Laravel Reverb Configuration (VPS)**
```bash
# Install and configure Reverb for real-time features
php artisan reverb:install
php artisan reverb:start --host=0.0.0.0 --port=8080

# Configure process manager (Supervisor) for production
sudo nano /etc/supervisor/conf.d/reverb.conf

[program:reverb]
command=php /home/username/domains/yourdomain.com/socialgaminghub/artisan reverb:start --host=0.0.0.0 --port=8080
user=username
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

---

## 🚀 Post-Deployment Tasks

### **1. Verification Checklist**
- [ ] Website loads correctly
- [ ] Database connection works
- [ ] User registration/login functions
- [ ] Email delivery works (OTP emails)
- [ ] Steam integration connects
- [ ] Real-time chat functions (Reverb WebSocket)
- [ ] File uploads work
- [ ] SSL certificate active

### **2. VPS Performance Optimization**
```bash
# Production optimization commands
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set up queue worker for background jobs
php artisan queue:work --daemon

# Configure cron jobs for scheduled tasks
* * * * * cd /home/username/domains/yourdomain.com/socialgaminghub && php artisan schedule:run >> /dev/null 2>&1
```

### **3. Monitoring Setup**
- [ ] Configure error logging
- [ ] Set up uptime monitoring
- [ ] Enable backup automation
- [ ] Monitor resource usage

---

## 🔍 Troubleshooting Guide

### **Common Issues & Solutions**

#### **500 Internal Server Error**
```bash
# Check permissions
chmod -R 755 storage bootstrap/cache

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check error logs
tail -f storage/logs/laravel.log
```

#### **Database Connection Issues**
```bash
# Verify credentials in .env
# Check database exists in hPanel
# Test connection:
php artisan tinker
DB::connection()->getPdo();
```

#### **Email Not Sending**
```bash
# Check SMTP settings
# Verify email account exists
# Test with simple mail:
Mail::raw('Test', function($m) { $m->to('test@example.com'); });
```

#### **Real-Time Features Not Working**
```bash
# VPS: Check Reverb is running
ps aux | grep reverb

# Shared: Verify Pusher configuration
# Check JavaScript console for WebSocket errors
```

---

## 📊 Resource Requirements

### **Minimum System Requirements**
- **PHP**: 8.1 or higher
- **MySQL**: 5.7 or higher
- **Memory**: 512MB (shared), 1GB+ (VPS)
- **Storage**: 1GB minimum
- **SSL**: Required for production

### **Performance Recommendations**
- **CPU**: 2+ cores for VPS
- **Memory**: 2GB+ for VPS with real-time features
- **Storage**: SSD preferred
- **Bandwidth**: Unlimited preferred

---

## 🔐 Security Considerations

### **Production Security Checklist**
- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords
- [ ] Regular security updates
- [ ] File permission restrictions
- [ ] SSL certificate installed
- [ ] Firewall configured (VPS)
- [ ] Regular backups enabled

### **Laravel Security Best Practices**
```php
// Ensure these are properly configured:
- CSRF protection enabled
- SQL injection prevention (Eloquent)
- XSS protection (Blade templates)
- Input validation on all forms
- Rate limiting on authentication
```

---

## 📞 Support Resources

### **Hostinger Support**
- **Live Chat**: 24/7 available
- **Knowledge Base**: help.hostinger.com
- **Community Forum**: community.hostinger.com

### **Laravel Resources**
- **Documentation**: laravel.com/docs
- **Community**: laracasts.com
- **GitHub**: github.com/laravel/laravel

---

## 🎯 Quick Commands Reference

### **Essential Laravel Commands**
```bash
# Application
php artisan key:generate
php artisan migrate --force
php artisan optimize
php artisan down/up

# Caching
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:clear

# Debugging
php artisan tinker
tail -f storage/logs/laravel.log
```

### **VPS Management**
```bash
# Process management
pm2 start reverb
supervisorctl restart all

# Server monitoring
htop
df -h
free -m
```

---

**🎉 Deployment Complete!**

Your Glyph Gaming Platform should now be live on Hostinger. Remember to regularly update your application and monitor performance for the best user experience.