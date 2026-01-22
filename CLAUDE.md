# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## CRITICAL COMMIT AUTHORSHIP REQUIREMENT

**MANDATORY: When pushing code to GitHub, ensure ONLY Syameer Anwari appears as the author.**

### **Authorship Rules:**
- **NO Claude co-authorship**: Never include `Co-Authored-By: Claude <noreply@anthropic.com>`
- **NO Claude Code references**: Never include `Generated with [Claude Code](https://claude.ai/code)`
- **ONLY Syameer Anwari**: All commits must show only `Syameer Anwari <syameer.anwari@gmail.com>` as author
- **Clean commit messages**: Simple, descriptive messages without AI attribution

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

## DEVELOPMENT STANDARDS

**ACT AS A SENIOR FULL-STACK DEVELOPER & UI/UX EXPERT**

Approach every task with expertise of 10+ years experience in software engineering and UI/UX design:

### **Core Principles**
- **Root Cause Analysis**: Don't fix symptoms - identify and resolve underlying issues
- **Production-Ready Code**: Write maintainable, well-documented, scalable code
- **Performance-First**: Consider optimization, efficiency, and N+1 prevention
- **Comprehensive Error Handling**: Graceful degradation with user-friendly feedback
- **Security & Validation**: Follow Laravel best practices, validate all inputs
- **UI/UX Excellence**: User-centered design, accessibility, modern patterns, responsive mobile-first

### **CRITICAL DEBUGGING PROTOCOL**

**If First Fix Fails -> IMMEDIATELY Add Debug Logging**

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

### **Common Laravel/MySQL Issues**
- **Ambiguous Columns**: Always qualify in joins (`teams.status` not `status`)
- **Collection Methods**: Use `->count() > 0` not `->exists()` on Collections
- **Route Cache**: Clear after route changes: `php artisan route:clear && php artisan route:cache`
- **Route Order**: Place static routes BEFORE dynamic routes with parameters
- **N+1 Queries**: Use eager loading and single-query patterns
- **Frontend-Backend**: Ensure AJAX forms match JSON API responses
- **Schema Alignment**: Verify model fillable fields match database columns

---

## PRODUCTION ENVIRONMENT

### **Server Infrastructure**
| Component | Version/Details |
|-----------|-----------------|
| **OS** | Ubuntu 22.04 LTS |
| **PHP** | 8.2.29 (CLI) |
| **MySQL** | 8.0.44 |
| **Web Server** | Nginx |
| **Process Manager** | Supervisor |
| **Node.js** | Required for Vite build |
| **Domain** | playglyph.me |

### **Server Paths**
- **Application Root**: `/var/www/glyph`
- **Public Directory**: `/var/www/glyph/public`
- **PHP-FPM Socket**: `/var/run/php/php8.2-fpm.sock`
- **Supervisor Logs**: `/var/log/supervisor/`
- **Laravel Logs**: `/var/www/glyph/storage/logs/`

### **Running Services (Supervisor)**
```bash
# Check service status
sudo supervisorctl status

# Services managed:
# - glyph:glyph-reverb    - WebSocket server (port 8080)
# - glyph:glyph-queue_00  - Queue worker 1
# - glyph:glyph-queue_01  - Queue worker 2

# Restart all services
sudo supervisorctl restart glyph:*

# View logs
sudo supervisorctl tail -f glyph:glyph-reverb
sudo tail -f /var/log/supervisor/queue.log
```

### **Nginx Configuration**
- **Config Location**: `/etc/nginx/sites-available/glyph`
- **WebSocket Proxy**: `/app` -> `http://127.0.0.1:8080`
- **Max Upload**: 10MB (for avatars, server icons)
- **PHP Timeout**: 300 seconds
- **Gzip**: Enabled for CSS, JS, JSON, XML

---

## APPLICATION OVERVIEW

**Glyph** is a Laravel-based gaming community platform with Steam integration, real-time chat, and Discord-inspired UI.

