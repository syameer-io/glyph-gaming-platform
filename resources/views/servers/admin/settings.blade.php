@extends('layouts.app')

@section('title', 'Server Settings - ' . $server->name)

@push('styles')
<style>
    .settings-sidebar {
        width: 200px;
        background-color: var(--color-surface);
        padding: 24px;
        border-radius: 12px;
    }

    .settings-content {
        flex: 1;
        background-color: var(--color-surface);
        padding: 24px;
        border-radius: 12px;
        margin-left: 24px;
    }
    
    .tab-content {
        display: none;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .member-item {
        display: flex;
        align-items: center;
        padding: 12px;
        background-color: var(--color-bg-primary);
        border-radius: 8px;
        margin-bottom: 8px;
    }
    
    .member-info {
        flex: 1;
        margin-left: 12px;
    }
    
    .channel-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px;
        background-color: var(--color-bg-primary);
        border-radius: 8px;
        margin-bottom: 8px;
    }
    
    .role-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        margin-right: 4px;
    }
    
    .color-input {
        width: 50px;
        height: 40px;
        padding: 4px;
        border: 2px solid var(--color-border-primary);
        border-radius: 8px;
        cursor: pointer;
    }

    .kebab-menu {
        position: relative;
        display: inline-block;
    }

    .kebab-button {
        background: none;
        border: none;
        color: var(--color-text-muted);
        font-size: 18px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .kebab-button:hover {
        background-color: var(--color-surface-active);
        color: var(--color-text-primary);
    }

    .kebab-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: var(--color-surface);
        border: 1px solid var(--color-border-primary);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        min-width: 120px;
        z-index: 1000;
        display: none;
    }

    .kebab-dropdown.active {
        display: block;
    }

    .kebab-option {
        display: block;
        width: 100%;
        padding: 8px 12px;
        background: none;
        border: none;
        color: var(--color-text-primary);
        text-align: left;
        cursor: pointer;
        transition: background-color 0.2s;
        font-size: 14px;
    }

    .kebab-option:hover {
        background-color: var(--color-surface-active);
    }

    .kebab-option:first-child {
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
    }

    .kebab-option:last-child {
        border-bottom-left-radius: 8px;
        border-bottom-right-radius: 8px;
    }

    .kebab-option.danger {
        color: #f87171;
    }

    .kebab-option.danger:hover {
        background-color: #dc2626;
        color: #ffffff;
    }

    .back-to-server-link {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        margin-bottom: 16px;
        color: var(--color-text-secondary);
        text-decoration: none;
        border-radius: 8px;
        transition: all 0.2s;
        font-size: 14px;
        border-bottom: 1px solid var(--color-border-primary);
        padding-bottom: 16px;
    }

    .back-to-server-link:hover {
        background-color: var(--color-surface-hover);
        color: var(--color-text-primary);
    }

    .back-to-server-link svg {
        flex-shrink: 0;
    }

    /* Goal Form Enhanced Styles */
    .goal-form-container {
        background: var(--color-bg-primary);
        border-radius: 12px;
        padding: 28px;
        margin-bottom: 32px;
        position: relative;
        border: 1px solid var(--color-border-primary);
    }

    .goal-form-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px 12px 0 0;
    }

    .goal-form-header {
        display: flex;
        align-items: center;
        gap: 14px;
        margin-bottom: 24px;
        padding-bottom: 20px;
        border-bottom: 1px solid var(--color-border-primary);
    }

    .goal-form-header-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .goal-form-section {
        margin-bottom: 24px;
    }

    .goal-form-section:last-of-type {
        margin-bottom: 0;
    }

    .goal-form-section-title {
        font-size: 11px;
        font-weight: 600;
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-bottom: 14px;
    }

    .goal-form-row {
        display: grid;
        gap: 20px;
    }

    .goal-form-row-2 {
        grid-template-columns: 1fr 1fr;
    }

    .goal-form-row-3 {
        grid-template-columns: minmax(100px, 1fr) minmax(150px, 1.4fr) minmax(130px, 1fr);
    }

    .goal-form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--color-text-secondary);
    }

    .goal-form-label svg {
        width: 16px;
        height: 16px;
        color: var(--accent-primary);
        flex-shrink: 0;
    }

    .goal-form-hint {
        color: var(--color-text-muted);
        font-size: 12px;
        margin-top: 6px;
        margin-bottom: 0;
    }

    .goal-form-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        padding-top: 24px;
        border-top: 1px solid var(--color-border-primary);
        margin-top: 28px;
    }

    .goal-form-actions .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .goal-form-actions .btn svg {
        flex-shrink: 0;
    }

    @media (max-width: 768px) {
        .goal-form-container {
            padding: 20px;
        }

        .goal-form-row-2,
        .goal-form-row-3 {
            grid-template-columns: 1fr;
        }

        .goal-form-header {
            flex-direction: column;
            text-align: center;
            gap: 12px;
        }

        .goal-form-actions {
            flex-direction: column;
        }

        .goal-form-actions .btn {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<x-navbar active-section="servers" />

<main>
    <div class="container">
        <h1 style="margin-bottom: 32px;">{{ $server->name }} - Settings</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div style="display: flex;">
            <!-- Settings Sidebar -->
            <div class="settings-sidebar">
                <!-- Back to Server Link -->
                <a href="{{ route('server.show', $server) }}" class="back-to-server-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Server
                </a>

                <div class="sidebar-nav">
                    <a href="#overview" class="sidebar-link active" onclick="showTab('overview', this)">Overview</a>
                    <a href="#channels" class="sidebar-link" onclick="showTab('channels', this)">Channels</a>
                    <a href="#members" class="sidebar-link" onclick="showTab('members', this)">Members</a>
                    <a href="#roles" class="sidebar-link" onclick="showTab('roles', this)">Roles</a>
                    <a href="#tags" class="sidebar-link" onclick="showTab('tags', this)">Tags</a>
                    <a href="#goals" class="sidebar-link" onclick="showTab('goals', this)">Goals</a>
                    <a href="#telegram" class="sidebar-link" onclick="showTab('telegram', this)">Telegram Bot</a>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <!-- Overview Tab -->
                <div id="overview" class="tab-content active">
                    <h3 style="margin-bottom: 24px;">Server Overview</h3>
                    
                    <form method="POST" action="{{ route('server.admin.update', $server) }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="name">Server Name</label>
                            <input type="text" id="name" name="name" value="{{ $server->name }}" required>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3">{{ $server->description }}</textarea>
                        </div>

                        <div style="margin-top: 24px; padding: 16px; background-color: var(--color-bg-primary); border-radius: 8px;">
                            <p style="font-weight: 600; margin-bottom: 8px;">Server Info</p>
                            <p style="font-size: 14px; color: var(--color-text-secondary);">Created: {{ $server->created_at->format('F j, Y') }}</p>
                            <p style="font-size: 14px; color: var(--color-text-secondary);">Members: {{ $server->members->count() }}</p>
                            <p style="font-size: 14px; color: var(--color-text-secondary);">Invite Code: <code style="background-color: var(--color-surface); padding: 2px 6px; border-radius: 4px;">{{ $server->invite_code }}</code></p>
                        </div>

                        <button type="submit" class="btn btn-primary" style="margin-top: 24px;">Save Changes</button>
                    </form>
                </div>

                <!-- Channels Tab -->
                <div id="channels" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Channels</h3>
                    
                    <form method="POST" action="{{ route('server.admin.channel.create', $server) }}" style="margin-bottom: 32px;" onsubmit="console.log('Form submitted!');">
                        @csrf
                        <h4 style="margin-bottom: 16px;">Create Channel</h4>
                        
                        @if ($errors->any())
                            <div style="background-color: var(--accent-danger); color: white; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                                <ul style="margin: 0; padding-left: 20px;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div style="display: flex; gap: 12px;">
                            <input type="text" name="name" placeholder="channel-name" pattern="[a-z0-9\-]+" required style="flex: 3;" value="{{ old('name') }}">
                            <select name="type" required style="flex: 1;">
                                <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>Text Channel</option>
                                <option value="voice" {{ old('type') === 'voice' ? 'selected' : '' }}>Voice Channel</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                        <small style="color: var(--color-text-muted); font-size: 12px;">Use lowercase letters, numbers, and hyphens only</small>
                    </form>

                    <h4 style="margin-bottom: 16px;">Existing Channels</h4>
                    @foreach(['text' => 'Text Channels', 'voice' => 'Voice Channels'] as $type => $label)
                        <p style="font-size: 14px; font-weight: 600; color: var(--color-text-muted); text-transform: uppercase; margin-top: 24px; margin-bottom: 12px;">{{ $label }}</p>
                        @foreach($server->channels->where('type', $type) as $channel)
                            @php
                                // Get existing channel overrides for this channel
                                $channelOverrides = $channel->permissionOverrides ?? collect();
                            @endphp
                            <div style="background-color: var(--color-bg-primary); border-radius: 8px; margin-bottom: 12px; padding: 12px;">
                                <div class="channel-item" style="background-color: transparent; margin: 0; padding: 0;">
                                    <div>
                                        <span style="color: var(--color-text-muted); margin-right: 8px;">{{ $type === 'text' ? '#' : '🔊' }}</span>
                                        <span>{{ $channel->name }}</span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        {{-- Permission Overrides Button --}}
                                        <button type="button"
                                                class="btn btn-sm"
                                                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background-color: var(--color-surface-active); color: var(--color-text-primary); border: none; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                                                onclick="toggleChannelOverrides('{{ $channel->id }}')">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                            </svg>
                                            Overrides
                                        </button>

                                        <div class="kebab-menu">
                                            <button class="kebab-button" onclick="toggleKebabMenu('channel-{{ $channel->id }}')">⋮</button>
                                            <div class="kebab-dropdown" id="kebab-channel-{{ $channel->id }}">
                                                <button class="kebab-option" onclick="toggleChannelEdit('{{ $channel->id }}'); closeKebabMenu('channel-{{ $channel->id }}')">Edit</button>
                                                @if($server->channels->where('type', $type)->count() > 1)
                                                    <form method="POST" action="{{ route('server.admin.channel.delete', [$server, $channel]) }}" style="display: inline;">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kebab-option danger" onclick="return confirm('Delete this channel?')">Delete</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Edit Channel Form -->
                                <div id="channel-edit-{{ $channel->id }}" style="display: none; margin-top: 16px;">
                                    <form method="POST" action="{{ route('server.admin.channel.update', [$server, $channel]) }}">
                                        @csrf
                                        @method('PUT')
                                        <h5 style="margin-bottom: 12px;">Edit Channel</h5>
                                        <div style="display: flex; gap: 12px;">
                                            <input type="text" name="name" value="{{ $channel->name }}" pattern="[a-z0-9\-]+" required style="flex: 3;" placeholder="channel-name">
                                            <select name="type" required style="flex: 1;">
                                                <option value="text" {{ $channel->type === 'text' ? 'selected' : '' }}>Text Channel</option>
                                                <option value="voice" {{ $channel->type === 'voice' ? 'selected' : '' }}>Voice Channel</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleChannelEdit('{{ $channel->id }}')">Cancel</button>
                                        </div>
                                        <small style="color: var(--color-text-muted); font-size: 12px; display: block; margin-top: 8px;">Use lowercase letters, numbers, and hyphens only</small>
                                    </form>
                                </div>

                                <!-- Channel Permission Overrides Section -->
                                <div id="channel-overrides-{{ $channel->id }}" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-border-secondary);">
                                    <h5 style="margin-bottom: 12px; color: var(--color-text-primary); font-size: 14px; font-weight: 600;">Permission Overrides</h5>
                                    <p style="color: var(--color-text-muted); font-size: 12px; margin-bottom: 16px;">
                                        Customize permissions for specific roles in this channel. Use "Inherit" to use the default role permission.
                                    </p>

                                    @php
                                        // Get relevant permissions based on channel type
                                        $relevantPermissions = $type === 'text'
                                            ? ['view_channels', 'send_messages', 'manage_messages']
                                            : ['view_channels', 'connect', 'speak'];

                                        $permissionLabels = [
                                            'view_channels' => 'View',
                                            'send_messages' => 'Send Messages',
                                            'manage_messages' => 'Manage Messages',
                                            'connect' => 'Connect',
                                            'speak' => 'Speak',
                                        ];
                                    @endphp

                                    <div style="overflow-x: auto;">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                            <thead>
                                                <tr style="border-bottom: 1px solid var(--color-border-secondary);">
                                                    <th style="text-align: left; padding: 8px 12px; color: var(--color-text-muted); font-weight: 600;">Role</th>
                                                    @foreach($relevantPermissions as $permission)
                                                        <th style="text-align: center; padding: 8px 12px; color: var(--color-text-muted); font-weight: 600; min-width: 100px;">{{ $permissionLabels[$permission] }}</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($server->roles->where('name', '!=', 'Server Admin')->sortByDesc('position') as $role)
                                                    <tr style="border-bottom: 1px solid var(--color-border-secondary);">
                                                        <td style="padding: 10px 12px;">
                                                            <span class="role-badge" style="background-color: {{ $role->color }}; color: white; font-size: 12px;">
                                                                {{ $role->name }}
                                                            </span>
                                                        </td>
                                                        @foreach($relevantPermissions as $permission)
                                                            @php
                                                                // Find existing override value for this role/permission combo
                                                                $existingOverride = $channelOverrides
                                                                    ->where('role_id', $role->id)
                                                                    ->where('permission', $permission)
                                                                    ->first();
                                                                $currentValue = $existingOverride ? $existingOverride->value : 'inherit';
                                                            @endphp
                                                            <td style="padding: 10px 12px; text-align: center;">
                                                                <select class="channel-override-select"
                                                                        data-role="{{ $role->id }}"
                                                                        data-permission="{{ $permission }}"
                                                                        style="padding: 6px 10px; border-radius: 4px; border: 1px solid var(--color-border-primary); background-color: var(--color-surface); color: var(--color-text-primary); font-size: 12px; width: 90px; cursor: pointer;">
                                                                    <option value="inherit" {{ $currentValue === 'inherit' ? 'selected' : '' }}>-- Inherit</option>
                                                                    <option value="allow" {{ $currentValue === 'allow' ? 'selected' : '' }}>&#10003; Allow</option>
                                                                    <option value="deny" {{ $currentValue === 'deny' ? 'selected' : '' }}>&#10007; Deny</option>
                                                                </select>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div style="margin-top: 16px; display: flex; gap: 8px; justify-content: flex-end;">
                                        <button type="button"
                                                class="btn btn-secondary btn-sm"
                                                style="padding: 8px 16px; font-size: 12px;"
                                                onclick="toggleChannelOverrides('{{ $channel->id }}')">
                                            Cancel
                                        </button>
                                        <button type="button"
                                                class="btn btn-primary btn-sm"
                                                style="padding: 8px 16px; font-size: 12px; background-color: var(--accent-primary);"
                                                onclick="saveChannelOverrides('{{ $channel->id }}')">
                                            Save Overrides
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <!-- Members Tab -->
                <div id="members" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Members</h3>
                    
                    @foreach($server->members as $member)
                        <div class="member-item">
                            <img src="{{ $member->profile->avatar_url }}" alt="{{ $member->display_name }}" 
                                 style="width: 40px; height: 40px; border-radius: 50%;">
                            <div class="member-info">
                                <div style="font-weight: 600;">{{ $member->display_name }}</div>
                                <div style="font-size: 14px; color: var(--color-text-muted);">{{ '@' . ($member->username ?? 'No username') }}</div>
                                <div style="margin-top: 4px;">
                                    @foreach($member->roles()->wherePivot('server_id', $server->id)->get() as $role)
                                        <span class="role-badge" style="background-color: {{ $role->color }}; color: white;">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                    @if($member->pivot->is_muted)
                                        <span class="role-badge" style="background-color: var(--accent-danger); color: white;">Muted</span>
                                    @endif
                                    @if($member->pivot->is_banned)
                                        <span class="role-badge" style="background-color: var(--accent-danger-dark, #991b1b); color: white;">Banned</span>
                                    @endif
                                </div>
                            </div>
                            @if($member->id !== auth()->id() && $member->id !== $server->creator_id)
                                <div class="kebab-menu">
                                    <button class="kebab-button" onclick="toggleKebabMenu('member-{{ $member->id }}')">⋮</button>
                                    <div class="kebab-dropdown" id="kebab-member-{{ $member->id }}">
                                        @if(!$member->pivot->is_muted)
                                            <form method="POST" action="{{ route('server.admin.member.mute', [$server, $member]) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kebab-option">Mute</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('server.admin.member.unmute', [$server, $member]) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kebab-option">Unmute</button>
                                            </form>
                                        @endif
                                        
                                        <form method="POST" action="{{ route('server.admin.member.kick', [$server, $member]) }}" style="display: inline;">
                                            @csrf
                                            <button type="submit" class="kebab-option danger" onclick="return confirm('Kick this member?')">Kick</button>
                                        </form>
                                        
                                        @if(!$member->pivot->is_banned)
                                            <form method="POST" action="{{ route('server.admin.member.ban', [$server, $member]) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kebab-option danger" onclick="return confirm('Ban this member?')">Ban</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('server.admin.member.unban', [$server, $member]) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="kebab-option" onclick="return confirm('Unban this member?')">Unban</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Roles Tab -->
                <div id="roles" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Roles</h3>
                    
                    <form method="POST" action="{{ route('server.admin.role.create', $server) }}" style="margin-bottom: 32px;">
                        @csrf
                        <h4 style="margin-bottom: 16px;">Create Role</h4>
                        <div style="display: flex; gap: 12px; align-items: end;">
                            <div class="form-group" style="flex: 1; margin: 0;">
                                <label for="role_name">Role Name</label>
                                <input type="text" id="role_name" name="name" placeholder="Moderator" required>
                            </div>
                            <div class="form-group" style="margin: 0;">
                                <label for="role_color">Color</label>
                                <input type="color" id="role_color" name="color" value="#667eea" class="color-input">
                            </div>
                            <button type="submit" class="btn btn-primary">Create Role</button>
                        </div>
                    </form>

                    <h4 style="margin-bottom: 16px;">Existing Roles</h4>
                    @foreach($server->roles->sortByDesc('position') as $role)
                        <div style="padding: 16px; background-color: var(--color-bg-primary); border-radius: 8px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span class="role-badge" style="background-color: {{ $role->color }}; color: white; font-size: 14px;">
                                        {{ $role->name }}
                                    </span>
                                    <span style="margin-left: 12px; color: var(--color-text-muted); font-size: 14px;">
                                        {{ $role->getMemberCount() }} members
                                    </span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    {{-- Permissions Button - Only for custom roles --}}
                                    @if(!in_array($role->name, ['Server Admin', 'Member']))
                                        <button type="button"
                                                class="btn btn-sm"
                                                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background-color: var(--color-surface-active); color: var(--color-text-primary); border: none; border-radius: 6px; font-size: 12px; font-weight: 500; cursor: pointer; transition: all 0.2s;"
                                                onclick="openPermissionsModalFromData({{ $role->id }})">
                                            <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                            Permissions
                                        </button>
                                    @else
                                        <span style="font-size: 11px; color: var(--color-text-muted); padding: 6px 12px; background-color: var(--color-border-secondary); border-radius: 6px;">
                                            Default Role
                                        </span>
                                    @endif

                                    <div class="kebab-menu">
                                        <button class="kebab-button" onclick="toggleKebabMenu('role-{{ $role->id }}')">⋮</button>
                                        <div class="kebab-dropdown" id="kebab-role-{{ $role->id }}">
                                            <button class="kebab-option" onclick="toggleRoleMembers('{{ $role->id }}'); closeKebabMenu('role-{{ $role->id }}')">
                                                Manage Members
                                            </button>
                                            @if($role->name !== 'Server Admin' && $role->name !== 'Member')
                                                <button class="kebab-option" onclick="toggleEditRole('{{ $role->id }}'); closeKebabMenu('role-{{ $role->id }}')">
                                                    Edit
                                                </button>
                                                <form method="POST" action="{{ route('server.admin.role.delete', [$server, $role]) }}" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="kebab-option danger" onclick="return confirm('Are you sure you want to delete this role? This will remove it from all members.')">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if($role->name !== 'Server Admin' && $role->name !== 'Member')
                                <!-- Edit Role Form -->
                                <div id="role-edit-{{ $role->id }}" style="display: none; margin-top: 16px;">
                                    <form method="POST" action="{{ route('server.admin.role.update', [$server, $role]) }}">
                                        @csrf
                                        @method('PUT')
                                        <h5 style="margin-bottom: 12px;">Edit Role</h5>
                                        <div style="display: flex; gap: 12px; align-items: end;">
                                            <div class="form-group" style="flex: 1; margin: 0;">
                                                <label for="edit_role_name_{{ $role->id }}">Role Name</label>
                                                <input type="text" id="edit_role_name_{{ $role->id }}" name="name" value="{{ $role->name }}" required>
                                            </div>
                                            <div class="form-group" style="margin: 0;">
                                                <label for="edit_role_color_{{ $role->id }}">Color</label>
                                                <input type="color" id="edit_role_color_{{ $role->id }}" name="color" value="{{ $role->color }}" class="color-input">
                                            </div>
                                            <button type="submit" class="btn btn-primary">Update Role</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleEditRole('{{ $role->id }}')">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Manage Members -->
                                <div id="role-members-{{ $role->id }}" style="display: none; margin-top: 16px;">
                                    <form method="POST" action="{{ route('server.admin.role.assign', $server) }}" style="margin-bottom: 16px;">
                                        @csrf
                                        <input type="hidden" name="role_id" value="{{ $role->id }}">
                                        <div style="display: flex; gap: 8px;">
                                            <select name="user_id" required style="flex: 1;">
                                                <option value="">Select a member...</option>
                                                @foreach($server->members as $member)
                                                    @if(!$member->roles()->where('role_id', $role->id)->exists())
                                                        <option value="{{ $member->id }}">{{ $member->display_name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm">Add Member</button>
                                        </div>
                                    </form>
                                    
                                    @foreach($role->getMembersForServer() as $user)
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background-color: var(--color-surface); border-radius: 4px; margin-bottom: 4px;">
                                            <span>{{ $user->display_name }}</span>
                                            @if($role->users()->wherePivot('server_id', $server->id)->where('users.id', $user->id)->exists())
                                                {{-- Only show remove button for users with explicit role assignment --}}
                                                <form method="POST" action="{{ route('server.admin.role.remove', [$server, $user, $role]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 12px;">Remove</button>
                                                </form>
                                            @else
                                                {{-- Implicit members (no explicit role) - show badge instead of remove button --}}
                                                <span style="font-size: 11px; color: var(--color-text-muted); padding: 4px 8px; background-color: var(--color-border-secondary); border-radius: 4px;">
                                                    Implicit
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Tags Tab -->
                <div id="tags" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Server Tags</h3>
                    <p style="color: var(--color-text-secondary); margin-bottom: 24px; font-size: 14px;">Tags help users discover your server based on games, skill levels, regions, and other preferences.</p>
                    
                    <!-- Add Tag Form -->
                    <div style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px; margin-bottom: 32px;">
                        <h4 style="margin-bottom: 16px;">Add New Tag</h4>
                        
                        <form id="addTagForm" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end;">
                            @csrf
                            <div class="form-group" style="margin: 0;">
                                <label for="tag_type">Tag Type</label>
                                <select id="tag_type" name="tag_type" required onchange="updateTagValueOptions()">
                                    <option value="">Select type...</option>
                                    <option value="game">Game</option>
                                    <option value="skill_level">Skill Level</option>
                                    <option value="region">Region</option>
                                    <option value="language">Language</option>
                                    <option value="activity_time">Activity Time</option>
                                </select>
                            </div>
                            
                            <div class="form-group" style="margin: 0;">
                                <label for="tag_value">Tag Value</label>
                                <select id="tag_value" name="tag_value" required disabled>
                                    <option value="">Select tag type first...</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Add Tag</button>
                        </form>
                        
                        <div id="tagSuggestions" style="margin-top: 16px; display: none;">
                            <h5 style="margin-bottom: 12px; color: var(--color-text-primary);">Suggested Tags</h5>
                            <div id="suggestedTagsList" style="display: flex; flex-wrap: wrap; gap: 8px;"></div>
                        </div>
                    </div>

                    <!-- Current Tags -->
                    <h4 style="margin-bottom: 16px;">Current Tags</h4>
                    <div id="currentTags">
                        @if($server->tags && $server->tags->count() > 0)
                            @foreach(['game', 'skill_level', 'region', 'language', 'activity_time'] as $tagType)
                                @php $typeTags = $server->tags->where('tag_type', $tagType); @endphp
                                @if($typeTags->count() > 0)
                                    <div style="margin-bottom: 20px;">
                                        <h5 style="color: var(--color-text-secondary); font-size: 14px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">
                                            {{ ucfirst(str_replace('_', ' ', $tagType)) }}
                                        </h5>
                                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                            @foreach($typeTags as $tag)
                                                <div style="display: flex; align-items: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;">
                                                    <span>{{ ucfirst(str_replace('_', ' ', $tag->tag_value)) }}</span>
                                                    <button onclick="removeTag('{{ $tag->id }}')" style="background: none; border: none; color: white; margin-left: 8px; font-size: 16px; cursor: pointer; padding: 0; line-height: 1;" title="Remove tag">×</button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        @else
                            <div style="text-align: center; padding: 40px; background-color: var(--color-bg-primary); border-radius: 8px; border: 2px dashed var(--color-border-primary);">
                                <p style="color: var(--color-text-muted); margin-bottom: 16px;">No tags added yet</p>
                                <p style="color: var(--color-text-secondary); font-size: 14px;">Add tags to help users discover your server based on games, skill levels, and preferences.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Tag Analytics -->
                    @if($server->tags && $server->tags->count() > 0)
                        <div style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px; margin-top: 32px;">
                            <h4 style="margin-bottom: 16px;">Tag Performance</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 600; color: var(--accent-primary); margin-bottom: 4px;">{{ $server->tags->count() }}</div>
                                    <div style="color: var(--color-text-secondary); font-size: 14px;">Total Tags</div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 600; color: var(--accent-success); margin-bottom: 4px;">{{ $server->tags->where('tag_type', 'game')->count() }}</div>
                                    <div style="color: var(--color-text-secondary); font-size: 14px;">Game Tags</div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 600; color: var(--accent-warning); margin-bottom: 4px;">{{ $server->members->count() }}</div>
                                    <div style="color: var(--color-text-secondary); font-size: 14px;">Members</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Goals Tab -->
                <div id="goals" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Community Goals</h3>
                    <p style="color: var(--color-text-secondary); margin-bottom: 24px; font-size: 14px;">Create and manage community goals to engage your members and track achievements together.</p>

                    <!-- Create Goal Form - Enhanced UI -->
                    <div class="goal-form-container">
                        <!-- Form Header -->
                        <div class="goal-form-header">
                            <div class="goal-form-header-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="8" r="6"/>
                                    <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                                </svg>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 16px; font-weight: 600; color: var(--color-text-primary);">Create New Goal</h4>
                                <p style="margin: 2px 0 0 0; font-size: 13px; color: var(--color-text-muted);">Set up a community challenge for your members</p>
                            </div>
                        </div>

                        <form id="createGoalForm">
                            @csrf

                            <!-- Section 1: Basics -->
                            <div class="goal-form-section">
                                <div class="goal-form-section-title">Basic Information</div>
                                <div class="goal-form-row goal-form-row-2">
                                    <div class="form-group" style="margin: 0;">
                                        <label for="goal_title" class="goal-form-label">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
                                                <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
                                                <path d="M4 22h16"/>
                                                <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
                                                <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
                                                <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                                            </svg>
                                            Goal Title <span style="color: var(--accent-danger);">*</span>
                                        </label>
                                        <input type="text" id="goal_title" name="title" placeholder="e.g., Reach 100 CS2 Wins" required>
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="goal_type" class="goal-form-label">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z"/>
                                                <path d="M7 7h.01"/>
                                            </svg>
                                            Goal Type <span style="color: var(--accent-danger);">*</span>
                                        </label>
                                        <select id="goal_type" name="goal_type" required onchange="updateGoalFields()">
                                            <option value="">Select type...</option>
                                            <option value="achievement">Achievement Goal</option>
                                            <option value="playtime">Playtime Goal</option>
                                            <option value="participation">Participation Goal</option>
                                            <option value="community">Community Goal</option>
                                            <option value="custom">Custom Goal</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Description -->
                            <div class="goal-form-section">
                                <div class="goal-form-section-title">Details</div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="goal_description" class="goal-form-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="21" y1="6" x2="3" y2="6"/>
                                            <line x1="17" y1="12" x2="3" y2="12"/>
                                            <line x1="19" y1="18" x2="3" y2="18"/>
                                        </svg>
                                        Description <span style="color: var(--accent-danger);">*</span>
                                    </label>
                                    <textarea id="goal_description" name="description" rows="3" placeholder="Describe the goal and how members can contribute..." required></textarea>
                                </div>
                            </div>

                            <!-- Section 3: Target Configuration -->
                            <div class="goal-form-section">
                                <div class="goal-form-section-title">Target Configuration</div>
                                <div class="goal-form-row goal-form-row-3">
                                    <div class="form-group" style="margin: 0;">
                                        <label for="target_value" class="goal-form-label">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"/>
                                                <circle cx="12" cy="12" r="6"/>
                                                <circle cx="12" cy="12" r="2"/>
                                            </svg>
                                            Target Value <span style="color: var(--accent-danger);">*</span>
                                        </label>
                                        <input type="number" id="target_value" name="target_value" placeholder="100" required min="1">
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="game_appid" class="goal-form-label">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="6" y1="11" x2="10" y2="11"/>
                                                <line x1="8" y1="9" x2="8" y2="13"/>
                                                <line x1="15" y1="12" x2="15.01" y2="12"/>
                                                <line x1="18" y1="10" x2="18.01" y2="10"/>
                                                <path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/>
                                            </svg>
                                            Game <span style="color: var(--accent-danger);">*</span>
                                        </label>
                                        <select id="game_appid" name="game_appid" required>
                                            <option value="">Select a game...</option>
                                            <option value="730">Counter-Strike 2</option>
                                            <option value="548430">Deep Rock Galactic</option>
                                            <option value="493520">GTFO</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin: 0;">
                                        <label for="deadline" class="goal-form-label">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                                <line x1="16" y1="2" x2="16" y2="6"/>
                                                <line x1="8" y1="2" x2="8" y2="6"/>
                                                <line x1="3" y1="10" x2="21" y2="10"/>
                                            </svg>
                                            Deadline <span style="color: var(--accent-danger);">*</span>
                                        </label>
                                        <input type="date" id="deadline" name="deadline" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 4: Achievement Fields (Conditional) -->
                            <div id="achievementFields" class="goal-form-section" style="display: none;">
                                <div class="goal-form-section-title">Achievement Settings</div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="achievement_id" class="goal-form-label">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M12 15l-2 5l9-9l-9-9l2 5l-9 4l9 4z"/>
                                        </svg>
                                        Steam Achievement
                                    </label>
                                    <input type="text" id="achievement_id" name="achievement_id" placeholder="e.g., WIN_BOMB_PLANT">
                                    <p class="goal-form-hint">Steam achievement API name (will sync automatically)</p>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="goal-form-actions">
                                <button type="button" onclick="clearGoalForm()" class="btn btn-secondary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                                        <path d="M3 6h18"/>
                                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                                    </svg>
                                    Clear
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                                        <path d="M12 5v14"/>
                                        <path d="M5 12h14"/>
                                    </svg>
                                    Create Goal
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Active Goals -->
                    <h4 style="margin-bottom: 16px;">Active Goals</h4>
                    <div id="activeGoals">
                        @if($server->goals && $server->goals->where('status', 'active')->count() > 0)
                            @foreach($server->goals->where('status', 'active') as $goal)
                                <div class="goal-card" data-goal-id="{{ $goal->id }}" style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px; margin-bottom: 16px; border-left: 4px solid var(--accent-primary);">
                                    <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 12px;">
                                        <div style="flex: 1;">
                                            <h5 style="color: var(--color-text-primary); margin-bottom: 4px;">{{ $goal->title }}</h5>
                                            <p style="color: var(--color-text-secondary); font-size: 14px; margin-bottom: 8px;">{{ $goal->description }}</p>
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <span style="font-size: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 3px; text-transform: uppercase; font-weight: 600;">
                                                    {{ ucfirst($goal->goal_type) }}
                                                </span>
                                                @if($goal->game_appid)
                                                    <span style="font-size: 12px; background-color: var(--color-surface-active); color: var(--color-text-secondary); padding: 2px 6px; border-radius: 3px;">
                                                        {{ $goal->game_name ?? 'Game' }}
                                                    </span>
                                                @endif
                                                @if($goal->deadline)
                                                    <span style="font-size: 12px; color: var(--accent-warning);">
                                                        📅 Due {{ \Carbon\Carbon::parse($goal->deadline)->diffForHumans() }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div style="position: relative;">
                                            <button onclick="toggleGoalActions('{{ $goal->id }}')" style="background: none; border: none; color: var(--color-text-muted); font-size: 18px; cursor: pointer; padding: 4px;">&#8942;</button>
                                            <div class="goal-actions" id="goal-actions-{{ $goal->id }}" style="display: none; position: absolute; top: 100%; right: 0; background-color: var(--color-surface); border: 1px solid var(--color-border-primary); border-radius: 6px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); z-index: 100; min-width: 140px; margin-top: 4px;">
                                                <button onclick="syncGoalProgress('{{ $goal->id }}')" class="goal-action-btn" style="display: block; width: 100%; padding: 10px 14px; background: none; border: none; color: var(--color-text-primary); text-align: left; cursor: pointer; font-size: 14px; border-radius: 6px 6px 0 0; transition: background-color 0.2s;">Sync Progress</button>
                                                <button onclick="editGoal('{{ $goal->id }}')" class="goal-action-btn" style="display: block; width: 100%; padding: 10px 14px; background: none; border: none; color: var(--color-text-primary); text-align: left; cursor: pointer; font-size: 14px; transition: background-color 0.2s;">Edit Goal</button>
                                                <button onclick="deleteGoal('{{ $goal->id }}')" class="goal-action-btn-danger" style="display: block; width: 100%; padding: 10px 14px; background: none; border: none; color: var(--accent-danger); text-align: left; cursor: pointer; font-size: 14px; border-radius: 0 0 6px 6px; transition: background-color 0.2s;">Delete Goal</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div style="margin-bottom: 16px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                            <span style="font-size: 14px; color: var(--color-text-secondary);">Progress</span>
                                            <span class="progress-text" style="font-size: 14px; color: var(--color-text-primary); font-weight: 600;" data-target="{{ $goal->target_value }}">
                                                {{ $goal->current_progress ?? 0 }} / {{ $goal->target_value }}
                                                ({{ $goal->target_value > 0 ? round(($goal->current_progress ?? 0) / $goal->target_value * 100, 1) : 0 }}%)
                                            </span>
                                        </div>
                                        <div style="width: 100%; height: 8px; background-color: var(--color-surface-active); border-radius: 4px; overflow: hidden;">
                                            <div class="progress-bar" style="height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: {{ $goal->target_value > 0 ? min(($goal->current_progress ?? 0) / $goal->target_value * 100, 100) : 0 }}%; transition: width 0.8s ease;" 
                                                 aria-valuenow="{{ $goal->target_value > 0 ? round(($goal->current_progress ?? 0) / $goal->target_value * 100, 1) : 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <!-- Participants -->
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="participant-count" style="font-size: 14px; color: var(--color-text-secondary);">{{ $goal->participants->count() }} participants</span>
                                            <div class="participants-list" style="display: flex; margin-left: 8px;">
                                                @foreach($goal->participants->take(5) as $participant)
                                                    <img src="{{ $participant->user->profile->avatar_url }}" alt="{{ $participant->user->display_name }}" 
                                                         style="width: 24px; height: 24px; border-radius: 50%; margin-left: -4px; border: 2px solid var(--color-surface);"
                                                         title="{{ $participant->user->display_name }} - {{ $participant->contribution_percentage }}% contribution"
                                                         data-user-id="{{ $participant->user_id }}">
                                                @endforeach
                                                @if($goal->participants->count() > 5)
                                                    <div style="width: 24px; height: 24px; border-radius: 50%; background-color: var(--color-surface-active); margin-left: -4px; border: 2px solid var(--color-surface); display: flex; align-items: center; justify-content: center; color: var(--color-text-secondary); font-size: 10px; font-weight: 600;">
                                                        +{{ $goal->participants->count() - 5 }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 8px;">
                                            <button onclick="showGoalDetails('{{ $goal->id }}')" class="btn btn-sm" style="background-color: var(--accent-primary); color: white; padding: 4px 8px; font-size: 12px;">View Details</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="text-align: center; padding: 40px; background-color: var(--color-bg-primary); border-radius: 8px; border: 2px dashed var(--color-border-primary);">
                                <div style="font-size: 32px; margin-bottom: 16px;">🎯</div>
                                <p style="color: var(--color-text-muted); margin-bottom: 16px;">No goals created yet</p>
                                <p style="color: var(--color-text-secondary); font-size: 14px;">Create your first community goal to start engaging your members!</p>
                            </div>
                        @endif
                    </div>

                    <!-- Completed Goals -->
                    @if($server->goals && $server->goals->where('status', 'completed')->count() > 0)
                        <div style="margin-top: 32px;">
                            <h4 style="margin-bottom: 16px;">Completed Goals ({{ $server->goals->where('status', 'completed')->count() }})</h4>
                            <div style="max-height: 300px; overflow-y: auto;">
                                @foreach($server->goals->where('status', 'completed')->take(5) as $goal)
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background-color: var(--color-bg-primary); border-radius: 6px; margin-bottom: 8px; border-left: 4px solid var(--accent-success);">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary); margin-bottom: 2px;">✅ {{ $goal->title }}</div>
                                            <div style="font-size: 12px; color: var(--color-text-secondary);">
                                                Completed {{ $goal->completed_at ? \Carbon\Carbon::parse($goal->completed_at)->diffForHumans() : 'recently' }} • 
                                                {{ $goal->participants->count() }} participants
                                            </div>
                                        </div>
                                        <div style="text-align: right; color: var(--accent-success); font-weight: 600;">
                                            {{ $goal->current_progress }}/{{ $goal->target_value }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Goal Analytics -->
                    <div style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px; margin-top: 32px;">
                        <h4 style="margin-bottom: 16px;">Goal Analytics</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: var(--accent-primary); margin-bottom: 4px;">{{ $server->goals->count() ?? 0 }}</div>
                                <div style="color: var(--color-text-secondary); font-size: 14px;">Total Goals</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: var(--accent-success); margin-bottom: 4px;">{{ $server->goals->where('status', 'completed')->count() ?? 0 }}</div>
                                <div style="color: var(--color-text-secondary); font-size: 14px;">Completed</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: var(--accent-warning); margin-bottom: 4px;">{{ $server->goals->where('status', 'active')->count() ?? 0 }}</div>
                                <div style="color: var(--color-text-secondary); font-size: 14px;">Active</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: var(--accent-danger); margin-bottom: 4px;">{{ $server->goals->sum('participants_count') ?? 0 }}</div>
                                <div style="color: var(--color-text-secondary); font-size: 14px;">Participations</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Telegram Bot Tab -->
                <div id="telegram" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Telegram Bot Integration</h3>
                    <p style="color: var(--color-text-secondary); margin-bottom: 24px; font-size: 14px;">Connect your server to Telegram to receive goal notifications and updates in your Telegram group or channel.</p>
                    
                    <!-- Bot Status Card -->
                    <div id="telegramStatusCard" style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px; margin-bottom: 32px; position: relative;">
                        <!-- Status Badge - Positioned absolutely in top-right -->
                        <div id="statusIndicator" style="position: absolute; top: 16px; right: 16px; display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background-color: var(--color-surface-active); color: var(--color-text-primary);">
                            <span id="statusDot" style="width: 8px; height: 8px; border-radius: 50%; background-color: var(--color-text-muted); flex-shrink: 0;"></span>
                            <span id="statusText">Checking...</span>
                        </div>

                        <h4 style="margin: 0 0 16px 0;">Connection Status</h4>
                        
                        <div id="telegramInfo" style="display: none;">
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px;">
                                <div>
                                    <div style="font-size: 14px; color: var(--color-text-secondary);">Chat ID</div>
                                    <div style="font-weight: 600; color: var(--color-text-primary);" id="chatId">-</div>
                                </div>
                                <div>
                                    <div style="font-size: 14px; color: var(--color-text-secondary);">Chat Name</div>
                                    <div style="font-weight: 600; color: var(--color-text-primary);" id="chatName">-</div>
                                </div>
                                <div>
                                    <div style="font-size: 14px; color: var(--color-text-secondary);">Linked</div>
                                    <div style="font-weight: 600; color: var(--color-text-primary);" id="linkedAt">-</div>
                                </div>
                                <div>
                                    <div style="font-size: 14px; color: var(--color-text-secondary);">Notifications</div>
                                    <div style="font-weight: 600; color: var(--color-text-primary);" id="notificationsEnabled">-</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button id="unlinkBtn" class="btn btn-danger btn-sm" onclick="unlinkTelegram()" style="display: none;">Unlink</button>
                                <button id="testBtn" class="btn btn-secondary btn-sm" onclick="testTelegramMessage()" style="display: none;">Send Test Message</button>
                            </div>
                        </div>
                        
                        <div id="setupInstructions" style="display: none;">
                            <div style="background-color: var(--color-surface); border-radius: 6px; padding: 16px; border-left: 4px solid var(--accent-primary);">
                                <h5 style="margin-bottom: 12px; color: var(--color-text-primary);">How to Connect</h5>
                                <ol style="margin: 0; padding-left: 20px; color: var(--color-text-secondary); font-size: 14px; line-height: 1.6;">
                                    <li>Add <code style="background-color: var(--color-bg-primary); padding: 2px 6px; border-radius: 4px; color: var(--color-text-primary);">@@PlayGlyphBot</code> to your Telegram group</li>
                                    <li>Send the command: <code style="background-color: var(--color-bg-primary); padding: 2px 6px; border-radius: 4px; color: var(--color-text-primary);">/link {{ $server->invite_code }}</code></li>
                                    <li>The bot will automatically link and start sending notifications</li>
                                </ol>
                                <p style="margin-top: 12px; margin-bottom: 0; font-size: 13px; color: var(--color-text-muted);">
                                    <strong>Note:</strong> Make sure the bot has permission to send messages in your group.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px; margin-bottom: 32px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h4 style="margin-bottom: 0;">Notification Settings</h4>
                            <span id="saveIndicator" style="display: none; font-size: 12px; color: var(--accent-success);">&#10003; Saved</span>
                        </div>

                        <div id="notificationSettings" style="display: none;">
                            <div style="margin-bottom: 16px;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" id="notificationsToggle" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                    <span style="font-weight: 600; color: var(--color-text-primary);">Enable Telegram Notifications</span>
                                </label>
                                <small style="color: var(--color-text-muted); font-size: 12px; margin-left: 24px;">Turn off to disable all Telegram notifications for this server</small>
                            </div>

                            <div id="notificationTypes" style="display: none; margin-left: 24px; margin-top: 16px;">
                                <p style="font-size: 14px; font-weight: 600; color: var(--color-text-primary); margin-bottom: 12px;">Notification Types</p>

                                <div style="display: grid; gap: 12px;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="goalCompleted" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">Goal Completed</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">When a community goal is completed</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="goalProgress" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">Goal Progress</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">Milestone progress updates (every 10%)</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="newGoal" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">New Goals</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">When new community goals are created</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="userJoined" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">Member Joined</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">When members join goals</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="milestoneReached" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">Milestone Reached</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">When goal milestones are achieved</div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Team Notifications Divider -->
                                <div style="margin-top: 20px; margin-bottom: 16px; border-top: 1px solid var(--color-border-primary); padding-top: 16px;">
                                    <p style="font-size: 14px; font-weight: 600; color: var(--color-text-primary); margin-bottom: 12px;">Team Notifications</p>
                                </div>

                                <div style="display: grid; gap: 12px;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="teamCreated" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">Team Created</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">When new teams are created</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="teamMemberJoined" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">Team Member Joined</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">When members join teams</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="teamMemberLeft" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: var(--color-text-primary);">Team Member Left</div>
                                            <div style="font-size: 12px; color: var(--color-text-muted);">When members leave teams</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="settingsDisabled" style="color: var(--color-text-muted); font-style: italic;">
                            Connect to Telegram first to configure notification settings.
                        </div>
                    </div>

                    <!-- Bot Commands Help -->
                    <div style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px;">
                        <h4 style="margin-bottom: 16px;">Bot Commands</h4>
                        <p style="color: var(--color-text-secondary); margin-bottom: 16px; font-size: 14px;">Available commands for your Telegram bot:</p>

                        <div style="display: grid; gap: 12px;">
                            <div style="padding: 12px; background-color: var(--color-surface); border-radius: 6px;">
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--accent-success);">/start</code>
                                <span style="color: var(--color-text-secondary); margin-left: 8px;">Show welcome message and available commands</span>
                            </div>

                            <div style="padding: 12px; background-color: var(--color-surface); border-radius: 6px;">
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--accent-success);">/link {invite_code}</code>
                                <span style="color: var(--color-text-secondary); margin-left: 8px;">Link the bot to your server</span>
                            </div>

                            <div style="padding: 12px; background-color: var(--color-surface); border-radius: 6px;">
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--accent-success);">/goals</code>
                                <span style="color: var(--color-text-secondary); margin-left: 8px;">View all active community goals</span>
                            </div>

                            <div style="padding: 12px; background-color: var(--color-surface); border-radius: 6px;">
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--accent-success);">/stats</code>
                                <span style="color: var(--color-text-secondary); margin-left: 8px;">View server goal statistics (total, completed, active)</span>
                            </div>

                            <div style="padding: 12px; background-color: var(--color-surface); border-radius: 6px;">
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--accent-success);">/leaderboard</code>
                                <span style="color: var(--color-text-secondary); margin-left: 8px;">Top 10 goal contributors with medals</span>
                            </div>

                            <div style="padding: 12px; background-color: var(--color-surface); border-radius: 6px;">
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--accent-success);">/upcoming</code>
                                <span style="color: var(--color-text-secondary); margin-left: 8px;">Active goals sorted by deadline (urgent first)</span>
                            </div>

                            <div style="padding: 12px; background-color: var(--color-surface); border-radius: 6px;">
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--accent-success);">/help</code>
                                <span style="color: var(--color-text-secondary); margin-left: 8px;">Show detailed help and commands</span>
                            </div>
                        </div>

                        <div style="background-color: var(--color-surface); border-radius: 6px; padding: 16px; margin-top: 16px; border-left: 4px solid var(--accent-warning);">
                            <p style="margin: 0; color: var(--color-text-secondary); font-size: 14px;">
                                <strong style="color: var(--color-text-primary);">Server Invite Code:</strong>
                                <code style="background-color: var(--color-bg-primary); padding: 4px 8px; border-radius: 4px; color: var(--color-text-primary);">{{ $server->invite_code }}</code>
                                <br><small style="color: var(--color-text-muted);">Use this code with the /link command</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function showTab(tabName, element) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked link
    element.classList.add('active');
    
    // Update URL hash to preserve tab state
    window.location.hash = tabName;
}

