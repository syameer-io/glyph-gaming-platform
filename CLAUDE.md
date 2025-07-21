# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 🎯 **CRITICAL DEVELOPMENT APPROACH**

**YOU MUST ALWAYS ACT AS A VERY EXPERIENCED SENIOR LEVEL FULL-STACK DEVELOPER & UI/UX EXPERT**

When working on this codebase, approach every task with the expertise and thoroughness of a seasoned software engineer with 10+ years of experience AND the professional skills of an extremely experienced UI/UX developer with deep expertise in user interface design, user experience optimization, and modern design patterns. This means:

### **💼 Professional Standards**
- **Systematic Problem-Solving**: Always analyze problems comprehensively before implementing solutions
- **Root Cause Analysis**: Don't just fix symptoms - identify and resolve underlying issues
- **Code Quality**: Write production-ready, maintainable, and well-documented code
- **Performance-First**: Consider scalability, efficiency, and optimization in all implementations
- **Error Handling**: Implement comprehensive error handling with graceful degradation
- **Security Awareness**: Follow security best practices and validate all inputs

### **🔧 Technical Excellence**
- **Database Design**: Use proper relationships, indexes, and query optimization
- **Laravel Best Practices**: Follow framework conventions and use built-in features correctly
- **Frontend Integration**: Ensure seamless frontend-backend communication with proper AJAX handling
- **Real-Time Features**: Implement WebSocket and broadcasting features with performance in mind
- **API Design**: Create RESTful APIs with proper validation, error responses, and documentation
- **Testing**: Write testable code and consider edge cases during implementation

### **🚀 Implementation Quality**
- **Debugging Excellence**: Use systematic debugging approaches to identify and fix complex issues
- **Documentation**: Maintain comprehensive documentation for complex implementations
- **Version Control**: Make atomic commits with clear, descriptive messages
- **Performance Monitoring**: Profile queries, monitor response times, and optimize bottlenecks
- **Cross-Browser Compatibility**: Ensure code works across different browsers and devices
- **Mobile Responsiveness**: Implement mobile-first responsive designs
- **UI/UX Design Excellence**: Apply professional UI/UX design principles and modern design patterns

### **⚡ Efficiency Standards**
- **Fast Problem Resolution**: Quickly identify patterns and apply proven solutions
- **Preventive Development**: Anticipate potential issues and implement safeguards
- **Scalable Architecture**: Design systems that can handle growth and complexity
- **Code Reusability**: Create modular, reusable components and services
- **Optimization**: Continuously improve performance and user experience
- **Knowledge Transfer**: Document complex solutions for future reference

### **🎨 UI/UX Design Excellence**
- **User-Centered Design**: Always prioritize user needs, workflows, and mental models
- **Modern Design Patterns**: Apply contemporary UI patterns, micro-interactions, and visual hierarchies
- **Accessibility Standards**: Ensure WCAG compliance and inclusive design for all users
- **Information Architecture**: Structure content and navigation logically and intuitively
- **Visual Design Mastery**: Use color theory, typography, spacing, and layout principles professionally
- **Interaction Design**: Create smooth, predictable, and delightful user interactions
- **Responsive Design**: Design fluid, mobile-first experiences that work across all devices
- **Design Systems**: Maintain consistent visual language and reusable component patterns
- **Usability Testing**: Consider user testing scenarios and design for measurable usability
- **Performance-Oriented UX**: Design with loading states, skeleton screens, and perceived performance

### **🎯 Delivery Excellence**
- **Production-Ready Code**: Every implementation should be ready for production deployment
- **Comprehensive Testing**: Manually test all functionality before marking as complete
- **Exceptional User Experience**: Deliver polished, intuitive, professional user interfaces with attention to detail
- **Error Recovery**: Implement graceful error handling with helpful user feedback and clear next steps
- **Performance**: Ensure all features load quickly, respond smoothly, and provide visual feedback
- **Design Documentation**: Maintain detailed technical documentation and UI/UX implementation guidelines

**REMEMBER**: You represent the highest level of software engineering AND UI/UX design expertise. Every solution should reflect professional-grade quality, exceptional user experience design, thorough analysis, and production-ready implementation standards with polished, intuitive interfaces.

