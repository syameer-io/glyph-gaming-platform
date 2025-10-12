# Phase 4 Task 4.1: Steam Lobby Link Database Schema

## Implementation Complete ✓

This document details the database schema and model enhancements for CS2 Steam lobby link integration.

---

## Files Modified/Created

### 1. Migration File
**Location:** `database/migrations/2025_10_12_122628_add_lobby_link_to_profiles_table.php`

**Columns Added:**
- `steam_lobby_link` (VARCHAR 512, nullable)
- `steam_lobby_link_updated_at` (TIMESTAMP, nullable)

**Status:** ✅ Migrated successfully

### 2. Profile Model
**Location:** `app/Models/Profile.php`

**Enhancements:**
- Added lobby-related fields to `$fillable` array
- Added timestamp cast for `steam_lobby_link_updated_at`
- Implemented 6 comprehensive helper methods
- Added `LOBBY_EXPIRATION_MINUTES` constant (30 minutes)

---

## Design Decisions

### 1. Column Sizing
**`steam_lobby_link` - VARCHAR(512)**
- Steam lobby URLs follow format: `steam://joinlobby/730/[lobby_id]/[steam_id]`
- Lobby IDs and Steam IDs can be 17+ digits long
- 512 characters provides headroom for:
  - Future Steam URL parameters
  - Query strings
  - Potential URL variations
- Balance between storage efficiency and flexibility

### 2. Separate Timestamp Column
**Why not use `updated_at`?**
- Profile `updated_at` changes with ANY profile modification (bio, avatar, etc.)
- Lobby expiration needs precise 30-minute tracking from LOBBY update
- Separate timestamp ensures accurate expiration logic
- Allows profile updates without affecting lobby validity

### 3. Nullable Design
**Backward Compatibility:**
- Existing profiles won't break (no default values required)
- Users without active lobbies have NULL values
- Clean schema - no meaningless empty strings or zero timestamps

### 4. No Additional Indexes
**Performance Analysis:**
- Lobby queries will always be user-specific: `WHERE user_id = ?`
- `user_id` already has foreign key index from table creation
- Lobby link queries won't be filtered/searched globally
- Adding index on `steam_lobby_link` would waste space and slow writes
- Timestamp column doesn't need index (always paired with user_id lookup)

### 5. Expiration Constant
**30 Minutes Rationale:**
- CS2 lobbies are short-lived (typically 5-15 minutes in practice)
- 30 minutes provides buffer for:
  - Players joining gradually
  - Pre-game warmup/discussion
  - Reconnection scenarios
- Short enough to prevent stale lobbies
- Long enough to be useful for matchmaking

---

## Model Helper Methods

### Core Methods

#### 1. `hasActiveLobby(): bool`
**Purpose:** Check if user has a valid, non-expired lobby

**Returns:**
- `true` - Lobby exists AND is within 30-minute window
- `false` - No lobby OR lobby expired

**Usage:**
```php
if ($profile->hasActiveLobby()) {
    // Show "Join Lobby" button
    return view('matchmaking.show', ['lobbyLink' => $profile->steam_lobby_link]);
}
```

**Logic:**
1. Checks lobby link exists
2. Checks timestamp exists
3. Delegates to `isLobbyExpired()` for age validation

---

#### 2. `getLobbyAgeInMinutes(): ?float`
**Purpose:** Get precise age of lobby for UI display

**Returns:**
- `float` - Minutes since lobby was last updated (e.g., 12.5)
- `null` - No lobby exists

**Usage:**
```php
$age = $profile->getLobbyAgeInMinutes();
if ($age !== null) {
    echo "Lobby active for " . round($age) . " minutes";
    echo "Expires in " . (Profile::LOBBY_EXPIRATION_MINUTES - round($age)) . " minutes";
}
```

**Implementation:**
- Uses `Carbon::diffInMinutes()` with absolute value
- Decimal precision for accurate calculations
- Defensive null checks prevent errors

---

#### 3. `isLobbyExpired(): bool`
**Purpose:** Check if lobby has exceeded expiration threshold

**Returns:**
- `true` - Lobby exists AND is older than 30 minutes
- `false` - No lobby OR lobby still valid

**Usage:**
```php
if ($profile->isLobbyExpired()) {
    // Auto-cleanup expired lobby
    $profile->clearLobby();
    return response()->json(['message' => 'Lobby expired'], 410);
}
```

