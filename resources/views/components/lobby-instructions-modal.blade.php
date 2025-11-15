@props(['configuration'])

<div style="padding: 24px;">
    {{-- Modal Header --}}
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #efeff1; font-size: 20px; font-weight: 600;" x-text="configuration?.display_name || 'Lobby Instructions'"></h3>
        <button
            @click="showInstructions = false"
            type="button"
            style="width: 32px; height: 32px; border-radius: 6px; background-color: #3f3f46; color: #b3b3b5; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
            @mouseenter="$el.style.backgroundColor = '#52525b'; $el.style.color = '#efeff1'"
            @mouseleave="$el.style.backgroundColor = '#3f3f46'; $el.style.color = '#b3b3b5'"
        >
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
        </button>
    </div>

    {{-- Instructions Content --}}
    <div style="max-height: 60vh; overflow-y: auto; padding-right: 8px;">
        {{-- How to Create Section --}}
        <div style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="18" height="18" fill="white" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h4 style="margin: 0; color: #efeff1; font-size: 16px; font-weight: 600;">How to Create</h4>
            </div>
            <div
                style="background-color: #0e0e10; border-radius: 8px; padding: 16px; color: #b3b3b5; font-size: 14px; line-height: 1.8; border-left: 3px solid #667eea;"
                x-html="formatMarkdown(configuration?.instructions_how_to_create || 'No instructions available')"
            ></div>
        </div>

        {{-- How to Join Section --}}
        <div>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="18" height="18" fill="white" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <h4 style="margin: 0; color: #efeff1; font-size: 16px; font-weight: 600;">How to Join</h4>
            </div>
            <div
                style="background-color: #0e0e10; border-radius: 8px; padding: 16px; color: #b3b3b5; font-size: 14px; line-height: 1.8; border-left: 3px solid #10b981;"
                x-html="formatMarkdown(configuration?.instructions_how_to_join || 'No instructions available')"
            ></div>
        </div>

        {{-- Additional Info (if manual setup required) --}}
        <div x-show="configuration?.requires_manual_setup" x-cloak style="margin-top: 24px; background-color: #f59e0b1a; border-radius: 8px; padding: 16px; border-left: 3px solid #f59e0b;">
            <div style="display: flex; align-items: flex-start; gap: 10px;">
                <svg width="20" height="20" fill="#f59e0b" viewBox="0 0 20 20" style="flex-shrink: 0; margin-top: 2px;">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div>
                    <div style="color: #f59e0b; font-weight: 600; font-size: 14px; margin-bottom: 4px;">Manual Setup Required</div>
                    <div style="color: #b3b3b5; font-size: 13px;">This join method requires manual configuration in the game. Follow the instructions carefully.</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #3f3f46;">
        <button
            @click="showInstructions = false"
            type="button"
            class="btn btn-primary"
            style="width: 100%;"
        >
            Got It!
        </button>
    </div>
</div>

<script>
// Simple markdown-like formatting for instructions
function formatMarkdown(text) {
    if (!text) return '';

    return text
        // Bold: **text** → <strong>text</strong>
        .replace(/\*\*(.+?)\*\*/g, '<strong style="color: #efeff1;">$1</strong>')
        // Code: `text` → <code>text</code>
        .replace(/`(.+?)`/g, '<code style="background-color: #3f3f46; padding: 2px 6px; border-radius: 4px; color: #10b981; font-family: monospace; font-size: 13px;">$1</code>')
        // Line breaks
        .replace(/\n/g, '<br>')
        // Numbered lists: Match lines starting with a number followed by a dot
        .replace(/^(\d+)\.\s+(.+)$/gm, '<div style="margin-left: 20px; margin-bottom: 8px;"><span style="color: #667eea; font-weight: 600; margin-right: 8px;">$1.</span><span>$2</span></div>')
        // Unordered lists: Match lines starting with - or *
        .replace(/^[-*]\s+(.+)$/gm, '<div style="margin-left: 20px; margin-bottom: 8px;"><span style="color: #667eea; margin-right: 8px;">•</span><span>$1</span></div>');
}

// Make formatMarkdown available globally for Alpine.js
window.formatMarkdown = formatMarkdown;
</script>