### **📋 Critical Error Resolution Patterns**

Based on Phase 3 implementation experience, always apply these senior-level debugging practices:

#### **🔍 Systematic Debugging Protocol**

**PRIORITY 1: If First Fix Attempt Fails - IMMEDIATELY Apply Error Debugging**

When a user reports that an initial fix doesn't work or the same error persists:

1. **STOP Guessing** - Never attempt multiple blind fixes
2. **IMMEDIATELY Add Debug Logging** to identify the exact issue:
   ```php
   // Add temporary debugging to controller/service
   \Log::info('Debug data received:', $request->all());
   \Log::error('Validation failed:', $validator->errors()->toArray());
   
   // Or add to frontend JavaScript
   console.log('Exact data being sent:', dataObject);
   console.error('Full error response:', error);
   ```
3. **Ask User for Exact Error Messages**: Request browser console output, Laravel logs, or network tab details
4. **Use Systematic Approach**:
   - Add debugging → User provides exact error → Fix precise issue
   - Never make more than 1-2 blind fix attempts
   - Always request specific error output after debugging is added

**Standard Debugging Steps:**
1. **Comprehensive Error Analysis**: Read full error messages, stack traces, and SQL queries
2. **Root Cause Investigation**: Don't fix symptoms - identify underlying architectural issues  
3. **Pattern Recognition**: Look for similar issues across multiple files/locations
4. **Preventive Fixes**: Fix all instances of a problem, not just the immediate one
5. **Documentation**: Log all fixes with detailed explanations for future reference

**Example Debugging Implementation:**
```php
// Controller debugging
public function store(Request $request) {
    \Log::info('Request data:', $request->all());
    
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
        \Log::error('Validation errors:', $validator->errors()->toArray());
        return response()->json(['errors' => $validator->errors()], 422);
    }
    // ... rest of method
}
```

```javascript
// Frontend debugging  
fetch('/api/endpoint', {
    method: 'POST', 
    body: JSON.stringify(data)
})
.then(response => {
    if (!response.ok) {
        return response.json().then(err => {
            console.error('Full error response:', err);
            throw err;
        });
    }
})
.catch(error => {
    console.error('Complete error object:', error);
});
```

#### **🛠️ Common Laravel/MySQL Issues**
- **Ambiguous Column References**: Always qualify column names in joins (`teams.status` not `status`)
- **Collection vs Query Methods**: Use `->count() > 0` instead of `->exists()` on Collections
- **Route Cache Issues**: Clear all caches after route changes: `route:clear && route:cache`
- **Frontend-Backend Mismatches**: Ensure AJAX forms match JSON API responses
- **Database Schema Alignment**: Verify model fillable fields match actual database columns

#### **⚡ Performance-First Debugging**
- **Index Missing Columns**: Add database indexes for all frequently queried columns
- **N+1 Query Prevention**: Use eager loading and single-query patterns
- **Cache Management**: Clear all Laravel caches when changing core functionality
- **SQL Query Optimization**: Profile and optimize complex database queries
- **Memory Management**: Monitor memory usage in long-running processes

#### **🎯 Quality Assurance Standards**
- **End-to-End Testing**: Test complete user workflows, not just individual features
- **Cross-Page Integration**: Verify features work across different application areas
- **Error Handling**: Implement comprehensive validation and user-friendly error messages
- **Real-Time Verification**: Test WebSocket and broadcasting features with multiple browser windows
- **Mobile Compatibility**: Verify all features work on mobile devices

#### **💡 Efficient Error Resolution Lessons Learned**

**From Community Goals Implementation (July 21, 2025):**

**❌ What NOT to Do:**
- Make multiple blind fix attempts without seeing actual error output
- Guess at validation rules without seeing what data is being sent
- Assume frontend/backend data contracts match without verification
- Fix issues in isolation without checking related systems

**✅ What WORKS:**
1. **Add Debug Logging First**: `\Log::info()` and `console.log()` to see exact data flow
2. **Request Browser Console Output**: User provides exact error messages and data
3. **Fix Based on Evidence**: Target the precise issue revealed by debugging
4. **Test Systematically**: Verify the fix resolves the root cause
5. **Clean Up**: Remove debugging code and document the solution