function toggleRoleMembers(roleId) {
    const element = document.getElementById('role-members-' + roleId);
    element.style.display = element.style.display === 'none' ? 'block' : 'none';
    
    // Hide edit form if open
    const editElement = document.getElementById('role-edit-' + roleId);
    if (editElement) {
        editElement.style.display = 'none';
    }
}

function toggleEditRole(roleId) {
    const element = document.getElementById('role-edit-' + roleId);
    element.style.display = element.style.display === 'none' ? 'block' : 'none';
    
    // Hide members form if open
    const membersElement = document.getElementById('role-members-' + roleId);
    if (membersElement) {
        membersElement.style.display = 'none';
    }
}

function toggleChannelEdit(channelId) {
    const element = document.getElementById('channel-edit-' + channelId);
    element.style.display = element.style.display === 'none' ? 'block' : 'none';
}

function toggleKebabMenu(menuId) {
    // Close all other kebab menus
    document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
        if (dropdown.id !== 'kebab-' + menuId) {
            dropdown.classList.remove('active');
        }
    });
    
    // Toggle the clicked menu
    const menu = document.getElementById('kebab-' + menuId);
    menu.classList.toggle('active');
}

function closeKebabMenu(menuId) {
    const menu = document.getElementById('kebab-' + menuId);
    menu.classList.remove('active');
}

