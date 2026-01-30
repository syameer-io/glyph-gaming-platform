@props([
    'team',
    'showCompatibility' => false,
    'compatibilityScore' => null,
    'compatibilityDetails' => null,
    'context' => 'browse'
])

@php
    // Validate required props
    if (!isset($team)) {
        throw new \InvalidArgumentException('team-card component requires a $team prop');
    }

    // Determine status information
    $isRecruiting = $team->status === 'recruiting' && $team->activeMembers->count() < $team->max_size;
    $isFull = $team->activeMembers->count() >= $team->max_size;

    // Status indicator class
    $statusClass = match($team->status) {
        'recruiting' => 'team-badge-recruiting',
        'full' => 'team-badge-full',
        'active' => 'team-badge-active',
        default => 'team-badge-active'
    };

    // Compatibility score class
    $compatClass = '';
    if ($showCompatibility && $compatibilityScore !== null) {
        if ($compatibilityScore >= 80) {
            $compatClass = 'score-high';
        } elseif ($compatibilityScore >= 60) {
            $compatClass = 'score-medium';
        } else {
            $compatClass = 'score-low';
        }
    }

    // Check if user is a member (prevent errors if not authenticated)
    $isMember = false;
    if (auth()->check()) {
        $isMember = $team->activeMembers->contains('user_id', auth()->id());
    }

    // Check if team is open for direct joining
    $isOpenRecruitment = $team->recruitment_status === 'open';

    // Show join/request button logic
    $showJoinButton = false;
    if (auth()->check() && !$isMember) {
        if ($context === 'matchmaking') {
            $showJoinButton = true;
        } elseif ($context === 'browse' && $isRecruiting) {
            $showJoinButton = true;
        }
    }

    // Truncate description
    $truncatedDescription = null;
    if ($team->description) {
        $truncatedDescription = strlen($team->description) > 100
            ? substr($team->description, 0, 100) . '...'
            : $team->description;
    }
@endphp

<div class="team-card"
    data-team-id="{{ $team->id }}"
    data-server-id="{{ $team->server_id ?? '' }}"
    data-game="{{ $team->game_appid }}"
    data-skill="{{ $team->skill_level }}"
    data-region="{{ $team->preferred_region }}"
    data-status="{{ $team->status }}"
    data-name="{{ strtolower($team->name) }}"
    data-members="{{ $team->activeMembers->count() }}"
    data-created="{{ $team->created_at->timestamp }}"
    @if(auth()->check() && $isMember)
    data-user-member="true"
    @endif
