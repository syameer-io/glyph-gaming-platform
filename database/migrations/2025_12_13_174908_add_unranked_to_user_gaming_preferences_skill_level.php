<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds 'unranked' to the skill_level enum for users with insufficient playtime.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE user_gaming_preferences MODIFY COLUMN skill_level ENUM('beginner', 'intermediate', 'advanced', 'expert', 'unranked') DEFAULT 'intermediate'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'unranked' to 'beginner' before removing the enum value
        DB::table('user_gaming_preferences')
            ->where('skill_level', 'unranked')
            ->update(['skill_level' => 'beginner']);

        DB::statement("ALTER TABLE user_gaming_preferences MODIFY COLUMN skill_level ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate'");
    }
};
