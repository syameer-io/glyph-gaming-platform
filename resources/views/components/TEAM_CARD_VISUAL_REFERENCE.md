# Team Card Component - Visual Reference

## Component States

### State 1: Browse Context (Standard View)
```
┌─────────────────────────────────────────┐
│ Elite Warriors              Intermediate│
│ Counter-Strike 2            3/5 members │
│ ● RECRUITING                            │
│                                         │
│ [avatar][avatar][avatar][+][+]          │
│                                         │
│ Competitive CS2 team looking for...    │
│                                         │
│ [Intermediate][EU West][Evening]        │
│                                         │
│ [View Team] [Request to Join]           │
└─────────────────────────────────────────┘
```

**Features:**
- Team name and game at top
- Status badge with colored dot
- Skill level and member count (top-right)
- Member avatars with empty slots
- Truncated description
- Tags for metadata
- Two action buttons

---

### State 2: Matchmaking Context (With Compatibility)
```
┌─────────────────────────────────────────┐
│ Elite Warriors              ┌─────────┐ │
│ Counter-Strike 2            │   87%   │ │
│ ● RECRUITING                │  Match  │ │
│                             └─────────┘ │
│                                         │
│ [avatar][avatar][avatar][+][+]          │
│                                         │
│ Competitive CS2 team looking for...    │
│                                         │
│ ┌────────┬────────┬────────┬────────┐  │
│ │ Skill  │  Role  │ Region │ Activity│  │
│ │  92%   │  85%   │  100%  │   80%  │  │
│ └────────┴────────┴────────┴────────┘  │
│                                         │
│ [Intermediate][EU West][Evening]        │
│                                         │
│ [View Team] [Request to Join]           │
└─────────────────────────────────────────┘
```

**Features:**
- Compatibility score badge (top-right) instead of stats
- Color-coded score (green/yellow/red)
- Detailed compatibility breakdown grid
- Join button shown regardless of recruiting status

---

### State 3: User is Member
```
┌─────────────────────────────────────────┐
│ Elite Warriors              Intermediate│
│ Counter-Strike 2            3/5 members │
│ ● RECRUITING                            │
│                                         │
│ [avatar][avatar][avatar][+][+]          │
│                                         │
│ Competitive CS2 team looking for...    │
│                                         │
│ [Intermediate][EU West][Evening]        │
│                                         │
│ [View Team] [✓ Member]                  │
└─────────────────────────────────────────┘
```

**Features:**
- "✓ Member" badge replaces join button
- Green background with checkmark
- User cannot join a team they're already in

---

### State 4: Full Team (Not Recruiting)
```
┌─────────────────────────────────────────┐
│ Elite Warriors              Intermediate│
│ Counter-Strike 2            5/5 members │
│ ● FULL                                  │
│                                         │
│ [avatar][avatar][avatar][avatar][avatar]│
│                                         │
│ Competitive CS2 team looking for...    │
│                                         │
│ [Intermediate][EU West][Evening]        │
│                                         │
│ [View Team]                             │
└─────────────────────────────────────────┘
```

**Features:**
- Yellow/orange "FULL" status
- All avatar slots filled (no placeholders)
- No join button (team not accepting members)

---

### State 5: Large Team (10+ Members)
```
┌─────────────────────────────────────────┐
│ Mega Clan                       Advanced│
│ Dota 2                        12/20 mems│
│ ● RECRUITING                            │
│                                         │
│ [avatar][avatar][avatar][avatar][avatar]│
│ +7 more                                 │
│                                         │
│ Large competitive Dota 2 clan...        │
│                                         │
│ [Advanced][Asia][Flexible]              │
│                                         │
│ [View Team] [Request to Join]           │
└─────────────────────────────────────────┘
```

**Features:**
- Shows first 5 members only
- "+7 more" indicator for additional members
- No empty slot placeholders (team has many members)

---

## Color Reference

### Status Colors
- **Recruiting**: `#10b981` (Green) - Team actively looking for members
- **Full**: `#f59e0b` (Yellow/Orange) - Team at capacity
- **Active**: `#667eea` (Purple) - Team playing together
- **Private**: `#9ca3af` (Gray) - Closed recruitment

### Compatibility Score Colors
- **80-100%**: `#10b981` (Green) - Excellent match
- **60-79%**: `#f59e0b` (Yellow) - Good match
- **40-59%**: `#ef4444` (Orange/Red) - Fair match
- **0-39%**: `#71717a` (Gray) - Poor match

### Background Colors
- **Card Background**: `#18181b` (Dark)
- **Border**: `#3f3f46` (Gray-700)
- **Border (Hover)**: `#667eea` (Purple)
- **Secondary Background**: `#0e0e10` (Darker)

