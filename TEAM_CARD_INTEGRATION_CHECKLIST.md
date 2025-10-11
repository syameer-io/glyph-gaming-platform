# Team Card Component - Integration Checklist

## Quick Start Guide

Follow this checklist to integrate the `team-card` component into your Laravel pages.

---

## Prerequisites

### ✅ Step 1: Verify Component Exists
```bash
# Check that the component file exists
ls resources/views/components/team-card.blade.php
```

### ✅ Step 2: Clear View Cache
```bash
php artisan view:clear
```

---

## Integration Steps

### ✅ Step 3: Add JavaScript Function to Your Page

Add this JavaScript function to any page that uses the component with join buttons:

```javascript
<script>
function requestToJoin(teamId, event) {
    const button = event.currentTarget;
    const btnText = button.querySelector('.btn-text');
    const spinner = button.querySelector('.loading-spinner');

    // Show loading state
    if (btnText) btnText.style.display = 'none';
    if (spinner) spinner.style.display = 'inline-block';
    button.disabled = true;

    // Make AJAX request
    fetch(`/teams/${teamId}/join-direct`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = '<span style="color: #10b981;">✓ Joined Team</span>';
            setTimeout(() => location.reload(), 1500);
        } else {
            alert(data.error || 'Error joining team');
            if (btnText) btnText.style.display = 'inline';
            if (spinner) spinner.style.display = 'none';
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error joining team');
        if (btnText) btnText.style.display = 'inline';
        if (spinner) spinner.style.display = 'none';
        button.disabled = false;
    });
}
</script>
```

### ✅ Step 4: Verify CSRF Token in Layout

Ensure your layout includes the CSRF token meta tag (usually in `layouts/app.blade.php`):

```html
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- other meta tags -->
</head>
```

### ✅ Step 5: Update Controller to Eager Load Relationships

**CRITICAL**: To prevent N+1 queries, eager-load relationships:

```php
// Example: TeamsController.php

public function index()
{
    $teams = Team::with([
        'activeMembers.user.profile',  // Load members, users, and profiles
        'server',                      // Load server
        'creator'                      // Load creator
    ])->get();

    return view('teams.index', compact('teams'));
}
```

### ✅ Step 6: Add Grid Layout CSS (Optional)

Add this CSS to your page for a responsive grid layout:

```html
<style>
    .teams-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    @media (max-width: 768px) {
        .teams-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
```

### ✅ Step 7: Replace Existing Team Cards

**Before:**
```blade
<!-- Old HTML code (50+ lines) -->
<div class="team-card">
    <div class="team-header">
        <div class="team-name">{{ $team->name }}</div>
        <!-- ... many lines ... -->
    </div>
</div>
```

**After:**
```blade
<!-- New component (1 line) -->
<x-team-card :team="$team" context="browse" />
```

---

## Context-Specific Integration

### Browse Context (Teams Index Page)

```blade
<div class="teams-grid">
    @foreach($teams as $team)
        <x-team-card
            :team="$team"
            context="browse"
        />
    @endforeach
</div>
```

**Features:**
- Shows "Request to Join" button ONLY for recruiting teams
- Displays skill level and member count
- Standard team information

---

### Matchmaking Context (Recommended Teams)

```blade
<div class="teams-grid">
    @foreach($recommendedTeams as $teamData)
        <x-team-card
            :team="$teamData['team']"
            :showCompatibility="true"
            :compatibilityScore="$teamData['compatibility_score']"
            context="matchmaking"
        />
    @endforeach
</div>
```

**Features:**
- Shows "Request to Join" button for ALL teams (regardless of recruiting status)
- Displays compatibility score instead of skill level
- Algorithmic matching context

---

### Matchmaking with Detailed Breakdown

```blade
<div class="teams-grid">
    @foreach($teams as $team)
        @php
            $compatibility = app(\App\Services\MatchmakingService::class)
                ->calculateDetailedCompatibility($team, $matchmakingRequest);
        @endphp

        <x-team-card
            :team="$team"
            :showCompatibility="true"
            :compatibilityScore="$compatibility['total_score']"
            :compatibilityDetails="$compatibility['breakdown']"
            context="matchmaking"
        />
    @endforeach
</div>
```

**Features:**
- Shows compatibility score
- Shows detailed breakdown grid (skill, role, region, etc.)
- Visual compatibility indicators

---

### Dashboard Context (User's Teams)

```blade
<div class="teams-grid">
    @foreach(auth()->user()->teams as $team)
        <x-team-card
            :team="$team"
            context="dashboard"
        />
    @endforeach
</div>
```

**Features:**
- Typically hides "Request to Join" button
- Shows "✓ Member" badge
- User's own team management context

---

## Testing Checklist

After integration, test these scenarios:

### Visual Tests
- [ ] Cards display correctly in grid layout
- [ ] Hover effects work on cards and buttons
- [ ] Status indicators show correct colors
- [ ] Member avatars display (or fallback to placeholder)
- [ ] Text truncation works for long descriptions

### Functional Tests
- [ ] "View Team" button navigates to team page
- [ ] "Request to Join" button shows loading spinner
- [ ] Join request succeeds and shows success state
- [ ] Join request error shows error message
- [ ] Member badge displays for teams user has joined
- [ ] Compatibility scores display correctly (if used)
- [ ] Compatibility breakdown grid displays (if used)

### Edge Case Tests
- [ ] Teams with 0 members display correctly
- [ ] Teams with 10+ members show "+N more" indicator
- [ ] Teams without descriptions don't break layout
- [ ] Missing team data doesn't cause errors
- [ ] Unauthenticated users see appropriate UI
- [ ] Full teams don't show join button (browse context)

