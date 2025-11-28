{{--
    Discord-style Notification Dropdown Component
    Phase 3: Server Header & Navigation

    A notification dropdown with:
    - Recent mentions
    - Role mentions
    - DM notifications
    - Mark all as read button
    - Tabs for filtering

    @param Server $server - The current server
    @param Channel|null $channel - The current channel
--}}

@props([
    'server',
    'channel' => null,
])

<div
    {{ $attributes->merge(['class' => 'notification-dropdown']) }}
    x-data="notificationDropdown()"
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 transform scale-100"
    x-transition:leave-end="opacity-0 transform scale-95"
    x-cloak
>
    {{-- Header --}}
    <div class="notification-header">
        <h3 class="notification-title">Notifications</h3>
        <button
            class="notification-mark-read"
            @click="markAllAsRead()"
            x-show="notifications.length > 0"
        >
            Mark all as read
        </button>
    </div>

    {{-- Tabs --}}
    <div class="notification-tabs">
        <button
            class="notification-tab"
            :class="{ 'active': activeTab === 'all' }"
            @click="activeTab = 'all'"
        >
            All
        </button>
        <button
            class="notification-tab"
            :class="{ 'active': activeTab === 'mentions' }"
            @click="activeTab = 'mentions'"
        >
            Mentions
        </button>
        <button
            class="notification-tab"
            :class="{ 'active': activeTab === 'dms' }"
            @click="activeTab = 'dms'"
        >
            DMs
        </button>
    </div>

    {{-- Notification List --}}
    <div class="notification-list">
        <template x-if="loading">
            <div class="notification-empty">
                <svg class="animate-spin" style="width: 32px; height: 32px; margin: 0 auto 8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Loading...</span>
            </div>
        </template>

        <template x-if="!loading && filteredNotifications.length === 0">
            <div class="notification-empty">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span>No notifications</span>
            </div>
        </template>

        <template x-if="!loading && filteredNotifications.length > 0">
            <div>
                <template x-for="notification in filteredNotifications" :key="notification.id">
                    <div
                        class="notification-item"
                        :class="{ 'unread': !notification.read }"
                        @click="goToNotification(notification)"
                    >
                        <img
                            :src="notification.sender.avatar_url"
                            :alt="notification.sender.display_name"
                            class="notification-avatar"
                        >
                        <div class="notification-content">
                            <div class="notification-message">
                                <strong x-text="notification.sender.display_name"></strong>
                                <span x-text="notification.preview"></span>
                            </div>
                            <div class="notification-time" x-text="formatTime(notification.created_at)"></div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>

<script>
function notificationDropdown() {
    return {
        activeTab: 'all',
        loading: true,
        notifications: [],

        init() {
            this.loadNotifications();
        },

        async loadNotifications() {
            try {
                // Placeholder - would fetch from API
                // const response = await fetch('/api/notifications');
                // this.notifications = await response.json();

                // Demo data for now
                this.notifications = [];
            } catch (error) {
                console.error('Failed to load notifications:', error);
            } finally {
                this.loading = false;
            }
        },

        get filteredNotifications() {
            if (this.activeTab === 'all') {
                return this.notifications;
            }
            return this.notifications.filter(n => n.type === this.activeTab);
        },

        async markAllAsRead() {
            try {
                // await fetch('/api/notifications/mark-read', { method: 'POST' });
                this.notifications = this.notifications.map(n => ({ ...n, read: true }));
            } catch (error) {
                console.error('Failed to mark notifications as read:', error);
            }
        },

        goToNotification(notification) {
            // Mark as read and navigate
            notification.read = true;
            if (notification.url) {
                window.location.href = notification.url;
            }
        },

        formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            if (diffDays < 7) return `${diffDays}d ago`;
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        }
    };
}
</script>
