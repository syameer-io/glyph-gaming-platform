@extends('layouts.app')

@section('title', 'Chat with ' . $otherParticipant->display_name . ' - Glyph')

@push('styles')
<style>
    /* ==========================================
       CSS Animations
       ========================================== */

    /* Typing indicator bounce animation */
    @keyframes bounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    .typing-dots span {
        display: inline-block;
        width: 6px;
        height: 6px;
        background-color: #71717a;
        border-radius: 50%;
        margin: 0 2px;
        animation: bounce 1s infinite;
    }

    .typing-dots span:nth-child(2) {
        animation-delay: 0.15s;
    }

    .typing-dots span:nth-child(3) {
        animation-delay: 0.3s;
    }

    /* Message appear animation */
    @keyframes messageAppear {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .message-item {
        animation: messageAppear 0.2s ease-out;
    }

    /* Connection status banner animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .connection-banner {
        animation: pulse 2s ease-in-out infinite;
    }

    /* Smooth scroll behavior */
    #messages-container {
        scroll-behavior: smooth;
    }

    /* ==========================================
       Three-Panel Layout
       ========================================== */

    .dm-layout {
        display: flex;
        height: 100vh;
        overflow: hidden;
    }

    /* Left Panel: Conversation Sidebar */
    .dm-sidebar-left {
        width: 260px;
        background-color: #18181b;
        border-right: 1px solid #3f3f46;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
    }

    /* Center Panel: Chat Area */
    .dm-chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background-color: #0e0e10;
        min-width: 0;
    }

    /* Right Panel: User Profile */
    .dm-sidebar-right {
        width: 280px;
        background-color: #18181b;
        flex-shrink: 0;
        display: none;
    }

    .dm-sidebar-right.visible {
        display: flex;
        flex-direction: column;
    }

    /* ==========================================
       Custom Scrollbar
       ========================================== */

    .dm-scrollable::-webkit-scrollbar {
        width: 8px;
    }

    .dm-scrollable::-webkit-scrollbar-track {
        background: #18181b;
    }

    .dm-scrollable::-webkit-scrollbar-thumb {
        background-color: #3f3f46;
        border-radius: 4px;
    }

    .dm-scrollable::-webkit-scrollbar-thumb:hover {
        background-color: #52525b;
    }

    /* ==========================================
       Read Receipt Indicator
       ========================================== */

    .read-receipt {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: #71717a;
        margin-left: auto;
    }

    .read-receipt svg {
        width: 14px;
        height: 14px;
    }

    .read-receipt.seen {
        color: #667eea;
    }

    /* ==========================================
       Connection Status Banner
       ========================================== */

    .connection-status-banner {
        display: none;
        padding: 8px 16px;
        text-align: center;
        font-size: 13px;
        font-weight: 500;
    }

    .connection-status-banner.disconnected {
        display: block;
        background-color: #eab308;
        color: #18181b;
    }

    .connection-status-banner.reconnecting {
        display: block;
        background-color: #f59e0b;
        color: #18181b;
    }

    /* ==========================================
       Chat Header
       ========================================== */

    .chat-header {
        padding: 12px 16px;
        border-bottom: 1px solid #3f3f46;
        background-color: #18181b;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .chat-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .chat-header-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .mobile-back-btn {
        display: none;
        color: #71717a;
        text-decoration: none;
        padding: 8px;
        border-radius: 6px;
        transition: all 0.15s;
    }

    .mobile-back-btn:hover {
        color: #efeff1;
        background-color: #27272a;
    }

    .header-avatar-wrapper {
        position: relative;
        flex-shrink: 0;
    }

    .header-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }

    .header-status-dot {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid #18181b;
    }

    .header-status-dot.online {
        background-color: #10b981;
    }

    .header-status-dot.offline {
        background-color: #6b7280;
    }

    .header-user-info {
        display: flex;
        flex-direction: column;
    }

    .header-user-name {
        font-weight: 600;
        color: #efeff1;
        text-decoration: none;
        font-size: 15px;
    }

    .header-user-name:hover {
        text-decoration: underline;
    }

    .header-user-status {
        font-size: 12px;
        color: #71717a;
    }

    .toggle-profile-btn {
        background: none;
        border: none;
        color: #71717a;
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
    }

    .toggle-profile-btn:hover {
        color: #efeff1;
        background-color: #27272a;
    }

    .toggle-profile-btn.active {
        color: #667eea;
        background-color: rgba(102, 126, 234, 0.1);
    }

    /* ==========================================
       Search Dropdown
       ========================================== */

    .search-container {
        position: relative;
    }

    .search-dropdown {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        width: 300px;
        background-color: #27272a;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        z-index: 100;
        display: none;
    }

    .search-dropdown.active {
        display: block;
    }

    .search-input-wrapper {
        padding: 12px;
        border-bottom: 1px solid #3f3f46;
    }

    .search-input {
        width: 100%;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 6px;
        padding: 8px 12px;
        color: #efeff1;
        font-size: 14px;
    }

    .search-input:focus {
        outline: none;
        border-color: #667eea;
    }

    .search-results {
        max-height: 300px;
        overflow-y: auto;
    }

    .search-result-item {
        padding: 12px;
        cursor: pointer;
        transition: background-color 0.15s;
        border-bottom: 1px solid #3f3f46;
    }

    .search-result-item:last-child {
        border-bottom: none;
    }

    .search-result-item:hover {
        background-color: #3f3f46;
    }

    .search-result-meta {
        font-size: 12px;
        color: #71717a;
        margin-bottom: 4px;
    }

    .search-result-content {
        font-size: 14px;
        color: #d4d4d8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .search-result-content mark {
        background-color: rgba(102, 126, 234, 0.3);
        color: #efeff1;
        padding: 1px 2px;
        border-radius: 2px;
    }

    .search-no-results {
        padding: 24px;
        text-align: center;
        color: #71717a;
        font-size: 14px;
    }

    /* ==========================================
       Emoji Picker
       ========================================== */

    .emoji-container {
        position: relative;
    }

    .emoji-btn {
        background: none;
        border: none;
        color: #71717a;
        padding: 8px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .emoji-btn:hover {
        color: #efeff1;
        background-color: #3f3f46;
    }

    .emoji-picker {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 0;
        background-color: #27272a;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        padding: 12px;
        display: none;
        z-index: 100;
    }

    .emoji-picker.active {
        display: block;
    }

    .emoji-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 4px;
    }

    .emoji-item {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        border-radius: 4px;
        cursor: pointer;
        transition: background-color 0.15s;
        background: none;
        border: none;
    }

    .emoji-item:hover {
        background-color: #3f3f46;
    }

    /* ==========================================
       Mobile Message Menu (Bottom Sheet)
       ========================================== */

    .mobile-menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        z-index: 2000;
        display: none;
        align-items: flex-end;
        justify-content: center;
    }

    .mobile-menu-overlay.active {
        display: flex;
    }

    .mobile-menu-content {
        width: 100%;
        max-width: 500px;
        background-color: #27272a;
        border-radius: 16px 16px 0 0;
        padding: 16px;
        padding-bottom: max(16px, env(safe-area-inset-bottom));
    }

    .mobile-menu-handle {
        width: 40px;
        height: 4px;
        background-color: #52525b;
        border-radius: 2px;
        margin: 0 auto 16px;
    }

    .mobile-menu-option {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        padding: 14px;
        background: none;
        border: none;
        color: #efeff1;
        font-size: 16px;
        cursor: pointer;
        border-radius: 8px;
        transition: background-color 0.15s;
    }

    .mobile-menu-option:hover {
        background-color: #3f3f46;
    }

    .mobile-menu-option.danger {
        color: #f87171;
    }

    .mobile-menu-option svg {
        width: 20px;
        height: 20px;
    }

    /* ==========================================
       Loading Skeleton
       ========================================== */

    .skeleton {
        background: linear-gradient(90deg, #27272a 25%, #3f3f46 50%, #27272a 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    .message-skeleton {
        display: flex;
        gap: 16px;
        padding: 8px 16px;
        margin: 4px 0;
    }

    .skeleton-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }

    .skeleton-content {
        flex: 1;
    }

    .skeleton-line {
        height: 16px;
        margin-bottom: 8px;
    }

    .skeleton-line.short {
        width: 30%;
    }

    .skeleton-line.medium {
        width: 60%;
    }

    .skeleton-line.long {
        width: 85%;
    }

    /* ==========================================
       Message Highlight Animation
       ========================================== */

    .message-highlight {
        animation: highlightFade 2s ease-out;
    }

    @keyframes highlightFade {
        0% { background-color: rgba(102, 126, 234, 0.3); }
        100% { background-color: transparent; }
    }

    /* ==========================================
       Screen Reader Only
       ========================================== */

    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    /* ==========================================
       Messages Container
       ========================================== */

    .messages-wrapper {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }

    #messages-container {
        flex: 1;
        padding: 16px;
        display: flex;
        flex-direction: column;
    }

    /* ==========================================
       Conversation Start Indicator
       ========================================== */

    .conversation-start {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 32px 16px;
        text-align: center;
        margin-bottom: 24px;
    }

    .conversation-start-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 16px;
    }

    .conversation-start-title {
        font-size: 24px;
        font-weight: 700;
        color: #efeff1;
        margin: 0 0 8px 0;
    }

    .conversation-start-subtitle {
        font-size: 14px;
        color: #71717a;
        margin: 0;
    }

    /* ==========================================
       Date Separators
       ========================================== */

    .date-separator {
        display: flex;
        align-items: center;
        margin: 24px 0;
    }

    .date-separator-line {
        flex: 1;
        height: 1px;
        background-color: #3f3f46;
    }

    .date-separator-text {
        padding: 0 16px;
        font-size: 12px;
        font-weight: 600;
        color: #71717a;
        white-space: nowrap;
    }

    /* ==========================================
       Message Styles
       ========================================== */

    .dm-message {
        display: flex;
        gap: 16px;
        padding: 4px 16px;
        margin: 2px 0;
        transition: background-color 0.1s;
        border-radius: 4px;
    }

    .dm-message:hover {
        background-color: rgba(255, 255, 255, 0.02);
    }

    .dm-message:hover .message-actions {
        opacity: 1 !important;
    }

    .dm-message-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        flex-shrink: 0;
        object-fit: cover;
    }

    .dm-message-content-wrapper {
        flex: 1;
        min-width: 0;
    }

    .dm-message-header {
        display: flex;
        align-items: baseline;
        gap: 8px;
        margin-bottom: 4px;
    }

    .dm-message-author {
        font-weight: 600;
        color: #efeff1;
        font-size: 15px;
    }

    .dm-message-timestamp {
        font-size: 12px;
        color: #71717a;
    }

    .dm-message-edited {
        font-size: 11px;
        color: #71717a;
        font-style: italic;
    }

    .dm-message-content {
        color: #d4d4d8;
        line-height: 1.5;
        word-wrap: break-word;
        white-space: pre-wrap;
        font-size: 15px;
    }

    /* Compact Message (same user within 5 minutes) */
    .dm-message.compact {
        padding: 2px 16px 2px 72px;
    }

    .dm-message.compact .dm-message-avatar {
        display: none;
    }

    .dm-message.compact .dm-message-header {
        display: none;
    }

    .dm-message.compact .dm-message-content-wrapper {
        position: relative;
    }

    .dm-message.compact .compact-timestamp {
        display: none;
        position: absolute;
        left: -56px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 11px;
        color: #52525b;
        width: 48px;
        text-align: right;
    }

    .dm-message.compact:hover .compact-timestamp {
        display: block;
    }

    /* ==========================================
       Message Actions (Edit/Delete)
       ========================================== */

    .message-actions {
        opacity: 0;
        transition: opacity 0.15s;
        flex-shrink: 0;
    }

    .dm-kebab-menu {
        position: relative;
        display: inline-block;
    }

    .dm-kebab-button {
        background: #27272a;
        border: 1px solid #3f3f46;
        color: #a1a1aa;
        font-size: 16px;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 4px;
        transition: all 0.15s;
    }

    .dm-kebab-button:hover {
        background-color: #3f3f46;
        color: #ffffff;
    }

    .dm-kebab-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #18181b;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        min-width: 120px;
        z-index: 1000;
        display: none;
        overflow: hidden;
    }

    .dm-kebab-dropdown.active {
        display: block;
    }

    .dm-kebab-option {
        display: block;
        width: 100%;
        padding: 10px 14px;
        background: none;
        border: none;
        color: #d4d4d8;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.15s;
        font-size: 14px;
    }

    .dm-kebab-option:hover {
        background-color: #27272a;
    }

    .dm-kebab-option.danger {
        color: #f87171;
    }

    .dm-kebab-option.danger:hover {
        background-color: rgba(239, 68, 68, 0.1);
    }

    /* ==========================================
       Typing Indicator
       ========================================== */

    .dm-typing-indicator {
        padding: 8px 16px;
        font-size: 13px;
        color: #71717a;
        display: none;
        align-items: center;
        gap: 8px;
    }

    .dm-typing-indicator.active {
        display: flex;
    }

    /* ==========================================
       Chat Input
       ========================================== */

    .dm-chat-input-container {
        padding: 16px;
        background-color: #0e0e10;
        border-top: 1px solid #27272a;
        flex-shrink: 0;
    }

    .chat-input-wrapper {
        display: flex;
        align-items: flex-end;
        gap: 12px;
        background-color: #27272a;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        padding: 4px 4px 4px 16px;
        transition: border-color 0.15s;
    }

    .chat-input-wrapper:focus-within {
        border-color: #667eea;
    }

    .dm-chat-input {
        flex: 1;
        background: none;
        border: none;
        color: #efeff1;
        font-size: 15px;
        resize: none;
        font-family: inherit;
        max-height: 120px;
        min-height: 40px;
        padding: 10px 0;
        line-height: 1.4;
    }

    .dm-chat-input:focus {
        outline: none;
    }

    .dm-chat-input::placeholder {
        color: #71717a;
    }

    .dm-chat-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .chat-input-footer {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .char-counter {
        font-size: 12px;
        color: #52525b;
        padding: 0 8px;
    }

    .char-counter.warning {
        color: #eab308;
    }

    .char-counter.danger {
        color: #ef4444;
    }

    .send-btn {
        background-color: #667eea;
        border: none;
        color: white;
        padding: 10px 12px;
        border-radius: 6px;
        cursor: pointer;
        transition: background-color 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .send-btn:hover {
        background-color: #5a6fd6;
    }

    .send-btn:disabled {
        background-color: #3f3f46;
        cursor: not-allowed;
    }

    .send-btn svg {
        width: 18px;
        height: 18px;
    }

    /* ==========================================
       Load More Button
       ========================================== */

    .load-more-btn {
        width: 100%;
        padding: 12px;
        background-color: #27272a;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        color: #a1a1aa;
        cursor: pointer;
        font-size: 14px;
        margin-bottom: 16px;
        transition: all 0.15s;
    }

    .load-more-btn:hover {
        background-color: #3f3f46;
        color: #efeff1;
    }

    .load-more-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* ==========================================
       Empty Messages State
       ========================================== */

    .dm-empty-messages {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        flex: 1;
        text-align: center;
        padding: 48px;
    }

    /* ==========================================
       Modals
       ========================================== */

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.8);
        backdrop-filter: blur(4px);
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .modal-overlay.active {
        display: flex;
    }

    .modal-content {
        background-color: #18181b;
        border-radius: 12px;
        padding: 24px;
        width: 90%;
        max-width: 480px;
        border: 1px solid #3f3f46;
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }

    .modal-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-icon.edit {
        background-color: rgba(102, 126, 234, 0.1);
        color: #667eea;
    }

    .modal-icon.warning {
        background-color: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .modal-icon svg {
        width: 20px;
        height: 20px;
    }

    .modal-title {
        font-size: 18px;
        font-weight: 600;
        color: #efeff1;
        margin: 0;
    }

    .modal-body {
        margin-bottom: 20px;
    }

    .modal-body p {
        color: #a1a1aa;
        font-size: 14px;
        margin: 0 0 16px 0;
    }

    .modal-textarea {
        width: 100%;
        min-height: 100px;
        padding: 12px;
        background-color: #0e0e10;
        border: 1px solid #3f3f46;
        border-radius: 8px;
        color: #efeff1;
        font-size: 15px;
        resize: vertical;
        font-family: inherit;
        line-height: 1.5;
    }

    .modal-textarea:focus {
        outline: none;
        border-color: #667eea;
    }

    .modal-footer {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    .modal-btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s;
    }

    .modal-btn-secondary {
        background-color: #3f3f46;
        border: none;
        color: #efeff1;
    }

    .modal-btn-secondary:hover {
        background-color: #52525b;
    }

    .modal-btn-primary {
        background-color: #667eea;
        border: none;
        color: white;
    }

    .modal-btn-primary:hover {
        background-color: #5a6fd6;
    }

    .modal-btn-danger {
        background-color: #ef4444;
        border: none;
        color: white;
    }

    .modal-btn-danger:hover {
        background-color: #dc2626;
    }

    /* ==========================================
       Responsive Design
       ========================================== */

    @media (max-width: 1279px) {
        .dm-sidebar-right {
            display: none !important;
        }

        .toggle-profile-btn {
            display: none;
        }
    }

    @media (max-width: 1023px) {
        .dm-sidebar-left {
            display: none;
        }

        .mobile-back-btn {
            display: flex;
        }
    }

    @media (max-width: 640px) {
        .dm-message {
            gap: 12px;
            padding: 4px 12px;
        }

        .dm-message-avatar {
            width: 32px;
            height: 32px;
        }

        .dm-message.compact {
            padding-left: 56px;
        }

        .header-avatar {
            width: 32px;
            height: 32px;
        }

        .dm-chat-input-container {
            padding: 12px;
        }
    }
</style>
@endpush

@section('content')
<!-- Connection Status Banner -->
<div id="connection-status" class="connection-status-banner">
    <span id="connection-message">Disconnected. Reconnecting...</span>
</div>

<div class="dm-layout">
    <!-- Left Panel: Conversations Sidebar (hidden on mobile) -->
    <div class="dm-sidebar-left">
        @include('direct-messages.partials.conversations-sidebar', ['conversation' => $conversation])
    </div>

    <!-- Center Panel: Chat Area -->
    <div class="dm-chat-main">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-left">
                <a href="{{ route('dm.index') }}" class="mobile-back-btn">
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div class="header-avatar-wrapper">
                    <img
                        src="{{ $otherParticipant->profile->avatar_url ?? '/images/default-avatar.png' }}"
                        alt="{{ $otherParticipant->display_name }}"
                        class="header-avatar"
                    >
                    <span class="header-status-dot {{ $otherParticipant->profile->status === 'online' ? 'online' : 'offline' }}"></span>
                </div>
                <div class="header-user-info">
                    <a href="{{ route('profile.show', $otherParticipant->username) }}" class="header-user-name">
                        {{ $otherParticipant->display_name }}
                    </a>
                    <span class="header-user-status">
                        @if($otherParticipant->profile->status === 'online')
                            @if($otherParticipant->profile->current_game)
                                Playing {{ $otherParticipant->profile->current_game['name'] ?? 'a game' }}
                            @else
                                Online
                            @endif
                        @else
                            Offline
                        @endif
                    </span>
                </div>
            </div>
            <div class="chat-header-right">
                {{-- Search Button --}}
                <div class="search-container">
                    <button
                        class="toggle-profile-btn"
                        onclick="toggleSearchDropdown()"
                        title="Search messages"
                        aria-label="Search messages"
                        aria-expanded="false"
                        aria-controls="search-dropdown"
                    >
                        <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <div id="search-dropdown" class="search-dropdown" role="dialog" aria-label="Message search">
                        <div class="search-input-wrapper">
                            <input
                                type="text"
                                id="message-search-input"
                                class="search-input"
                                placeholder="Search messages..."
                                aria-label="Search messages"
                                autocomplete="off"
                            >
                        </div>
                        <div id="search-results" class="search-results" role="listbox" aria-label="Search results"></div>
                    </div>
                </div>

                <button
                    id="toggle-profile-btn"
                    class="toggle-profile-btn"
                    onclick="toggleProfilePanel()"
                    title="Toggle user profile"
                    aria-label="Toggle user profile panel"
                    aria-expanded="false"
                >
                    <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="messages-wrapper dm-scrollable" role="log" aria-live="polite" aria-label="Message history">
            <div id="messages-container" aria-relevant="additions">
                @if($messages->count() >= 50)
                    <button id="load-more-btn" class="load-more-btn" onclick="loadMoreMessages()">
                        Load older messages
                    </button>
                @endif

                <!-- Conversation Start Indicator -->
                <div class="conversation-start">
                    <img
                        src="{{ $otherParticipant->profile->avatar_url ?? '/images/default-avatar.png' }}"
                        alt="{{ $otherParticipant->display_name }}"
                        class="conversation-start-avatar"
                    >
                    <h2 class="conversation-start-title">{{ $otherParticipant->display_name }}</h2>
                    <p class="conversation-start-subtitle">This is the beginning of your direct message history with {{ $otherParticipant->display_name }}.</p>
                </div>

                @php
                    $lastDate = null;
                    $lastSenderId = null;
                    $lastMessageTime = null;
                @endphp

                @forelse($messages as $message)
                    @php
                        $messageDate = $message->created_at->format('Y-m-d');
                        $isNewDate = $lastDate !== $messageDate;

                        // Check if this message should be compact
                        // Same sender within 5 minutes of previous message
                        $isCompact = false;
                        if (!$isNewDate && $lastSenderId === $message->sender_id && $lastMessageTime) {
                            $timeDiff = $message->created_at->diffInMinutes($lastMessageTime);
                            $isCompact = $timeDiff < 5;
                        }

                        $lastDate = $messageDate;
                        $lastSenderId = $message->sender_id;
                        $lastMessageTime = $message->created_at;
                    @endphp

                    @if($isNewDate)
                        <div class="date-separator">
                            <div class="date-separator-line"></div>
                            <span class="date-separator-text">{{ $message->created_at->format('F j, Y') }}</span>
                            <div class="date-separator-line"></div>
                        </div>
                    @endif

                    <div class="dm-message message-item {{ $isCompact ? 'compact' : '' }}"
                         data-message-id="{{ $message->id }}"
                         data-sender-id="{{ $message->sender_id }}"
                         data-timestamp="{{ $message->created_at->toIso8601String() }}">

                        @if(!$isCompact)
                            <img
                                src="{{ $message->sender->profile->avatar_url ?? '/images/default-avatar.png' }}"
                                alt="{{ $message->sender->display_name }}"
                                class="dm-message-avatar"
                            >
                        @endif

                        <div class="dm-message-content-wrapper">
                            @if(!$isCompact)
                                <div class="dm-message-header">
                                    <span class="dm-message-author">{{ $message->sender->display_name }}</span>
                                    <span class="dm-message-timestamp" data-timestamp="{{ $message->created_at->toIso8601String() }}">
                                        {{ $message->created_at->format('g:i A') }}
                                    </span>
                                    @if($message->is_edited)
                                        <span class="dm-message-edited">(edited)</span>
                                    @endif
                                </div>
                            @else
                                <span class="compact-timestamp">{{ $message->created_at->format('g:i A') }}</span>
                            @endif
                            <div class="dm-message-content">{{ $message->content }}</div>
                            @if($isCompact && $message->is_edited)
                                <span class="dm-message-edited">(edited)</span>
                            @endif
                        </div>

                        @if($message->sender_id === auth()->id())
                            <div class="message-actions" style="opacity: 0;">
                                <div class="dm-kebab-menu">
                                    <button class="dm-kebab-button" onclick="toggleMessageMenu({{ $message->id }})" title="More options">
                                        <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                                        </svg>
                                    </button>
                                    <div class="dm-kebab-dropdown" id="message-menu-{{ $message->id }}">
                                        <button class="dm-kebab-option" onclick="openEditModal({{ $message->id }})">Edit</button>
                                        <button class="dm-kebab-option danger" onclick="openDeleteModal({{ $message->id }})">Delete</button>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    {{-- Empty state is handled by conversation start indicator --}}
                @endforelse
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="typing-indicator" class="dm-typing-indicator">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span id="typing-user"></span> is typing...
        </div>

        <!-- Chat Input -->
        <div class="dm-chat-input-container">
            <form id="message-form" onsubmit="sendMessage(event)" role="form" aria-label="Send message">
                <div class="chat-input-wrapper">
                    {{-- Emoji Picker --}}
                    <div class="emoji-container">
                        <button type="button" class="emoji-btn" onclick="toggleEmojiPicker()" title="Add emoji" aria-label="Open emoji picker" aria-expanded="false" aria-controls="emoji-picker">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                        <div id="emoji-picker" class="emoji-picker" role="dialog" aria-label="Emoji picker">
                            <div class="emoji-grid" role="listbox">
                                @foreach(['😀','😂','😍','🤔','😎','👍','👎','❤️','🔥','✨','🎮','🎯','💯','🙌','😢','😡','🤣','😊','🥰','😋','🤩','😏','😴','🥳','😤','🤗','🤫','🤭','😱','😈','💀','👻','🤡','💩','🙈','🙉','🙊','💪','👏','🤝','✌️','🤞','🖐️','👋','🎉','🏆'] as $emoji)
                                    <button type="button" class="emoji-item" onclick="insertEmoji('{{ $emoji }}')" role="option" aria-label="{{ $emoji }}">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <textarea
                        id="message-input"
                        class="dm-chat-input"
                        placeholder="Message {{ $otherParticipant->display_name }}..."
                        rows="1"
                        maxlength="2000"
                        onkeydown="handleKeyDown(event)"
                        oninput="handleTyping(); updateCharCounter();"
                        aria-label="Message input"
                        aria-describedby="char-counter"
                    ></textarea>
                    <div class="chat-input-footer">
                        <span id="char-counter" class="char-counter" aria-live="polite">0/2000</span>
                        <button type="submit" class="send-btn" id="send-btn" title="Send message" aria-label="Send message">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Screen reader announcer for new messages --}}
        <div id="message-announcer" class="sr-only" aria-live="assertive" aria-atomic="true"></div>
    </div>

    <!-- Right Panel: User Profile (toggleable on xl+) -->
    <div id="profile-panel" class="dm-sidebar-right">
        @include('direct-messages.partials.user-profile-panel', ['user' => $otherParticipant])
    </div>