### **Essential Commands**
```bash
# Development (local)
composer run dev                          # Server, queue, logs, vite, reverb

# Production deployment
./deploy.sh                               # Pull, composer, migrate, cache, restart

# Cache management (run after .env or route changes)
php artisan config:clear && php artisan config:cache
php artisan route:clear && php artisan route:cache
php artisan view:clear && php artisan view:cache

# Database
php artisan migrate --force               # Production migrations
php artisan db:seed

# Assets (production)
npm run build                             # Production build

# Background services
php artisan queue:work --tries=3 --timeout=60
php artisan reverb:start --host=0.0.0.0 --port=8080

# Scheduled tasks (run hourly via cron or supervisor)
php artisan teams:expire-invitations      # Expire old team invitations

# Maintenance commands
php artisan permissions:clear-cache       # Clear all permission caches
php artisan permissions:clear-cache --server=5  # Clear specific server
php artisan skills:recalculate            # Recalculate user skill levels
php artisan app:fetch-steam-data          # Refresh Steam data for all users

# Testing
php artisan mail:test user@example.com    # Test email delivery
php artisan mail:test-production user@example.com --with-otp  # Test OTP email

# Telegram bot
php artisan telegram:set-menu             # Register bot commands
php artisan telegram:poll                 # Poll for updates (dev only)
```

---

## HIGH-LEVEL ARCHITECTURE

### **Core Domain**
- **Servers**: Gaming communities with invite-based membership
- **Channels**: Text/voice communication within servers
- **Roles**: Server-scoped permission hierarchy (position-based)
- **Messages**: Real-time chat with edit/delete (Laravel Reverb WebSocket)
- **Steam Integration**: Profile sync, achievements, "currently playing" status
- **Voice Chat**: Agora.io WebRTC integration for real-time voice communication
- **Direct Messages**: Private 1-on-1 messaging between friends
- **Game Lobbies**: Multi-game lobby system with various join methods
- **Teams**: Competitive team formation with matchmaking
- **Goals**: Community goals with milestone tracking

### **Tech Stack**
- **Backend**: Laravel 12, PHP 8.2, MySQL 8.0, Eloquent ORM
- **Real-Time**: Laravel Reverb (WebSocket), Laravel Echo, Pusher.js
- **Voice Chat**: Agora.io WebRTC SDK for voice channels
- **Frontend**: Blade templates, Alpine.js, vanilla JavaScript, Tailwind CSS 4.0
- **Authentication**: Dual (Custom OTP email + Steam OpenID)
- **Email**: SendGrid API (production), Gmail SMTP (development)
- **Process Manager**: Supervisor (production)
- **External APIs**: Steam API, Telegram Bot API, Agora.io

### **Database Patterns**
- **Rich Pivot Tables**: `server_members` (is_banned, is_muted), `user_roles` (server-scoped)
- **Cascade Relationships**: Foreign keys with automatic cleanup
- **Performance Indexes**: Messages, server_members, user_roles, conversations, direct_messages
- **JSON Storage**: Steam data, role permissions, Telegram settings
- **Encrypted Fields**: Lobby passwords (server_password, match_password)

---

## KEY MODELS & RELATIONSHIPS

```php
// Core entities (30 models)
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
Team → TeamInvitation (inviter_id, status, expires_at)
Team → TeamJoinRequest (user_id, status, message, responded_by)
User → MatchmakingRequest (intelligent queue)
User → PlayerGameRole (gaming role preferences)
Server → ServerGoal (community goals)
Goal → GoalMilestone (milestone checkpoints)
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

// Configuration & Analytics
MatchmakingConfiguration (admin-configurable weights)
MatchmakingAnalytics (algorithm performance tracking)
TelegramNotificationLog (audit trail)

// User Status & Privacy
User → UserStatus (online, away, dnd, invisible)
Channel → ChannelPermissionOverride (per-channel permissions)

// Permission hierarchy
Server Creator > Server Admin Role > Custom Roles > Member Role
Team Creator > Team Co-Leader > Team Member
```

---

## KEY SERVICE CLASSES

| Service | Purpose | Location |
|---------|---------|----------|
| `AgoraService` | Voice chat token generation, session management | `app/Services/AgoraService.php` |
| `GameLobbyService` | Lobby CRUD, validation, join methods | `app/Services/GameLobbyService.php` |
| `GamingSessionService` | Gaming session tracking | `app/Services/GamingSessionService.php` |
| `LobbyStatusService` | User lobby status across app | `app/Services/LobbyStatusService.php` |
| `MatchmakingService` | Team matching algorithm (1400+ lines) | `app/Services/MatchmakingService.php` |
| `PermissionService` | Role permission checks, hierarchy validation | `app/Services/PermissionService.php` |
| `ServerGoalService` | Community goals management | `app/Services/ServerGoalService.php` |
| `ServerRecommendationService` | Server discovery algorithm | `app/Services/ServerRecommendationService.php` |
| `SkillCalculationService` | Skill level calculation from playtime | `app/Services/SkillCalculationService.php` |
| `SteamApiService` | Steam API integration, data caching | `app/Services/SteamApiService.php` |
| `TeamInvitationService` | Team invitation lifecycle | `app/Services/TeamInvitationService.php` |
| `TeamRoleService` | Team role assignment | `app/Services/TeamRoleService.php` |
| `TeamService` | Team management operations | `app/Services/TeamService.php` |
| `TelegramBotService` | Telegram notifications & commands | `app/Services/TelegramBotService.php` |

