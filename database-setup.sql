-- =============================================================================
-- GLYPH DATABASE SETUP
-- =============================================================================
-- Run this script after server-setup.sh completes
--
-- Usage:
--   1. First run: mysql_secure_installation
--   2. Then run: mysql -u root < database-setup.sql
--   Or run commands interactively: mysql -u root
--
-- IMPORTANT: Replace 'YOUR_STRONG_PASSWORD' with a secure password!
-- Generate one with: openssl rand -base64 16
-- =============================================================================

-- Create database with proper character set
CREATE DATABASE IF NOT EXISTS glyph
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Create application user
-- CHANGE THIS PASSWORD before running!
CREATE USER IF NOT EXISTS 'glyph_user'@'localhost'
    IDENTIFIED BY 'YOUR_STRONG_PASSWORD';

-- Grant all privileges on glyph database
GRANT ALL PRIVILEGES ON glyph.* TO 'glyph_user'@'localhost';

-- Apply privilege changes
FLUSH PRIVILEGES;

-- Verify user was created
SELECT User, Host FROM mysql.user WHERE User = 'glyph_user';

-- Show databases to verify
SHOW DATABASES;

-- =============================================================================
-- VERIFICATION COMPLETE
-- =============================================================================
-- If you see 'glyph_user' in the user list and 'glyph' in databases,
-- the setup was successful.
--
-- Save your database password securely!
-- You'll need it for .env configuration.
-- =============================================================================
