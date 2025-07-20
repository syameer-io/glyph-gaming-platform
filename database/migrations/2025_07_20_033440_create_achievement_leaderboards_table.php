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
        Schema::create('achievement_leaderboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('game_appid')->nullable(); // Specific game or overall server ranking
            $table->string('game_name')->nullable();
            $table->integer('total_achievements')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
            $table->integer('rare_achievements')->default(0); // Achievements with <5% unlock rate
            $table->integer('rank_position')->default(0);
            $table->integer('points_scored')->default(0); // Weighted achievement points
            $table->decimal('skill_rating', 5, 2)->nullable(); // Overall skill rating for this game
            $table->json('achievement_breakdown')->nullable(); // Detailed achievement categories
            $table->json('recent_achievements')->nullable(); // Last 10 achievements with timestamps
            $table->timestamp('first_achievement_at')->nullable();
            $table->timestamp('latest_achievement_at')->nullable();
            $table->integer('achievement_streak')->default(0); // Current streak of days with achievements
            $table->integer('longest_streak')->default(0);
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('bronze');
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();

            // Unique constraint - one leaderboard entry per user per server per game
            $table->unique(['server_id', 'user_id', 'game_appid']);
            
            // Indexes for performance (with custom short names)
            $table->index(['server_id', 'game_appid', 'rank_position'], 'leaderboard_server_game_rank_idx');
            $table->index(['server_id', 'points_scored'], 'leaderboard_server_points_idx');
            $table->index(['game_appid', 'completion_percentage'], 'leaderboard_game_completion_idx');
            $table->index(['tier', 'points_scored'], 'leaderboard_tier_points_idx');
            $table->index(['user_id', 'server_id'], 'leaderboard_user_server_idx');
            $table->index(['latest_achievement_at'], 'leaderboard_latest_achievement_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievement_leaderboards');
    }
};