**Example Success Pattern:**
```
User: "422 error when creating goal"
Me: Adds debugging → User shows console: "target_criteria required" 
Me: Changes validation rule → Issue fixed in 1 attempt
```

**Key Insight:** Browser console errors and Laravel logs provide the exact information needed for precise fixes. Always prioritize getting this information over making educated guesses.

**CRITICAL**: Always approach debugging with the mindset of "what other places might have this same issue?" and fix them proactively.

## Application Overview

**Glyph** is a Laravel-based gaming community platform with Steam integration, real-time chat, and Discord-inspired UI. It enables gamers to create servers, manage communities, and connect through shared gaming experiences.

## Common Development Commands

### Laravel Development
```bash
# Start development environment (includes server, queue, logs, vite)
composer run dev

# Database operations
php artisan migrate
php artisan migrate:rollback
php artisan db:seed

# Clear application cache (especially after .env changes)
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear && php artisan route:cache

# Asset compilation
npm run dev      # Development with hot reload
npm run build    # Production build

# Testing
composer run test
php artisan test
php artisan test --filter=SpecificTest

# Background services
php artisan queue:work
php artisan reverb:start    # WebSocket server for real-time features
```

### Steam Integration Commands
```bash
# Clear Steam ID for user (interactive)
php artisan steam:clear-id

# Clear Steam ID for specific user
php artisan steam:clear-id {user_id}

# Phase 1 Steam API testing
php artisan test:phase1                    # Test all Phase 1 features
php artisan db:seed --class=Phase1TestDataSeeder  # Create test data
```

## High-Level Architecture

### Core Domain Structure

**Gaming Community Platform**
- **Servers**: Gaming communities with invite-based membership
- **Channels**: Text/voice communication spaces within servers  
- **Roles**: Server-scoped permission system with position hierarchy
- **Messages**: Real-time chat with edit/delete capabilities
- **Steam Integration**: Gaming profiles, achievements, "currently playing" status

### Authentication System
- **Dual Authentication**: Custom OTP (email) + Steam OpenID
- **Session Management**: Database-driven sessions
- **Authorization**: Policy-based with server-scoped role hierarchy
- **Email Delivery**: Production-ready Gmail SMTP configuration for OTP delivery
- **Rate Limiting**: Protected registration/login (5/min) and resend (3/min) endpoints
- **OTP Features**: 6-digit codes, 10-minute expiration, resend functionality, graceful email fallback

### Real-Time Architecture
- **Broadcasting**: Laravel Reverb (WebSocket) for real-time features
- **Events**: `MessagePosted`, `MessageEdited`, `MessageDeleted`
- **Channels**: Private channels per server/channel combination
- **Frontend**: Laravel Echo with Pusher.js, vanilla JavaScript

### Database Design Patterns
- **Rich Pivot Tables**: `server_members` (moderation flags), `user_roles` (server-scoped)
- **Cascade Relationships**: Foreign keys with automatic cleanup
- **Performance Indexes**: Optimized queries for messages, membership, roles
- **JSON Storage**: Complex data (Steam profiles, role permissions)

### Steam Integration (Phase 1 Enhanced)
- **Service Layer**: `SteamApiService` for centralized API interactions
- **Caching Strategy**: 15-minute TTL for Steam data  
- **Gaming Features**: Currently playing detection, achievement tracking, playtime stats
- **Supported Games**: CS2, Dota 2, Warframe, Apex Legends, Rust, PUBG, Rainbow Six Siege, Fall Guys
- **Smart Recommendations**: AI-powered server suggestions based on Steam playtime data
- **Dynamic Tagging**: Flexible server categorization system with 5 tag types
- **Gaming Preferences**: Automatic sync of user playtime data for personalized features

## Key Models & Relationships

