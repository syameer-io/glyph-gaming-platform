{{--
    Discord-style Role Header Component
    Phase 2: Member List Enhancement

    A collapsible role group header with member count.
    Features:
    - Collapse/expand functionality with Alpine.js
    - localStorage persistence for collapse state
    - Uppercase styling with letter-spacing
    - Hover highlight
    - Accessible keyboard navigation

    @param string $roleName - The name of the role
    @param int $count - Number of members with this role
    @param int $serverId - Server ID for localStorage key
    @param string $roleColor - Optional role color (default: #96989d)
--}}

@props([
    'roleName',
    'count',
    'serverId',
    'roleColor' => '#96989d',
])

@php
    $storageKey = "role_collapsed_{$serverId}_{$roleName}";
    $safeRoleName = preg_replace('/[^a-zA-Z0-9_]/', '_', $roleName);
@endphp

<div
    class="role-group"
    x-data="{ collapsed: localStorage.getItem('{{ $storageKey }}') === 'true', toggle() { this.collapsed = !this.collapsed; localStorage.setItem('{{ $storageKey }}', this.collapsed); } }"
>
    {{-- Role Header --}}
    <div
        class="role-header"
        :class="{ 'role-header-collapsed': collapsed }"
        @click="toggle()"
        @keydown.enter="toggle()"
        @keydown.space.prevent="toggle()"
        tabindex="0"
        role="button"
        :aria-expanded="!collapsed"
        aria-controls="role-members-{{ $safeRoleName }}"
    >
        <div class="role-header-content">
            {{-- Collapse/Expand Icon --}}
            <svg
                class="role-header-icon"
                :class="{ 'rotate-icon': collapsed }"
                width="12"
                height="12"
                viewBox="0 0 12 12"
                fill="currentColor"
            >
                <path d="M3.5 4.5L6 7L8.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
            </svg>

            {{-- Role Name --}}
            <span class="role-header-name" style="color: {{ $roleColor }}">
                {{ strtoupper($roleName) }}
            </span>

            {{-- Member Count --}}
            <span class="role-header-count">
                &mdash; {{ $count }}
            </span>
        </div>
    </div>

    {{-- Members Container (Collapsible) --}}
    <div
        id="role-members-{{ $safeRoleName }}"
        class="role-header-members"
        x-show="!collapsed"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 max-h-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0 max-h-0"
        :style="collapsed ? 'display: none;' : ''"
    >
        {{ $slot }}
    </div>
</div>

<style>
    /* Inline styles for rotate icon animation */
    .rotate-icon {
        transform: rotate(-90deg);
    }
</style>
