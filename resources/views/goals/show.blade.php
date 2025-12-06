@extends('layouts.app')

@section('title', $goal->title . ' - ' . $server->name)

@push('styles')
<style>
    .goal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 32px;
        border-radius: 12px;
        margin-bottom: 24px;
    }
    
    .progress-ring {
        transform: rotate(-90deg);
    }
    
    .progress-ring-circle {
        stroke: rgba(255, 255, 255, 0.3);
        stroke-width: 8;
        fill: transparent;
        r: 45;
        cx: 50;
        cy: 50;
    }
    
    .progress-ring-fill {
        stroke: #10b981;
        stroke-width: 8;
        fill: transparent;
        r: 45;
        cx: 50;
        cy: 50;
        stroke-dasharray: 283;
        stroke-dashoffset: 283;
        transition: stroke-dashoffset 0.5s ease;
    }
    
    .leaderboard-item {
        display: flex;
        align-items: center;
        padding: 16px;
        background-color: #0e0e10;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }
    
    .leaderboard-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .rank-badge {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        margin-right: 16px;
    }
    
    .rank-1 { background: linear-gradient(135deg, #ffd700, #ffed4a); color: #1a1a1a; }
    .rank-2 { background: linear-gradient(135deg, #c0c0c0, #e5e7eb); color: #1a1a1a; }
    .rank-3 { background: linear-gradient(135deg, #cd7f32, #d97706); color: white; }
    .rank-other { background-color: #3f3f46; color: #b3b3b5; }
    
    .milestone-item {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        background-color: #0e0e10;
        border-radius: 8px;
        margin-bottom: 8px;
        border-left: 4px solid #3f3f46;
    }
    
    .milestone-achieved {
        border-left-color: #10b981;
        background-color: rgba(16, 185, 129, 0.1);
    }
</style>
@endpush

@section('content')
<x-navbar />

<main>
    <div class="container">
        <!-- Goal Header -->
        <div class="goal-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <h1 style="margin: 0 0 8px 0; font-size: 28px; font-weight: 700;">{{ $goal->title }}</h1>
                    <p style="margin: 0 0 16px 0; opacity: 0.9; font-size: 16px;">{{ $goal->description }}</p>
                    
                    <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                        <span style="background: rgba(255, 255, 255, 0.2); padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                            📊 {{ ucfirst($goal->goal_type) }}
                        </span>
                        @if($goal->game_name)
                            <span style="background: rgba(255, 255, 255, 0.2); padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                🎮 {{ $goal->game_name }}
                            </span>
                        @endif
                        @if($goal->deadline)
                            <span style="background: rgba(255, 255, 255, 0.2); padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                                📅 {{ \Carbon\Carbon::parse($goal->deadline)->format('M j, Y') }}
                            </span>
                        @endif
                        <span style="background: rgba(255, 255, 255, 0.2); padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: 600;">
                            💎 {{ ucfirst($goal->difficulty ?? 'Medium') }}
                        </span>
                    </div>
                </div>
                
                <!-- Progress Circle -->
                <div style="text-align: center;">
                    <div style="position: relative; width: 100px; height: 100px;">
                        <svg class="progress-ring" width="100" height="100">
                            <circle class="progress-ring-circle"></circle>
                            <circle class="progress-ring-fill" style="stroke-dashoffset: {{ 283 - (283 * min(($goal->current_progress ?? 0) / $goal->target_value * 100, 100) / 100) }};"></circle>
                        </svg>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                            <div style="font-size: 18px; font-weight: 700;">{{ $goal->target_value > 0 ? round(($goal->current_progress ?? 0) / $goal->target_value * 100, 1) : 0 }}%</div>
                            <div style="font-size: 10px; opacity: 0.8;">COMPLETE</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Join/Leave Button -->
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <div style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">{{ $goal->current_progress ?? 0 }} / {{ $goal->target_value }}</div>
                    <div style="opacity: 0.8;">Current Progress</div>
                </div>
                
                @php
                    $userParticipant = $goal->participants->where('user_id', auth()->id())->where('participation_status', 'active')->first();
                @endphp
                
                @if($userParticipant)
                    <button onclick="leaveGoal({{ $goal->id }})" class="btn" style="background: rgba(255, 255, 255, 0.2); border: 2px solid white; color: white; padding: 12px 24px; font-weight: 600;">
                        ✓ Joined Goal
                    </button>
                @else
                    <button onclick="joinGoal({{ $goal->id }})" class="btn" style="background: white; color: #667eea; padding: 12px 24px; font-weight: 600;">
                        Join This Goal
                    </button>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-3" style="gap: 24px;">
            <!-- Left Column: Leaderboard -->
            <div style="grid-column: span 2;">
                <div class="card">
                    <h3 class="card-header">🏆 Leaderboard</h3>
                    
                    @if($leaderboard && $leaderboard->count() > 0)
                        @foreach($leaderboard as $index => $participant)
                            <div class="leaderboard-item">
                                <div class="rank-badge {{ $index + 1 <= 3 ? 'rank-' . ($index + 1) : 'rank-other' }}">
                                    {{ $index + 1 }}
                                </div>
                                <img src="{{ $participant['user']->profile->avatar_url }}" alt="{{ $participant['user']->display_name }}" 
                                     style="width: 48px; height: 48px; border-radius: 50%; margin-right: 16px;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #efeff1; margin-bottom: 4px;">{{ $participant['user']->display_name }}</div>
                                    <div style="font-size: 14px; color: #b3b3b5;">
                                        Progress: {{ $participant['progress'] }} • 
                                        Contribution: {{ round($participant['contribution'], 1) }}%
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: 20px; font-weight: 700; color: #10b981;">{{ $participant['progress'] }}</div>
                                    <div style="font-size: 12px; color: #71717a;">points</div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="text-align: center; padding: 40px; color: #71717a;">
                            <div style="font-size: 48px; margin-bottom: 16px;">🏆</div>
                            <p>No participants yet. Be the first to join!</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Stats & Milestones -->
            <div>
                <!-- Participation Stats -->
                <div class="card" style="margin-bottom: 24px;">
                    <h4 style="margin-bottom: 16px;">📊 Statistics</h4>
                    
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #b3b3b5;">Participants</span>
                            <span style="font-weight: 600;">{{ $goal->participants->where('participation_status', 'active')->count() }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #b3b3b5;">Created</span>
                            <span style="font-weight: 600;">{{ $goal->created_at->format('M j, Y') }}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span style="color: #b3b3b5;">Status</span>
                            <span style="padding: 4px 8px; background-color: #10b981; color: white; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                {{ ucfirst($goal->status) }}
                            </span>
                        </div>
                        @if($goal->deadline)
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #b3b3b5;">Time Left</span>
                                <span style="font-weight: 600; color: #f59e0b;">{{ \Carbon\Carbon::parse($goal->deadline)->diffForHumans() }}</span>
                            </div>
                        @endif
                    </div>
                    
                    @if($userParticipant)
                        <button onclick="updateProgress({{ $goal->id }})" class="btn btn-primary" style="width: 100%; margin-bottom: 12px;">
                            Update My Progress
                        </button>
                    @endif
                    
                    <button onclick="shareGoal()" class="btn btn-secondary" style="width: 100%;">
                        Share Goal
                    </button>
                </div>

                <!-- Milestones -->
                @if($goal->milestones && $goal->milestones->count() > 0)
                    <div class="card">
                        <h4 style="margin-bottom: 16px;">🎯 Milestones</h4>
                        
                        @foreach($goal->milestones as $milestone)
                            <div class="milestone-item {{ $milestone->is_achieved ? 'milestone-achieved' : '' }}">
                                <div style="margin-right: 12px; font-size: 16px;">
                                    {{ $milestone->is_achieved ? '✅' : '⏳' }}
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: {{ $milestone->is_achieved ? '#10b981' : '#efeff1' }}; margin-bottom: 2px;">
                                        {{ $milestone->milestone_name }}
                                    </div>
                                    <div style="font-size: 12px; color: #71717a;">
                                        {{ $milestone->progress_required }} / {{ $goal->target_value }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>

<script>
function joinGoal(goalId) {
    fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}/join`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Join goal response:', data); // Debug logging
        
        if (data.success) {
            showNotification('Successfully joined the goal!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.error || data.message || 'Failed to join goal', 'error');
        }
    })
    .catch(error => {
        console.error('Network or parsing error:', error);
        showNotification('An error occurred while joining the goal: ' + error.message, 'error');
    });
}

function leaveGoal(goalId) {
    if (!confirm('Are you sure you want to leave this goal?')) {
        return;
    }
    
    fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}/leave`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Successfully left the goal', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.error || 'Failed to leave goal', 'error');
        }
    })
    .catch(error => {
        console.error('Error leaving goal:', error);
        showNotification('An error occurred while leaving the goal', 'error');
    });
}

function updateProgress(goalId) {
    const progressValue = prompt('Enter your current progress value:');
    
    if (progressValue === null || progressValue === '') {
        return;
    }
    
    if (isNaN(progressValue) || progressValue < 0) {
        showNotification('Please enter a valid positive number', 'error');
        return;
    }
    
    fetch(`{{ url('servers/' . $server->id . '/goals') }}/${goalId}/my-progress`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            progress: parseInt(progressValue)
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Update progress response:', data); // Debug logging
        
        if (data.success) {
            showNotification('Progress updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification(data.error || data.message || 'Failed to update progress', 'error');
        }
    })
    .catch(error => {
        console.error('Network or parsing error:', error);
        showNotification('An error occurred while updating progress: ' + error.message, 'error');
    });
}

function shareGoal() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: '{{ $goal->title }}',
            text: 'Join our community goal: {{ $goal->description }}',
            url: url
        });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url);
        showNotification('Goal link copied to clipboard!', 'success');
    } else {
        prompt('Copy this link to share:', url);
    }
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10000;
        padding: 12px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        font-size: 14px;
        opacity: 0;
        transform: translateX(100px);
        transition: all 0.3s ease;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    `;
    
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#10b981';
            break;
        case 'error':
            notification.style.backgroundColor = '#ef4444';
            break;
        default:
            notification.style.backgroundColor = '#667eea';
    }
    
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '1';
        notification.style.transform = 'translateX(0)';
    }, 10);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100px)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 4000);
}
</script>
@endsection