// Close kebab menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.kebab-menu')) {
        document.querySelectorAll('.kebab-dropdown').forEach(dropdown => {
            dropdown.classList.remove('active');
        });
    }
});

// Function to show tab based on session, URL hash, or default to overview
function showTabFromHash() {
    // Check for session active tab first (from server redirect)
    const sessionTab = '{{ session("active_tab") }}';
    const hash = window.location.hash.substring(1); // Remove the # symbol
    const validTabs = ['overview', 'channels', 'members', 'roles', 'tags', 'goals', 'telegram'];
    
    // Priority: 1. Session tab, 2. URL hash, 3. Default to overview
    let tabToShow = 'overview';
    if (sessionTab && validTabs.includes(sessionTab)) {
        tabToShow = sessionTab;
    } else if (validTabs.includes(hash)) {
        tabToShow = hash;
    }
    
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show the appropriate tab
    const tabElement = document.getElementById(tabToShow);
    const linkElement = document.querySelector(`a[onclick*="${tabToShow}"]`);
    
    if (tabElement && linkElement) {
        tabElement.classList.add('active');
        linkElement.classList.add('active');
        // Update URL hash to match
        window.location.hash = tabToShow;
    }
    
    console.log('Active tab set to:', tabToShow, 'Session tab:', sessionTab, 'Hash:', hash);
}

