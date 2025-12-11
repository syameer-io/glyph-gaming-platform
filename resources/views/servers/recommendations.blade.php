@extends('layouts.app')

@section('title', 'Server Recommendations - Glyph')

@section('content')
<x-navbar active-section="servers" />

<main>
    <div class="container">
        <div style="margin-bottom: 32px;">
            <h1 style="margin-bottom: 8px;">🎮 Server Recommendations</h1>
            <p style="color: var(--color-text-secondary); font-size: 16px;">Discover gaming communities tailored to your Steam activity</p>
        </div>

        @if(!auth()->user()->steam_id)
            <div class="card" style="text-align: center; margin-bottom: 32px;">
                <h3 class="card-header">Link Your Steam Account</h3>
                <p style="color: var(--color-text-secondary); margin-bottom: 24px;">To receive personalized server recommendations, connect your Steam account to analyze your gaming preferences.</p>
                <a href="{{ route('steam.link') }}" class="btn btn-primary">Link Steam Account</a>
            </div>
        @elseif(!isset($recommendations) || $recommendations->isEmpty())
            <div class="card" style="text-align: center;">
                <h3 class="card-header">No Recommendations Available</h3>
                <p style="color: var(--color-text-secondary); margin-bottom: 24px;">We couldn't find any server recommendations for you at the moment. This might be because:</p>
                <ul style="color: var(--color-text-muted); text-align: left; margin-bottom: 24px;">
                    <li>Your Steam profile is private or has limited game data</li>
                    <li>There aren't many servers matching your gaming preferences yet</li>
                    <li>Your Steam data is still being analyzed</li>
                </ul>
                <div style="display: flex; gap: 12px; justify-content: center;">
                    <a href="{{ route('server.create') }}" class="btn btn-primary">Create a Server</a>
                    <a href="{{ route('server.join') }}" class="btn btn-secondary">Join by Invite</a>
                </div>
            </div>
        @else
            <div style="display: grid; gap: 24px;">
                @foreach($recommendations as $recommendation)
                    <div class="card" style="position: relative;">
                        <div style="position: absolute; top: 24px; right: 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 12px; border-radius: 8px; font-weight: 600; font-size: 14px;">
                            {{ number_format($recommendation['score'], 1) }}% Match
                        </div>

                        <div style="margin-bottom: 16px; padding-right: 120px;">
                            <h3 style="margin: 0 0 8px 0; color: var(--color-text-primary); font-weight: 600; font-size: 24px;">
                                <a href="{{ route('server.show', $recommendation['server']) }}" style="color: inherit; text-decoration: none;">
                                    {{ $recommendation['server']->name }}
                                </a>
                            </h3>
                            @if($recommendation['server']->description)
                                <p style="margin: 0; color: var(--color-text-secondary); font-size: 16px; line-height: 1.5;">{{ $recommendation['server']->description }}</p>
                            @endif
                        </div>

                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-bottom: 20px;">
                            <div>
                                @if(!empty($recommendation['reasons']))
                                    <h4 style="margin: 0 0 12px 0; color: var(--color-text-primary); font-size: 16px; font-weight: 600;">Why this recommendation?</h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px;">
                                        @foreach($recommendation['reasons'] as $reason)
                                            <span style="font-size: 13px; background-color: var(--color-bg-primary); color: var(--color-text-secondary); padding: 6px 12px; border-radius: 6px; border: 1px solid var(--color-border-primary);">{{ $reason }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                @if($recommendation['server']->tags && $recommendation['server']->tags->count() > 0)
                                    <h4 style="margin: 16px 0 12px 0; color: var(--color-text-primary); font-size: 16px; font-weight: 600;">Server Tags</h4>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        @foreach($recommendation['server']->tags as $tag)
                                            <span style="font-size: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 10px; border-radius: 4px; text-transform: uppercase; font-weight: 600;">
                                                {{ ucfirst(str_replace('_', ' ', $tag->tag_type)) }}: {{ ucfirst(str_replace('_', ' ', $tag->tag_value)) }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div style="background-color: var(--color-bg-primary); border-radius: 8px; padding: 20px;">
                                <h4 style="margin: 0 0 16px 0; color: var(--color-text-primary); font-size: 16px; font-weight: 600;">Server Stats</h4>
                                <div style="space-y: 12px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                        <span style="color: var(--color-text-secondary);">Members</span>
                                        <span style="color: var(--color-text-primary); font-weight: 600;">{{ $recommendation['server']->members()->count() }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                        <span style="color: var(--color-text-secondary);">Channels</span>
                                        <span style="color: var(--color-text-primary); font-weight: 600;">{{ $recommendation['server']->channels()->count() }}</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 12px;">
                                        <span style="color: var(--color-text-secondary);">Created</span>
                                        <span style="color: var(--color-text-primary); font-weight: 600;">{{ $recommendation['server']->created_at->diffForHumans() }}</span>
                                    </div>
                                    @if($recommendation['server']->tags->where('tag_type', 'region')->first())
                                        <div style="display: flex; justify-content: space-between;">
                                            <span style="color: var(--color-text-secondary);">Region</span>
                                            <span style="color: var(--color-text-primary); font-weight: 600;">{{ strtoupper($recommendation['server']->tags->where('tag_type', 'region')->first()->tag_value) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px; align-items: center;">
                            <a href="{{ route('server.show', $recommendation['server']) }}" class="btn btn-primary">View Server</a>
                            @if(!$recommendation['server']->members->contains(auth()->user()))
                                <form method="POST" action="{{ route('server.join.direct', $recommendation['server']) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary">Join Server</button>
                                </form>
                            @else
                                <span style="color: #10b981; font-weight: 600; padding: 8px 16px; background-color: rgba(16, 185, 129, 0.1); border-radius: 6px; font-size: 14px;">Already a member</span>
                            @endif
                            <div style="margin-left: auto; color: var(--color-text-muted); font-size: 14px;">
                                Based on your {{ implode(', ', $recommendation['reasons'] ?? []) }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($recommendations->count() >= 10)
                <div style="text-align: center; margin-top: 32px; padding: 24px; background-color: var(--color-surface); border-radius: 12px;">
                    <h4 style="margin: 0 0 12px 0; color: var(--color-text-primary);">Want more recommendations?</h4>
                    <p style="color: var(--color-text-secondary); margin-bottom: 16px;">Keep playing games on Steam to improve our recommendations for you!</p>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            @endif
        @endif
    </div>
</main>

<script>
// Handle direct join with AJAX for better UX
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('form[action*="join-direct"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const button = form.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            // Show loading state
            button.disabled = true;
            button.textContent = 'Joining...';
            
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success state
                    button.textContent = '✓ Joined!';
                    button.style.backgroundColor = '#10b981';
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000);
                } else {
                    // Show error and restore button
                    alert(data.message || 'Failed to join server');
                    button.disabled = false;
                    button.textContent = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while joining the server');
                button.disabled = false;
                button.textContent = originalText;
            });
        });
    });
});
</script>
@endsection