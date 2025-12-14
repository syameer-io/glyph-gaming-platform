<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Team Invitation Settings
    |--------------------------------------------------------------------------
    |
    | Configure how team invitations behave, including expiry times and limits.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Invitation Expiry
    |--------------------------------------------------------------------------
    |
    | The number of days a team invitation remains valid before expiring.
    | Set to null for invitations that never expire.
    |
    */
    'invitation_expiry_days' => env('TEAM_INVITATION_EXPIRY_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Maximum Pending Invitations Per Team
    |--------------------------------------------------------------------------
    |
    | The maximum number of pending invitations a team can have at once.
    | Set to null for unlimited.
    |
    */
    'max_pending_invitations' => env('TEAM_MAX_PENDING_INVITATIONS', 20),

];