// Initialize tab on page load
document.addEventListener('DOMContentLoaded', function() {
    showTabFromHash();
});

// Handle browser back/forward buttons
window.addEventListener('hashchange', function() {
    showTabFromHash();
});

// Tag management functions
const tagOptions = {
    game: ['cs2', 'deep_rock_galactic', 'gtfo'],
    skill_level: ['beginner', 'intermediate', 'advanced', 'expert', 'casual', 'competitive'],
    region: ['na_east', 'na_west', 'eu_west', 'eu_east', 'asia', 'oceania', 'south_america', 'africa'],
    language: ['english', 'spanish', 'french', 'german', 'russian', 'chinese', 'japanese', 'korean', 'portuguese', 'italian'],
    activity_time: ['morning', 'afternoon', 'evening', 'late_night', 'weekends', 'weekdays', '24_7']
};

function updateTagValueOptions() {
    const typeSelect = document.getElementById('tag_type');
    const valueSelect = document.getElementById('tag_value');
    const selectedType = typeSelect.value;
    
    // Clear existing options
    valueSelect.innerHTML = '';
    
    if (selectedType && tagOptions[selectedType]) {
        valueSelect.disabled = false;
        valueSelect.innerHTML = '<option value="">Select value...</option>';
        
        tagOptions[selectedType].forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option;
            optionElement.textContent = option.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            valueSelect.appendChild(optionElement);
        });
        
        // Load tag suggestions
        loadTagSuggestions();
    } else {
        valueSelect.disabled = true;
        valueSelect.innerHTML = '<option value="">Select tag type first...</option>';
        hideTagSuggestions();
    }
}

