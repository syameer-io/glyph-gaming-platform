<?php

namespace App\Services;

use App\Models\User;
use App\Models\Server;
use App\Models\ServerGoal;
use App\Models\GoalParticipant;
use App\Models\GoalMilestone;
use App\Events\GoalProgressUpdated;
use App\Events\GoalMilestoneReached;
use App\Events\GoalCompleted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ServerGoalService
{
    /**
     * Create a new server goal with milestones
     */
    public function createGoal(Server $server, User $creator, array $goalData): ServerGoal
    {
        DB::beginTransaction();
        
        try {
            $goal = ServerGoal::create([
                'server_id' => $server->id,
                'creator_id' => $creator->id,
                'title' => $goalData['title'],
                'description' => $goalData['description'],
                'game_appid' => $goalData['game_appid'] ?? null,
                'game_name' => $goalData['game_name'] ?? null,
                'goal_type' => $goalData['goal_type'],
                'target_criteria' => $goalData['target_criteria'],
                'target_value' => $goalData['target_value'],
                'difficulty' => $goalData['difficulty'] ?? 'medium',
                'visibility' => $goalData['visibility'] ?? 'public',
                'status' => $goalData['status'] ?? 'draft',
                'rewards' => $goalData['rewards'] ?? null,
                'goal_settings' => $goalData['goal_settings'] ?? null,
                'start_date' => $goalData['start_date'] ?? now(),
                'deadline' => $goalData['deadline'] ?? null,
            ]);

            // Create milestones if provided
            if (!empty($goalData['milestones'])) {
                $this->createMilestones($goal, $goalData['milestones']);
            }

            DB::commit();
            return $goal;
            
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Create milestones for a goal
     */
    public function createMilestones(ServerGoal $goal, array $milestonesData): Collection
    {
        $milestones = collect();

        foreach ($milestonesData as $index => $milestoneData) {
            $milestone = GoalMilestone::create([
                'goal_id' => $goal->id,
                'milestone_name' => $milestoneData['milestone_name'],
                'description' => $milestoneData['description'] ?? null,
                'progress_required' => $milestoneData['progress_required'],
                'percentage_required' => $milestoneData['percentage_required'],
                'reward_description' => $milestoneData['reward_description'] ?? null,
                'milestone_type' => $milestoneData['milestone_type'] ?? 'progress',
                'order' => $index + 1,
                'broadcast_achievement' => $milestoneData['broadcast_achievement'] ?? true,
            ]);
            
            $milestones->push($milestone);
        }

        return $milestones;
    }

    /**
     * Add user to goal participation
     */
    public function joinGoal(ServerGoal $goal, User $user): bool
    {
        if (!$goal->canUserParticipate($user)) {
            return false;
        }

        $skillScore = $this->getUserSkillScore($user, $goal->game_appid);

        GoalParticipant::create([
            'goal_id' => $goal->id,
            'user_id' => $user->id,
            'individual_progress' => 0,
            'participation_status' => 'active',
            'joined_at' => now(),
            'last_activity_at' => now(),
            'skill_score_at_start' => $skillScore,
            'current_skill_score' => $skillScore,
        ]);

        $goal->increment('participant_count');
        return true;
    }

    /**
     * Update user's progress towards a goal
     */
    public function updateUserProgress(ServerGoal $goal, User $user, int $newProgress, array $progressData = []): bool
    {
        $participant = GoalParticipant::where('goal_id', $goal->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$participant || !$participant->isActive()) {
            return false;
        }

        $participant->update([
            'individual_progress' => $newProgress,
            'progress_data' => array_merge($participant->progress_data ?? [], $progressData),
            'last_activity_at' => now(),
        ]);

        // Update goal's overall progress
        $goal->updateProgress();

        return true;
    }

    /**
     * Auto-update progress from Steam data
     */
    public function updateProgressFromSteamData(User $user): array
    {
        $updatedGoals = [];
        
        // Get user's active goal participations
        $participations = GoalParticipant::where('user_id', $user->id)
            ->where('participation_status', 'active')
            ->with('goal')
            ->get();

        $steamData = $user->profile->steam_data ?? [];

        foreach ($participations as $participation) {
            $goal = $participation->goal;
            
            if ($goal->goal_type === 'achievement' && $goal->game_appid) {
                $newProgress = $this->calculateAchievementProgress($steamData, $goal);
                
                if ($newProgress > $participation->individual_progress) {
                    $this->updateUserProgress($goal, $user, $newProgress);
                    $updatedGoals[] = $goal;
                }
            } elseif ($goal->goal_type === 'playtime' && $goal->game_appid) {
                $newProgress = $this->calculatePlaytimeProgress($steamData, $goal);
                
                if ($newProgress > $participation->individual_progress) {
                    $this->updateUserProgress($goal, $user, $newProgress);
                    $updatedGoals[] = $goal;
                }
            }
        }

        return $updatedGoals;
    }

    /**
     * Get goal recommendations for a server
     */
    public function getGoalRecommendations(Server $server): Collection
    {
        // Analyze server members' gaming patterns
        $memberGames = $this->analyzeServerGamingPatterns($server);
        $recommendations = collect();

        foreach ($memberGames as $gameData) {
            $gameAppId = $gameData['game_appid'];
            $playerCount = $gameData['player_count'];
            $avgPlaytime = $gameData['avg_playtime'];
            $avgAchievements = $gameData['avg_achievements'];

            // Recommend achievement-based goals
            if ($avgAchievements < 50) {
                $recommendations->push([
                    'type' => 'achievement',
                    'title' => "Master {$gameData['game_name']} Together",
                    'description' => "Let's work together to unlock more achievements in {$gameData['game_name']}!",
                    'game_appid' => $gameAppId,
                    'game_name' => $gameData['game_name'],
                    'target_value' => max(10, $playerCount * 5),
                    'difficulty' => $avgAchievements < 25 ? 'easy' : 'medium',
                    'estimated_participants' => $playerCount,
                ]);
            }

            // Recommend playtime-based goals
            if ($playerCount >= 5) {
                $recommendations->push([
                    'type' => 'playtime',
                    'title' => "{$gameData['game_name']} Community Hours Challenge",
                    'description' => "Let's rack up community hours in {$gameData['game_name']} together!",
                    'game_appid' => $gameAppId,
                    'game_name' => $gameData['game_name'],
                    'target_value' => $playerCount * 20, // 20 hours per active player
                    'difficulty' => 'medium',
                    'estimated_participants' => $playerCount,
                ]);
            }
        }

        return $recommendations->take(5);
    }

    /**
     * Get server goal statistics
     */
    public function getServerGoalStatistics(Server $server): array
    {
        $goals = $server->goals()->get();
        
        return [
            'total_goals' => $goals->count(),
            'active_goals' => $goals->where('status', 'active')->count(),
            'completed_goals' => $goals->where('status', 'completed')->count(),
            'total_participants' => $goals->sum('participant_count'),
            'completion_rate' => $goals->count() > 0 
                ? ($goals->where('status', 'completed')->count() / $goals->count()) * 100 
                : 0,
            'avg_participation' => $goals->count() > 0 
                ? $goals->avg('participant_count')
                : 0,
            'goals_by_type' => $goals->groupBy('goal_type')->map->count(),
            'goals_by_difficulty' => $goals->groupBy('difficulty')->map->count(),
            'recent_completions' => $goals->where('status', 'completed')
                ->where('completed_at', '>=', now()->subDays(30))
                ->count(),
        ];
    }

    /**
     * Get leaderboard for a goal
     */
    public function getGoalLeaderboard(ServerGoal $goal, int $limit = 10): Collection
    {
        return $goal->participants()
            ->where('participation_status', 'active')
            ->orderBy('individual_progress', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->with('user')
            ->take($limit)
            ->get()
            ->map(function ($participant, $index) {
                return [
                    'rank' => $index + 1,
                    'user' => $participant->user,
                    'progress' => $participant->individual_progress,
                    'contribution' => $participant->contribution_percentage,
                    'last_activity' => $participant->last_activity_at,
                ];
            });
    }

    /**
     * Check and update expired goals
     */
    public function processExpiredGoals(): array
    {
        $expiredGoals = ServerGoal::where('status', 'active')
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now())
            ->get();

        $processed = [];

        foreach ($expiredGoals as $goal) {
            if ($goal->completion_percentage >= 100) {
                $goal->update(['status' => 'completed', 'completed_at' => now()]);
                $processed['completed'][] = $goal;
            } else {
                $goal->update(['status' => 'failed']);
                $processed['failed'][] = $goal;
            }
        }

        return $processed;
    }

    /**
     * Calculate achievement progress from Steam data
     */
    protected function calculateAchievementProgress(array $steamData, ServerGoal $goal): int
    {
        $achievements = $steamData['achievements'] ?? [];
        $gameAchievements = $achievements[$goal->game_appid] ?? [];
        
        return $gameAchievements['unlocked'] ?? 0;
    }

    /**
     * Calculate playtime progress from Steam data
     */
    protected function calculatePlaytimeProgress(array $steamData, ServerGoal $goal): int
    {
        $games = $steamData['games'] ?? [];
        
        foreach ($games as $game) {
            if ($game['appid'] == $goal->game_appid) {
                return intval(($game['playtime_forever'] ?? 0) / 60); // Convert to hours
            }
        }

        return 0;
    }

    /**
     * Analyze server members' gaming patterns
     */
    protected function analyzeServerGamingPatterns(Server $server): array
    {
        $members = $server->members()->with('profile')->get();
        $gameStats = [];

        foreach ($members as $member) {
            $steamData = $member->profile->steam_data ?? [];
            $games = $steamData['games'] ?? [];
            $achievements = $steamData['achievements'] ?? [];

            foreach ($games as $game) {
                $appId = $game['appid'];
                $playtime = ($game['playtime_forever'] ?? 0) / 60; // Convert to hours

                if ($playtime > 5) { // Only include games with 5+ hours
                    if (!isset($gameStats[$appId])) {
                        $gameStats[$appId] = [
                            'game_appid' => $appId,
                            'game_name' => $game['name'],
                            'player_count' => 0,
                            'total_playtime' => 0,
                            'total_achievements' => 0,
                            'achievement_players' => 0,
                        ];
                    }

                    $gameStats[$appId]['player_count']++;
                    $gameStats[$appId]['total_playtime'] += $playtime;

                    if (isset($achievements[$appId])) {
                        $gameStats[$appId]['total_achievements'] += $achievements[$appId]['unlocked'] ?? 0;
                        $gameStats[$appId]['achievement_players']++;
                    }
                }
            }
        }

        // Calculate averages
        foreach ($gameStats as &$stats) {
            $stats['avg_playtime'] = $stats['player_count'] > 0 
                ? $stats['total_playtime'] / $stats['player_count'] 
                : 0;
            $stats['avg_achievements'] = $stats['achievement_players'] > 0 
                ? $stats['total_achievements'] / $stats['achievement_players'] 
                : 0;
        }

        // Sort by player count and return top games
        usort($gameStats, function ($a, $b) {
            return $b['player_count'] - $a['player_count'];
        });

        return array_slice($gameStats, 0, 10);
    }

    /**
     * Get user's skill score for a game
     */
    protected function getUserSkillScore(User $user, ?string $gameAppId): float
    {
        if (!$gameAppId) return 50;

        $steamData = $user->profile->steam_data ?? [];
        $skillMetrics = $steamData['skill_metrics'] ?? [];
        
        return $skillMetrics[$gameAppId]['skill_score'] ?? 50;
    }
}