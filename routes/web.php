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
use App\Http\Controllers\TeamJoinRequestController;
use App\Http\Controllers\ServerGoalController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\VoiceController;
use App\Http\Controllers\Admin\MatchmakingConfigurationController;
use App\Http\Controllers\DirectMessageController;

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

    // CS2 Lobby link management (Phase 4)
    Route::post('/profile/lobby-link', [ProfileController::class, 'updateLobbyLink'])
        ->name('profile.lobby-link.update')
        ->middleware('throttle:10,1'); // 10 updates per minute
    Route::post('/profile/lobby-link/clear', [ProfileController::class, 'clearLobbyLink'])
        ->name('profile.lobby-link.clear');

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
    Route::post('/servers/{server}/join-direct', [ServerController::class, 'joinDirect'])->name('server.join.direct');
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
        Route::post('/find-teams', [MatchmakingController::class, 'findCompatibleTeamsForRequest'])->name('find.teams');
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
        Route::post('/{team}/join-direct', [TeamController::class, 'joinTeamDirect'])->name('join.direct'); // Direct join from teams page
        Route::post('/{team}/join', [MatchmakingController::class, 'joinTeam'])->name('join'); // Matchmaking join
        Route::post('/{team}/members', [TeamController::class, 'addMember'])->name('members.add');
        Route::delete('/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('members.remove');
        Route::put('/{team}/members/{user}/role', [TeamController::class, 'updateMemberRole'])->name('members.role.update');

        // Team join request management (Phase 6)
        Route::post('/{team}/join-requests', [TeamJoinRequestController::class, 'store'])->name('join.request.store');
        Route::get('/{team}/join-requests', [TeamJoinRequestController::class, 'index'])->name('join.request.index');
        Route::post('/{team}/join-requests/{joinRequest}/approve', [TeamJoinRequestController::class, 'approve'])->name('join.request.approve');
        Route::post('/{team}/join-requests/{joinRequest}/reject', [TeamJoinRequestController::class, 'reject'])->name('join.request.reject');
        Route::delete('/{team}/join-requests/{joinRequest}', [TeamJoinRequestController::class, 'cancel'])->name('join.request.cancel');

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

    // Telegram Bot Integration routes
    Route::prefix('servers/{server}/telegram')->name('server.telegram.')->group(function () {
        Route::get('/status', [TelegramController::class, 'getServerStatus'])->name('status');
        Route::post('/link', [TelegramController::class, 'linkServer'])->name('link');
        Route::delete('/unlink', [TelegramController::class, 'unlinkServer'])->name('unlink');
        Route::patch('/settings', [TelegramController::class, 'updateNotificationSettings'])->name('settings');
        Route::post('/test', [TelegramController::class, 'testMessage'])->name('test');
    });

    // Voice chat routes (Agora.io WebRTC integration)
    Route::prefix('voice')->name('voice.')->group(function () {
        Route::post('/join', [VoiceController::class, 'join'])->name('join');
        Route::post('/leave', [VoiceController::class, 'leave'])->name('leave');
        Route::post('/mute', [VoiceController::class, 'toggleMute'])->name('mute');
        Route::get('/channel/{channelId}/participants', [VoiceController::class, 'getParticipants'])->name('participants');
        Route::get('/stats', [VoiceController::class, 'getUserStats'])->name('stats');
    });

    // Direct Messages (Friends DM) routes
    Route::prefix('dm')->name('dm.')->group(function () {
        // Conversation list
        Route::get('/', [DirectMessageController::class, 'index'])->name('index');

        // Start new conversation (or get existing) with first message
        Route::post('/start', [DirectMessageController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name('store');

        // Get or create conversation with specific user (route model binding)
        Route::get('/with/{user}', [DirectMessageController::class, 'getConversationWith'])->name('with');

        // Conversation-specific routes
        Route::prefix('/{conversation}')->group(function () {
            // View conversation
            Route::get('/', [DirectMessageController::class, 'show'])->name('show');

            // Search messages in conversation
            Route::get('/search', [DirectMessageController::class, 'searchMessages'])->name('search');

            // Send message in conversation
            Route::post('/messages', [DirectMessageController::class, 'sendMessage'])
                ->middleware('throttle:60,1')
                ->name('message.send');

            // Load more messages (infinite scroll)
            Route::get('/messages/more', [DirectMessageController::class, 'loadMoreMessages'])->name('message.more');

            // Mark messages as read
            Route::post('/read', [DirectMessageController::class, 'markAsRead'])->name('read');

            // Edit message
            Route::put('/messages/{message}', [DirectMessageController::class, 'editMessage'])->name('message.edit');

            // Delete message
            Route::delete('/messages/{message}', [DirectMessageController::class, 'deleteMessage'])->name('message.delete');

            // Typing indicator
            Route::post('/typing', [DirectMessageController::class, 'typing'])->name('typing');
        });
    });

});

// Public Telegram webhook (outside auth middleware)
Route::post('/telegram/webhook', [TelegramController::class, 'webhook'])->name('telegram.webhook');

// Temporary testing routes for Telegram bot
Route::get('/test-telegram', function () {
    $telegramService = app(\App\Services\TelegramBotService::class);
    
    // Test bot info
    $botInfo = $telegramService->getBotInfo();
    
    return response()->json([
        'bot_info' => $botInfo,
        'webhook_url' => route('telegram.webhook'),
        'app_url' => config('app.url')
    ]);
});

Route::post('/test-telegram-webhook', function (Illuminate\Http\Request $request) {
    // Simulate a Telegram webhook call for testing
    $testUpdate = [
        'update_id' => 123456,
        'message' => [
            'message_id' => 1,
            'from' => [
                'id' => 12345,
                'is_bot' => false,
                'first_name' => 'Test User'
            ],
            'chat' => [
                'id' => $request->input('chat_id', 12345),
                'type' => 'private'
            ],
            'date' => time(),
            'text' => $request->input('text', '/start')
        ]
    ];
    
    $telegramController = app(\App\Http\Controllers\TelegramController::class);
    
    // Create a request with the test data
    $webhookRequest = new Illuminate\Http\Request();
    $webhookRequest->merge($testUpdate);
    
    try {
        $response = $telegramController->webhook($webhookRequest);
        return response()->json([
            'status' => 'success',
            'webhook_response' => $response->getData(),
            'test_update' => $testUpdate
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
});

// Admin Telegram management routes (for bot setup)
Route::middleware('auth')->prefix('admin/telegram')->name('admin.telegram.')->group(function () {
    Route::get('/info', [TelegramController::class, 'getBotInfo'])->name('info');
    Route::post('/webhook/set', [TelegramController::class, 'setWebhook'])->name('webhook.set');
    Route::delete('/webhook', [TelegramController::class, 'removeWebhook'])->name('webhook.remove');
});

// Admin Matchmaking Configuration routes (Phase 6)
Route::middleware('auth')->prefix('admin/matchmaking')->name('admin.matchmaking.')->group(function () {
    // Configuration CRUD
    Route::resource('configurations', MatchmakingConfigurationController::class);

    // Activate configuration
    Route::post('configurations/{configuration}/activate', [MatchmakingConfigurationController::class, 'activate'])
        ->name('configurations.activate');

    // Analytics dashboard
    Route::get('analytics/dashboard', [MatchmakingConfigurationController::class, 'analytics'])
        ->name('analytics.dashboard');
});

// TEMPORARY DIAGNOSTIC ROUTE - Remove after SSL issue is fixed
Route::get('/debug-php-config', function () {
    return response()->json([
        'loaded_php_ini' => php_ini_loaded_file(),
        'additional_ini_files' => php_ini_scanned_files(),
        'curl_ssl_settings' => [
            'curl.cainfo' => ini_get('curl.cainfo'),
            'openssl.cafile' => ini_get('openssl.cafile'),
            'openssl.capath' => ini_get('openssl.capath'),
        ],
        'php_version' => phpversion(),
        'php_sapi' => php_sapi_name(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'curl_version' => curl_version(),
        'expected_cacert_path' => 'C:\laragon\etc\ssl\cacert.pem',
        'cacert_exists' => file_exists('C:\laragon\etc\ssl\cacert.pem'),
        'cacert_readable' => is_readable('C:\laragon\etc\ssl\cacert.pem'),
    ]);
});