function loadTagSuggestions() {
    fetch('{{ route("server.admin.tag.suggestions", $server) }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Tag suggestions response:', data);
        if (data.suggestions && data.suggestions.length > 0) {
            showTagSuggestions(data.suggestions);
        } else {
            hideTagSuggestions();
        }
    })
    .catch(error => {
        console.error('Error loading tag suggestions:', error);
        hideTagSuggestions();
    });
}

function showTagSuggestions(suggestions) {
    const suggestionsDiv = document.getElementById('tagSuggestions');
    const suggestionsList = document.getElementById('suggestedTagsList');
    
    suggestionsList.innerHTML = '';
    
    suggestions.forEach(suggestion => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-sm';
        button.style.cssText = 'background-color: #3f3f46; color: #b3b3b5; padding: 6px 12px; font-size: 12px; margin-right: 8px; margin-bottom: 8px;';
        button.textContent = `${suggestion.type}: ${suggestion.value}`.replace(/_/g, ' ');
        button.onclick = () => addSuggestedTag(suggestion.type, suggestion.value);
        suggestionsList.appendChild(button);
    });
    
    suggestionsDiv.style.display = 'block';
}

function hideTagSuggestions() {
    document.getElementById('tagSuggestions').style.display = 'none';
}

function addSuggestedTag(tagType, tagValue) {
    document.getElementById('tag_type').value = tagType;
    updateTagValueOptions();
    setTimeout(() => {
        document.getElementById('tag_value').value = tagValue;
    }, 100);
}

