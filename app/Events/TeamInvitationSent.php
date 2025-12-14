<?php

namespace App\Events;

use App\Models\TeamInvitation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TeamInvitation $invitation;

    /**
     * Create a new event instance.
     */
    public function __construct(TeamInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the channels the event should broadcast on.
     * - Notify the invitee (for their notification badge)
     * - Notify the team channel (for real-time list updates)
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->invitation->invitee_id),
            new PrivateChannel('team.' . $this->invitation->team_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'team.invitation.sent';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'invitation' => [
                'id' => $this->invitation->id,
                'team_id' => $this->invitation->team_id,
                'team_name' => $this->invitation->team->name,
                'game_name' => $this->invitation->team->game_name,
                'game_appid' => $this->invitation->team->game_appid,
                'inviter_id' => $this->invitation->inviter_id,
                'inviter_name' => $this->invitation->inviter->display_name,
                'inviter_avatar' => $this->invitation->inviter->profile?->avatar_url ?? null,
                'invitee_id' => $this->invitation->invitee_id,
                'invitee_name' => $this->invitation->invitee->display_name,
                'invitee_avatar' => $this->invitation->invitee->profile?->avatar_url ?? null,
                'role' => $this->invitation->role,
                'role_display_name' => $this->invitation->role_display_name,
                'message' => $this->invitation->message,
                'expires_at' => $this->invitation->expires_at?->toISOString(),
                'expires_in' => $this->invitation->expires_in,
                'created_at' => $this->invitation->created_at->toISOString(),
            ],
        ];
    }
}