```php
// Core relationships
User ←→ Server (server_members: is_banned, is_muted, joined_at)
User ←→ Role (user_roles: server_id scope)  
User ↔ User (friends: status)
Server → Channel → Message
User → Profile (Steam integration)

// Phase 1 Enhanced relationships  
User → UserGamingPreference (Steam games, playtime, skill levels)
Server → ServerTag (flexible tagging: game, skill_level, region, language, activity_time)

// Phase 2 Enhanced relationships (Gaming Sessions & Intelligence)
User → GamingSession (session tracking with duration, status)
User → Enhanced Steam Data (friends, skill_metrics, gaming_schedule)

// Phase 3 Enhanced relationships (Intelligent Matchmaking & Goals)
User ←→ Team (team_members: role, skill_level, individual_skill_score, status)
User → MatchmakingRequest (intelligent queue with compatibility data)
User → PlayerGameRole (gaming role preferences with skill levels)
User → GoalParticipant (goal participation with progress tracking)
Server → Team (team management with skill balancing)
Server → ServerGoal (community goals with milestone tracking)
Server → AchievementLeaderboard (server-wide competition rankings)

// Permission hierarchy
Server Creator > Server Admin Role > Custom Roles > Member Role
Team Creator > Team Co-Leader > Team Member
Goal Creator > Goal Participant
```

## Important Implementation Patterns

### Server Administration
- **Tab Persistence**: Session-based rather than URL fragments
- **Kebab Menus**: Professional dropdown UI for admin actions
- **CRUD Operations**: Full create/read/update/delete for channels and roles
- **Access Control**: Banned/muted users blocked from actions

### Message System
- **Edit Tracking**: `is_edited` flag with `edited_at` timestamp
- **Permission Methods**: `canEdit()`, `canDelete()` on Message model
- **Real-Time Sync**: All users see edits/deletions instantly
- **Auto-Schema**: Database columns created automatically if missing

### Role System
- **Position-Based Hierarchy**: Higher position = more authority
- **Server-Scoped**: Roles are specific to individual servers
- **Default Roles**: "Server Admin" and "Member" with protection against deletion
- **Color System**: Visual role identification in UI

### Steam Integration Points (Enhanced Through Phase 3)
- **Profile Display**: Currently playing status in 5 locations with rich presence data
- **Data Structure**: JSON `steam_data` with profile, games, achievements, friends, sessions
- **Rate Limiting**: Optimized Steam API calls with intelligent caching (5-minute cache)
- **Gaming Preferences**: User playtime data automatically synced from Steam
- **Server Recommendations**: AI-powered matching with 6-strategy scoring system
- **Dynamic Server Tags**: Flexible categorization system for discovery
- **Expanded Game Support**: 8 supported games with extensible architecture
- **Team Formation**: Intelligent matchmaking with skill balancing algorithms
- **Goal Integration**: Community challenges with Steam achievement synchronization
- **Achievement Leaderboards**: Server-wide competition with seasonal rankings
- **Real-Time Features**: Live gaming status updates and progress tracking

#### ✅ Critical Bug Fixes - **RESOLVED**

**Route Definition Error Fix** ✅ **(July 20, 2025)**
- **Issue**: `Route [profile.steam.refresh] not defined` error when visiting profile pages
- **Root Cause**: Laravel route cache was outdated after adding new Phase 2 routes
- **Solution**: Cleared and regenerated all Laravel caches:
  ```bash
  php artisan route:clear && php artisan route:cache
  php artisan config:clear && php artisan view:clear
  ```
- **Verification**: Route properly registered as `POST /profile/refresh-steam → ProfileController@refreshSteamData`

**Steam Game Detection Enhancement** ✅
- **Issue**: Current game not detected even when games are running on Steam
- **Root Cause**: Detection logic required both `personastate == 1` AND `gameid` field presence
- **Solution**: Enhanced detection to check for ANY game presence (`gameid` OR `gameextrainfo`)
- **Result**: More robust game detection that works with various Steam API response patterns

## Security Considerations

### Access Control
- **Membership Validation**: Server membership checked on all actions
- **Ban/Mute Enforcement**: Blocked users cannot perform restricted actions
- **Message Permissions**: Users can only edit/delete own messages
- **Steam Validation**: OpenID verification against Steam community

### Data Protection
- **Input Validation**: Comprehensive form validation with Laravel requests
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **XSS Protection**: Blade template escaping
- **CSRF Protection**: Laravel middleware on all forms

## Environment Setup

