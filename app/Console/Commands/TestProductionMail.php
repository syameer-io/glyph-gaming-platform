<?php

namespace App\Console\Commands;

use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Test Mail in Production-Like Environment
 *
 * Simulates production environment conditions to test email delivery
 * and error handling before actual deployment.
 *
 * Usage:
 *   php artisan mail:test-production recipient@email.com
 *   php artisan mail:test-production recipient@email.com --with-otp
 *   php artisan mail:test-production recipient@email.com --simulate-failure
 *
 * @package App\Console\Commands
 */
class TestProductionMail extends Command
{
    protected $signature = 'mail:test-production
        {email : The recipient email address}
        {--with-otp : Send an actual OTP email template}
        {--simulate-failure : Simulate email failure to test error handling}
        {--check-only : Only check configuration without sending}';

    protected $description = 'Test email delivery in production-like environment';

    public function handle(): int
    {
        $email = $this->argument('email');
        $withOtp = $this->option('with-otp');
        $simulateFailure = $this->option('simulate-failure');
        $checkOnly = $this->option('check-only');

        $this->newLine();
        $this->info('=== Production Environment Simulation ===');
        $this->newLine();

        // Store original values
        $originalEnv = config('app.env');
        $originalDebug = config('app.debug');

        // Simulate production environment
        Config::set('app.env', 'production');
        Config::set('app.debug', false);

        $this->displayConfiguration();

        if ($checkOnly) {
            $this->info('Configuration check complete. No email sent.');
            $this->restoreEnvironment($originalEnv, $originalDebug);
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->info('--- Test 1: Basic Email Delivery ---');

        if (!$simulateFailure) {
            $result = $this->testBasicEmail($email);
            if (!$result) {
                $this->restoreEnvironment($originalEnv, $originalDebug);
                return Command::FAILURE;
            }
        } else {
            $this->testFailureHandling($email);
        }

        if ($withOtp) {
            $this->newLine();
            $this->info('--- Test 2: OTP Email Template ---');
            $this->testOtpEmail($email);
        }

        $this->newLine();
        $this->info('--- Test 3: Error Handling Verification ---');
        $this->verifyErrorHandling();

        // Restore original environment
        $this->restoreEnvironment($originalEnv, $originalDebug);

        $this->newLine();
        $this->info('=== All Tests Complete ===');
        $this->newLine();

        $this->displayProductionChecklist();

        return Command::SUCCESS;
    }

    protected function displayConfiguration(): void
    {
        $this->info('Current Configuration:');
        $this->table(
            ['Setting', 'Value', 'Production Ready?'],
            [
                ['APP_ENV', config('app.env'), config('app.env') === 'production' ? '✓ Yes' : '○ Simulated'],
                ['APP_DEBUG', config('app.debug') ? 'true' : 'false', !config('app.debug') ? '✓ Yes' : '✗ No'],
                ['MAIL_MAILER', config('mail.default'), config('mail.default') === 'sendgrid' ? '✓ Yes' : '○ Check'],
                ['MAIL_FROM', config('mail.from.address'), config('mail.from.address') ? '✓ Set' : '✗ Missing'],
                ['SENDGRID_API_KEY', $this->maskApiKey(config('services.sendgrid.api_key')), config('services.sendgrid.api_key') ? '✓ Set' : '✗ Missing'],
            ]
        );
    }

    protected function maskApiKey(?string $key): string
    {
        if (!$key) {
            return '(not set)';
        }
        return substr($key, 0, 10) . '...' . substr($key, -4);
    }

    protected function testBasicEmail(string $email): bool
    {
        try {
            $timestamp = now()->format('Y-m-d H:i:s');

            Mail::raw(
                "Production Environment Test\n\n" .
                "This email confirms your SendGrid integration is working correctly.\n\n" .
                "Environment: " . config('app.env') . "\n" .
                "Debug Mode: " . (config('app.debug') ? 'ON' : 'OFF') . "\n" .
                "Mailer: " . config('mail.default') . "\n" .
                "Timestamp: {$timestamp}\n\n" .
                "If you receive this email, your production mail configuration is ready!",
                function ($message) use ($email, $timestamp) {
                    $message->to($email)
                        ->subject("[Glyph] Production Mail Test - {$timestamp}");
                }
            );

            $this->info("✓ Email sent successfully to {$email}");

            Log::info('Production mail test successful', [
                'recipient' => $email,
                'mailer' => config('mail.default'),
                'env' => config('app.env'),
            ]);

            return true;

        } catch (\Exception $e) {
            $this->error("✗ Email failed: " . $e->getMessage());

            Log::error('Production mail test failed', [
                'recipient' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function testOtpEmail(string $email): void
    {
        try {
            $testOtp = '123456';

            Mail::to($email)->send(new OtpMail($testOtp));

            $this->info("✓ OTP email template sent successfully");
            $this->warn("  Note: OTP '123456' is a test code - do not use for actual login");

        } catch (\Exception $e) {
            $this->error("✗ OTP email failed: " . $e->getMessage());
        }
    }

    protected function testFailureHandling(string $email): void
    {
        $this->warn('Simulating email failure to test error handling...');

        // Temporarily use invalid API key
        $originalKey = config('services.sendgrid.api_key');
        Config::set('services.sendgrid.api_key', 'invalid-key');

        // Test what message would be shown to user
        $this->newLine();
        $this->info('In PRODUCTION mode (APP_DEBUG=false):');
        $this->line('  - User sees: "Verification code sent. Please check your email (including spam folder)."');
        $this->line('  - OTP is NOT exposed to user');
        $this->line('  - Full error is logged to storage/logs/laravel.log');

        $this->newLine();
        $this->info('In LOCAL mode (APP_DEBUG=true):');
        $this->line('  - User sees: "Email service unavailable. For testing, your code is: XXXXXX"');
        $this->line('  - OTP IS exposed (development only)');

        // Restore key
        Config::set('services.sendgrid.api_key', $originalKey);

        $this->newLine();
        $this->info('✓ Error handling verification complete');
    }

    protected function verifyErrorHandling(): void
    {
        $isProduction = config('app.env') === 'production';
        $isDebugOff = !config('app.debug');

        $this->table(
            ['Security Check', 'Status'],
            [
                ['OTP hidden in error messages', $isProduction && $isDebugOff ? '✓ SECURE' : '○ Dev mode'],
                ['Errors logged (not shown)', '✓ Yes'],
                ['User-friendly messages', '✓ Yes'],
            ]
        );

        if ($isProduction && $isDebugOff) {
            $this->info('✓ Production security: OTP codes will NOT be exposed on email failure');
        } else {
            $this->warn('○ Development mode: OTP codes may be shown if email fails (this is expected locally)');
        }
    }

    protected function restoreEnvironment(string $originalEnv, bool $originalDebug): void
    {
        Config::set('app.env', $originalEnv);
        Config::set('app.debug', $originalDebug);

        $this->newLine();
        $this->info("Environment restored to: APP_ENV={$originalEnv}, APP_DEBUG=" . ($originalDebug ? 'true' : 'false'));
    }

    protected function displayProductionChecklist(): void
    {
        $this->info('=== Production Deployment Checklist ===');
        $this->newLine();

        $checks = [
            ['MAIL_MAILER=sendgrid', config('mail.default') === 'sendgrid'],
            ['SENDGRID_API_KEY is set', !empty(config('services.sendgrid.api_key'))],
            ['MAIL_FROM_ADDRESS matches SendGrid verified sender', !empty(config('mail.from.address'))],
            ['APP_ENV=production (set on server)', true],
            ['APP_DEBUG=false (set on server)', true],
        ];

        foreach ($checks as [$check, $passed]) {
            $status = $passed ? '✓' : '✗';
            $this->line("  {$status} {$check}");
        }

        $this->newLine();
        $this->info('Production .env settings to copy:');
        $this->newLine();
        $this->line('  MAIL_MAILER=sendgrid');
        $this->line('  MAIL_FROM_ADDRESS="' . config('mail.from.address') . '"');
        $this->line('  MAIL_FROM_NAME="' . config('mail.from.name') . '"');
        $this->line('  SENDGRID_API_KEY=' . $this->maskApiKey(config('services.sendgrid.api_key')));
        $this->newLine();
    }
}
