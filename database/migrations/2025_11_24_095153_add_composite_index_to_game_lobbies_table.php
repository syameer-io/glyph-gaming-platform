<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds optimized composite index for fetching active lobbies by user.
     * This index targets the most common query pattern:
     * WHERE user_id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())
     */
    public function up(): void
    {
        Schema::table('game_lobbies', function (Blueprint $table) {
            // Drop the old basic index to avoid redundancy (if it exists)
            try {
                $table->dropIndex('idx_user_active');
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }
        });

        // Add optimized composite index using raw SQL to check existence first
        $connection = Schema::getConnection();
        $tableName = 'game_lobbies';
        $indexName = 'idx_user_active_lobby_query';

        // Check if index exists
        $driver = $connection->getDriverName();

        if ($driver === 'mysql') {
            $exists = $connection->select("SHOW INDEX FROM {$tableName} WHERE Key_name = ?", [$indexName]);
        } elseif ($driver === 'pgsql') {
            $exists = $connection->select("SELECT indexname FROM pg_indexes WHERE indexname = ?", [$indexName]);
        } else { // sqlite or others
            $exists = []; // For SQLite, just try to create it
        }

        if (empty($exists)) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->index(
                    ['user_id', 'is_active', 'expires_at'],
                    'idx_user_active_lobby_query'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_lobbies', function (Blueprint $table) {
            // Drop the composite index (if it exists)
            try {
                $table->dropIndex('idx_user_active_lobby_query');
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }
        });

        // Restore the original index only if it doesn't exist
        // For SQLite, we'll just try and catch the error
        try {
            Schema::table('game_lobbies', function (Blueprint $table) {
                $table->index(['user_id', 'is_active'], 'idx_user_active');
            });
        } catch (\Exception $e) {
            // Index already exists (from original migration), this is fine
            // This is expected behavior when rolling back in tests
        }
    }
};
