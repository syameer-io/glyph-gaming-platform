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
        Schema::create('user_gaming_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('game_appid', 20);
            $table->string('game_name', 255);
            $table->integer('playtime_forever')->default(0); // minutes
            $table->integer('playtime_2weeks')->default(0); // minutes
            $table->enum('preference_level', ['high', 'medium', 'low'])->default('medium');
            $table->enum('skill_level', ['beginner', 'intermediate', 'advanced', 'expert'])->default('intermediate');
            $table->timestamp('last_played')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'game_appid'], 'user_gaming_user_game_idx');
            $table->index(['game_appid', 'preference_level'], 'user_gaming_game_pref_idx');
            $table->unique(['user_id', 'game_appid'], 'user_gaming_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_gaming_preferences');
    }
};