>
    {{-- Team Header --}}
    <div class="team-card-header">
        <div class="team-card-info">
            <h3 class="team-card-name">{{ $team->name }}</h3>
            <div class="team-card-game">{{ $team->game_name ?? 'Unknown Game' }}</div>

            {{-- Status Badges --}}
            <div class="team-card-badges">
                <div class="team-badge {{ $statusClass }}">
                    <span class="team-badge-dot"></span>
                    @if($team->status === 'recruiting')
                        Recruiting
                    @elseif($team->status === 'full')
                        Full
                    @elseif($team->status === 'active')
                        Active
                    @else
                        {{ ucfirst($team->status) }}
                    @endif
                </div>

                @if($team->status === 'recruiting')
                    @if($isOpenRecruitment)
                        <div class="team-badge team-badge-open" title="Anyone can join directly">Open</div>
                    @else
                        <div class="team-badge team-badge-closed" title="Requires approval to join">Closed</div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Compatibility Score OR Team Stats --}}
        @if($showCompatibility && $compatibilityScore !== null)
            <div class="team-compat-score {{ $compatClass }}">
                <div class="team-compat-value {{ $compatClass }}">{{ round($compatibilityScore) }}%</div>
                <div class="team-compat-label">Match</div>
            </div>
        @else
            <div class="team-card-stats">
                <div class="team-card-skill">{{ ucfirst($team->skill_level ?? 'Casual') }}</div>
                <div class="team-card-members">{{ $team->activeMembers->count() }}/{{ $team->max_size }} members</div>
            </div>
        @endif
    </div>

    {{-- Team Members Preview --}}
    <div class="team-members-stack">
        @foreach($team->activeMembers->take(5) as $member)
            <img
                src="{{ $member->user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
                alt="{{ $member->user->display_name }}"
                title="{{ $member->user->display_name }}{{ $member->game_role ? ' (' . ucfirst(str_replace('_', ' ', $member->game_role)) . ')' : '' }}"
                class="team-member-avatar"
            >
        @endforeach

        @for($i = $team->activeMembers->count(); $i < min($team->max_size, 5); $i++)
            <div class="team-member-placeholder">+</div>
        @endfor

        @if($team->activeMembers->count() > 5)
            <div class="team-members-overflow">+{{ $team->activeMembers->count() - 5 }} more</div>
        @endif
    </div>

    {{-- Team Description --}}
    @if($truncatedDescription)
        <div class="team-card-description">{{ $truncatedDescription }}</div>
    @endif

    {{-- Compatibility Details Breakdown (if available) --}}
    @if($showCompatibility && $compatibilityDetails)
        <div class="team-compat-breakdown">
            @foreach($compatibilityDetails as $key => $value)
                @php
                    $itemClass = $value >= 70 ? 'score-high' : ($value >= 50 ? 'score-medium' : 'score-low');
                @endphp
                <div class="team-compat-item">
                    <div class="team-compat-item-label">{{ ucfirst($key) }}</div>
                    <div class="team-compat-item-value {{ $itemClass }}">{{ round($value) }}%</div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Team Tags --}}
    <div class="team-card-tags">
        {{-- Skill Level Tag (Primary) --}}
        @if($team->skill_level)
            <span class="team-tag team-tag-skill">{{ ucfirst($team->skill_level) }}</span>
        @endif

        {{-- Preferred Region Tag --}}
        @if($team->preferred_region)
            <span class="team-tag team-tag-region">{{ ucfirst(str_replace('_', ' ', $team->preferred_region)) }}</span>
        @endif

        {{-- Required Roles Tags --}}
        @if(!empty($team->required_roles) && is_array($team->required_roles))
            @foreach($team->required_roles as $role)
                <span class="team-tag team-tag-role" title="Looking for this role">{{ ucfirst(str_replace('_', ' ', $role)) }}</span>
            @endforeach
        @endif

        {{-- Activity Times Tags --}}
        @if(!empty($team->activity_times) && is_array($team->activity_times))
            @foreach($team->activity_times as $time)
                <span class="team-tag team-tag-time" title="Active during this time">{{ ucfirst(str_replace('_', ' ', $time)) }}</span>
            @endforeach
        @endif

        {{-- Languages Tags --}}
        @if(!empty($team->languages) && is_array($team->languages))
            @php
                $languageMap = [
                    'en' => 'English',
                    'es' => 'Spanish',
                    'zh' => 'Chinese',
                    'fr' => 'French',
                    'de' => 'German',
                    'pt' => 'Portuguese',
                    'ru' => 'Russian',
                    'ja' => 'Japanese',
                    'ko' => 'Korean'
                ];
            @endphp
            @foreach($team->languages as $langCode)
                <span class="team-tag team-tag-language" title="Speaks this language">{{ $languageMap[$langCode] ?? ucfirst($langCode) }}</span>
            @endforeach
        @endif

        {{-- Legacy: Single Activity Time from team_data (backward compatibility) --}}
        @if($team->activity_time && (empty($team->activity_times) || !is_array($team->activity_times)))
            <span class="team-tag team-tag-time">{{ ucfirst(str_replace('_', ' ', $team->activity_time)) }}</span>
        @endif

        {{-- Communication Required Tag --}}
        @if($team->communication_required)
            <span class="team-tag team-tag-voice">Voice Chat</span>
        @endif
    </div>

    {{-- Action Buttons --}}
    <div class="team-card-actions">
        <a href="{{ route('teams.show', $team) }}" class="btn btn-view">View Team</a>

        @if($showJoinButton)
            @if($isOpenRecruitment)
                {{-- Open team: Green button for direct join --}}
                <button onclick="joinTeamDirect({{ $team->id }}, event)" class="btn btn-join">
                    <span class="btn-text">Join Team</span>
                    <span class="teams-loading-spinner" style="display: none;"></span>
                </button>
            @else
                {{-- Closed team: Purple button for request --}}
                <button onclick="requestToJoin({{ $team->id }}, event)" class="btn btn-request">
                    <span class="btn-text">Request to Join</span>
                    <span class="teams-loading-spinner" style="display: none;"></span>
                </button>
            @endif
        @elseif($isMember)
            <span class="team-card-member-badge">Member</span>
        @endif
    </div>
</div>
