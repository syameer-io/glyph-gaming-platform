@props(['activeSection' => null])

@php
    $user = auth()->user();
    $unreadDmCount = $user ? $user->getUnreadDmCount() : 0;
    $pendingTeamInvitationsCount = $user ? $user->pending_team_invitations_count : 0;
@endphp

<nav class="navbar-new">
    <div class="container">
        <div class="navbar-new-content">
            {{-- Brand Logo --}}
            <a href="{{ route('dashboard') }}" class="navbar-new-brand">Glyph</a>

            {{-- Desktop Navigation --}}
            <div class="navbar-new-nav">
                {{-- Gaming Dropdown --}}
                <div class="navbar-dropdown">
                    <button type="button" class="navbar-dropdown-trigger {{ in_array($activeSection, ['gaming', 'matchmaking', 'teams', 'lobbies']) ? 'active' : '' }}">
                        Gaming
                        <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="navbar-dropdown-menu">
                        <a href="{{ route('matchmaking.index') }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="22" y1="12" x2="18" y2="12"></line>
                                <line x1="6" y1="12" x2="2" y2="12"></line>
                                <line x1="12" y1="6" x2="12" y2="2"></line>
                                <line x1="12" y1="22" x2="12" y2="18"></line>
                            </svg>
                            Find Teammates
                        </a>
                        <a href="{{ route('teams.index') }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            Teams
                        </a>
                        <a href="{{ route('lobbies.index') }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                            Lobbies
                        </a>
                    </div>
                </div>

                {{-- Social Dropdown --}}
                <div class="navbar-dropdown">
                    <button type="button" class="navbar-dropdown-trigger {{ in_array($activeSection, ['social', 'messages', 'friends']) ? 'active' : '' }}">
                        Social
                        <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="navbar-dropdown-menu">
                        <a href="{{ route('dm.index') }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Messages
                            @if($unreadDmCount > 0)
                                <span class="navbar-badge">{{ $unreadDmCount > 99 ? '99+' : $unreadDmCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('friends.index') }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <line x1="19" y1="8" x2="19" y2="14"></line>
                                <line x1="22" y1="11" x2="16" y2="11"></line>
                            </svg>
                            Friends
                        </a>
                        <a href="{{ route('friends.search') }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                <line x1="11" y1="8" x2="11" y2="14"></line>
                                <line x1="8" y1="11" x2="14" y2="11"></line>
                            </svg>
                            Add Friends
                        </a>
                    </div>
                </div>

                {{-- Servers Direct Link --}}
                <a href="{{ route('servers.discover') }}" class="navbar-link {{ $activeSection === 'servers' ? 'active' : '' }}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    Discover Servers
                </a>
            </div>

            {{-- User Menu --}}
            <div class="navbar-new-right">
                {{-- Team Invitation Dropdown --}}
                <x-team-invitation-dropdown />

                <div class="navbar-dropdown">
                    <button type="button" class="navbar-user-trigger">
                        <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" class="navbar-user-avatar">
                        <span class="navbar-user-name">{{ $user->display_name }}</span>
                        <svg class="navbar-user-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <div class="navbar-dropdown-menu right">
                        {{-- User Card Header --}}
                        <div class="navbar-user-card">
                            <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" class="navbar-user-card-avatar">
                            <div class="navbar-user-card-info">
                                <div class="navbar-user-card-name">{{ $user->display_name }}</div>
                                <div class="navbar-user-card-username">{{ '@' . $user->username }}</div>
                            </div>
                        </div>

                        <a href="{{ route('profile.show', $user->username) }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            View Profile
                        </a>
                        <a href="{{ route('settings') }}" class="navbar-dropdown-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"></circle>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                            </svg>
                            Settings
                        </a>
                        <div class="navbar-dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="navbar-dropdown-item danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                    <polyline points="16 17 21 12 16 7"></polyline>
                                    <line x1="21" y1="12" x2="9" y2="12"></line>
                                </svg>
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Mobile Hamburger --}}
            <button type="button" class="navbar-hamburger" aria-label="Toggle navigation menu">
                <span class="navbar-hamburger-line"></span>
                <span class="navbar-hamburger-line"></span>
                <span class="navbar-hamburger-line"></span>
            </button>
        </div>
    </div>
