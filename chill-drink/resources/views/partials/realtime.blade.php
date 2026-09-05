@if(\App\Support\RealtimeOrderNotifier::isConfigured())
<script>
    @php
        $realtimeUser = auth()->user();
        $realtimeBranchIds = $realtimeUser?->isSuperAdmin()
            ? \App\Models\Branch::query()->where('status', true)->pluck('id')->map(fn ($id) => (int) $id)->all()
            : array_values(array_filter([(int) (session('nearest_branch_id') ?? $realtimeUser?->branch_id)]));
        $realtimeAdminChannels = $realtimeUser?->isSuperAdmin()
            ? array_merge(['admin-notifications'], array_map(fn ($branchId) => 'admin-notifications.'.$branchId, $realtimeBranchIds))
            : (($realtimeUser?->isAdmin() || $realtimeUser?->isStaffOnly()) && is_numeric($realtimeUser?->branch_id)
                ? ['admin-notifications.'.(int) $realtimeUser->branch_id]
                : []);
        $receivesBranchOrders = ($realtimeUser?->isAdmin() || $realtimeUser?->isSuperAdmin() || $realtimeUser?->isStaffOnly()) ?? false;
    @endphp
    window.realtimeConfig = {
        isAdmin: @json(($realtimeUser?->isAdmin() || $realtimeUser?->isSuperAdmin()) ?? false),
        receivesBranchOrders: @json($receivesBranchOrders),
        adminBranchId: @json($realtimeUser?->isAdmin() && is_numeric($realtimeUser?->branch_id) ? (int) $realtimeUser->branch_id : null),
        adminChannels: @json($realtimeAdminChannels),
        branchAdminOrderChannel: @json($realtimeUser?->isAdmin() && is_numeric($realtimeUser?->branch_id) ? 'admin-notifications.'.(int) $realtimeUser->branch_id : null),
        staffOrderChannel: @json($realtimeUser?->isStaffOnly() && is_numeric($realtimeUser?->branch_id) ? 'staff-orders.'.(int) $realtimeUser->branch_id : null),
        superAdminOrderChannel: @json($realtimeUser?->isSuperAdmin() ? 'super-admin-orders' : null),
        userId: @json(auth()->id()),
        branchId: @json(session('nearest_branch_id') ?? auth()->user()?->branch_id),
        branchIds: @json($realtimeBranchIds),
    };
    window.orderStatusRealtimeState = window.orderStatusRealtimeState || { recent: new Map() };
    window.dispatchOrderStatusUpdate = window.dispatchOrderStatusUpdate || function (payload, options = {}) {
        if (!payload || !payload.order_id || !payload.status) return false;

        const eventKey = `${payload.order_id}:${payload.status}`;
        const now = Date.now();
        const previousEventAt = window.orderStatusRealtimeState.recent.get(eventKey) || 0;
        if (now - previousEventAt < 15000) return false;

        window.orderStatusRealtimeState.recent.set(eventKey, now);
        window.setTimeout(() => window.orderStatusRealtimeState.recent.delete(eventKey), 16000);

        if (options.toast !== false && typeof window.showRealtimeToast === 'function') {
            const orderCode = payload.order_code || `#${payload.order_id}`;
            const statusLabel = payload.status_label || payload.status;
            window.showRealtimeToast(
                `Đơn hàng ${orderCode} đã được cập nhật: ${statusLabel}`,
                'info',
                payload.url || null
            );
        }

        document.dispatchEvent(new CustomEvent('order:status-updated', { detail: payload }));
        return true;
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Echo || !window.realtimeConfig) {
            return;
        }

        const applyAvailability = function (payload) {
            if (!payload || !window.realtimeConfig.branchIds.map(Number).includes(Number(payload.branch_id))) return;

            document.querySelectorAll(`[data-product-availability="${payload.product_id}"][data-branch-id="${payload.branch_id}"]`).forEach(function (container) {
                const badge = container.matches('[data-availability-badge]') ? container : container.querySelector('[data-availability-badge]');
                if (badge) {
                    badge.classList.remove('text-bg-success', 'text-bg-danger', 'text-bg-secondary');
                    badge.classList.add(payload.is_available ? 'text-bg-success' : 'text-bg-danger');
                    badge.textContent = badge.hasAttribute('data-availability-compact')
                        ? (payload.is_available ? 'Còn hàng' : 'Hết hàng')
                        : (payload.is_available
                            ? `Còn hàng tại Chi nhánh ${payload.branch_name || ''}`
                            : `Hết hàng tại Chi nhánh ${payload.branch_name || ''}`);
                }

                const actionButtons = Array.from(container.querySelectorAll('[data-product-action]'));
                if (container.matches('[data-product-action]')) actionButtons.push(container);

                actionButtons.forEach(function (button) {
                    button.disabled = !payload.is_available;
                    button.classList.toggle('disabled', !payload.is_available);
                    if (button.hasAttribute('data-availability-label')) {
                        button.textContent = payload.is_available ? 'Thêm vào giỏ' : 'Hết hàng';
                    }
                });

                const input = container.querySelector('[data-availability-input]');
                const toggle = container.querySelector('[data-availability-button]');
                if (input) input.value = payload.is_available ? '0' : '1';
                if (toggle) toggle.textContent = payload.is_available ? 'Chuyển hết hàng' : 'Chuyển còn hàng';
            });
        };

        document.addEventListener('product:availability-updated', function (event) {
            applyAvailability(event.detail);
        });

        window.realtimeConfig.branchIds.forEach(function (branchId) {
            window.Echo.channel('branch.' + branchId)
                .listen('.product.availability.updated', applyAvailability);
        });

        if (window.realtimeConfig.receivesBranchOrders) {
            window.realtimeConfig.adminChannels.forEach(function (adminChannel) {
                window.Echo.private(adminChannel)
                    .listen('.order.status.updated', function (payload) {
                        window.dispatchOrderStatusUpdate(payload, { toast: false });
                    });
            });

            const newOrderChannel = window.realtimeConfig.staffOrderChannel || window.realtimeConfig.superAdminOrderChannel;
            if (newOrderChannel) {
                window.Echo.private(newOrderChannel)
                    .listen('.order.created', function (payload) {
                        document.dispatchEvent(new CustomEvent('order:created', { detail: payload }));
                    });
            }

            // Keep branch-admin order tables/dashboard live without restoring the
            // new-order modal, whose Blade/JS is excluded for this role.
            if (window.realtimeConfig.branchAdminOrderChannel) {
                window.Echo.private(window.realtimeConfig.branchAdminOrderChannel)
                    .listen('.order.created', function (payload) {
                        document.dispatchEvent(new CustomEvent('order:created', { detail: payload }));
                    });
            }
        } else if (window.realtimeConfig.userId) {
            window.Echo.private('user.' + window.realtimeConfig.userId)
                .listen('.order.status.updated', function (payload) {
                    console.debug('[Order realtime] payload received', payload);
                    window.dispatchOrderStatusUpdate(payload);
                });
        }
    });
</script>
@endif
