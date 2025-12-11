@extends('layouts.app')

@section('title', 'Link Steam Account - Glyph')

@push('styles')
<style>
/* Steam Benefits Grid - 2 columns on desktop, 1 on mobile */
.steam-benefits-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
}

@media (min-width: 640px) {
    .steam-benefits-grid {
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
}

.steam-benefit-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.steam-benefit-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.steam-benefit-title {
    color: var(--color-text-primary);
    font-weight: 600;
    font-size: 14px;
    line-height: 1.3;
}

.steam-benefit-subtitle {
    color: var(--color-text-muted);
    font-size: 13px;
    line-height: 1.4;
    margin-top: 2px;
}
</style>
@endpush

@section('content')
<x-navbar />

<main>
    <div class="container">
        <div style="max-width: 600px; margin: 0 auto;">
            <div class="auth-box" style="text-align: center;">
                <h2 style="margin-bottom: 24px;">Link Your Steam Account</h2>
                
                <div style="margin-bottom: 32px;">
                    <div style="width: 100px; height: 100px; margin: 0 auto 24px; background-color: #1b2838; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                        <svg width="60" height="60" viewBox="0 0 256 259" fill="none">
                            <path d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0zM80.352 196.332l-15.749-6.568c2.787 5.867 7.621 10.775 14.033 13.47 13.857 5.83 29.836-.803 35.612-14.799a27.555 27.555 0 0 0 .046-21.035c-2.768-6.79-7.999-12.086-14.706-14.909-6.67-2.795-13.811-2.694-20.085-.304l16.275 6.79c10.222 4.3 15.056 16.145 10.794 26.461-4.253 10.314-15.998 15.195-26.22 10.894zm121.957-100.29c0-17.925-14.457-32.52-32.217-32.52-17.769 0-32.226 14.595-32.226 32.52 0 17.926 14.457 32.512 32.226 32.512 17.76 0 32.217-14.586 32.217-32.512zm-56.37-.055c0-13.488 10.84-24.42 24.2-24.42 13.368 0 24.208 10.932 24.208 24.42 0 13.488-10.84 24.421-24.209 24.421-13.359 0-24.2-10.933-24.2-24.42z" fill="#FFFFFF"/>
                        </svg>
                    </div>
                    
                    <h3 style="margin-bottom: 16px; color: var(--color-text-primary);">Why Link Your Steam Account?</h3>

                    <div style="text-align: left; background-color: var(--color-bg-primary); padding: 24px; border-radius: 12px; margin-bottom: 32px;">
                        <div class="steam-benefits-grid">
                            <!-- 1. Show Your Gaming Activity -->
                            <div class="steam-benefit-item">
                                <div class="steam-benefit-icon">
                                    <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="steam-benefit-title">Show Your Gaming Activity</div>
                                    <div class="steam-benefit-subtitle">Display what you're currently playing to friends in real-time</div>
                                </div>
                            </div>

                            <!-- 2. Game Library & Skill Badges -->
                            <div class="steam-benefit-item">
                                <div class="steam-benefit-icon">
                                    <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="steam-benefit-title">Game Library & Skill Badges</div>
                                    <div class="steam-benefit-subtitle">Showcase your Steam games with playtime and automatic skill levels</div>
                                </div>
                            </div>

                            <!-- 3. Personalized Recommendations -->
                            <div class="steam-benefit-item">
                                <div class="steam-benefit-icon">
                                    <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="steam-benefit-title">Personalized Recommendations</div>
                                    <div class="steam-benefit-subtitle">Get server and team suggestions based on your games</div>
                                </div>
                            </div>

                            <!-- 4. Smart Matchmaking -->
                            <div class="steam-benefit-item">
                                <div class="steam-benefit-icon">
                                    <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="steam-benefit-title">Smart Matchmaking</div>
                                    <div class="steam-benefit-subtitle">Find teammates matched to your skill level and playstyle</div>
                                </div>
                            </div>

                            <!-- 5. Achievement Leaderboards -->
                            <div class="steam-benefit-item">
                                <div class="steam-benefit-icon">
                                    <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="steam-benefit-title">Achievement Leaderboards</div>
                                    <div class="steam-benefit-subtitle">Compete on server leaderboards and track your progress</div>
                                </div>
                            </div>

                            <!-- 6. Multi-Game Lobbies -->
                            <div class="steam-benefit-item">
                                <div class="steam-benefit-icon">
                                    <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div>
                                    <div class="steam-benefit-title">Multi-Game Lobbies</div>
                                    <div class="steam-benefit-subtitle">Create and share game lobbies for CS2, Dota 2, and more</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('steam.auth') }}" class="btn btn-primary" style="background: #1b2838; padding: 16px 32px;">
                    <span style="display: flex; align-items: center; justify-content: center;">
                        <svg width="24" height="24" viewBox="0 0 256 259" fill="currentColor" style="margin-right: 12px;">
                            <path d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0z"/>
                        </svg>
                        Sign in through Steam
                    </span>
                </a>

                <p style="margin-top: 24px; color: var(--color-text-muted); font-size: 14px;">
                    Your Steam credentials are handled securely by Steam. We only receive your public profile information.
                </p>
            </div>
        </div>
    </div>
</main>
@endsection