</div>

<!-- Edit Message Modal -->
<div id="edit-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon edit">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </div>
            <h3 class="modal-title">Edit Message</h3>
        </div>
        <div class="modal-body">
            <form id="edit-form" onsubmit="saveEdit(event)">
                <textarea
                    id="edit-content"
                    class="modal-textarea"
                    placeholder="Edit your message..."
                    maxlength="2000"
                    required
                ></textarea>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-secondary" onclick="closeEditModal()">Cancel</button>
            <button type="submit" form="edit-form" class="modal-btn modal-btn-primary" id="edit-save-btn">Save Changes</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon warning">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="modal-title">Delete Message</h3>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this message? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="modal-btn modal-btn-danger" id="delete-confirm-btn" onclick="confirmDelete()">Delete</button>
        </div>
    </div>
</div>

<!-- Mobile Message Actions Menu (Bottom Sheet) -->
<div id="mobile-message-menu" class="mobile-menu-overlay" role="dialog" aria-label="Message actions">
    <div class="mobile-menu-content">
        <div class="mobile-menu-handle"></div>
        <button class="mobile-menu-option" onclick="openEditFromMobile()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Message
        </button>
        <button class="mobile-menu-option danger" onclick="openDeleteFromMobile()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete Message
        </button>
        <button class="mobile-menu-option" onclick="closeMobileMenu()">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Cancel
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
// ==========================================
// Global Variables
// ==========================================

