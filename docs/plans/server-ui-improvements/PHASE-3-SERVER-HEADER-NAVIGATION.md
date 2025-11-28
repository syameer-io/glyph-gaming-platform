# Phase 3: Server Header & Navigation

## Overview
Enhance the server header area with a polished dropdown menu, search functionality, server boost progress, notification icons, and improved breadcrumb navigation.

## Current State Analysis
- Simple server name display at top
- Invite code shown below name
- "Server Settings" button for admins
- Basic breadcrumb: Dashboard > Server Name > # channel
- No search functionality
- No boost progress indicator
- No notification/settings icons in header

## Target State
- Server name as dropdown trigger with menu
- Server boost progress bar (like Discord)
- Inline search with keyboard shortcut (Ctrl+K)
- Notification bell with badge
- Pin/threads icons
- Member toggle button
- Polished breadcrumb with proper separators
- Server banner support (future)

---

## Implementation Checklist

### 3.1 Server Dropdown Menu
- [x] Convert server name to dropdown trigger
- [x] Add chevron down icon that rotates on open
- [x] Design dropdown menu with sections:
  - [x] Server Boost section (with progress)
  - [x] Invite People
  - [x] Server Settings (admin only)
  - [x] Notification Settings
  - [x] Privacy Settings
  - [x] Edit Server Profile
  - [x] Hide Muted Channels toggle
  - [x] Divider line
  - [x] Leave Server (red, at bottom)
- [x] Implement keyboard navigation (arrow keys)
- [x] Close on Escape or click outside

### 3.2 Server Boost Progress
- [x] Add boost progress bar in dropdown
- [x] Show current boost level (Level 0-3)
- [x] Display "X boosts needed for Level Y"
- [x] Add sparkle icon for boost level
- [x] Link to boost purchase page

### 3.3 Channel Header Bar
- [x] Design header with channel name on left
- [x] Add channel description/topic (optional)
- [x] Implement header action icons on right:
  - [x] Threads icon (if supported)
  - [x] Notification bell icon
  - [x] Pin icon (pinned messages)
  - [x] Member list toggle icon
  - [x] Search icon
- [x] Add tooltips on hover for each icon
- [x] Add divider line between icon groups

### 3.4 Search Functionality
- [x] Create search modal (centered, large)
- [x] Implement Ctrl+K keyboard shortcut
- [x] Design search input with icon
- [x] Add search filters:
  - [x] In: [channel name]
  - [x] From: [username]
  - [x] Has: [link, image, file, embed]
  - [x] Before/After date
- [x] Show recent searches
- [x] Display search results with context
- [x] Highlight matching text in results

