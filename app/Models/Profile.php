<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Profile extends Model
{
    use HasFactory;

    /**
     * Lobby expiration time in minutes.
     * CS2 lobbies are typically short-lived, 30 minutes provides reasonable validity window.
     */
    public const LOBBY_EXPIRATION_MINUTES = 30;

    protected $fillable = [
        'user_id',
        'avatar_url',
        'bio',
        'status',
        'steam_data',
        'steam_lobby_link',
        'steam_lobby_link_updated_at',
    ];

    protected $casts = [
        'steam_data' => 'array',
        'steam_lobby_link_updated_at' => 'datetime',
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

    // ==========================================
    // Steam Lobby Management Methods
    // ==========================================

    /**
     * Check if the profile has an active (non-expired) lobby link.
     *
     * A lobby is considered active if:
     * 1. A lobby link exists
     * 2. The link was updated within the last 30 minutes
     *
     * @return bool True if lobby exists and is not expired
     */
    public function hasActiveLobby(): bool
    {
        // No lobby link set
        if (empty($this->steam_lobby_link)) {
            return false;
        }

        // No timestamp (shouldn't happen, but defensive coding)
        if (is_null($this->steam_lobby_link_updated_at)) {
            return false;
        }

        // Check if lobby has expired
        return !$this->isLobbyExpired();
    }

    /**
     * Get the age of the lobby link in minutes.
     *
     * Returns null if no lobby exists or timestamp is missing.
     * Useful for displaying "lobby created X minutes ago" to users.
     *
     * @return float|null Minutes since lobby was last updated, or null if no lobby
     */
    public function getLobbyAgeInMinutes(): ?float
    {
        if (empty($this->steam_lobby_link) || is_null($this->steam_lobby_link_updated_at)) {
            return null;
        }

        // Calculate difference in minutes with decimal precision
        return Carbon::now()->diffInMinutes($this->steam_lobby_link_updated_at, true);
    }

    /**
     * Check if the lobby link has expired based on LOBBY_EXPIRATION_MINUTES.
     *
     * Returns true if:
     * - Lobby link exists but is older than expiration time
     *
     * Returns false if:
     * - No lobby exists (can't be expired if it doesn't exist)
     * - Lobby exists and is still within valid time window
     *
     * @return bool True if lobby has expired
     */
    public function isLobbyExpired(): bool
    {
        // If no lobby exists, it's not expired (it's just absent)
        if (empty($this->steam_lobby_link) || is_null($this->steam_lobby_link_updated_at)) {
            return false;
        }

        // Check if current time exceeds expiration threshold (use absolute value)
        return Carbon::now()->diffInMinutes($this->steam_lobby_link_updated_at, true) > self::LOBBY_EXPIRATION_MINUTES;
    }

    /**
     * Clear the lobby link and its timestamp.
     *
     * Use cases:
     * - User manually closes their lobby
     * - Lobby expires and needs cleanup
     * - User wants to stop sharing lobby
     *
     * Note: This method saves the model automatically.
     * Use fill() + save() manually if batching multiple updates.
     *
     * @return bool True if successfully saved
     */
    public function clearLobby(): bool
    {
        $this->steam_lobby_link = null;
        $this->steam_lobby_link_updated_at = null;

        return $this->save();
    }

    /**
     * Validate if a string is a valid Steam lobby link format.
     *
     * Valid format: steam://joinlobby/730/[lobby_id]/[steam_id]
     * - Protocol: steam://
     * - Action: joinlobby
     * - App ID: 730 (CS2/CS:GO)
     * - Lobby ID: numeric
     * - Steam ID: numeric (optional but typically present)
     *
     * @param string $link The lobby link to validate
     * @return bool True if link matches expected Steam lobby format
     */
    public static function isValidSteamLobbyLink(string $link): bool
    {
        // Pattern explanation:
        // ^steam:\/\/ - Must start with steam://
        // joinlobby\/ - Must have joinlobby action
        // 730\/ - CS2 app ID (730)
        // \d+ - Lobby ID (one or more digits)
        // (\/\d+)? - Optional Steam ID (slash followed by digits)
        // $ - End of string
        $pattern = '/^steam:\/\/joinlobby\/730\/\d+(\/\d+)?$/';

        return (bool) preg_match($pattern, $link);
    }

    /**
     * Update or set the Steam lobby link with automatic timestamp.
     *
     * This method:
     * 1. Validates the lobby link format
     * 2. Sets the link and current timestamp
     * 3. Saves the model
     *
     * @param string $lobbyLink The Steam lobby link to set
     * @return bool True if validation passed and save succeeded
     * @throws \InvalidArgumentException If lobby link format is invalid
     */
    public function setLobbyLink(string $lobbyLink): bool
    {
        // Validate format before setting
        if (!self::isValidSteamLobbyLink($lobbyLink)) {
            throw new \InvalidArgumentException(
                'Invalid Steam lobby link format. Expected: steam://joinlobby/730/[lobby_id]/[steam_id]'
            );
        }

        $this->steam_lobby_link = $lobbyLink;
        $this->steam_lobby_link_updated_at = Carbon::now();

        return $this->save();
    }
}