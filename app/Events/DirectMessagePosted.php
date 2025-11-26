<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\DirectMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessagePosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public DirectMessage $message;
    public Conversation $conversation;

    public function __construct(DirectMessage $message, Conversation $conversation)
    {
        $this->message = $message;
        $this->conversation = $conversation;
    }

    /**
     * Get the channels the event should broadcast on.
     * Broadcasts to both participants' personal DM channels
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('dm.user.' . $this->conversation->user_one_id),
            new PrivateChannel('dm.user.' . $this->conversation->user_two_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'dm.message.posted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'content' => $this->message->content,
                'is_edited' => $this->message->is_edited,
                'edited_at' => $this->message->edited_at?->toISOString(),
                'created_at' => $this->message->created_at->toISOString(),
                'sender' => [
                    'id' => $this->message->sender->id,
                    'display_name' => $this->message->sender->display_name,
                    'avatar_url' => $this->message->sender->profile->avatar_url ?? null,
                ],
            ],
        ];
    }
}