---

## ARTISAN COMMANDS

| Command | Description |
|---------|-------------|
| `permissions:clear-cache` | Clear permission caches (supports `--server=ID`) |
| `steam:clear-id {user_id}` | Clear Steam ID from user (testing) |
| `teams:expire-invitations` | Expire old team invitations (scheduled hourly) |
| `app:fetch-steam-data` | Fetch Steam data for users (supports `--user=ID`) |
| `skills:recalculate` | Recalculate skill levels (supports `--user=ID`) |
| `telegram:poll` | Poll Telegram for updates (development) |
| `telegram:set-menu` | Register Telegram bot commands |
| `mail:test {email}` | Test email delivery (supports `--mailer=`) |
| `mail:test-production {email}` | Production email test with OTP |
| `test:lobby` | Test lobby functionality |
| `test:phase1` | Test Phase 1 recommendations |

---

## BROADCASTING EVENTS

### **Server Chat Events**
- `MessagePosted` - New message in channel
- `MessageEdited` - Message edited
- `MessageDeleted` - Message deleted

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
- `TeamCreated` - New team created
- `TeamMemberJoined` - User joined team
- `TeamMemberLeft` - User left team
- `TeamStatusChanged` - Team status update
- `TeamInvitationSent` - Invitation sent
- `TeamInvitationAccepted` - Invitation accepted
- `TeamInvitationDeclined` - Invitation declined
- `TeamInvitationCancelled` - Invitation cancelled
- `MatchmakingRequestCreated` - Queue entry
- `MatchmakingRequestCancelled` - Queue exit
- `MatchFound` - Match found

### **Gaming Status Events**
- `UserStartedPlaying` - User launched game
- `UserStoppedPlaying` - User closed game
- `UserChangedGame` - User switched games
- `UserGameStatusChanged` - In-game status update
- `UserStatusUpdated` - Online status change

### **Goal Events**
- `GoalCreated` - New goal created
- `GoalProgressUpdated` - Goal progress changed
- `GoalMilestoneReached` - Milestone achieved
- `GoalCompleted` - Goal finished
- `UserJoinedGoal` - User joined goal

---

## KEY FRONTEND FILES

### **JavaScript (18 files, ~330KB)**

| File | Size | Purpose |
|------|------|---------|
| `voice-chat.js` | 67KB | Agora.io WebRTC voice chat core |
| `live-matchmaking.js` | 43KB | Real-time team recommendations |
| `lobby-page.js` | 27KB | Lobby creation/browsing page |
| `lobby-manager.js` | 20KB | Profile lobby widget |
| `team-role-assignment.js` | 19KB | Team role assignment UI |
| `realtime-phase3.js` | 17KB | Team/goal real-time events |
| `gaming-status.js` | 9.5KB | Gaming status sync |
| `voice-sidebar.js` | 8.4KB | Voice sidebar real-time |
| `voice-view.js` | 8KB | Full-screen voice view |
| `bootstrap.js` | 5.6KB | Echo/WebSocket initialization with retry |
| `theme-switcher.js` | 5.3KB | Dark/light theme |
| `navbar.js` | 6KB | Navigation and mobile drawer |
| `app.js` | 1.2KB | Entry point, module orchestrator |
| `echo.js` | 458B | Legacy (unused) |

### **Utility Files**
- `utils/clipboard.js` - Cross-browser clipboard operations
- `utils/toast.js` - Global toast notifications
- `components/countdown-timer.js` - Lobby expiration countdown
- `components/lobby-join-button.js` - Reusable lobby join component

### **CSS**
- `resources/css/app.css` - Main stylesheet
- `resources/css/sidebar.css` - Server sidebar styling
- `resources/css/voice-view.css` - Voice channel view styling

---

## CRITICAL IMPLEMENTATION PATTERNS

