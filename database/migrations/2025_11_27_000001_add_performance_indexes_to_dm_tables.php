<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds performance indexes to direct messaging tables.
     */
    public function up(): void
    {
        // Add indexes to direct_messages table for better performance
        Schema::table('direct_messages', function (Blueprint $table) {
            // Index for fetching messages by conversation ordered by date
            $table->index(['conversation_id', 'created_at'], 'dm_conversation_created');
            // Index for fetching messages by sender ordered by date
            $table->index(['sender_id', 'created_at'], 'dm_sender_created');
            // Index for counting unread messages
            $table->index(['conversation_id', 'read_at'], 'dm_conversation_read');
            // Index for message search (content search)
            $table->fullText('content', 'dm_content_fulltext');
        });

        // Add indexes to conversations table
        Schema::table('conversations', function (Blueprint $table) {
            // Index for fetching user's conversations sorted by activity
            $table->index(['user_one_id', 'last_message_at'], 'conv_user_one_last');
            $table->index(['user_two_id', 'last_message_at'], 'conv_user_two_last');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('direct_messages', function (Blueprint $table) {
            $table->dropIndex('dm_conversation_created');
            $table->dropIndex('dm_sender_created');
            $table->dropIndex('dm_conversation_read');
            $table->dropFullText('dm_content_fulltext');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conv_user_one_last');
            $table->dropIndex('conv_user_two_last');
        });
    }
};
