# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 🚨 **CRITICAL COMMIT AUTHORSHIP REQUIREMENT**

**MANDATORY: When pushing code to GitHub, ensure ONLY Syameer Anwari appears as the author.**

### **Authorship Rules:**
- ❌ **NO Claude co-authorship**: Never include `Co-Authored-By: Claude <noreply@anthropic.com>`
- ❌ **NO Claude Code references**: Never include `🤖 Generated with [Claude Code](https://claude.ai/code)`
- ✅ **ONLY Syameer Anwari**: All commits must show only `Syameer Anwari <syameer.anwari@gmail.com>` as author
- ✅ **Clean commit messages**: Simple, descriptive messages without AI attribution

### **Before Every Push:**
1. Check commit messages: `git log --format="fuller" -3`
2. Verify no Claude references: `git log --grep="Claude" --oneline`
3. Ensure clean authorship: Only Syameer Anwari should appear as author

### **If Claude Appears:**
```bash
# Remove Claude co-authorship from commit message
git commit --amend -m "Your clean commit message here"

# For multiple commits, use interactive rebase
git rebase -i HEAD~N

# Force push to update remote
git push --force-with-lease origin branch-name
```

**REMEMBER:** The repository should show professional, single-author commits for presentation purposes.

---

## 🎯 **DEVELOPMENT STANDARDS**

**ACT AS A SENIOR FULL-STACK DEVELOPER & UI/UX EXPERT**

Approach every task with expertise of 10+ years experience in software engineering and UI/UX design:

### **Core Principles**
- **Root Cause Analysis**: Don't fix symptoms - identify and resolve underlying issues
- **Production-Ready Code**: Write maintainable, well-documented, scalable code
- **Performance-First**: Consider optimization, efficiency, and N+1 prevention
- **Comprehensive Error Handling**: Graceful degradation with user-friendly feedback
- **Security & Validation**: Follow Laravel best practices, validate all inputs
- **UI/UX Excellence**: User-centered design, accessibility, modern patterns, responsive mobile-first

### **🔍 CRITICAL DEBUGGING PROTOCOL**

**If First Fix Fails → IMMEDIATELY Add Debug Logging**

When a fix doesn't work or error persists:

1. **STOP Guessing** - Never attempt multiple blind fixes
2. **Add Debug Logging**:
   ```php
   \Log::info('Request data:', $request->all());
   \Log::error('Validation errors:', $validator->errors()->toArray());
   ```
   ```javascript
   console.log('Data being sent:', dataObject);
   console.error('Full error response:', error);
   ```
3. **Request Exact Error Messages**: Browser console, Laravel logs, network tab
4. **Fix Based on Evidence**: Target precise issue revealed by debugging
5. **Apply Pattern Recognition**: Fix all similar instances proactively

### **🛠️ Common Laravel/MySQL Issues**
- **Ambiguous Columns**: Always qualify in joins (`teams.status` not `status`)
- **Collection Methods**: Use `->count() > 0` not `->exists()` on Collections
- **Route Cache**: Clear after route changes: `php artisan route:clear && php artisan route:cache`
- **Route Order**: Place static routes BEFORE dynamic routes with parameters
- **N+1 Queries**: Use eager loading and single-query patterns
- **Frontend-Backend**: Ensure AJAX forms match JSON API responses
- **Schema Alignment**: Verify model fillable fields match database columns

---

## 📦 **APPLICATION OVERVIEW**

**Glyph** is a Laravel-based gaming community platform with Steam integration, real-time chat, and Discord-inspired UI.

### **Essential Commands**
```bash
# Start development
composer run dev                          # Server, queue, logs, vite, reverb

# Cache management (run after .env or route changes)
php artisan config:clear
php artisan route:clear && php artisan route:cache
php artisan view:clear

# Database
php artisan migrate
php artisan db:seed

# Assets
npm run dev                               # Hot reload
npm run build                             # Production

# Background services
php artisan queue:work
php artisan reverb:start                  # WebSocket for real-time

# Team invitation management (scheduled task - runs hourly)
php artisan teams:expire-invitations      # Expire old team invitations

# Lobby link management (manual)
php artisan lobby:clear-expired           # Clear expired Steam lobby links

# Permission cache management
php artisan permissions:clear-cache       # Clear all permission caches
php artisan permissions:clear-cache --server=5  # Clear specific server
```

