<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MatchmakingApiController;
use App\Http\Controllers\LobbyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Matchmaking API Routes
Route::middleware(['auth'])->prefix('matchmaking')->group(function () {
    Route::get('/active-requests', [MatchmakingApiController::class, 'getActiveRequests']);
    Route::post('/find-compatible-teams', [MatchmakingApiController::class, 'findCompatibleTeams']);
    Route::get('/live-recommendations', [MatchmakingApiController::class, 'getLiveRecommendations']);
    Route::get('/live-team-updates', [MatchmakingApiController::class, 'getLiveTeamUpdates']);
});

// Game Lobby API Routes
Route::middleware(['auth'])->group(function () {
    // Lobby management
    Route::post('/lobbies', [LobbyController::class, 'store'])->middleware('throttle:5,60');
    Route::put('/lobbies/{lobby}', [LobbyController::class, 'update']);
    Route::delete('/lobbies/{lobby}', [LobbyController::class, 'destroy']);
    Route::get('/lobbies/my-lobbies', [LobbyController::class, 'myLobbies']);

    // Game configurations
    Route::get('/games/{gameId}/join-methods', [LobbyController::class, 'getGameJoinMethods']);
});