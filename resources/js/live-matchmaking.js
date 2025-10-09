/**
 * Live Matchmaking System
 * Handles real-time team recommendations and compatibility updates
 *
 * Features:
 * - Real-time WebSocket updates via Laravel Echo
 * - Automatic reconnection handling
 * - Graceful degradation when WebSocket unavailable
 * - Comprehensive error handling and user feedback
 */

class LiveMatchmakingManager {
    constructor() {
        this.userId = window.Laravel?.user?.id;
        this.activeRequests = new Map();
        this.recommendations = new Map();
        this.updateInterval = null;
        this.echoConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.init();
    }

    init() {
        console.log('[LiveMatchmaking] Initializing...');

        if (!this.userId) {
            console.error('[LiveMatchmaking] User ID not available');
            this.showConnectionStatus('error', 'User authentication required');
            return;
        }

        // Listen for Echo connection events
        this.setupEchoConnectionListeners();

        // Try to initialize with current Echo state
        if (window.Echo) {
            this.onEchoConnected();
        } else {
            console.warn('[LiveMatchmaking] Waiting for Echo connection...');
            this.showConnectionStatus('warning', 'Connecting to real-time server...');
        }
    }

    setupEchoConnectionListeners() {
        // Listen for Echo connection events from bootstrap.js
        window.addEventListener('echo:connected', () => {
            console.log('[LiveMatchmaking] Echo connected event received');
            this.onEchoConnected();
        });

        window.addEventListener('echo:disconnected', () => {
            console.warn('[LiveMatchmaking] Echo disconnected event received');
            this.onEchoDisconnected();
        });

        window.addEventListener('echo:failed', () => {
            console.error('[LiveMatchmaking] Echo connection permanently failed');
            this.onEchoFailed();
        });

        window.addEventListener('echo:unavailable', () => {
            console.warn('[LiveMatchmaking] Echo temporarily unavailable');
            this.showConnectionStatus('warning', 'Real-time connection temporarily unavailable');
        });
    }

    onEchoConnected() {
        if (this.echoConnected) return; // Already connected

        console.log('[LiveMatchmaking] ✅ Echo connection established');
        this.echoConnected = true;
        this.reconnectAttempts = 0;

        this.showConnectionStatus('success', 'Connected to live matchmaking');

        // Auto-hide success message after 3 seconds
        setTimeout(() => this.hideConnectionStatus(), 3000);

        this.setupEventListeners();
        this.loadActiveRequests();
        this.startRecommendationUpdates();
    }

