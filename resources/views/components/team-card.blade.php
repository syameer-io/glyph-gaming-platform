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

    // Status indicator color
    $statusColor = match($team->status) {
        'recruiting' => '#10b981',
        'full' => '#f59e0b',
        'active' => '#667eea',
        default => '#9ca3af'
    };

    // Compatibility score color
    $compatColor = '#71717a'; // default gray
    if ($showCompatibility && $compatibilityScore !== null) {
        if ($compatibilityScore >= 80) {
            $compatColor = '#10b981'; // green
        } elseif ($compatibilityScore >= 60) {
            $compatColor = '#f59e0b'; // yellow
        } elseif ($compatibilityScore >= 40) {
            $compatColor = '#ef4444'; // orange/red
        }
    }

    // Check if user is a member (prevent errors if not authenticated)
    $isMember = false;
    if (auth()->check()) {
        $isMember = $team->activeMembers->contains('user_id', auth()->id());
    }

    // Show "Request to Join" button logic
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

<div class="team-card" style="
    background-color: var(--color-surface);
    border-radius: 12px;
    padding: 24px;
    border: 1px solid var(--color-border-primary);
    transition: all 0.2s ease;
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 16px;
"
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
    <!-- Team Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;">
        <div style="flex: 1; min-width: 0;">
            <h3 style="
                font-size: 20px;
                font-weight: 600;
                color: var(--color-text-primary);
                margin: 0 0 4px 0;
                line-height: 1.3;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            ">
                {{ $team->name }}
            </h3>

            <div style="
                font-size: 14px;
                color: var(--color-text-secondary);
                margin-bottom: 8px;
            ">
                {{ $team->game_name ?? 'Unknown Game' }}
            </div>

            <!-- Status Badge -->
            <div style="
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                background-color: {{ $statusColor }}20;
                color: {{ $statusColor }};
            ">
                <div style="
                    width: 6px;
                    height: 6px;
                    background-color: {{ $statusColor }};
                    border-radius: 50%;
                "></div>
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
        </div>

        <!-- Compatibility Score OR Team Stats -->
        @if($showCompatibility && $compatibilityScore !== null)
            <div style="
                text-align: center;
                padding: 12px 16px;
                background: var(--color-bg-primary);
                border-radius: 8px;
                border: 2px solid {{ $compatColor }};
                min-width: 80px;
            ">
                <div style="
                    font-size: 28px;
                    font-weight: 700;
                    color: {{ $compatColor }};
                    line-height: 1;
                    margin-bottom: 4px;
                ">
                    {{ round($compatibilityScore) }}%
                </div>
                <div style="
                    font-size: 10px;
                    color: var(--color-text-secondary);
                    text-transform: uppercase;
                    font-weight: 600;
                    letter-spacing: 0.5px;
                ">
                    Match
                </div>
            </div>
        @else
            <div style="text-align: right; min-width: 100px;">
                <div style="
                    font-size: 14px;
                    font-weight: 600;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                    margin-bottom: 4px;
                ">
                    {{ ucfirst($team->skill_level ?? 'Casual') }}
                </div>
                <div style="
                    font-size: 12px;
                    color: var(--color-text-secondary);
                ">
                    {{ $team->activeMembers->count() }}/{{ $team->max_size }} members
                </div>
            </div>
        @endif
    </div>

    <!-- Team Members Preview -->
    <div style="
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    ">
        @foreach($team->activeMembers->take(5) as $member)
            <img
                src="{{ $member->user->profile->avatar_url ?? asset('images/default-avatar.png') }}"
                alt="{{ $member->user->display_name }}"
                title="{{ $member->user->display_name }}{{ $member->game_role ? ' (' . ucfirst(str_replace('_', ' ', $member->game_role)) . ')' : '' }}"
                style="
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 2px solid var(--color-border-primary);
                    transition: all 0.2s ease;
                    cursor: pointer;
                "
                onmouseover="this.style.borderColor='#667eea'; this.style.transform='scale(1.1)';"
                onmouseout="this.style.borderColor='var(--color-border-primary)'; this.style.transform='scale(1)';"
            >
        @endforeach

        @for($i = $team->activeMembers->count(); $i < min($team->max_size, 5); $i++)
            <div style="
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background-color: var(--color-bg-primary);
                border: 2px dashed var(--color-border-primary);
                display: flex;
                align-items: center;
                justify-content: center;
                color: var(--color-text-muted);
                font-size: 14px;
                font-weight: 600;
            ">+</div>
        @endfor

        @if($team->activeMembers->count() > 5)
            <div style="
                display: flex;
                align-items: center;
                color: var(--color-text-secondary);
                font-size: 12px;
                margin-left: 4px;
            ">
                +{{ $team->activeMembers->count() - 5 }} more
            </div>
        @endif
    </div>

    <!-- Team Description -->
    @if($truncatedDescription)
        <div style="
            color: var(--color-text-secondary);
            font-size: 14px;
            line-height: 1.5;
        ">
            {{ $truncatedDescription }}
        </div>
    @endif

    <!-- Compatibility Details Breakdown (if available) -->
    @if($showCompatibility && $compatibilityDetails)
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 8px;
            padding: 12px;
            background-color: var(--color-bg-primary);
            border-radius: 8px;
        ">
            @foreach($compatibilityDetails as $key => $value)
                <div style="text-align: center;">
                    <div style="
                        font-size: 11px;
                        color: var(--color-text-muted);
                        text-transform: uppercase;
                        margin-bottom: 2px;
                    ">
                        {{ ucfirst($key) }}
                    </div>
                    <div style="
                        font-size: 16px;
                        font-weight: 600;
                        color: {{ $value >= 70 ? '#10b981' : ($value >= 50 ? '#f59e0b' : 'var(--color-text-muted)') }};
                    ">
                        {{ round($value) }}%
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Team Tags -->
    <div style="
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    ">
        {{-- Skill Level Tag (Primary) --}}
        @if($team->skill_level)
            <span style="
                font-size: 11px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 4px 8px;
                border-radius: 4px;
                text-transform: uppercase;
                font-weight: 500;
            ">
                {{ ucfirst($team->skill_level) }}
            </span>
        @endif

        {{-- Preferred Region Tag --}}
        @if($team->preferred_region)
            <span style="
                font-size: 11px;
                background-color: var(--color-surface-active);
                color: var(--color-text-secondary);
                padding: 4px 8px;
                border-radius: 4px;
                text-transform: uppercase;
                font-weight: 500;
            ">
                {{ ucfirst(str_replace('_', ' ', $team->preferred_region)) }}
            </span>
        @endif

        {{-- Required Roles Tags --}}
        @if(!empty($team->required_roles) && is_array($team->required_roles))
            @foreach($team->required_roles as $role)
                <span style="
                    font-size: 11px;
                    background-color: rgba(102, 126, 234, 0.2);
                    color: #8b9aef;
                    padding: 4px 8px;
                    border-radius: 4px;
                    text-transform: uppercase;
                    font-weight: 500;
                    border: 1px solid rgba(102, 126, 234, 0.3);
                " title="Looking for this role">
                    {{ ucfirst(str_replace('_', ' ', $role)) }}
                </span>
            @endforeach
        @endif

        {{-- Activity Times Tags --}}
        @if(!empty($team->activity_times) && is_array($team->activity_times))
            @foreach($team->activity_times as $time)
                <span style="
                    font-size: 11px;
                    background-color: rgba(245, 158, 11, 0.2);
                    color: #fbbf24;
                    padding: 4px 8px;
                    border-radius: 4px;
                    text-transform: uppercase;
                    font-weight: 500;
                    border: 1px solid rgba(245, 158, 11, 0.3);
                " title="Active during this time">
                    {{ ucfirst(str_replace('_', ' ', $time)) }}
                </span>
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
                <span style="
                    font-size: 11px;
                    background-color: rgba(16, 185, 129, 0.2);
                    color: #10b981;
                    padding: 4px 8px;
                    border-radius: 4px;
                    text-transform: uppercase;
                    font-weight: 500;
                    border: 1px solid rgba(16, 185, 129, 0.3);
                " title="Speaks this language">
                    {{ $languageMap[$langCode] ?? ucfirst($langCode) }}
                </span>
            @endforeach
        @endif

        {{-- Legacy: Single Activity Time from team_data (backward compatibility) --}}
        @if($team->activity_time && (empty($team->activity_times) || !is_array($team->activity_times)))
            <span style="
                font-size: 11px;
                background-color: rgba(245, 158, 11, 0.2);
                color: #fbbf24;
                padding: 4px 8px;
                border-radius: 4px;
                text-transform: uppercase;
                font-weight: 500;
                border: 1px solid rgba(245, 158, 11, 0.3);
            ">
                {{ ucfirst(str_replace('_', ' ', $team->activity_time)) }}
            </span>
        @endif

        {{-- Communication Required Tag --}}
        @if($team->communication_required)
            <span style="
                font-size: 11px;
                background-color: var(--color-surface-active);
                color: var(--color-text-secondary);
                padding: 4px 8px;
                border-radius: 4px;
                text-transform: uppercase;
                font-weight: 500;
            ">
                Voice Chat
            </span>
        @endif
    </div>

    <!-- Action Buttons -->
    <div style="
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: auto;
    ">
        <a
            href="{{ route('teams.show', $team) }}"
            class="btn btn-secondary btn-sm"
            style="
                flex: 1;
                padding: 8px 16px;
                background-color: var(--color-surface-active);
                color: var(--color-text-primary);
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 600;
                text-align: center;
                text-decoration: none;
                cursor: pointer;
                transition: all 0.2s ease;
            "
            onmouseover="this.style.backgroundColor='var(--color-text-faint)';"
            onmouseout="this.style.backgroundColor='var(--color-surface-active)';"
        >
            View Team
        </a>

        @if($showJoinButton)
            <button
                onclick="requestToJoin({{ $team->id }}, event)"
                class="btn btn-primary btn-sm"
                style="
                    flex: 1;
                    padding: 8px 16px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    border: none;
                    border-radius: 6px;
                    font-size: 14px;
                    font-weight: 600;
                    text-align: center;
                    cursor: pointer;
                    transition: all 0.2s ease;
                "
                onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.4)';"
                onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';"
            >
                <span class="btn-text">Request to Join</span>
                <span class="loading-spinner" style="display: none;">
                    <span style="
                        display: inline-block;
                        width: 14px;
                        height: 14px;
                        border: 2px solid rgba(255, 255, 255, 0.3);
                        border-radius: 50%;
                        border-top-color: white;
                        animation: spin 0.8s linear infinite;
                    "></span>
                </span>
            </button>
        @elseif($isMember)
            <span style="
                flex: 1;
                color: #10b981;
                font-size: 12px;
                font-weight: 600;
                padding: 8px 12px;
                background-color: rgba(16, 185, 129, 0.1);
                border-radius: 6px;
                text-align: center;
            ">
                ✓ Member
            </span>
        @endif
    </div>
</div>

<style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .team-card:hover {
        border-color: #667eea !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }
</style>
