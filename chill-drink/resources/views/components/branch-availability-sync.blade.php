<script>
document.addEventListener('DOMContentLoaded', () => {
    let signature = null;
    let busy = false;
    async function refreshBranches() {
        const select = document.getElementById('branch_id');
        if (document.hidden || busy || !select || select.disabled || !window.refreshCheckoutBranches) return;
        busy = true;
        try {
            const response = await fetch(@json(route('api.branches.availability')), {
                headers: { Accept: 'application/json' }, cache: 'no-store',
            });
            if (!response.ok) return;
            const payload = await response.json();
            const branches = Array.isArray(payload.data) ? payload.data : [];
            const next = JSON.stringify(branches);
            if (next !== signature) {
                await window.refreshCheckoutBranches(branches);
                signature = next;
            }
        } catch (error) {
            console.warn('Chưa cập nhật được danh sách chi nhánh.', error);
        } finally {
            busy = false;
        }
    }
    window.setInterval(refreshBranches, 5000);
    window.addEventListener('focus', refreshBranches);
    window.addEventListener('storage', event => {
        if (event.key === 'branch-availability-changed') refreshBranches();
    });
    document.addEventListener('visibilitychange', refreshBranches);
    if (window.Echo) {
        window.Echo.channel('branches').listen('BranchStatusUpdated', refreshBranches);
    }
    refreshBranches();
});
</script>
