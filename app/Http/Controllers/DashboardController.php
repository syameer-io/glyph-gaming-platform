<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\ServerRecommendationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $user->load([
            'profile', 
            'servers',
            'friends' => function ($query) {
                $query->wherePivot('status', 'accepted')->with('profile');
            }
        ]);
        
        // Load teams relationship if it exists
        if (method_exists($user, 'teams')) {
            $user->load([
                'teams' => function ($query) {
                    $query->whereIn('teams.status', ['recruiting', 'full', 'active'])
                          ->with('activeMembers.user.profile');
                }
            ]);
        }
        
        // Load matchmaking requests if the method exists
        if (method_exists($user, 'activeMatchmakingRequests')) {
            $user->load('activeMatchmakingRequests');
        }

        // Get online friends
        $onlineFriends = $user->friends->filter(function ($friend) {
            return $friend->profile->status === 'online';
        });

        // Get server recommendations
        $recommendationService = app(ServerRecommendationService::class);
        $recommendations = $recommendationService->getRecommendationsForUser($user, 3);

        // Get recent activity from user's servers, teams, and friends
        $recentActivity = $this->getRecentActivity($user);

        return view('dashboard', compact('user', 'onlineFriends', 'recommendations', 'recentActivity'));
    }

    /**
     * Get recent activity from user's servers, teams, and friends
     */
    private function getRecentActivity(User $user): Collection
    {
        $sevenDaysAgo = now()->subDays(7);
        $userServerIds = $user->servers->pluck('id');
        $userTeamIds = $user->teams ? $user->teams->pluck('id') : collect();

        $activities = collect();

        // Skip if user has no servers
        if ($userServerIds->isEmpty()) {
            return $activities;
        }

        // 1. Messages from user's servers (not by the user)
        $messages = Message::with(['user:id,display_name', 'channel:id,name,server_id', 'channel.server:id,name'])
            ->whereHas('channel', fn($q) => $q->whereIn('server_id', $userServerIds))
            ->where('user_id', '!=', $user->id)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->map(fn($msg) => [
                'type' => 'message',
                'user' => $msg->user->display_name,
                'server' => $msg->channel->server->name ?? 'Unknown Server',
                'channel' => $msg->channel->name,
                'timestamp' => $msg->created_at,
                'time' => $msg->created_at->diffForHumans(),
            ]);

        // 2. Server joins (new members in user's servers, excluding user)
        $serverJoins = DB::table('server_members')
            ->join('users', 'server_members.user_id', '=', 'users.id')
            ->join('servers', 'server_members.server_id', '=', 'servers.id')
            ->whereIn('server_members.server_id', $userServerIds)
            ->where('server_members.user_id', '!=', $user->id)
            ->where('server_members.is_banned', false)
            ->where('server_members.joined_at', '>=', $sevenDaysAgo)
            ->orderBy('server_members.joined_at', 'desc')
            ->limit(15)
            ->select([
                'users.display_name as user_name',
                'servers.name as server_name',
                'server_members.joined_at as timestamp',
            ])
            ->get()
            ->map(fn($join) => [
                'type' => 'join',
                'user' => $join->user_name,
                'server' => $join->server_name,
                'channel' => null,
                'timestamp' => Carbon::parse($join->timestamp),
                'time' => Carbon::parse($join->timestamp)->diffForHumans(),
            ]);

        // 3. Team joins (new members in user's teams)
        $teamJoins = collect();
        if ($userTeamIds->isNotEmpty()) {
            $teamJoins = TeamMember::with(['user:id,display_name', 'team:id,name,game_name'])
                ->whereIn('team_id', $userTeamIds)
                ->where('user_id', '!=', $user->id)
                ->where('status', 'active')
                ->where('joined_at', '>=', $sevenDaysAgo)
                ->orderBy('joined_at', 'desc')
                ->limit(15)
                ->get()
                ->map(fn($member) => [
                    'type' => 'team_join',
                    'user' => $member->user->display_name,
                    'server' => $member->team->name,
                    'channel' => $member->team->game_name,
                    'timestamp' => $member->joined_at,
                    'time' => $member->joined_at->diffForHumans(),
                ]);
        }

        // 4. Friend acceptances (people who accepted user's friend requests)
        $friendAccepts = DB::table('friends')
            ->join('users', 'friends.friend_id', '=', 'users.id')
            ->where('friends.user_id', $user->id)
            ->where('friends.status', 'accepted')
            ->where('friends.updated_at', '>=', $sevenDaysAgo)
            ->orderBy('friends.updated_at', 'desc')
            ->limit(10)
            ->select([
                'users.display_name as user_name',
                'friends.updated_at as timestamp',
            ])
            ->get()
            ->map(fn($friend) => [
                'type' => 'friend_accept',
                'user' => $friend->user_name,
                'server' => null,
                'channel' => null,
                'timestamp' => Carbon::parse($friend->timestamp),
                'time' => Carbon::parse($friend->timestamp)->diffForHumans(),
            ]);

        // Merge all activities, sort by timestamp desc, take 5
        return $activities
            ->concat($messages)
            ->concat($serverJoins)
            ->concat($teamJoins)
            ->concat($friendAccepts)
            ->sortByDesc('timestamp')
            ->take(5)
            ->values();
    }
}