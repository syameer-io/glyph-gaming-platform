/**
 * Team Role Assignment Module
 *
 * Handles UI interactions for assigning game roles to team members.
 * Leaders can assign roles from a dropdown that highlights preferred roles.
 */

const TeamRoles = {
    teamId: null,
    activeDropdown: null,
    csrfToken: null,

    /**
     * Initialize the role assignment module
     * @param {number} teamId - The team ID
     */
    init(teamId) {
        this.teamId = teamId;
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.activeDropdown && !e.target.closest('.role-dropdown-container')) {
                this.closeDropdown();
            }
        });

        // Close dropdown on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeDropdown) {
                this.closeDropdown();
            }
        });
    },

    /**
     * Open the role dropdown for a member
     * @param {number} memberId - The team member ID
     * @param {HTMLElement} buttonElement - The trigger button
     */
    async openDropdown(memberId, buttonElement) {
        // Close any existing dropdown
        this.closeDropdown();

        // Show loading state
        buttonElement.classList.add('loading');

        try {
            const response = await fetch(`/teams/${this.teamId}/members/${memberId}/available-roles`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch roles');
            }

            const result = await response.json();
            if (!result.success) {
                throw new Error(result.message || 'Failed to fetch roles');
            }

            this.renderDropdown(result.data, buttonElement, memberId);
        } catch (error) {
            console.error('Error fetching roles:', error);
            this.showToast('Failed to load role options', 'error');
        } finally {
            buttonElement.classList.remove('loading');
        }
    },

    /**
     * Render the role dropdown
     * @param {Object} data - Role data from API
     * @param {HTMLElement} buttonElement - The trigger button
     * @param {number} memberId - The team member ID
     */
    renderDropdown(data, buttonElement, memberId) {
        const dropdown = document.createElement('div');
        dropdown.className = 'role-dropdown';
        dropdown.id = `role-dropdown-${memberId}`;

        const preferredRoles = data.preferred_roles || [];
        const otherRoles = data.other_roles || [];
        const currentRole = data.member.current_role;

        let html = '';

        // Preferred roles section
        if (preferredRoles.length > 0) {
            html += `
                <div class="role-section">
                    <div class="role-section-header">
                        <span class="preferred-icon">&#9733;</span> Recommended
                    </div>
                    ${this.renderRoleOptions(preferredRoles, currentRole, memberId)}
                </div>
            `;
        }

        // Divider
        if (preferredRoles.length > 0 && otherRoles.length > 0) {
            html += '<div class="role-divider"></div>';
        }

        // Other roles section
        if (otherRoles.length > 0) {
            html += `
                <div class="role-section">
                    <div class="role-section-header">Other Roles</div>
                    ${this.renderRoleOptions(otherRoles, currentRole, memberId)}
                </div>
            `;
        }

        // Clear role option
        if (currentRole) {
            html += `
                <div class="role-divider"></div>
                <div class="role-option role-clear" onclick="TeamRoles.clearRole(${memberId})">
                    <span class="role-clear-icon">&#10005;</span>
                    Clear Assignment
                </div>
            `;
        }

        dropdown.innerHTML = html;

        // Create container for positioning
        const container = document.createElement('div');
        container.className = 'role-dropdown-container';
        container.appendChild(dropdown);

        // Position the dropdown
        const rect = buttonElement.getBoundingClientRect();
        container.style.position = 'fixed';
        container.style.left = `${rect.left}px`;
        container.style.top = `${rect.bottom + 4}px`;
        container.style.zIndex = '9999';

        document.body.appendChild(container);
        this.activeDropdown = container;

        // Adjust position if dropdown goes off-screen
        const dropdownRect = dropdown.getBoundingClientRect();
        if (dropdownRect.right > window.innerWidth) {
            container.style.left = `${rect.right - dropdownRect.width}px`;
        }
        if (dropdownRect.bottom > window.innerHeight) {
            container.style.top = `${rect.top - dropdownRect.height - 4}px`;
        }
    },

    /**
     * Render role option items
     * @param {Array} roles - Array of role objects
     * @param {string|null} currentRole - Currently assigned role
     * @param {number} memberId - The team member ID
     * @returns {string} HTML string
     */
    renderRoleOptions(roles, currentRole, memberId) {
        return roles.map(role => {
            const classes = ['role-option'];
            if (role.is_preferred) classes.push('preferred');
            if (role.is_required) classes.push('required');
            if (role.is_filled && role.value !== currentRole) classes.push('filled');
            if (role.value === currentRole) classes.push('current');

            const badges = [];
            if (role.is_required && !role.is_filled) {
                badges.push('<span class="role-badge needed">Needed</span>');
            }
            if (role.is_filled && role.value !== currentRole) {
                badges.push(`<span class="role-badge filled-by">${role.filled_by}</span>`);
            }

            return `
                <div class="${classes.join(' ')}" onclick="TeamRoles.selectRole(${memberId}, '${role.value}')">
                    <div class="role-info">
                        <span class="role-name">${role.display_name}</span>
                        ${badges.join('')}
                    </div>
                    ${role.value === currentRole ? '<span class="role-check">&#10003;</span>' : ''}
                </div>
            `;
        }).join('');
    },

    /**
     * Select a role for a member
     * @param {number} memberId - The team member ID
     * @param {string} role - The role to assign
     */
    async selectRole(memberId, role) {
        this.closeDropdown();

        try {
            const response = await fetch(`/teams/${this.teamId}/members/${memberId}/game-role`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                },
                body: JSON.stringify({ role })
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to assign role');
            }

            // Update UI
            this.updateMemberRoleDisplay(memberId, result.data.member);
            this.updateRoleCoverage(result.data.role_coverage);

            // Show warnings if any
            if (result.data.warnings && result.data.warnings.length > 0) {
                this.showToast(result.data.warnings.join(' '), 'warning');
            } else {
                this.showToast('Role assigned successfully', 'success');
            }
        } catch (error) {
            console.error('Error assigning role:', error);
            this.showToast(error.message || 'Failed to assign role', 'error');
        }
    },

    /**
     * Clear a member's role assignment
     * @param {number} memberId - The team member ID
     */
    async clearRole(memberId) {
        this.closeDropdown();

        try {
            const response = await fetch(`/teams/${this.teamId}/members/${memberId}/game-role`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                }
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.message || 'Failed to clear role');
            }

            // Update UI
            this.updateMemberRoleDisplay(memberId, result.data.member);
            this.updateRoleCoverage(result.data.role_coverage);

            this.showToast('Role cleared', 'success');
        } catch (error) {
            console.error('Error clearing role:', error);
            this.showToast(error.message || 'Failed to clear role', 'error');
        }
    },

    /**
     * Close the active dropdown
     */
    closeDropdown() {
        if (this.activeDropdown) {
            this.activeDropdown.remove();
            this.activeDropdown = null;
        }
    },

    /**
     * Update the member's role display in the UI
     * @param {number} memberId - The team member ID
     * @param {Object} memberData - Updated member data
     */
    updateMemberRoleDisplay(memberId, memberData) {
        // Find the role button for this member
        const button = document.querySelector(`[data-member-id="${memberId}"] .role-assign-btn`);
        if (button) {
            const roleText = button.querySelector('.current-role');
            if (roleText) {
                roleText.textContent = memberData.game_role_display || 'Assign Role';
            }

            // Update preferred indicator
            if (memberData.is_preferred) {
                button.classList.add('is-preferred');
            } else {
                button.classList.remove('is-preferred');
            }
        }

        // Also update the role badge in the member row
        const memberRow = document.querySelector(`[data-member-id="${memberId}"]`);
        if (memberRow) {
            const roleBadge = memberRow.querySelector('.member-game-role');
            if (roleBadge) {
                if (memberData.game_role) {
                    roleBadge.textContent = memberData.game_role_display;
                    roleBadge.style.display = 'inline-block';
                } else {
                    roleBadge.style.display = 'none';
                }
            }
        }
    },

    /**
     * Update the role coverage display
     * @param {Object} coverage - Role coverage data
     */
    updateRoleCoverage(coverage) {
        // Update percentage display
        const percentElement = document.querySelector('.role-coverage-percent');
        if (percentElement) {
            percentElement.textContent = `${coverage.percent}%`;

            // Update color class based on percentage
            percentElement.classList.remove('excellent', 'good', 'poor');
            if (coverage.percent >= 75) {
                percentElement.classList.add('excellent');
            } else if (coverage.percent >= 50) {
                percentElement.classList.add('good');
            } else {
                percentElement.classList.add('poor');
            }
        }

        // Update the Team Balance card
        const balanceCard = document.querySelector('.balance-score-role-coverage');
        if (balanceCard) {
            balanceCard.textContent = `${coverage.percent}%`;
            balanceCard.classList.remove('excellent', 'good', 'poor');
            if (coverage.percent >= 75) {
                balanceCard.classList.add('excellent');
            } else if (coverage.percent >= 50) {
                balanceCard.classList.add('good');
            } else {
                balanceCard.classList.add('poor');
            }
        }

        // Update unfilled roles list
        const unfilledContainer = document.querySelector('.unfilled-roles-list');
        if (unfilledContainer && coverage.unfilled_roles) {
            if (coverage.unfilled_roles.length > 0) {
                unfilledContainer.innerHTML = coverage.unfilled_roles.map(role =>
                    `<span class="unfilled-role-badge">${this.formatRoleName(role)}</span>`
                ).join('');
                unfilledContainer.parentElement.style.display = 'block';
            } else {
                unfilledContainer.parentElement.style.display = 'none';
            }
        }
    },

    /**
     * Format a role name for display
     * @param {string} role - Role identifier
     * @returns {string} Formatted name
     */
    formatRoleName(role) {
        return role.split('_').map(word =>
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    },

    /**
     * Show a toast notification
     * @param {string} message - Message to display
     * @param {string} type - 'success', 'error', or 'warning'
     */
    showToast(message, type = 'success') {
        // Check if there's a global toast function
        if (typeof showToast === 'function') {
            showToast(message, type);
            return;
        }

        // Simple fallback toast
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            z-index: 10000;
            animation: slideIn 0.3s ease;
            background: ${type === 'success' ? '#23a559' : type === 'error' ? '#ed4245' : '#f5a623'};
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// Add CSS for the dropdown (injected once)
if (!document.getElementById('team-role-styles')) {
    const styles = document.createElement('style');
    styles.id = 'team-role-styles';
    styles.textContent = `
        .role-dropdown-container {
            font-family: inherit;
        }

        .role-dropdown {
            background: #1e1f22;
            border: 1px solid #3d3e44;
            border-radius: 8px;
            min-width: 220px;
            max-width: 280px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
            overflow: hidden;
        }

        .role-section {
            padding: 8px 0;
        }

        .role-section-header {
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #8b8d93;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .preferred-icon {
            color: #f5a623;
            font-size: 12px;
        }

        .role-divider {
            height: 1px;
            background: #3d3e44;
            margin: 4px 0;
        }

        .role-option {
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: background 0.15s ease;
        }

        .role-option:hover {
            background: #35363c;
        }

        .role-option.preferred {
            border-left: 3px solid #23a559;
            padding-left: 9px;
        }

        .role-option.current {
            background: rgba(88, 101, 242, 0.15);
        }

        .role-option.current:hover {
            background: rgba(88, 101, 242, 0.25);
        }

        .role-option.filled:not(.current) {
            opacity: 0.6;
        }

        .role-info {
            display: flex;
            align-items: center;
            gap: 8px;
            flex: 1;
            min-width: 0;
        }

        .role-name {
            color: #efeff1;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .role-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            white-space: nowrap;
        }

        .role-badge.needed {
            background: rgba(237, 66, 69, 0.2);
            color: #ed4245;
            border: 1px solid rgba(237, 66, 69, 0.3);
        }

        .role-badge.filled-by {
            background: rgba(139, 141, 147, 0.2);
            color: #8b8d93;
            font-size: 9px;
        }

        .role-check {
            color: #23a559;
            font-size: 14px;
            font-weight: bold;
        }

        .role-clear {
            color: #ed4245;
        }

        .role-clear-icon {
            margin-right: 8px;
        }

        .role-assign-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: #35363c;
            border: 1px solid #4a4b52;
            border-radius: 6px;
            color: #efeff1;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .role-assign-btn:hover {
            background: #404249;
            border-color: #5865f2;
        }

        .role-assign-btn.loading {
            opacity: 0.6;
            pointer-events: none;
        }

        .role-assign-btn.is-preferred .current-role {
            color: #23a559;
        }

        .role-assign-btn .dropdown-arrow {
            font-size: 10px;
            transition: transform 0.2s;
        }

        .member-game-role {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(88, 101, 242, 0.15);
            border: 1px solid rgba(88, 101, 242, 0.3);
            border-radius: 4px;
            font-size: 12px;
            color: #8b8dff;
        }

        .unfilled-role-badge {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(237, 66, 69, 0.15);
            border: 1px solid rgba(237, 66, 69, 0.3);
            border-radius: 4px;
            font-size: 11px;
            color: #ed4245;
            margin: 2px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(styles);
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TeamRoles;
}

// Make globally available
window.TeamRoles = TeamRoles;
