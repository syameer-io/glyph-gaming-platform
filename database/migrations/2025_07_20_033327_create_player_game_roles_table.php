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
        Schema::create('player_game_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('game_appid');
            $table->string('game_name');
            $table->json('preferred_roles'); // Array of preferred roles (tank, dps, support, etc.)
            $table->json('role_ratings')->nullable(); // Individual skill ratings per role
            $table->enum('primary_role', ['tank', 'dps', 'support', 'flex', 'igl', 'entry', 'anchor', 'awper', 'rifler'])->nullable();
            $table->enum('secondary_role', ['tank', 'dps', 'support', 'flex', 'igl', 'entry', 'anchor', 'awper', 'rifler'])->nullable();
            $table->enum('experience_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('beginner');
            $table->decimal('overall_skill_rating', 5, 2)->nullable(); // 0-100 overall skill
            $table->json('availability_pattern')->nullable(); // Weekly availability pattern
            $table->json('playstyle_preferences')->nullable(); // Aggressive, defensive, supportive, etc.
            $table->json('communication_preferences')->nullable(); // Voice chat requirements, languages
            $table->boolean('open_to_coaching')->default(false);
            $table->boolean('open_to_leading')->default(false);
            $table->text('additional_notes')->nullable();
            $table->timestamp('last_updated_from_steam')->nullable(); // Last sync from Steam data
            $table->timestamps();

            // Unique constraint - one record per user per game
            $table->unique(['user_id', 'game_appid']);
            
            // Indexes for performance
            $table->index(['game_appid', 'primary_role']);
            $table->index(['game_appid', 'experience_level']);
            $table->index(['user_id', 'experience_level']);
            $table->index(['overall_skill_rating']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_game_roles');
    }
};
