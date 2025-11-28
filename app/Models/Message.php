<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'user_id',
        'content',
        'is_edited',
        'edited_at',
        'is_pinned',
        'pinned_at',
        'pinned_by',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
    ];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pinnedByUser()
    {
        return $this->belongsTo(User::class, 'pinned_by');
    }

    public function canEdit($userId)
    {
        return $this->user_id === $userId;
    }

    public function canDelete($userId)
    {
        return $this->user_id === $userId;
    }

    public function markAsEdited()
    {
        $this->update([
            'is_edited' => true,
            'edited_at' => now(),
        ]);
    }
}