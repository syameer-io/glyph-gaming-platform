# Phase 5: Voice Connected Panel

## Overview
Transform the minimal voice connection bar into a rich, Discord-inspired voice control panel with proper user information, quality indicators, and comprehensive controls.

## Current State Analysis
- Fixed bar at bottom showing "VOICE CONNECTED"
- Channel name displayed
- Connection quality indicator (Excellent/Good/Poor)
- Green dot for connected status
- Mute toggle button
- Disconnect button (red)
- Very basic, minimal design

## Target State
- Polished voice panel matching Discord's design
- User avatar and name prominently displayed
- Connection quality with network stats
- Expandable for more options
- Controls: Mute, Deafen, Video, Screen Share, Activities
- Noise suppression toggle
- Settings access
- Disconnect button
- Voice channel info popup
- User volume controls (future)

---

## Implementation Checklist

### 5.1 Panel Layout Redesign
- [x] Redesign panel with two-section layout:
  - [x] Left: Channel info + connection status
  - [x] Right: Control buttons
- [x] Add proper padding and spacing
- [x] Use consistent dark theme colors
- [x] Add subtle top border/separator
- [x] Fixed position at sidebar bottom

### 5.2 Channel Info Section
- [x] Show channel name with speaker icon
- [x] Add server name in smaller text
- [x] Show connection quality badge
- [x] Add expandable details popup:
  - [x] Ping/latency
  - [x] Packet loss
  - [x] Audio quality
  - [x] Connection duration
  - [x] Server region

### 5.3 Connection Quality Indicator
- [x] Redesign quality bars (5 bars like mobile signal)
- [x] Color coding:
  - [x] Green (Excellent): All bars, < 50ms
  - [x] Yellow (Good): 3-4 bars, 50-100ms
  - [x] Orange (Poor): 2 bars, 100-200ms
  - [x] Red (Bad): 1 bar, > 200ms
- [x] Add tooltip with exact ping
- [x] Animate on quality change
- [x] Click to show detailed stats

### 5.4 Control Buttons - Row 1
- [x] Mute microphone button:
  - [x] Icon: Microphone (normal) / Crossed mic (muted)
  - [x] Red background when muted
  - [x] Tooltip: "Mute" / "Unmute"
  - [x] Keyboard shortcut indicator
- [x] Deafen button:
  - [x] Icon: Headphones (normal) / Crossed headphones (deafened)
  - [x] Red background when deafened
  - [x] Tooltip: "Deafen" / "Undeafen"
- [x] Settings button:
  - [x] Opens voice settings modal
  - [x] Gear icon

### 5.5 Control Buttons - Row 2 (Expandable)
- [ ] Video toggle button:
  - [ ] Camera icon (on/off state)
  - [ ] Disabled if no camera
  - [ ] Tooltip with camera name
- [ ] Screen share button:
  - [ ] Monitor icon
  - [ ] Opens screen/window picker
  - [ ] Shows "Streaming" when active
- [ ] Activities button:
  - [ ] Rocket/gamepad icon
  - [ ] Opens activity picker
- [x] Noise suppression toggle:
  - [x] AI noise suppression icon
  - [x] On/off indicator

### 5.6 Disconnect Button
- [x] Red background button
- [x] Phone with X icon
- [x] Confirm on click (or immediate disconnect)
- [x] Hover: Brighter red
- [x] Tooltip: "Disconnect"

### 5.7 Voice Settings Quick Access
- [x] Gear icon opens popover with:
  - [x] Input device selector
  - [x] Output device selector
  - [x] Input sensitivity slider
  - [x] Output volume slider
  - [x] "Voice Settings" link to full settings

### 5.8 Connection Status Indicator
- [x] Show different states:
  - [x] Connecting (pulsing yellow)
  - [x] Connected (solid green)
  - [x] Reconnecting (pulsing orange)
  - [x] Failed (solid red)
