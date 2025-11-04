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
        Schema::create('matchmaking_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matchmaking_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            $table->float('compatibility_score');
            $table->json('score_breakdown');
            $table->boolean('was_accepted')->default(false);
            $table->string('configuration_used')->nullable();
            $table->timestamp('match_shown_at')->nullable();
            $table->timestamp('user_action_at')->nullable();
            $table->timestamps();

            // Indexes for analytics queries
            $table->index('configuration_used');
            $table->index('was_accepted');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matchmaking_analytics');
    }
};