// Handle tag form submission
document.getElementById('addTagForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const tagType = document.getElementById('tag_type').value;
    const tagValue = document.getElementById('tag_value').value;
    
    if (!tagType || !tagValue) {
        alert('Please select both tag type and value');
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('tag_type', tagType);
    formData.append('tag_value', tagValue);
    
    fetch('{{ route("server.admin.tag.add", $server) }}', {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            location.reload(); // Reload to show updated tags
        } else {
            alert(data.message || 'Error adding tag');
        }
    })
    .catch(error => {
        console.error('Error adding tag:', error);
        alert('Error adding tag: ' + error.message);
    });
});

function removeTag(tagId) {
    if (!confirm('Remove this tag?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
    formData.append('_method', 'DELETE');
    
    fetch(`{{ url('servers/' . $server->id . '/admin/tags') }}/${tagId}`, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload(); // Reload to show updated tags
        } else {
            alert(data.message || 'Error removing tag');
        }
    })
    .catch(error => {
        console.error('Error removing tag:', error);
        alert('Error removing tag');
    });
}

// Goals management functions
const gameAppIdToName = {
    '730': 'Counter-Strike 2',
    '548430': 'Deep Rock Galactic',
    '493520': 'GTFO'
};

function updateGoalFields() {
    const goalType = document.getElementById('goal_type').value;
    const achievementFields = document.getElementById('achievementFields');
    
    if (goalType === 'achievement') {
        achievementFields.style.display = 'block';
    } else {
        achievementFields.style.display = 'none';
    }
}

