<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\GameLobby;
use App\Models\Server;
use App\Models\Team;
use App\Services\LobbyStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1: Lobby Integration Backend Tests
 *
 * These tests verify:
 * 1. No N+1 query problems when loading users with lobbies
 * 2. Eager loading works correctly
 * 3. Service layer bulk operations work efficiently
 */
class LobbyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected LobbyStatusService $lobbyStatusService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->lobbyStatusService = app(LobbyStatusService::class);
    }

    /**
     * Test that bulk lobby fetching uses single query
     */
    public function test_bulk_lobby_fetch_prevents_n_plus_one()
    {
        // Create 10 users with lobbies
        $users = User::factory(10)->create();
        foreach ($users as $user) {
            GameLobby::factory()->create([
                'user_id' => $user->id,
                'is_active' => true,
                'expires_at' => now()->addHours(2),
            ]);
        }

        $userIds = $users->pluck('id')->toArray();

        // Enable query log
        DB::enableQueryLog();

        // Fetch lobbies for all users
        $lobbies = $this->lobbyStatusService->getActiveLobbiesForUsers($userIds);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should be 1 query (or 2 if counting the transaction)
        // The key is that it's NOT 10+ queries (one per user)
        $this->assertLessThanOrEqual(2, count($queries),
            'Bulk lobby fetch should use at most 2 queries, not N queries');

        // Verify all users' lobbies were fetched
        $this->assertCount(10, $lobbies);
    }

    /**
     * Test eager loading on server members
     */
    public function test_server_members_with_lobbies_eager_loaded()
    {
        // Create server with members
        $server = Server::factory()->create();
        $members = User::factory(5)->create();

        foreach ($members as $member) {
            $server->members()->attach($member->id, [
                'joined_at' => now(),
                'is_banned' => false,
                'is_muted' => false,
            ]);

            // Create lobby for each member
            GameLobby::factory()->create([
                'user_id' => $member->id,
                'is_active' => true,
            ]);
        }

        // Load server with members and lobbies using eager loading
        DB::enableQueryLog();

        $server->load([
            'members.activeLobbies'
        ]);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should use eager loading, not N+1 queries
        // Expected: 1 query for members, 1 query for all lobbies
        $this->assertLessThanOrEqual(3, count($queries),
            'Eager loading should prevent N+1 queries');

        // Verify lobbies are loaded
        foreach ($server->members as $member) {
            $this->assertTrue($member->relationLoaded('activeLobbies'));
        }
    }

    /**
     * Test User scope for active lobbies
     */
    public function test_user_scope_with_active_lobbies()
    {
        $users = User::factory(3)->create();

        // Create active lobbies for users
        foreach ($users as $user) {
            GameLobby::factory()->create([
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        DB::enableQueryLog();

        // Use the scope to load users with lobbies
        $usersWithLobbies = User::withActiveLobbies()->get();

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should use 2 queries: one for users, one for all lobbies
        $this->assertLessThanOrEqual(2, count($queries));

        // Verify lobbies are loaded
        foreach ($usersWithLobbies as $user) {
            $this->assertTrue($user->relationLoaded('activeLobbies'));
        }
    }

    /**
     * Test cache invalidation when lobby is created
     */
    public function test_cache_invalidation_on_lobby_create()
    {
        $user = User::factory()->create();

        // Warm cache
        $status = $user->getActiveLobbyStatus();
        $this->assertNull($status); // No lobby yet

        // Create lobby - should invalidate cache automatically via observer
        $lobby = GameLobby::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        // Fetch again - should see new lobby
        $status = $user->fresh()->getActiveLobbyStatus();
        $this->assertNotNull($status);
        $this->assertEquals($lobby->id, $status['id']);
    }

    /**
     * Test API bulk status endpoint
     */
    public function test_api_bulk_status_endpoint()
    {
        $this->actingAs(User::factory()->create());

        $users = User::factory(3)->create();
        foreach ($users as $user) {
            GameLobby::factory()->create([
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        $response = $this->postJson('/api/lobbies/bulk-status', [
            'user_ids' => $users->pluck('id')->toArray()
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'count'
            ]);

        $this->assertEquals(3, $response->json('count'));
    }

    /**
     * Test API single user lobby endpoint
     */
    public function test_api_user_lobbies_endpoint()
    {
        $this->actingAs(User::factory()->create());

        $user = User::factory()->create();
        $lobby = GameLobby::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/users/{$user->id}/lobbies");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'has_active_lobby' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'game_id',
                    'join_method',
                    'join_link',
                    'display_format'
                ]
            ]);
    }
}
