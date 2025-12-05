@props([
    'skillLevel' => null,
    'skillScore' => null,
    'breakdown' => null,
    'id' => 'skill-display',
])

@php
    $levelColors = [
        'expert' => '#10b981',
        'advanced' => '#667eea',
        'intermediate' => '#f59e0b',
        'beginner' => '#71717a',
        'unranked' => '#9ca3af',
    ];

    $levelIcons = [
        'expert' => '⭐',
        'advanced' => '🎯',
        'intermediate' => '📊',
        'beginner' => '🎮',
        'unranked' => '❓',
    ];

    $color = $levelColors[$skillLevel] ?? '#71717a';
    $icon = $levelIcons[$skillLevel] ?? '❓';
@endphp

<div id="{{ $id }}" class="skill-display-component" style="position: relative;">
    @if($skillLevel)
        <div style="
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background-color: {{ $color }}20;
            border: 1px solid {{ $color }}40;
            border-radius: 8px;
            cursor: help;
        "
        class="skill-badge"
        onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? 'block' : 'none'"
        title="Click to see breakdown">
            <span style="font-size: 18px;">{{ $icon }}</span>
            <div>
                <div style="
                    font-size: 14px;
                    font-weight: 600;
                    color: {{ $color }};
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                ">
                    {{ $skillLevel === 'unranked' ? 'Unranked' : ucfirst($skillLevel) }}
                </div>
                @if($skillScore !== null && $skillLevel !== 'unranked')
                    <div style="font-size: 11px; color: #b3b3b5;">
                        Score: {{ round($skillScore) }}/100
                    </div>
                @endif
            </div>

            <span style="margin-left: 4px; color: #71717a; font-size: 14px;">ℹ️</span>
        </div>

        {{-- Tooltip with breakdown --}}
        <div class="skill-tooltip" style="
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 8px;
            background-color: #18181b;
            border: 1px solid #3f3f46;
            border-radius: 8px;
            padding: 16px;
            min-width: 280px;
            z-index: 100;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
        ">
            @if($skillLevel !== 'unranked' && $breakdown)
                <div style="font-weight: 600; margin-bottom: 12px; color: #efeff1; font-size: 14px;">
                    Skill Calculation Breakdown
                </div>
                @foreach($breakdown as $key => $value)
                    {{-- Skip 'note' (handled separately) and 'weights' (rendered as a special section) --}}
                    @if($key !== 'note' && $key !== 'weights')
                        <div style="
                            display: flex;
                            justify-content: space-between;
                            padding: 6px 0;
                            border-bottom: 1px solid #27272a;
                            font-size: 13px;
                        ">
                            <span style="color: #a1a1aa;">
                                {{ ucwords(str_replace('_', ' ', $key)) }}
                            </span>
                            <span style="color: #efeff1; font-weight: 500;">
                                @if(str_contains($key, 'hours'))
                                    {{ number_format($value, 1) }} hrs
                                @elseif(str_contains($key, 'ratio'))
                                    {{ number_format($value, 2) }}
                                @elseif(is_numeric($value))
                                    {{ number_format($value, 1) }}%
                                @else
                                    {{ $value }}
                                @endif
                            </span>
                        </div>
                    @endif
                @endforeach

                {{-- Render weights as a formatted section if present --}}
                @if(isset($breakdown['weights']) && is_array($breakdown['weights']))
                    @php
                        $weightsFormatted = collect($breakdown['weights'])
                            ->map(fn($v, $k) => str_replace('_', ' ', $k) . ': ' . round($v * 100) . '%')
                            ->join(', ');
                    @endphp
                    <div style="
                        display: flex;
                        justify-content: space-between;
                        padding: 6px 0;
                        border-bottom: 1px solid #27272a;
                        font-size: 13px;
                    ">
                        <span style="color: #a1a1aa;">Weights</span>
                        <span style="color: #efeff1; font-weight: 500; font-size: 11px; text-align: right; max-width: 180px;">
                            {{ $weightsFormatted }}
                        </span>
                    </div>
                @endif

                @if(isset($breakdown['note']))
                    <div style="
                        margin-top: 12px;
                        padding: 10px;
                        background-color: #0e0e10;
                        border-radius: 6px;
                        font-size: 12px;
                        color: #71717a;
                    ">
                        ℹ️ {{ $breakdown['note'] }}
                    </div>
                @endif
            @else
                {{-- Unranked explanation --}}
                <div style="font-weight: 600; margin-bottom: 8px; color: #efeff1;">
                    Why Unranked?
                </div>
                <p style="font-size: 13px; color: #a1a1aa; margin: 0 0 8px 0;">
                    We couldn't find enough game data to calculate your skill level.
                </p>
                <ul style="font-size: 12px; color: #71717a; margin: 0; padding-left: 16px;">
                    <li>You haven't played this game yet</li>
                    <li>Your Steam profile is private</li>
                    <li>Less than 10 hours playtime</li>
                </ul>
                <p style="font-size: 12px; color: #71717a; margin: 12px 0 0 0;">
                    You can still queue - teams will see you as "Unranked".
                </p>
            @endif
        </div>
    @else
        <div style="color: #71717a; font-size: 14px; padding: 10px 0;">
            Select a game to see your skill level
        </div>
    @endif
</div>
