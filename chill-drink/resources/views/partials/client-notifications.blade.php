@auth
@if(! auth()->user()->isAdmin())
<script>
    (function () {
        const feedUrl = @json(route('notifications.feed'));
        const ordersIndexUrl = @json(route('orders.index'));

        function orderNotificationUrl(orderId) {
            if (!orderId) {
                return ordersIndexUrl;
            }

            const url = new URL(ordersIndexUrl, window.location.origin);
            url.searchParams.set('order', String(orderId));

            return url.toString();
        }

        if (!window.showRealtimeToast) {
            window.showRealtimeToast = function (message, type = 'info', url = null) {
                const containerId = 'realtimeToastContainer';
                let container = document.getElementById(containerId);

                if (!container) {
                    container = document.createElement('div');
                    container.id = containerId;
                    container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:10001;width:360px;max-width:calc(100vw - 40px);display:flex;flex-direction:column;gap:10px;';
                    document.body.appendChild(container);
                }

                const alert = document.createElement('div');
                const alertType = type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'primary');
                alert.className = `alert alert-${alertType} shadow-sm mb-0`;
                alert.style.borderRadius = '12px';

                if (url) {
                    alert.style.cursor = 'pointer';
                }

                alert.innerHTML = `
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-bell-fill mt-1"></i>
                        <div class="flex-grow-1">${message}</div>
                        <button type="button" class="btn-close" aria-label="Đóng"></button>
                    </div>
                `;

                alert.querySelector('.btn-close')?.addEventListener('click', (event) => {
                    event.stopPropagation();
                    alert.remove();
                });

                if (url) {
                    alert.addEventListener('click', () => {
                        window.location.href = url;
                    });
                }
                container.appendChild(alert);

                window.setTimeout(() => {
                    alert.style.transition = 'opacity .3s ease';
                    alert.style.opacity = '0';
                    window.setTimeout(() => alert.remove(), 300);
                }, 6000);
            };
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        const notificationIconMap = {
            'order_pending': '{{ \App\Support\OrderStatus::notificationIconByType('order_pending') }}',
            'order_in_progress': '{{ \App\Support\OrderStatus::notificationIconByType('order_in_progress') }}',
            'order_processing': '{{ \App\Support\OrderStatus::notificationIconByType('order_processing') }}',
            'order_shipper_accepted': '{{ \App\Support\OrderStatus::notificationIconByType('order_shipper_accepted') }}',
            'order_shipped': '{{ \App\Support\OrderStatus::notificationIconByType('order_shipped') }}',
            'order_arrived': '{{ \App\Support\OrderStatus::notificationIconByType('order_arrived') }}',
            'order_completed': '{{ \App\Support\OrderStatus::notificationIconByType('order_completed') }}',
            'order_delivered': '{{ \App\Support\OrderStatus::notificationIconByType('order_delivered') }}',
            'order_cancelled': '{{ \App\Support\OrderStatus::notificationIconByType('order_cancelled') }}'
        };

        function notificationIcon(type) {
            const iconClass = notificationIconMap[type] || 'bi-bell';
            return `<i class="${iconClass}"></i>`;
        }

        function renderNotifications(notifications) {
            const list = document.getElementById('clientNotificationList');
            if (!list) {
                return;
            }

            if (!notifications.length) {
                list.innerHTML = `
                    <div class="text-center py-4 text-secondary">
                        <i class="bi bi-bell-slash fs-2 d-block mb-2"></i>
                        <div style="font-size: 0.85rem;">Chưa có thông báo mới</div>
                    </div>
                `;
                return;
            }

            list.innerHTML = notifications.map((notification) => {
                const href = notification.url || orderNotificationUrl(notification.order_id);
                const orderAttr = notification.order_id ? ` data-order-id="${escapeHtml(notification.order_id)}"` : '';

                return `
                <a href="${escapeHtml(href)}"
                   class="notification-item is-clickable ${notification.read_at ? '' : 'unread'}"
                   data-notification-id="${escapeHtml(notification.id)}"${orderAttr}>
                    <span class="notification-icon">${notificationIcon(notification.type)}</span>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <div class="fw-semibold" style="font-size: 0.85rem;">${escapeHtml(notification.title)}</div>
                            ${notification.status_label ? `<span class="badge rounded-pill" style="background: var(--c-primary-light); color: var(--c-primary); font-size: 0.68rem;">${escapeHtml(notification.status_label)}</span>` : ''}
                        </div>
                        <div class="text-secondary" style="font-size: 0.8rem;">${escapeHtml(notification.message)}</div>
                        <div class="notification-time mt-1">${escapeHtml(notification.created_at || '')}</div>
                    </div>
                </a>
            `;
            }).join('');
        }

        function updateUnreadUi(unreadCount) {
            const dot = document.getElementById('clientNotificationDot');
            const badge = document.getElementById('clientNotificationBadge');
            const markAllBtn = document.getElementById('markAllReadBtn');

            if (dot) {
                if (unreadCount > 0) {
                    dot.classList.remove('d-none');
                } else {
                    dot.classList.add('d-none');
                }
            }

            if (badge) {
                if (unreadCount > 0) {
                    badge.textContent = `${unreadCount} mới`;
                    badge.classList.remove('d-none');
                } else {
                    badge.classList.add('d-none');
                }
            }

            // Show/hide mark all read button
            if (markAllBtn) {
                if (unreadCount > 0) {
                    markAllBtn.classList.remove('d-none');
                } else {
                    markAllBtn.classList.add('d-none');
                }
            }
        }

        let knownNotificationIds = new Set(
            Array.from(document.querySelectorAll('[data-notification-id]'))
                .map((element) => element.dataset.notificationId)
                .filter(Boolean)
        );
        let isFirstPoll = true;

        async function pollNotifications() {
            try {
                const response = await fetch(feedUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    return;
                }

                const data = await response.json();
                const notifications = Array.isArray(data.notifications) ? data.notifications : [];
                const unreadCount = Number(data.unread_count || 0);

                updateUnreadUi(unreadCount);
                renderNotifications(notifications);

                notifications.forEach((notification) => {
                    if (!knownNotificationIds.has(notification.id)) {
                        knownNotificationIds.add(notification.id);

                        if (!isFirstPoll && notification.message && typeof window.showRealtimeToast === 'function') {
                            const toastMessage = notification.title
                                ? `${notification.title}: ${notification.message}`
                                : notification.message;
                            window.showRealtimeToast(
                                toastMessage,
                                'info',
                                notification.url || orderNotificationUrl(notification.order_id)
                            );
                        }

                        document.dispatchEvent(new CustomEvent('order:status-updated', {
                            detail: {
                                order_id: notification.order_id,
                                status: notification.status,
                                status_label: notification.status_label,
                                message: notification.message,
                            },
                        }));
                    }
                });

                isFirstPoll = false;
            } catch (error) {
                console.warn('Không thể tải thông báo.', error);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            pollNotifications(); // Poll immediately on page load
            window.setInterval(pollNotifications, 5000);

            // Mark all as read handler
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', async function() {
                    try {
                        const response = await fetch(@json(route('notifications.mark-all-read')), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            credentials: 'same-origin',
                        });

                        if (response.ok) {
                            // Reload notifications to reflect changes
                            await pollNotifications();
                            
                            if (typeof window.showRealtimeToast === 'function') {
                                window.showRealtimeToast('Đã đánh dấu tất cả thông báo là đã đọc', 'success');
                            }
                        }
                    } catch (error) {
                        console.error('Không thể đánh dấu thông báo:', error);
                    }
                });
            }
        });
    })();
</script>
@endif
@endauth
