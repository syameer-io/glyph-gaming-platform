<?php

namespace App\Http\Controllers;

use App\Services\ServerRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServerRecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(ServerRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 5);
        
        $recommendations = $this->recommendationService->getRecommendationsForUser($user, $limit);
        
        return view('servers.recommendations', compact('recommendations'));
    }

    public function api(Request $request)
    {
        $user = Auth::user();
        $limit = $request->get('limit', 5);
        
        $recommendations = $this->recommendationService->getRecommendationsForUser($user, $limit);
        
        return response()->json([
            'recommendations' => $recommendations->map(function($rec) {
                return [
                    'server' => [
                        'id' => $rec['server']->id,
                        'name' => $rec['server']->name,
                        'description' => $rec['server']->description,
                        'icon_url' => $rec['server']->icon_url,
                        'member_count' => $rec['server']->members->count(),
                        'invite_code' => $rec['server']->invite_code,
                    ],
                    'score' => round($rec['score'], 1),
                    'reasons' => $rec['reasons'],
                ];
            })
        ]);
    }

    public function discover(Request $request)
    {
        \Log::info('ServerRecommendationController::discover called');
        $user = Auth::user();
        
        // Get all servers with filters
        $query = \App\Models\Server::with(['tags', 'members']);
        
        // Apply filters
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->filled('game')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tag_type', 'game')->where('tag_value', $request->game);
            });
        }
        
        if ($request->filled('skill_level')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tag_type', 'skill_level')->where('tag_value', $request->skill_level);
            });
        }
        
        if ($request->filled('region')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tag_type', 'region')->where('tag_value', $request->region);
            });
        }
        
        if ($request->filled('language')) {
            $query->whereHas('tags', function($q) use ($request) {
                $q->where('tag_type', 'language')->where('tag_value', $request->language);
            });
        }
        
        // Apply sorting
        switch ($request->get('sort', 'recommended')) {
            case 'members':
                $query->withCount('members')->orderBy('members_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default: // recommended
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        $servers = $query->paginate(10);
        
        // Get recommendations for compatibility scores
        $serverRecommendations = [];
        if ($user->steam_id) {
            $allRecommendations = $this->recommendationService->getRecommendationsForUser($user, 50);
            foreach ($allRecommendations as $rec) {
                $serverRecommendations[$rec['server']->id] = $rec;
            }
        }
        
        \Log::info('ServerRecommendationController::discover returning view');
        return view('servers.discover', compact('servers', 'serverRecommendations'));
    }
}
