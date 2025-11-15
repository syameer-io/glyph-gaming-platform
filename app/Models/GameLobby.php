<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class GameLobby extends Model
{
    protected $fillable = [
        'user_id',
        'game_id',
        'join_method',
        'steam_app_id',
        'steam_lobby_id',
        'steam_profile_id',
        'server_ip',
        'server_port',
        'server_password',
        'lobby_code',
        'join_command',
        'match_name',
        'match_password',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'server_port' => 'integer',
        'steam_app_id' => 'integer',
        'server_password' => 'encrypted',
        'match_password' => 'encrypted',
    ];

    protected $hidden = [
        'server_password',
        'match_password',
    ];

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * For now, we don't have a Game model, so game_id references user_gaming_preferences.game_appid
     * This will be updated when a proper games table is created
     */
    public function gamingPreference(): BelongsTo
    {
        return $this->belongsTo(UserGamingPreference::class, 'game_id', 'game_appid');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Business logic methods
     */

    /**
     * Check if lobby has expired
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false; // Persistent lobbies never expire
        }

        return $this->expires_at->isPast();
    }

    /**
     * Check if lobby is currently active
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Get time remaining until expiration in minutes
     */
    public function timeRemaining(): ?int
    {
        if ($this->expires_at === null) {
            return null; // Persistent lobby
        }

        if ($this->isExpired()) {
            return 0;
        }

        return (int) now()->diffInMinutes($this->expires_at);
    }

    /**
     * Generate join link/code based on join method
     */
    public function generateJoinLink(): ?string
    {
        return match($this->join_method) {
            'steam_lobby' => $this->steam_app_id && $this->steam_lobby_id && $this->steam_profile_id
                ? "steam://joinlobby/{$this->steam_app_id}/{$this->steam_lobby_id}/{$this->steam_profile_id}"
                : null,

            'steam_connect' => $this->server_ip && $this->server_port
                ? "steam://connect/{$this->server_ip}:{$this->server_port}"
                : null,

            'lobby_code' => $this->lobby_code,

            'server_address' => $this->server_ip && $this->server_port
                ? "{$this->server_ip}:{$this->server_port}"
                : $this->server_ip,

            'join_command' => $this->join_command,

            'private_match' => $this->match_name,

            default => null,
        };
    }

    /**
     * Mark lobby as expired/inactive
     */
    public function markAsExpired(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Get join instructions based on join method and configuration
     */
    public function getJoinInstructions(): array
    {
        $baseInstructions = [
            'join_method' => $this->join_method,
            'join_link' => $this->generateJoinLink(),
        ];

        // Add method-specific details
        $methodDetails = match($this->join_method) {
            'steam_lobby' => [
                'app_id' => $this->steam_app_id,
                'instructions' => 'Click the join button or paste the Steam link in your browser.',
            ],
            'steam_connect' => [
                'server_ip' => $this->server_ip,
                'server_port' => $this->server_port,
                'has_password' => !empty($this->server_password),
                'instructions' => 'Click the join button or paste the Steam connect link in your browser.',
            ],
            'lobby_code' => [
                'code' => $this->lobby_code,
                'instructions' => 'Copy the code and paste it in the game\'s lobby join screen.',
            ],
            'server_address' => [
                'server_ip' => $this->server_ip,
                'server_port' => $this->server_port,
                'instructions' => 'Copy the server address and add it to your server list in-game.',
            ],
            'join_command' => [
                'command' => $this->join_command,
                'instructions' => 'Copy the command and paste it in the game\'s chat.',
            ],
            'private_match' => [
                'match_name' => $this->match_name,
                'has_password' => !empty($this->match_password),
                'instructions' => 'Search for the match name in-game private match browser.',
            ],
            default => [],
        };

        return array_merge($baseInstructions, $methodDetails);
    }
}
