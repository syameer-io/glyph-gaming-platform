{{--
    Discord-style Search Modal Component
    Phase 3: Server Header & Navigation

    A centered search modal with:
    - Search input with icon
    - Filters (in, from, has, before/after)
    - Recent searches
    - Search results with context
    - Keyboard navigation

    @param Server $server - The current server
    @param Channel|null $channel - The current channel (optional)
--}}

@props([
    'server',
    'channel' => null,
])

<div
    x-data="searchModal()"
    x-show="isOpen"
    x-on:open-search-modal.window="open()"
    x-on:keydown.escape.window="close()"
    x-on:keydown.ctrl.k.window.prevent="toggle()"
    x-on:keydown.meta.k.window.prevent="toggle()"
    x-cloak
    class="search-modal-overlay"
    @click.self="close()"
>
    <div
        class="search-modal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95 -translate-y-4"
        x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
        x-transition:leave-end="opacity-0 transform scale-95 -translate-y-4"
        @click.stop
    >
        {{-- Search Header --}}
        <div class="search-modal-header">
            <div class="search-modal-input-wrapper">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    class="search-modal-input"
                    placeholder="Search messages in {{ $server->name }}"
                    x-model="query"
                    x-ref="searchInput"
                    @input.debounce.300ms="search()"
                    @keydown.enter.prevent="search()"
                    @keydown.down.prevent="selectNext()"
                    @keydown.up.prevent="selectPrev()"
                >
            </div>
        </div>

        {{-- Search Filters --}}
        <div class="search-filters">
            <button
                class="search-filter"
                :class="{ 'active': filters.channel }"
                @click="toggleFilter('channel')"
            >
                <span class="search-filter-label">in:</span>
                <span class="search-filter-value" x-text="filters.channel || 'channel'"></span>
            </button>
            <button
                class="search-filter"
                :class="{ 'active': filters.from }"
                @click="toggleFilter('from')"
            >
                <span class="search-filter-label">from:</span>
                <span class="search-filter-value" x-text="filters.from || 'user'"></span>
            </button>
            <button
                class="search-filter"
                :class="{ 'active': filters.has }"
                @click="toggleFilter('has')"
            >
                <span class="search-filter-label">has:</span>
                <span class="search-filter-value" x-text="filters.has || 'link, image, file'"></span>
            </button>
            <button
                class="search-filter"
                :class="{ 'active': filters.before }"
                @click="toggleFilter('before')"
            >
                <span class="search-filter-label">before:</span>
                <span class="search-filter-value" x-text="filters.before || 'date'"></span>
            </button>
            <button
                class="search-filter"
                :class="{ 'active': filters.after }"
                @click="toggleFilter('after')"
            >
                <span class="search-filter-label">after:</span>
                <span class="search-filter-value" x-text="filters.after || 'date'"></span>
            </button>
        </div>

        {{-- Search Results / Recent Searches --}}
        <div class="search-results">
            {{-- Loading State --}}
            <template x-if="loading">
                <div class="search-empty">
                    <svg class="animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    <div class="search-empty-text">Searching...</div>
                </div>
            </template>

            {{-- Results --}}
            <template x-if="!loading && results.length > 0">
                <div>
                    <template x-for="(result, index) in results" :key="result.id">
                        <div
                            class="search-result-item"
                            :class="{ 'bg-accent-primary/10': selectedIndex === index }"
                            @click="goToMessage(result)"
                            @mouseenter="selectedIndex = index"
                        >
                            <img :src="result.author.avatar_url" :alt="result.author.display_name" class="search-result-avatar">
                            <div class="search-result-content">
                                <div class="search-result-meta">
                                    <span class="search-result-author" x-text="result.author.display_name"></span>
                                    <span class="search-result-channel" x-text="'#' + result.channel.name"></span>
                                    <span class="search-result-time" x-text="formatDate(result.created_at)"></span>
                                </div>
                                <div class="search-result-text" x-html="highlightMatch(result.content, query)"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Empty State --}}
            <template x-if="!loading && query && results.length === 0 && searched">
                <div class="search-empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="search-empty-title">No results found</div>
                    <div class="search-empty-text">Try different keywords or filters</div>
                </div>
            </template>

            {{-- Recent Searches (when no query) --}}
            <template x-if="!loading && !query && recentSearches.length > 0">
                <div class="search-recent">
                    <div class="search-recent-header">
                        <span>Recent Searches</span>
                        <button class="search-recent-clear" @click="clearRecentSearches()">Clear</button>
                    </div>
                    <template x-for="(search, index) in recentSearches" :key="index">
                        <div
                            class="search-recent-item"
                            @click="query = search; search()"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="search-recent-text" x-text="search"></span>
                            <button class="search-recent-remove" @click.stop="removeRecentSearch(index)">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </template>

            {{-- Initial State --}}
            <template x-if="!loading && !query && recentSearches.length === 0">
                <div class="search-empty">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <div class="search-empty-title">Search messages</div>
                    <div class="search-empty-text">Use filters to narrow down your search</div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function searchModal() {
    return {
        isOpen: false,
        query: '',
        loading: false,
        searched: false,
        results: [],
        selectedIndex: 0,
        filters: {
            channel: null,
            from: null,
            has: null,
            before: null,
            after: null
        },
        recentSearches: JSON.parse(localStorage.getItem('recentSearches_{{ $server->id }}') || '[]'),

        open() {
            this.isOpen = true;
            this.$nextTick(() => {
                this.$refs.searchInput.focus();
            });
        },

        close() {
            this.isOpen = false;
            this.query = '';
            this.results = [];
            this.searched = false;
        },

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },

        async search() {
            if (!this.query.trim()) {
                this.results = [];
                this.searched = false;
                return;
            }

            this.loading = true;
            this.searched = true;

            try {
                const params = new URLSearchParams({
                    q: this.query,
                    ...(this.filters.channel && { channel: this.filters.channel }),
                    ...(this.filters.from && { from: this.filters.from }),
                    ...(this.filters.has && { has: this.filters.has }),
                    ...(this.filters.before && { before: this.filters.before }),
                    ...(this.filters.after && { after: this.filters.after })
                });

                const response = await fetch(`/api/servers/{{ $server->id }}/search?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                this.results = data.results || [];
                this.selectedIndex = 0;

                // Save to recent searches
                this.addToRecentSearches(this.query);
            } catch (error) {
                console.error('Search error:', error);
                this.results = [];
            } finally {
                this.loading = false;
            }
        },

        toggleFilter(filter) {
            // For now, just toggle the filter state
            // In a full implementation, this would open a picker
            if (this.filters[filter]) {
                this.filters[filter] = null;
            }
        },

        selectNext() {
            if (this.selectedIndex < this.results.length - 1) {
                this.selectedIndex++;
            }
        },

        selectPrev() {
            if (this.selectedIndex > 0) {
                this.selectedIndex--;
            }
        },

        goToMessage(result) {
            // Navigate to the message
            window.location.href = `/servers/{{ $server->id }}/channels/${result.channel.id}?message=${result.id}`;
        },

        formatDate(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));

            if (diffDays === 0) {
                return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
            } else if (diffDays === 1) {
                return 'Yesterday';
            } else if (diffDays < 7) {
                return date.toLocaleDateString('en-US', { weekday: 'short' });
            } else {
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            }
        },

        highlightMatch(text, query) {
            if (!query) return this.escapeHtml(text);
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return this.escapeHtml(text).replace(regex, '<mark>$1</mark>');
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        addToRecentSearches(query) {
            const searches = this.recentSearches.filter(s => s !== query);
            searches.unshift(query);
            this.recentSearches = searches.slice(0, 5);
            localStorage.setItem('recentSearches_{{ $server->id }}', JSON.stringify(this.recentSearches));
        },

        removeRecentSearch(index) {
            this.recentSearches.splice(index, 1);
            localStorage.setItem('recentSearches_{{ $server->id }}', JSON.stringify(this.recentSearches));
        },

        clearRecentSearches() {
            this.recentSearches = [];
            localStorage.removeItem('recentSearches_{{ $server->id }}');
        }
    };
}
</script>