### Performance Tests
- [ ] No N+1 queries (check Laravel Debugbar)
- [ ] Page loads quickly with 10+ teams
- [ ] No console errors in browser
- [ ] Responsive layout works on mobile

---

## Common Issues & Solutions

### Issue 1: Component Not Found
**Error**: `Component [team-card] not found.`

**Solution**:
```bash
# Clear view cache
php artisan view:clear

# Verify file exists
ls resources/views/components/team-card.blade.php
```

---

### Issue 2: N+1 Query Problem
**Error**: Hundreds of queries in Laravel Debugbar

**Solution**:
```php
// Add eager loading in controller
$teams = Team::with([
    'activeMembers.user.profile',
    'server',
    'creator'
])->get();
```

---

### Issue 3: Join Button Does Nothing
**Error**: Button doesn't respond to clicks

**Solution**:
- Verify `requestToJoin()` JavaScript function is defined on the page
- Check browser console for JavaScript errors
- Verify CSRF token meta tag is present

---

### Issue 4: Missing Avatar Images
**Error**: Broken image icons for member avatars

**Solution**:
```php
// Ensure profile relationship is loaded
Team::with(['activeMembers.user.profile'])->get()

// Or add fallback in profile model
public function getAvatarUrlAttribute()
{
    return $this->steam_data['avatar'] ?? asset('images/default-avatar.png');
}
```

---

### Issue 5: Compatibility Score Not Showing
**Error**: Score shows as 0% or not at all

**Solution**:
```blade
<!-- Verify props are set correctly -->
<x-team-card
    :team="$team"
    :showCompatibility="true"               <!-- Must be true -->
    :compatibilityScore="$score"            <!-- Must not be null -->
/>
```

---

## Performance Best Practices

### 1. Always Eager Load Relationships
```php
// Good
Team::with(['activeMembers.user.profile'])->get()

// Bad
Team::all() // Will cause N+1 queries when rendering avatars
```

### 2. Limit Results in Controller
```php
// Good
Team::recruiting()->take(20)->get()

// Bad
Team::all() // Could load thousands of teams
```

### 3. Cache Expensive Queries
```php
$teams = Cache::remember('teams.browse', 300, function () {
    return Team::with(['activeMembers.user.profile'])->recruiting()->get();
});
```

---

## Migration Examples

### Example 1: Teams Index Page

**File**: `resources/views/teams/index.blade.php`

**Before (Old Code)**:
```blade
@foreach($teams as $team)
    <div class="team-card">
        <div class="team-header">
            <div class="team-info">
                <div class="team-name">{{ $team->name }}</div>
                <div class="team-game">{{ $team->game_name }}</div>
                <div class="team-status status-{{ $team->status }}">
                    @if($team->status === 'recruiting')
                        <div style="width: 6px; height: 6px; background-color: #10b981;"></div>
                        Recruiting
                    @endif
                </div>
            </div>
            <!-- ... 40+ more lines ... -->
        </div>
    </div>
@endforeach
```

**After (New Component)**:
```blade
@foreach($teams as $team)
    <x-team-card :team="$team" context="browse" />
@endforeach
```

**Lines Saved**: ~50 lines per team × N teams

---

### Example 2: Matchmaking Results

**File**: `resources/views/matchmaking/results.blade.php`

**Before (Old Code)**:
```blade
@foreach($recommendedTeams as $teamData)
    <div class="team-card">
        <!-- Duplicate HTML with compatibility score logic -->
        <div class="compatibility-badge">
            <div class="score">{{ $teamData['compatibility_score'] }}%</div>
        </div>
        <!-- ... 40+ more lines ... -->
    </div>
@endforeach
```

**After (New Component)**:
```blade
@foreach($recommendedTeams as $teamData)
    <x-team-card
        :team="$teamData['team']"
        :showCompatibility="true"
        :compatibilityScore="$teamData['compatibility_score']"
        context="matchmaking"
    />
@endforeach
```

**Lines Saved**: ~50 lines per team × N teams
**Consistency**: 100% visual consistency with browse page

---

## Rollback Plan

If you need to rollback the integration:

1. **Keep Old HTML**: Don't delete old team card HTML until fully tested
2. **Gradual Migration**: Migrate one page at a time
3. **Feature Flag**: Use blade conditionals to toggle between old/new

```blade
@if(config('features.new_team_cards'))
    <x-team-card :team="$team" />
@else
    <!-- Old HTML code -->
@endif
```

---

## Support & Documentation

### Documentation Files
- **Usage Guide**: `resources/views/components/TEAM_CARD_USAGE.md`
- **Visual Reference**: `resources/views/components/TEAM_CARD_VISUAL_REFERENCE.md`
- **Technical Docs**: `TEAM_CARD_COMPONENT_DOCUMENTATION.md`
- **Task Summary**: `TASK_3.4_IMPLEMENTATION_SUMMARY.md`

### Component Location
- **Component File**: `resources/views/components/team-card.blade.php`

---

## Final Checklist

Before deploying to production:

- [ ] Component file exists and is valid
- [ ] View cache cleared
- [ ] JavaScript function defined on all pages using component
- [ ] CSRF token meta tag present in layout
- [ ] Relationships eager-loaded in controllers
- [ ] All visual tests pass
- [ ] All functional tests pass
- [ ] No N+1 queries (checked with Debugbar)
- [ ] No console errors in browser
- [ ] Responsive layout tested on mobile
- [ ] Documentation reviewed
- [ ] Old HTML code backed up (for rollback)

---

**When all items are checked, the component is ready for production!**

---

## Questions?

Refer to the comprehensive documentation:
- Technical details → `TEAM_CARD_COMPONENT_DOCUMENTATION.md`
- Visual reference → `TEAM_CARD_VISUAL_REFERENCE.md`
- Usage examples → `TEAM_CARD_USAGE.md`
