<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\TelegramBotService;

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