<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the game_join_configurations table for storing game-specific join method configurations.
     * Each game can support multiple join methods (e.g., CS2 supports both steam_lobby and steam_connect).
     */
    public function up(): void
    {
        Schema::create('game_join_configurations', function (Blueprint $table) {
            $table->id();

            // References the game (for now using game_appid from user_gaming_preferences)
            // Will be converted to proper FK when games table exists
            $table->unsignedBigInteger('game_id')->nullable();

            // Join method details
            $table->enum('join_method', [
                'steam_lobby',
                'steam_connect',
                'lobby_code',
                'join_command',
                'private_match',
                'server_address',
                'manual_invite'
            ])->nullable(false);

            // Display configuration
            $table->string('display_name', 50)->nullable(false); // "Steam Lobby Link", "Party Code", etc.
            $table->string('icon', 50)->nullable(); // Icon identifier
            $table->integer('priority')->unsigned()->default(0); // Higher = shown first in UI

            // Validation rules
            $table->string('validation_pattern', 255)->nullable(); // Regex pattern for validation
            $table->boolean('requires_manual_setup')->default(false);

            // Game-specific data
            $table->integer('steam_app_id')->unsigned()->nullable(); // For Steam games
            $table->integer('default_port')->unsigned()->nullable(); // For server addresses
            $table->integer('expiration_minutes')->unsigned()->nullable(); // NULL = persistent

            // Instructions
            $table->text('instructions_how_to_create')->nullable(); // Markdown for "How to create lobby"
            $table->text('instructions_how_to_join')->nullable(); // Markdown for "How to join"

            // Feature flags
            $table->boolean('is_enabled')->default(true);

            $table->timestamps();

            // Unique constraint: One configuration per game per join method
            $table->unique(['game_id', 'join_method'], 'unique_game_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_join_configurations');
    }
};
