/**
 * Team Skill Distribution Charts
 * Visual analytics for team performance and skill balance
 */
import { Chart, registerables } from 'chart.js';

// Register Chart.js components
Chart.register(...registerables);

class TeamSkillCharts {
    constructor(teamData) {
        this.teamData = teamData;
        this.charts = {};
        this.init();
    }

    init() {
        this.createSkillDistributionChart();
        this.createRoleBalanceChart();
        this.createSkillProgressChart();
        this.createTeamCompatibilityChart();
    }

    /**
     * Create skill distribution radar chart
     */
    createSkillDistributionChart() {
        const ctx = document.getElementById('skillDistributionChart');
        if (!ctx) return;

        const members = this.teamData.members || [];
        const skillLabels = ['Aim', 'Game Sense', 'Communication', 'Strategy', 'Teamwork', 'Adaptability'];
        
        // Calculate average skills for the team
        const teamSkillData = skillLabels.map(skill => {
            const average = members.reduce((sum, member) => {
                return sum + (member.skills?.[skill.toLowerCase()] || Math.floor(Math.random() * 40) + 60);
            }, 0) / members.length;
            return Math.round(average);
        });

        // Individual member data for comparison
        const memberDatasets = members.slice(0, 3).map((member, index) => ({
            label: member.name,
            data: skillLabels.map(skill => member.skills?.[skill.toLowerCase()] || Math.floor(Math.random() * 40) + 60),
            borderColor: this.getSkillColor(index),
            backgroundColor: this.getSkillColor(index, 0.1),
            borderWidth: 2,
            pointBackgroundColor: this.getSkillColor(index),
        }));

        this.charts.skillDistribution = new Chart(ctx, {
            type: 'radar',
            data: {
                labels: skillLabels,
                datasets: [
                    {
                        label: 'Team Average',
                        data: teamSkillData,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                    },
                    ...memberDatasets
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Team Skill Distribution',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        labels: { color: '#b3b3b5' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(24, 24, 27, 0.95)',
                        titleColor: '#efeff1',
                        bodyColor: '#b3b3b5',
                        borderColor: '#3f3f46',
                        borderWidth: 1,
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#3f3f46' },
                        angleLines: { color: '#3f3f46' },
                        pointLabels: { color: '#b3b3b5', font: { size: 12 } },
                        ticks: { 
                            color: '#71717a',
                            stepSize: 20,
                            showLabelBackdrop: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Create role balance doughnut chart
     */
    createRoleBalanceChart() {
        const ctx = document.getElementById('roleBalanceChart');
        if (!ctx) return;

        const members = this.teamData.members || [];
        const roleCounts = {};
        
        // Count members by role
        members.forEach(member => {
            const role = member.game_role || 'unassigned';
            roleCounts[role] = (roleCounts[role] || 0) + 1;
        });

        const roleLabels = Object.keys(roleCounts);
        const roleData = Object.values(roleCounts);
        const roleColors = roleLabels.map((_, index) => this.getRoleColor(index));

        this.charts.roleBalance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: roleLabels.map(role => this.formatRoleName(role)),
                datasets: [{
                    data: roleData,
                    backgroundColor: roleColors,
                    borderColor: '#18181b',
                    borderWidth: 2,
                    hoverBorderWidth: 3,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Role Distribution',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        position: 'bottom',
                        labels: { 
                            color: '#b3b3b5',
                            padding: 20,
                            usePointStyle: true,
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(24, 24, 27, 0.95)',
                        titleColor: '#efeff1',
                        bodyColor: '#b3b3b5',
                        borderColor: '#3f3f46',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((sum, val) => sum + val, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return `${context.label}: ${context.parsed} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%',
            }
        });
    }

    /**
     * Create skill progress line chart
     */
    createSkillProgressChart() {
        const ctx = document.getElementById('skillProgressChart');
        if (!ctx) return;

        const members = this.teamData.members || [];
        
        // Generate mock progress data over the last 30 days
        const days = 30;
        const labels = Array.from({length: days}, (_, i) => {
            const date = new Date();
            date.setDate(date.getDate() - (days - 1 - i));
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        });

        const progressDatasets = members.slice(0, 4).map((member, index) => {
            const baseSkill = member.individual_skill_score || Math.floor(Math.random() * 40) + 60;
            const progressData = [];
            let currentSkill = Math.max(30, baseSkill - Math.floor(Math.random() * 20));
            
            for (let i = 0; i < days; i++) {
                const change = (Math.random() - 0.3) * 3; // Slight upward trend
                currentSkill = Math.max(30, Math.min(100, currentSkill + change));
                progressData.push(Math.round(currentSkill));
            }

            return {
                label: member.name,
                data: progressData,
                borderColor: this.getSkillColor(index),
                backgroundColor: this.getSkillColor(index, 0.1),
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 0,
                pointHoverRadius: 6,
            };
        });

        this.charts.skillProgress = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: progressDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Skill Progress (30 Days)',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        labels: { color: '#b3b3b5' }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(24, 24, 27, 0.95)',
                        titleColor: '#efeff1',
                        bodyColor: '#b3b3b5',
                        borderColor: '#3f3f46',
                        borderWidth: 1,
                    }
                },
                scales: {
                    x: {
                        grid: { color: '#3f3f46' },
                        ticks: { 
                            color: '#71717a',
                            maxTicksLimit: 8
                        }
                    },
                    y: {
                        beginAtZero: false,
                        min: 30,
                        max: 100,
                        grid: { color: '#3f3f46' },
                        ticks: { 
                            color: '#71717a',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
    }

    /**
     * Create team compatibility matrix chart
     */
    createTeamCompatibilityChart() {
        const ctx = document.getElementById('teamCompatibilityChart');
        if (!ctx) return;

        const compatibilityCategories = ['Skill Match', 'Schedule Sync', 'Communication', 'Play Style', 'Goals Alignment'];
        const teamStats = this.teamData.stats || {};
        
        const compatibilityData = compatibilityCategories.map(category => {
            const key = category.toLowerCase().replace(/\s+/g, '_');
            return teamStats[key] || Math.floor(Math.random() * 30) + 70;
        });

        this.charts.teamCompatibility = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: compatibilityCategories,
                datasets: [{
                    label: 'Compatibility Score',
                    data: compatibilityData,
                    backgroundColor: compatibilityData.map(score => this.getCompatibilityColor(score)),
                    borderColor: '#667eea',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Team Compatibility Analysis',
                        color: '#efeff1',
                        font: { size: 16, weight: 'bold' }
                    },
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(24, 24, 27, 0.95)',
                        titleColor: '#efeff1',
                        bodyColor: '#b3b3b5',
                        borderColor: '#3f3f46',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const score = context.parsed.y;
                                const rating = score >= 85 ? 'Excellent' : score >= 70 ? 'Good' : score >= 55 ? 'Average' : 'Needs Improvement';
                                return `${context.label}: ${score}% (${rating})`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: '#b3b3b5',
                            font: { size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#3f3f46' },
                        ticks: { 
                            color: '#71717a',
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    }

    /**
     * Update charts with new data
     */
    updateCharts(newTeamData) {
        this.teamData = { ...this.teamData, ...newTeamData };
        
        // Destroy existing charts
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
        
        // Recreate charts with new data
        this.init();
    }

    /**
     * Utility methods for colors and formatting
     */
    getSkillColor(index, alpha = 1) {
        const colors = [
            `rgba(102, 126, 234, ${alpha})`, // Primary blue
            `rgba(16, 185, 129, ${alpha})`,  // Green
            `rgba(245, 158, 11, ${alpha})`,  // Orange
            `rgba(239, 68, 68, ${alpha})`,   // Red
            `rgba(168, 85, 247, ${alpha})`,  // Purple
            `rgba(6, 182, 212, ${alpha})`,   // Cyan
        ];
        return colors[index % colors.length];
    }

    getRoleColor(index) {
        const colors = [
            '#667eea', '#10b981', '#f59e0b', '#ef4444', 
            '#a855f7', '#06b6d4', '#84cc16', '#f97316'
        ];
        return colors[index % colors.length];
    }

    getCompatibilityColor(score) {
        if (score >= 85) return 'rgba(16, 185, 129, 0.8)';  // Green
        if (score >= 70) return 'rgba(245, 158, 11, 0.8)';  // Orange
        if (score >= 55) return 'rgba(102, 126, 234, 0.8)'; // Blue
        return 'rgba(239, 68, 68, 0.8)';                    // Red
    }

    formatRoleName(role) {
        return role.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    /**
     * Destroy all charts
     */
    destroy() {
        Object.values(this.charts).forEach(chart => {
            if (chart) chart.destroy();
        });
        this.charts = {};
    }
}

// Export for global use
window.TeamSkillCharts = TeamSkillCharts;

export default TeamSkillCharts;