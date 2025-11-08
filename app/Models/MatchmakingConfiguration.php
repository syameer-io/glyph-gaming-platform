<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class MatchmakingConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'weights',
        'thresholds',
        'settings',
        'applies_to',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weights' => 'array',
        'thresholds' => 'array',
        'settings' => 'array',
    ];

    /**
     * Boot method to clear cache on update
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($configuration) {
            // Clear all matchmaking config caches
            Cache::forget('matchmaking_config_default');
            Cache::forget('matchmaking_config_all');
            Cache::forget('matchmaking_config_' . $configuration->applies_to);

            // Clear all game-specific caches
            Cache::flush();
        });

        static::deleted(function ($configuration) {
            // Clear caches on deletion
            Cache::forget('matchmaking_config_default');
            Cache::forget('matchmaking_config_all');
            Cache::forget('matchmaking_config_' . $configuration->applies_to);
        });
    }

    /**
     * Get active configuration for scope (all, game, server)
     *
     * @param string $scope Scope like 'all', 'game:730', 'server:5'
     * @return MatchmakingConfiguration
     */
    public static function getActiveForScope(string $scope = 'all'): self
    {
        $cacheKey = "matchmaking_config_{$scope}";

        return Cache::remember($cacheKey, 3600, function () use ($scope) {
            // Try to find specific scope configuration
            $config = self::where('is_active', true)
                ->where('applies_to', $scope)
                ->first();

            if (!$config) {
                // Fallback to default
                $config = self::where('is_active', true)
                    ->where('applies_to', 'all')
                    ->first();
            }

            if (!$config) {
                // Create default if none exists
                return self::createDefault();
            }

            return $config;
        });
    }

    /**
     * Create default configuration
     *
     * @return MatchmakingConfiguration
     */
    public static function createDefault(): self
    {
        return self::create([
            'name' => 'default',
            'description' => 'Default configuration (5 criteria - SIZE removed)',
            'is_active' => true,
            'weights' => [
                'skill' => 0.40,
                'composition' => 0.30,  // Increased from 0.25 (absorbed SIZE's 5%)
                'region' => 0.15,
                'schedule' => 0.10,
                'language' => 0.05,
            ],
            'thresholds' => [
                'min_compatibility' => 50,
                'max_results' => 10,
            ],
            'settings' => [
                'enable_skill_penalty' => true,
                'skill_penalty_threshold' => 2,
                'skill_penalty_multiplier' => 0.5,
            ],
            'applies_to' => 'all',
        ]);
    }

    /**
     * Validate that weights sum to 1.0
     *
     * @return bool
     */
    public function validateWeights(): bool
    {
        $sum = array_sum($this->weights);
        return abs($sum - 1.0) < 0.001;
    }

    /**
     * Get human-readable scope description
     *
     * @return string
     */
    public function getScopeDescriptionAttribute(): string
    {
        if ($this->applies_to === 'all') {
            return 'All games and servers';
        }

        if (str_starts_with($this->applies_to, 'game:')) {
            $gameId = substr($this->applies_to, 5);
            $gameNames = [
                '730' => 'CS2',
                '570' => 'Dota 2',
                '230410' => 'Warframe',
                '1172470' => 'Apex Legends',
                '252490' => 'Rust',
                '578080' => 'PUBG',
                '359550' => 'Rainbow Six Siege',
                '433850' => 'Fall Guys',
            ];
            return 'Game: ' . ($gameNames[$gameId] ?? $gameId);
        }

        if (str_starts_with($this->applies_to, 'server:')) {
            $serverId = substr($this->applies_to, 7);
            return "Server #{$serverId}";
        }

        return $this->applies_to;
    }
}