const messagesContainer = document.getElementById('messages-container');
const messagesWrapper = document.querySelector('.messages-wrapper');
const messageInput = document.getElementById('message-input');
const typingIndicator = document.getElementById('typing-indicator');
const typingUser = document.getElementById('typing-user');
const connectionStatus = document.getElementById('connection-status');
const connectionMessage = document.getElementById('connection-message');
const charCounter = document.getElementById('char-counter');

const currentUserId = {{ auth()->id() }};
const conversationId = {{ $conversation->id }};
const otherUserName = @json($otherParticipant->display_name);
const otherUserId = {{ $otherParticipant->id }};

let currentEditingMessage = null;
let currentDeletingMessage = null;
let typingTimeout = null;
let isTyping = false;
let oldestMessageId = {{ $messages->first()?->id ?? 'null' }};
let hasMoreMessages = {{ $messages->count() >= 50 ? 'true' : 'false' }};
let isConnected = false;
let messageRetryQueue = [];
let searchTimeout = null;
let mobileMenuMessageId = null;
let lastReadMessageId = null;

// Request browser notification permission on load
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().then(permission => {
        console.log('[DM] Notification permission:', permission);
    });
}

// ==========================================
// WebSocket Connection Management
// ==========================================

let dmChannelSubscribed = false;

