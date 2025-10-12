# Steam Lobby Link - Usage Examples

Quick reference guide for using the lobby link functionality in controllers, views, and API endpoints.

---

## Controller Examples

### Setting a Lobby Link

```php
// app/Http/Controllers/LobbyController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Profile;

class LobbyController extends Controller
{
    /**
     * Update user's lobby link
     */
    public function store(Request $request)
    {
        // Validate the lobby link format
        $request->validate([
            'lobby_link' => [
                'required',
                'string',
                'max:512',
                function ($attribute, $value, $fail) {
                    if (!Profile::isValidSteamLobbyLink($value)) {
                        $fail('The lobby link must be a valid CS2 Steam lobby URL (steam://joinlobby/730/...)');
                    }
                }
            ]
        ]);

        try {
            // Use the helper method - it validates, sets timestamp, and saves automatically
            $request->user()->profile->setLobbyLink($request->lobby_link);

            return response()->json([
                'success' => true,
                'message' => 'Lobby link updated successfully',
                'data' => [
                    'lobby_link' => $request->user()->profile->steam_lobby_link,
                    'created_at' => $request->user()->profile->steam_lobby_link_updated_at,
                    'expires_at' => $request->user()->profile->steam_lobby_link_updated_at
                        ->addMinutes(Profile::LOBBY_EXPIRATION_MINUTES),
                    'expires_in_minutes' => Profile::LOBBY_EXPIRATION_MINUTES
                ]
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Get current user's lobby status
     */
    public function show(Request $request)
    {
        $profile = $request->user()->profile;

        // Check if lobby exists and is still valid
        if (!$profile->hasActiveLobby()) {
            return response()->json([
                'has_lobby' => false,
                'message' => 'No active lobby'
            ]);
        }

        // Get lobby age for displaying countdown
        $ageInMinutes = $profile->getLobbyAgeInMinutes();
        $remainingMinutes = Profile::LOBBY_EXPIRATION_MINUTES - $ageInMinutes;

        return response()->json([
            'has_lobby' => true,
            'lobby_link' => $profile->steam_lobby_link,
            'created_at' => $profile->steam_lobby_link_updated_at,
            'age_minutes' => round($ageInMinutes, 1),
            'remaining_minutes' => round($remainingMinutes, 1),
            'expires_at' => $profile->steam_lobby_link_updated_at
                ->addMinutes(Profile::LOBBY_EXPIRATION_MINUTES)
        ]);
    }

    /**
     * Clear user's lobby link
     */
    public function destroy(Request $request)
    {
        $profile = $request->user()->profile;

        if (!$profile->steam_lobby_link) {
            return response()->json([
                'success' => false,
                'message' => 'No lobby link to clear'
            ], 404);
        }

        $profile->clearLobby();

        return response()->json([
            'success' => true,
            'message' => 'Lobby link cleared successfully'
        ]);
    }

    /**
     * Get list of active lobbies for matchmaking
     */
    public function index(Request $request)
    {
        // Get all profiles with lobby links
        $profiles = Profile::whereNotNull('steam_lobby_link')
            ->with('user') // Eager load to prevent N+1
            ->get()
            ->filter(function ($profile) {
                // Filter to only active (non-expired) lobbies
                return $profile->hasActiveLobby();
            })
            ->map(function ($profile) {
                return [
                    'user_id' => $profile->user_id,
                    'username' => $profile->user->username,
                    'avatar' => $profile->avatar_url,
                    'lobby_link' => $profile->steam_lobby_link,
                    'age_minutes' => round($profile->getLobbyAgeInMinutes(), 1),
                    'remaining_minutes' => round(
                        Profile::LOBBY_EXPIRATION_MINUTES - $profile->getLobbyAgeInMinutes(),
                        1
                    )
                ];
            })
            ->values(); // Re-index array after filter

        return response()->json([
            'success' => true,
            'lobbies' => $profiles,
            'count' => $profiles->count()
        ]);
    }
}
```

---

## Middleware for Lobby Validation

```php
// app/Http/Middleware/EnsureActiveLobby.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveLobby
{
    /**
     * Ensure user has an active lobby before allowing action
     */
    public function handle(Request $request, Closure $next)
    {
        $profile = $request->user()->profile;

        // Check if lobby exists
        if (!$profile->steam_lobby_link) {
            return response()->json([
                'error' => 'No lobby link set'
            ], 404);
        }

        // Check if lobby has expired
        if ($profile->isLobbyExpired()) {
            // Auto-cleanup expired lobby
            $profile->clearLobby();

            return response()->json([
                'error' => 'Lobby link has expired'
            ], 410); // 410 Gone - resource no longer available
        }

        return $next($request);
    }
}
```

---

## Blade View Examples

### Display Lobby Status