### 3.5 Breadcrumb Enhancement
- [x] Use proper separator icons (>)
- [x] Make each part clickable
- [x] Add hover states for clickable parts
- [x] Show channel icon (# or speaker) before name
- [x] Truncate long names with ellipsis

### 3.6 Notification Bell
- [x] Add bell icon to header
- [x] Show badge with unread count
- [x] Create notification dropdown:
  - [x] Recent mentions
  - [x] Role mentions
  - [x] DM notifications
  - [x] Mark all as read button
- [x] Implement notification settings quick toggle

### 3.7 Member List Toggle
- [x] Add members icon on right side
- [x] Toggle member sidebar visibility
- [x] Remember state in localStorage
- [x] Animate sidebar slide in/out
- [x] Update icon to show current state

### 3.8 Pinned Messages
- [x] Add pin icon to header
- [x] Create pinned messages popover
- [x] Show list of pinned messages
- [x] Allow unpinning (with permission)
- [x] Show "No pinned messages" empty state

---

## Technical Specifications

### CSS Variables
```css
:root {
  --header-bg: #2b2d31;
  --header-border: #1e1f22;
  --header-text: #f2f3f5;
  --header-icon: #b5bac1;
  --header-icon-hover: #dbdee1;
  --dropdown-bg: #111214;
  --dropdown-hover: #36373d;
  --dropdown-separator: #3d3e45;
  --boost-pink: #ff73fa;
  --boost-purple: #9b59b6;
}
```

### Server Dropdown Structure
```html
<div class="server-header" x-data="{ open: false }">
  <button class="server-dropdown-trigger" @click="open = !open">
    <span class="server-name">CS2 Chill Zone</span>
    <svg class="dropdown-chevron" :class="{ 'rotated': open }">...</svg>
  </button>

  <div class="server-dropdown-menu" x-show="open" @click.away="open = false">
    <div class="dropdown-section boost-section">
      <div class="boost-progress">
        <div class="boost-bar" style="width: 60%"></div>
      </div>
      <span>Level 1 - 2 Boosts</span>
    </div>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item">
      <svg>...</svg>
      Invite People
    </a>
    <!-- More items -->
  </div>
</div>
```

### Channel Header Structure
```html
<div class="channel-header">
  <div class="channel-info">
    <span class="channel-icon">#</span>
    <h1 class="channel-name">general</h1>
    <span class="channel-topic">Welcome to the server!</span>
  </div>

  <div class="channel-actions">
    <button class="header-icon-btn" title="Threads">
      <svg>...</svg>
    </button>
    <button class="header-icon-btn" title="Notification Settings">
      <svg>...</svg>
      <span class="notification-badge">3</span>
    </button>
    <button class="header-icon-btn" title="Pinned Messages">
      <svg>...</svg>
    </button>
    <div class="header-divider"></div>
    <button class="header-icon-btn" title="Toggle Member List">
      <svg>...</svg>
    </button>
    <div class="header-search">
      <input type="text" placeholder="Search">
      <span class="search-shortcut">Ctrl+K</span>
    </div>
  </div>
</div>
```

---

## Files to Modify

| File | Changes |
|------|---------|
| `resources/views/channels/show.blade.php` | Add header components |
| `resources/views/servers/show.blade.php` | Add server dropdown |
| `resources/views/components/server-dropdown.blade.php` | Create new component |
| `resources/views/components/channel-header.blade.php` | Create new component |
| `resources/views/components/search-modal.blade.php` | Create new component |
| `resources/css/header.css` | Create dedicated header styles |
| `resources/js/search.js` | Create search functionality |
| `app/Http/Controllers/SearchController.php` | Add search API |

---

## Search API Specification

### Endpoint
```
GET /api/servers/{server}/search?q=keyword&channel=general&from=user&has=image
```

### Response
```json
{
  "results": [
    {
      "id": 123,
      "content": "This is a message with the keyword",
      "author": { "id": 1, "username": "Accedix", "avatar": "..." },
      "channel": { "id": 1, "name": "general" },
      "created_at": "2025-01-15T10:30:00Z",
      "context": { "before": "...", "after": "..." }
    }
  ],
  "total": 42,
  "page": 1
}
```

---

## Design Reference

### Server Dropdown
```
┌─────────────────────────────────┐
│  CS2 Chill Zone            ▼   │
├─────────────────────────────────┤
│  ║████████░░░░░░░║  Level 1    │
│  2 Boosts • 3 for Level 2      │
├─────────────────────────────────┤
│  ✉️  Invite People              │
│  ⚙️  Server Settings            │
│  🔔  Notification Settings      │
│  🔒  Privacy Settings           │
├─────────────────────────────────┤
│  ✏️  Edit Server Profile        │
│  👁️  Hide Muted Channels        │
├─────────────────────────────────┤
│  🚪  Leave Server (red)         │
└─────────────────────────────────┘
```

### Channel Header
```
┌─────────────────────────────────────────────────────────────────────┐
│ # general  │  Welcome to the server     [🧵] [🔔] [📌] │ [👥] [🔍 Search] │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Accessibility Considerations
- [x] Dropdown has proper ARIA attributes
- [x] Focus management in modals
- [x] Keyboard navigation (Tab, Arrow keys, Escape)
- [x] Screen reader announces notifications
- [x] Search results navigable via keyboard
- [x] High contrast mode for icons

---

## Testing Checklist
- [x] Server dropdown opens/closes correctly
- [x] All dropdown items clickable
- [x] Leave server shows confirmation
- [x] Search modal opens with Ctrl+K
- [x] Search returns relevant results
- [x] Notification badge shows correct count
- [x] Member list toggle works
- [x] Pinned messages display correctly
- [x] Breadcrumb navigation works
- [x] Mobile: Touch-friendly buttons
- [x] Icons have proper tooltips

---

## Keyboard Shortcuts
| Shortcut | Action |
|----------|--------|
| Ctrl+K | Open search |
| Escape | Close modals/dropdowns |
| M | Toggle member list |
| / | Focus message input |

---

## Dependencies
- Alpine.js (already installed)
- Heroicons for consistent icons
- Fuse.js for client-side search (optional)

---

## Future Enhancements
- Server banner image in dropdown
- Thread list in header
- Quick switcher (Ctrl+K for servers too)
- Server discovery from dropdown
- Boost animations and effects
