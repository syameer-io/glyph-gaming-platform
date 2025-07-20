<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SteamApiService;
use Illuminate\Console\Command;

class FetchSteamData extends Command
{
    protected $signature = 'app:fetch-steam-data {--user=}';
    protected $description = 'Fetch Steam data for users';

    protected $steamApiService;

    public function __construct(SteamApiService $steamApiService)
    {
        parent::__construct();
        $this->steamApiService = $steamApiService;
    }

    public function handle()
    {
        $userId = $this->option('user');

        if ($userId) {
            $user = User::find($userId);
            if ($user && $user->steam_id) {
                $this->info("Fetching Steam data for {$user->username}...");
                $this->steamApiService->fetchUserData($user);
                $this->info("Steam data updated successfully!");
            } else {
                $this->error("User not found or no Steam ID linked.");
            }
        } else {
            $users = User::whereNotNull('steam_id')->get();
            $this->info("Fetching Steam data for {$users->count()} users...");
            
            $bar = $this->output->createProgressBar($users->count());
            $bar->start();

            foreach ($users as $user) {
                $this->steamApiService->fetchUserData($user);
                $bar->advance();
                sleep(1); // Rate limiting
            }

            $bar->finish();
            $this->newLine();
            $this->info("Steam data update completed!");
        }
    }
}