---

## 🏗️ **HIGH-LEVEL ARCHITECTURE**

### **Core Domain**
- **Servers**: Gaming communities with invite-based membership
- **Channels**: Text/voice communication within servers
- **Roles**: Server-scoped permission hierarchy (position-based)
- **Messages**: Real-time chat with edit/delete (Laravel Reverb WebSocket)
- **Steam Integration**: Profile sync, achievements, "currently playing" status
- **Voice Chat**: Agora.io WebRTC integration for real-time voice communication
- **Direct Messages**: Private 1-on-1 messaging between friends
- **Game Lobbies**: Multi-game lobby system with various join methods

### **Tech Stack**
- **Backend**: Laravel 12, MySQL, Eloquent ORM
- **Real-Time**: Laravel Reverb (WebSocket), Laravel Echo, Pusher.js
- **Voice Chat**: Agora.io WebRTC SDK for voice channels
- **Frontend**: Blade templates, vanilla JavaScript, Tailwind CSS 4.0
- **Authentication**: Dual (Custom OTP email + Steam OpenID)
- **Email**: Gmail SMTP (`syameer.anwari@gmail.com` with App Password)
- **Environment**: Laragon (Windows), MySQL database

### **Database Patterns**
- **Rich Pivot Tables**: `server_members` (is_banned, is_muted), `user_roles` (server-scoped)
- **Cascade Relationships**: Foreign keys with automatic cleanup
- **Performance Indexes**: Messages, server_members, user_roles, conversations, direct_messages
- **JSON Storage**: Steam data, role permissions
- **Encrypted Fields**: Lobby passwords (server_password, match_password)

---

## 🔗 **KEY MODELS & RELATIONSHIPS**

```php
// Core entities
User ←→ Server (server_members: is_banned, is_muted, joined_at)
User ←→ Role (user_roles: server_id scope)
User ↔ User (friends: status)
Server → Channel → Message
User → Profile (Steam integration)

// Enhanced features (Phase 1-3)
User → UserGamingPreference (Steam games, playtime, skill levels)
Server → ServerTag (game, skill_level, region, language, activity_time)
User → GamingSession (session tracking)
User ←→ Team (team_members: role, skill_level, status)
User → MatchmakingRequest (intelligent queue)
User → PlayerGameRole (gaming role preferences)
Server → ServerGoal (community goals)
User → GoalParticipant (goal progress)
Server → AchievementLeaderboard (rankings)

// Voice Chat (Agora.io WebRTC)
User → VoiceSession (channel_id, server_id, is_muted, is_deafened, session_duration)
Channel → VoiceSession (active participants)

// Direct Messaging
User ←→ User (conversations: user_one_id, user_two_id, canonical ordering)
Conversation → DirectMessage (sender_id, content, is_edited, read_at)

// Multi-Game Lobby System
User → GameLobby (game_id, join_method, expires_at)
GameLobby → GameJoinConfiguration (join methods per game)

// Team Enhancements
Team → TeamJoinRequest (user_id, status, message, responded_by)

// Permission hierarchy
Server Creator > Server Admin Role > Custom Roles > Member Role
Team Creator > Team Co-Leader > Team Member
```

---

## 🎨 **CRITICAL IMPLEMENTATION PATTERNS**

### **Server Administration**
- **Tab Persistence**: Session-based (not URL fragments)
- **Access Control**: Banned/muted users blocked from actions
- **Default Roles**: "Server Admin" and "Member" protected from deletion
- **Role Hierarchy**: Position-based (higher position = more authority)

### **Message System**
- **Edit Tracking**: `is_edited` flag with `edited_at` timestamp
- **Permission Methods**: `canEdit()`, `canDelete()` on Message model
- **Real-Time Sync**: All users see edits/deletions via WebSocket
- **Broadcasting Events**: `MessagePosted`, `MessageEdited`, `MessageDeleted`

### **Steam Integration**
- **Service Layer**: `SteamApiService` for centralized API calls
- **Caching**: 5-15 minute cache for Steam API data
- **Data Structure**: JSON `steam_data` field with profile, games, achievements
- **Supported Games**: CS2, Deep Rock Galactic, GTFO (lobby system)
- **Rate Limiting**: Respect Steam API limits with delays

