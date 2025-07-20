/**
 * Live Matchmaking System
 * Handles real-time team recommendations and compatibility updates
 */

class LiveMatchmakingManager {
    constructor() {
        this.userId = window.Laravel?.user?.id;
        this.activeRequests = new Map();
        this.recommendations = new Map();
        this.updateInterval = null;
        this.init();
    }

    init() {
        if (!window.Echo || !this.userId) {
            console.warn('Echo or user ID not available for live matchmaking');
            return;
        }

        this.setupEventListeners();
        this.loadActiveRequests();
        this.startRecommendationUpdates();
    }

    setupEventListeners() {
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
    }

    loadActiveRequests() {
        // Load user's active matchmaking requests
        fetch('/api/matchmaking/active-requests')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.requests.forEach(request => {
                        this.activeRequests.set(request.id, request);
                        this.findCompatibleTeams(request);
                    });
                    this.updateRecommendationsDisplay();
                }
            })
            .catch(error => console.error('Error loading active requests:', error));
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
        fetch('/api/matchmaking/find-compatible-teams', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Laravel.csrfToken
            },
            body: JSON.stringify({
                request_id: request.id,
                live_update: true
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateRecommendations(request.id, data.teams);
            }
        })
        .catch(error => console.error('Error finding compatible teams:', error));
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
        // Implement join request logic
        fetch(`/teams/${teamId}/request-join`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.Laravel.csrfToken
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Join request sent successfully!');
            } else {
                alert(data.message || 'Failed to send join request');
            }
        })
        .catch(error => {
            console.error('Error sending join request:', error);
            alert('Error sending join request');
        });
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