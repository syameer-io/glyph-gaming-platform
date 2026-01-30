@props(['show' => false])

<div
    x-data="steamReminderModal({{ $show ? 'true' : 'false' }})"
    x-show="isOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="dismiss()"
    class="steam-reminder-overlay"
    @click.self="dismiss()"
>
    <div
        x-show="isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 transform scale-95 translate-y-4"
        class="steam-reminder-modal"
        @click.stop
    >
        <!-- Close Button -->
        <button @click="dismiss()" class="steam-reminder-close-btn">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>

        <!-- Steam Logo -->
        <div class="steam-reminder-header">
            <div class="steam-reminder-logo">
                <!-- Steam Logo SVG - Using detailed version from link page -->
                <svg width="48" height="48" viewBox="0 0 256 259" fill="none">
                    <path d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0zM80.352 196.332l-15.749-6.568c2.787 5.867 7.621 10.775 14.033 13.47 13.857 5.83 29.836-.803 35.612-14.799a27.555 27.555 0 0 0 .046-21.035c-2.768-6.79-7.999-12.086-14.706-14.909-6.67-2.795-13.811-2.694-20.085-.304l16.275 6.79c10.222 4.3 15.056 16.145 10.794 26.461-4.253 10.314-15.998 15.195-26.22 10.894zm121.957-100.29c0-17.925-14.457-32.52-32.217-32.52-17.769 0-32.226 14.595-32.226 32.52 0 17.926 14.457 32.512 32.226 32.512 17.76 0 32.217-14.586 32.217-32.512zm-56.37-.055c0-13.488 10.84-24.42 24.2-24.42 13.368 0 24.208 10.932 24.208 24.42 0 13.488-10.84 24.421-24.209 24.421-13.359 0-24.2-10.933-24.2-24.42z" fill="#FFFFFF"/>
                </svg>
            </div>
            <h2 class="steam-reminder-title">Link Your Steam Account</h2>
            <p class="steam-reminder-subtitle">Unlock the full Glyph experience</p>
        </div>

        <!-- Benefits List -->
        <div class="steam-reminder-benefits">
            <div class="steam-reminder-benefits-grid">
                <!-- 1. Show Your Gaming Activity -->
                <div class="steam-reminder-benefit">
                    <div class="steam-reminder-benefit-icon">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="steam-reminder-benefit-title">Gaming Activity</div>
                        <div class="steam-reminder-benefit-desc">Show what you're playing</div>
                    </div>
                </div>

                <!-- 2. Game Library & Skill Badges -->
                <div class="steam-reminder-benefit">
                    <div class="steam-reminder-benefit-icon">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="steam-reminder-benefit-title">Game Library</div>
                        <div class="steam-reminder-benefit-desc">Games & skill badges</div>
                    </div>
                </div>

                <!-- 3. Personalized Recommendations -->
                <div class="steam-reminder-benefit">
                    <div class="steam-reminder-benefit-icon">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="steam-reminder-benefit-title">Recommendations</div>
                        <div class="steam-reminder-benefit-desc">Personalized suggestions</div>
                    </div>
                </div>

                <!-- 4. Smart Matchmaking -->
                <div class="steam-reminder-benefit">
                    <div class="steam-reminder-benefit-icon">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="steam-reminder-benefit-title">Matchmaking</div>
                        <div class="steam-reminder-benefit-desc">Find skill-matched teams</div>
                    </div>
                </div>

                <!-- 5. Achievement Leaderboards -->
                <div class="steam-reminder-benefit">
                    <div class="steam-reminder-benefit-icon">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732l-3.354 1.935-1.18 4.455a1 1 0 01-1.933 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732l3.354-1.935 1.18-4.455A1 1 0 0112 2z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="steam-reminder-benefit-title">Leaderboards</div>
                        <div class="steam-reminder-benefit-desc">Track achievements</div>
                    </div>
                </div>

                <!-- 6. Multi-Game Lobbies -->
                <div class="steam-reminder-benefit">
                    <div class="steam-reminder-benefit-icon">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div class="steam-reminder-benefit-title">Game Lobbies</div>
                        <div class="steam-reminder-benefit-desc">CS2, Dota 2 & more</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="steam-reminder-actions">
            <a href="{{ route('steam.link') }}" class="btn btn-primary">
                <svg width="20" height="20" viewBox="0 0 256 259" fill="currentColor">
                    <path d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0z"/>
                </svg>
                Link Steam Account
            </a>
            <button @click="dismiss()" class="btn btn-secondary">
                Maybe Later
            </button>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
</style>

<script>
function steamReminderModal(shouldShow) {
    return {
        isOpen: false,

        init() {
            if (shouldShow) {
                // Delay showing by 1.5 seconds for better UX
                setTimeout(() => {
                    this.isOpen = true;
                }, 1500);
            }
        },

        dismiss() {
            this.isOpen = false;

            // Save dismissal to session via AJAX
            fetch('{{ route("steam.reminder.dismiss") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            }).catch(err => console.error('Failed to save dismissal:', err));
        }
    };
}
</script>
