<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGamingPreference extends Model
{
    protected $fillable = [
        'user_id',
        'game_appid',
        'game_name',
        'playtime_forever',
        'playtime_2weeks',
        'preference_level',
        'skill_level',
        'last_played',
    ];

    protected $casts = [
        'playtime_forever' => 'integer',
        'playtime_2weeks' => 'integer',
        'last_played' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPlaytimeHours(): float
    {
        return round($this->playtime_forever / 60, 1);
    }

    public function getRecentPlaytimeHours(): float
    {
        return round($this->playtime_2weeks / 60, 1);
    }

    public function isRecentlyPlayed(): bool
    {
        return $this->playtime_2weeks > 0;
    }

    public function getPreferenceWeight(): int
    {
        return match($this->preference_level) {
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 1,
        };
    }

    public function getSkillWeight(): int
    {
        return match($this->skill_level) {
            'expert' => 4,
            'advanced' => 3,
            'intermediate' => 2,
            'beginner' => 1,
            default => 2,
        };
    }
}
