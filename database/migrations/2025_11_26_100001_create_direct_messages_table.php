<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the direct_messages table for storing messages in conversations.
     * Follows the same edit tracking pattern as the existing Message model.
     */
    public function up(): void
    {
        Schema::create('direct_messages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->onDelete('cascade');

            $table->foreignId('sender_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->text('content');

            // Edit tracking (matching existing Message model pattern)
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();

            // Read receipt for unread message tracking
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            // Primary use case: fetching messages in a conversation, sorted by time
            $table->index(['conversation_id', 'created_at']);
            // For fetching user's sent messages
            $table->index(['sender_id', 'created_at']);
            // For counting unread messages efficiently
            $table->index(['conversation_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_messages');
    }
};
