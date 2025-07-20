{{-- Gaming Status Real-time Synchronization (Phase 2) --}}
@auth
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.GamingStatusManager && window.Echo) {
        const userId = {{ auth()->id() }};
        const userServers = @json(auth()->user()->servers->pluck('id')->toArray());
        
        // Initialize gaming status manager
        window.GamingStatusManager.init(userId, userServers);
        
        console.log('Gaming status manager initialized for authenticated user');
    } else {
        console.warn('Gaming status manager or Echo not available');
    }
});
</script>
@endpush
@endauth