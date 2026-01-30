{{--
    Team Invitation Dropdown Component
    Phase 5: Team Invitation System - Frontend

    Displays pending team invitations in the navbar with:
    - Notification badge with count
    - Invitation list with accept/decline actions
    - Real-time updates via Laravel Echo
--}}

@php
    $user = auth()->user();
    $pendingInvitations = $user->pendingTeamInvitations()
        ->with(['team', 'inviter.profile'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    $invitationCount = $user->pending_team_invitations_count;
@endphp

<div class="team-invitation-dropdown" id="teamInvitationDropdown">
    {{-- Trigger Button --}}
    <button
        type="button"
        class="team-invitation-trigger"
        onclick="toggleInvitationDropdown()"
        aria-label="Team Invitations"
        aria-expanded="false"
        aria-haspopup="true"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
            <circle cx="9" cy="7" r="4"></circle>
            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        @if($invitationCount > 0)
            <span class="team-invitation-badge" id="invitationBadge">
                {{ $invitationCount > 9 ? '9+' : $invitationCount }}
            </span>
        @else
            <span class="team-invitation-badge" id="invitationBadge" style="display: none;">0</span>
        @endif
    </button>

    {{-- Dropdown Panel --}}
    <div class="team-invitation-panel" id="invitationPanel">
        {{-- Header --}}
        <div class="invitation-panel-header">
            <h3>Team Invitations</h3>
            <button type="button" class="invitation-refresh-btn" onclick="refreshInvitationDropdown()" title="Refresh">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"></path>
                    <path d="M1 20v-6h6"></path>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
            </button>
        </div>

        {{-- Invitation List --}}
        <div class="invitation-list" id="invitationList">
            @if($pendingInvitations->isEmpty())
                <div class="invitation-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <p>No pending invitations</p>
                    <span>You'll be notified when a team invites you</span>
                </div>
            @else
                @foreach($pendingInvitations as $invitation)
                    <div class="invitation-item" id="invitation-{{ $invitation->id }}" data-invitation-id="{{ $invitation->id }}">
                        <div class="invitation-content">
                            <div class="invitation-team-info">
                                <div class="invitation-team-name">{{ $invitation->team->name }}</div>
                                <div class="invitation-game">{{ $invitation->team->game_name }}</div>
                            </div>
                            <div class="invitation-meta">
                                <div class="invitation-role">
                                    <span class="role-badge role-{{ $invitation->role }}">{{ $invitation->role_display_name }}</span>
                                </div>
                                <div class="invitation-inviter">
                                    <img
                                        src="{{ $invitation->inviter->profile->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($invitation->inviter->display_name) }}"
                                        alt="{{ $invitation->inviter->display_name }}"
                                        class="inviter-avatar"
                                    >
                                    <span>from {{ $invitation->inviter->display_name }}</span>
                                </div>
                                @if($invitation->message)
                                    <div class="invitation-message" title="{{ $invitation->message }}">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                        </svg>
                                        <span>{{ Str::limit($invitation->message, 40) }}</span>
                                    </div>
                                @endif
                                <div class="invitation-expires">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                    <span>{{ $invitation->expires_in ?? 'No expiration' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="invitation-actions">
                            <button
                                type="button"
                                class="invitation-btn accept"
                                onclick="acceptInvitation({{ $invitation->id }})"
                                title="Accept Invitation"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="invitation-btn decline"
                                onclick="declineInvitation({{ $invitation->id }})"
                                title="Decline Invitation"
                            >
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- View All Link --}}
        @if($invitationCount > 5)
            <div class="invitation-panel-footer">
                <a href="{{ route('teams.index') }}?tab=invitations" class="view-all-link">
                    View all {{ $invitationCount }} invitations
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        @endif
    </div>
</div>

<style>
.team-invitation-dropdown {
    position: relative;
}

.team-invitation-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    color: var(--color-text-muted);
    position: relative;
    transition: all 0.2s ease;
}

.team-invitation-trigger:hover {
    background-color: var(--color-surface-hover);
    color: var(--color-text-primary);
}

.team-invitation-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    background-color: #ef4444;
    color: white;
    font-size: 11px;
    font-weight: 700;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
}

.team-invitation-panel {
    position: absolute;
    top: 100%;
    right: 0;
    width: 360px;
    max-height: 400px;
    background-color: var(--modal-bg);
    border: 1px solid var(--modal-border);
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: none;
    flex-direction: column;
    margin-top: 8px;
    overflow: hidden;
}