function setupDMChannel() {
    if (dmChannelSubscribed) {
        console.log('[DM] Channel already subscribed, skipping...');
        return;
    }

    if (!window.Echo) {
        console.warn('[DM] Echo not available yet, waiting for initialization...');
        return;
    }

    console.log('[DM] Setting up Echo listener for user DM channel:', currentUserId);
    console.log('[DM] Current conversation ID:', conversationId);

    dmChannelSubscribed = true;

    // Subscribe to user's personal DM channel
    const dmChannel = window.Echo.private(`dm.user.${currentUserId}`);

    dmChannel.listen('.dm.message.posted', (e) => {
        console.log('[DM] Received message broadcast:', e);
        if (e.conversation_id === conversationId) {
            if (e.message.sender_id !== currentUserId) {
                appendMessage(e.message);
                markAsRead();
                announceMessage(e.message);
            }
        } else {
            // Different conversation - show browser notification
            showBrowserNotification(e.message);
        }
    })
    .listen('.dm.message.edited', (e) => {
        console.log('[DM] Received edit broadcast:', e);
        if (e.conversation_id === conversationId) {
            updateMessage(e.message);
        }
    })
    .listen('.dm.message.deleted', (e) => {
        console.log('[DM] Received delete broadcast:', e);
        if (e.conversation_id === conversationId) {
            removeMessage(e.message_id);
        }
    })
    .listen('.dm.user.typing', (e) => {
        console.log('[DM] Received typing broadcast:', e);
        if (e.conversation_id === conversationId && e.user.id !== currentUserId) {
            showTypingIndicator(e.user.display_name, e.is_typing);
        }
    })
    .listen('.dm.read', (e) => {
        console.log('[DM] Received read receipt:', e);
        if (e.conversation_id === conversationId) {
            // Update read receipts for all own messages
            updateReadReceipts(e.read_at);
        }
    })
    .error((error) => {
        console.error('[DM] Echo channel error:', error);
    });

    console.log('[DM] Channel subscription initiated');
}

