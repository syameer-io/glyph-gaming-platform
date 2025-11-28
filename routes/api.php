<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MatchmakingApiController;
use App\Http\Controllers\Api\LobbyStatusController;
use App\Http\Controllers\Api\UserStatusController;
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
// Note: Using 'web' middleware to enable session-based auth for same-origin requests
Route::middleware(['web', 'auth'])->group(function () {
    // Lobby management
    Route::post('/lobbies', [LobbyController::class, 'store'])->middleware('throttle:15,1');
    Route::put('/lobbies/{lobby}', [LobbyController::class, 'update']);
    Route::delete('/lobbies/{lobby}', [LobbyController::class, 'destroy']);
    Route::get('/lobbies/my-lobbies', [LobbyController::class, 'myLobbies']);

    // Lobby status endpoints for integration (Phase 1)
    Route::post('/lobbies/bulk-status', [LobbyStatusController::class, 'bulkStatus'])
        ->middleware('throttle:60,1');
    Route::get('/users/{user}/lobbies', [LobbyStatusController::class, 'userLobbies'])
        ->middleware('throttle:120,1');
    Route::get('/users/{user}/has-lobby', [LobbyStatusController::class, 'hasLobby'])
        ->middleware('throttle:120,1');

    // Game configurations
    Route::get('/games/{gameId}/join-methods', [LobbyController::class, 'getGameJoinMethods']);
});

// Phase 2: User Status API Routes
// Note: Using 'web' middleware for session-based auth (same-origin requests)
Route::middleware(['web', 'auth'])->prefix('status')->group(function () {
    // Update user status (online, idle, dnd, offline)
    Route::post('/', [UserStatusController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('api.status.update');

    // Set custom status (text + emoji with optional expiry)
    Route::post('/custom', [UserStatusController::class, 'setCustomStatus'])
        ->middleware('throttle:20,1')
        ->name('api.status.custom.set');

    // Clear custom status
    Route::delete('/custom', [UserStatusController::class, 'clearCustomStatus'])
        ->middleware('throttle:20,1')
        ->name('api.status.custom.clear');

    // Bulk fetch statuses for multiple users
    Route::post('/bulk', [UserStatusController::class, 'bulkStatus'])
        ->middleware('throttle:60,1')
        ->name('api.status.bulk');
});

// Get specific user's status (public endpoint for members)
Route::middleware(['web', 'auth'])->get('/users/{user}/status', [UserStatusController::class, 'getStatus'])
    ->middleware('throttle:120,1')
    ->name('api.users.status');