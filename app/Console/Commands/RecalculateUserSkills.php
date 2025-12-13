<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SkillCalculationService;
use Illuminate\Console\Command;

class RecalculateUserSkills extends Command
{
    protected $signature = 'skills:recalculate {--user= : Specific user ID to recalculate}';
    protected $description = 'Recalculate skill levels for all gaming preferences based on playtime';

    protected SkillCalculationService $skillService;

    public function __construct(SkillCalculationService $skillService)
    {
        parent::__construct();
        $this->skillService = $skillService;
    }

    public function handle(): int
    {
        $userId = $this->option('user');

        if ($userId) {
            return $this->recalculateSingleUser($userId);
        }

        return $this->recalculateAllUsers();
    }

    protected function recalculateSingleUser(int $userId): int
    {
        $user = User::with('gamingPreferences')->find($userId);

        if (!$user) {
            $this->error("User not found with ID: {$userId}");
            return 1;
        }

        if ($user->gamingPreferences->isEmpty()) {
            $this->warn("User {$user->username} has no gaming preferences to recalculate.");
            return 0;
        }

        $this->info("Recalculating skills for {$user->username}...");

        $updated = 0;
        foreach ($user->gamingPreferences as $preference) {
            $skillData = $this->skillService->calculateSkillForGame($user, (string)$preference->game_appid);
            $newLevel = $skillData['skill_level'] ?? 'beginner';

            if ($preference->skill_level !== $newLevel) {
                $this->line("  {$preference->game_name}: {$preference->skill_level} -> {$newLevel}");
                $preference->update(['skill_level' => $newLevel]);
                $updated++;
            }
        }

        $this->info("Updated {$updated} of {$user->gamingPreferences->count()} gaming preferences.");
        return 0;
    }

    protected function recalculateAllUsers(): int
    {
        $users = User::whereHas('gamingPreferences')->with('gamingPreferences', 'profile')->get();

        if ($users->isEmpty()) {
            $this->warn("No users with gaming preferences found.");
            return 0;
        }

        $this->info("Recalculating skill levels for {$users->count()} users...");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $totalUpdated = 0;
        $totalPreferences = 0;

        foreach ($users as $user) {
            foreach ($user->gamingPreferences as $preference) {
                $skillData = $this->skillService->calculateSkillForGame($user, (string)$preference->game_appid);
                $newLevel = $skillData['skill_level'] ?? 'beginner';

                if ($preference->skill_level !== $newLevel) {
                    $preference->update(['skill_level' => $newLevel]);
                    $totalUpdated++;
                }
                $totalPreferences++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Recalculation complete!");
        $this->info("Updated {$totalUpdated} of {$totalPreferences} gaming preferences across {$users->count()} users.");

        return 0;
    }
}
