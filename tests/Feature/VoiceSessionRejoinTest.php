<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Server;
use App\Models\Channel;
use App\Models\VoiceSession;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Voice Session Rejoin Test
 *
 * Tests the fix for the voice chat rejoin issue where users would get
 * a 500 error when trying to join again after disconnecting.
 */
class VoiceSessionRejoinTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Server $server;
    protected Channel $voiceChannel;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'display_name' => 'Test User',
        ]);

        // Create test server
        $this->server = Server::create([
            'name' => 'Test Server',
            'description' => 'Test server for voice chat',
            'invite_code' => 'TEST123',
            'created_by' => $this->user->id,
        ]);

        // Add user as server member
        $this->server->members()->attach($this->user->id, [
            'joined_at' => now(),
            'is_banned' => false,
            'is_muted' => false,
        ]);

        // Create voice channel
        $this->voiceChannel = Channel::create([
            'server_id' => $this->server->id,
            'name' => 'Test Voice Channel',
            'type' => 'voice',
        ]);
    }

    /**
     * Test user can join voice channel successfully
     */
    public function test_user_can_join_voice_channel(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'token',
                'channel_name',
                'uid',
                'expires_at',
                'session',
            ]);

        // Verify session was created
        $this->assertDatabaseHas('voice_sessions', [
            'user_id' => $this->user->id,
            'channel_id' => $this->voiceChannel->id,
            'server_id' => $this->server->id,
            'agora_channel_name' => "voice-channel-{$this->voiceChannel->id}",
        ]);
    }

    /**
     * Test user can leave voice channel successfully
     */
    public function test_user_can_leave_voice_channel(): void
    {
        // First join
        $this->actingAs($this->user)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ])
            ->assertStatus(200);

        // Then leave
        $response = $this->actingAs($this->user)
            ->postJson('/voice/leave', [
                'channel_id' => $this->voiceChannel->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'session' => [
                    'left_at',
                    'duration_seconds',
                    'duration_formatted',
                ],
            ]);

        // Verify session was ended (left_at is set)
        $session = VoiceSession::where('user_id', $this->user->id)
            ->where('channel_id', $this->voiceChannel->id)
            ->latest('joined_at')
            ->first();

        $this->assertNotNull($session->left_at);
    }

    /**
     * Test user can rejoin voice channel after leaving (THE MAIN FIX)
     */
    public function test_user_can_rejoin_voice_channel_after_leaving(): void
    {
        // First join
        $firstJoin = $this->actingAs($this->user)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ])
            ->assertStatus(200);

        $firstSessionId = $firstJoin->json('session.id');

        // Leave
        $this->actingAs($this->user)
            ->postJson('/voice/leave', [
                'channel_id' => $this->voiceChannel->id,
            ])
            ->assertStatus(200);

        // Rejoin - THIS SHOULD NOW WORK (previously returned 500)
        $secondJoin = $this->actingAs($this->user)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ]);

        $secondJoin->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $secondSessionId = $secondJoin->json('session.id');

        // Verify a new session was created
        $this->assertNotEquals($firstSessionId, $secondSessionId);

        // Verify we have 2 total sessions (1 ended, 1 active)
        $this->assertEquals(2, VoiceSession::where('user_id', $this->user->id)
            ->where('channel_id', $this->voiceChannel->id)
            ->count());

        // Verify first session is ended
        $firstSession = VoiceSession::find($firstSessionId);
        $this->assertNotNull($firstSession->left_at);

        // Verify second session is active
        $secondSession = VoiceSession::find($secondSessionId);
        $this->assertNull($secondSession->left_at);
    }

    /**
     * Test multiple users can join the same voice channel simultaneously
     */
    public function test_multiple_users_can_join_same_voice_channel(): void
    {
        // Create second user
        $user2 = User::create([
            'username' => 'testuser2',
            'email' => 'test2@example.com',
            'password' => bcrypt('password'),
            'display_name' => 'Test User 2',
        ]);
        $this->server->members()->attach($user2->id, [
            'joined_at' => now(),
            'is_banned' => false,
            'is_muted' => false,
        ]);

        // First user joins
        $response1 = $this->actingAs($this->user)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ]);

        $response1->assertStatus(200);

        // Second user joins the SAME channel - should work with the fix
        $response2 = $this->actingAs($user2)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ]);

        $response2->assertStatus(200);

        // Verify both have the same agora_channel_name
        $session1 = VoiceSession::find($response1->json('session.id'));
        $session2 = VoiceSession::find($response2->json('session.id'));

        $this->assertEquals($session1->agora_channel_name, $session2->agora_channel_name);
        $this->assertEquals("voice-channel-{$this->voiceChannel->id}", $session1->agora_channel_name);

        // Verify both sessions are active
        $this->assertNull($session1->left_at);
        $this->assertNull($session2->left_at);
    }

    /**
     * Test rejoining without leaving returns existing session
     */
    public function test_rejoining_without_leaving_returns_existing_session(): void
    {
        // First join
        $firstJoin = $this->actingAs($this->user)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ])
            ->assertStatus(200);

        $firstSessionId = $firstJoin->json('session.id');

        // Try to join again without leaving - should return same session
        $secondJoin = $this->actingAs($this->user)
            ->postJson('/voice/join', [
                'channel_id' => $this->voiceChannel->id,
            ])
            ->assertStatus(200);

        $secondSessionId = $secondJoin->json('session.id');

        // Should be the same session
        $this->assertEquals($firstSessionId, $secondSessionId);

        // Verify only 1 session exists
        $this->assertEquals(1, VoiceSession::where('user_id', $this->user->id)
            ->where('channel_id', $this->voiceChannel->id)
            ->count());
    }
}
