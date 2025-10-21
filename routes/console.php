<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\TelegramBotService;
use App\Models\Profile;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// For testing/demo purposes - fetch Steam data every 5 minutes
Schedule::command('app:fetch-steam-data')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/steam-fetch.log'));

// Demo command to show scheduler is working
Schedule::call(function () {
    \Log::info('Scheduler is running at ' . now()->format('Y-m-d H:i:s'));
})->everyMinute();

// Clear expired Steam lobby links (older than 30 minutes)
Schedule::call(function () {
    $expirationThreshold = Carbon::now()->subMinutes(Profile::LOBBY_EXPIRATION_MINUTES);

    $expiredCount = Profile::whereNotNull('steam_lobby_link')
        ->whereNotNull('steam_lobby_link_updated_at')
        ->where('steam_lobby_link_updated_at', '<', $expirationThreshold)
        ->update([
            'steam_lobby_link' => null,
            'steam_lobby_link_updated_at' => null,
        ]);

    if ($expiredCount > 0) {
        \Log::info("Cleared {$expiredCount} expired Steam lobby link(s) at " . now()->format('Y-m-d H:i:s'));
    }
})->everyFiveMinutes()->name('clear-expired-lobby-links');

// Manual command to test lobby link expiration clearing
Artisan::command('lobby:clear-expired', function () {
    $this->info('Checking for expired Steam lobby links...');

    $expirationThreshold = Carbon::now()->subMinutes(Profile::LOBBY_EXPIRATION_MINUTES);

    // Show what will be cleared
    $expiredProfiles = Profile::whereNotNull('steam_lobby_link')
        ->whereNotNull('steam_lobby_link_updated_at')
        ->where('steam_lobby_link_updated_at', '<', $expirationThreshold)
        ->with('user:id,username,display_name')
        ->get();

    if ($expiredProfiles->isEmpty()) {
        $this->info('No expired lobby links found.');
        return 0;
    }

    $this->table(
        ['User', 'Lobby Link', 'Updated At', 'Age (minutes)'],
        $expiredProfiles->map(function ($profile) {
            return [
                $profile->user->display_name ?? $profile->user->username ?? 'Unknown',
                substr($profile->steam_lobby_link, 0, 50) . '...',
                $profile->steam_lobby_link_updated_at->format('Y-m-d H:i:s'),
                Carbon::now()->diffInMinutes($profile->steam_lobby_link_updated_at, true)
            ];
        })
    );

    if ($this->confirm("Clear {$expiredProfiles->count()} expired lobby link(s)?", true)) {
        $expiredCount = Profile::whereNotNull('steam_lobby_link')
            ->whereNotNull('steam_lobby_link_updated_at')
            ->where('steam_lobby_link_updated_at', '<', $expirationThreshold)
            ->update([
                'steam_lobby_link' => null,
                'steam_lobby_link_updated_at' => null,
            ]);

        $this->info("✅ Cleared {$expiredCount} expired lobby link(s)");
        \Log::info("Manually cleared {$expiredCount} expired Steam lobby link(s)");
        return 0;
    }

    $this->warn('Operation cancelled.');
    return 1;
})->purpose('Manually clear expired Steam lobby links');

Artisan::command('test:telegram', function () {
    try {
        $telegramService = app(TelegramBotService::class);
        $botInfo = $telegramService->getBotInfo();
        
        if ($botInfo) {
            $this->info('✅ Telegram Bot Connection: SUCCESS');
            $this->info('Bot ID: ' . $botInfo['id']);
            $this->info('Bot Name: ' . $botInfo['first_name']);
            $this->info('Bot Username: @' . $botInfo['username']);
        } else {
            $this->error('❌ Failed to connect to Telegram Bot');
        }
    } catch (\Exception $e) {
        $this->error('❌ Error: ' . $e->getMessage());
    }
})->describe('Test Telegram bot connection');

Artisan::command('test:telegram-message {chatId} {message}', function ($chatId, $message) {
    try {
        $telegramService = app(TelegramBotService::class);
        $success = $telegramService->sendMessage($chatId, $message);
        
        if ($success) {
            $this->info('✅ Message sent successfully to chat ID: ' . $chatId);
        } else {
            $this->error('❌ Failed to send message');
        }
    } catch (\Exception $e) {
        $this->error('❌ Error: ' . $e->getMessage());
    }
})->describe('Test sending a Telegram message');

Artisan::command('telegram:test-commands', function () {
    $this->info('🤖 Testing Telegram bot commands...');
    
    $telegramService = app(TelegramBotService::class);
    
    // Test /start command
    $this->info('Testing /start command...');
    $startUpdate = [
        'update_id' => 1,
        'message' => [
            'message_id' => 1,
            'from' => ['id' => 12345, 'first_name' => 'Test User'],
            'chat' => ['id' => 12345, 'type' => 'private'],
            'date' => time(),
            'text' => '/start'
        ]
    ];
    
    try {
        $telegramService->processWebhook($startUpdate);
        $this->info('✅ /start command processed successfully');
    } catch (\Exception $e) {
        $this->error('❌ Error processing /start: ' . $e->getMessage());
    }
    
    // Test /help command
    $this->info('Testing /help command...');
    $helpUpdate = [
        'update_id' => 2,
        'message' => [
            'message_id' => 2,
            'from' => ['id' => 12345, 'first_name' => 'Test User'],
            'chat' => ['id' => 12345, 'type' => 'private'],
            'date' => time(),
            'text' => '/help'
        ]
    ];
    
    try {
        $telegramService->processWebhook($helpUpdate);
        $this->info('✅ /help command processed successfully');
    } catch (\Exception $e) {
        $this->error('❌ Error processing /help: ' . $e->getMessage());
    }
    
    $this->info('🎉 Command testing completed!');
})->describe('Test Telegram bot commands');