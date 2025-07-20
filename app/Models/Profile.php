<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar_url',
        'bio',
        'status',
        'steam_data',
    ];

    protected $casts = [
        'steam_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getSteamGamesAttribute()
    {
        return $this->steam_data['games'] ?? [];
    }

    public function getTotalPlaytimeAttribute()
    {
        if (!isset($this->steam_data['games'])) {
            return 0;
        }

        return collect($this->steam_data['games'])
            ->sum('playtime_forever');
    }

    public function getCurrentGameAttribute()
    {
        return $this->steam_data['current_game'] ?? null;
    }
}