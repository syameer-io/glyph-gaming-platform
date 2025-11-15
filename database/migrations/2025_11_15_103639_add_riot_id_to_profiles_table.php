<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds riot_id field to profiles table for Valorant community features.
     */
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Add riot_id after steam_lobby_link_updated_at for logical grouping
            $table->string('riot_id', 255)->nullable()->after('steam_lobby_link_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('riot_id');
        });
    }
};
