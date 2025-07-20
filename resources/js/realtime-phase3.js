/**
 * Phase 3 Real-time WebSocket Integration
 * Handles live team formation, goal progress, and matchmaking events
 */

class Phase3RealtimeManager {
    constructor() {
        this.userId = window.Laravel?.user?.id;
        this.currentServerIds = [];
        this.subscribedChannels = new Set();
        this.init();
    }

    init() {
        if (!window.Echo || !this.userId) {
            console.warn('Echo or user ID not available for Phase 3 real-time features');
            return;
        }

        this.setupGlobalChannels();
        this.setupServerChannels();
        this.setupUserChannel();
    }

    setupGlobalChannels() {
        // Global teams channel for matchmaking
        if (!this.subscribedChannels.has('teams.global')) {
            window.Echo.private('teams.global')
                .listen('.team.created', (e) => this.handleTeamCreated(e))
                .listen('.team.status.changed', (e) => this.handleTeamStatusChanged(e));
            
            this.subscribedChannels.add('teams.global');
        }
    }

    setupServerChannels() {
        // Get server IDs from page data
        this.currentServerIds = this.getServerIdsFromPage();

        this.currentServerIds.forEach(serverId => {
            if (!this.subscribedChannels.has(`server.${serverId}`)) {
                window.Echo.private(`server.${serverId}`)
                    // Team events
                    .listen('.team.created', (e) => this.handleTeamCreated(e))
                    .listen('.team.member.joined', (e) => this.handleTeamMemberJoined(e))
                    .listen('.team.member.left', (e) => this.handleTeamMemberLeft(e))
                    .listen('.team.status.changed', (e) => this.handleTeamStatusChanged(e))
                    // Goal events
                    .listen('.goal.progress.updated', (e) => this.handleGoalProgressUpdated(e))
                    .listen('.goal.milestone.reached', (e) => this.handleGoalMilestoneReached(e))
                    .listen('.goal.completed', (e) => this.handleGoalCompleted(e))
                    .listen('.goal.user.joined', (e) => this.handleUserJoinedGoal(e));

                this.subscribedChannels.add(`server.${serverId}`);
            }
        });
    }

    setupUserChannel() {
        // User-specific notifications
        if (!this.subscribedChannels.has(`user.${this.userId}`)) {
            window.Echo.private(`user.${this.userId}`)
                .listen('.team.member.joined', (e) => this.handlePersonalTeamJoined(e))
                .listen('.team.member.left', (e) => this.handlePersonalTeamLeft(e))
                .listen('.goal.user.joined', (e) => this.handlePersonalGoalJoined(e));

            this.subscribedChannels.add(`user.${this.userId}`);
        }
    }

    getServerIdsFromPage() {
        const serverIds = [];
        
        // Try to get server ID from various page elements
        const serverIdMeta = document.querySelector('meta[name="server-id"]');
        if (serverIdMeta) {
            serverIds.push(parseInt(serverIdMeta.getAttribute('content')));
        }

        // Get from data attributes
        document.querySelectorAll('[data-server-id]').forEach(el => {
            const serverId = parseInt(el.dataset.serverId);
            if (serverId && !serverIds.includes(serverId)) {
                serverIds.push(serverId);
            }
        });

        return serverIds;
    }

    // Team Event Handlers
    handleTeamCreated(event) {
        const team = event.team;
        console.log('Team created:', team);

        // Update team lists on relevant pages
        this.updateTeamList(team, 'created');
        
        // Show notification
        this.showNotification(`New team "${team.name}" created for ${team.game_name}`, 'info');

        // Update dashboard widgets if on dashboard
        if (this.isDashboardPage()) {
            this.refreshDashboardWidget('teams');
        }
    }

    handleTeamMemberJoined(event) {
        const { team, member } = event;
        console.log('Team member joined:', member);

        // Update team displays
        this.updateTeamMemberCount(team.id, team.current_size, team.max_size);
        this.updateTeamStatus(team.id, team.status);
        
        // Add member to team member list if showing team details
        this.addTeamMemberToUI(team.id, member);

        // Show notification for team members
        if (this.isTeamMember(team.id)) {
            this.showNotification(`${member.user.display_name} joined the team`, 'success');
        }
    }

    handleTeamMemberLeft(event) {
        const { team, user } = event;
        console.log('Team member left:', user);

        // Update team displays
        this.updateTeamMemberCount(team.id, team.current_size, team.max_size);
        this.updateTeamStatus(team.id, team.status);
        
        // Remove member from team member list
        this.removeTeamMemberFromUI(team.id, user.id);

        // Show notification for team members
        if (this.isTeamMember(team.id)) {
            this.showNotification(`${user.display_name} left the team`, 'warning');
        }
    }

