<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

class GameJoinConfiguration extends Model
{
    protected $fillable = [
        'game_id',
        'join_method',
        'display_name',
        'icon',
        'priority',
        'validation_pattern',
        'requires_manual_setup',
        'steam_app_id',
        'default_port',
        'expiration_minutes',
        'instructions_how_to_create',
        'instructions_how_to_join',
        'is_enabled',
    ];

    protected $casts = [
        'priority' => 'integer',
        'requires_manual_setup' => 'boolean',
        'is_enabled' => 'boolean',
        'steam_app_id' => 'integer',
        'default_port' => 'integer',
        'expiration_minutes' => 'integer',
    ];

    /**
     * Relationships
     */

    /**
     * For now, we don't have a Game model, so game_id references user_gaming_preferences.game_appid
     * This will be updated when a proper games table is created
     */
    public function gamingPreference(): BelongsTo
    {
        return $this->belongsTo(UserGamingPreference::class, 'game_id', 'game_appid');
    }

    /**
     * Business logic methods
     */

    /**
     * Validate lobby data against this configuration's validation pattern
     *
     * @param array $data The lobby data to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(array $data): bool
    {
        if (!$this->validation_pattern) {
            return true; // No pattern means no validation required
        }

        // Determine which field to validate based on join method
        $fieldToValidate = match($this->join_method) {
            'steam_lobby' => $data['steam_lobby_link'] ?? null,
            'steam_connect' => isset($data['server_ip']) && isset($data['server_port'])
                ? "{$data['server_ip']}:{$data['server_port']}"
                : null,
            'lobby_code' => $data['lobby_code'] ?? null,
            'join_command' => $data['join_command'] ?? null,
            'server_address' => isset($data['server_ip'])
                ? $data['server_ip']
                : null,
            default => null,
        };

        if ($fieldToValidate === null) {
            return false;
        }

        // Validate against pattern
        return preg_match('/' . $this->validation_pattern . '/', $fieldToValidate) === 1;
    }

    /**
     * Get expiration timestamp based on expiration_minutes setting
     *
     * @return Carbon|null Expiration timestamp, or null if persistent
     */
    public function getExpirationTimestamp(): ?Carbon
    {
        if ($this->expiration_minutes === null) {
            return null; // Persistent lobby
        }

        return now()->addMinutes($this->expiration_minutes);
    }

    /**
     * Scope to get enabled configurations only
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope to get unique enabled games for lobby creation
     * Returns distinct game_id entries where is_enabled = true
     */
    public function scopeEnabledGames($query)
    {
        return $query->where('is_enabled', true)
            ->select('game_id')
            ->distinct()
            ->orderBy('game_id');
    }

    /**
     * Scope to order by priority (highest first)
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
