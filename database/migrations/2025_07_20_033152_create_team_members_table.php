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
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['leader', 'co-leader', 'member', 'reserve'])->default('member');
            $table->enum('game_role', ['tank', 'dps', 'support', 'flex', 'igl', 'entry', 'anchor', 'awper', 'rifler'])->nullable(); // Game-specific roles
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'expert'])->nullable();
            $table->decimal('individual_skill_score', 5, 2)->nullable(); // Individual member skill score
            $table->enum('status', ['active', 'inactive', 'kicked', 'left'])->default('active');
            $table->json('member_data')->nullable(); // Additional member-specific data
            $table->timestamp('joined_at');
            $table->timestamp('last_active_at')->nullable();
            $table->timestamps();

            // Unique constraint - user can only be on one team per game
            $table->unique(['team_id', 'user_id']);
            
            // Indexes for performance
            $table->index(['team_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['team_id', 'role']);
            $table->index(['skill_level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
