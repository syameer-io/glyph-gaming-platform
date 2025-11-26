<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view any conversations.
     * Users can view their own conversations list.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtering to user's conversations happens at query level
    }

    /**
     * Determine whether the user can view the conversation.
     * User must be a participant in the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user->id);
    }

    /**
     * Determine whether the user can create conversations.
     * Any authenticated user can create conversations (friendship check in controller).
     */
    public function create(User $user): bool
    {
        return true; // Friendship verification handled in controller
    }

    /**
     * Determine whether the user can update the conversation.
     * Conversations cannot be updated directly - only messages are updated.
     */
    public function update(User $user, Conversation $conversation): bool
    {
        return false; // Conversations don't get updated directly
    }

    /**
     * Determine whether the user can delete the conversation.
     * Conversations are not deletable - they persist as long as users exist.
     */
    public function delete(User $user, Conversation $conversation): bool
    {
        return false; // Conversations should not be deleted
    }

    /**
     * Determine whether the user can restore the conversation.
     */
    public function restore(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the conversation.
     */
    public function forceDelete(User $user, Conversation $conversation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can send messages in the conversation.
     * User must be a participant and still friends with the other participant.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (!$conversation->hasParticipant($user->id)) {
            return false;
        }

        // Verify still friends with the other participant
        $otherParticipant = $conversation->getOtherParticipant($user);
        return $user->canDirectMessage($otherParticipant);
    }

    /**
     * Determine whether the user can mark messages as read in the conversation.
     * User must be a participant.
     */
    public function markAsRead(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user->id);
    }
}
