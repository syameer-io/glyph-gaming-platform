<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramBotService;
use TelegramBot\Api\BotApi;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Poll Telegram for updates and process them';

    protected TelegramBotService $telegramService;
    protected BotApi $bot;
    protected int $offset = 0;

    public function __construct(TelegramBotService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->bot = new BotApi(config('services.telegram.bot_token'));
    }

    public function handle()
    {
        $this->info('🤖 Starting Telegram bot polling...');
        $this->info('Press Ctrl+C to stop');

        while (true) {
            try {
                $updates = $this->bot->getUpdates($this->offset, 100, 2);
                
                foreach ($updates as $update) {
                    $this->processUpdate($update);
                    $this->offset = $update->getUpdateId() + 1;
                }

                // Show activity
                if (count($updates) > 0) {
                    $this->info('📨 Processed ' . count($updates) . ' updates');
                }

                // Small delay to avoid hitting rate limits
                usleep(100000); // 0.1 second

            } catch (\Exception $e) {
                $this->error('❌ Error polling Telegram: ' . $e->getMessage());
                sleep(5); // Wait 5 seconds before retrying
            }
        }
    }

    protected function processUpdate($update)
    {
        try {
            $updateArray = [
                'update_id' => $update->getUpdateId(),
            ];

            if ($update->getMessage()) {
                $message = $update->getMessage();
                $updateArray['message'] = [
                    'message_id' => $message->getMessageId(),
                    'from' => [
                        'id' => $message->getFrom()->getId(),
                        'is_bot' => $message->getFrom()->isBot(),
                        'first_name' => $message->getFrom()->getFirstName(),
                        'last_name' => $message->getFrom()->getLastName(),
                        'username' => $message->getFrom()->getUsername(),
                    ],
                    'chat' => [
                        'id' => $message->getChat()->getId(),
                        'type' => $message->getChat()->getType(),
                        'first_name' => $message->getChat()->getFirstName(),
                        'last_name' => $message->getChat()->getLastName(),
                        'username' => $message->getChat()->getUsername(),
                        'title' => $message->getChat()->getTitle(),
                    ],
                    'date' => $message->getDate(),
                    'text' => $message->getText(),
                ];

                $this->info("💬 Message from {$message->getFrom()->getFirstName()}: {$message->getText()}");
            }

            // Process the update through our service
            $this->telegramService->processWebhook($updateArray);

        } catch (\Exception $e) {
            $this->error('❌ Error processing update: ' . $e->getMessage());
        }
    }
}