### **Route Management**
- **Static First**: Place `/servers/create` BEFORE `/servers/{server}`
- **Import Controllers**: At top of routes file (not full namespaces)
- **Cache After Changes**: `route:clear && route:cache`

### **Performance**
- **Eager Loading**: Load relationships to prevent N+1
- **Database Indexes**: On frequently queried columns
- **Cache Strategy**: Balance freshness with API limits
- **Query Optimization**: Profile and optimize complex queries

### **Real-Time Features**
- **Private Channels**: Per server/channel combination
- **Error Handling**: Comprehensive WebSocket error logging
- **Auto-Reconnection**: Frontend connection management
- **Consistent Formatting**: Standardized event data structure

### **Voice Chat System (Agora.io WebRTC)**
- **Service Layer**: `AgoraService` for token generation and session management
- **Token Generation**: RTC tokens with configurable expiry (default 3600s)
- **Session Tracking**: `VoiceSession` model tracks join/leave with duration
- **Mute/Deafen States**: Server-side state tracking for UI sync
- **Orphan Cleanup**: Auto-end sessions after 24h (stale session cleanup)
- **Broadcasting Events**: `VoiceUserJoined`, `VoiceUserLeft`, `VoiceUserMuted`, `VoiceUserSpeaking`, `VoiceUserDeafened`
- **Voice Channel View**: Full-screen Discord-style interface with participant grid
- **Activities**: Watch Together, Chess, Poker, Sketch, Trivia, Music (static list)
- **Configuration**: `config/services.agora` (app_id, app_certificate, token_expiry)

### **Direct Messaging System**
- **Canonical Ordering**: Conversations use min/max user IDs to prevent duplicates
- **Friendship Requirement**: Can only DM accepted friends (`canDirectMessage()` check)
- **Read Receipts**: `read_at` timestamp on messages, `DirectMessageRead` event
- **Edit/Delete**: Same permissions as server messages (only own messages)
- **Typing Indicators**: `UserTypingDM` event with debounce
- **Caching**: 5-minute conversation list cache per user
- **Infinite Scroll**: Paginated message loading (50 per page)
- **Broadcasting Events**: `DirectMessagePosted`, `DirectMessageEdited`, `DirectMessageDeleted`, `DirectMessageRead`, `UserTypingDM`

### **Multi-Game Lobby System**
- **Service Layer**: `GameLobbyService` for CRUD and validation
- **Game ID Architecture**: Uses Steam App IDs directly (no games table)
- **Flexible Creation**: Users can create lobbies for any game (no ownership required)
- **Join Methods**: `steam_lobby`, `steam_connect`, `lobby_code`, `server_address`, `join_command`, `private_match`
- **Expiration**: Configurable per join method via `GameJoinConfiguration`
- **Password Encryption**: `server_password` and `match_password` use Laravel encryption
- **Duplicate Prevention**: One active lobby per user per game (auto-deletes old)
- **Broadcasting Events**: `LobbyCreated`, `LobbyDeleted`, `LobbyExpired`, `UserLobbyUpdated`
- **Supported Games**: CS2 (730), Deep Rock Galactic (548430), GTFO (493520)

### **Team Join Request System**
- **Recruitment Status**: `open` (direct join), `closed` (request only), `invite_only`
- **Request Flow**: User requests → Leader approves/rejects → User added
- **Server Optional**: Teams can exist without server association
- **Bypass Check**: Approved requests bypass recruitment status check
- **Auto-Lock Size**: Team size locked based on game selection

### **Matchmaking Algorithm**
- **Implementation**: `app/Services/MatchmakingService.php` (lines 461-1901)
- **Documentation**: `docs/MATCHMAKING_ALGORITHM_EXPLANATION.md` (full technical details)
- **Core Formula**: `Match Score = Σ(weight_i × normalized_score_i)`

**Research-Backed Approach:**
- Based on Awesomenauts Algorithm, TrueSkill 2, Jaccard Similarity, Multi-Criteria Decision Making
- All changes MUST maintain normalization ([0, 1] range before weighting)
- Weights MUST sum to 1.0 (validated in code)

**Six Criteria (Weights):**
1. **Skill Level (40%)** - Categorical with non-linear penalties
   - Converts: beginner=1, intermediate=2, advanced=3, expert=4
   - 2+ level gap: 50% penalty multiplier (INTER vs EXPERT = 17%, not 76%)
   - Method: `calculateSkillCompatibilityForTeam()` returns [0, 100], divide by 100

