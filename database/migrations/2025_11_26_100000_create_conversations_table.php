<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the conversations table for direct messaging between friends.
     * Uses canonical ordering where user_one_id < user_two_id to ensure
     * unique conversation pairs and prevent duplicate conversations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // Canonical ordering: user_one_id < user_two_id (always)
            // This ensures each user pair has exactly one conversation
            $table->foreignId('user_one_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('user_two_id')
                ->constrained('users')
                ->onDelete('cascade');

            // For sorting conversations by recent activity
            $table->timestamp('last_message_at')->nullable();

            $table->timestamps();

            // Ensure unique conversation per user pair
            $table->unique(['user_one_id', 'user_two_id']);

            // Indexes for fast lookups when fetching user's conversations
            $table->index(['user_one_id', 'last_message_at']);
            $table->index(['user_two_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