function updateConnectionStatus(connected, reconnecting = false) {
    isConnected = connected;

    if (connected) {
        connectionStatus.classList.remove('disconnected', 'reconnecting');
        connectionStatus.style.display = 'none';
        // Process retry queue
        processRetryQueue();
    } else if (reconnecting) {
        connectionStatus.classList.remove('disconnected');
        connectionStatus.classList.add('reconnecting');
        connectionMessage.textContent = 'Reconnecting...';
    } else {
        connectionStatus.classList.remove('reconnecting');
        connectionStatus.classList.add('disconnected');
        connectionMessage.textContent = 'Disconnected. Reconnecting...';
    }
}

function processRetryQueue() {
    while (messageRetryQueue.length > 0) {
        const content = messageRetryQueue.shift();
        sendMessageToServer(content);
    }
}

// Initialize Echo connection
if (window.Echo && window.Echo.connector && window.Echo.connector.pusher) {
    const pusherConnection = window.Echo.connector.pusher.connection;

    if (pusherConnection.state === 'connected') {
        console.log('[DM] Echo already connected, setting up channel...');
        setupDMChannel();
        updateConnectionStatus(true);
    } else {
        console.log('[DM] Waiting for Echo connection (current state:', pusherConnection.state, ')');
        pusherConnection.bind('connected', () => {
            console.log('[DM] WebSocket connected, setting up channel...');
            setupDMChannel();
            updateConnectionStatus(true);
        });
    }

    pusherConnection.bind('disconnected', () => {
        console.warn('[DM] WebSocket disconnected');
        updateConnectionStatus(false);
    });

    pusherConnection.bind('connecting', () => {
        console.log('[DM] WebSocket connecting...');
        updateConnectionStatus(false, true);
    });

    pusherConnection.bind('unavailable', () => {
        console.error('[DM] WebSocket unavailable');
        updateConnectionStatus(false);
    });

    pusherConnection.bind('failed', () => {
        console.error('[DM] WebSocket connection failed');
        updateConnectionStatus(false);
    });
} else {
    console.log('[DM] Echo not initialized yet, waiting for echo:connected event...');

    window.addEventListener('echo:connected', () => {
        console.log('[DM] Received echo:connected event, setting up channel...');
        setupDMChannel();
        updateConnectionStatus(true);
    });

    window.addEventListener('echo:failed', () => {
        console.error('[DM] Echo initialization failed');
        updateConnectionStatus(false);
    });
}

// ==========================================
// Profile Panel Toggle
// ==========================================

function toggleProfilePanel() {
    const panel = document.getElementById('profile-panel');
    const btn = document.getElementById('toggle-profile-btn');

    panel.classList.toggle('visible');
    btn.classList.toggle('active');

    // Save preference
    localStorage.setItem('dm-profile-panel-visible', panel.classList.contains('visible'));
}

// Restore profile panel state
document.addEventListener('DOMContentLoaded', function() {
    const savedState = localStorage.getItem('dm-profile-panel-visible');
    if (savedState === 'true' && window.innerWidth >= 1280) {
        document.getElementById('profile-panel').classList.add('visible');
        document.getElementById('toggle-profile-btn').classList.add('active');
    }
});

// ==========================================
// Character Counter
// ==========================================

function updateCharCounter() {
    const length = messageInput.value.length;
    charCounter.textContent = `${length}/2000`;

    if (length >= 1900) {
        charCounter.classList.add('danger');
        charCounter.classList.remove('warning');
    } else if (length >= 1500) {
        charCounter.classList.add('warning');
        charCounter.classList.remove('danger');
    } else {
        charCounter.classList.remove('warning', 'danger');
    }
}

// ==========================================
// Message Input Handling
// ==========================================

function handleKeyDown(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage(event);
    }
}

function handleTyping() {
    if (!isTyping) {
        isTyping = true;
        sendTypingIndicator(true);
    }

    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }

    typingTimeout = setTimeout(() => {
        isTyping = false;
        sendTypingIndicator(false);
    }, 2000);
}

function sendTypingIndicator(typing) {
    fetch(`/dm/${conversationId}/typing`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ is_typing: typing })
    }).catch(err => console.log('Typing indicator error:', err));
}