**Logic:**
- Returns `false` if no lobby (can't expire what doesn't exist)
- Calculates time difference from current moment
- Compares against `LOBBY_EXPIRATION_MINUTES` constant

---

#### 4. `clearLobby(): bool`
**Purpose:** Remove lobby link and timestamp, persist to database

**Returns:**
- `true` - Successfully saved
- `false` - Save failed

**Usage:**
```php
// User manually closes lobby
$profile->clearLobby();

// Batch operation (don't auto-save)
$profile->fill([
    'steam_lobby_link' => null,
    'steam_lobby_link_updated_at' => null,
    'status' => 'online'
])->save();
```

**Note:** This method calls `save()` automatically. For batch updates, set fields manually and save once.

---

#### 5. `isValidSteamLobbyLink(string $link): bool` (Static)
**Purpose:** Validate Steam lobby URL format before storage

**Returns:**
- `true` - Link matches CS2 lobby format
- `false` - Invalid format

**Usage:**
```php
// Controller validation
if (!Profile::isValidSteamLobbyLink($request->lobby_link)) {
    return back()->withErrors(['lobby_link' => 'Invalid Steam lobby link format']);
}

// Or use in Form Request validation
'lobby_link' => ['required', 'string', function ($attribute, $value, $fail) {
    if (!Profile::isValidSteamLobbyLink($value)) {
        $fail('The lobby link must be a valid CS2 Steam lobby URL.');
    }
}]
```

**Validation Pattern:**
```regex
^steam:\/\/joinlobby\/730\/\d+(\/\d+)?$
```

**Pattern Breakdown:**
- `^steam:\/\/` - Must start with "steam://"
- `joinlobby\/` - Action must be "joinlobby"
- `730\/` - App ID must be 730 (CS2/CS:GO)
- `\d+` - Lobby ID (numeric)
- `(\/\d+)?` - Optional Steam ID (slash + numeric)
- `$` - End of string (no trailing garbage)

**Security Benefits:**
- Prevents XSS via malicious URLs
- Blocks non-Steam protocol injection
- Ensures only CS2 lobbies (not other games)
- Validates structure before database storage

---

#### 6. `setLobbyLink(string $lobbyLink): bool`
**Purpose:** Set lobby link with automatic validation and timestamping

**Returns:**
- `true` - Validation passed, successfully saved
- Throws `InvalidArgumentException` - Invalid format

**Usage:**
```php
try {
    $profile->setLobbyLink($request->lobby_link);
    return response()->json([
        'message' => 'Lobby link updated',
        'expires_at' => $profile->steam_lobby_link_updated_at->addMinutes(30)
    ]);
} catch (\InvalidArgumentException $e) {
    return response()->json(['error' => $e->getMessage()], 422);
}
```

**Flow:**
1. Validates format using `isValidSteamLobbyLink()`
2. Throws exception if invalid (controller catches and handles)
3. Sets `steam_lobby_link` to provided value
4. Sets `steam_lobby_link_updated_at` to current timestamp
5. Saves model automatically
6. Returns save result

**Why throw exception?**
- Forces caller to handle validation failures
- Prevents silent failures
- Clear error messages for debugging
- Follows Laravel exception patterns

---

## Security Considerations

### 1. URL Format Validation
**Threat:** Malicious URLs (XSS, protocol injection)

**Mitigation:**
- Strict regex validation before storage
- Only allows `steam://` protocol
- Only allows CS2 app ID (730)
- Rejects URLs with unexpected characters

**Example Attack Prevented:**
```php
// ❌ BLOCKED by validation
"javascript:alert('XSS')"
"steam://joinlobby/570/123/456" // Wrong app ID (Dota 2)
"steam://joinlobby/730/abc/def" // Non-numeric IDs
"steam://joinlobby/730/123/456?malicious=param"
```

### 2. Data Type Safety
**Threat:** Type juggling vulnerabilities

**Mitigation:**
- Timestamp cast to Carbon object
- String type enforcement on lobby link
- Null coalescing in helper methods
- Strict comparison operators (`===`, `!==`)

### 3. Mass Assignment Protection
**Threat:** Unintended field updates

**Mitigation:**
- New fields added to `$fillable` explicitly
- No `$guarded = []` wildcard
- `setLobbyLink()` method provides controlled interface
- Validation before assignment

