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
        <button
            @click="dismiss()"
            style="position: absolute; top: 16px; right: 16px; background: none; border: none; color: #71717a; cursor: pointer; padding: 4px; transition: color 0.2s; display: flex; align-items: center; justify-content: center;"
            @mouseenter="$el.style.color = '#efeff1'"
            @mouseleave="$el.style.color = '#71717a'"
        >
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>

        <!-- Steam Logo -->
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 80px; height: 80px; margin: 0 auto 16px; background: linear-gradient(135deg, #1b2838 0%, #2a475e 100%); border-radius: 20px; display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);">
                <!-- Steam Logo SVG - Using detailed version from link page -->
                <svg width="48" height="48" viewBox="0 0 256 259" fill="none">
                    <path d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0zM80.352 196.332l-15.749-6.568c2.787 5.867 7.621 10.775 14.033 13.47 13.857 5.83 29.836-.803 35.612-14.799a27.555 27.555 0 0 0 .046-21.035c-2.768-6.79-7.999-12.086-14.706-14.909-6.67-2.795-13.811-2.694-20.085-.304l16.275 6.79c10.222 4.3 15.056 16.145 10.794 26.461-4.253 10.314-15.998 15.195-26.22 10.894zm121.957-100.29c0-17.925-14.457-32.52-32.217-32.52-17.769 0-32.226 14.595-32.226 32.52 0 17.926 14.457 32.512 32.226 32.512 17.76 0 32.217-14.586 32.217-32.512zm-56.37-.055c0-13.488 10.84-24.42 24.2-24.42 13.368 0 24.208 10.932 24.208 24.42 0 13.488-10.84 24.421-24.209 24.421-13.359 0-24.2-10.933-24.2-24.42z" fill="#FFFFFF"/>
                </svg>
            </div>
            <h2 style="color: #efeff1; font-size: 24px; font-weight: 700; margin: 0 0 8px;">Link Your Steam Account</h2>
            <p style="color: #b3b3b5; font-size: 14px; margin: 0;">Unlock the full Glyph experience</p>
        </div>

        <!-- Benefits List -->
        <div style="background-color: #0e0e10; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div style="color: #efeff1; font-weight: 600; font-size: 14px;">Show Your Gaming Activity</div>
                        <div style="color: #71717a; font-size: 13px;">Display what you're playing to friends</div>
                    </div>
                </div>

                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div style="color: #efeff1; font-weight: 600; font-size: 14px;">Personalized Recommendations</div>
                        <div style="color: #71717a; font-size: 13px;">Get server suggestions based on your games</div>
                    </div>
                </div>

                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div style="color: #efeff1; font-weight: 600; font-size: 14px;">Smart Team Matching</div>
                        <div style="color: #71717a; font-size: 13px;">Find teammates with similar skill levels</div>
                    </div>
                </div>

                <div style="display: flex; align-items: flex-start; gap: 12px;">
                    <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="16" height="16" fill="white" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div>
                        <div style="color: #efeff1; font-weight: 600; font-size: 14px;">Achievement Showcase</div>
                        <div style="color: #71717a; font-size: 13px;">Display your gaming achievements on your profile</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="{{ route('steam.link') }}" class="btn btn-primary" style="width: 100%; text-align: center; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;">
                <svg width="20" height="20" viewBox="0 0 256 259" fill="currentColor">
                    <path d="M127.779 0C60.42 0 5.24 52.412 0 119.014l68.724 28.674a35.812 35.812 0 0 1 20.426-6.366c.682 0 1.356.019 2.02.056l30.566-44.71v-.626c0-26.903 21.69-48.796 48.353-48.796 26.662 0 48.352 21.893 48.352 48.796 0 26.902-21.69 48.804-48.352 48.804-.37 0-.73-.009-1.098-.018l-43.593 31.377c.028.582.046 1.163.046 1.735 0 20.204-16.283 36.636-36.294 36.636-17.566 0-32.263-12.658-35.584-29.412L4.41 164.654c15.223 54.313 64.673 94.132 123.369 94.132 70.818 0 128.221-57.938 128.221-129.393C256 57.93 198.597 0 127.779 0z"/>
                </svg>
                Link Steam Account
            </a>
            <button
                @click="dismiss()"
                class="btn btn-secondary"
                style="width: 100%;"
            >
                Maybe Later
            </button>
        </div>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }

/* Steam Reminder Modal - Centered Overlay */
.steam-reminder-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Steam Reminder Modal - Content Box */
.steam-reminder-modal {
    background-color: #18181b;
    border-radius: 16px;
    padding: 32px;
    width: 100%;
    max-width: 440px;
    margin: 20px;
    border: 1px solid #3f3f46;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    position: relative;
}
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