function showTypingIndicator(userName, isTyping) {
    if (isTyping) {
        typingUser.textContent = userName;
        typingIndicator.classList.add('active');
    } else {
        typingIndicator.classList.remove('active');
    }
}

// Auto-resize textarea
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// ==========================================
// Send Message
// ==========================================

function sendMessage(event) {
    event.preventDefault();

    const content = messageInput.value.trim();
    if (!content) return;

    messageInput.disabled = true;
    document.getElementById('send-btn').disabled = true;

    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
    isTyping = false;
    sendTypingIndicator(false);

    if (!isConnected) {
        // Queue message for retry
        messageRetryQueue.push(content);
        showToast('Message queued. Will send when reconnected.');
        messageInput.value = '';
        messageInput.style.height = 'auto';
        updateCharCounter();
        messageInput.disabled = false;
        document.getElementById('send-btn').disabled = false;
        return;
    }

    sendMessageToServer(content);
}

function sendMessageToServer(content) {
    fetch(`/dm/${conversationId}/messages`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ content })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            appendMessage(data.message);
            messageInput.value = '';
            messageInput.style.height = 'auto';
            updateCharCounter();
        } else {
            showToast(data.error || 'Failed to send message', 'error');
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        // Queue for retry
        messageRetryQueue.push(content);
        showToast('Failed to send. Will retry when connected.', 'error');
    })
    .finally(() => {
        messageInput.disabled = false;
        document.getElementById('send-btn').disabled = false;
        messageInput.focus();
    });
}

// ==========================================
// Message Rendering
// ==========================================

function appendMessage(message) {
    // Check if message already exists
    const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
    if (existingMessage) {
        console.log('Message already exists, skipping:', message.id);
        return;
    }

    // Check if we need a date separator
    const lastMessage = messagesContainer.querySelector('.dm-message:last-child');
    let needsDateSeparator = true;
    let isCompact = false;

    if (lastMessage) {
        const lastTimestamp = new Date(lastMessage.dataset.timestamp);
        const newTimestamp = new Date(message.created_at);

        // Same day?
        if (lastTimestamp.toDateString() === newTimestamp.toDateString()) {
            needsDateSeparator = false;

            // Check for compact mode (same sender within 5 minutes)
            const lastSenderId = parseInt(lastMessage.dataset.senderId);
            const timeDiff = (newTimestamp - lastTimestamp) / (1000 * 60);

            if (lastSenderId === message.sender_id && timeDiff < 5) {
                isCompact = true;
            }
        }
    }

    // Add date separator if needed
    if (needsDateSeparator) {
        const dateDiv = document.createElement('div');
        dateDiv.className = 'date-separator';
        const msgDate = new Date(message.created_at);
        dateDiv.innerHTML = `
            <div class="date-separator-line"></div>
            <span class="date-separator-text">${formatDateLong(msgDate)}</span>
            <div class="date-separator-line"></div>
        `;
        messagesContainer.appendChild(dateDiv);
    }

    const messageDiv = document.createElement('div');
    messageDiv.className = `dm-message message-item ${isCompact ? 'compact' : ''}`;
    messageDiv.dataset.messageId = message.id;
    messageDiv.dataset.senderId = message.sender_id;
    messageDiv.dataset.timestamp = message.created_at;

    const time = formatTime(message.created_at);
    const editedTag = message.is_edited ? '<span class="dm-message-edited">(edited)</span>' : '';

    const actionsHtml = message.sender_id === currentUserId ? `
        <div class="message-actions" style="opacity: 0;">
            <div class="dm-kebab-menu">
                <button class="dm-kebab-button" onclick="toggleMessageMenu(${message.id})" title="More options">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                </button>
                <div class="dm-kebab-dropdown" id="message-menu-${message.id}">
                    <button class="dm-kebab-option" onclick="openEditModal(${message.id})">Edit</button>
                    <button class="dm-kebab-option danger" onclick="openDeleteModal(${message.id})">Delete</button>
                </div>
            </div>
        </div>
    ` : '';

    if (isCompact) {
        messageDiv.innerHTML = `
            <div class="dm-message-content-wrapper">
                <span class="compact-timestamp">${time}</span>
                <div class="dm-message-content">${escapeHtml(message.content)}</div>
                ${editedTag}
            </div>
            ${actionsHtml}
        `;
    } else {
        messageDiv.innerHTML = `
            <img src="${message.sender.avatar_url || '/images/default-avatar.png'}" alt="${escapeHtml(message.sender.display_name)}" class="dm-message-avatar">
            <div class="dm-message-content-wrapper">
                <div class="dm-message-header">
                    <span class="dm-message-author">${escapeHtml(message.sender.display_name)}</span>
                    <span class="dm-message-timestamp" data-timestamp="${message.created_at}">${time}</span>
                    ${editedTag}
                </div>
                <div class="dm-message-content">${escapeHtml(message.content)}</div>
            </div>
            ${actionsHtml}
        `;
    }

    messagesContainer.appendChild(messageDiv);
    scrollToBottom();
}

function updateMessage(message) {
    const messageElement = document.querySelector(`[data-message-id="${message.id}"]`);
    if (!messageElement) return;

    const messageContent = messageElement.querySelector('.dm-message-content');
    messageContent.textContent = message.content;

    // Update or add edited tag
    let editedTag = messageElement.querySelector('.dm-message-edited');
    if (message.is_edited && !editedTag) {
        editedTag = document.createElement('span');
        editedTag.className = 'dm-message-edited';
        editedTag.textContent = '(edited)';

        if (messageElement.classList.contains('compact')) {
            messageElement.querySelector('.dm-message-content-wrapper').appendChild(editedTag);
        } else {
            messageElement.querySelector('.dm-message-header').appendChild(editedTag);
        }
    }
}

function removeMessage(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (messageElement) {
        messageElement.remove();
    }
}

function prependMessage(message) {
    const existingMessage = document.querySelector(`[data-message-id="${message.id}"]`);
    if (existingMessage) return;

    // Find the right position to insert (after load more button or at beginning)
    const loadMoreBtn = document.getElementById('load-more-btn');
    const conversationStart = document.querySelector('.conversation-start');

    const messageDiv = document.createElement('div');
    messageDiv.className = 'dm-message message-item';
    messageDiv.dataset.messageId = message.id;
    messageDiv.dataset.senderId = message.sender_id;
    messageDiv.dataset.timestamp = message.created_at;

    const time = formatTime(message.created_at);
    const editedTag = message.is_edited ? '<span class="dm-message-edited">(edited)</span>' : '';

    const actionsHtml = message.sender_id === currentUserId ? `
        <div class="message-actions" style="opacity: 0;">
            <div class="dm-kebab-menu">
                <button class="dm-kebab-button" onclick="toggleMessageMenu(${message.id})" title="More options">
                    <svg style="width: 16px; height: 16px;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                    </svg>
                </button>
                <div class="dm-kebab-dropdown" id="message-menu-${message.id}">
                    <button class="dm-kebab-option" onclick="openEditModal(${message.id})">Edit</button>
                    <button class="dm-kebab-option danger" onclick="openDeleteModal(${message.id})">Delete</button>
                </div>
            </div>
        </div>
    ` : '';

    messageDiv.innerHTML = `
        <img src="${message.sender.avatar_url || '/images/default-avatar.png'}" alt="${escapeHtml(message.sender.display_name)}" class="dm-message-avatar">
        <div class="dm-message-content-wrapper">
            <div class="dm-message-header">
                <span class="dm-message-author">${escapeHtml(message.sender.display_name)}</span>
                <span class="dm-message-timestamp" data-timestamp="${message.created_at}">${time}</span>
                ${editedTag}
            </div>
            <div class="dm-message-content">${escapeHtml(message.content)}</div>
        </div>
        ${actionsHtml}
    `;

    // Insert after conversation start or load more button
    if (conversationStart && conversationStart.nextElementSibling) {
        conversationStart.after(messageDiv);
    } else if (loadMoreBtn) {
        loadMoreBtn.after(messageDiv);
    } else {
        messagesContainer.prepend(messageDiv);
    }
}

