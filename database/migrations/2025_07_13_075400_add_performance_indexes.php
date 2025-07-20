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
        Schema::table('messages', function (Blueprint $table) {
            // Index for loading messages by channel (most common query)
            $table->index(['channel_id', 'created_at'], 'messages_channel_created_idx');
            // Index for user's message history
            $table->index(['user_id', 'created_at'], 'messages_user_created_idx');
        });

        Schema::table('server_members', function (Blueprint $table) {
            // Index for checking banned/muted status
            $table->index(['server_id', 'is_banned'], 'server_members_banned_idx');
            $table->index(['server_id', 'is_muted'], 'server_members_muted_idx');
            // Index for member listing by join date
            $table->index(['server_id', 'joined_at'], 'server_members_joined_idx');
        });

        Schema::table('user_roles', function (Blueprint $table) {
            // Index for server role lookups (most common authorization query)
            $table->index(['user_id', 'server_id'], 'user_roles_user_server_idx');
            $table->index(['server_id', 'role_id'], 'user_roles_server_role_idx');
        });

        Schema::table('profiles', function (Blueprint $table) {
            // Index for status queries (online users, gaming status)
            $table->index('status', 'profiles_status_idx');
            // Index for Steam data queries (will be used heavily in Steam features)
            $table->index(['user_id', 'status'], 'profiles_user_status_idx');
        });

        Schema::table('channels', function (Blueprint $table) {
            // Index for server channel listing
            $table->index(['server_id', 'type'], 'channels_server_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_channel_created_idx');
            $table->dropIndex('messages_user_created_idx');
        });

        Schema::table('server_members', function (Blueprint $table) {
            $table->dropIndex('server_members_banned_idx');
            $table->dropIndex('server_members_muted_idx');
            $table->dropIndex('server_members_joined_idx');
        });

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex('user_roles_user_server_idx');
            $table->dropIndex('user_roles_server_role_idx');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropIndex('profiles_status_idx');
            $table->dropIndex('profiles_user_status_idx');
        });

        Schema::table('channels', function (Blueprint $table) {
            $table->dropIndex('channels_server_type_idx');
        });
    }
};