```blade
<!-- resources/views/profile/show.blade.php -->

@if($profile->hasActiveLobby())
    <div class="lobby-active bg-green-100 border border-green-400 p-4 rounded">
        <h3 class="text-lg font-bold text-green-800">Active Lobby</h3>
        <p class="text-sm text-green-700">
            Created {{ round($profile->getLobbyAgeInMinutes()) }} minutes ago
        </p>
        <p class="text-sm text-green-700">
            Expires in {{ round(\App\Models\Profile::LOBBY_EXPIRATION_MINUTES - $profile->getLobbyAgeInMinutes()) }} minutes
        </p>

        <div class="mt-3 flex gap-2">
            <a href="{{ $profile->steam_lobby_link }}"
               class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Join Lobby
            </a>

            <button onclick="clearLobby()"
                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Close Lobby
            </button>
        </div>
    </div>
@else
    <div class="lobby-inactive bg-gray-100 border border-gray-300 p-4 rounded">
        <h3 class="text-lg font-bold text-gray-800">No Active Lobby</h3>
        <p class="text-sm text-gray-600">Share your CS2 lobby link to find teammates</p>

        <button onclick="showLobbyModal()"
                class="mt-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            Share Lobby Link
        </button>
    </div>
@endif

<script>
function clearLobby() {
    if (confirm('Are you sure you want to clear your lobby link?')) {
        fetch('/api/profile/lobby', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

function showLobbyModal() {
    // Show modal to input lobby link
    document.getElementById('lobbyModal').classList.remove('hidden');
}
</script>
```

### Lobby List for Matchmaking

```blade
<!-- resources/views/matchmaking/lobbies.blade.php -->

<div class="lobbies-list space-y-4">
    @forelse($activeLobbies as $lobby)
        <div class="lobby-card bg-white border rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ $lobby['avatar'] }}"
                     alt="{{ $lobby['username'] }}"
                     class="w-12 h-12 rounded-full">

                <div>
                    <h4 class="font-bold">{{ $lobby['username'] }}</h4>
                    <p class="text-sm text-gray-600">
                        Lobby active for {{ round($lobby['age_minutes']) }} min
                        (expires in {{ round($lobby['remaining_minutes']) }} min)
                    </p>
                </div>
            </div>

            <a href="{{ $lobby['lobby_link'] }}"
               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Join Lobby
            </a>
        </div>
    @empty
        <div class="text-center text-gray-500 py-8">
            No active lobbies available
        </div>
    @endforelse
</div>
```

---

## JavaScript/AJAX Examples

### Submit Lobby Link

```javascript
// public/js/lobby.js

/**
 * Submit lobby link to backend
 */
async function submitLobbyLink(lobbyLink) {
    try {
        const response = await fetch('/api/profile/lobby', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ lobby_link: lobbyLink })
        });

        const data = await response.json();

        if (response.ok) {
            console.log('Lobby link set successfully:', data);

            // Display success message
            alert(`Lobby link active! Expires in ${data.data.expires_in_minutes} minutes`);

            // Reload page or update UI
            location.reload();
        } else {
            console.error('Error setting lobby link:', data);
            alert(data.error || 'Failed to set lobby link');
        }
    } catch (error) {
        console.error('Network error:', error);
        alert('Network error occurred');
    }
}

/**
 * Get current lobby status and update UI
 */
async function updateLobbyStatus() {
    try {
        const response = await fetch('/api/profile/lobby');
        const data = await response.json();

        const statusElement = document.getElementById('lobby-status');

        if (data.has_lobby) {
            statusElement.innerHTML = `
                <div class="lobby-active">
                    <p>Active Lobby</p>
                    <p>Expires in ${Math.round(data.remaining_minutes)} minutes</p>
                    <a href="${data.lobby_link}">Join Lobby</a>
                </div>
            `;

            // Start countdown timer
            startCountdown(data.remaining_minutes);
        } else {
            statusElement.innerHTML = `
                <div class="lobby-inactive">
                    <p>No active lobby</p>
                    <button onclick="showLobbyModal()">Share Lobby</button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error fetching lobby status:', error);
    }
}

/**
 * Countdown timer for lobby expiration
 */
function startCountdown(remainingMinutes) {
    const countdownElement = document.getElementById('lobby-countdown');
    let remaining = remainingMinutes * 60; // Convert to seconds

    const interval = setInterval(() => {
        if (remaining <= 0) {
            clearInterval(interval);
            alert('Your lobby has expired!');
            location.reload();
            return;
        }

        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;

        countdownElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        remaining--;
    }, 1000);
}

/**
 * Validate lobby link format on client side
 */
function validateLobbyLink(link) {
    const pattern = /^steam:\/\/joinlobby\/730\/\d+(\/\d+)?$/;
    return pattern.test(link);
}

