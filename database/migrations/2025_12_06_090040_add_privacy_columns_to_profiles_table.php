<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Core privacy settings
            $table->boolean('show_steam_data')->default(true)->after('steam_data');
            $table->boolean('show_online_status')->default(true)->after('show_steam_data');

            // Extended privacy options
            $table->boolean('show_gaming_activity')->default(true)->after('show_online_status');
            $table->boolean('show_steam_friends')->default(true)->after('show_gaming_activity');
            $table->boolean('show_servers')->default(true)->after('show_steam_friends');
            $table->boolean('show_lobbies_to_friends_only')->default(false)->after('show_servers');
            $table->boolean('profile_visible_to_friends_only')->default(false)->after('show_lobbies_to_friends_only');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn([
                'show_steam_data',
                'show_online_status',
                'show_gaming_activity',
                'show_steam_friends',
                'show_servers',
                'show_lobbies_to_friends_only',
                'profile_visible_to_friends_only',
            ]);
        });
    }
};
