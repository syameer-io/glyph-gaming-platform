<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Server;

class ServerPolicy
{
    public function manage(User $user, Server $server)
    {
        return $user->isServerAdmin($server->id);
    }

    public function manageChannels(User $user, Server $server)
    {
        return $user->isServerAdmin($server->id);
    }

    public function manageRoles(User $user, Server $server)
    {
        return $user->isServerAdmin($server->id);
    }

    public function manageMembers(User $user, Server $server)
    {
        return $user->isServerAdmin($server->id);
    }

    public function kickMembers(User $user, Server $server)
    {
        return $user->isServerAdmin($server->id);
    }

    public function banMembers(User $user, Server $server)
    {
        return $user->isServerAdmin($server->id);
    }

    public function admin(User $user, Server $server)
    {
        return $user->isServerAdmin($server->id);
    }

    public function view(User $user, Server $server)
    {
        return $user->servers()->where('server_id', $server->id)->exists();
    }
}