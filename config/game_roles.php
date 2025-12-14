<?php

/**
 * Game-specific role definitions for team role assignment.
 *
 * Each game has a list of valid roles that team members can be assigned.
 * This configuration is used by TeamRoleService for:
 * - Validating role assignments
 * - Providing role options in the UI
 * - Generating display names for roles
 */

return [
    'games' => [
        // Counter-Strike 2
        '730' => [
            'name' => 'Counter-Strike 2',
            'roles' => [
                'entry_fragger',
                'awper',
                'igl',
                'lurker',
                'support',
                'anchor',
                'rifler',
            ],
            'display_names' => [
                'entry_fragger' => 'Entry Fragger',
                'awper' => 'AWPer',
                'igl' => 'In-Game Leader',
                'lurker' => 'Lurker',
                'support' => 'Support',
                'anchor' => 'Anchor',
                'rifler' => 'Rifler',
            ],
            'descriptions' => [
                'entry_fragger' => 'First player to enter sites and create space',
                'awper' => 'Primary sniper rifle player',
                'igl' => 'Team strategist and shot-caller',
                'lurker' => 'Flanker who creates pressure from unexpected angles',
                'support' => 'Utility player who assists teammates',
                'anchor' => 'Site holder who delays pushes',
                'rifler' => 'Versatile rifle player',
            ],
        ],

        // Dota 2
        '570' => [
            'name' => 'Dota 2',
            'roles' => [
                'carry',
                'mid',
                'offlaner',
                'soft_support',
                'hard_support',
            ],
            'display_names' => [
                'carry' => 'Carry (Pos 1)',
                'mid' => 'Mid (Pos 2)',
                'offlaner' => 'Offlaner (Pos 3)',
                'soft_support' => 'Soft Support (Pos 4)',
                'hard_support' => 'Hard Support (Pos 5)',
            ],
            'descriptions' => [
                'carry' => 'Farm-dependent hero who scales into late game',
                'mid' => 'Solo mid lane, high impact playmaker',
                'offlaner' => 'Initiator and space creator',
                'soft_support' => 'Roaming support with utility',
                'hard_support' => 'Ward support focused on protecting carry',
            ],
        ],

        // Warframe
        '230410' => [
            'name' => 'Warframe',
            'roles' => [
                'dps',
                'tank',
                'support',
                'crowd_control',
                'buffer',
            ],
            'display_names' => [
                'dps' => 'DPS',
                'tank' => 'Tank',
                'support' => 'Support',
                'crowd_control' => 'Crowd Control',
                'buffer' => 'Buffer',
            ],
            'descriptions' => [
                'dps' => 'High damage output frames',
                'tank' => 'High survivability frames',
                'support' => 'Healing and team sustain',
                'crowd_control' => 'Enemy control and lockdown',
                'buffer' => 'Team damage and ability enhancement',
            ],
        ],

        // Apex Legends
        '1172470' => [
            'name' => 'Apex Legends',
            'roles' => [
                'fragger',
                'scout',
                'sniper',
                'support',
                'igl',
            ],
            'display_names' => [
                'fragger' => 'Fragger',
                'scout' => 'Scout',
                'sniper' => 'Sniper',
                'support' => 'Support',
                'igl' => 'IGL',
            ],
            'descriptions' => [
                'fragger' => 'Aggressive player who initiates fights',
                'scout' => 'Recon specialist gathering intel',
                'sniper' => 'Long-range marksman',
                'support' => 'Team sustain and utility',
                'igl' => 'In-game leader and shot-caller',
            ],
        ],

        // Rust
        '252490' => [
            'name' => 'Rust',
            'roles' => [
                'pvp',
                'farmer',
                'builder',
                'raider',
                'scout',
            ],
            'display_names' => [
                'pvp' => 'PvP Specialist',
                'farmer' => 'Farmer',
                'builder' => 'Builder',
                'raider' => 'Raider',
                'scout' => 'Scout',
            ],
            'descriptions' => [
                'pvp' => 'Combat focused player',
                'farmer' => 'Resource gathering specialist',
                'builder' => 'Base construction expert',
                'raider' => 'Offense and raid specialist',
                'scout' => 'Intel gathering and patrol',
            ],
        ],

        // PUBG
        '578080' => [
            'name' => 'PUBG: Battlegrounds',
            'roles' => [
                'fragger',
                'sniper',
                'support',
                'igl',
                'driver',
            ],
            'display_names' => [
                'fragger' => 'Fragger',
                'sniper' => 'Sniper',
                'support' => 'Support',
                'igl' => 'IGL',
                'driver' => 'Driver',
            ],
            'descriptions' => [
                'fragger' => 'Close to mid-range combat specialist',
                'sniper' => 'Long-range marksman',
                'support' => 'Utility and healing provider',
                'igl' => 'Team leader and strategist',
                'driver' => 'Vehicle specialist and rotation caller',
            ],
        ],

        // Rainbow Six Siege
        '359550' => [
            'name' => 'Rainbow Six Siege',
            'roles' => [
                'entry_fragger',
                'support',
                'flex',
                'anchor',
                'roamer',
                'igl',
            ],
            'display_names' => [
                'entry_fragger' => 'Entry Fragger',
                'support' => 'Support',
                'flex' => 'Flex',
                'anchor' => 'Anchor',
                'roamer' => 'Roamer',
                'igl' => 'IGL',
            ],
            'descriptions' => [
                'entry_fragger' => 'First to enter and get kills',
                'support' => 'Utility and intel provider',
                'flex' => 'Adaptable to any operator',
                'anchor' => 'Site defender',
                'roamer' => 'Off-site flanker',
                'igl' => 'Team strategist',
            ],
        ],

        // Fall Guys
        '1097150' => [
            'name' => 'Fall Guys',
            'roles' => [
                'grabber',
                'runner',
                'tactician',
            ],
            'display_names' => [
                'grabber' => 'Grabber',
                'runner' => 'Runner',
                'tactician' => 'Tactician',
            ],
            'descriptions' => [
                'grabber' => 'Physical play specialist',
                'runner' => 'Speed and agility focused',
                'tactician' => 'Strategic play caller',
            ],
        ],

        // Deep Rock Galactic
        '548430' => [
            'name' => 'Deep Rock Galactic',
            'roles' => [
                'scout',
                'driller',
                'engineer',
                'gunner',
            ],
            'display_names' => [
                'scout' => 'Scout',
                'driller' => 'Driller',
                'engineer' => 'Engineer',
                'gunner' => 'Gunner',
            ],
            'descriptions' => [
                'scout' => 'Mobile class with grappling hook and flare gun for exploration',
                'driller' => 'Terrain manipulation specialist with drilling equipment',
                'engineer' => 'Defensive specialist with platforms and turrets',
                'gunner' => 'Heavy firepower class with shields and ziplines',
            ],
        ],

        // GTFO
        '493520' => [
            'name' => 'GTFO',
            'roles' => [
                'scout',
                'cqc',
                'sniper',
                'support',
            ],
            'display_names' => [
                'scout' => 'Scout',
                'cqc' => 'CQC (Close Quarters)',
                'sniper' => 'Sniper',
                'support' => 'Support',
            ],
            'descriptions' => [
                'scout' => 'Reconnaissance and stealth specialist',
                'cqc' => 'Front-line combat specialist for close encounters',
                'sniper' => 'Long-range precision shooter',
                'support' => 'Team resource management and backup',
            ],
        ],
    ],

    // Generic roles for unsupported games
    'generic' => [
        'name' => 'Generic',
        'roles' => [
            'tank',
            'dps',
            'support',
            'flex',
            'igl',
        ],
        'display_names' => [
            'tank' => 'Tank',
            'dps' => 'DPS',
            'support' => 'Support',
            'flex' => 'Flex',
            'igl' => 'In-Game Leader',
        ],
        'descriptions' => [
            'tank' => 'High survivability role',
            'dps' => 'Damage dealer',
            'support' => 'Team utility and healing',
            'flex' => 'Adaptable to any role',
            'igl' => 'Team strategist and leader',
        ],
    ],

    // All valid roles across all games (for validation)
    'all_roles' => [
        'entry_fragger',
        'awper',
        'igl',
        'lurker',
        'support',
        'anchor',
        'rifler',
        'carry',
        'mid',
        'offlaner',
        'soft_support',
        'hard_support',
        'dps',
        'tank',
        'crowd_control',
        'buffer',
        'fragger',
        'scout',
        'sniper',
        'pvp',
        'farmer',
        'builder',
        'raider',
        'driver',
        'flex',
        'roamer',
        'grabber',
        'runner',
        'tactician',
        'healer',
        'driller',   // Deep Rock Galactic
        'engineer',  // Deep Rock Galactic
        'gunner',    // Deep Rock Galactic
        'cqc',       // GTFO
    ],
];
