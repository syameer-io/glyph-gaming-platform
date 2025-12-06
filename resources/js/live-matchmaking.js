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

        // Check if server-rendered content already exists (team-card components from Blade)
        const hasServerRenderedContent = container.querySelector('.team-card') !== null;

        // On initial load, if server has already rendered detailed team cards, preserve them
        if (hasServerRenderedContent && !this._hasReplacedInitialContent) {
            console.log('[LiveMatchmaking] Preserving server-rendered recommendations with detailed UI');
            this._hasReplacedInitialContent = true;

            // Still attach event listeners to existing cards
            this.attachRecommendationEventListeners();
            return;
        }

        // Get all recommendations
        const allRecommendations = [];
        this.recommendations.forEach((teams, requestId) => {
            teams.forEach(team => {
                team.requestId = requestId;
                allRecommendations.push(team);
            });
        });

        if (allRecommendations.length === 0) {
            // Only show empty state if there's no server-rendered content
            if (!hasServerRenderedContent) {
                container.innerHTML = this.getEmptyRecommendationsHTML();
            }
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
                <p>No compatible teams found</p>
                <p style="font-size: 14px; margin-top: 8px;">Try adjusting your criteria or check back later for new teams</p>
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
        const breakdown = team.compatibility_breakdown || {};

        return `
            <div class="recommendation-card team-card" data-team-id="${team.id}" style="
                background: linear-gradient(135deg, #18181b 0%, #27272a 100%);
                border-radius: 8px;
                padding: 16px;
                border: 1px solid #3f3f46;
                transition: all 0.2s ease;
                position: relative;
                overflow: hidden;
            ">
                <div style="position: absolute; top: 0; right: 0; width: 4px; height: 100%; background: ${compatibilityColor};"></div>

                <!-- Header with Team Name and Match Score -->
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #efeff1; margin-bottom: 4px; font-size: 16px;">${team.name}</div>
                        <div style="color: #b3b3b5; font-size: 14px;">${team.game_name || 'Unknown Game'}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 28px; font-weight: 700; color: ${compatibilityColor}; line-height: 1;">
                            ${Math.round(team.compatibility_score || 0)}%
                        </div>
                        <div style="font-size: 11px; color: #b3b3b5; text-transform: uppercase; letter-spacing: 0.5px;">
                            Match
                        </div>
                    </div>
                </div>

                <!-- Detailed Compatibility Breakdown -->
                ${Object.keys(breakdown).length > 0 ? `
                    <div style="
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
                        gap: 8px;
                        padding: 12px;
                        background-color: #0e0e10;
                        border-radius: 8px;
                        margin-bottom: 12px;
                    ">
                        ${Object.entries(breakdown).map(([key, value]) => `
                            <div style="text-align: center;">
                                <div style="font-size: 10px; color: #71717a; text-transform: uppercase; margin-bottom: 2px;">
                                    ${this.formatBreakdownKey(key)}
                                </div>
                                <div style="font-size: 14px; font-weight: 600; color: ${value >= 70 ? '#10b981' : (value >= 50 ? '#f59e0b' : '#71717a')};">
                                    ${Math.round(value)}%
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}

                <!-- Team Tags -->
                <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px;">
                    ${team.skill_level ? `
                        <span style="font-size: 11px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; font-weight: 500;">
                            ${team.skill_level}
                        </span>
                    ` : ''}
                    <span style="font-size: 11px; background-color: #3f3f46; color: #b3b3b5; padding: 4px 8px; border-radius: 4px;">
                        ${team.current_size || 0}/${team.max_size || 5} Members
                    </span>
                    ${team.preferred_region ? `
                        <span style="font-size: 11px; background-color: #3f3f46; color: #b3b3b5; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">
                            ${team.preferred_region.replace(/_/g, ' ')}
                        </span>
                    ` : ''}
                    ${(team.required_roles || []).map(role => `
                        <span style="font-size: 11px; background-color: rgba(102, 126, 234, 0.2); color: #8b9aef; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; font-weight: 500; border: 1px solid rgba(102, 126, 234, 0.3);">
                            ${role.replace(/_/g, ' ')}
                        </span>
                    `).join('')}
                    ${(team.activity_times || []).map(time => `
                        <span style="font-size: 11px; background-color: rgba(245, 158, 11, 0.2); color: #fbbf24; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; font-weight: 500; border: 1px solid rgba(245, 158, 11, 0.3);">
                            ${time.replace(/_/g, ' ')}
                        </span>
                    `).join('')}
                    ${(team.languages || []).map(lang => `
                        <span style="font-size: 11px; background-color: rgba(16, 185, 129, 0.2); color: #10b981; padding: 4px 8px; border-radius: 4px; text-transform: uppercase; font-weight: 500; border: 1px solid rgba(16, 185, 129, 0.3);">
                            ${lang}
                        </span>
                    `).join('')}
                    ${team.communication_required ? `
                        <span style="font-size: 11px; background-color: #3f3f46; color: #b3b3b5; padding: 4px 8px; border-radius: 4px; text-transform: uppercase;">
                            Voice Chat
                        </span>
                    ` : ''}
                </div>

                <!-- Member Avatars -->
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="display: flex; margin-right: 8px;">
                            ${team.members ? team.members.slice(0, 5).map((member, idx) => `
                                <img src="${member.avatar_url || this.getDefaultAvatarUrl(member.display_name)}"
                                     alt="${member.display_name || 'Member'}"
                                     style="width: 28px; height: 28px; border-radius: 50%; margin-left: ${idx > 0 ? '-8px' : '0'}; border: 2px solid #18181b;"
                                     title="${member.display_name || 'Member'}"
                                     onerror="this.src='https://ui-avatars.com/api/?name=User&background=5865F2&color=fff&size=128&bold=true'">
                            `).join('') : ''}
                        </div>
                    </div>
                </div>

                <!-- Why It's a Good Match -->
                ${team.match_reasons && team.match_reasons.length > 0 ? `
                    <div style="padding: 10px; background-color: rgba(102, 126, 234, 0.1); border-radius: 6px; border-left: 3px solid #667eea; margin-bottom: 12px;">
                        <div style="font-size: 10px; color: #8b9aef; margin-bottom: 4px; text-transform: uppercase; font-weight: 600;">Why It's a Good Match</div>
                        <div style="font-size: 12px; color: #d4d4d8;">
                            ${team.match_reasons.slice(0, 3).join(' • ')}
                        </div>
                    </div>
                ` : ''}

                <!-- Action Buttons -->
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button onclick="LiveMatchmaking.viewTeam(${team.id})" class="btn btn-secondary btn-sm">
                        View Team
                    </button>
                    <button onclick="LiveMatchmaking.requestToJoin(${team.id})" class="btn btn-primary btn-sm">
                        Request to Join
                    </button>
                </div>
            </div>
        `;
    }

    formatBreakdownKey(key) {
        const keyMap = {
            'skill': 'Skill',
            'composition': 'Comp',
            'region': 'Region',
            'schedule': 'Schedule',
            'language': 'Lang',
            'size': 'Size'
        };
        return keyMap[key] || key.charAt(0).toUpperCase() + key.slice(1);
    }

    getCompatibilityColor(score) {
        if (score >= 80) return '#10b981'; // green
        if (score >= 60) return '#f59e0b'; // yellow
        if (score >= 40) return '#f97316'; // orange
        return '#ef4444'; // red
    }

    getDefaultAvatarUrl(name) {
        const displayName = name || 'User';
        const encodedName = encodeURIComponent(displayName);
        return `https://ui-avatars.com/api/?name=${encodedName}&background=5865F2&color=fff&size=128&bold=true`;
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
        modal.className = 'live-matchmaking-modal';
        modal.innerHTML = `
            <div class="mm-modal-backdrop" style="
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                backdrop-filter: blur(4px);
            ">
                <div class="mm-modal-content" style="
                    background-color: #1e1f22;
                    border-radius: 12px;
                    width: 95%;
                    max-width: 800px;
                    max-height: 85vh;
                    display: flex;
                    flex-direction: column;
                    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
                    overflow: hidden;
                " onclick="event.stopPropagation()">
                    <div class="mm-modal-header" style="
                        padding: 20px;
                        border-bottom: 1px solid #3f3f46;
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        flex-shrink: 0;
                    ">
                        <h3 style="font-size: 18px; font-weight: 600; color: #f2f3f5; margin: 0;">
                            All Team Recommendations
                        </h3>
                        <button style="
                            width: 32px;
                            height: 32px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            background: none;
                            border: none;
                            border-radius: 6px;
                            color: #71717a;
                            cursor: pointer;
                            font-size: 24px;
                            transition: all 0.15s ease;
                        " onmouseover="this.style.background='rgba(255,255,255,0.1)';this.style.color='#fff'"
                           onmouseout="this.style.background='none';this.style.color='#71717a'"
                           onclick="this.closest('.live-matchmaking-modal').remove()">×</button>
                    </div>
                    <div class="mm-modal-body" style="
                        padding: 20px;
                        overflow-y: auto;
                        flex: 1;
                    ">
                        ${this.getAllRecommendationsHTML()}
                    </div>
                </div>
            </div>
        `;

        // Close modal when clicking backdrop
        modal.querySelector('.mm-modal-backdrop').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                modal.remove();
            }
        });

        // Close modal on Escape key
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);

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