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
        Schema::create('goal_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('server_goals')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('individual_progress')->default(0);
            $table->decimal('contribution_percentage', 5, 2)->default(0.00);
            $table->json('progress_data')->nullable(); // Detailed progress tracking
            $table->enum('participation_status', ['active', 'completed', 'dropped', 'inactive'])->default('active');
            $table->timestamp('joined_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('achievements_unlocked')->nullable(); // Specific achievements for this goal
            $table->decimal('skill_score_at_start', 5, 2)->nullable(); // Track skill improvement
            $table->decimal('current_skill_score', 5, 2)->nullable();
            $table->timestamps();

            // Unique constraint - user can only participate once per goal
            $table->unique(['goal_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['goal_id', 'participation_status']);
            $table->index(['user_id', 'participation_status']);
            $table->index(['goal_id', 'individual_progress']);
            $table->index(['participation_status', 'last_activity_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_participants');
    }
};
