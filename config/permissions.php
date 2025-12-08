<?php

/**
 * Permission Configuration
 *
 * Defines all permission categories, individual permissions, and their metadata
 * for the Glyph gaming community platform's role-based access control system.
 *
 * This file is the single source of truth for:
 * - Permission categories and their members
 * - Permission labels and descriptions
 * - Dangerous permission flags
 * - Default permission sets for protected roles
 * - Cache TTL settings
 *
 * @package Glyph
 * @since Phase 1 - Role Permissions System
 */

return [

    /*
    |--------------------------------------------------------------------------
    | General Server Permissions
    |--------------------------------------------------------------------------
    |
    | Permissions related to server-wide actions and settings.
    |
    */
    'categories' => [
        'general_server' => [
            'label' => 'General Server',
            'description' => 'Server-wide permissions for viewing and management',
            'permissions' => [
                'view_channels' => [
                    'label' => 'View Channels',
                    'description' => 'Allows viewing channel names and accessing text/voice channels',
                    'dangerous' => false,
                    'default' => true,
                ],
                'manage_server' => [
                    'label' => 'Manage Server',
                    'description' => 'Allows editing server name, description, icon, and settings. This is a dangerous permission.',
                    'dangerous' => true,
                    'default' => false,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Membership Permissions
        |--------------------------------------------------------------------------
        |
        | Permissions related to managing server members.
        |
        */
        'membership' => [
            'label' => 'Membership',
            'description' => 'Permissions for managing server members',
            'permissions' => [
                'manage_members' => [
                    'label' => 'Manage Members',
                    'description' => 'Allows viewing and modifying member details and roles',
                    'dangerous' => false,
                    'default' => false,
                ],
                'kick_members' => [
                    'label' => 'Kick Members',
                    'description' => 'Allows removing members from the server. This is a dangerous permission.',
                    'dangerous' => true,
                    'default' => false,
                ],
                'ban_members' => [
                    'label' => 'Ban Members',
                    'description' => 'Allows permanently banning members from the server. This is a dangerous permission.',
                    'dangerous' => true,
                    'default' => false,
                ],
                'mute_members' => [
                    'label' => 'Mute Members',
                    'description' => 'Allows muting members from sending messages in text channels',
                    'dangerous' => false,
                    'default' => false,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Role Permissions
        |--------------------------------------------------------------------------
        |
        | Permissions related to managing server roles.
        |
        */
        'roles' => [
            'label' => 'Roles',
            'description' => 'Permissions for managing server roles',
            'permissions' => [
                'manage_roles' => [
                    'label' => 'Manage Roles',
                    'description' => 'Allows creating, editing, and deleting roles below own highest role. This is a dangerous permission.',
                    'dangerous' => true,
                    'default' => false,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Channel Permissions
        |--------------------------------------------------------------------------
        |
        | Permissions related to managing channels.
        |
        */
        'channels' => [
            'label' => 'Channels',
            'description' => 'Permissions for managing server channels',
            'permissions' => [
                'manage_channels' => [
                    'label' => 'Manage Channels',
                    'description' => 'Allows creating, editing, and deleting channels',
                    'dangerous' => false,
                    'default' => false,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Text Channel Permissions
        |--------------------------------------------------------------------------
        |
        | Permissions specific to text channels and messaging.
        |
        */
        'text_channel' => [
            'label' => 'Text Channels',
            'description' => 'Permissions for text channel interactions',
            'permissions' => [
                'send_messages' => [
                    'label' => 'Send Messages',
                    'description' => 'Allows sending messages in text channels',
                    'dangerous' => false,
                    'default' => true,
                ],
                'manage_messages' => [
                    'label' => 'Manage Messages',
                    'description' => 'Allows deleting messages from other members',
                    'dangerous' => false,
                    'default' => false,
                ],
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Voice Channel Permissions
        |--------------------------------------------------------------------------
        |
        | Permissions specific to voice channels.
        |
        */
        'voice_channel' => [
            'label' => 'Voice Channels',
            'description' => 'Permissions for voice channel interactions',
            'permissions' => [
                'connect' => [
                    'label' => 'Connect',
                    'description' => 'Allows connecting to voice channels',
                    'dangerous' => false,
                    'default' => true,
                ],
                'speak' => [
                    'label' => 'Speak',
                    'description' => 'Allows speaking in voice channels',
                    'dangerous' => false,
                    'default' => true,
                ],
                'mute_voice_members' => [
                    'label' => 'Mute Voice Members',
                    'description' => 'Allows server-muting other members in voice channels',
                    'dangerous' => false,
                    'default' => false,
                ],
                'move_members' => [
                    'label' => 'Move Members',
                    'description' => 'Allows moving members between voice channels',
                    'dangerous' => false,
                    'default' => false,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Administrator Permission
    |--------------------------------------------------------------------------
    |
    | The special administrator permission that grants all other permissions.
    | Users with this permission bypass all permission checks.
    |
    */
    'administrator' => 'administrator',

    /*
    |--------------------------------------------------------------------------
    | All Permissions (Flat List)
    |--------------------------------------------------------------------------
    |
    | A flat array of all permission keys for validation and iteration.
    | This is auto-generated from categories but listed here for reference.
    |
    */
    'all' => [
        'administrator',
        // General Server
        'view_channels',
        'manage_server',
        // Membership
        'manage_members',
        'kick_members',
        'ban_members',
        'mute_members',
        // Roles
        'manage_roles',
        // Channels
        'manage_channels',
        // Text Channel
        'send_messages',
        'manage_messages',
        // Voice Channel
        'connect',
        'speak',
        'mute_voice_members',
        'move_members',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dangerous Permissions
    |--------------------------------------------------------------------------
    |
    | Permissions that require additional warnings in the UI and should
    | be granted with caution.
    |
    */
    'dangerous' => [
        'administrator',
        'manage_server',
        'kick_members',
        'ban_members',
        'manage_roles',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Role Permissions
    |--------------------------------------------------------------------------
    |
    | Default permission sets for the protected roles that are created
    | when a new server is established.
    |
    */
    'defaults' => [
        /*
         * Server Admin role default permissions
         * Gets the administrator permission which bypasses all checks
         */
        'server_admin' => [
            'administrator',
        ],

        /*
         * Member role default permissions
         * Basic permissions for all new members
         */
        'member' => [
            'view_channels',
            'send_messages',
            'connect',
            'speak',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache TTL for permission computations to reduce database queries.
    | Caches are invalidated when roles or permissions change.
    |
    */
    'cache_ttl' => 300, // 5 minutes in seconds

];
