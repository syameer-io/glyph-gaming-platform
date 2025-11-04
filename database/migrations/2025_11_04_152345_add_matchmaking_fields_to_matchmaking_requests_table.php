<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds matchmaking algorithm required fields to matchmaking_requests table:
     * - preferred_regions: JSON array of region preferences (e.g., ['NA', 'EU'])
     * - activity_times: JSON array of time slots (e.g., ['morning', 'evening'])
     * - languages: JSON array of language codes (e.g., ['en', 'es'])
     *
     * Note: availability_hours already exists but will be used for activity_times
     * Note: additional_requirements already exists but will be restructured for languages
     */
    public function up(): void
    {
        Schema::table('matchmaking_requests', function (Blueprint $table) {
            // Preferred regions for region compatibility (15% weight)
            $table->json('preferred_regions')->nullable()->after('server_preferences')
                ->comment('Preferred regions: NA, EU, ASIA, SA, OCEANIA, AFRICA, MIDDLE_EAST');

            // Activity times for schedule matching (10% weight)
            // Note: availability_hours already exists, we'll use that instead
            // No new column needed for activity_times

            // Languages for language compatibility (5% weight)
            $table->json('languages')->nullable()->after('preferred_regions')
                ->comment('Languages spoken: en, es, zh, fr, de, pt, ru, ja, etc.');

            // Index for matchmaking queries
            $table->index(['game_appid', 'status', 'skill_level'], 'idx_matchmaking_requests');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matchmaking_requests', function (Blueprint $table) {
            $table->dropIndex('idx_matchmaking_requests');
            $table->dropColumn(['preferred_regions', 'languages']);
        });
    }
};
