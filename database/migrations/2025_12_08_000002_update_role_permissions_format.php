<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Phase 6: Data Migration for Discord-style Role Permissions System
     *
     * This migration updates existing role permissions to the new format:
     * - Server Admin roles use ['administrator'] instead of full permission list
     * - Member roles include voice permissions (connect, speak)
     * - Custom roles with send_messages also get voice permissions
     * - Null permissions are converted to empty arrays
     */
    public function up(): void
    {
        // Update Server Admin roles to use administrator permission
        // This grants all permissions through the PermissionService bypass
        DB::table('roles')
            ->where('name', 'Server Admin')
            ->update(['permissions' => json_encode(['administrator'])]);

        // Ensure Member roles have the correct default permissions
        // Including voice permissions (connect, speak) which were missing
        DB::table('roles')
            ->where('name', 'Member')
            ->update(['permissions' => json_encode([
                'view_channels',
                'send_messages',
                'connect',
                'speak'
            ])]);

        // Update custom roles that have basic permissions to also include voice
        // Rationale: If a role can send messages, they should be able to use voice
        $customRoles = DB::table('roles')
            ->whereNotIn('name', ['Server Admin', 'Member'])
            ->get();

        foreach ($customRoles as $role) {
            $permissions = json_decode($role->permissions, true) ?? [];

            // Add voice permissions if they have send_messages
            if (in_array('send_messages', $permissions)) {
                if (!in_array('connect', $permissions)) {
                    $permissions[] = 'connect';
                }
                if (!in_array('speak', $permissions)) {
                    $permissions[] = 'speak';
                }

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode(array_unique($permissions))]);
            }
        }

        // Handle null permissions - set to empty array
        // This prevents issues with json_decode returning null
        DB::table('roles')
            ->whereNull('permissions')
            ->update(['permissions' => json_encode([])]);
    }

    /**
     * Reverse the migrations.
     *
     * Reverts to the old permission format for Server Admin and Member roles.
     * Note: Custom role voice permissions are not reverted as they may have
     * been intentionally added by admins.
     */
    public function down(): void
    {
        // Revert Server Admin roles to full permission list (old format)
        $fullPermissions = [
            'manage_server',
            'manage_channels',
            'manage_roles',
            'manage_members',
            'kick_members',
            'ban_members',
            'mute_members',
            'send_messages',
            'view_channels',
        ];

        DB::table('roles')
            ->where('name', 'Server Admin')
            ->update(['permissions' => json_encode($fullPermissions)]);

        // Revert Member roles to old format (without voice permissions)
        DB::table('roles')
            ->where('name', 'Member')
            ->update(['permissions' => json_encode([
                'send_messages',
                'view_channels'
            ])]);
    }
};