- [x] Text matches state

### 5.9 Timer Display (Optional)
- [x] Show time in voice call
- [x] Format: HH:MM:SS or MM:SS
- [x] Update every second
- [x] Position near channel info

---

## Technical Specifications

### CSS Variables
```css
:root {
  --voice-panel-bg: #232428;
  --voice-panel-border: #1e1f22;
  --voice-connected: #23a559;
  --voice-connecting: #f0b232;
  --voice-failed: #ed4245;
  --voice-button-bg: #313338;
  --voice-button-hover: #404249;
  --voice-button-active: #ed4245;
  --voice-quality-excellent: #43b581;
  --voice-quality-good: #faa61a;
  --voice-quality-poor: #f04747;
}
```

### Panel Structure
```html
<div class="voice-panel" x-data="voicePanel" x-show="isConnected">
  <!-- Channel Info -->
  <div class="voice-panel-info">
    <div class="voice-status">
      <span class="status-indicator" :class="connectionStatus"></span>
      <span class="status-text" x-text="statusText">Voice Connected</span>
    </div>
    <div class="voice-channel-info">
      <svg class="voice-icon">...</svg>
      <span class="channel-name">voice-chat</span>
      <div class="connection-quality" @click="showStats = !showStats">
        <div class="quality-bars" :data-quality="quality">
          <span></span><span></span><span></span><span></span><span></span>
        </div>
        <span class="quality-text" x-text="qualityText"></span>
      </div>
    </div>
  </div>

  <!-- Connection Stats Popup -->
  <div class="voice-stats-popup" x-show="showStats" x-transition>
    <div class="stat-row">
      <span>Ping</span>
      <span x-text="ping + 'ms'">32ms</span>
    </div>
    <div class="stat-row">
      <span>Packet Loss</span>
      <span x-text="packetLoss + '%'">0%</span>
    </div>
  </div>

  <!-- Controls -->
  <div class="voice-panel-controls">
    <button class="voice-btn" :class="{ 'active': isMuted }" @click="toggleMute">
      <svg x-show="!isMuted"><!-- mic --></svg>
      <svg x-show="isMuted"><!-- mic-off --></svg>
    </button>
    <button class="voice-btn" :class="{ 'active': isDeafened }" @click="toggleDeafen">
      <svg x-show="!isDeafened"><!-- headphones --></svg>
      <svg x-show="isDeafened"><!-- headphones-off --></svg>
    </button>
    <button class="voice-btn" @click="openSettings">
      <svg><!-- settings --></svg>
    </button>
    <button class="voice-btn disconnect" @click="disconnect">
      <svg><!-- phone-off --></svg>
    </button>
  </div>
</div>
```

### Quality Bars CSS
```css
.quality-bars {
  display: flex;
  gap: 2px;
  align-items: flex-end;
  height: 16px;
}

.quality-bars span {
  width: 3px;
  background: var(--voice-quality-excellent);
  border-radius: 1px;
  opacity: 0.3;
}

.quality-bars span:nth-child(1) { height: 4px; }
.quality-bars span:nth-child(2) { height: 6px; }
.quality-bars span:nth-child(3) { height: 8px; }
.quality-bars span:nth-child(4) { height: 12px; }
.quality-bars span:nth-child(5) { height: 16px; }

.quality-bars[data-quality="excellent"] span { opacity: 1; }
.quality-bars[data-quality="good"] span:nth-child(-n+4) { opacity: 1; }
.quality-bars[data-quality="poor"] span:nth-child(-n+2) { opacity: 1; }
.quality-bars[data-quality="bad"] span:first-child { opacity: 1; }
```