// Example usage
document.getElementById('lobbyForm').addEventListener('submit', (e) => {
    e.preventDefault();

    const lobbyInput = document.getElementById('lobbyLinkInput');
    const lobbyLink = lobbyInput.value.trim();

    // Client-side validation
    if (!validateLobbyLink(lobbyLink)) {
        alert('Invalid lobby link format. Expected: steam://joinlobby/730/[lobby_id]/[steam_id]');
        return;
    }

    submitLobbyLink(lobbyLink);
});

// Auto-refresh lobby list every 30 seconds
setInterval(() => {
    updateLobbyStatus();
}, 30000);
```

---

## API Route Definitions

```php
// routes/api.php

use App\Http\Controllers\LobbyController;

Route::middleware('auth:sanctum')->group(function () {
    // Lobby management
    Route::post('/profile/lobby', [LobbyController::class, 'store']);
    Route::get('/profile/lobby', [LobbyController::class, 'show']);
    Route::delete('/profile/lobby', [LobbyController::class, 'destroy']);

    // Matchmaking lobby list
    Route::get('/matchmaking/lobbies', [LobbyController::class, 'index']);
});
```

---

## Scheduled Cleanup Job

```php
// app/Console/Commands/CleanupExpiredLobbies.php

namespace App\Console\Commands;

use App\Models\Profile;
use Illuminate\Console\Command;

class CleanupExpiredLobbies extends Command
{
    protected $signature = 'lobbies:cleanup';
    protected $description = 'Clear expired lobby links from profiles';

    public function handle()
    {
        $this->info('Cleaning up expired lobbies...');

        $count = 0;

        // Use chunk to handle large datasets efficiently
        Profile::whereNotNull('steam_lobby_link')
            ->chunk(100, function ($profiles) use (&$count) {
                foreach ($profiles as $profile) {
                    if ($profile->isLobbyExpired()) {
                        $profile->clearLobby();
                        $count++;
                    }
                }
            });

        $this->info("Cleaned up {$count} expired lobbies");
    }
}
```

**Register in Scheduler:**

```php
// app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Run cleanup every 5 minutes
    $schedule->command('lobbies:cleanup')
             ->everyFiveMinutes()
             ->withoutOverlapping();
}
```

---

## Form Request Validation

```php
// app/Http/Requests/StoreLobbyLinkRequest.php

namespace App\Http\Requests;

use App\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;

class StoreLobbyLinkRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Authorization handled by auth middleware
    }

    public function rules()
    {
        return [
            'lobby_link' => [
                'required',
                'string',
                'max:512',
                function ($attribute, $value, $fail) {
                    if (!Profile::isValidSteamLobbyLink($value)) {
                        $fail('The lobby link must be a valid CS2 Steam lobby URL (steam://joinlobby/730/...)');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'lobby_link.required' => 'Please provide a lobby link',
            'lobby_link.max' => 'Lobby link is too long'
        ];
    }
}
```

**Use in Controller:**

```php
public function store(StoreLobbyLinkRequest $request)
{
    try {
        $request->user()->profile->setLobbyLink($request->lobby_link);
        return response()->json(['success' => true]);
    } catch (\InvalidArgumentException $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}
```

---

## Testing Examples

### Quick Manual Test via Tinker

```bash
php artisan tinker
```

```php
// Get a user's profile
$profile = User::first()->profile;

// Set a lobby link
$profile->setLobbyLink('steam://joinlobby/730/123456789/987654321');

// Check if active
$profile->hasActiveLobby(); // Should return true

// Get age
$profile->getLobbyAgeInMinutes(); // Should return ~0

// Check expiration
$profile->isLobbyExpired(); // Should return false

// Clear lobby
$profile->clearLobby();

// Verify cleared
$profile->steam_lobby_link; // Should be null
$profile->hasActiveLobby(); // Should return false
```

---

## Common Pitfalls & Solutions

### 1. Lobby Shows as Expired Immediately
**Cause:** Timestamp not being set correctly

**Solution:**
```php
// ❌ WRONG - timestamp not set
$profile->steam_lobby_link = $link;
$profile->save();

// ✅ CORRECT - use helper method
$profile->setLobbyLink($link);
```

### 2. Validation Passes Invalid Links
**Cause:** Not using the static validation method

**Solution:**
```php
// ❌ WRONG - no validation
$profile->steam_lobby_link = $request->lobby_link;

// ✅ CORRECT - validate first
if (Profile::isValidSteamLobbyLink($request->lobby_link)) {
    $profile->setLobbyLink($request->lobby_link);
}
```

### 3. N+1 Queries When Listing Lobbies
**Cause:** Not eager loading user relationship

**Solution:**
```php
// ❌ WRONG - causes N+1
$profiles = Profile::whereNotNull('steam_lobby_link')->get();

// ✅ CORRECT - eager load
$profiles = Profile::with('user')->whereNotNull('steam_lobby_link')->get();
```

---

This guide provides complete examples for implementing lobby link functionality across your application. All examples follow Laravel best practices and include proper error handling.
