<?php

namespace App\Policies;

use App\Models\GameLobby;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GameLobbyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GameLobby $gameLobby): bool
    {
        return true; // All authenticated users can view lobbies
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create lobbies
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GameLobby $gameLobby): bool
    {
        // Only the lobby owner can update their lobby
        return $user->id === $gameLobby->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GameLobby $gameLobby): bool
    {
        // Only the lobby owner can delete their lobby
        return $user->id === $gameLobby->user_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GameLobby $gameLobby): bool
    {
        return $user->id === $gameLobby->user_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GameLobby $gameLobby): bool
    {
        return $user->id === $gameLobby->user_id;
    }
}