### Alpine.js Component
```javascript
Alpine.data('voicePanel', () => ({
  isConnected: false,
  isMuted: false,
  isDeafened: false,
  connectionStatus: 'connected',
  statusText: 'Voice Connected',
  quality: 'excellent',
  qualityText: 'Excellent',
  ping: 32,
  packetLoss: 0,
  showStats: false,
  callDuration: 0,

  init() {
    // Sync with VoiceChat instance
    this.syncWithVoiceChat();
    this.startDurationTimer();
  },

  toggleMute() {
    this.isMuted = !this.isMuted;
    window.voiceChat?.toggleMute();
  },

  toggleDeafen() {
    this.isDeafened = !this.isDeafened;
    if (this.isDeafened) this.isMuted = true;
    window.voiceChat?.toggleDeafen();
  },

  disconnect() {
    window.voiceChat?.disconnect();
    this.isConnected = false;
  },

  updateQuality(network) {
    if (network.rtt < 50) this.quality = 'excellent';
    else if (network.rtt < 100) this.quality = 'good';
    else if (network.rtt < 200) this.quality = 'poor';
    else this.quality = 'bad';
  }
}));
```

---

## Files to Modify

| File | Changes |
|------|---------|
| `resources/views/channels/show.blade.php` | Replace voice panel section |
| `resources/views/servers/show.blade.php` | Replace voice panel section |
| `resources/views/components/voice-panel.blade.php` | Create new component |
| `resources/views/components/voice-settings-popup.blade.php` | Create settings popup |
| `resources/css/voice-panel.css` | Create dedicated styles |
| `resources/js/voice-chat.js` | Add deafen, stats methods |

---

## Design Reference

### Voice Panel (Compact)
```
┌─────────────────────────────────────────────────────────┐
│  ● Voice Connected                                       │
│  🔊 voice-chat  │▐▐▐▐▐│ 32ms                            │
│                                                          │
│  [🎤] [🎧] [⚙️]                              [📞 Disconnect] │
└─────────────────────────────────────────────────────────┘
```

### Voice Panel (Expanded with Stats)
```
┌─────────────────────────────────────────────────────────┐
│  ● Voice Connected            ┌─────────────────────┐   │
│  🔊 voice-chat               │ Ping: 32ms          │   │
│                               │ Packet Loss: 0%     │   │
│                               │ Quality: Excellent  │   │
│                               └─────────────────────┘   │
│  [🎤] [🎧] [📹] [🖥️] [🎮] [⚙️]              [📞 Disconnect] │
└─────────────────────────────────────────────────────────┘
```

### Button States
- **Default**: Dark gray background, light icon
- **Hover**: Lighter background
- **Active (on)**: Red background, white icon
- **Disabled**: 50% opacity, cursor not-allowed

---

## Accessibility Considerations
- [x] All buttons have aria-labels
- [x] Mute/deafen states announced
- [x] Connection status announced
- [x] Keyboard navigation for all controls
- [x] Focus visible on buttons
- [x] Screen reader announces quality

---

## Testing Checklist
- [x] Panel shows when voice connected
- [x] Panel hides when disconnected
- [x] Mute button toggles correctly
- [x] Deafen button toggles correctly
- [x] Deafen auto-mutes microphone
- [x] Quality bars update in real-time
- [x] Stats popup shows on click
- [x] Disconnect ends voice call
- [x] Settings popup opens
- [x] All icons display correctly
- [x] Mobile: Touch targets adequate
- [x] Keyboard shortcuts work

---

## Keyboard Shortcuts
| Shortcut | Action |
|----------|--------|
| M | Toggle mute |
| D | Toggle deafen |
| Esc | Disconnect (when focused) |
| V | Toggle video (if enabled) |
| S | Toggle screen share |

---

## Dependencies
- Agora RTC SDK (existing)
- Alpine.js (existing)
- Heroicons for icons
- New CSS file

---

## Future Enhancements
- User volume controls (per-user sliders)
- Push-to-talk mode
- Voice activity vs. push-to-talk toggle
- Echo cancellation toggle
- Automatic gain control
- Audio processing visualization
- Picture-in-picture for video
