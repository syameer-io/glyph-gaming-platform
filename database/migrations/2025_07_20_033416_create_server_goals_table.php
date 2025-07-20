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
        Schema::create('server_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->string('game_appid')->nullable(); // Specific game or server-wide goal
            $table->string('game_name')->nullable();
            $table->enum('goal_type', ['achievement', 'playtime', 'participation', 'community', 'custom'])->default('achievement');
            $table->json('target_criteria'); // Specific achievement IDs, playtime hours, etc.
            $table->integer('target_value'); // Target number (achievements, hours, participants)
            $table->integer('current_progress')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'extreme'])->default('medium');
            $table->enum('visibility', ['public', 'members_only', 'private'])->default('public');
            $table->enum('status', ['draft', 'active', 'completed', 'failed', 'cancelled'])->default('draft');
            $table->json('rewards')->nullable(); // Description of rewards/recognition
            $table->json('goal_settings')->nullable(); // Additional settings, requirements
            $table->timestamp('start_date')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('participant_count')->default(0);
            $table->timestamps();

            // Indexes for performance
            $table->index(['server_id', 'status']);
            $table->index(['game_appid', 'status']);
            $table->index(['creator_id']);
            $table->index(['status', 'deadline']);
            $table->index(['goal_type', 'status']);
            $table->index(['visibility', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_goals');
    }
};
