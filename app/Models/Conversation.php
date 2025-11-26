<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    /**
     * Get the first user in the conversation (lower ID due to canonical ordering).
     */
    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Get the second user in the conversation (higher ID due to canonical ordering).
     */
    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Get all messages in the conversation.
     */
    public function messages()
    {
        return $this->hasMany(DirectMessage::class);
    }

    /**
     * Get the latest message in the conversation.
     */
    public function latestMessage()
    {
        return $this->hasOne(DirectMessage::class)->latestOfMany();
    }

    /**
     * Get the other participant in the conversation (not the given user).
     *
     * @param User $user The current user
     * @return User The other participant
     */
    public function getOtherParticipant(User $user): User
    {
        if ($this->user_one_id === $user->id) {
            return $this->userTwo;
        }

        return $this->userOne;
    }

    /**
     * Check if a user is a participant in this conversation.
     *
     * @param int $userId The user ID to check
     * @return bool
     */
    public function hasParticipant(int $userId): bool
    {
        return $this->user_one_id === $userId || $this->user_two_id === $userId;
    }

    /**
     * Get the count of unread messages for a specific user.
     *
     * @param int $userId The user ID to count unread messages for
     * @return int
     */
    public function getUnreadCountFor(int $userId): int
    {
        return $this->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Find or create a conversation between two users.
     * Ensures canonical ordering (lower ID first) to prevent duplicates.
     *
     * @param int $userIdOne First user ID
     * @param int $userIdTwo Second user ID
     * @return Conversation
     */
    public static function findOrCreateBetween(int $userIdOne, int $userIdTwo): Conversation
    {
        // Ensure canonical ordering: smaller ID is always user_one_id
        $userOneId = min($userIdOne, $userIdTwo);
        $userTwoId = max($userIdOne, $userIdTwo);

        return static::firstOrCreate([
            'user_one_id' => $userOneId,
            'user_two_id' => $userTwoId,
        ]);
    }

    /**
     * Find an existing conversation between two users.
     *
     * @param int $userIdOne First user ID
     * @param int $userIdTwo Second user ID
     * @return Conversation|null
     */
    public static function findBetween(int $userIdOne, int $userIdTwo): ?Conversation
    {
        // Ensure canonical ordering for lookup
        $userOneId = min($userIdOne, $userIdTwo);
        $userTwoId = max($userIdOne, $userIdTwo);

        return static::where('user_one_id', $userOneId)
            ->where('user_two_id', $userTwoId)
            ->first();
    }

    /**
     * Scope to get all conversations for a specific user.
     * Returns conversations sorted by most recent activity.
     *
     * @param int $userId The user ID to find conversations for
     * @return Builder
     */
    public static function forUser(int $userId): Builder
    {
        return static::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at');
    }

    /**
     * Update the last_message_at timestamp when a new message is sent.
     */
    public function touchLastMessageAt(): void
    {
        $this->update(['last_message_at' => now()]);
    }
}