### Text Colors
- **Primary Text**: `#efeff1` (White-ish)
- **Secondary Text**: `#b3b3b5` (Gray)
- **Muted Text**: `#71717a` (Darker Gray)

---

## Interactive States

### Hover Effects

**Card Hover:**
```
Border color changes: #3f3f46 → #667eea
Transform: translateY(0) → translateY(-2px)
Box shadow: none → 0 8px 25px rgba(102, 126, 234, 0.15)
```

**Button Hover (Primary):**
```
Transform: translateY(0) → translateY(-1px)
Box shadow: none → 0 4px 12px rgba(102, 126, 234, 0.4)
```

**Button Hover (Secondary):**
```
Background: #3f3f46 → #52525b
```

**Avatar Hover:**
```
Border color: #3f3f46 → #667eea
Transform: scale(1) → scale(1.1)
```

### Loading State

**Join Button (Loading):**
```
┌─────────────────────┐
│        ⟳           │  ← Spinning animation
└─────────────────────┘
```

Button text hidden, spinner shown with rotation animation.

---

## Responsive Behavior

### Desktop (>768px)
- Cards in grid layout: 3-4 columns
- Full member avatars shown
- Buttons side-by-side
- All text visible

### Tablet (768px)
- Cards in grid layout: 2 columns
- Member avatars wrap
- Buttons may stack
- Text may truncate

### Mobile (<768px)
- Cards in single column
- Member avatars wrap
- Buttons stack vertically
- Text truncates aggressively

---

## Typography

### Font Sizes
- **Team Name**: 20px (bold, 600 weight)
- **Game Name**: 14px (regular)
- **Status Badge**: 12px (uppercase, bold)
- **Skill Level**: 14px (gradient fill)
- **Member Count**: 12px
- **Description**: 14px (line-height: 1.5)
- **Tags**: 11px (uppercase)
- **Buttons**: 14px (bold)
- **Compatibility Score**: 28px (bold, 700 weight)
- **Compatibility Label**: 10px (uppercase)

### Font Weights
- **Regular**: 400 (descriptions, secondary text)
- **Semibold**: 600 (team name, buttons)
- **Bold**: 700 (compatibility score)

---

## Spacing

### Card Padding
- **All sides**: 24px

### Internal Gaps
- **Header to Members**: 16px
- **Members to Description**: 16px
- **Description to Compatibility**: 16px
- **Compatibility to Tags**: 16px
- **Tags to Buttons**: 16px (margin-top: auto for sticky bottom)

### Element Gaps
- **Avatar spacing**: 8px
- **Button gap**: 8px
- **Tag gap**: 6px

---

## Grid Layouts

### Recommended Container
```css
.teams-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
```

### Alternative Layouts

**Two Column:**
```css
.teams-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}
```

**Three Column:**
```css
.teams-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}
```

**List View:**
```css
.teams-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
```

---

## Animation Timings

- **Card Hover**: 0.2s ease
- **Button Hover**: 0.2s ease
- **Avatar Hover**: 0.2s ease
- **Loading Spinner**: 0.8s linear infinite

---

## Component Hierarchy

```
team-card
├── header
│   ├── team-info
│   │   ├── name
│   │   ├── game
│   │   └── status-badge
│   └── stats (compatibility OR skill/members)
├── member-avatars
│   ├── avatar (×5 max)
│   ├── placeholder (empty slots)
│   └── "+N more" indicator
├── description (truncated)
├── compatibility-breakdown (optional)
├── tags
│   ├── skill-level
│   ├── region
│   ├── activity-time
│   └── voice-chat
└── actions
    ├── view-team-button
    └── join-button OR member-badge
```

---

## Accessibility Notes

### ARIA Labels (Future Enhancement)
```html
<div role="article" aria-label="Team card for {{ $team->name }}">
    <button aria-label="Request to join {{ $team->name }}">
        Request to Join
    </button>
</div>
```

### Screen Reader Text
- Alt text on avatars: "{{ $member->user->display_name }}"
- Title tooltips: "{{ $member->user->display_name }} ({{ $member->game_role }})"

### Keyboard Navigation
- Tab order: Card → View Button → Join Button
- Enter/Space activates buttons
- Focus visible on all interactive elements

---

## Print Styles (Future Enhancement)

```css
@media print {
    .team-card {
        break-inside: avoid;
        border: 1px solid #000;
        background: #fff;
    }

    .team-card button {
        display: none; /* Hide interactive elements */
    }
}
```

---

This visual reference should help developers understand how the component looks and behaves in different scenarios.
