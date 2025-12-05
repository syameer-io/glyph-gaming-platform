<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'unranked' to the skill_level ENUM for matchmaking_requests table.
     * This supports the Auto-Skill Calculation feature where users without
     * sufficient game data will be marked as 'unranked' instead of forcing
     * them to select a skill level.
     */
    public function up(): void
    {
        // Modify ENUM to add 'unranked' option
        DB::statement("ALTER TABLE matchmaking_requests MODIFY COLUMN skill_level ENUM('any', 'beginner', 'intermediate', 'advanced', 'expert', 'unranked') DEFAULT 'any'");
    }

    /**
     * Reverse the migrations.
     *
     * Before removing 'unranked' from ENUM, we need to update any existing
     * records that use 'unranked' to a valid fallback value ('intermediate').
     */
    public function down(): void
    {
        // First, update any 'unranked' values to 'intermediate' as fallback
        DB::statement("UPDATE matchmaking_requests SET skill_level = 'intermediate' WHERE skill_level = 'unranked'");

        // Then remove 'unranked' from the ENUM
        DB::statement("ALTER TABLE matchmaking_requests MODIFY COLUMN skill_level ENUM('any', 'beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'any'");
    }
};