.team-invitation-panel.active {
    display: flex;
}

.invitation-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 16px 12px;
    border-bottom: 1px solid var(--color-border-secondary);
}

.invitation-panel-header h3 {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text-primary);
    margin: 0;
}

.invitation-refresh-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: transparent;
    border: none;
    border-radius: 6px;
    color: var(--color-text-muted);
    cursor: pointer;
    transition: all 0.2s ease;
}

.invitation-refresh-btn:hover {
    background-color: var(--color-surface-hover);
    color: var(--color-text-primary);
}

.invitation-refresh-btn.spinning svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.invitation-list {
    flex: 1;
    overflow-y: auto;
    max-height: 320px;
}

.invitation-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    text-align: center;
    color: var(--color-text-muted);
}

.invitation-empty svg {
    margin-bottom: 12px;
    opacity: 0.5;
}

.invitation-empty p {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-text-secondary);
    margin: 0 0 4px;
}

.invitation-empty span {
    font-size: 12px;
}

.invitation-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 14px 16px;
    border-bottom: 1px solid var(--color-border-secondary);
    transition: background-color 0.2s ease;
}

.invitation-item:hover {
    background-color: var(--color-surface-hover);
}

.invitation-item:last-child {
    border-bottom: none;
}

.invitation-content {
    flex: 1;
    min-width: 0;
}

.invitation-team-info {
    margin-bottom: 8px;
}

.invitation-team-name {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.invitation-game {
    font-size: 12px;
    color: var(--color-text-secondary);
    margin-top: 2px;
}

.invitation-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.invitation-role .role-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 4px;
}

.role-badge.role-member {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
}

.role-badge.role-co_leader {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
}

.role-badge.role-leader {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
}

.invitation-inviter {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--color-text-muted);
}

.inviter-avatar {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    object-fit: cover;
}

.invitation-message {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--color-text-secondary);
    font-style: italic;
}

.invitation-message svg {
    flex-shrink: 0;
    opacity: 0.6;
}

.invitation-message span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.invitation-expires {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 11px;
    color: var(--color-text-muted);
}

.invitation-expires svg {
    flex-shrink: 0;
    opacity: 0.6;
}

.invitation-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-left: 12px;
    flex-shrink: 0;
}

.invitation-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}

.invitation-btn.accept {
    background-color: rgba(34, 197, 94, 0.15);
    color: #22c55e;
    border-color: rgba(34, 197, 94, 0.3);
}

.invitation-btn.accept:hover {
    background-color: #22c55e;
    color: white;
    border-color: #22c55e;
    transform: scale(1.1);
}

.invitation-btn.decline {
    background-color: rgba(239, 68, 68, 0.15);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
}

.invitation-btn.decline:hover {
    background-color: #ef4444;
    color: white;
    border-color: #ef4444;
    transform: scale(1.1);
}

.invitation-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

.invitation-panel-footer {
    padding: 12px 16px;
    border-top: 1px solid var(--color-border-secondary);
    background-color: var(--color-bg-primary);
}

.view-all-link {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: #667eea;
    text-decoration: none;
    transition: color 0.2s ease;
}

.view-all-link:hover {
    color: #764ba2;
}

/* Toast Notification Styles */
.toast-notification {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 14px 20px;
    background-color: var(--color-surface-hover);
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    z-index: 9999;
    display: flex;
    align-items: center;
    gap: 12px;
    max-width: 380px;
    animation: slideInRight 0.3s ease;
    border-left: 4px solid;
}

.toast-notification.success {
    border-left-color: #22c55e;
}

.toast-notification.error {
    border-left-color: #ef4444;
}

.toast-notification.info {
    border-left-color: #3b82f6;
}

.toast-notification.warning {
    border-left-color: #f59e0b;
}

.toast-notification .toast-icon {
    font-size: 18px;
    flex-shrink: 0;
}

.toast-notification .toast-message {
    font-size: 14px;
    color: var(--color-text-primary);
    flex: 1;
}

.toast-notification .toast-close {
    background: none;
    border: none;
    color: var(--color-text-muted);
    cursor: pointer;
    padding: 4px;
    font-size: 18px;
    line-height: 1;
    transition: color 0.2s ease;
}

