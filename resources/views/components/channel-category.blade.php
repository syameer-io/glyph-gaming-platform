{{--
/**
 * Channel Category Component
 *
 * A reusable collapsible category component for organizing channels in the server sidebar.
 * Features Alpine.js state management with localStorage persistence for collapse state.
 *
 * @component
 * @package Glyph
 * @version 1.0.0
 *
 * @param string $name - Category display name (e.g., "TEXT CHANNELS", "VOICE CHANNELS")
 * @param int $serverId - Server ID used for localStorage key generation
 * @param bool $canAddChannel - Whether to show the add channel button (default: false)
 * @param string|null $addChannelRoute - Route for the add channel action
 * @param string $type - Channel type identifier for category (default: 'text')
 *
 * @example Basic usage:
 * <x-channel-category name="TEXT CHANNELS" :serverId="$server->id" type="text">
 *     <x-text-channel-item :channel="$channel" :server="$server" />
 * </x-channel-category>
 *
 * @example With add channel button:
 * <x-channel-category
 *     name="TEXT CHANNELS"
 *     :serverId="$server->id"
 *     :canAddChannel="$canManageChannels"
 *     :addChannelRoute="route('server.admin.channel.create', $server)"
 *     type="text"
 * >
 *     @foreach($textChannels as $channel)
 *         <x-text-channel-item :channel="$channel" :server="$server" />
 *     @endforeach
 * </x-channel-category>
 */
--}}

@props([
    'name',
    'serverId',
    'canAddChannel' => false,
    'addChannelRoute' => null,
    'type' => 'text',
])

@php
    // Validate required props
    if (!isset($name) || !isset($serverId)) {
        throw new \InvalidArgumentException('channel-category component requires $name and $serverId props');
    }

    // Generate a unique storage key for this category
    $storageKey = "channel_category_{$serverId}_{$type}_collapsed";
@endphp

<div
    class="channel-category"
    x-data="{
        collapsed: localStorage.getItem('{{ $storageKey }}') === 'true',
        storageKey: '{{ $storageKey }}',

        init() {
            // Apply initial collapsed class if needed
            if (this.collapsed) {
                this.$el.classList.add('collapsed');
            }
        },

        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem(this.storageKey, this.collapsed);

            // Toggle collapsed class for CSS animations
            if (this.collapsed) {
                this.$el.classList.add('collapsed');
            } else {
                this.$el.classList.remove('collapsed');
            }
        }
    }"
    :class="{ 'collapsed': collapsed }"
>
    {{-- Category Header --}}
    <div
        class="category-header"
        @click="toggle()"
        :aria-expanded="!collapsed"
        role="button"
        tabindex="0"
        @keydown.enter="toggle()"
        @keydown.space.prevent="toggle()"
    >
        {{-- Left side: Chevron + Name --}}
        <div class="category-header-left">
            {{-- Chevron Icon --}}
            <span class="category-chevron" :class="{ 'rotated': collapsed }">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path
                        fill-rule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                        clip-rule="evenodd"
                    />
                </svg>
            </span>

            {{-- Category Name --}}
            <span class="category-name">{{ $name }}</span>
        </div>

        {{-- Add Channel Button (visible only if canAddChannel is true) --}}
        @if($canAddChannel)
            <button
                class="category-add-btn"
                @click.stop="window.location.href = '{{ $addChannelRoute ?? '#' }}'"
                title="Create Channel"
                aria-label="Create a new channel in {{ $name }}"
                type="button"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Channels Container (slot content) --}}
    <div
        class="category-channels"
        x-show="!collapsed"
        x-collapse
        role="group"
        aria-label="{{ $name }} channels"
    >
        {{ $slot }}
    </div>
</div>