    handleTeamStatusChanged(event) {
        const team = event.team;
        console.log('Team status changed:', team);

        // Update status indicators
        this.updateTeamStatus(team.id, team.status);
        
        // Update team in lists
        this.updateTeamList(team, 'updated');
    }

    handlePersonalTeamJoined(event) {
        const { team, member } = event;
        
        if (member.user_id === this.userId) {
            this.showNotification(`You joined team "${team.name}"!`, 'success');
            
            // Refresh page sections
            if (this.isDashboardPage()) {
                this.refreshDashboardWidget('teams');
            }
        }
    }

    handlePersonalTeamLeft(event) {
        const { team, user } = event;
        
        if (user.id === this.userId) {
            this.showNotification(`You left team "${team.name}"`, 'info');
            
            // Refresh page sections
            if (this.isDashboardPage()) {
                this.refreshDashboardWidget('teams');
            }
        }
    }

    // Goal Event Handlers
    handleGoalProgressUpdated(event) {
        const goal = event.goal;
        console.log('Goal progress updated:', goal);

        // Update progress bars
        this.updateGoalProgress(goal.id, goal.progress, goal.completion_percentage);
        
        // Update participant count
        this.updateGoalParticipantCount(goal.id, goal.participant_count);
    }

    handleGoalMilestoneReached(event) {
        const { goal, milestone } = event;
        console.log('Goal milestone reached:', milestone);

        // Show celebration notification
        this.showMilestoneCelebration(goal, milestone);
        
        // Update milestone indicators
        this.updateGoalMilestones(goal.id);
    }

    handleGoalCompleted(event) {
        const { goal, top_contributors } = event;
        console.log('Goal completed:', goal);

        // Show completion celebration
        this.showGoalCompletionCelebration(goal, top_contributors);
        
        // Update goal status
        this.updateGoalStatus(goal.id, 'completed');
        
        // Update progress to 100%
        this.updateGoalProgress(goal.id, goal.target_value, 100);
    }

    handleUserJoinedGoal(event) {
        const { goal, participant } = event;
        console.log('User joined goal:', participant);

        // Update participant count and list
        this.updateGoalParticipantCount(goal.id, goal.participant_count);
        this.addGoalParticipant(goal.id, participant);
    }

    handlePersonalGoalJoined(event) {
        const { goal, participant } = event;
        
        if (participant.user_id === this.userId) {
            this.showNotification(`You joined goal "${goal.title}"!`, 'success');
            
            // Refresh dashboard if on dashboard
            if (this.isDashboardPage()) {
                this.refreshDashboardWidget('goals');
            }
        }
    }

    // UI Update Methods
    updateTeamList(team, action) {
        // Update team browser and matchmaking lists
        const teamElements = document.querySelectorAll(`[data-team-id="${team.id}"]`);
        
        if (action === 'created') {
            // Add new team card if on team browser
            this.addTeamToList(team);
        } else if (action === 'updated') {
            teamElements.forEach(el => {
                // Update team status badges
                const statusBadge = el.querySelector('.team-status-badge');
                if (statusBadge) {
                    statusBadge.textContent = this.formatTeamStatus(team.status);
                    statusBadge.className = `team-status-badge status-${team.status}`;
                }
            });
        }
    }

    updateTeamMemberCount(teamId, currentSize, maxSize) {
        const memberCountElements = document.querySelectorAll(`[data-team-id="${teamId}"] .member-count`);
        memberCountElements.forEach(el => {
            el.textContent = `${currentSize}/${maxSize}`;
        });
    }

    updateTeamStatus(teamId, status) {
        const statusElements = document.querySelectorAll(`[data-team-id="${teamId}"] .team-status`);
        statusElements.forEach(el => {
            el.textContent = this.formatTeamStatus(status);
            el.className = `team-status status-${status}`;
        });
    }

    addTeamMemberToUI(teamId, member) {
        const memberLists = document.querySelectorAll(`[data-team-id="${teamId}"] .team-members-list`);
        memberLists.forEach(list => {
            const memberEl = this.createMemberElement(member);
            list.appendChild(memberEl);
        });
    }

    removeTeamMemberFromUI(teamId, userId) {
        const memberElements = document.querySelectorAll(`[data-team-id="${teamId}"] [data-user-id="${userId}"]`);
        memberElements.forEach(el => el.remove());
    }

    updateGoalProgress(goalId, progress, percentage) {
        // Update progress bars
        const progressBars = document.querySelectorAll(`[data-goal-id="${goalId}"] .progress-bar`);
        progressBars.forEach(bar => {
            bar.style.width = `${percentage}%`;
            bar.setAttribute('aria-valuenow', percentage);
        });

        // Update progress text
        const progressTexts = document.querySelectorAll(`[data-goal-id="${goalId}"] .progress-text`);
        progressTexts.forEach(text => {
            text.textContent = `${progress} / ${text.dataset.target} (${Math.round(percentage)}%)`;
        });
    }