### **Server Administration**
- **Tab Persistence**: Session-based (not URL fragments)
- **Access Control**: Banned/muted users blocked from actions
- **Default Roles**: "Server Admin" and "Member" protected from deletion
- **Role Hierarchy**: Position-based (higher position = more authority)

### **Message System**
- **Edit Tracking**: `is_edited` flag with `edited_at` timestamp
- **Permission Methods**: `canEdit()`, `canDelete()` on Message model
- **Real-Time Sync**: All users see edits/deletions via WebSocket
- **Pinned Messages**: `is_pinned`, `pinned_at`, `pinned_by` fields

### **Steam Integration**
- **Service Layer**: `SteamApiService` for centralized API calls
- **Caching**: 5-15 minute cache for Steam API data
- **Data Structure**: JSON `steam_data` field with profile, games, achievements
- **Supported Games**: CS2 (730), Deep Rock Galactic (548430), GTFO (493520)
- **Rate Limiting**: Respect Steam API limits with delays

### **Voice Chat System (Agora.io WebRTC)**
- **Service Layer**: `AgoraService` for token generation and session management
- **Token Generation**: RTC tokens with configurable expiry (default 3600s)
- **Session Tracking**: `VoiceSession` model tracks join/leave with duration
- **Mute/Deafen States**: Server-side state tracking for UI sync
- **Orphan Cleanup**: Auto-end sessions after 24h
- **Activities**: Watch Together, Chess, Poker, Sketch, Trivia, Music
- **Configuration**: `config/services.agora` (app_id, app_certificate, token_expiry)
- **Frontend**: Full-screen Discord-style interface with participant grid

### **Direct Messaging System**
- **Canonical Ordering**: Conversations use min/max user IDs to prevent duplicates
- **Friendship Requirement**: Can only DM accepted friends (`canDirectMessage()`)
- **Read Receipts**: `read_at` timestamp on messages
- **Edit/Delete**: Same permissions as server messages (own messages only)
- **Typing Indicators**: `UserTypingDM` event with debounce
- **Infinite Scroll**: Paginated message loading (50 per page)

### **Multi-Game Lobby System**
- **Service Layer**: `GameLobbyService` for CRUD and validation
- **Game ID Architecture**: Uses Steam App IDs directly (no games table)
- **Join Methods**: `steam_lobby`, `steam_connect`, `lobby_code`, `server_address`, `join_command`, `private_match`
- **Expiration**: Configurable per join method via `GameJoinConfiguration`
- **Password Encryption**: `server_password` and `match_password` use Laravel encryption
- **Duplicate Prevention**: One active lobby per user per game

### **Team System**
- **Recruitment Status**: `open` (direct join), `closed` (request only), `invite_only`
- **Request Flow**: User requests -> Leader approves/rejects -> User added
- **Invitation System**: Time-limited invitations with accept/decline/cancel
- **Server Optional**: Teams can exist without server association
- **Auto-Lock Size**: Team size locked based on game selection
- **Skill Levels**: beginner, intermediate, advanced, expert, unranked

### **WebSocket Connection Management**
- **Retry Logic**: 3 attempts with 2-second delays in `bootstrap.js`
- **Connection States**: connected, disconnected, unavailable, failed
- **Custom Events**: `echo:connected`, `echo:disconnected`, `echo:unavailable`, `echo:failed`
- **Graceful Degradation**: Falls back to polling when WebSocket unavailable
- **Private Channels**: Per server/channel combination for security

---

## MATCHMAKING ALGORITHM

**Implementation**: `app/Services/MatchmakingService.php` (1400+ lines)
**Documentation**: `docs/MATCHMAKING_ALGORITHM_EXPLANATION.md`
**Core Formula**: `Match Score = Sum(weight_i * normalized_score_i)`

### **Six Criteria (Weights)**
1. **Skill Level (40%)** - Categorical with non-linear penalties
2. **Team Composition (25%)** - Jaccard similarity for role overlap
3. **Region (15%)** - Geographic proximity matrix
4. **Schedule (10%)** - Jaccard similarity on time slots
5. **Team Size (5%)** - Optimal range 40-60% capacity
6. **Language (5%)** - Set intersection with English fallback

### **Important Rules**
- All criterion methods MUST return [0, 1] normalized scores
- Weights MUST sum to 1.0 (validated in code)
- Use eager loading to prevent N+1 queries
- Minimum threshold: 50% compatibility to show team

---

## CONFIGURATION FILES

