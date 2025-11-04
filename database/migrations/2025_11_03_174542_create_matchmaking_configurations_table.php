<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('matchmaking_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('weights'); // ['skill' => 0.40, 'composition' => 0.25, ...]
            $table->json('thresholds')->nullable(); // ['min_compatibility' => 50, ...]
            $table->json('settings')->nullable(); // Region matrix, time ranges, etc.
            $table->string('applies_to')->default('all'); // Scope: all, game:730, server:5
            $table->timestamps();
        });

        // Insert default configuration
        DB::table('matchmaking_configurations')->insert([
            'name' => 'default',
            'description' => 'Default matchmaking weights (research-based)',
            'is_active' => true,
            'weights' => json_encode([
                'skill' => 0.40,
                'composition' => 0.25,
                'region' => 0.15,
                'schedule' => 0.10,
                'size' => 0.05,
                'language' => 0.05,
            ]),
            'thresholds' => json_encode([
                'min_compatibility' => 50,
                'max_results' => 10,
            ]),
            'settings' => json_encode([
                'enable_skill_penalty' => true,
                'skill_penalty_threshold' => 2,
                'skill_penalty_multiplier' => 0.5,
            ]),
            'applies_to' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Competitive CS2 configuration example
        DB::table('matchmaking_configurations')->insert([
            'name' => 'competitive_cs2',
            'description' => 'High skill weight for competitive CS2 matchmaking',
            'is_active' => false,
            'weights' => json_encode([
                'skill' => 0.50,      // Higher skill importance
                'composition' => 0.25,
                'region' => 0.15,
                'schedule' => 0.05,
                'size' => 0.03,
                'language' => 0.02,
            ]),
            'thresholds' => json_encode([
                'min_compatibility' => 60, // Stricter threshold
                'max_results' => 5,
            ]),
            'settings' => json_encode([
                'enable_skill_penalty' => true,
                'skill_penalty_threshold' => 1, // Penalize even 1-level gap
                'skill_penalty_multiplier' => 0.3, // Harsher penalty
            ]),
            'applies_to' => 'game:730',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Casual configuration example
        DB::table('matchmaking_configurations')->insert([
            'name' => 'casual',
            'description' => 'Relaxed matchmaking for casual play',
            'is_active' => false,
            'weights' => json_encode([
                'skill' => 0.20,      // Lower skill importance
                'composition' => 0.20,
                'region' => 0.20,
                'schedule' => 0.20,
                'size' => 0.10,
                'language' => 0.10,
            ]),
            'thresholds' => json_encode([
                'min_compatibility' => 40, // More lenient threshold
                'max_results' => 15,
            ]),
            'settings' => json_encode([
                'enable_skill_penalty' => false,
                'skill_penalty_threshold' => 3,
                'skill_penalty_multiplier' => 0.8,
            ]),
            'applies_to' => 'all',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_configurations');
    }
};
