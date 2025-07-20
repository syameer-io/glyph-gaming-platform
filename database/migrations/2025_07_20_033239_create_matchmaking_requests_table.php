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
        Schema::create('matchmaking_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('game_appid');
            $table->string('game_name');
            $table->enum('request_type', ['find_team', 'find_teammates', 'substitute'])->default('find_team');
            $table->json('preferred_roles')->nullable(); // Array of preferred game roles
            $table->enum('skill_level', ['any', 'beginner', 'intermediate', 'advanced', 'expert'])->default('any');
            $table->decimal('skill_score', 5, 2)->nullable(); // User's calculated skill score
            $table->json('availability_hours')->nullable(); // Available hours in UTC format
            $table->json('server_preferences')->nullable(); // Preferred server IDs or regions
            $table->json('additional_requirements')->nullable(); // Voice chat, age, language, etc.
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['active', 'matched', 'expired', 'cancelled'])->default('active');
            $table->text('description')->nullable(); // Additional details about the request
            $table->timestamp('expires_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['game_appid', 'status']);
            $table->index(['request_type', 'status']);
            $table->index(['skill_level', 'status']);
            $table->index(['status', 'expires_at']);
            $table->index(['status', 'priority', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_requests');
    }
};
