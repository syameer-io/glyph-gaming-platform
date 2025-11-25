<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Events\LobbyCreated;
use App\Events\LobbyDeleted;
use App\Events\LobbyExpired;
use Carbon\Carbon;

class GameLobby extends Model
{
    use HasFactory;
    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => LobbyCreated::class,
    ];
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
     * Legacy relationship to user's gaming preference
     * Returns null if user doesn't own the game (which is now allowed)
     *
     * @deprecated Use gameJoinConfiguration() for reliable game information
     */
    public function gamingPreference(): BelongsTo
    {
        return $this->belongsTo(UserGamingPreference::class, 'game_id', 'game_appid');
    }

    /**
     * Primary relationship to game join configuration
     * This provides reliable game information regardless of user ownership
     *
     * game_id references game_join_configurations.game_id (Steam App ID)
     */
    public function gameJoinConfiguration(): BelongsTo
    {
        return $this->belongsTo(GameJoinConfiguration::class, 'game_id', 'game_id');
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
     * Generate join link/code based on join method with enhanced validation
     *
     * @return string|null Returns the join link/code or null if required fields are missing
     */
    public function generateJoinLink(): ?string
    {
        try {
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
        } catch (\Exception $e) {
            \Log::error('Failed to generate join link for lobby', [
                'lobby_id' => $this->id,
                'join_method' => $this->join_method,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get user-friendly display format for the join method
     *
     * @return string Human-readable join method description
     */
    public function getDisplayFormat(): string
    {
        return match($this->join_method) {
            'steam_lobby' => 'Steam Lobby Link',
            'steam_connect' => 'Steam Connect',
            'lobby_code' => 'Lobby Code',
            'server_address' => 'Server Address',
            'join_command' => 'Join Command',
            'private_match' => 'Private Match',
            'manual_invite' => 'Manual Invite',
            default => 'Unknown Method',
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
     * Override delete to dispatch LobbyDeleted event
     *
     * @return bool|null
     */
    public function delete()
    {
        $lobbyId = $this->id;
        $userId = $this->user_id;

        $result = parent::delete();

        if ($result) {
            event(new LobbyDeleted($lobbyId, $userId));
        }

        return $result;
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