2. **Team Composition (25%)** - Jaccard similarity for role overlap
   - Perfect fill (all roles): 1.0, Partial fill: 0.70-0.95, No match: 0.30
   - Expert players (skill ≥70) auto-gain role flexibility
   - Method: `calculateRoleMatchForTeam()` returns [0, 1]

3. **Region (15%)** - Geographic proximity matrix
   - Same region: 1.0, Adjacent (NA-SA): 0.70, Far (NA-ASIA): 0.25
   - Server match prioritized over region
   - Method: `calculateRegionCompatibilityForTeam()` returns [0, 1]

4. **Schedule (10%)** - Jaccard similarity on time slots
   - Expands ranges: "evening" → [17,18,19,20,21] hours
   - Calculates intersection/union overlap
   - Method: `calculateActivityTimeMatch()` returns [0, 1]

5. **Team Size (5%)** - Optimal range 40-60% capacity
   - Too empty (<30%): unstable, Too full (>70%): hard to integrate
   - Method: `calculateTeamSizeScore()` returns [0, 1]

6. **Language (5%)** - Set intersection with English fallback
   - Common language: 0.50-1.0, English fallback: 0.60, No overlap: 0.30
   - Method: `calculateLanguageCompatibility()` returns [0, 1]

**Critical Helper Methods:**
- `calculateJaccardSimilarity(array $set1, array $set2)` - Standard set similarity [0, 1]
- `convertSkillLevelToNumeric(string $level)` - Categorical → numeric conversion
- `normalizeSkillScore(int $diff)` - Applies non-linear penalty for skill gaps
- `getMatchmakingWeights()` - Centralized weight configuration
- `validateMatchmakingWeights(array $weights)` - Ensures sum = 1.0

**Important Rules:**
1. **ALL criterion methods MUST return [0, 1] normalized scores** (except skill which returns [0, 100])
2. **Never modify weights without updating validation tests**
3. **Use eager loading**: `->with(['activeMembers.user.playerGameRoles'])`  to prevent N+1
4. **Minimum threshold**: 50% compatibility to show team (configurable)
5. **Logging**: Debug logging at each calculation step for troubleshooting

**Testing:**
- Unit tests: `tests/Unit/Services/MatchmakingService*.php`
- Integration: `tests/Integration/Matchmaking*.php`
- Performance: `tests/Performance/MatchmakingPerformanceTest.php`
- Regression: Validates 76% bug fix (INTER vs EXPERT must show <20% compatibility)

**Performance Targets:**
- <10ms per team calculation
- <100ms for 100 teams with caching
- No N+1 queries (verify with Laravel Telescope)

**Configuration (Phase 6 - Future):**
- Database-backed: `MatchmakingConfiguration` model
- Per-game weights possible (CS2 vs Dota 2)
- Admin dashboard for tuning
- A/B testing capability

---

## 🔒 **SECURITY & VALIDATION**

- **Membership Validation**: Check server membership on all actions
- **Ban/Mute Enforcement**: Block restricted users from actions
- **Permission Checks**: Users can only edit/delete own messages
- **Input Validation**: Comprehensive Laravel form requests
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **CSRF Protection**: Laravel middleware on all forms
- **Steam Validation**: OpenID verification against Steam community
- **DM Authorization**: Only friends can message each other
- **Conversation Participant Checks**: Every DM action verifies user is participant
- **Password Encryption**: Lobby passwords encrypted at rest
- **Rate Limiting**: Throttle on message sending, lobby updates

---

## 📁 **KEY FRONTEND FILES**

### **JavaScript**
- `resources/js/voice-chat.js` - Voice channel Agora.io WebRTC integration
- `resources/js/voice-view.js` - Full-screen voice channel view logic
- `resources/js/voice-sidebar.js` - Voice panel in server sidebar
- `resources/js/lobby-manager.js` - Multi-game lobby creation/management
- `resources/js/gaming-status.js` - Real-time gaming status updates
- `resources/js/live-matchmaking.js` - Live matchmaking queue updates
- `resources/js/team-skill-charts.js` - Team skill visualization
- `resources/js/realtime-phase3.js` - Phase 3 real-time features
- `resources/js/echo.js` - Laravel Echo/Reverb configuration

### **CSS**
- `resources/css/sidebar.css` - Server sidebar styling
- `resources/css/voice-view.css` - Voice channel view styling