    onEchoDisconnected() {
        console.warn('[LiveMatchmaking] ⚠️ Echo connection lost');
        this.echoConnected = false;

        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            this.showConnectionStatus('warning', `Connection lost. Reconnecting (${this.reconnectAttempts}/${this.maxReconnectAttempts})...`);
        } else {
            this.showConnectionStatus('error', 'Connection lost. Please refresh the page.');
        }
    }

    onEchoFailed() {
        console.error('[LiveMatchmaking] ❌ Echo permanently failed');
        this.echoConnected = false;
        this.showConnectionStatus('error', 'Real-time updates unavailable. Recommendations will update periodically.');

        // Fall back to polling mode only
        this.loadActiveRequests();
        this.startRecommendationUpdates();
    }

    showConnectionStatus(type, message) {
        const statusEl = document.getElementById('matchmaking-connection-status');
        if (!statusEl) {
            // Create status element if it doesn't exist
            const status = document.createElement('div');
            status.id = 'matchmaking-connection-status';
            status.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                padding: 12px 20px;
                border-radius: 8px;
                font-size: 14px;
                font-weight: 500;
                z-index: 9998;
                display: flex;
                align-items: center;
                gap: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                animation: slideInRight 0.3s ease;
            `;
            document.body.appendChild(status);
        }

        const el = document.getElementById('matchmaking-connection-status');

        const colors = {
            success: { bg: '#065f46', text: '#6ee7b7', border: '#047857' },
            warning: { bg: '#78350f', text: '#fbbf24', border: '#92400e' },
            error: { bg: '#7f1d1d', text: '#fca5a5', border: '#991b1b' }
        };

        const icons = {
            success: '✓',
            warning: '⚠',
            error: '✕'
        };

        const color = colors[type] || colors.warning;

        el.style.backgroundColor = color.bg;
        el.style.color = color.text;
        el.style.border = `1px solid ${color.border}`;
        el.style.display = 'flex';

        el.innerHTML = `
            <span style="font-weight: 700;">${icons[type]}</span>
            <span>${message}</span>
        `;
    }

    hideConnectionStatus() {
        const el = document.getElementById('matchmaking-connection-status');
        if (el) {
            el.style.opacity = '0';
            el.style.transform = 'translateX(100%)';
            el.style.transition = 'all 0.3s ease';
            setTimeout(() => {
                el.style.display = 'none';
            }, 300);
        }
    }

    setupEventListeners() {
        if (!window.Echo) {
            console.warn('[LiveMatchmaking] Cannot setup event listeners - Echo not available');
            return;
        }

        try {
            // Listen for team creation/updates
            window.Echo.private('teams.global')
                .listen('.team.created', (e) => this.handleTeamCreated(e))
                .listen('.team.status.changed', (e) => this.handleTeamStatusChanged(e))
                .listen('.team.member.joined', (e) => this.handleTeamMemberChanged(e))
                .listen('.team.member.left', (e) => this.handleTeamMemberChanged(e));

            // Listen for user-specific matchmaking events
            window.Echo.private(`user.${this.userId}`)
                .listen('.matchmaking.request.created', (e) => this.handleRequestCreated(e))
                .listen('.matchmaking.request.updated', (e) => this.handleRequestUpdated(e))
                .listen('.matchmaking.match.found', (e) => this.handleMatchFound(e));

            console.log('[LiveMatchmaking] ✅ Event listeners registered');
        } catch (error) {
            console.error('[LiveMatchmaking] ❌ Failed to setup event listeners:', error);
        }
    }

    loadActiveRequests() {
        console.log('[LiveMatchmaking] Loading active requests...');

        // Load user's active matchmaking requests
        fetch('/api/matchmaking/active-requests', {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Laravel?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    console.log(`[LiveMatchmaking] ✅ Loaded ${data.requests?.length || 0} active requests`);

                    if (data.requests && data.requests.length > 0) {
                        data.requests.forEach(request => {
                            this.activeRequests.set(request.id, request);
                            this.findCompatibleTeams(request);
                        });
                    }

                    this.updateRecommendationsDisplay();
                } else {
                    console.warn('[LiveMatchmaking] ⚠️ No active requests found');
                    this.updateRecommendationsDisplay();
                }
            })
            .catch(error => {
                console.error('[LiveMatchmaking] ❌ Error loading active requests:', error);
                // Don't show error to user - gracefully degrade
                this.updateRecommendationsDisplay();
            });
    }

    startRecommendationUpdates() {
        // Update recommendations every 30 seconds
        this.updateInterval = setInterval(() => {
            this.refreshRecommendations();
        }, 30000);
    }

    refreshRecommendations() {
        this.activeRequests.forEach(request => {
            this.findCompatibleTeams(request);
        });
    }

    findCompatibleTeams(request) {
        if (!request || !request.id) {
            console.error('[LiveMatchmaking] Invalid request object');
            return;
        }

        fetch('/api/matchmaking/find-compatible-teams', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Laravel?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                request_id: request.id,
                live_update: true
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log(`[LiveMatchmaking] ✅ Found ${data.teams?.length || 0} compatible teams for request ${request.id}`);
                this.updateRecommendations(request.id, data.teams || []);
            } else {
                console.warn(`[LiveMatchmaking] ⚠️ No compatible teams found for request ${request.id}`);
                this.updateRecommendations(request.id, []);
            }
        })
        .catch(error => {
            console.error('[LiveMatchmaking] ❌ Error finding compatible teams:', error);
            // Gracefully handle by showing empty recommendations for this request
            this.updateRecommendations(request.id, []);
        });
    }

    updateRecommendations(requestId, teams) {
        this.recommendations.set(requestId, teams);
        this.updateRecommendationsDisplay();
    }

    updateRecommendationsDisplay() {
        const container = document.getElementById('live-recommendations-content');
        if (!container) return;

        // Get all recommendations
        const allRecommendations = [];
        this.recommendations.forEach((teams, requestId) => {
            teams.forEach(team => {
                team.requestId = requestId;
                allRecommendations.push(team);
            });
        });

        if (allRecommendations.length === 0) {
            container.innerHTML = this.getEmptyRecommendationsHTML();
            return;
        }

        // Sort by compatibility score
        allRecommendations.sort((a, b) => (b.compatibility_score || 0) - (a.compatibility_score || 0));

        // Take top 3 recommendations
        const topRecommendations = allRecommendations.slice(0, 3);

        container.innerHTML = this.getRecommendationsHTML(topRecommendations);
        
        // Add event listeners to recommendation cards
        this.attachRecommendationEventListeners();
    }

    getEmptyRecommendationsHTML() {
        return `
            <div style="text-align: center; padding: 40px; color: #b3b3b5;">
                <div style="font-size: 24px; margin-bottom: 12px;">🎯</div>
                <p>No active matchmaking requests</p>
                <p style="font-size: 14px; margin-top: 8px;">Create a matchmaking request to get personalized recommendations</p>
                <button onclick="showCreateRequestModal()" class="btn btn-primary" style="margin-top: 16px;">
                    Find Teammates
                </button>
            </div>
        `;
    }

    getRecommendationsHTML(recommendations) {
        return `
            <div style="display: grid; gap: 16px;">
                ${recommendations.map(team => this.getRecommendationCardHTML(team)).join('')}
                ${recommendations.length > 0 ? `
                    <div style="text-align: center; margin-top: 12px;">
                        <button onclick="LiveMatchmaking.showAllRecommendations()" class="btn btn-secondary btn-sm">
                            View All Recommendations
                        </button>
                    </div>
                ` : ''}
            </div>
        `;
    }

    getRecommendationCardHTML(team) {
        const compatibilityColor = this.getCompatibilityColor(team.compatibility_score || 0);
        const roleNeeds = team.role_needs || [];
        
        return `
            <div class="recommendation-card" data-team-id="${team.id}" style="
                background: linear-gradient(135deg, #18181b 0%, #27272a 100%);
                border-radius: 8px;
                padding: 16px;
                border: 1px solid #3f3f46;
                transition: all 0.2s ease;
                position: relative;
                overflow: hidden;
            ">
                <div style="position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: ${compatibilityColor};"></div>
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #efeff1; margin-bottom: 4px;">${team.name}</div>
                        <div style="color: #b3b3b5; font-size: 14px; margin-bottom: 8px;">${team.game_name || 'Unknown Game'}</div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                ${team.skill_level || 'Casual'}
                            </span>
                            ${roleNeeds.length > 0 ? `
                                <span style="background-color: #3f3f46; color: #b3b3b5; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                    Needs: ${roleNeeds.slice(0, 2).join(', ')}${roleNeeds.length > 2 ? '...' : ''}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 24px; font-weight: 700; color: ${compatibilityColor}; line-height: 1;">
                            ${Math.round(team.compatibility_score || 0)}%
                        </div>
                        <div style="font-size: 11px; color: #b3b3b5; text-transform: uppercase; letter-spacing: 0.5px;">
                            Match
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="display: flex; margin-right: 8px;">
                            ${team.members ? team.members.slice(0, 3).map(member => `
                                <img src="${member.avatar_url || '/images/default-avatar.png'}" 
                                     alt="${member.display_name}" 
                                     style="width: 24px; height: 24px; border-radius: 50%; margin-left: -4px; border: 2px solid #18181b;"
                                     title="${member.display_name}">
                            `).join('') : ''}
                        </div>
                        <span style="color: #b3b3b5; font-size: 12px;">
                            ${team.current_size || 0}/${team.max_size || 5} members
                        </span>
                    </div>
                </div>

                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button onclick="LiveMatchmaking.viewTeam(${team.id})" class="btn btn-secondary btn-sm">
                        View Team
                    </button>
                    <button onclick="LiveMatchmaking.requestToJoin(${team.id})" class="btn btn-primary btn-sm">
                        Request to Join
                    </button>
                </div>

                ${team.match_reasons ? `
                    <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #3f3f46;">
                        <div style="font-size: 11px; color: #b3b3b5; margin-bottom: 4px;">WHY IT'S A GOOD MATCH:</div>
                        <div style="font-size: 12px; color: #d4d4d8;">
                            ${team.match_reasons.slice(0, 2).join(' • ')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    getCompatibilityColor(score) {
        if (score >= 80) return '#10b981'; // green
        if (score >= 60) return '#f59e0b'; // yellow
        if (score >= 40) return '#f97316'; // orange
        return '#ef4444'; // red
    }

    attachRecommendationEventListeners() {
        // Add hover effects to recommendation cards
        document.querySelectorAll('.recommendation-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-2px)';
                card.style.borderColor = '#667eea';
                card.style.boxShadow = '0 8px 25px rgba(102, 126, 234, 0.15)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.borderColor = '#3f3f46';
                card.style.boxShadow = 'none';
            });
        });
    }

    // Event Handlers
    handleTeamCreated(event) {
        const team = event.team;
        console.log('New team created:', team);
        
        // Check if this team might be compatible with active requests
        this.activeRequests.forEach(request => {
            if (this.isTeamPotentiallyCompatible(team, request)) {
                this.findCompatibleTeams(request);
            }
        });
    }

    handleTeamStatusChanged(event) {
        const team = event.team;
        console.log('Team status changed:', team);
        
        // Update team in existing recommendations
        this.recommendations.forEach((teams, requestId) => {
            const teamIndex = teams.findIndex(t => t.id === team.id);
            if (teamIndex !== -1) {
                teams[teamIndex].status = team.status;
                
                // Remove team if no longer recruiting
                if (team.status !== 'recruiting') {
                    teams.splice(teamIndex, 1);
                }
            }
        });
        
        this.updateRecommendationsDisplay();
    }

    handleTeamMemberChanged(event) {
        const { team } = event;
        
        // Update member count in recommendations
        this.recommendations.forEach((teams, requestId) => {
            const teamIndex = teams.findIndex(t => t.id === team.id);
            if (teamIndex !== -1) {
                teams[teamIndex].current_size = team.current_size;
                teams[teamIndex].max_size = team.max_size;
            }
        });
        
        this.updateRecommendationsDisplay();
    }

    handleRequestCreated(event) {
        const request = event.request;
        this.activeRequests.set(request.id, request);
        this.findCompatibleTeams(request);
    }

    handleRequestUpdated(event) {
        const request = event.request;
        this.activeRequests.set(request.id, request);
        this.findCompatibleTeams(request);
    }

    handleMatchFound(event) {
        const { team, compatibility_score } = event;
        
        // Show notification
        this.showMatchNotification(team, compatibility_score);
    }

    isTeamPotentiallyCompatible(team, request) {
        // Basic compatibility check
        if (request.game_appid && team.game_appid !== request.game_appid) {
            return false;
        }
        
        if (request.skill_level && team.skill_level !== request.skill_level) {
            return false;
        }
        
        return true;
    }

    showMatchNotification(team, score) {
        // Create match notification
        const notification = document.createElement('div');
        notification.className = 'match-notification';
        notification.innerHTML = `
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 16px; border-radius: 8px; margin: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 24px;">🎯</div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; margin-bottom: 4px;">Perfect Match Found!</div>
                        <div style="font-size: 14px; opacity: 0.9;">
                            "${team.name}" is ${score}% compatible with your request
                        </div>
                    </div>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()" style="background: none; border: none; color: white; font-size: 18px; cursor: pointer;">×</button>
                </div>
                <div style="margin-top: 12px; display: flex; gap: 8px;">
                    <button onclick="LiveMatchmaking.viewTeam(${team.id})" class="btn btn-secondary btn-sm">View Team</button>
                    <button onclick="LiveMatchmaking.requestToJoin(${team.id})" class="btn btn-primary btn-sm">Join Now</button>
                </div>
            </div>
        `;

        // Add to notifications container
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        container.appendChild(notification);

        // Auto-remove after 10 seconds
        setTimeout(() => {
            notification.remove();
        }, 10000);
    }

    // Public Methods
    viewTeam(teamId) {
        window.location.href = `/teams/${teamId}`;
    }

    requestToJoin(teamId) {
        if (!teamId) {
            console.error('[LiveMatchmaking] Invalid team ID');
            this.showUserNotification('error', 'Invalid team selection');
            return;
        }

        console.log(`[LiveMatchmaking] Sending join request for team ${teamId}...`);

        // Implement join request logic
        fetch(`/teams/${teamId}/join`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Laravel?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(errorData => {
                    throw new Error(errorData.message || errorData.error || `HTTP ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                console.log('[LiveMatchmaking] ✅ Join request sent successfully');
                this.showUserNotification('success', 'Join request sent successfully! The team will be notified.');

                // Reload page after 2 seconds to show updated state
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                throw new Error(data.message || data.error || 'Failed to send join request');
            }
        })
        .catch(error => {
            console.error('[LiveMatchmaking] ❌ Error sending join request:', error);
            this.showUserNotification('error', error.message || 'Failed to send join request. Please try again.');
        });
    }

    showUserNotification(type, message) {
        // Create toast notification for user actions
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
            animation: slideInUp 0.3s ease;
            max-width: 400px;
        `;

        const colors = {
            success: { bg: '#065f46', text: '#6ee7b7', border: '#047857' },
            error: { bg: '#7f1d1d', text: '#fca5a5', border: '#991b1b' }
        };

        const icons = {
            success: '✓',
            error: '✕'
        };

        const color = colors[type] || colors.success;

        toast.style.backgroundColor = color.bg;
        toast.style.color = color.text;
        toast.style.border = `1px solid ${color.border}`;

        toast.innerHTML = `
            <span style="font-weight: 700; font-size: 18px;">${icons[type]}</span>
            <span>${message}</span>
        `;

        document.body.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(20px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    showAllRecommendations() {
        // Show modal with all recommendations
        const modal = document.createElement('div');
        modal.innerHTML = `
            <div class="modal-backdrop" onclick="this.parentElement.remove()">
                <div class="modal-content" onclick="event.stopPropagation()" style="max-width: 800px;">
                    <div class="modal-header">
                        <h3>All Team Recommendations</h3>
                        <button onclick="this.closest('.modal-backdrop').remove()" class="modal-close">×</button>
                    </div>
                    <div class="modal-body">
                        ${this.getAllRecommendationsHTML()}
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    }

    getAllRecommendationsHTML() {
        const allRecommendations = [];
        this.recommendations.forEach((teams, requestId) => {
            teams.forEach(team => {
                team.requestId = requestId;
                allRecommendations.push(team);
            });
        });

        if (allRecommendations.length === 0) {
            return '<p style="text-align: center; color: #b3b3b5;">No recommendations available</p>';
        }

        allRecommendations.sort((a, b) => (b.compatibility_score || 0) - (a.compatibility_score || 0));

        return `
            <div style="display: grid; gap: 16px;">
                ${allRecommendations.map(team => this.getRecommendationCardHTML(team)).join('')}
            </div>
        `;
    }

    destroy() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize on matchmaking page
    if (window.location.pathname.includes('/matchmaking')) {
        window.LiveMatchmaking = new LiveMatchmakingManager();
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (window.LiveMatchmaking) {
        window.LiveMatchmaking.destroy();
    }
});

export default LiveMatchmakingManager;