<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Test Mail Configuration Command
 *
 * Sends a test email to verify mail configuration is working correctly.
 * Supports testing specific mailers to diagnose delivery issues.
 *
 * Usage:
 *   php artisan mail:test recipient@email.com              # Use default mailer
 *   php artisan mail:test recipient@email.com --mailer=sendgrid  # Test SendGrid
 *   php artisan mail:test recipient@email.com --mailer=log      # Test log driver
 *   php artisan mail:test recipient@email.com -v           # Verbose with stack trace
 *
 * @package App\Console\Commands
 */
class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test
        {email : The recipient email address}
        {--mailer= : Specific mailer to test (sendgrid, smtp, log)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $mailer = $this->option('mailer') ?: config('mail.default');

        $this->info('Testing mail configuration...');
        $this->newLine();
        $this->table(
            ['Setting', 'Value'],
            [
                ['Mailer', $mailer],
                ['Recipient', $email],
                ['From Address', config('mail.from.address')],
                ['From Name', config('mail.from.name')],
            ]
        );
        $this->newLine();

        try {
            $mail = Mail::mailer($mailer);

            $timestamp = now()->format('Y-m-d H:i:s');
            $subject = "[Glyph] Mail Configuration Test - {$timestamp}";
            $body = $this->getEmailBody($mailer, $timestamp);

            $mail->raw($body, function ($message) use ($email, $subject) {
                $message->to($email)
                    ->subject($subject);
            });

            $this->info('Test email sent successfully!');
            $this->info('Check your inbox (and spam folder) for the test email.');

            Log::info('Test email sent via mail:test command', [
                'mailer' => $mailer,
                'recipient' => $email,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('Failed to send test email!');
            $this->error('Error: ' . $e->getMessage());

            Log::error('Test email failed via mail:test command', [
                'mailer' => $mailer,
                'recipient' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($this->output->isVerbose()) {
                $this->newLine();
                $this->error('Stack trace:');
                $this->line($e->getTraceAsString());
            }

            $this->newLine();
            $this->warn('Troubleshooting tips:');
            $this->line('  1. Check your .env file for correct mail settings');
            $this->line('  2. Run: php artisan config:clear');
            $this->line('  3. Check storage/logs/laravel.log for detailed errors');

            if ($mailer === 'sendgrid') {
                $this->line('  4. Verify SENDGRID_API_KEY is set in .env');
                $this->line('  5. Ensure sender email is verified in SendGrid dashboard');
            }

            return Command::FAILURE;
        }
    }

    /**
     * Generate the test email body.
     *
     * @param string $mailer
     * @param string $timestamp
     * @return string
     */
    protected function getEmailBody(string $mailer, string $timestamp): string
    {
        return <<<BODY
This is a test email from Glyph.

If you received this email, your mail configuration is working correctly!

Details:
- Mailer: {$mailer}
- Timestamp: {$timestamp}
- From: {$this->getFromAddress()}

This email was sent using the command:
php artisan mail:test

---
Glyph - Gaming Community Platform
BODY;
    }

    /**
     * Get the configured from address.
     *
     * @return string
     */
    protected function getFromAddress(): string
    {
        $address = config('mail.from.address');
        $name = config('mail.from.name');

        return $name ? "{$name} <{$address}>" : $address;
    }
}