</nav>

{{-- Mobile Drawer Overlay --}}
<div class="mobile-drawer-overlay" aria-hidden="true"></div>

{{-- Mobile Drawer --}}
<div class="mobile-drawer" aria-label="Navigation menu">
    <div class="mobile-drawer-header">
        <span class="mobile-drawer-title">Glyph</span>
        <button type="button" class="mobile-drawer-close" aria-label="Close menu">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <div class="mobile-drawer-content">
        {{-- Gaming Section --}}
        <div class="mobile-drawer-section">
            <div class="mobile-drawer-section-title">Gaming</div>
            <a href="{{ route('matchmaking.index') }}" class="mobile-drawer-link {{ $activeSection === 'matchmaking' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="22" y1="12" x2="18" y2="12"></line>
                    <line x1="6" y1="12" x2="2" y2="12"></line>
                    <line x1="12" y1="6" x2="12" y2="2"></line>
                    <line x1="12" y1="22" x2="12" y2="18"></line>
                </svg>
                Find Teammates
            </a>
            <a href="{{ route('teams.index') }}" class="mobile-drawer-link {{ $activeSection === 'teams' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Teams
                @if($pendingTeamInvitationsCount > 0)
                    <span class="navbar-badge">{{ $pendingTeamInvitationsCount > 9 ? '9+' : $pendingTeamInvitationsCount }}</span>
                @endif
            </a>
            <a href="{{ route('lobbies.index') }}" class="mobile-drawer-link {{ $activeSection === 'lobbies' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                Lobbies
            </a>
        </div>

        {{-- Social Section --}}
        <div class="mobile-drawer-section">
            <div class="mobile-drawer-section-title">Social</div>
            <a href="{{ route('dm.index') }}" class="mobile-drawer-link {{ $activeSection === 'messages' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                Messages
                @if($unreadDmCount > 0)
                    <span class="navbar-badge">{{ $unreadDmCount > 99 ? '99+' : $unreadDmCount }}</span>
                @endif
            </a>
            <a href="{{ route('friends.index') }}" class="mobile-drawer-link {{ $activeSection === 'friends' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <line x1="19" y1="8" x2="19" y2="14"></line>
                    <line x1="22" y1="11" x2="16" y2="11"></line>
                </svg>
                Friends
            </a>
            <a href="{{ route('friends.search') }}" class="mobile-drawer-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    <line x1="11" y1="8" x2="11" y2="14"></line>
                    <line x1="8" y1="11" x2="14" y2="11"></line>
                </svg>
                Add Friends
            </a>
        </div>

        {{-- Discover Section --}}
        <div class="mobile-drawer-section">
            <div class="mobile-drawer-section-title">Discover</div>
            <a href="{{ route('servers.discover') }}" class="mobile-drawer-link {{ $activeSection === 'servers' ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="2" y1="12" x2="22" y2="12"></line>
                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                </svg>
                Discover Servers
            </a>
        </div>
    </div>

    {{-- Mobile User Section --}}
    <div class="mobile-drawer-user">
        <div class="mobile-drawer-user-card">
            <img src="{{ $user->profile->avatar_url }}" alt="{{ $user->display_name }}" class="mobile-drawer-user-avatar">
            <div class="mobile-drawer-user-info">
                <div class="mobile-drawer-user-name">{{ $user->display_name }}</div>
                <div class="mobile-drawer-user-username">{{ '@' . $user->username }}</div>
            </div>
        </div>

        <div class="mobile-drawer-user-actions">
            <a href="{{ route('profile.show', $user->username) }}" class="mobile-drawer-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                View Profile
            </a>
            <a href="{{ route('settings') }}" class="mobile-drawer-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
                Settings
            </a>
        </div>

        <form method="POST" action="{{ route('logout') }}" style="margin-top: 12px;">
            @csrf
            <button type="submit" class="mobile-drawer-logout">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </button>
        </form>
    </div>
</div>