### 4. Expiration Logic
**Threat:** Stale lobby links causing failed joins

**Mitigation:**
- 30-minute hard expiration
- `hasActiveLobby()` checks expiration before use
- Frontend should call this before displaying join button
- Backend should validate on join request

---

## Performance Considerations

### 1. Database Queries
**Efficient Patterns:**
```php
// ✅ GOOD: Single query with eager loading
$profiles = Profile::with('user')
    ->whereNotNull('steam_lobby_link')
    ->get()
    ->filter(fn($p) => $p->hasActiveLobby());

// ❌ BAD: N+1 queries
$profiles = Profile::whereNotNull('steam_lobby_link')->get();
foreach ($profiles as $profile) {
    if ($profile->hasActiveLobby()) {
        $user = $profile->user; // Separate query per profile!
    }
}
```

### 2. Caching Strategy
For high-traffic lobby listing pages:
```php
// Cache active lobbies for 1 minute
$activeLobbies = Cache::remember('active_lobbies', 60, function () {
    return Profile::whereNotNull('steam_lobby_link')
        ->where('steam_lobby_link_updated_at', '>', now()->subMinutes(30))
        ->with('user')
        ->get();
});
```

### 3. Index Analysis
**Current Indexes:**
- `user_id` (foreign key) - Sufficient for lobby queries
- No additional indexes needed

**Why?**
- Lobby queries always filter by `user_id` first
- Global lobby searches will use `whereNotNull()` + timestamp filter
- Small dataset (only active lobbies, max 30-min lifespan)
- Adding indexes would slow INSERT/UPDATE operations

**When to add index:**
If implementing global lobby search with filters:
```sql
-- Only if needed for complex queries
CREATE INDEX idx_steam_lobby_active ON profiles(steam_lobby_link_updated_at)
WHERE steam_lobby_link IS NOT NULL;
```

### 4. Memory Optimization
For bulk operations (e.g., cleanup expired lobbies):
```php
// Use chunk() for large datasets
Profile::whereNotNull('steam_lobby_link')
    ->chunk(100, function ($profiles) {
        foreach ($profiles as $profile) {
            if ($profile->isLobbyExpired()) {
                $profile->clearLobby();
            }
        }
    });
```

---

## Testing Scenarios

### 1. Unit Tests (Profile Model)
```php
// tests/Unit/ProfileTest.php

public function test_has_active_lobby_returns_true_for_valid_lobby()
{
    $profile = Profile::factory()->create([
        'steam_lobby_link' => 'steam://joinlobby/730/123456/789012',
        'steam_lobby_link_updated_at' => now()->subMinutes(15)
    ]);

    $this->assertTrue($profile->hasActiveLobby());
}

public function test_has_active_lobby_returns_false_for_expired_lobby()
{
    $profile = Profile::factory()->create([
        'steam_lobby_link' => 'steam://joinlobby/730/123456/789012',
        'steam_lobby_link_updated_at' => now()->subMinutes(35)
    ]);

    $this->assertFalse($profile->hasActiveLobby());
}

public function test_get_lobby_age_returns_correct_minutes()
{
    $profile = Profile::factory()->create([
        'steam_lobby_link' => 'steam://joinlobby/730/123456/789012',
        'steam_lobby_link_updated_at' => now()->subMinutes(20)
    ]);

    $age = $profile->getLobbyAgeInMinutes();
    $this->assertEqualsWithDelta(20, $age, 0.1);
}

public function test_is_lobby_expired_correctly_identifies_expired_lobbies()
{
    $profile = Profile::factory()->create([
        'steam_lobby_link' => 'steam://joinlobby/730/123456/789012',
        'steam_lobby_link_updated_at' => now()->subMinutes(31)
    ]);

    $this->assertTrue($profile->isLobbyExpired());
}

public function test_clear_lobby_removes_link_and_timestamp()
{
    $profile = Profile::factory()->create([
        'steam_lobby_link' => 'steam://joinlobby/730/123456/789012',
        'steam_lobby_link_updated_at' => now()
    ]);

    $profile->clearLobby();

    $this->assertNull($profile->steam_lobby_link);
    $this->assertNull($profile->steam_lobby_link_updated_at);
}

public function test_is_valid_steam_lobby_link_accepts_valid_format()
{
    $validLinks = [
        'steam://joinlobby/730/123456789012345678/987654321098765432',
        'steam://joinlobby/730/123/456',
        'steam://joinlobby/730/999999999999999999',
    ];

    foreach ($validLinks as $link) {
        $this->assertTrue(Profile::isValidSteamLobbyLink($link));
    }
}

public function test_is_valid_steam_lobby_link_rejects_invalid_format()
{
    $invalidLinks = [
        'http://steamcommunity.com/joinlobby/730/123/456',
        'steam://joinlobby/570/123/456', // Wrong app ID
        'steam://joinlobby/730/abc/def', // Non-numeric
        'javascript:alert("xss")',
        'steam://joinlobby/730/123/456?extra=param',
    ];

    foreach ($invalidLinks as $link) {
        $this->assertFalse(Profile::isValidSteamLobbyLink($link));
    }
}

public function test_set_lobby_link_updates_link_and_timestamp()
{
    $profile = Profile::factory()->create();
    $link = 'steam://joinlobby/730/123456/789012';

    $result = $profile->setLobbyLink($link);

    $this->assertTrue($result);
    $this->assertEquals($link, $profile->steam_lobby_link);
    $this->assertNotNull($profile->steam_lobby_link_updated_at);
}

public function test_set_lobby_link_throws_exception_for_invalid_format()
{
    $this->expectException(\InvalidArgumentException::class);

    $profile = Profile::factory()->create();
    $profile->setLobbyLink('invalid_link_format');
}
```

