<?php

namespace App\Events;

use App\Models\Channel;
use Illuminate\Broadcasting\Channel as BroadcastChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $channel;

    public function __construct($messageId, Channel $channel)
    {
        $this->messageId = $messageId;
        $this->channel = $channel;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('server.' . $this->channel->server_id . '.channel.' . $this->channel->id);
    }

    public function broadcastAs()
    {
        return 'message.deleted';
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
        ];
    }
}