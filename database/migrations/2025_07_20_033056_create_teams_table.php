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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('game_appid');
            $table->string('game_name');
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->integer('max_size')->default(5);
            $table->integer('current_size')->default(1);
            $table->enum('skill_level', ['any', 'beginner', 'intermediate', 'advanced', 'expert'])->default('any');
            $table->enum('status', ['recruiting', 'full', 'active', 'disbanded'])->default('recruiting');
            $table->json('team_data')->nullable(); // Additional team settings, requirements, etc.
            $table->timestamp('recruitment_deadline')->nullable();
            $table->decimal('average_skill_score', 5, 2)->nullable(); // Calculated team skill average
            $table->timestamps();

            // Indexes for performance
            $table->index(['server_id', 'status']);
            $table->index(['game_appid', 'status']);
            $table->index(['creator_id']);
            $table->index(['skill_level', 'status']);
            $table->index(['status', 'recruitment_deadline']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
