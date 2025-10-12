<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds CS2 Steam lobby link tracking to profiles table.
     *
     * Design Decisions:
     * - steam_lobby_link: Max 512 chars to accommodate full Steam URL format
     *   (steam://joinlobby/730/[lobbyid]/[steamid] plus any future params)
     * - steam_lobby_link_updated_at: Separate timestamp for precise 30-min expiration
     *   (can't use updated_at as it changes with any profile update)
     * - Both nullable: backward compatible, users may not have lobby active
     * - No index needed: queries will be user-specific (user_id already indexed via FK)
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Add columns after steam_data for logical grouping of Steam-related fields
            $table->string('steam_lobby_link', 512)->nullable()->after('steam_data');
            $table->timestamp('steam_lobby_link_updated_at')->nullable()->after('steam_lobby_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['steam_lobby_link', 'steam_lobby_link_updated_at']);
        });
    }
};
