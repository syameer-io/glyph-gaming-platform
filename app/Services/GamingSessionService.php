<?php

namespace App\Services;

use App\Models\GamingSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class GamingSessionService
{
    /**
     * Start a new gaming session
     */
    public function startSession(User $user, array $gameData): ?GamingSession
    {
        try {
            // End any existing active sessions for this user
            $this->endAllActiveSessions($user);

            // Create new session
            $session = GamingSession::create([
                'user_id' => $user->id,
                'game_appid' => $gameData['appid'],
                'game_name' => $gameData['name'],
                'started_at' => now(),
                'session_data' => [
                    'server_name' => $gameData['server_name'] ?? null,
                    'map' => $gameData['map'] ?? null,
                    'game_mode' => $gameData['game_mode'] ?? null,
                    'lobby_id' => $gameData['lobby_id'] ?? null,
                ],
                'status' => 'active',
            ]);

            Log::info("Gaming session started for user {$user->id}: {$gameData['name']}");
            return $session;

        } catch (\Exception $e) {
            Log::error("Failed to start gaming session for user {$user->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * End a gaming session
     */
    public function endSession(User $user, string $gameAppId, bool $abandoned = false): bool
    {
        try {
            $session = GamingSession::getActiveSession($user->id, $gameAppId);
            
            if (!$session) {
                Log::info("No active session found for user {$user->id} and game {$gameAppId}");
                return false;
            }

            if ($abandoned) {
                $session->abandon();
                Log::info("Gaming session abandoned for user {$user->id}: {$session->game_name}");
            } else {
                $session->complete();
                Log::info("Gaming session completed for user {$user->id}: {$session->game_name} ({$session->duration_minutes} minutes)");
            }

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to end gaming session for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Switch to a different game (end current, start new)
     */
    public function switchGame(User $user, array $previousGame, array $currentGame): ?GamingSession
    {
        // End previous session
        $this->endSession($user, $previousGame['appid']);

        // Start new session
        return $this->startSession($user, $currentGame);
    }

    /**
     * Update session data for game status changes
     */
    public function updateSessionData(User $user, string $gameAppId, array $updatedData): bool
    {
        try {
            $session = GamingSession::getActiveSession($user->id, $gameAppId);
            
            if (!$session) {
                return false;
            }

            $sessionData = $session->session_data ?? [];
            $sessionData = array_merge($sessionData, [
                'server_name' => $updatedData['server_name'] ?? $sessionData['server_name'] ?? null,
                'map' => $updatedData['map'] ?? $sessionData['map'] ?? null,
                'game_mode' => $updatedData['game_mode'] ?? $sessionData['game_mode'] ?? null,
                'lobby_id' => $updatedData['lobby_id'] ?? $sessionData['lobby_id'] ?? null,
                'last_updated' => now()->toDateTimeString(),
            ]);

            $session->session_data = $sessionData;
            $session->save();

            Log::info("Gaming session data updated for user {$user->id}: {$session->game_name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to update gaming session data for user {$user->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * End all active sessions for a user
     */
    public function endAllActiveSessions(User $user, bool $abandoned = false): int
    {
        try {
            $activeSessions = GamingSession::where('user_id', $user->id)
                ->where('status', 'active')
                ->get();

            $endedCount = 0;
            foreach ($activeSessions as $session) {
                if ($abandoned) {
                    $session->abandon();
                } else {
                    $session->complete();
                }
                $endedCount++;
            }

            if ($endedCount > 0) {
                Log::info("Ended {$endedCount} active sessions for user {$user->id}");
            }

            return $endedCount;

        } catch (\Exception $e) {
            Log::error("Failed to end active sessions for user {$user->id}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get user's recent gaming activity
     */
    public function getRecentActivity(User $user, int $days = 7): array
    {
        $sessions = GamingSession::where('user_id', $user->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->where('status', '!=', 'active')
            ->orderByDesc('started_at')
            ->get();

        return [
            'total_sessions' => $sessions->count(),
            'total_hours' => round($sessions->sum('duration_minutes') / 60, 1),
            'games_played' => $sessions->pluck('game_name')->unique()->values()->toArray(),
            'daily_breakdown' => $this->getDailyBreakdown($sessions, $days),
            'sessions' => $sessions->map(function ($session) {
                return [
                    'game_name' => $session->game_name,
                    'started_at' => $session->started_at->toISOString(),
                    'duration_minutes' => $session->duration_minutes,
                    'session_data' => $session->session_data,
                ];
            })->take(20)->toArray(), // Latest 20 sessions
        ];
    }

    /**
     * Analyze gaming schedule patterns
     */
    public function analyzeGamingSchedule(User $user, int $days = 30): array
    {
        $sessions = GamingSession::where('user_id', $user->id)
            ->where('started_at', '>=', now()->subDays($days))
            ->where('status', '!=', 'active')
            ->get();

        if ($sessions->isEmpty()) {
            return [
                'peak_hours' => [],
                'peak_days' => [],
                'average_session_length' => 0,
                'timezone' => 'UTC',
                'activity_pattern' => 'unknown',
                'last_analyzed' => now()->toDateTimeString(),
            ];
        }

        // Analyze peak hours (0-23)
        $hourCounts = [];
        foreach ($sessions as $session) {
            $hour = $session->started_at->hour;
            $hourCounts[$hour] = ($hourCounts[$hour] ?? 0) + 1;
        }
        arsort($hourCounts);
        $peakHours = array_slice(array_keys($hourCounts), 0, 4); // Top 4 hours

        // Analyze peak days
        $dayCounts = [];
        foreach ($sessions as $session) {
            $day = strtolower($session->started_at->format('l')); // monday, tuesday, etc.
            $dayCounts[$day] = ($dayCounts[$day] ?? 0) + 1;
        }
        arsort($dayCounts);
        $peakDays = array_slice(array_keys($dayCounts), 0, 3); // Top 3 days

        // Calculate average session length
        $avgSessionLength = $sessions->avg('duration_minutes');

        // Determine activity pattern
        $activityPattern = $this->determineActivityPattern($peakHours, $peakDays);

        return [
            'peak_hours' => $peakHours,
            'peak_days' => $peakDays,
            'average_session_length' => round($avgSessionLength),
            'timezone' => 'UTC', // TODO: Use user's timezone
            'activity_pattern' => $activityPattern,
            'sessions_analyzed' => $sessions->count(),
            'last_analyzed' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get daily breakdown of gaming activity
     */
    protected function getDailyBreakdown($sessions, int $days): array
    {
        $breakdown = [];
        
        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $daySessions = $sessions->filter(function ($session) use ($date) {
                return $session->started_at->format('Y-m-d') === $date;
            });

            $breakdown[$date] = [
                'sessions' => $daySessions->count(),
                'total_minutes' => $daySessions->sum('duration_minutes'),
                'games' => $daySessions->pluck('game_name')->unique()->count(),
            ];
        }

        return array_reverse($breakdown, true); // Oldest first
    }

    /**
     * Determine activity pattern based on peak hours and days
     */
    protected function determineActivityPattern(array $peakHours, array $peakDays): string
    {
        // Evening pattern (6 PM - 11 PM)
        $eveningHours = [18, 19, 20, 21, 22, 23];
        $eveningOverlap = count(array_intersect($peakHours, $eveningHours));
        
        // Weekend pattern
        $weekendDays = ['friday', 'saturday', 'sunday'];
        $weekendOverlap = count(array_intersect($peakDays, $weekendDays));
        
        // Morning pattern (6 AM - 12 PM)
        $morningHours = [6, 7, 8, 9, 10, 11, 12];
        $morningOverlap = count(array_intersect($peakHours, $morningHours));

        if ($eveningOverlap >= 3) {
            return $weekendOverlap >= 2 ? 'evening_weekend' : 'evening';
        }
        
        if ($weekendOverlap >= 2) {
            return 'weekend';
        }
        
        if ($morningOverlap >= 3) {
            return 'morning';
        }
        
        // Check for consistent activity across weekdays
        $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $weekdayOverlap = count(array_intersect($peakDays, $weekdays));
        
        if ($weekdayOverlap >= 3) {
            return 'consistent';
        }

        return 'varied';
    }

    /**
     * Cleanup old sessions (run as scheduled task)
     */
    public function cleanupOldSessions(int $daysToKeep = 90): int
    {
        try {
            $cutoffDate = now()->subDays($daysToKeep);
            
            $deletedCount = GamingSession::where('started_at', '<', $cutoffDate)
                ->where('status', '!=', 'active')
                ->delete();

            Log::info("Cleaned up {$deletedCount} gaming sessions older than {$daysToKeep} days");
            return $deletedCount;

        } catch (\Exception $e) {
            Log::error("Failed to cleanup old gaming sessions: " . $e->getMessage());
            return 0;
        }
    }
}