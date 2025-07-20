<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MatchmakingApiController;

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