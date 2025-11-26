<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Conversation $conversation;
    public int $readByUserId;

    public function __construct(Conversation $conversation, int $readByUserId)
    {
        $this->conversation = $conversation;
        $this->readByUserId = $readByUserId;
    }

    public function broadcastOn(): array
    {
        // Only broadcast to the sender (other participant)
        $recipientId = $this->conversation->user_one_id === $this->readByUserId
            ? $this->conversation->user_two_id
            : $this->conversation->user_one_id;

        return [
            new PrivateChannel('dm.user.' . $recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dm.message.read';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'read_by_user_id' => $this->readByUserId,
            'read_at' => now()->toISOString(),
        ];
    }
}
