# Development Notes

## Email Configuration Issues

### Current Status
The application is configured to use Gmail SMTP but experiencing authentication failures.

### Solutions Implemented

1. **Graceful Error Handling**: Modified AuthController to catch email exceptions and display OTP in flash messages during development.

2. **Environment-based Behavior**: 
   - In `local` environment: Shows OTP in success message if email fails
   - In `production` environment: Shows generic message

### Alternative Development Setup

To completely bypass email during development:

1. **Option 1: Use Log Driver** (Already set in .env)
   ```bash
   # Clear config cache
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Option 2: Use Array Driver** (Stores emails in memory)
   ```env
   MAIL_MAILER=array
   ```

3. **Option 3: Use File Driver** (Saves emails to storage/app/mail)
   ```env
   MAIL_MAILER=file
   ```

### For Production Gmail Setup

1. Enable 2-Factor Authentication on Gmail
2. Generate App Password:
   - Google Account → Security → 2-Step Verification → App passwords
   - Select "Mail" and generate password
3. Update .env:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=your-email@gmail.com
   MAIL_PASSWORD=your-16-char-app-password
   MAIL_ENCRYPTION=tls
   ```

### Testing the Application

1. Register a new account
2. If email fails, the OTP will be shown in the success message
3. Use the displayed OTP to complete verification
4. Check `storage/logs/laravel.log` for logged emails when using log driver

### Database Schema

Make sure migrations are run:
```bash
php artisan migrate:fresh --seed
```

This will create:
- Users table with OTP fields
- Profiles table for user profiles
- Other gaming-related tables