// ==========================================
// Utility Functions
// ==========================================

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(isoString) {
    const date = new Date(isoString);
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

function formatDateLong(date) {
    return date.toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric'
    });
}

function scrollToBottom() {
    messagesWrapper.scrollTop = messagesWrapper.scrollHeight;
}

function showToast(message, type = 'info') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        bottom: 100px;
        left: 50%;
        transform: translateX(-50%);
        background-color: ${type === 'error' ? '#ef4444' : '#27272a'};
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        z-index: 3000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    `;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// ==========================================
// Read Receipts
// ==========================================

function updateReadReceipts(readAt) {
    // Add "Seen" indicator to all own messages that don't have it yet
    document.querySelectorAll(`.dm-message[data-sender-id="${currentUserId}"]`).forEach(el => {
        if (!el.querySelector('.read-receipt')) {
            const contentWrapper = el.querySelector('.dm-message-content-wrapper');
            if (contentWrapper) {
                const readReceipt = document.createElement('div');
                readReceipt.className = 'read-receipt seen';
                readReceipt.title = readAt ? `Seen ${formatDateTime(readAt)}` : 'Seen';
                readReceipt.innerHTML = `
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                    Seen
                `;
                contentWrapper.appendChild(readReceipt);
            }
        }
    });
}

function formatDateTime(isoString) {
    const date = new Date(isoString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    });
}

// ==========================================
// Browser Notifications
// ==========================================

function showBrowserNotification(message) {
    // Only show if tab is not focused and permission granted
    if (document.hidden && Notification.permission === 'granted') {
        const notification = new Notification(message.sender.display_name, {
            body: message.content.substring(0, 100) + (message.content.length > 100 ? '...' : ''),
            icon: message.sender.avatar_url || '/images/default-avatar.png',
            tag: `dm-${message.conversation_id}`, // Prevents multiple notifications
            silent: false,
        });

        notification.onclick = function() {
            window.focus();
            window.location.href = `/dm/${message.conversation_id}`;
            notification.close();
        };

        // Auto close after 5 seconds
        setTimeout(() => notification.close(), 5000);
    }
}

function announceMessage(message) {
    const announcer = document.getElementById('message-announcer');
    if (announcer) {
        announcer.textContent = `New message from ${message.sender.display_name}: ${message.content.substring(0, 100)}`;
        setTimeout(() => announcer.textContent = '', 1000);
    }
}

// ==========================================
// Message Search
// ==========================================

function toggleSearchDropdown() {
    const dropdown = document.getElementById('search-dropdown');
    const isOpen = dropdown.classList.toggle('active');

    const btn = dropdown.previousElementSibling;
    if (btn) btn.setAttribute('aria-expanded', isOpen);

    if (isOpen) {
        const input = document.getElementById('message-search-input');
        input.focus();
        input.value = '';
        document.getElementById('search-results').innerHTML = '';
    }
}

document.getElementById('message-search-input')?.addEventListener('input', function(e) {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();

    if (query.length < 2) {
        document.getElementById('search-results').innerHTML = '';
        return;
    }

    searchTimeout = setTimeout(() => searchMessages(query), 300);
});

async function searchMessages(query) {
    const resultsEl = document.getElementById('search-results');
    resultsEl.innerHTML = '<div class="search-no-results">Searching...</div>';

    try {
        const response = await fetch(`/dm/${conversationId}/search?q=${encodeURIComponent(query)}`, {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();

        if (data.messages && data.messages.length > 0) {
            resultsEl.innerHTML = data.messages.map(msg => `
                <div class="search-result-item" onclick="jumpToMessage(${msg.id})" role="option" tabindex="0">
                    <div class="search-result-meta">${escapeHtml(msg.sender.display_name)} - ${formatDateTime(msg.created_at)}</div>
                    <div class="search-result-content">${highlightText(escapeHtml(msg.content), query)}</div>
                </div>
            `).join('');
        } else {
            resultsEl.innerHTML = '<div class="search-no-results">No messages found</div>';
        }
    } catch (error) {
        console.error('Search error:', error);
        resultsEl.innerHTML = '<div class="search-no-results">Search failed. Try again.</div>';
    }
}

function highlightText(text, query) {
    const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return text.replace(regex, '<mark>$1</mark>');
}

function jumpToMessage(messageId) {
    const el = document.querySelector(`[data-message-id="${messageId}"]`);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        el.classList.add('message-highlight');
        setTimeout(() => el.classList.remove('message-highlight'), 2000);
        toggleSearchDropdown(); // Close search dropdown
    } else {
        showToast('Message may be on a previous page. Loading...', 'info');
        // Could implement loading older messages until we find it
    }
}

// Close search on click outside
document.addEventListener('click', function(event) {
    const searchContainer = document.querySelector('.search-container');
    if (searchContainer && !searchContainer.contains(event.target)) {
        const dropdown = document.getElementById('search-dropdown');
        if (dropdown.classList.contains('active')) {
            dropdown.classList.remove('active');
        }
    }
});

// ==========================================
// Emoji Picker
// ==========================================

function toggleEmojiPicker() {
    const picker = document.getElementById('emoji-picker');
    const isOpen = picker.classList.toggle('active');

    const btn = picker.previousElementSibling;
    if (btn) btn.setAttribute('aria-expanded', isOpen);
}

function insertEmoji(emoji) {
    const input = document.getElementById('message-input');
    const start = input.selectionStart;
    const end = input.selectionEnd;
    const text = input.value;

    input.value = text.substring(0, start) + emoji + text.substring(end);
    input.selectionStart = input.selectionEnd = start + emoji.length;
    input.focus();

    // Trigger input event to update character count
    input.dispatchEvent(new Event('input'));

    toggleEmojiPicker();
}

// Close emoji picker on click outside
document.addEventListener('click', function(event) {
    const emojiContainer = document.querySelector('.emoji-container');
    if (emojiContainer && !emojiContainer.contains(event.target)) {
        const picker = document.getElementById('emoji-picker');
        if (picker.classList.contains('active')) {
            picker.classList.remove('active');
        }
    }
});

// ==========================================
// Mobile Touch Menu
// ==========================================

let pressTimer;
const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

if (isTouchDevice) {
    document.addEventListener('touchstart', function(e) {
        const messageEl = e.target.closest('.dm-message[data-sender-id="' + currentUserId + '"]');
        if (messageEl) {
            pressTimer = setTimeout(() => {
                showMobileMenu(messageEl.dataset.messageId);
            }, 500);
        }
    }, { passive: true });

    document.addEventListener('touchend', function() {
        clearTimeout(pressTimer);
    });

    document.addEventListener('touchmove', function() {
        clearTimeout(pressTimer);
    });
}

function showMobileMenu(messageId) {
    mobileMenuMessageId = messageId;
    document.getElementById('mobile-message-menu').classList.add('active');
    // Haptic feedback if available
    if (navigator.vibrate) {
        navigator.vibrate(50);
    }
}

function closeMobileMenu() {
    document.getElementById('mobile-message-menu').classList.remove('active');
    mobileMenuMessageId = null;
}

function openEditFromMobile() {
    closeMobileMenu();
    if (mobileMenuMessageId) {
        openEditModal(mobileMenuMessageId);
    }
}

function openDeleteFromMobile() {
    closeMobileMenu();
    if (mobileMenuMessageId) {
        openDeleteModal(mobileMenuMessageId);
    }
}

// Close mobile menu on tap outside
document.getElementById('mobile-message-menu')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeMobileMenu();
    }
});

// ==========================================
// Enhanced Error Handling & Retry
// ==========================================

async function sendMessageWithRetry(content, retries = 3) {
    for (let i = 0; i < retries; i++) {
        try {
            const response = await fetch(`/dm/${conversationId}/messages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ content }),
            });

            if (response.ok) {
                return await response.json();
            }

            throw new Error(`HTTP ${response.status}`);
        } catch (error) {
            console.error(`Attempt ${i + 1} failed:`, error);

            if (i === retries - 1) {
                throw error;
            }

            // Wait before retrying (exponential backoff)
            await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
        }
    }
}

