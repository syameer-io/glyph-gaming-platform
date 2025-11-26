<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'content',
        'is_edited',
        'edited_at',
        'read_at',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    /**
     * Get the conversation this message belongs to.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Check if the given user can edit this message.
     * Only the sender can edit their own messages.
     *
     * @param int $userId The user ID to check
     * @return bool
     */
    public function canEdit(int $userId): bool
    {
        return $this->sender_id === $userId;
    }

    /**
     * Check if the given user can delete this message.
     * Only the sender can delete their own messages.
     *
     * @param int $userId The user ID to check
     * @return bool
     */
    public function canDelete(int $userId): bool
    {
        return $this->sender_id === $userId;
    }

    /**
     * Mark this message as edited and update the edited_at timestamp.
     */
    public function markAsEdited(): void
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }

    /**
     * Mark this message as read.
     */
    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if this message has been read.
     *
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Get the recipient of this message (the other participant in the conversation).
     *
     * @return User
     */
    public function getRecipient(): User
    {
        $conversation = $this->conversation;

        if ($conversation->user_one_id === $this->sender_id) {
            return $conversation->userTwo;
        }

        return $conversation->userOne;
    }

    /**
     * Boot method to handle model events.
     */
    protected static function boot()
    {
        parent::boot();

        // Update conversation's last_message_at when a new message is created
        static::created(function (DirectMessage $message) {
            $message->conversation->touchLastMessageAt();
        });
    }
}
