<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Create channel_permission_overrides table
 *
 * This table enables channel-specific permission overrides for roles.
 * It allows fine-grained control where a role's default permissions
 * can be overridden (allowed or denied) on a per-channel basis.
 *
 * The override system follows a tri-state model:
 * - 'allow': Explicitly grants the permission for this channel
 * - 'deny': Explicitly denies the permission for this channel
 * - 'inherit': Uses the role's default permission setting
 *
 * @package Glyph
 * @since Phase 1 - Role Permissions System
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('channel_permission_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->string('permission', 50);
            $table->enum('value', ['allow', 'deny', 'inherit'])->default('inherit');
            $table->timestamps();

            // Unique constraint: one override per channel-role-permission combination
            $table->unique(['channel_id', 'role_id', 'permission'], 'channel_role_permission_unique');

            // Indexes for efficient querying
            // Used when fetching all overrides for a specific channel and permission
            $table->index(['channel_id', 'permission']);

            // Used when fetching all overrides for a specific role and permission
            $table->index(['role_id', 'permission']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_permission_overrides');
    }
};
