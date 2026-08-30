<script>
(() => {
    const badge = document.getElementById('sidebar-order-issue-badge');
    if (!badge) return;

    const feedUrl = @json(route(auth()->user()?->isCskh() ? 'admin.chat.order-issues.pending-count' : 'admin.order-issues.pending-count'));
    const issueUrl = @json(route(auth()->user()?->isCskh() ? 'admin.chat.order-issues.index' : 'admin.order-issues.index'));
    let previousCount = Number(@json((int) ($pendingOrderIssueCount ?? 0)));
    let latestId = null;
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    })[character]);

    const showIssueToast = (message) => {
        const safeMessage = escapeHtml(message);
        if (typeof window.showRealtimeToast === 'function') {
            window.showRealtimeToast(`<strong>Có khiếu nại mới</strong><br>${safeMessage}<br><a href="${issueUrl}" class="fw-bold">Mở yêu cầu hỗ trợ</a>`, 'warning');
            return;
        }

        const toast = document.createElement('a');
        toast.href = issueUrl;
        toast.className = 'alert alert-warning shadow position-fixed text-decoration-none text-dark';
        toast.style.cssText = 'right:20px;top:80px;z-index:10050;max-width:360px;border-radius:14px;';
        toast.innerHTML = `<strong><i class="bi bi-headset me-1"></i>Có khiếu nại mới</strong><div class="small mt-1">${safeMessage}</div>`;
        document.body.appendChild(toast);
        window.setTimeout(() => toast.remove(), 10000);
    };

    const refreshIssueBadge = async () => {
        try {
            const response = await fetch(feedUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!response.ok) return;
            const data = await response.json();
            const count = Number(data.count || 0);
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.style.display = count > 0 ? '' : 'none';

            if (count > previousCount && data.latest_id && data.latest_id !== latestId) {
                latestId = data.latest_id;
                showIssueToast(data.message || 'Có một yêu cầu hỗ trợ mới đang chờ xử lý.');
            }
            previousCount = count;
        } catch (_) {
            // Giữ nguyên badge hiện tại nếu mạng tạm thời gián đoạn.
        }
    };

    refreshIssueBadge();
    window.setInterval(() => {
        if (!document.hidden) refreshIssueBadge();
    }, 5000);
})();
</script>
