@if(auth()->check() && \App\Support\RealtimeOrderNotifier::isConfigured())
<script>
    window.realtimeConfig = {
        isAdmin: @json(auth()->user()->isAdmin()),
        userId: @json(auth()->id()),
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Echo || !window.realtimeConfig) {
            return;
        }

        if (window.realtimeConfig.isAdmin) {
            window.Echo.private('admin-notifications')
                .listen('.order.created', function (payload) {
                    if (payload.message) {
                        window.showRealtimeToast(payload.message, 'success');
                    }

                    document.dispatchEvent(new CustomEvent('order:created', { detail: payload }));
                });
        } else if (window.realtimeConfig.userId) {
            window.Echo.private('user.' + window.realtimeConfig.userId)
                .listen('.order.status.updated', function (payload) {
                    const toastMessage = payload.title && payload.message
                        ? `${payload.title}: ${payload.message}`
                        : (payload.message || payload.title);
                    if (toastMessage && typeof window.showRealtimeToast === 'function') {
                        window.showRealtimeToast(toastMessage, 'info', payload.url || null);
                    }

                    document.dispatchEvent(new CustomEvent('order:status-updated', { detail: payload }));
                });
        }
    });
</script>
@endif
