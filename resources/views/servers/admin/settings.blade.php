@extends('layouts.app')

@section('title', 'Server Settings - ' . $server->name)

@push('styles')
<style>
    .settings-sidebar {
        width: 200px;
        background-color: #18181b;
        padding: 24px;
        border-radius: 12px;
    }
    
    .settings-content {
        flex: 1;
        background-color: #18181b;
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
        background-color: #0e0e10;
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
        background-color: #0e0e10;
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
        border: 2px solid #3f3f46;
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
        color: #71717a;
        font-size: 18px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .kebab-button:hover {
        background-color: #3f3f46;
        color: #ffffff;
    }

    .kebab-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #18181b;
        border: 1px solid #3f3f46;
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
        color: #ffffff;
        text-align: left;
        cursor: pointer;
        transition: background-color 0.2s;
        font-size: 14px;
    }

    .kebab-option:hover {
        background-color: #3f3f46;
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
</style>
@endpush

@section('content')
<nav class="navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="{{ route('dashboard') }}" class="navbar-brand">Glyph</a>
            <div class="navbar-nav">
                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm" style="margin-right: 8px;">Dashboard</a>
                <a href="{{ route('server.show', $server) }}" class="btn btn-secondary btn-sm">Back to Server</a>
            </div>
        </div>
    </div>
</nav>

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

                        <div style="margin-top: 24px; padding: 16px; background-color: #0e0e10; border-radius: 8px;">
                            <p style="font-weight: 600; margin-bottom: 8px;">Server Info</p>
                            <p style="font-size: 14px; color: #b3b3b5;">Created: {{ $server->created_at->format('F j, Y') }}</p>
                            <p style="font-size: 14px; color: #b3b3b5;">Members: {{ $server->members->count() }}</p>
                            <p style="font-size: 14px; color: #b3b3b5;">Invite Code: <code style="background-color: #18181b; padding: 2px 6px; border-radius: 4px;">{{ $server->invite_code }}</code></p>
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
                            <div style="background-color: #dc2626; color: white; padding: 12px; border-radius: 4px; margin-bottom: 16px;">
                                <ul style="margin: 0; padding-left: 20px;">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <div style="display: flex; gap: 12px;">
                            <input type="text" name="name" placeholder="channel-name" pattern="[a-z0-9-]+" required style="flex: 3;" value="{{ old('name') }}">
                            <select name="type" required style="flex: 1;">
                                <option value="text" {{ old('type') === 'text' ? 'selected' : '' }}>Text Channel</option>
                                <option value="voice" {{ old('type') === 'voice' ? 'selected' : '' }}>Voice Channel</option>
                            </select>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                        <small style="color: #71717a; font-size: 12px;">Use lowercase letters, numbers, and hyphens only</small>
                    </form>

                    <h4 style="margin-bottom: 16px;">Existing Channels</h4>
                    @foreach(['text' => 'Text Channels', 'voice' => 'Voice Channels'] as $type => $label)
                        <p style="font-size: 14px; font-weight: 600; color: #71717a; text-transform: uppercase; margin-top: 24px; margin-bottom: 12px;">{{ $label }}</p>
                        @foreach($server->channels->where('type', $type) as $channel)
                            <div style="background-color: #0e0e10; border-radius: 8px; margin-bottom: 12px; padding: 12px;">
                                <div class="channel-item" style="background-color: transparent; margin: 0; padding: 0;">
                                    <div>
                                        <span style="color: #71717a; margin-right: 8px;">{{ $type === 'text' ? '#' : '🔊' }}</span>
                                        <span>{{ $channel->name }}</span>
                                    </div>
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
                                
                                <!-- Edit Channel Form -->
                                <div id="channel-edit-{{ $channel->id }}" style="display: none; margin-top: 16px;">
                                    <form method="POST" action="{{ route('server.admin.channel.update', [$server, $channel]) }}">
                                        @csrf
                                        @method('PUT')
                                        <h5 style="margin-bottom: 12px;">Edit Channel</h5>
                                        <div style="display: flex; gap: 12px;">
                                            <input type="text" name="name" value="{{ $channel->name }}" pattern="[a-z0-9-]+" required style="flex: 3;" placeholder="channel-name">
                                            <select name="type" required style="flex: 1;">
                                                <option value="text" {{ $channel->type === 'text' ? 'selected' : '' }}>Text Channel</option>
                                                <option value="voice" {{ $channel->type === 'voice' ? 'selected' : '' }}>Voice Channel</option>
                                            </select>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                            <button type="button" class="btn btn-secondary" onclick="toggleChannelEdit('{{ $channel->id }}')">Cancel</button>
                                        </div>
                                        <small style="color: #71717a; font-size: 12px; display: block; margin-top: 8px;">Use lowercase letters, numbers, and hyphens only</small>
                                    </form>
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
                                <div style="font-size: 14px; color: #71717a;">{{ '@' . ($member->username ?? 'No username') }}</div>
                                <div style="margin-top: 4px;">
                                    @foreach($member->roles()->wherePivot('server_id', $server->id)->get() as $role)
                                        <span class="role-badge" style="background-color: {{ $role->color }}; color: white;">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                    @if($member->pivot->is_muted)
                                        <span class="role-badge" style="background-color: #dc2626; color: white;">Muted</span>
                                    @endif
                                    @if($member->pivot->is_banned)
                                        <span class="role-badge" style="background-color: #991b1b; color: white;">Banned</span>
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
                        <div style="padding: 16px; background-color: #0e0e10; border-radius: 8px; margin-bottom: 12px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div>
                                    <span class="role-badge" style="background-color: {{ $role->color }}; color: white; font-size: 14px;">
                                        {{ $role->name }}
                                    </span>
                                    <span style="margin-left: 12px; color: #71717a; font-size: 14px;">
                                        {{ $role->users()->wherePivot('server_id', $server->id)->count() }} members
                                    </span>
                                </div>
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
                                    
                                    @foreach($role->users()->wherePivot('server_id', $server->id)->get() as $user)
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px; background-color: #18181b; border-radius: 4px; margin-bottom: 4px;">
                                            <span>{{ $user->display_name }}</span>
                                            <form method="POST" action="{{ route('server.admin.role.remove', [$server, $user, $role]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm" style="padding: 4px 8px; font-size: 12px;">Remove</button>
                                            </form>
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
                    <p style="color: #b3b3b5; margin-bottom: 24px; font-size: 14px;">Tags help users discover your server based on games, skill levels, regions, and other preferences.</p>
                    
                    <!-- Add Tag Form -->
                    <div style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-bottom: 32px;">
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
                            <h5 style="margin-bottom: 12px; color: #efeff1;">Suggested Tags</h5>
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
                                        <h5 style="color: #b3b3b5; font-size: 14px; font-weight: 600; text-transform: uppercase; margin-bottom: 8px;">
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
                            <div style="text-align: center; padding: 40px; background-color: #0e0e10; border-radius: 8px; border: 2px dashed #3f3f46;">
                                <p style="color: #71717a; margin-bottom: 16px;">No tags added yet</p>
                                <p style="color: #b3b3b5; font-size: 14px;">Add tags to help users discover your server based on games, skill levels, and preferences.</p>
                            </div>
                        @endif
                    </div>

                    <!-- Tag Analytics -->
                    @if($server->tags && $server->tags->count() > 0)
                        <div style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-top: 32px;">
                            <h4 style="margin-bottom: 16px;">Tag Performance</h4>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 600; color: #667eea; margin-bottom: 4px;">{{ $server->tags->count() }}</div>
                                    <div style="color: #b3b3b5; font-size: 14px;">Total Tags</div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 600; color: #10b981; margin-bottom: 4px;">{{ $server->tags->where('tag_type', 'game')->count() }}</div>
                                    <div style="color: #b3b3b5; font-size: 14px;">Game Tags</div>
                                </div>
                                <div style="text-align: center;">
                                    <div style="font-size: 24px; font-weight: 600; color: #f59e0b; margin-bottom: 4px;">{{ $server->members->count() }}</div>
                                    <div style="color: #b3b3b5; font-size: 14px;">Members</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Goals Tab -->
                <div id="goals" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Community Goals</h3>
                    <p style="color: #b3b3b5; margin-bottom: 24px; font-size: 14px;">Create and manage community goals to engage your members and track achievements together.</p>
                    
                    <!-- Create Goal Form -->
                    <div style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-bottom: 32px;">
                        <h4 style="margin-bottom: 16px;">Create New Goal</h4>
                        
                        <form id="createGoalForm" style="display: grid; gap: 16px;">
                            @csrf
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="goal_title">Goal Title</label>
                                    <input type="text" id="goal_title" name="title" placeholder="e.g., Reach 100 CS2 Wins" required>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="goal_type">Goal Type</label>
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
                            
                            <div class="form-group" style="margin: 0;">
                                <label for="goal_description">Description</label>
                                <textarea id="goal_description" name="description" rows="3" placeholder="Describe the goal and how members can contribute..."></textarea>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="target_value">Target Value</label>
                                    <input type="number" id="target_value" name="target_value" placeholder="100" required>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="game_appid">Game (Optional)</label>
                                    <select id="game_appid" name="game_appid">
                                        <option value="">Any Game</option>
                                        <option value="730">Counter-Strike 2</option>
                                        <option value="570">Dota 2</option>
                                        <option value="230410">Warframe</option>
                                        <option value="1172470">Apex Legends</option>
                                        <option value="252490">Rust</option>
                                        <option value="578080">PUBG</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin: 0;">
                                    <label for="deadline">Deadline (Optional)</label>
                                    <input type="date" id="deadline" name="deadline">
                                </div>
                            </div>

                            <div id="achievementFields" style="display: none;">
                                <div class="form-group" style="margin: 0;">
                                    <label for="achievement_id">Steam Achievement</label>
                                    <input type="text" id="achievement_id" name="achievement_id" placeholder="e.g., WIN_BOMB_PLANT">
                                    <small style="color: #71717a; font-size: 12px;">Steam achievement API name (will sync automatically)</small>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                                <button type="button" onclick="clearGoalForm()" class="btn btn-secondary">Clear</button>
                                <button type="submit" class="btn btn-primary">Create Goal</button>
                            </div>
                        </form>
                    </div>

                    <!-- Active Goals -->
                    <h4 style="margin-bottom: 16px;">Active Goals</h4>
                    <div id="activeGoals">
                        @if($server->goals && $server->goals->where('status', 'active')->count() > 0)
                            @foreach($server->goals->where('status', 'active') as $goal)
                                <div class="goal-card" data-goal-id="{{ $goal->id }}" style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-bottom: 16px; border-left: 4px solid #667eea;">
                                    <div style="display: flex; justify-content: between; align-items: flex-start; margin-bottom: 12px;">
                                        <div style="flex: 1;">
                                            <h5 style="color: #efeff1; margin-bottom: 4px;">{{ $goal->title }}</h5>
                                            <p style="color: #b3b3b5; font-size: 14px; margin-bottom: 8px;">{{ $goal->description }}</p>
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <span style="font-size: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 3px; text-transform: uppercase; font-weight: 600;">
                                                    {{ ucfirst($goal->goal_type) }}
                                                </span>
                                                @if($goal->game_appid)
                                                    <span style="font-size: 12px; background-color: #3f3f46; color: #b3b3b5; padding: 2px 6px; border-radius: 3px;">
                                                        {{ $goal->game_name ?? 'Game' }}
                                                    </span>
                                                @endif
                                                @if($goal->deadline)
                                                    <span style="font-size: 12px; color: #f59e0b;">
                                                        📅 Due {{ \Carbon\Carbon::parse($goal->deadline)->diffForHumans() }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div style="text-align: right;">
                                            <button onclick="toggleGoalActions('{{ $goal->id }}')" style="background: none; border: none; color: #71717a; font-size: 18px; cursor: pointer; padding: 4px;">⋮</button>
                                            <div class="goal-actions" id="goal-actions-{{ $goal->id }}" style="display: none; position: absolute; background-color: #18181b; border: 1px solid #3f3f46; border-radius: 6px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3); z-index: 100; min-width: 120px; right: 0; margin-top: 4px;">
                                                <button onclick="syncGoalProgress('{{ $goal->id }}')" style="display: block; width: 100%; padding: 8px 12px; background: none; border: none; color: #ffffff; text-align: left; cursor: pointer; font-size: 14px; border-radius: 6px 6px 0 0;">Sync Progress</button>
                                                <button onclick="editGoal('{{ $goal->id }}')" style="display: block; width: 100%; padding: 8px 12px; background: none; border: none; color: #ffffff; text-align: left; cursor: pointer; font-size: 14px;">Edit Goal</button>
                                                <button onclick="deleteGoal('{{ $goal->id }}')" style="display: block; width: 100%; padding: 8px 12px; background: none; border: none; color: #f87171; text-align: left; cursor: pointer; font-size: 14px; border-radius: 0 0 6px 6px;">Delete Goal</button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div style="margin-bottom: 16px;">
                                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                            <span style="font-size: 14px; color: #b3b3b5;">Progress</span>
                                            <span class="progress-text" style="font-size: 14px; color: #efeff1; font-weight: 600;" data-target="{{ $goal->target_value }}">
                                                {{ $goal->current_value ?? 0 }} / {{ $goal->target_value }}
                                                ({{ $goal->target_value > 0 ? round(($goal->current_value ?? 0) / $goal->target_value * 100, 1) : 0 }}%)
                                            </span>
                                        </div>
                                        <div style="width: 100%; height: 8px; background-color: #3f3f46; border-radius: 4px; overflow: hidden;">
                                            <div class="progress-bar" style="height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: {{ $goal->target_value > 0 ? min(($goal->current_value ?? 0) / $goal->target_value * 100, 100) : 0 }}%; transition: width 0.8s ease;" 
                                                 aria-valuenow="{{ $goal->target_value > 0 ? round(($goal->current_value ?? 0) / $goal->target_value * 100, 1) : 0 }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>

                                    <!-- Participants -->
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span class="participant-count" style="font-size: 14px; color: #b3b3b5;">{{ $goal->participants->count() }} participants</span>
                                            <div class="participants-list" style="display: flex; margin-left: 8px;">
                                                @foreach($goal->participants->take(5) as $participant)
                                                    <img src="{{ $participant->user->profile->avatar_url }}" alt="{{ $participant->user->display_name }}" 
                                                         style="width: 24px; height: 24px; border-radius: 50%; margin-left: -4px; border: 2px solid #18181b;"
                                                         title="{{ $participant->user->display_name }} - {{ $participant->contribution_percentage }}% contribution"
                                                         data-user-id="{{ $participant->user_id }}">
                                                @endforeach
                                                @if($goal->participants->count() > 5)
                                                    <div style="width: 24px; height: 24px; border-radius: 50%; background-color: #3f3f46; margin-left: -4px; border: 2px solid #18181b; display: flex; align-items: center; justify-content: center; color: #b3b3b5; font-size: 10px; font-weight: 600;">
                                                        +{{ $goal->participants->count() - 5 }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 8px;">
                                            <button onclick="showGoalLeaderboard('{{ $goal->id }}')" class="btn btn-sm" style="background-color: #3f3f46; color: #efeff1; padding: 4px 8px; font-size: 12px;">Leaderboard</button>
                                            <button onclick="showGoalDetails('{{ $goal->id }}')" class="btn btn-sm" style="background-color: #667eea; color: white; padding: 4px 8px; font-size: 12px;">View Details</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="text-align: center; padding: 40px; background-color: #0e0e10; border-radius: 8px; border: 2px dashed #3f3f46;">
                                <div style="font-size: 32px; margin-bottom: 16px;">🎯</div>
                                <p style="color: #71717a; margin-bottom: 16px;">No goals created yet</p>
                                <p style="color: #b3b3b5; font-size: 14px;">Create your first community goal to start engaging your members!</p>
                            </div>
                        @endif
                    </div>

                    <!-- Completed Goals -->
                    @if($server->goals && $server->goals->where('status', 'completed')->count() > 0)
                        <div style="margin-top: 32px;">
                            <h4 style="margin-bottom: 16px;">Completed Goals ({{ $server->goals->where('status', 'completed')->count() }})</h4>
                            <div style="max-height: 300px; overflow-y: auto;">
                                @foreach($server->goals->where('status', 'completed')->take(5) as $goal)
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background-color: #0e0e10; border-radius: 6px; margin-bottom: 8px; border-left: 4px solid #10b981;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1; margin-bottom: 2px;">✅ {{ $goal->title }}</div>
                                            <div style="font-size: 12px; color: #b3b3b5;">
                                                Completed {{ $goal->completed_at ? \Carbon\Carbon::parse($goal->completed_at)->diffForHumans() : 'recently' }} • 
                                                {{ $goal->participants->count() }} participants
                                            </div>
                                        </div>
                                        <div style="text-align: right; color: #10b981; font-weight: 600;">
                                            {{ $goal->current_value }}/{{ $goal->target_value }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Goal Analytics -->
                    <div style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-top: 32px;">
                        <h4 style="margin-bottom: 16px;">Goal Analytics</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: #667eea; margin-bottom: 4px;">{{ $server->goals->count() ?? 0 }}</div>
                                <div style="color: #b3b3b5; font-size: 14px;">Total Goals</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: #10b981; margin-bottom: 4px;">{{ $server->goals->where('status', 'completed')->count() ?? 0 }}</div>
                                <div style="color: #b3b3b5; font-size: 14px;">Completed</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: #f59e0b; margin-bottom: 4px;">{{ $server->goals->where('status', 'active')->count() ?? 0 }}</div>
                                <div style="color: #b3b3b5; font-size: 14px;">Active</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 600; color: #ef4444; margin-bottom: 4px;">{{ $server->goals->sum('participants_count') ?? 0 }}</div>
                                <div style="color: #b3b3b5; font-size: 14px;">Participations</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Telegram Bot Tab -->
                <div id="telegram" class="tab-content">
                    <h3 style="margin-bottom: 24px;">Telegram Bot Integration</h3>
                    <p style="color: #b3b3b5; margin-bottom: 24px; font-size: 14px;">Connect your server to Telegram to receive goal notifications and updates in your Telegram group or channel.</p>
                    
                    <!-- Bot Status Card -->
                    <div id="telegramStatusCard" style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-bottom: 32px; position: relative;">
                        <!-- Status Badge - Positioned absolutely in top-right -->
                        <div id="statusIndicator" style="position: absolute; top: 16px; right: 16px; display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; background-color: #3f3f46; color: #efeff1;">
                            <span id="statusDot" style="width: 8px; height: 8px; border-radius: 50%; background-color: #71717a; flex-shrink: 0;"></span>
                            <span id="statusText">Checking...</span>
                        </div>

                        <h4 style="margin: 0 0 16px 0;">Connection Status</h4>
                        
                        <div id="telegramInfo" style="display: none;">
                            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 16px;">
                                <div>
                                    <div style="font-size: 14px; color: #b3b3b5;">Chat ID</div>
                                    <div style="font-weight: 600; color: #efeff1;" id="chatId">-</div>
                                </div>
                                <div>
                                    <div style="font-size: 14px; color: #b3b3b5;">Chat Name</div>
                                    <div style="font-weight: 600; color: #efeff1;" id="chatName">-</div>
                                </div>
                                <div>
                                    <div style="font-size: 14px; color: #b3b3b5;">Linked</div>
                                    <div style="font-weight: 600; color: #efeff1;" id="linkedAt">-</div>
                                </div>
                                <div>
                                    <div style="font-size: 14px; color: #b3b3b5;">Notifications</div>
                                    <div style="font-weight: 600; color: #efeff1;" id="notificationsEnabled">-</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button id="unlinkBtn" class="btn btn-danger btn-sm" onclick="unlinkTelegram()" style="display: none;">Unlink</button>
                                <button id="testBtn" class="btn btn-secondary btn-sm" onclick="testTelegramMessage()" style="display: none;">Send Test Message</button>
                            </div>
                        </div>
                        
                        <div id="setupInstructions" style="display: none;">
                            <div style="background-color: #18181b; border-radius: 6px; padding: 16px; border-left: 4px solid #667eea;">
                                <h5 style="margin-bottom: 12px; color: #efeff1;">How to Connect</h5>
                                <ol style="margin: 0; padding-left: 20px; color: #b3b3b5; font-size: 14px; line-height: 1.6;">
                                    <li>Add <code style="background-color: #0e0e10; padding: 2px 6px; border-radius: 4px; color: #efeff1;">@YourBotName</code> to your Telegram group</li>
                                    <li>Send the command: <code style="background-color: #0e0e10; padding: 2px 6px; border-radius: 4px; color: #efeff1;">/link {{ $server->invite_code }}</code></li>
                                    <li>The bot will automatically link and start sending notifications</li>
                                </ol>
                                <p style="margin-top: 12px; margin-bottom: 0; font-size: 13px; color: #71717a;">
                                    <strong>Note:</strong> Make sure the bot has permission to send messages in your group.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div style="background-color: #0e0e10; border-radius: 8px; padding: 20px; margin-bottom: 32px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                            <h4 style="margin-bottom: 0;">Notification Settings</h4>
                            <span id="saveIndicator" style="display: none; font-size: 12px; color: #10b981;">&#10003; Saved</span>
                        </div>
                        
                        <div id="notificationSettings" style="display: none;">
                            <div style="margin-bottom: 16px;">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" id="notificationsToggle" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                    <span style="font-weight: 600; color: #efeff1;">Enable Telegram Notifications</span>
                                </label>
                                <small style="color: #71717a; font-size: 12px; margin-left: 24px;">Turn off to disable all Telegram notifications for this server</small>
                            </div>
                            
                            <div id="notificationTypes" style="display: none; margin-left: 24px; margin-top: 16px;">
                                <p style="font-size: 14px; font-weight: 600; color: #efeff1; margin-bottom: 12px;">Notification Types</p>
                                
                                <div style="display: grid; gap: 12px;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="goalCompleted" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">Goal Completed</div>
                                            <div style="font-size: 12px; color: #71717a;">When a community goal is completed</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="goalProgress" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">Goal Progress</div>
                                            <div style="font-size: 12px; color: #71717a;">Milestone progress updates (every 10%)</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="newGoal" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">New Goals</div>
                                            <div style="font-size: 12px; color: #71717a;">When new community goals are created</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="userJoined" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">Member Joined</div>
                                            <div style="font-size: 12px; color: #71717a;">When members join goals</div>
                                        </div>
                                    </label>
                                    
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="milestoneReached" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">Milestone Reached</div>
                                            <div style="font-size: 12px; color: #71717a;">When goal milestones are achieved</div>
                                        </div>
                                    </label>
                                </div>

                                <!-- Team Notifications Divider -->
                                <div style="margin-top: 20px; margin-bottom: 16px; border-top: 1px solid #3f3f46; padding-top: 16px;">
                                    <p style="font-size: 14px; font-weight: 600; color: #efeff1; margin-bottom: 12px;">Team Notifications</p>
                                </div>

                                <div style="display: grid; gap: 12px;">
                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="teamCreated" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">Team Created</div>
                                            <div style="font-size: 12px; color: #71717a;">When new teams are created</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="teamMemberJoined" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">Team Member Joined</div>
                                            <div style="font-size: 12px; color: #71717a;">When members join teams</div>
                                        </div>
                                    </label>

                                    <label style="display: flex; align-items: center; cursor: pointer;">
                                        <input type="checkbox" id="teamMemberLeft" onchange="updateNotificationSettings()" style="margin-right: 12px;">
                                        <div>
                                            <div style="font-weight: 600; color: #efeff1;">Team Member Left</div>
                                            <div style="font-size: 12px; color: #71717a;">When members leave teams</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="settingsDisabled" style="color: #71717a; font-style: italic;">
                            Connect to Telegram first to configure notification settings.
                        </div>
                    </div>

                    <!-- Bot Commands Help -->
                    <div style="background-color: #0e0e10; border-radius: 8px; padding: 20px;">
                        <h4 style="margin-bottom: 16px;">Bot Commands</h4>
                        <p style="color: #b3b3b5; margin-bottom: 16px; font-size: 14px;">Available commands for your Telegram bot:</p>

                        <div style="display: grid; gap: 12px;">
                            <div style="padding: 12px; background-color: #18181b; border-radius: 6px;">
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #10b981;">/start</code>
                                <span style="color: #b3b3b5; margin-left: 8px;">Show welcome message and available commands</span>
                            </div>

                            <div style="padding: 12px; background-color: #18181b; border-radius: 6px;">
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #10b981;">/link {invite_code}</code>
                                <span style="color: #b3b3b5; margin-left: 8px;">Link the bot to your server</span>
                            </div>

                            <div style="padding: 12px; background-color: #18181b; border-radius: 6px;">
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #10b981;">/goals</code>
                                <span style="color: #b3b3b5; margin-left: 8px;">View all active community goals</span>
                            </div>

                            <div style="padding: 12px; background-color: #18181b; border-radius: 6px;">
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #10b981;">/stats</code>
                                <span style="color: #b3b3b5; margin-left: 8px;">View server goal statistics (total, completed, active)</span>
                            </div>

                            <div style="padding: 12px; background-color: #18181b; border-radius: 6px;">
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #10b981;">/leaderboard</code>
                                <span style="color: #b3b3b5; margin-left: 8px;">Top 10 goal contributors with medals</span>
                            </div>

                            <div style="padding: 12px; background-color: #18181b; border-radius: 6px;">
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #10b981;">/upcoming</code>
                                <span style="color: #b3b3b5; margin-left: 8px;">Active goals sorted by deadline (urgent first)</span>
                            </div>

                            <div style="padding: 12px; background-color: #18181b; border-radius: 6px;">
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #10b981;">/help</code>
                                <span style="color: #b3b3b5; margin-left: 8px;">Show detailed help and commands</span>
                            </div>
                        </div>

                        <div style="background-color: #18181b; border-radius: 6px; padding: 16px; margin-top: 16px; border-left: 4px solid #f59e0b;">
                            <p style="margin: 0; color: #b3b3b5; font-size: 14px;">
                                <strong style="color: #efeff1;">Server Invite Code:</strong>
                                <code style="background-color: #0e0e10; padding: 4px 8px; border-radius: 4px; color: #efeff1;">{{ $server->invite_code }}</code>
                                <br><small style="color: #71717a;">Use this code with the /link command</small>
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
    game: ['cs2', 'dota2', 'warframe', 'apex_legends', 'rust', 'pubg', 'rainbow_six_siege', 'fall_guys', 'valorant', 'overwatch', 'league_of_legends', 'minecraft'],
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
    '570': 'Dota 2', 
    '230410': 'Warframe',
    '1172470': 'Apex Legends',
    '252490': 'Rust',
    '578080': 'PUBG'
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

function showGoalLeaderboard(goalId) {
    fetch(`{{ url('api/goals') }}/${goalId}/leaderboard`, {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let leaderboardHtml = 'Leaderboard:\n\n';
            data.leaderboard.forEach((participant, index) => {
                leaderboardHtml += `${index + 1}. ${participant.user.display_name} - ${participant.contribution_percentage}%\n`;
            });
            alert(leaderboardHtml);
        } else {
            alert('Error loading leaderboard');
        }
    })
    .catch(error => {
        console.error('Error loading leaderboard:', error);
        alert('Error loading leaderboard');
    });
}

function showGoalDetails(goalId) {
    // For now, just show basic info (could be enhanced with modal)
    alert('Goal details view - this could open a detailed modal with statistics, participant list, and progress history.');
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
    fetch('{{ route("server.telegram.status", $server) }}', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateTelegramUI(data.status);
        } else {
            console.error('Error loading Telegram status:', data);
            showTelegramStatus('Error', '#dc2626');
        }
    })
    .catch(error => {
        console.error('Error loading Telegram status:', error);
        showTelegramStatus('Error', '#dc2626');
    });
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
</script>
@endsection