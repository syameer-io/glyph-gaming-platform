<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTypingDM implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Conversation $conversation;
    public User $user;
    public bool $isTyping;

    public function __construct(Conversation $conversation, User $user, bool $isTyping = true)
    {
        $this->conversation = $conversation;
        $this->user = $user;
        $this->isTyping = $isTyping;
    }

    public function broadcastOn(): array
    {
        // Only broadcast to the other participant
        $recipientId = $this->conversation->user_one_id === $this->user->id
            ? $this->conversation->user_two_id
            : $this->conversation->user_one_id;

        return [
            new PrivateChannel('dm.user.' . $recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dm.user.typing';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user' => [
                'id' => $this->user->id,
                'display_name' => $this->user->display_name,
            ],
            'is_typing' => $this->isTyping,
        ];
    }
}