function markMessageAsFailed(tempId) {
    const el = document.querySelector(`[data-temp-id="${tempId}"]`);
    if (el) {
        el.classList.add('opacity-50');
        el.insertAdjacentHTML('beforeend', `
            <button onclick="retryMessage('${tempId}')"
                    class="text-red-400 text-xs hover:underline ml-2">
                Failed to send. Click to retry.
            </button>
        `);
    }
}

// ==========================================
// Message Menu
// ==========================================

function toggleMessageMenu(messageId) {
    document.querySelectorAll('.dm-kebab-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'message-menu-' + messageId) {
            dropdown.classList.remove('active');
        }
    });

    const menu = document.getElementById('message-menu-' + messageId);
    menu.classList.toggle('active');
}

document.addEventListener('click', function(event) {
    if (!event.target.closest('.dm-kebab-menu')) {
        document.querySelectorAll('.dm-kebab-dropdown').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    }
});

// ==========================================
// Edit Modal
// ==========================================

function openEditModal(messageId) {
    const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
    if (!messageElement) return;

    const messageContent = messageElement.querySelector('.dm-message-content').textContent;
    const editModal = document.getElementById('edit-modal');
    const editContent = document.getElementById('edit-content');

    currentEditingMessage = messageId;
    editContent.value = messageContent;
    editModal.classList.add('active');
    editContent.focus();

    // Close menu
    const menu = document.getElementById('message-menu-' + messageId);
    if (menu) menu.classList.remove('active');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.remove('active');
    currentEditingMessage = null;
}

function saveEdit(event) {
    event.preventDefault();

    if (!currentEditingMessage) return;

    const editContent = document.getElementById('edit-content');
    const newContent = editContent.value.trim();

    if (!newContent) {
        showToast('Message cannot be empty', 'error');
        return;
    }

    const saveBtn = document.getElementById('edit-save-btn');
    saveBtn.disabled = true;
    saveBtn.textContent = 'Saving...';

    fetch(`/dm/${conversationId}/messages/${currentEditingMessage}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ content: newContent })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateMessage(data.message);
            closeEditModal();
        } else {
            showToast(data.error || 'Failed to edit message', 'error');
        }
    })
    .catch(error => {
        console.error('Error editing message:', error);
        showToast('Failed to edit message. Please try again.', 'error');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';
    });
}

// ==========================================
// Delete Modal
// ==========================================

function openDeleteModal(messageId) {
    currentDeletingMessage = messageId;
    document.getElementById('delete-modal').classList.add('active');

    // Close menu
    const menu = document.getElementById('message-menu-' + messageId);
    if (menu) menu.classList.remove('active');
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.remove('active');
    currentDeletingMessage = null;
}

function confirmDelete() {
    if (!currentDeletingMessage) return;

    const deleteBtn = document.getElementById('delete-confirm-btn');
    deleteBtn.disabled = true;
    deleteBtn.textContent = 'Deleting...';

    fetch(`/dm/${conversationId}/messages/${currentDeletingMessage}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            removeMessage(data.message_id);
            closeDeleteModal();
        } else {
            showToast(data.error || 'Failed to delete message', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting message:', error);
        showToast('Failed to delete message. Please try again.', 'error');
    })
    .finally(() => {
        deleteBtn.disabled = false;
        deleteBtn.textContent = 'Delete';
    });
}

// ==========================================
// Load More Messages
// ==========================================

function loadMoreMessages() {
    if (!hasMoreMessages || !oldestMessageId) return;

    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.disabled = true;
        loadMoreBtn.textContent = 'Loading...';
    }

    fetch(`/dm/${conversationId}/messages/more?before_id=${oldestMessageId}`, {
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.messages.length > 0) {
            const scrollHeightBefore = messagesWrapper.scrollHeight;

            data.messages.forEach(message => {
                prependMessage(message);
            });

            oldestMessageId = data.messages[0].id;
            hasMoreMessages = data.has_more;

            // Maintain scroll position
            const scrollHeightAfter = messagesWrapper.scrollHeight;
            messagesWrapper.scrollTop = scrollHeightAfter - scrollHeightBefore;
        }

        if (!hasMoreMessages && loadMoreBtn) {
            loadMoreBtn.remove();
        }
    })
    .catch(error => {
        console.error('Error loading more messages:', error);
        showToast('Failed to load messages', 'error');
    })
    .finally(() => {
        if (loadMoreBtn && hasMoreMessages) {
            loadMoreBtn.disabled = false;
            loadMoreBtn.textContent = 'Load older messages';
        }
    });
}

// ==========================================
// Mark as Read
// ==========================================

function markAsRead() {
    fetch(`/dm/${conversationId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        }
    }).catch(err => console.log('Mark as read error:', err));
}

// ==========================================
// Modal Event Listeners
// ==========================================

document.getElementById('edit-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeEditModal();
    }
});

document.getElementById('delete-modal').addEventListener('click', function(event) {
    if (event.target === this) {
        closeDeleteModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        if (currentEditingMessage) {
            closeEditModal();
        }
        if (currentDeletingMessage) {
            closeDeleteModal();
        }
    }
});

// ==========================================
// Initialize
// ==========================================

// Scroll to bottom on load
scrollToBottom();

// Mark messages as read on page load
markAsRead();

// Initialize character counter
updateCharCounter();
</script>
@endpush
