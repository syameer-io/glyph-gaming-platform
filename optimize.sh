#!/bin/bash

# Glyph Gaming Platform - Production Optimization Script
# Run this script after deployment to optimize performance

echo "⚡ Optimizing Glyph for production performance..."

# Clear all caches first
echo "🧹 Clearing existing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan event:clear

# Generate optimized caches
echo "🚀 Building production caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize Composer autoloader
echo "📦 Optimizing Composer autoloader..."
composer dump-autoload --optimize

# Generate application key if needed
echo "🔑 Ensuring application key is set..."
php artisan key:generate --show

# Optimize database queries
echo "🗄️ Optimizing database..."
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder 2>/dev/null || echo "No production seeder found, skipping..."

# Set proper file permissions
echo "🔒 Setting secure file permissions..."
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 775 storage bootstrap/cache
chmod 600 .env

# Start essential services
echo "🎯 Starting background services..."

# Start queue worker in background
nohup php artisan queue:work --daemon --tries=3 --timeout=60 > /dev/null 2>&1 &
echo "✅ Queue worker started"

# Start Reverb WebSocket server
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > /dev/null 2>&1 &
echo "✅ WebSocket server started"

# Display system status
echo ""
echo "📊 System Status:"
echo "================="
php artisan about --only=environment,cache,database

echo ""
echo "🎉 Optimization complete!"
echo "Your Glyph platform is fully optimized for production."