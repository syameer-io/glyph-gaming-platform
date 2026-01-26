<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerTag extends Model
{
    protected $fillable = [
        'server_id',
        'tag_type',
        'tag_value',
        'weight',
    ];

    protected $casts = [
        'weight' => 'integer',
    ];

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public static function getTagTypes(): array
    {
        return ['game', 'skill_level', 'region', 'language', 'activity_time'];
    }

    public static function getSkillLevels(): array
    {
        return ['beginner', 'intermediate', 'advanced', 'expert'];
    }

    public static function getRegions(): array
    {
        return ['na_east', 'na_west', 'eu_west', 'eu_east', 'asia', 'oceania', 'sa'];
    }

    public static function getLanguages(): array
    {
        return ['english', 'spanish', 'french', 'german', 'portuguese', 'russian', 'chinese'];
    }

    public static function getActivityTimes(): array
    {
        return ['morning', 'afternoon', 'evening', 'night', 'weekend'];
    }

    public static function getGameTags(): array
    {
        return [
            '730' => 'cs2',
            '548430' => 'deep_rock_galactic',
            '493520' => 'gtfo',
        ];
    }
}