### **Blade Components**
- `components/voice-channel-item.blade.php` - Voice channel in sidebar
- `components/voice-panel.blade.php` - Connected voice panel
- `components/voice-user-item.blade.php` - User in voice channel
- `components/lobby-manager.blade.php` - Lobby creation modal
- `components/lobby-join-button.blade.php` - Join lobby button
- `components/lobby-join-card.blade.php` - Lobby display card

---

## 📝 **DEVELOPMENT WORKFLOW**

1. **Analyze**: Understand problem comprehensively before coding
2. **Plan**: Consider architecture, patterns, edge cases
3. **Implement**: Write production-ready code with error handling
4. **Debug**: Use logging and evidence-based fixes (not guessing)
5. **Test**: End-to-end workflows, cross-browser, mobile compatibility
6. **Optimize**: Profile queries, check N+1, verify indexes
7. **Document**: Complex implementations and architectural decisions
8. **Commit**: Clean, atomic commits with descriptive messages

---

## 🎯 **QUALITY CHECKLIST**

Before marking work complete:
- ✅ Root cause fixed (not just symptoms)
- ✅ Similar issues fixed proactively across codebase
- ✅ Error handling with user-friendly feedback
- ✅ N+1 queries prevented with eager loading
- ✅ Database indexes on new query columns
- ✅ Real-time features tested with multiple browser windows
- ✅ Mobile responsive and cross-browser compatible
- ✅ Caches cleared after route/config changes
- ✅ Security validation and permission checks
- ✅ Production-ready code quality
- ✅ Voice sessions properly cleaned up on disconnect
- ✅ DM friendship checks enforced
- ✅ Lobby expiration handled correctly
- ✅ Broadcasting events fire for real-time updates

---

## 🗂️ **KEY SERVICE CLASSES**

| Service | Purpose | Location |
|---------|---------|----------|
| `AgoraService` | Voice chat token generation, session management | `app/Services/AgoraService.php` |
| `GameLobbyService` | Lobby CRUD, validation, join methods | `app/Services/GameLobbyService.php` |
| `LobbyStatusService` | User lobby status across app | `app/Services/LobbyStatusService.php` |
| `MatchmakingService` | Team matching algorithm | `app/Services/MatchmakingService.php` |
| `PermissionService` | Role permission checks, hierarchy validation | `app/Services/PermissionService.php` |
| `TeamService` | Team management operations | `app/Services/TeamService.php` |
| `SteamApiService` | Steam API integration | `app/Services/SteamApiService.php` |
| `ServerGoalService` | Community goals management | `app/Services/ServerGoalService.php` |
| `ServerRecommendationService` | Server discovery algorithm | `app/Services/ServerRecommendationService.php` |
| `TelegramBotService` | Telegram notifications | `app/Services/TelegramBotService.php` |

---

## 📡 **BROADCASTING EVENTS**

### **Voice Chat Events**
- `VoiceUserJoined` - User joined voice channel
- `VoiceUserLeft` - User left voice channel
- `VoiceUserMuted` - User muted/unmuted
- `VoiceUserDeafened` - User deafened/undeafened
- `VoiceUserSpeaking` - User speaking indicator

### **Direct Message Events**
- `DirectMessagePosted` - New DM sent
- `DirectMessageEdited` - DM edited
- `DirectMessageDeleted` - DM deleted
- `DirectMessageRead` - DM read receipt
- `UserTypingDM` - User typing indicator

### **Lobby Events**
- `LobbyCreated` - New lobby created
- `LobbyDeleted` - Lobby removed
- `LobbyExpired` - Lobby expired
- `UserLobbyUpdated` - User's lobby changed
- `UserLobbyCleared` - User cleared their lobby

### **Team/Matchmaking Events**
- `TeamCreated`, `TeamMemberJoined`, `TeamMemberLeft`, `TeamStatusChanged`
- `MatchmakingRequestCreated`, `MatchFound`

### **Server Chat Events**
- `MessagePosted`, `MessageEdited`, `MessageDeleted`

### **Goal Events**
- `GoalProgressUpdated`, `GoalMilestoneReached`, `GoalCompleted`, `UserJoinedGoal`

---

**Remember**: Professional-grade quality, thorough analysis, evidence-based debugging, and production-ready implementation standards.
