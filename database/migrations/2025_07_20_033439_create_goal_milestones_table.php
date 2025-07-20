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
        Schema::create('goal_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained('server_goals')->onDelete('cascade');
            $table->string('milestone_name');
            $table->text('description')->nullable();
            $table->integer('progress_required'); // Progress value needed to reach this milestone
            $table->decimal('percentage_required', 5, 2); // Percentage of goal completion
            $table->text('reward_description')->nullable();
            $table->json('milestone_data')->nullable(); // Additional milestone settings
            $table->enum('milestone_type', ['progress', 'participation', 'time_based', 'achievement', 'custom'])->default('progress');
            $table->boolean('is_achieved')->default(false);
            $table->timestamp('achieved_at')->nullable();
            $table->integer('achieved_by_count')->default(0); // How many people achieved this milestone
            $table->json('achieved_by_users')->nullable(); // User IDs who achieved this milestone
            $table->boolean('broadcast_achievement')->default(true); // Whether to announce this milestone
            $table->integer('order')->default(0); // Order of milestones
            $table->timestamps();

            // Indexes for performance
            $table->index(['goal_id', 'order']);
            $table->index(['goal_id', 'is_achieved']);
            $table->index(['milestone_type', 'is_achieved']);
            $table->index(['progress_required']);
            $table->index(['percentage_required']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goal_milestones');
    }
};