### 2. Feature Tests (Controller Integration)
```php
// tests/Feature/LobbyControllerTest.php

public function test_user_can_set_lobby_link()
{
    $user = User::factory()->create();
    $link = 'steam://joinlobby/730/123456/789012';

    $response = $this->actingAs($user)
        ->postJson('/api/profile/lobby', ['lobby_link' => $link]);

    $response->assertStatus(200);
    $this->assertEquals($link, $user->profile->fresh()->steam_lobby_link);
}

public function test_user_cannot_set_invalid_lobby_link()
{
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/profile/lobby', ['lobby_link' => 'invalid_link']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['lobby_link']);
}

public function test_expired_lobby_returns_410_status()
{
    $user = User::factory()->create();
    $user->profile->update([
        'steam_lobby_link' => 'steam://joinlobby/730/123456/789012',
        'steam_lobby_link_updated_at' => now()->subMinutes(35)
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/profile/lobby');

    $response->assertStatus(410)
        ->assertJson(['message' => 'Lobby expired']);
}
```

---

## Migration Rollback

If needed to rollback:
```bash
php artisan migrate:rollback --step=1
```

The `down()` method will cleanly remove both columns:
```php
public function down(): void
{
    Schema::table('profiles', function (Blueprint $table) {
        $table->dropColumn(['steam_lobby_link', 'steam_lobby_link_updated_at']);
    });
}
```

---

## Next Steps (Phase 4 Remaining Tasks)

### Task 4.2: Lobby API Endpoints
- POST `/api/profile/lobby` - Set/update lobby link
- DELETE `/api/profile/lobby` - Clear lobby link
- GET `/api/matchmaking/lobbies` - List active lobbies for game

### Task 4.3: Frontend UI
- "Share Lobby" button on profile/matchmaking page
- Modal for lobby link input with validation
- "Join Lobby" buttons on team member profiles
- Lobby expiration countdown timer
- Auto-refresh lobby list

### Task 4.4: Background Cleanup
- Scheduled task to clear expired lobbies
- Queue job for bulk cleanup
- Notification to users when lobby expires

---

## Summary

✅ **Database Schema:** Two columns added to `profiles` table
✅ **Model Enhancement:** 6 comprehensive helper methods implemented
✅ **Security:** URL validation with strict regex pattern
✅ **Performance:** Optimized for user-specific queries, no unnecessary indexes
✅ **Maintainability:** Constant-based expiration, comprehensive PHPDoc
✅ **Testing:** Clear test scenarios defined for unit and feature tests

**Migration Status:** Successfully applied
**Backward Compatibility:** Fully maintained (nullable columns)
**Production Ready:** Yes, with comprehensive error handling and validation

---

**Implementation Date:** 2025-10-12
**Developer:** Syameer Anwari
**Laravel Version:** 11.x
**Database:** MySQL
