<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Channel;
use Illuminate\Broadcasting\Channel as BroadcastChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessagePosted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $channel;

    public function __construct(Message $message, Channel $channel)
    {
        $this->message = $message;
        $this->channel = $channel;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->channel->server_id . '.channel.' . $this->channel->id),
        ];
    }

    public function broadcastAs()
    {
        return 'message.posted';
    }

    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'created_at' => $this->message->created_at->toISOString(),
                'is_edited' => $this->message->is_edited,
                'edited_at' => $this->message->edited_at ? $this->message->edited_at->toISOString() : null,
                'user' => [
                    'id' => $this->message->user->id,
                    'display_name' => $this->message->user->display_name,
                    'avatar_url' => $this->message->user->profile->avatar_url,
                ],
            ],
        ];
    }
}