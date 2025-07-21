#!/bin/bash

# Glyph Gaming Platform - Production Deployment Script
# For Hostinger VPS KVM 2 deployment

echo "🚀 Starting Glyph deployment..."

# Pull latest changes from repository
echo "📥 Pulling latest code..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed. Deployment aborted."
    exit 1
fi

# Install/update dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

if [ $? -ne 0 ]; then
    echo "❌ Composer install failed. Deployment aborted."
    exit 1
fi

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Clear and optimize caches
echo "🧹 Optimizing application..."
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
php artisan view:clear
php artisan view:cache

# Restart queue workers
echo "⚡ Restarting queue workers..."
php artisan queue:restart

# Restart Reverb WebSocket server (if running)
echo "🔄 Restarting WebSocket server..."
pkill -f "artisan reverb:start" || true
nohup php artisan reverb:start --host=0.0.0.0 --port=8080 > /dev/null 2>&1 &

# Set proper permissions
echo "🔒 Setting file permissions..."
chmod -R 755 storage bootstrap/cache
chmod 644 .env

echo "✅ Deployment completed successfully!"
echo "🌐 Your Glyph platform is now updated and running."

# Optional: Run a quick health check
echo "🏥 Running health check..."
php artisan tinker --execute="echo 'Database connection: ' . (DB::connection()->getPdo() ? 'OK' : 'Failed') . PHP_EOL;"