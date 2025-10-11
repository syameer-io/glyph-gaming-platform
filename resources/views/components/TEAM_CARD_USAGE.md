# Team Card Component Usage Guide

## Overview
The `team-card` component is a unified, reusable Blade component for displaying team information across different contexts (browse, matchmaking, dashboard).

## Location
`resources/views/components/team-card.blade.php`

## Props

### Required Props
- **`team`** (Team model): The team instance to display

### Optional Props
- **`showCompatibility`** (bool, default: `false`): Whether to show compatibility score instead of team stats
- **`compatibilityScore`** (float|null, default: `null`): Compatibility percentage (0-100)
- **`compatibilityDetails`** (array|null, default: `null`): Detailed breakdown (e.g., `['skill' => 85, 'role' => 90]`)
- **`context`** (string, default: `'browse'`): Context where component is used (`'browse'`, `'matchmaking'`, `'dashboard'`)

## Usage Examples

### Example 1: Basic Browse Context (Teams Index Page)
```blade
@foreach($teams as $team)
    <x-team-card
        :team="$team"
        context="browse"
    />
@endforeach
```

### Example 2: Matchmaking Context with Compatibility Score
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

### Example 3: Matchmaking with Detailed Compatibility Breakdown
```blade
@php
    $compatibility = $matchmakingService->calculateDetailedCompatibility($team, $request);
@endphp

<x-team-card
    :team="$team"
    :showCompatibility="true"
    :compatibilityScore="$compatibility['total_score']"
    :compatibilityDetails="$compatibility['breakdown']"
    context="matchmaking"
/>
```

### Example 4: Dashboard Context (User's Teams)
```blade
@foreach(auth()->user()->teams as $team)
    <x-team-card
        :team="$team"
        context="dashboard"
    />
@endforeach
```

## Context-Specific Behavior

### Browse Context
- Shows "Request to Join" button **only if team is recruiting**
- Displays standard team stats (skill level, member count)

### Matchmaking Context
- Shows "Request to Join" button **regardless of recruiting status** (assumes algorithmic match)
- Displays compatibility score if provided
- Can show detailed compatibility breakdown

### Dashboard Context
- Typically hides "Request to Join" button (user manages their own teams)
- Shows standard team stats

## Compatibility Details Structure

The `compatibilityDetails` prop expects an array with score keys:

```php
[
    'skill' => 85.5,      // Skill compatibility (0-100)
    'role' => 90.0,       // Role match score (0-100)
    'region' => 100.0,    // Region compatibility (0-100)
    'size' => 75.0,       // Team size preference (0-100)
    'activity' => 80.0,   // Activity time match (0-100)
]
```

These are automatically displayed as a visual breakdown grid below the team description.

## Styling

The component uses inline styles to be self-contained and follows Glyph's design system:

- **Background**: `#18181b` (dark theme)
- **Border**: `#3f3f46`
- **Text Colors**: `#efeff1` (primary), `#b3b3b5` (secondary), `#71717a` (muted)
- **Gradient**: `linear-gradient(135deg, #667eea 0%, #764ba2 100%)`
- **Status Colors**:
  - Recruiting: `#10b981` (green)
  - Full: `#f59e0b` (yellow/orange)
  - Active: `#667eea` (purple)

## JavaScript Integration

The component includes a "Request to Join" button that calls the `requestToJoin(teamId, event)` function.

**Ensure this function is defined in your page**:

```javascript
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
```

## Accessibility

The component includes:
- Semantic HTML structure
- Alt text for member avatars
- Title attributes for hover tooltips
- High-contrast colors for readability
- Clear focus states for interactive elements

## Responsive Design

The component is fully responsive:
- Uses flexbox for adaptive layouts
- Member avatars wrap on narrow screens
- Button layout adjusts to available space
- Works well in grid layouts (e.g., `grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))`)

## Migration from Existing Code

To replace existing team card HTML with this component:

**Before:**
```blade
<div class="team-card">
    <div class="team-name">{{ $team->name }}</div>
    <!-- 50+ lines of HTML... -->
</div>
```

**After:**
```blade
<x-team-card :team="$team" context="browse" />
```

This reduces template code by ~95% and ensures consistency across the application.
