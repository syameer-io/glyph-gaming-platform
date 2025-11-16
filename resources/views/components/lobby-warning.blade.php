@props(['message', 'type' => 'warning'])

@php
    $styles = [
        'info' => [
            'bg' => '#3b82f61a',
            'border' => '#3b82f6',
            'icon' => '#3b82f6',
            'text' => '#60a5fa'
        ],
        'warning' => [
            'bg' => '#f59e0b1a',
            'border' => '#f59e0b',
            'icon' => '#f59e0b',
            'text' => '#fbbf24'
        ],
        'danger' => [
            'bg' => '#ef44441a',
            'border' => '#ef4444',
            'icon' => '#ef4444',
            'text' => '#f87171'
        ]
    ];

    $currentStyle = $styles[$type] ?? $styles['warning'];
@endphp

<div
    style="
        background-color: {{ $currentStyle['bg'] }};
        border-left: 3px solid {{ $currentStyle['border'] }};
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 16px;
    "
>
    <div style="display: flex; align-items: flex-start; gap: 10px;">
        {{-- Icon --}}
        <svg
            width="20"
            height="20"
            fill="{{ $currentStyle['icon'] }}"
            viewBox="0 0 20 20"
            style="flex-shrink: 0; margin-top: 2px;"
        >
            @if($type === 'info')
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            @elseif($type === 'danger')
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            @else
                {{-- Warning icon (default) --}}
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            @endif
        </svg>

        {{-- Message Content --}}
        <div style="flex: 1;">
            <p style="margin: 0; color: {{ $currentStyle['text'] }}; font-size: 14px; line-height: 1.6;">
                {!! $message !!}
            </p>
        </div>
    </div>
</div>
