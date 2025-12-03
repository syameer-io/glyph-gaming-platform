<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramBotService;

class TelegramSetMenuCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:set-menu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register bot commands menu with Telegram';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $telegramService): int
    {
        $this->info('Registering Telegram bot commands...');
        $this->newLine();

        try {
            $success = $telegramService->setMyCommands();

            if ($success) {
                $this->info('Bot commands registered successfully!');
                $this->newLine();

                // Display registered commands
                $commands = [
                    ['Command' => '/start', 'Description' => 'Welcome message and setup info'],
                    ['Command' => '/help', 'Description' => 'Show available commands'],
                    ['Command' => '/link', 'Description' => 'Link chat to a gaming server'],
                    ['Command' => '/goals', 'Description' => 'View active community goals'],
                    ['Command' => '/stats', 'Description' => 'View server goal statistics'],
                    ['Command' => '/leaderboard', 'Description' => 'Top 10 goal contributors'],
                    ['Command' => '/upcoming', 'Description' => 'Goals sorted by deadline'],
                ];

                $this->table(['Command', 'Description'], $commands);

                return self::SUCCESS;
            } else {
                $this->error('Failed to register bot commands. Check logs for details.');
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Error registering bot commands: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
