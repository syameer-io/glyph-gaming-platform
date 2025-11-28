<?php

namespace App\Events;

use App\Models\User;
use App\Models\UserStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * UserStatusUpdated Event
 *
 * Phase 2: Member List Enhancement
 *
 * Broadcasts when a user's status changes (online, idle, dnd, offline)
 * or when their custom status is updated.
 *
 * Broadcasts to server private channels so all server members
 * can update their member lists in real-time.
 */
class UserStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $user;
    public UserStatus $userStatus;
    public int $serverId;

    /**
     * Create a new event instance.
     *
     * @param User $user The user whose status changed
     * @param UserStatus $userStatus The updated status record
     * @param int $serverId The server to broadcast to
     */
    public function __construct(User $user, UserStatus $userStatus, int $serverId)
    {
        $this->user = $user;
        $this->userStatus = $userStatus;
        $this->serverId = $serverId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Broadcasts to the server-wide channel so all members can update
     * their member lists when a user's status changes.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('server.' . $this->serverId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * Using a dot-prefixed name for Laravel Echo compatibility.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'user.status.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'display_name' => $this->user->display_name,
            'username' => $this->user->username,
            'avatar_url' => $this->user->profile->avatar_url ?? null,
            'status' => $this->userStatus->status,
            'status_color' => $this->userStatus->getStatusColor(),
            'status_label' => $this->userStatus->getDisplayStatus(),
            'custom_text' => $this->userStatus->custom_text,
            'custom_emoji' => $this->userStatus->custom_emoji,
            'has_custom_status' => $this->userStatus->hasCustomStatus(),
            'full_custom_status' => $this->userStatus->getFullCustomStatus(),
            'activity' => $this->user->getDisplayActivity(),
            'is_playing' => $this->user->isPlayingGame(),
            'server_id' => $this->serverId,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * Determine if this event should broadcast.
     *
     * Don't broadcast offline status changes for users who were already offline.
     *
     * @return bool
     */
    public function broadcastWhen(): bool
    {
        return true;
    }
}
