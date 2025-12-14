<?php

namespace App\Events;

use App\Models\TeamInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationAccepted implements ShouldBroadcast
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
     * - Notify the team channel (new member added)
     * - Notify the inviter (their invitation was accepted)
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('team.' . $this->invitation->team_id),
            new PrivateChannel('user.' . $this->invitation->inviter_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'team.invitation.accepted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'team_id' => $this->invitation->team_id,
            'team_name' => $this->invitation->team->name,
            'invitee_id' => $this->invitation->invitee_id,
            'invitee_name' => $this->invitation->invitee->display_name,
            'invitee_avatar' => $this->invitation->invitee->profile?->avatar_url ?? null,
            'role' => $this->invitation->role,
            'role_display_name' => $this->invitation->role_display_name,
            'accepted_at' => $this->invitation->responded_at?->toISOString(),
        ];
    }
}
