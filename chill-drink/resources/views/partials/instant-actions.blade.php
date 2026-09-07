<script>
(() => {
    if (window.__chillInstantActionsBooted) return;
    window.__chillInstantActionsBooted = true;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const notify = (message, type = 'success') => {
        if (!message) return;
        if (typeof window.showRealtimeToast === 'function') {
            window.showRealtimeToast(message, type);
            return;
        }
        const containerId = 'instantActionToastContainer';
        let container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:10001;width:360px;max-width:calc(100vw - 40px);display:flex;flex-direction:column;gap:10px;';
            document.body.appendChild(container);
        }
        const alert = document.createElement('div');
        alert.className = `alert alert-${type === 'error' ? 'danger' : type} shadow-sm mb-0`;
        alert.style.borderRadius = '12px';
        alert.textContent = message;
        container.appendChild(alert);
        window.setTimeout(() => {
            alert.style.transition = 'opacity .25s ease';
            alert.style.opacity = '0';
            window.setTimeout(() => alert.remove(), 260);
        }, 3500);
    };

    const boolFromPayload = (payload) => {
        if (typeof payload?.status === 'boolean') return payload.status;
        if (typeof payload?.is_active === 'boolean') return payload.is_active;
        if (typeof payload?.visible === 'boolean') return payload.visible;
        if (typeof payload?.review?.status === 'boolean') return payload.review.status;
        if (typeof payload?.user?.is_active === 'boolean') return payload.user.is_active;
        if (typeof payload?.branch?.status === 'boolean') return payload.branch.status;
        return null;
    };

    const updateToggleRow = (form, payload) => {
        if (!form.matches('[data-instant-toggle-status]')) return;
        const active = boolFromPayload(payload);
        if (active === null) return;

        const row = form.closest('[data-instant-row]') || form.closest('tr') || form.closest('.card');
        const badge = row?.querySelector('[data-instant-status-badge]');
        const button = form.querySelector('[data-instant-submit]') || form.querySelector('button[type="submit"]');
        const icon = button?.querySelector('[data-instant-status-icon]') || button?.querySelector('i');

        if (badge) {
            badge.textContent = active
                ? (form.dataset.activeLabel || 'Hoạt động')
                : (form.dataset.inactiveLabel || 'Đã khóa');
            if (form.dataset.activeBadgeClass && form.dataset.inactiveBadgeClass) {
                badge.className = active ? form.dataset.activeBadgeClass : form.dataset.inactiveBadgeClass;
            }
        }

        if (form.dataset.instantAdminId && window.CSS?.escape) {
            const tableBadge = document.querySelector(`tr[data-admin-id="${CSS.escape(form.dataset.instantAdminId)}"] [data-instant-status-badge]`);
            if (tableBadge) {
                tableBadge.textContent = active
                    ? (form.dataset.activeLabel || 'Hoạt động')
                    : (form.dataset.inactiveLabel || 'Đã khóa');
                if (form.dataset.activeBadgeClass && form.dataset.inactiveBadgeClass) {
                    tableBadge.className = active ? form.dataset.activeBadgeClass : form.dataset.inactiveBadgeClass;
                }
            }
        }

        if (button) {
            button.title = active
                ? (form.dataset.activeTitle || button.title)
                : (form.dataset.inactiveTitle || button.title);
            if (form.dataset.activeButtonClass && form.dataset.inactiveButtonClass) {
                button.className = active ? form.dataset.activeButtonClass : form.dataset.inactiveButtonClass;
            }
            const buttonLabel = active ? form.dataset.activeButtonLabel : form.dataset.inactiveButtonLabel;
            if (buttonLabel) {
                if (icon) {
                    button.replaceChildren(icon, document.createTextNode(' ' + buttonLabel));
                } else {
                    button.textContent = buttonLabel;
                }
            }
        }

        if (form.dataset.activeConfirm && form.dataset.inactiveConfirm) {
            form.dataset.confirm = active ? form.dataset.activeConfirm : form.dataset.inactiveConfirm;
        }

        if (icon && form.dataset.activeIcon && form.dataset.inactiveIcon) {
            icon.className = active ? form.dataset.activeIcon : form.dataset.inactiveIcon;
        }

        const hiddenStatus = form.querySelector("input[name='status'][type='hidden']");
        if (hiddenStatus && form.dataset.nextActiveValue && form.dataset.nextInactiveValue) {
            hiddenStatus.value = active ? form.dataset.nextInactiveValue : form.dataset.nextActiveValue;
        }
    };

    const updateReadAll = (form) => {
        if (!form.matches('[data-instant-mark-read]')) return;
        document.querySelectorAll('.notify-card.is-unread').forEach((card) => card.classList.remove('is-unread'));
        document.querySelectorAll('.notify-dot').forEach((dot) => dot.remove());
        document.querySelectorAll('[data-notify-unread-count]').forEach((node) => {
            node.textContent = '0';
            if (node.hasAttribute('data-notify-hide-when-zero')) node.hidden = true;
        });
        document.querySelectorAll('[data-notify-unread]').forEach((node) => {
            node.classList.remove('is-unread');
            node.removeAttribute('data-notify-unread');
        });
        document.querySelectorAll('.shipper-mobile-badge').forEach((node) => node.remove());
        form.remove();
    };

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-instant-form]');
        if (!form || form.dataset.instantBusy === '1') return;

        event.preventDefault();

        const submitter = event.submitter || form.querySelector('button[type="submit"]');
        const confirmMessage = form.dataset.confirm || submitter?.dataset.confirm;
        if (confirmMessage && !window.confirm(confirmMessage)) return;

        form.dataset.instantBusy = '1';
        submitter?.setAttribute('disabled', 'disabled');
        form.classList.add('is-submitting');

        try {
            const formData = new FormData(form);
            if (submitter?.name && !formData.has(submitter.name)) {
                formData.append(submitter.name, submitter.value || '');
            }

            const response = await fetch(form.action || window.location.href, {
                method: (form.method || 'POST').toUpperCase(),
                body: formData,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json') ? await response.json() : {};

            if (!response.ok || payload.success === false || payload.error) {
                notify(payload.message || payload.error || 'Thao tác chưa thực hiện được.', 'error');
                return;
            }

            updateToggleRow(form, payload);
            updateReadAll(form);
            if (form.dataset.removeTarget) {
                form.closest(form.dataset.removeTarget)?.remove();
            }

            notify(payload.message || form.dataset.successMessage || 'Đã cập nhật.', 'success');
            document.dispatchEvent(new CustomEvent('instant-form:success', { detail: { form, payload } }));
        } catch (error) {
            console.error('Instant form failed', error);
            notify('Có lỗi mạng, vui lòng thử lại.', 'error');
        } finally {
            delete form.dataset.instantBusy;
            submitter?.removeAttribute('disabled');
            form.classList.remove('is-submitting');
        }
    });
})();
</script>