### Required Services
- **MySQL Database**: Primary data storage (configured for MySQL in Laragon)
- **WebSocket Server**: Laravel Reverb for real-time features
- **Steam API**: Integration requires `STEAM_API_KEY` in `.env`

### Configuration Notes
- **Database**: MySQL configured with performance indexes
- **Cache**: Database cache (Redis recommended for production)
- **Broadcasting**: Reverb WebSocket server for real-time features
- **Vite**: Asset compilation with Tailwind CSS 4.0

## Development Guidelines

### Performance Optimizations
- **Database Indexes**: Critical indexes added for messages, server_members, user_roles
- **N+1 Prevention**: Helper methods in controllers to consolidate membership queries
- **Eager Loading**: Proper relationship loading in server/channel displays
- **Caching**: Steam data cached for 15 minutes

### Real-Time Features
- **Event Broadcasting**: Use consistent data formatting across all events
- **Error Handling**: Comprehensive WebSocket error logging
- **Duplicate Prevention**: Frontend logic to prevent duplicate messages
- **Connection Management**: Auto-reconnection handling

### Steam Integration
- **Rate Limiting**: Respect Steam API limits with delays
- **Error Graceful**: Graceful degradation when Steam API unavailable
- **Data Validation**: Verify Steam responses before storage
- **Caching Strategy**: Balance freshness with API rate limits

#### **Key Takeaways for Future Development**

1. **Route Order Matters**: Always place static routes BEFORE dynamic routes with parameters
2. **Import Controllers**: Always import controller classes at the top of routes file
3. **Cache Management**: Clear and regenerate route cache after route changes
4. **Systematic Debugging**: Use `php artisan route:list` to verify route registration and ordering
5. **Route Conflicts**: Be aware of parameter conflicts between static and dynamic routes

#### **Best Practices Established**
- Place all static `/servers/*` routes before `/servers/{server}` routes
- Always import controller classes rather than using full namespaces
- Clear caches after route modifications
- Verify route ordering during development
- Document route conflicts for future reference

This routing issue resolution ensures robust route handling for all future feature development.

## 📧 **Email Configuration & OTP System** ✅ **(July 20, 2025)**

### **Production-Ready Email Delivery**
- **SMTP Provider**: Gmail with App Password authentication  
- **Configuration**: `.env` file with `MAIL_MAILER=smtp`
- **Authentication**: Uses `syameer.anwari@gmail.com` as SMTP service account
- **Delivery**: Can send to any email address (not just the SMTP account)
- **Branding**: Emails appear from `noreply@glyph.com` to recipients

### **Gmail SMTP Setup Requirements**
1. **2-Factor Authentication**: Must be enabled on Gmail account
2. **App Password**: Generate 16-character app password (no spaces)
3. **Configuration**:
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=syameer.anwari@gmail.com
   MAIL_PASSWORD=your_16_char_app_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS="noreply@glyph.com"
   ```

### **OTP System Features**
- **Implementation**: `AuthController@resendOtp()` method with route protection
- **Security**: 6-digit cryptographically secure codes, 10-minute expiration
- **Rate Limiting**: Registration/login (5/min), resend (3/min) via `throttle` middleware
- **User Experience**: Professional email template (`OtpMail` class), resend button on verification page
- **Error Handling**: Graceful fallback shows OTP in development when email fails
- **Templates**: Located in `resources/views/emails/otp.blade.php`

### **Important Notes**
- **Cache Management**: Always run `php artisan config:clear` after `.env` mail changes
- **SMTP Service**: Gmail account acts as delivery service for any recipient email
- **Development**: Can use `MAIL_MAILER=log` for testing without actual email delivery
- **Production**: Consider Mailgun, SES, or SendGrid for high-volume applications

## Future Architecture Notes

The application is designed for scalability with planned enhancements documented in `steamplan.md`:
- **Server Recommendations**: Smart matching based on gaming preferences
- **Advanced Steam Features**: Rich presence data, real-time status updates
- **Gaming Analytics**: Server insights and member activity tracking
- **Cross-Platform**: Integration with Discord, Epic Games, Xbox Live

The architecture supports these future features through extensible service layers, event-driven design, and flexible database schema optimized for high-performance gaming data queries.