<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Fix voice sessions table to allow multiple users in the same Agora channel.
     * The unique constraint on agora_channel_name was incorrect as multiple users
     * should be able to join the same voice channel simultaneously.
     */
    public function up(): void
    {
        Schema::table('voice_sessions', function (Blueprint $table) {
            // Drop the incorrect unique constraint on agora_channel_name
            $table->dropUnique(['agora_channel_name']);

            // Add index on agora_channel_name for query performance
            $table->index('agora_channel_name', 'idx_agora_channel');

            // Add composite index to ensure a user cannot have multiple active sessions
            // in the same channel (active sessions have left_at = NULL)
            $table->index(['user_id', 'channel_id', 'left_at'], 'idx_user_channel_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voice_sessions', function (Blueprint $table) {
            // Restore the unique constraint (for rollback purposes only)
            $table->unique('agora_channel_name');

            // Drop the indexes we added
            $table->dropIndex('idx_agora_channel');
            $table->dropIndex('idx_user_channel_active');
        });
    }
};