    updateGoalParticipantCount(goalId, count) {
        const countElements = document.querySelectorAll(`[data-goal-id="${goalId}"] .participant-count`);
        countElements.forEach(el => {
            el.textContent = count;
        });
    }

    updateGoalStatus(goalId, status) {
        const statusElements = document.querySelectorAll(`[data-goal-id="${goalId}"] .goal-status`);
        statusElements.forEach(el => {
            el.textContent = this.formatGoalStatus(status);
            el.className = `goal-status status-${status}`;
        });
    }

    // Notification Methods
    showNotification(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${this.getIconForType(type)}"></i>
                <span>${message}</span>
            </div>
        `;

        // Add to notification container
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        container.appendChild(toast);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }

    showMilestoneCelebration(goal, milestone) {
        this.showNotification(
            `🎉 Milestone "${milestone.name}" reached in "${goal.title}"!`,
            'success'
        );
    }

    showGoalCompletionCelebration(goal, topContributors) {
        let message = `🏆 Goal "${goal.title}" completed!`;
        if (topContributors.length > 0) {
            message += ` Top contributors: ${topContributors.map(c => c.user.display_name).join(', ')}`;
        }
        
        this.showNotification(message, 'success');
    }

    // Utility Methods
    isDashboardPage() {
        return window.location.pathname === '/dashboard';
    }

    isTeamMember(teamId) {
        // Check if current user is member of team
        return document.querySelector(`[data-team-id="${teamId}"][data-user-member="true"]`) !== null;
    }

    formatTeamStatus(status) {
        const statusMap = {
            'recruiting': 'Recruiting',
            'full': 'Full',
            'active': 'Active',
            'disbanded': 'Disbanded'
        };
        return statusMap[status] || status;
    }

    formatGoalStatus(status) {
        const statusMap = {
            'active': 'Active',
            'completed': 'Completed',
            'expired': 'Expired',
            'paused': 'Paused'
        };
        return statusMap[status] || status;
    }

    getIconForType(type) {
        const iconMap = {
            'info': 'info-circle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'times-circle'
        };
        return iconMap[type] || 'info-circle';
    }

    refreshDashboardWidget(widgetType) {
        // Refresh specific dashboard widgets
        const widget = document.querySelector(`[data-widget="${widgetType}"]`);
        if (widget) {
            // Add refresh logic here
            widget.classList.add('refreshing');
            setTimeout(() => {
                widget.classList.remove('refreshing');
            }, 1000);
        }
    }

    createMemberElement(member) {
        const div = document.createElement('div');
        div.className = 'team-member';
        div.setAttribute('data-user-id', member.user_id);
        div.innerHTML = `
            <img src="${member.user.avatar_url || '/images/default-avatar.png'}" 
                 alt="${member.user.display_name}" class="member-avatar">
            <span class="member-name">${member.user.display_name}</span>
            <span class="member-role">${member.role}</span>
        `;
        return div;
    }

    addTeamToList(team) {
        // Add new team to team browser if present
        const teamList = document.querySelector('.teams-grid, .teams-list');
        if (teamList) {
            // Create team card element and add to list
            // Implementation depends on existing team card structure
        }
    }

    addGoalParticipant(goalId, participant) {
        const participantLists = document.querySelectorAll(`[data-goal-id="${goalId}"] .participants-list`);
        participantLists.forEach(list => {
            const participantEl = this.createParticipantElement(participant);
            list.appendChild(participantEl);
        });
    }

    createParticipantElement(participant) {
        const div = document.createElement('div');
        div.className = 'goal-participant';
        div.setAttribute('data-user-id', participant.user_id);
        div.innerHTML = `
            <img src="${participant.user.avatar_url || '/images/default-avatar.png'}" 
                 alt="${participant.user.display_name}" class="participant-avatar">
            <span class="participant-name">${participant.user.display_name}</span>
            <span class="participant-contribution">${participant.contribution_percentage}%</span>
        `;
        return div;
    }

    updateGoalMilestones(goalId) {
        // Refresh milestone indicators for a goal
        const milestoneContainers = document.querySelectorAll(`[data-goal-id="${goalId}"] .milestones-container`);
        milestoneContainers.forEach(container => {
            // Add logic to refresh milestone display
            container.classList.add('milestone-updated');
            setTimeout(() => {
                container.classList.remove('milestone-updated');
            }, 2000);
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.Phase3Realtime = new Phase3RealtimeManager();
});

// Export for manual initialization if needed
export default Phase3RealtimeManager;