function clearGoalForm() {
    document.getElementById('createGoalForm').reset();
    document.getElementById('achievementFields').style.display = 'none';
}

// Handle goal form submission
document.getElementById('createGoalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const gameAppId = document.getElementById('game_appid').value;
    const goalData = {
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        title: document.getElementById('goal_title').value,
        description: document.getElementById('goal_description').value,
        goal_type: document.getElementById('goal_type').value,
        target_value: parseInt(document.getElementById('target_value').value),
        game_appid: gameAppId,
        game_name: gameAppId ? gameAppIdToName[gameAppId] : null,
        deadline: document.getElementById('deadline').value,
        target_criteria: [], // Default empty array (not object)
        difficulty: 'medium', // Default value
        visibility: 'public', // Default value
        rewards: [], // Add missing rewards array
        goal_settings: [], // Add missing goal_settings array
        milestones: [] // Add missing milestones array
    };

    if (goalData.goal_type === 'achievement') {
        goalData.target_criteria = {
            achievement_id: document.getElementById('achievement_id').value
        };
    }

    fetch('{{ route("server.goals.store", $server) }}', {
        method: 'POST',
        body: JSON.stringify(goalData),
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': goalData._token
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => { throw err; });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Goal created successfully!');
            location.reload();
        } else {
            // Handle validation errors
            if (data.errors) {
                let errorMessages = Object.values(data.errors).flat().join('\n');
                alert('Validation failed:\n' + errorMessages);
            } else {
                alert(data.message || 'Error creating goal');
            }
        }
    })
    .catch(error => {
        console.error('Error creating goal:', error);
        alert('An unexpected error occurred. Please check the console for details.');
    });
});