.toast-notification .toast-close:hover {
    color: var(--color-text-primary);
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(100px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slideOutRight {
    from {
        opacity: 1;
        transform: translateX(0);
    }
    to {
        opacity: 0;
        transform: translateX(100px);
    }
}

/* Responsive adjustments */
@media (max-width: 480px) {
    .team-invitation-panel {
        width: calc(100vw - 24px);
        right: -8px;
    }
}
</style>

<script>
// Toggle dropdown visibility
function toggleInvitationDropdown() {
    const panel = document.getElementById('invitationPanel');
    const trigger = document.querySelector('.team-invitation-trigger');

    if (panel.classList.contains('active')) {
        panel.classList.remove('active');
        trigger.setAttribute('aria-expanded', 'false');
    } else {
        // Close any other open dropdowns
        document.querySelectorAll('.team-invitation-panel.active').forEach(p => p.classList.remove('active'));
        panel.classList.add('active');
        trigger.setAttribute('aria-expanded', 'true');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('teamInvitationDropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        const panel = document.getElementById('invitationPanel');
        const trigger = document.querySelector('.team-invitation-trigger');
        if (panel && panel.classList.contains('active')) {
            panel.classList.remove('active');
            trigger.setAttribute('aria-expanded', 'false');
        }
    }
});

// Accept invitation
async function acceptInvitation(invitationId) {
    const btn = document.querySelector(`#invitation-${invitationId} .invitation-btn.accept`);
    if (btn) btn.disabled = true;

    try {
        const response = await fetch(`/team-invitations/${invitationId}/accept`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message || 'Invitation accepted! Welcome to the team!', 'success');

            // Remove the invitation item
            const item = document.getElementById(`invitation-${invitationId}`);
            if (item) {
                item.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => {
                    item.remove();
                    updateInvitationBadge(-1);
                    checkEmptyState();
                }, 300);
            }

            // Redirect to team page after short delay
            if (data.team && data.team.id) {
                setTimeout(() => {
                    window.location.href = `/teams/${data.team.id}`;
                }, 1500);
            }
        } else {
            showToast(data.error || 'Failed to accept invitation', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (error) {
        console.error('Error accepting invitation:', error);
        showToast('An error occurred. Please try again.', 'error');
        if (btn) btn.disabled = false;
    }
}

// Decline invitation
async function declineInvitation(invitationId) {
    const btn = document.querySelector(`#invitation-${invitationId} .invitation-btn.decline`);
    if (btn) btn.disabled = true;

    try {
        const response = await fetch(`/team-invitations/${invitationId}/decline`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message || 'Invitation declined', 'info');

            // Remove the invitation item
            const item = document.getElementById(`invitation-${invitationId}`);
            if (item) {
                item.style.animation = 'slideOutRight 0.3s ease forwards';
                setTimeout(() => {
                    item.remove();
                    updateInvitationBadge(-1);
                    checkEmptyState();
                }, 300);
            }
        } else {
            showToast(data.error || 'Failed to decline invitation', 'error');
            if (btn) btn.disabled = false;
        }
    } catch (error) {
        console.error('Error declining invitation:', error);
        showToast('An error occurred. Please try again.', 'error');
        if (btn) btn.disabled = false;
    }
}

// Update badge count
function updateInvitationBadge(change) {
    const badge = document.getElementById('invitationBadge');
    if (!badge) return;

    let currentCount = parseInt(badge.textContent) || 0;
    if (badge.textContent === '9+') currentCount = 10;

    const newCount = Math.max(0, currentCount + change);

    if (newCount > 0) {
        badge.textContent = newCount > 9 ? '9+' : newCount;
        badge.style.display = 'flex';
    } else {
        badge.style.display = 'none';
    }
}

// Check if list is empty and show empty state
function checkEmptyState() {
    const list = document.getElementById('invitationList');
    const items = list.querySelectorAll('.invitation-item');

    if (items.length === 0) {
        list.innerHTML = `
            <div class="invitation-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <p>No pending invitations</p>
                <span>You'll be notified when a team invites you</span>
            </div>
        `;
    }
}

// Refresh invitation dropdown
async function refreshInvitationDropdown() {
    const refreshBtn = document.querySelector('.invitation-refresh-btn');
    if (refreshBtn) {
        refreshBtn.classList.add('spinning');
    }

    try {
        const response = await fetch('/team-invitations', {
            headers: {
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            const list = document.getElementById('invitationList');
            const badge = document.getElementById('invitationBadge');

            // Update badge
            if (data.count > 0) {
                badge.textContent = data.count > 9 ? '9+' : data.count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }

            // Rebuild list
            if (data.invitations.length === 0) {
                list.innerHTML = `
                    <div class="invitation-empty">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <p>No pending invitations</p>
                        <span>You'll be notified when a team invites you</span>
                    </div>
                `;
            } else {
                list.innerHTML = data.invitations.map(inv => createInvitationItemHTML(inv)).join('');
            }
        }
    } catch (error) {
        console.error('Error refreshing invitations:', error);
        showToast('Failed to refresh invitations', 'error');
    } finally {
        if (refreshBtn) {
            refreshBtn.classList.remove('spinning');
        }
    }
}

// Create invitation item HTML
function createInvitationItemHTML(invitation) {
    const roleClass = `role-${invitation.role}`;
    const messageHtml = invitation.message
        ? `<div class="invitation-message" title="${escapeHtml(invitation.message)}">
               <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                   <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
               </svg>
               <span>${escapeHtml(truncateText(invitation.message, 40))}</span>
           </div>`
        : '';

    return `
        <div class="invitation-item" id="invitation-${invitation.id}" data-invitation-id="${invitation.id}">
            <div class="invitation-content">
                <div class="invitation-team-info">
                    <div class="invitation-team-name">${escapeHtml(invitation.team.name)}</div>
                    <div class="invitation-game">${escapeHtml(invitation.team.game_name)}</div>
                </div>
                <div class="invitation-meta">
                    <div class="invitation-role">
                        <span class="role-badge ${roleClass}">${escapeHtml(invitation.role_display_name)}</span>
                    </div>
                    <div class="invitation-inviter">
                        <img src="${invitation.inviter.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(invitation.inviter.display_name)}"
                             alt="${escapeHtml(invitation.inviter.display_name)}"
                             class="inviter-avatar">
                        <span>from ${escapeHtml(invitation.inviter.display_name)}</span>
                    </div>
                    ${messageHtml}
                    <div class="invitation-expires">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <span>${invitation.expires_in || 'No expiration'}</span>
                    </div>
                </div>
            </div>
            <div class="invitation-actions">
                <button type="button" class="invitation-btn accept" onclick="acceptInvitation(${invitation.id})" title="Accept Invitation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                </button>
                <button type="button" class="invitation-btn decline" onclick="declineInvitation(${invitation.id})" title="Decline Invitation">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>
    `;
}

// Helper functions
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

// Global showToast function
function showToast(message, type = 'info') {
    // Remove existing toasts
    document.querySelectorAll('.toast-notification').forEach(t => t.remove());

    const icons = {
        success: '<span style="color: #22c55e;">&#10003;</span>',
        error: '<span style="color: #ef4444;">&#10007;</span>',
        info: '<span style="color: #3b82f6;">&#8505;</span>',
        warning: '<span style="color: #f59e0b;">&#9888;</span>'
    };

    const toast = document.createElement('div');
    toast.className = `toast-notification ${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-message">${escapeHtml(message)}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    document.body.appendChild(toast);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }
    }, 5000);
}

// Echo listeners for real-time updates
document.addEventListener('DOMContentLoaded', function() {
    // Only set up Echo listeners if Echo is available
    if (typeof window.Echo !== 'undefined') {
        const userId = {{ auth()->id() }};

        // Listen for new invitations on user's private channel
        window.Echo.private(`user.${userId}`)
            .listen('.team.invitation.sent', (e) => {
                console.log('Team invitation received:', e);
                showToast(`You've been invited to join ${e.invitation.team_name}!`, 'info');
                updateInvitationBadge(1);
                refreshInvitationDropdown();
            })
            .listen('.team.invitation.cancelled', (e) => {
                console.log('Team invitation cancelled:', e);

                // Remove the invitation item if visible
                const item = document.getElementById(`invitation-${e.invitation_id}`);
                if (item) {
                    item.style.animation = 'slideOutRight 0.3s ease forwards';
                    setTimeout(() => {
                        item.remove();
                        updateInvitationBadge(-1);
                        checkEmptyState();
                    }, 300);
                }
            });
    }
});
</script>