### **Key Config Files**
| File | Purpose |
|------|---------|
| `config/services.php` | Third-party services (Steam, Agora, Telegram, SendGrid) |
| `config/broadcasting.php` | WebSocket/Reverb configuration |
| `config/database.php` | MySQL connection settings |
| `config/game_roles.php` | Game-specific role definitions (10+ games) |
| `config/permissions.php` | Server permission system |
| `config/teams.php` | Team invitation settings |
| `config/reverb.php` | WebSocket server configuration |

### **Production Environment Variables**
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://playglyph.me
APP_TIMEZONE=Asia/Kuala_Lumpur

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=glyph
DB_USERNAME=glyph_user

# Session & Cache
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# WebSocket (Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=glyph_production
REVERB_HOST=playglyph.me
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080

# Email (SendGrid)
MAIL_MAILER=sendgrid
SENDGRID_API_KEY=<api_key>
MAIL_FROM_ADDRESS=noreply@playglyph.me

# Steam
STEAM_API_KEY=<api_key>
STEAM_CALLBACK_URL=${APP_URL}/auth/steam/callback

# Agora Voice Chat
AGORA_APP_ID=<app_id>
AGORA_APP_CERTIFICATE=<certificate>
AGORA_TOKEN_EXPIRY=3600

# Telegram (Optional)
TELEGRAM_BOT_TOKEN=<bot_token>

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error
```

---

## PRODUCTION DEPLOYMENT

### **Deployment Script** (`deploy.sh`)
```bash
#!/bin/bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
sudo supervisorctl restart glyph:*
```

### **Nginx Configuration** (`nginx-production.conf`)
- WebSocket proxy: `/app` -> internal port 8080
- PHP-FPM via Unix socket
- Security headers (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection)
- Gzip compression enabled
- Static asset caching (1 month)
- Sensitive file protection (.env, composer.json)

### **Supervisor Configuration** (`supervisor-production.conf`)
- `glyph-reverb`: WebSocket server on port 8080
- `glyph-queue`: 2 queue workers with auto-restart
- Log rotation: 10MB max, 3 backups

---

## SECURITY & VALIDATION

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
- **Sensitive File Protection**: Nginx blocks access to .env, composer files

---

## POLICIES

| Policy | Purpose |
|--------|---------|
| `ServerPolicy` | Server access, management permissions |
| `ConversationPolicy` | DM conversation access |
| `DirectMessagePolicy` | DM message edit/delete permissions |
| `GameLobbyPolicy` | Lobby ownership and access |

---

## QUALITY CHECKLIST

Before marking work complete:
- Root cause fixed (not just symptoms)
- Similar issues fixed proactively across codebase
- Error handling with user-friendly feedback
- N+1 queries prevented with eager loading
- Database indexes on new query columns
- Real-time features tested with multiple browser windows
- Mobile responsive and cross-browser compatible
- Caches cleared after route/config changes
- Security validation and permission checks
- Production-ready code quality
- Voice sessions properly cleaned up on disconnect
- DM friendship checks enforced
- Lobby expiration handled correctly
- Broadcasting events fire for real-time updates

---

## DEVELOPMENT WORKFLOW

1. **Analyze**: Understand problem comprehensively before coding
2. **Plan**: Consider architecture, patterns, edge cases
3. **Implement**: Write production-ready code with error handling
4. **Debug**: Use logging and evidence-based fixes (not guessing)
5. **Test**: End-to-end workflows, cross-browser, mobile compatibility
6. **Optimize**: Profile queries, check N+1, verify indexes
7. **Document**: Complex implementations and architectural decisions
8. **Commit**: Clean, atomic commits with descriptive messages

---

## TROUBLESHOOTING

### **WebSocket Issues**
```bash
# Check Reverb status
sudo supervisorctl status glyph:glyph-reverb
sudo tail -f /var/log/supervisor/reverb.log

# Restart Reverb
sudo supervisorctl restart glyph:glyph-reverb
```

### **Queue Issues**
```bash
# Check queue status
sudo supervisorctl status glyph:glyph-queue_00 glyph:glyph-queue_01

# Restart queue workers
php artisan queue:restart
sudo supervisorctl restart glyph:glyph-queue_00 glyph:glyph-queue_01

# Check failed jobs
php artisan queue:failed
```

### **Cache Issues**
```bash
# Full cache clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### **Permission Issues**
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

**Remember**: Professional-grade quality, thorough analysis, evidence-based debugging, and production-ready implementation standards.
