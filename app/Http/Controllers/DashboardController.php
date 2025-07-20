<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ServerRecommendationService;
use Illuminate\Http\Request;

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

        // Get recent server activity (mock data for now)
        $recentActivity = collect([
            [
                'type' => 'message',
                'user' => 'ProGamer One',
                'server' => 'Gaming Community Hub',
                'channel' => 'general',
                'time' => '5 minutes ago',
            ],
            [
                'type' => 'join',
                'user' => 'Elite Player',
                'server' => 'Gaming Community Hub',
                'time' => '1 hour ago',
            ],
        ]);

        return view('dashboard', compact('user', 'onlineFriends', 'recommendations', 'recentActivity'));
    }
}