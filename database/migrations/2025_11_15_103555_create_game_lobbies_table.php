<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the game_lobbies table for multi-game lobby system.
     * This table stores lobby information for various games with different join methods.
     */
    public function up(): void
    {
        Schema::create('game_lobbies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Note: We'll add game_id foreign key after games table exists
            // For now, we reference user_gaming_preferences.game_appid indirectly
            $table->unsignedBigInteger('game_id')->nullable(); // Will be converted to FK later

            // Join method configuration
            $table->enum('join_method', [
                'steam_lobby',      // steam://joinlobby/[appid]/[lobbyid]/[profileid]
                'steam_connect',    // steam://connect/[ip]:[port]
                'lobby_code',       // Simple code (Among Us, Valorant, Fall Guys)
                'join_command',     // Chat command (Destiny 2)
                'private_match',    // Name + password (Rocket League)
                'server_address',   // IP:Port (Minecraft)
                'manual_invite'     // Friend-based (for future)
            ])->nullable(false);

            // Steam lobby data (for steam_lobby join method)
            $table->integer('steam_app_id')->unsigned()->nullable();
            $table->string('steam_lobby_id', 255)->nullable();
            $table->string('steam_profile_id', 255)->nullable();

            // Server connect data (for steam_connect and server_address join methods)
            $table->string('server_ip', 255)->nullable();
            $table->integer('server_port')->unsigned()->nullable();
            $table->string('server_password', 255)->nullable(); // Will be encrypted via model cast

            // Simple codes (for lobby_code join method)
            $table->string('lobby_code', 50)->nullable();

            // Commands (for join_command join method)
            $table->string('join_command', 255)->nullable();

            // Private match data (for private_match join method)
            $table->string('match_name', 100)->nullable();
            $table->string('match_password', 255)->nullable(); // Will be encrypted via model cast

            // Metadata
            $table->boolean('is_active')->default(true);
            $table->timestamp('expires_at')->nullable(); // NULL = persistent (dedicated servers)
            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'is_active'], 'idx_user_active');
            $table->index(['game_id', 'is_active'], 'idx_game_active');
            $table->index('expires_at', 'idx_expires');

            // Business rule: One active lobby per user per game
            // Using partial unique index for active lobbies only
            $table->unique(['user_id', 'game_id', 'is_active'], 'unique_user_game_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_lobbies');
    }
};
