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
        Schema::create('gaming_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('game_appid');
            $table->string('game_name');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // calculated when session ends
            $table->json('session_data')->nullable(); // server, map, mode, etc.
            $table->string('status')->default('active'); // active, completed, abandoned
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'started_at']);
            $table->index(['game_appid', 'started_at']);
            $table->index(['user_id', 'game_appid', 'started_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaming_sessions');
    }
};
