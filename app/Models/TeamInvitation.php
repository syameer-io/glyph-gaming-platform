<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamInvitation extends Model
{
    use HasFactory;

    // ==================== CONFIGURATION ====================

    protected $fillable = [
        'team_id',
        'inviter_id',
        'invitee_id',
        'status',
        'role',
        'message',
        'responded_at',
        'expires_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the team this invitation is for
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who sent the invitation
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    /**
     * Get the user being invited
     */
    public function invitee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }

    // ==================== SCOPES ====================

    /**
     * Get only pending invitations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get active (pending and not expired) invitations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'pending')
                     ->where(function ($q) {
                         $q->whereNull('expires_at')
                           ->orWhere('expires_at', '>', now());
                     });
    }

    /**
     * Get expired invitations (pending but past expiry)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
                     ->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    /**
     * Get invitations for a specific user (as invitee)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('invitee_id', $userId);
    }

    /**
     * Get invitations from a specific team
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $teamId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Get accepted invitations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Get declined invitations
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    // ==================== STATUS CHECKS ====================

    /**
     * Check if invitation is pending
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if invitation was accepted
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if invitation was declined
     *
     * @return bool
     */
    public function isDeclined(): bool
    {
        return $this->status === 'declined';
    }

    /**
     * Check if invitation was cancelled
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if invitation is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if ($this->status === 'expired') {
            return true;
        }

        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if invitation can still be acted upon (accept/decline)
     *
     * @return bool
     */
    public function isActionable(): bool
    {
        return $this->isPending() && !$this->isExpired();
    }

    // ==================== STATUS TRANSITIONS ====================

    /**
     * Accept the invitation
     *
     * @return bool
     */
    public function accept(): bool
    {
        if (!$this->isActionable()) {
            return false;
        }

        $this->status = 'accepted';
        $this->responded_at = now();
        return $this->save();
    }

    /**
     * Decline the invitation
     *
     * @return bool
     */
    public function decline(): bool
    {
        if (!$this->isActionable()) {
            return false;
        }

        $this->status = 'declined';
        $this->responded_at = now();
        return $this->save();
    }

    /**
     * Cancel the invitation (by team leader)
     *
     * @return bool
     */
    public function cancel(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = 'cancelled';
        return $this->save();
    }

    /**
     * Mark invitation as expired
     *
     * @return bool
     */
    public function markExpired(): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->status = 'expired';
        return $this->save();
    }

    // ==================== ACCESSORS ====================

    /**
     * Get human-readable expiration time
     *
     * @return string|null
     */
    public function getExpiresInAttribute(): ?string
    {
        if (!$this->expires_at) {
            return null;
        }

        if ($this->expires_at->isPast()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Get display name for the assigned role
     *
     * @return string
     */
    public function getRoleDisplayNameAttribute(): string
    {
        return match($this->role) {
            'leader' => 'Team Leader',
            'co_leader' => 'Co-Leader',
            'member' => 'Member',
            default => 'Unknown'
        };
    }

    /**
     * Get CSS class for status badge
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-yellow-500',
            'accepted' => 'bg-green-500',
            'declined' => 'bg-red-500',
            'cancelled' => 'bg-gray-500',
            'expired' => 'bg-gray-400',
            default => 'bg-gray-500'
        };
    }
}
