<?php

namespace App\Policies;

use App\Models\DirectMessage;
use App\Models\User;

class DirectMessagePolicy
{
    /**
     * Determine whether the user can view any messages.
     * Users can view messages in conversations they participate in.
     */
    public function viewAny(User $user): bool
    {
        return true; // Actual filtering happens at query level
    }

    /**
     * Determine whether the user can view the message.
     * User must be a participant in the conversation.
     */
    public function view(User $user, DirectMessage $directMessage): bool
    {
        $conversation = $directMessage->conversation;
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can create messages.
     * User must be authenticated (friendship check in controller).
     */
    public function create(User $user): bool
    {
        return true; // Friendship verification handled in controller
    }

    /**
     * Determine whether the user can update the message.
     * Only the sender can edit their own messages.
     */
    public function update(User $user, DirectMessage $directMessage): bool
    {
        return $directMessage->canEdit($user->id);
    }

    /**
     * Determine whether the user can delete the message.
     * Only the sender can delete their own messages.
     */
    public function delete(User $user, DirectMessage $directMessage): bool
    {
        return $directMessage->canDelete($user->id);
    }

    /**
     * Determine whether the user can restore the message.
     * Soft delete not implemented - no restore capability.
     */
    public function restore(User $user, DirectMessage $directMessage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the message.
     * Only the sender can permanently delete their own messages.
     */
    public function forceDelete(User $user, DirectMessage $directMessage): bool
    {
        return $directMessage->canDelete($user->id);
    }
}
