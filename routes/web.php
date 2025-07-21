<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SteamAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ServerAdminController;
use App\Http\Controllers\ServerRecommendationController;
use App\Http\Controllers\MatchmakingController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ServerGoalController;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    
    Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('verify.otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('resend.otp')->middleware('throttle:3,1');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Steam Authentication
    Route::get('/steam/link', [SteamAuthController::class, 'showLinkPage'])->name('steam.link');
    Route::get('/steam/auth', [SteamAuthController::class, 'redirect'])->name('steam.auth');
    Route::get('/auth/steam/callback', [SteamAuthController::class, 'callback'])->name('steam.callback');

    // Profile routes
    Route::get('/profile/{username}', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/refresh-steam', [ProfileController::class, 'refreshSteamData'])->name('profile.steam.refresh');

    // Friend routes
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::get('/friends/search', [FriendController::class, 'search'])->name('friends.search');
    Route::post('/friends/search', [FriendController::class, 'searchUsers'])->name('friends.search.users');
    Route::post('/friends/request', [FriendController::class, 'sendRequest'])->name('friends.request');
    Route::post('/friends/accept/{user}', [FriendController::class, 'acceptRequest'])->name('friends.accept');
    Route::post('/friends/decline/{user}', [FriendController::class, 'declineRequest'])->name('friends.decline');
    Route::delete('/friends/{user}', [FriendController::class, 'removeFriend'])->name('friends.remove');

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/account', [SettingsController::class, 'updateAccount'])->name('settings.account');
    Route::put('/settings/privacy', [SettingsController::class, 'updatePrivacy'])->name('settings.privacy');

    // Server routes
    Route::get('/servers/create', [ServerController::class, 'create'])->name('server.create');
    Route::get('/servers/discover', [ServerRecommendationController::class, 'discover'])->name('servers.discover');
    Route::get('/servers/join', [ServerController::class, 'showJoinPage'])->name('server.join');
    Route::post('/servers', [ServerController::class, 'store'])->name('server.store');
    Route::post('/servers/join', [ServerController::class, 'join'])->name('server.join.submit');
    Route::get('/servers/{server}', [ServerController::class, 'show'])->name('server.show');
    Route::post('/servers/{server}/leave', [ServerController::class, 'leave'])->name('server.leave');
    Route::delete('/servers/{server}', [ServerController::class, 'destroy'])->name('server.destroy');
    
    // Channel routes
    Route::get('/servers/{server}/channels/{channel}', [ChannelController::class, 'show'])->name('channel.show');
    Route::post('/servers/{server}/channels/{channel}/messages', [ChannelController::class, 'sendMessage'])->name('channel.message.send');
    Route::put('/servers/{server}/channels/{channel}/messages/{message}', [ChannelController::class, 'editMessage'])->name('channel.message.edit');
    Route::delete('/servers/{server}/channels/{channel}/messages/{message}', [ChannelController::class, 'deleteMessage'])->name('channel.message.delete');

    // Server Admin routes
    Route::prefix('servers/{server}/admin')->name('server.admin.')->group(function () {
        Route::get('/settings', [ServerAdminController::class, 'settings'])->name('settings');
        Route::put('/update', [ServerAdminController::class, 'update'])->name('update');
        
        // Channel management
        Route::post('/channels', [ServerAdminController::class, 'createChannel'])->name('channel.create');
        Route::put('/channels/{channel}', [ServerAdminController::class, 'updateChannel'])->name('channel.update');
        Route::delete('/channels/{channel}', [ServerAdminController::class, 'deleteChannel'])->name('channel.delete');
        
        // Member management
        Route::post('/members/{member}/kick', [ServerAdminController::class, 'kickMember'])->name('member.kick');
        Route::post('/members/{member}/ban', [ServerAdminController::class, 'banMember'])->name('member.ban');
        Route::post('/members/{member}/unban', [ServerAdminController::class, 'unbanMember'])->name('member.unban');
        Route::post('/members/{member}/mute', [ServerAdminController::class, 'muteMember'])->name('member.mute');
        Route::post('/members/{member}/unmute', [ServerAdminController::class, 'unmuteMember'])->name('member.unmute');
        
        // Role management
        Route::post('/roles', [ServerAdminController::class, 'createRole'])->name('role.create');
        Route::put('/roles/{role}', [ServerAdminController::class, 'updateRole'])->name('role.update');
        Route::delete('/roles/{role}', [ServerAdminController::class, 'deleteRole'])->name('role.delete');
        Route::post('/roles/assign', [ServerAdminController::class, 'assignRole'])->name('role.assign');
        Route::delete('/roles/{role}/users/{user}', [ServerAdminController::class, 'removeRole'])->name('role.remove');
        
        // Tag management
        Route::post('/tags', [ServerAdminController::class, 'addTag'])->name('tag.add');
        Route::delete('/tags/{tagId}', [ServerAdminController::class, 'removeTag'])->name('tag.remove');
        Route::get('/tag-suggestions', [ServerAdminController::class, 'getTagSuggestions'])->name('tag.suggestions');
    });

    // Server Recommendations
    Route::get('/recommendations', [ServerRecommendationController::class, 'index'])->name('recommendations.index');
    Route::get('/api/recommendations', [ServerRecommendationController::class, 'api'])->name('api.server.recommendations');
    
    // Phase 3: Matchmaking routes
    Route::prefix('matchmaking')->name('matchmaking.')->group(function () {
        Route::get('/', [MatchmakingController::class, 'index'])->name('index');
        Route::post('/', [MatchmakingController::class, 'store'])->name('store');
        Route::post('/find-teammates', [MatchmakingController::class, 'findTeammates'])->name('find.teammates');
        Route::post('/find-teams', [MatchmakingController::class, 'findTeams'])->name('find.teams');
        Route::post('/auto-match', [MatchmakingController::class, 'autoMatch'])->name('auto.match');
        Route::delete('/requests/{request}', [MatchmakingController::class, 'cancelRequest'])->name('cancel');
        Route::get('/stats', [MatchmakingController::class, 'stats'])->name('stats');
        Route::get('/games', [MatchmakingController::class, 'availableGames'])->name('games');
    });

    // Phase 3: Team routes
    Route::prefix('teams')->name('teams.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/create', [TeamController::class, 'create'])->name('create');
        Route::post('/', [TeamController::class, 'store'])->name('store');
        Route::get('/{team}', [TeamController::class, 'show'])->name('show');
        Route::get('/{team}/edit', [TeamController::class, 'edit'])->name('edit');
        Route::put('/{team}', [TeamController::class, 'update'])->name('update');
        Route::delete('/{team}', [TeamController::class, 'destroy'])->name('destroy');
        
        // Team member management
        Route::post('/{team}/join', [MatchmakingController::class, 'joinTeam'])->name('join');
        Route::post('/{team}/members', [TeamController::class, 'addMember'])->name('members.add');
        Route::delete('/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('members.remove');
        Route::put('/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('members.role.update');
        
        // Team statistics and matchmaking
        Route::get('/{team}/stats', [TeamController::class, 'stats'])->name('stats');
        Route::get('/matchmaking/{gameAppid}', [TeamController::class, 'forMatchmaking'])->name('for.matchmaking');
    });

    // Phase 3: Server Goal Management routes
    Route::prefix('servers/{server}/goals')->name('server.goals.')->group(function () {
        Route::get('/', [ServerGoalController::class, 'index'])->name('index');
        Route::get('/create', [ServerGoalController::class, 'create'])->name('create');
        Route::post('/', [ServerGoalController::class, 'store'])->name('store');
        Route::get('/{goal}', [ServerGoalController::class, 'show'])->name('show');
        Route::get('/{goal}/edit', [ServerGoalController::class, 'edit'])->name('edit');
        Route::put('/{goal}', [ServerGoalController::class, 'update'])->name('update');
        Route::delete('/{goal}', [ServerGoalController::class, 'destroy'])->name('destroy');
        
        // Goal participation
        Route::post('/{goal}/join', [ServerGoalController::class, 'join'])->name('join');
        Route::post('/{goal}/leave', [ServerGoalController::class, 'leave'])->name('leave');
        Route::post('/{goal}/my-progress', [ServerGoalController::class, 'updateUserProgress'])->name('my.progress');
        
        // Goal management
        Route::post('/{goal}/progress', [ServerGoalController::class, 'updateProgress'])->name('progress.update');
        Route::post('/{goal}/sync', [ServerGoalController::class, 'syncProgress'])->name('sync');
        Route::get('/{goal}/stats', [ServerGoalController::class, 'stats'])->name('stats');
        Route::get('/{goal}/export', [ServerGoalController::class, 'export'])->name('export');
        
        // Administrative functions
        Route::get('/recommendations', [ServerGoalController::class, 'recommendations'])->name('recommendations');
        Route::post('/process-expired', [ServerGoalController::class, 'processExpired'])->name('process.expired');
    });

    // Phase 3: Public Goal routes (for non-admin users)
    Route::prefix('goals')->name('goals.')->group(function () {
        Route::get('/browse', function () {
            return redirect()->route('dashboard'); // Browse goals from dashboard
        })->name('browse');
    });

    // Phase 3: Enhanced Server Admin routes for Phase 3 features
    Route::prefix('servers/{server}/admin')->name('server.admin.')->group(function () {
        // Team management
        Route::get('/teams', function ($server) {
            return redirect()->route('teams.index', ['server_id' => $server]);
        })->name('teams');
        
        // Goal management (already handled by server.goals.* routes above)
        
        // Server insights and analytics
        Route::get('/insights', function () {
            // This could be handled by a future analytics controller
            return response()->json(['message' => 'Analytics coming soon']);
        })->name('insights');
        
        // Leaderboard management
        Route::get('/leaderboards', function () {
            // This could be handled by a future leaderboard controller
            return response()->json(['message' => 'Leaderboards coming soon']);
        })->name('leaderboards');
    });

    // Phase 3: API Routes for AJAX functionality
    Route::prefix('api')->name('api.')->group(function () {
        // Team API
        Route::get('/teams/{team}/members', function ($team) {
            return \App\Models\Team::findOrFail($team)->load('activeMembers.user');
        })->name('teams.members');
        
        // Matchmaking API  
        Route::get('/matchmaking/active-requests', function () {
            return auth()->user()->activeMatchmakingRequests()->with('server')->get();
        })->name('matchmaking.active');
        
        // Goal API
        Route::get('/goals/{goal}/leaderboard', function ($goal) {
            $goal = \App\Models\ServerGoal::findOrFail($goal);
            return app(\App\Services\ServerGoalService::class)->getGoalLeaderboard($goal, 10);
        })->name('goals.leaderboard');
        
        // Server stats API
        Route::get('/servers/{server}/community-health', function ($server) {
            return \App\Models\Server::findOrFail($server)->getCommunityHealth();
        })->name('servers.health');
        
        Route::get('/servers/{server}/gaming-activity', function ($server) {
            return \App\Models\Server::findOrFail($server)->getGamingActivity();
        })->name('servers.activity');
        
        // User gaming statistics
        Route::get('/users/{user}/gaming-stats', function ($user) {
            return \App\Models\User::findOrFail($user)->getGamingStatistics();
        })->name('users.gaming.stats');
    });
    
});