function toggleGoalActions(goalId) {
    // Close all other action menus
    document.querySelectorAll('.goal-actions').forEach(menu => {
        if (menu.id !== 'goal-actions-' + goalId) {
            menu.style.display = 'none';
        }
    });
    
    // Toggle the clicked menu
    const menu = document.getElementById('goal-actions-' + goalId);
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function syncGoalProgress(goalId) {
    if (confirm('Sync progress from Steam API? This may take a moment.')) {
        fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}/sync`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Progress synced successfully!');
                location.reload();
            } else {
                alert(data.message || 'Error syncing progress');
            }
        })
        .catch(error => {
            console.error('Error syncing progress:', error);
            alert('Error syncing progress');
        });
    }
}

function editGoal(goalId) {
    // For now, just redirect to a basic edit (could be enhanced with modal)
    const newTitle = prompt('Enter new goal title:');
    if (newTitle && newTitle.trim()) {
        fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                title: newTitle.trim()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error updating goal');
            }
        })
        .catch(error => {
            console.error('Error updating goal:', error);
            alert('Error updating goal');
        });
    }
}

function deleteGoal(goalId) {
    if (confirm('Are you sure you want to delete this goal? This action cannot be undone.')) {
        fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error deleting goal');
            }
        })
        .catch(error => {
            console.error('Error deleting goal:', error);
            alert('Error deleting goal');
        });
    }
}

function showGoalDetails(goalId) {
    window.location.href = `{{ url('servers/' . $server->id . '/goals') }}/${goalId}`;
}

// Close goal action menus when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.goal-actions') && !event.target.matches('[onclick*="toggleGoalActions"]')) {
        document.querySelectorAll('.goal-actions').forEach(menu => {
            menu.style.display = 'none';
        });
    }
});

// Telegram Bot Functions
function loadTelegramStatus() {
    // Show loading state
    showTelegramStatus('Checking...', '#71717a');

    // Create abort controller for timeout
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout

    fetch('{{ route("server.telegram.status", $server) }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            throw new Error('Server returned ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            updateTelegramUI(data.status);
        } else {
            console.error('Error loading Telegram status:', data);
            showTelegramStatus('Error', '#dc2626');
            showTelegramSetupInstructions();
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        console.error('Error loading Telegram status:', error);
        if (error.name === 'AbortError') {
            showTelegramStatus('Timeout', '#dc2626');
        } else {
            showTelegramStatus('Error', '#dc2626');
        }
        showTelegramSetupInstructions();
    });
}

// Helper function to show setup instructions on error
function showTelegramSetupInstructions() {
    document.getElementById('telegramInfo').style.display = 'none';
    document.getElementById('setupInstructions').style.display = 'block';
    document.getElementById('notificationSettings').style.display = 'none';
    document.getElementById('settingsDisabled').style.display = 'block';
}

function updateTelegramUI(status) {
    const isLinked = status.is_linked;

    // Update status indicator
    if (isLinked) {
        showTelegramStatus('Connected', '#10b981');

        // Show connection info
        document.getElementById('telegramInfo').style.display = 'block';
        document.getElementById('setupInstructions').style.display = 'none';

        // Populate connection details
        document.getElementById('chatId').textContent = status.chat_id || '-';
        document.getElementById('chatName').textContent =
            (status.settings && status.settings.chat_title) || 'Telegram Group';
        document.getElementById('linkedAt').textContent = status.linked_at
            ? new Date(status.linked_at).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
              })
            : '-';
        document.getElementById('notificationsEnabled').textContent =
            (status.settings && status.settings.notifications_enabled) ? 'Enabled' : 'Disabled';

        // Show action buttons
        document.getElementById('unlinkBtn').style.display = 'inline-block';
        document.getElementById('testBtn').style.display = 'inline-block';

        // Show notification settings
        document.getElementById('notificationSettings').style.display = 'block';
        document.getElementById('settingsDisabled').style.display = 'none';

        // Update notification checkboxes
        updateNotificationCheckboxes(status.settings);

    } else {
        showTelegramStatus('Not Connected', '#71717a');

        // Show setup instructions
        document.getElementById('telegramInfo').style.display = 'none';
        document.getElementById('setupInstructions').style.display = 'block';

        // Hide notification settings
        document.getElementById('notificationSettings').style.display = 'none';
        document.getElementById('settingsDisabled').style.display = 'block';
    }
}

function showTelegramStatus(text, color) {
    const statusIndicator = document.getElementById('statusIndicator');
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');

    statusText.textContent = text;
    statusDot.style.backgroundColor = color;
    statusIndicator.style.backgroundColor = color === '#10b981' ? 'rgba(16, 185, 129, 0.15)' : '#3f3f46';
    statusIndicator.style.color = '#efeff1';
}

function updateNotificationCheckboxes(settings) {
    if (!settings) return;

    // Main toggle
    document.getElementById('notificationsToggle').checked = settings.notifications_enabled || false;

    // Notification types
    const types = settings.notification_types || {};
    document.getElementById('goalCompleted').checked = types.goal_completed || false;
    document.getElementById('goalProgress').checked = types.goal_progress || false;
    document.getElementById('newGoal').checked = types.new_goal || false;
    document.getElementById('userJoined').checked = types.user_joined || false;
    document.getElementById('milestoneReached').checked = types.milestone_reached || false;

    // Team notification types
    document.getElementById('teamCreated').checked = types.team_created || false;
    document.getElementById('teamMemberJoined').checked = types.team_member_joined || false;
    document.getElementById('teamMemberLeft').checked = types.team_member_left || false;

    // Show/hide notification types based on main toggle
    const notificationTypes = document.getElementById('notificationTypes');
    notificationTypes.style.display = settings.notifications_enabled ? 'block' : 'none';
}

function updateNotificationSettings() {
    const notificationsEnabled = document.getElementById('notificationsToggle').checked;

    // Show/hide notification types
    const notificationTypes = document.getElementById('notificationTypes');
    notificationTypes.style.display = notificationsEnabled ? 'block' : 'none';

    // Collect settings
    const settings = {
        _token: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        notifications_enabled: notificationsEnabled,
        notification_types: {
            goal_completed: document.getElementById('goalCompleted').checked,
            goal_progress: document.getElementById('goalProgress').checked,
            new_goal: document.getElementById('newGoal').checked,
            user_joined: document.getElementById('userJoined').checked,
            milestone_reached: document.getElementById('milestoneReached').checked,
            team_created: document.getElementById('teamCreated').checked,
            team_member_joined: document.getElementById('teamMemberJoined').checked,
            team_member_left: document.getElementById('teamMemberLeft').checked,
        }
    };

    // Send update request
    fetch('{{ route("server.telegram.settings", $server) }}', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': settings._token
        },
        body: JSON.stringify(settings)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Notification settings updated successfully');
            const saveIndicator = document.getElementById('saveIndicator');
            saveIndicator.style.display = 'inline';
            setTimeout(() => { saveIndicator.style.display = 'none'; }, 2000);
        } else {
            alert('Error updating settings: ' + (data.message || 'Unknown error'));
            // Reload to reset checkboxes on error
            loadTelegramStatus();
        }
    })
    .catch(error => {
        console.error('Error updating notification settings:', error);
        alert('Error updating settings');
        // Reload to reset checkboxes on error
        loadTelegramStatus();
    });
}

function unlinkTelegram() {
    if (!confirm('Are you sure you want to unlink this server from Telegram? Notifications will stop immediately.')) {
        return;
    }
    
    fetch('{{ route("server.telegram.unlink", $server) }}', {
        method: 'DELETE',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Server unlinked from Telegram successfully');
            loadTelegramStatus(); // Refresh status
        } else {
            alert('Error unlinking server: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error unlinking Telegram:', error);
        alert('Error unlinking server');
    });
}

function testTelegramMessage() {
    const testMessage = '🤖 <b>Test Message from Glyph Bot</b>\\n\\nThis is a test notification from your {{ $server->name }} server!\\n\\n✅ Telegram integration is working correctly.';

    fetch('{{ route("server.telegram.test", $server) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            chat_id: document.getElementById('chatId').textContent,
            message: testMessage
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Test message sent successfully! Check your Telegram chat.');
        } else {
            alert('Error sending test message: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error sending test message:', error);
        alert('Error sending test message');
    });
}

// Load Telegram status when the telegram tab is shown
function showTab(tabName, element) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all links
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked link
    element.classList.add('active');
    
    // Update URL hash to preserve tab state
    window.location.hash = tabName;
    
    // Load Telegram status if telegram tab is selected
    if (tabName === 'telegram') {
        loadTelegramStatus();
    }
}

// ============================================
// Role Permissions Modal Functions (Phase 5)
// ============================================

// Permission modal state
let currentRolePermissions = [];
let permissionConfig = null;

// Store role data for all roles (used to persist permissions after AJAX save)
let rolePermissionsData = {
    @foreach($server->roles as $role)
    {{ $role->id }}: {
        id: {{ $role->id }},
        name: '{{ addslashes($role->name) }}',
        color: '{{ $role->color }}',
        permissions: @json($role->permissions ?? [])
    },
    @endforeach
};

// Load permission config on page load
async function loadPermissionConfig() {
    try {
        const response = await fetch('{{ route("server.admin.permissions.config", $server) }}');
        if (response.ok) {
            const data = await response.json();
            permissionConfig = data.categories;
            console.log('Permission config loaded:', permissionConfig);
        } else {
            console.error('Failed to load permission config:', response.status);
        }
    } catch (error) {
        console.error('Failed to load permission config:', error);
    }
}

// Open permissions modal from stored role data (uses rolePermissionsData object)
function openPermissionsModalFromData(roleId) {
    const roleData = rolePermissionsData[roleId];
    if (!roleData) {
        alert('Role not found. Please refresh the page.');
        return;
    }
    openPermissionsModal(roleId, roleData.name, roleData.color, roleData.permissions);
}

// Open permissions modal
function openPermissionsModal(roleId, roleName, roleColor, permissions) {
    if (!permissionConfig) {
        alert('Permission configuration not loaded. Please refresh the page.');
        return;
    }

    currentRolePermissions = permissions || [];

    document.getElementById('modal-role-id').value = roleId;
    document.getElementById('modal-role-badge').textContent = roleName;
    document.getElementById('modal-role-badge').style.backgroundColor = roleColor;
    document.getElementById('modal-role-badge').style.color = isLightColor(roleColor) ? '#1f1f23' : '#efeff1';

    // Build permission categories
    const container = document.getElementById('permission-categories');
    container.innerHTML = '';

    for (const [categoryKey, category] of Object.entries(permissionConfig)) {
        const categoryDiv = document.createElement('div');
        categoryDiv.className = 'permission-category';

        let permissionsHtml = '';
        for (const [permKey, perm] of Object.entries(category.permissions)) {
            const isDangerous = perm.dangerous || false;
            const isChecked = currentRolePermissions.includes(permKey);

            permissionsHtml += `
                <div class="permission-item ${isDangerous ? 'dangerous' : ''}">
                    <label class="toggle-switch permission-toggle">
                        <input type="checkbox"
                               name="permissions[]"
                               value="${permKey}"
                               ${isChecked ? 'checked' : ''}>
                        <span class="toggle-slider"></span>
                    </label>
                    <div class="permission-info">
                        <div class="permission-label">
                            ${perm.label}
                            ${isDangerous ? '<span class="danger-badge">DANGEROUS</span>' : ''}
                        </div>
                        <div class="permission-description">${perm.description}</div>
                    </div>
                </div>
            `;
        }

        categoryDiv.innerHTML = `
            <h4>${category.label}</h4>
            ${permissionsHtml}
        `;

        container.appendChild(categoryDiv);
    }

    // Add Administrator permission at the top (special category)
    const adminDiv = document.createElement('div');
    adminDiv.className = 'permission-category';
    const hasAdmin = currentRolePermissions.includes('administrator');
    adminDiv.innerHTML = `
        <h4>Special</h4>
        <div class="permission-item dangerous">
            <label class="toggle-switch permission-toggle">
                <input type="checkbox"
                       name="permissions[]"
                       value="administrator"
                       ${hasAdmin ? 'checked' : ''}>
                <span class="toggle-slider"></span>
            </label>
            <div class="permission-info">
                <div class="permission-label">
                    Administrator
                    <span class="danger-badge">DANGEROUS</span>
                </div>
                <div class="permission-description">Grants all permissions. Members with this permission can perform any action regardless of other permission settings.</div>
            </div>
        </div>
    `;
    container.insertBefore(adminDiv, container.firstChild);

    document.getElementById('role-permissions-modal').style.display = 'flex';
}

// Close permissions modal
function closePermissionsModal() {
    document.getElementById('role-permissions-modal').style.display = 'none';
    currentRolePermissions = [];
}

// Save role permissions
async function saveRolePermissions() {
    const roleId = document.getElementById('modal-role-id').value;
    const checkboxes = document.querySelectorAll('#permission-categories input[type="checkbox"]:checked');
    const permissions = Array.from(checkboxes).map(cb => cb.value);

    try {
        const response = await fetch(`{{ url('servers/' . $server->id . '/admin/roles') }}/${roleId}/permissions`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ permissions })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Update the stored role permissions data so reopening modal shows correct values
            if (rolePermissionsData[roleId]) {
                rolePermissionsData[roleId].permissions = permissions;
            }

            closePermissionsModal();
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.className = 'alert alert-success';
            successDiv.textContent = 'Permissions updated successfully!';
            successDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1100; padding: 12px 20px; background-color: #10b981; color: white; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
            document.body.appendChild(successDiv);
            setTimeout(() => successDiv.remove(), 3000);
        } else {
            alert(data.error || 'Error updating permissions');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating permissions');
    }
}

// Helper to determine if color is light
function isLightColor(hexColor) {
    const hex = hexColor.replace('#', '');
    const r = parseInt(hex.substr(0, 2), 16);
    const g = parseInt(hex.substr(2, 2), 16);
    const b = parseInt(hex.substr(4, 2), 16);
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;
    return brightness > 155;
}

// ============================================
// Channel Permission Overrides Functions (Phase 5)
// ============================================

// Toggle channel overrides section visibility
function toggleChannelOverrides(channelId) {
    const el = document.getElementById(`channel-overrides-${channelId}`);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
}

// Save channel permission overrides
async function saveChannelOverrides(channelId) {
    const selects = document.querySelectorAll(`#channel-overrides-${channelId} .channel-override-select`);
    const overrides = [];

    selects.forEach(select => {
        overrides.push({
            role_id: parseInt(select.dataset.role),
            permission: select.dataset.permission,
            value: select.value
        });
    });

    try {
        const response = await fetch(`{{ url('servers/' . $server->id . '/admin/channels') }}/${channelId}/permissions`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ overrides })
        });

        const data = await response.json();

        if (response.ok && data.success) {
            // Show success message
            const successDiv = document.createElement('div');
            successDiv.className = 'alert alert-success';
            successDiv.textContent = 'Channel permissions updated!';
            successDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 1100; padding: 12px 20px; background-color: #10b981; color: white; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
            document.body.appendChild(successDiv);
            setTimeout(() => successDiv.remove(), 3000);
        } else {
            alert(data.error || 'Error updating channel permissions');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error updating channel permissions');
    }
}

// Load config on page load
document.addEventListener('DOMContentLoaded', function() {
    loadPermissionConfig();
});
</script>

{{-- Include Role Permissions Modal Component --}}
@include('components.role-permissions-modal')

@endsection