<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\GameLobbyService;
use App\Services\LobbyStatusService;
use App\Models\GameJoinConfiguration;
use App\Models\GameLobby;

class LobbyPageController extends Controller
{
    protected GameLobbyService $lobbyService;
    protected LobbyStatusService $lobbyStatusService;

    public function __construct(GameLobbyService $lobbyService, LobbyStatusService $lobbyStatusService)
    {
        $this->lobbyService = $lobbyService;
        $this->lobbyStatusService = $lobbyStatusService;
    }

    /**
     * Display the lobbies page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // Get combined games for lobby creation (owned + all supported)
        $combinedGames = $user->getCombinedLobbyGames();

        // Get user's active lobbies for sidebar
        $activeLobbies = $user->activeLobbies()
            ->orderBy('created_at', 'desc')
            ->get();

        // Get unique games for filter dropdown
        $supportedGames = GameJoinConfiguration::enabled()
            ->select('game_id')
            ->distinct()
            ->get()
            ->map(fn($config) => [
                'game_id' => $config->game_id,
                'game_name' => GameLobby::getGameNameById($config->game_id),
            ]);

        // Get friend IDs for real-time lobby updates (Phase 3)
        $friendIds = $user->friends()
            ->wherePivot('status', 'accepted')
            ->pluck('users.id')
            ->toArray();

        return view('lobbies.index', compact(
            'user',
            'combinedGames',
            'activeLobbies',
            'supportedGames',
            'friendIds'
        ));
    }
}
