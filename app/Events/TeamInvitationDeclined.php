<?php

namespace App\Events;

use App\Models\TeamInvitation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamInvitationDeclined implements ShouldBroadcast
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
     * - Notify the team channel (remove from pending list)
     * - Notify the inviter (their invitation was declined)
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
        return 'team.invitation.declined';
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
            'declined_at' => $this->invitation->responded_at?->toISOString(),
        ];
    }
}
