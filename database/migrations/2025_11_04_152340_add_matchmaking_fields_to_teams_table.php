<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds matchmaking algorithm required fields to teams table:
     * - required_roles: JSON array of roles team needs (e.g., ['entry_fragger', 'awper'])
     * - activity_times: JSON array of time slots (e.g., ['morning', 'evening'])
     * - languages: JSON array of language codes (e.g., ['en', 'es'])
     */
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            // Required roles for team composition matching (25% weight)
            $table->json('required_roles')->nullable()->after('team_data')
                ->comment('Roles this team is looking for: entry_fragger, support, awper, igl, lurker, etc.');

            // Activity times for schedule matching (10% weight)
            $table->json('activity_times')->nullable()->after('required_roles')
                ->comment('Time slots when team is active: morning, afternoon, evening, night, flexible');

            // Languages for language compatibility (5% weight)
            $table->json('languages')->nullable()->after('activity_times')
                ->comment('Languages spoken by team: en, es, zh, fr, de, pt, ru, ja, etc.');

            // Index for matchmaking queries
            $table->index(['game_appid', 'status', 'skill_level'], 'idx_matchmaking_teams');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropIndex('idx_matchmaking_teams');
            $table->dropColumn(['required_roles', 'activity_times', 'languages']);
        });
    }
};
