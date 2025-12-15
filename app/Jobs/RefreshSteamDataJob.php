<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\SteamApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshSteamDataJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $userId;
    public string $trigger;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 10;

    /**
     * Number of seconds after which the job's unique lock expires.
     */
    public int $uniqueFor = 60;

    /**
     * Create a new job instance.
     *
     * @param int $userId The user ID to refresh Steam data for
     * @param string $trigger The trigger source (login, profile_view, manual)
     */
    public function __construct(int $userId, string $trigger = 'manual')
    {
        $this->userId = $userId;
        $this->trigger = $trigger;
    }

    /**
     * The unique ID of the job (prevents duplicate jobs).
     */
    public function uniqueId(): string
    {
        return 'steam_refresh_' . $this->userId;
    }

    /**
     * Execute the job.
     */
    public function handle(SteamApiService $steamApiService): void
    {
        $user = User::find($this->userId);

        if (!$user || !$user->steam_id) {
            Log::info("RefreshSteamDataJob skipped - user {$this->userId} has no Steam ID");
            return;
        }

        Log::info("RefreshSteamDataJob started", [
            'user_id' => $this->userId,
            'trigger' => $this->trigger,
            'steam_id' => $user->steam_id,
        ]);

        try {
            // Force refresh bypasses cache and fetches fresh data from Steam API
            $steamApiService->forceRefreshUserData($user);

            Log::info("RefreshSteamDataJob completed", [
                'user_id' => $this->userId,
                'trigger' => $this->trigger,
            ]);
        } catch (\Exception $e) {
            Log::error("RefreshSteamDataJob failed", [
                'user_id' => $this->userId,
                'trigger' => $this->trigger,
                'error' => $e->getMessage(),
            ]);
            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error("RefreshSteamDataJob permanently failed", [
            'user_id' => $this->userId,
            'trigger' => $this->trigger,
            'error' => $exception?->getMessage(),
        ]);
    }
}
