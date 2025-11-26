<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $messageId;
    public Conversation $conversation;

    public function __construct(int $messageId, Conversation $conversation)
    {
        $this->messageId = $messageId;
        $this->conversation = $conversation;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dm.user.' . $this->conversation->user_one_id),
            new PrivateChannel('dm.user.' . $this->conversation->user_two_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dm.message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->messageId,
        ];
    }
}
