import { createApp } from 'vue';

const formatRemaining = (closesAt) => {
    const seconds = Math.max(0, Math.ceil((closesAt - Date.now()) / 1000));
    return {
        seconds,
        text: `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`,
    };
};

const createVisibleInterval = (callback, delay, runImmediately = true) => {
    let intervalId = null;
    let stopped = false;

    const run = () => {
        if (!stopped && !document.hidden) callback();
    };
    const start = (immediate = runImmediately) => {
        if (stopped || document.hidden || intervalId !== null) return;
        if (immediate) run();
        intervalId = window.setInterval(run, delay);
    };
    const pause = () => {
        if (intervalId === null) return;
        window.clearInterval(intervalId);
        intervalId = null;
    };
    const handleVisibility = () => {
        if (document.hidden) pause();
        else start(true);
    };
    const stop = () => {
        if (stopped) return;
        stopped = true;
        pause();
        document.removeEventListener('visibilitychange', handleVisibility);
        window.removeEventListener('pagehide', stop);
    };

    document.addEventListener('visibilitychange', handleVisibility);
    window.addEventListener('pagehide', stop);
    start();

    return stop;
};

const groupBranchPicker = {
    props: {
        branches: { type: Array, default: () => [] },
        initialSelected: { type: String, default: '' },
    },
    data() {
        return {
            isOpen: false,
            selectedId: String(this.initialSelected || ''),
        };
    },
    computed: {
        selectedBranch() {
            return this.branches.find((branch) => String(branch.id) === this.selectedId) || null;
        },
    },
    mounted() {
        this.closeFromOutside = (event) => {
            if (!this.$el.contains(event.target)) this.isOpen = false;
        };
        document.addEventListener('click', this.closeFromOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.closeFromOutside);
    },
    methods: {
        selectBranch(branch) {
            this.selectedId = String(branch.id);
            this.isOpen = false;
        },
    },
    template: `
        <div class="group-branch-picker">
            <input type="hidden" name="branch_id" :value="selectedId">
            <button id="groupBranch" type="button" class="group-branch-trigger" :class="{ 'is-open': isOpen, 'has-value': selectedBranch }" data-group-branch-trigger @click.stop="isOpen = !isOpen">
                <span class="group-branch-trigger-content">
                    <span class="group-branch-trigger-icon"><i class="bi bi-geo-alt-fill"></i></span>
                    <span><strong>{{ selectedBranch ? selectedBranch.name : 'Chọn chi nhánh' }}</strong><small v-if="selectedBranch">{{ selectedBranch.address }}</small><small v-else>Chọn nơi chuẩn bị món</small></span>
                </span>
                <i class="bi bi-chevron-down"></i>
            </button>
            <div v-if="isOpen" class="group-branch-menu" @click.stop>
                <div class="group-branch-menu-head"><span><strong>Chi nhánh phục vụ</strong><small>{{ branches.length }} chi nhánh đang hoạt động</small></span><i class="bi bi-shop"></i></div>
                <div class="group-branch-list">
                    <button v-for="branch in branches" :key="branch.id" type="button" class="group-branch-option" :class="{ 'is-selected': String(branch.id) === selectedId }" @click="selectBranch(branch)">
                        <span class="group-branch-option-icon"><i class="bi bi-geo-alt"></i></span>
                        <span class="group-branch-option-copy"><strong>{{ branch.name }}</strong><small>{{ branch.address || 'Chưa cập nhật địa chỉ' }}</small></span>
                        <span class="group-branch-check"><i class="bi" :class="String(branch.id) === selectedId ? 'bi-check-circle-fill' : 'bi-circle'"></i></span>
                    </button>
                    <div v-if="!branches.length" class="group-branch-empty"><i class="bi bi-shop-window"></i><span>Chưa có chi nhánh đang hoạt động.</span></div>
                </div>
            </div>
        </div>`,
};

const groupDateTimePicker = {
    props: {
        initialValue: { type: String, required: true },
        minValue: { type: String, required: true },
        maxValue: { type: String, required: true },
    },
    data() {
        const [datePart, timePart = '00:00'] = this.initialValue.split('T');
        const [year, month] = datePart.split('-').map(Number);
        const [hour, minute] = timePart.slice(0, 5).split(':');
        return {
            isOpen: false,
            selectedDate: datePart,
            selectedHour: hour || '00',
            selectedMinute: minute || '00',
            viewYear: year,
            viewMonth: month - 1,
        };
    },
    computed: {
        minDate() { return this.minValue.slice(0, 10); },
        maxDate() { return this.maxValue.slice(0, 10); },
        serialized() { return `${this.selectedDate}T${this.selectedHour}:${this.selectedMinute}`; },
        displayValue() {
            const value = new Date(`${this.serialized}:00`);
            return new Intl.DateTimeFormat('vi-VN', {
                weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', hour12: false,
            }).format(value);
        },
        monthLabel() {
            return new Intl.DateTimeFormat('vi-VN', { month: 'long', year: 'numeric' })
                .format(new Date(this.viewYear, this.viewMonth, 1));
        },
        calendarDays() {
            const firstWeekday = (new Date(this.viewYear, this.viewMonth, 1).getDay() + 6) % 7;
            const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const result = Array.from({ length: firstWeekday }, (_, index) => ({ key: `blank-${index}`, blank: true }));
            for (let day = 1; day <= daysInMonth; day += 1) {
                const key = `${this.viewYear}-${String(this.viewMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                result.push({ key, day, disabled: key < this.minDate || key > this.maxDate });
            }
            while (result.length % 7 !== 0) result.push({ key: `blank-end-${result.length}`, blank: true });
            return result;
        },
        hours() { return Array.from({ length: 24 }, (_, index) => String(index).padStart(2, '0')); },
        minutes() { return Array.from({ length: 60 }, (_, index) => String(index).padStart(2, '0')); },
    },
    mounted() {
        this.closeFromOutside = (event) => {
            if (!this.$el.contains(event.target)) this.isOpen = false;
        };
        document.addEventListener('click', this.closeFromOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.closeFromOutside);
    },
    methods: {
        selectDay(day) {
            if (day.blank || day.disabled) return;
            this.selectedDate = day.key;
        },
        changeMonth(offset) {
            const next = new Date(this.viewYear, this.viewMonth + offset, 1);
            const nextKey = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`;
            if (nextKey < this.minDate.slice(0, 7) || nextKey > this.maxDate.slice(0, 7)) return;
            this.viewYear = next.getFullYear();
            this.viewMonth = next.getMonth();
        },
        chooseToday() {
            const today = new Date();
            const key = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
            this.selectedDate = key < this.minDate ? this.minDate : (key > this.maxDate ? this.maxDate : key);
            const [year, month] = this.selectedDate.split('-').map(Number);
            this.viewYear = year;
            this.viewMonth = month - 1;
        },
    },
    template: `
        <div class="group-datetime">
            <input type="hidden" name="closes_at" :value="serialized">
            <button id="groupClosesAt" type="button" class="group-datetime-trigger" :class="{ 'is-open': isOpen }" @click.stop="isOpen = !isOpen">
                <span><i class="bi bi-clock"></i>{{ displayValue }}</span><i class="bi bi-chevron-down"></i>
            </button>
            <div v-if="isOpen" class="group-datetime-popover" @click.stop>
                <div class="group-datetime-calendar-head">
                    <button type="button" @click="changeMonth(-1)" aria-label="Tháng trước"><i class="bi bi-chevron-left"></i></button>
                    <strong>{{ monthLabel }}</strong>
                    <button type="button" @click="changeMonth(1)" aria-label="Tháng sau"><i class="bi bi-chevron-right"></i></button>
                </div>
                <div class="group-datetime-weekdays"><span v-for="label in ['T2','T3','T4','T5','T6','T7','CN']" :key="label">{{ label }}</span></div>
                <div class="group-datetime-days">
                    <template v-for="day in calendarDays" :key="day.key">
                        <span v-if="day.blank"></span>
                        <button v-else type="button" :disabled="day.disabled" :class="{ 'is-selected': selectedDate === day.key }" @click="selectDay(day)">{{ day.day }}</button>
                    </template>
                </div>
                <div class="group-datetime-time">
                    <div><label>Giờ</label><select v-model="selectedHour"><option v-for="hour in hours" :key="hour" :value="hour">{{ hour }}</option></select></div>
                    <span>:</span>
                    <div><label>Phút</label><select v-model="selectedMinute"><option v-for="minute in minutes" :key="minute" :value="minute">{{ minute }}</option></select></div>
                </div>
                <div class="group-datetime-footer"><button type="button" class="group-datetime-today" @click="chooseToday">Hôm nay</button><button type="button" class="group-datetime-done" @click="isOpen = false"><i class="bi bi-check2 me-1"></i>Xong</button></div>
            </div>
        </div>`,
};

const groupOrderCreate = {
    props: {
        rootElement: { type: Object, required: true },
    },
    mounted() {
        this.form = this.rootElement.querySelector('[data-group-create-form]');
        this.errorBox = this.rootElement.querySelector('[data-group-create-errors]');
        this.submitButton = this.rootElement.querySelector('[data-group-create-submit]');
        this.submitLabel = this.rootElement.querySelector('[data-group-create-submit-label]');
        this.handleSubmit = this.submit.bind(this);
        this.form?.addEventListener('submit', this.handleSubmit);
    },
    beforeUnmount() {
        this.form?.removeEventListener('submit', this.handleSubmit);
    },
    methods: {
        showErrors(messages) {
            if (!this.errorBox) return;
            const safeMessages = messages.filter(Boolean);
            this.errorBox.textContent = safeMessages.join(' ');
            this.errorBox.classList.toggle('d-none', safeMessages.length === 0);
            if (safeMessages.length) this.errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        },
        async submit(event) {
            event.preventDefault();
            if (!this.form || !this.form.reportValidity()) return;

            const branchInput = this.form.querySelector('input[name="branch_id"]');
            if (!branchInput?.value) {
                this.showErrors(['Vui lòng chọn chi nhánh phục vụ đơn nhóm.']);
                this.form.querySelector('[data-group-branch-trigger]')?.focus();
                return;
            }

            this.showErrors([]);
            if (this.submitButton) this.submitButton.disabled = true;
            if (this.submitLabel) this.submitLabel.textContent = 'Đang tạo phòng...';

            try {
                const response = await fetch(this.form.action, {
                    method: 'POST',
                    body: new FormData(this.form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validationErrors = Object.values(data.errors || {}).flat();
                    this.showErrors(validationErrors.length ? validationErrors : [data.message || 'Không thể tạo phòng. Vui lòng thử lại.']);
                    return;
                }

                if (!data.redirect_url) {
                    this.showErrors(['Đã tạo phòng nhưng không tìm thấy đường dẫn phòng.']);
                    return;
                }

                window.location.assign(data.redirect_url);
            } catch (error) {
                this.showErrors(['Kết nối bị gián đoạn. Vui lòng thử lại.']);
            } finally {
                if (this.submitButton) this.submitButton.disabled = false;
                if (this.submitLabel) this.submitLabel.textContent = 'Tạo phòng đặt hàng';
            }
        },
    },
};

const groupOrderIndex = {
    props: {
        rootElement: { type: Object, required: true },
    },
    mounted() {
        this.root = this.rootElement;
        this.stopTimers = [];
        this.root.querySelectorAll('[data-group-countdown]').forEach((countdown) => {
            const closesAt = new Date(countdown.dataset.closesAt).getTime();
            const time = countdown.querySelector('[data-countdown-time]');
            const card = countdown.closest('[data-group-card]');
            let finished = false;

            const tick = () => {
                const remaining = formatRemaining(closesAt);
                time.textContent = remaining.text;
                countdown.classList.toggle('is-urgent', remaining.seconds > 0 && remaining.seconds <= 60);

                if (remaining.seconds === 0 && !finished) {
                    finished = true;
                    countdown.classList.remove('is-urgent');
                    countdown.classList.add('is-finished');
                    countdown.querySelector('span:not([data-countdown-time])').textContent = 'Đã hết giờ';
                    card?.classList.add('has-just-expired');
                    const status = card?.querySelector('[data-group-status]');
                    status?.classList.remove('is-open');
                    status?.classList.add('is-closed');
                    const statusText = card?.querySelector('[data-group-status-text]');
                    if (statusText) statusText.textContent = 'Đã đóng';
                    window.setTimeout(() => window.location.reload(), 1500);
                }
            };

            this.stopTimers.push(createVisibleInterval(tick, 1000));
        });
    },
    beforeUnmount() {
        this.stopTimers?.forEach((stop) => stop());
    },
};

const groupOrderRoom = {
    props: {
        rootElement: { type: Object, required: true },
    },
    mounted() {
        this.root = this.rootElement;
        this.stopTimers = [];
        this.abortController = new AbortController();
        this.isRefreshing = false;
        this.isCheckingState = false;
        this.isMutating = false;
        this.roomFingerprint = null;
        this.suppressLeaveBeacon = false;
        const signal = this.abortController.signal;

        this.root.addEventListener('submit', (event) => {
            if (!event.target.closest('form[data-group-async-action]')) {
                this.suppressLeaveBeacon = true;
            }
        }, { signal, capture: true });
        this.root.addEventListener('submit', (event) => this.submitAsync(event), { signal });

        const copyButton = this.root.querySelector('[data-copy-group-link]');
        copyButton?.addEventListener('click', () => this.copyLink(copyButton), { signal });

        this.setupProductPicker(signal);
        this.setupCountdowns();
        this.setupPresence(signal);
        this.setupLiveRoom(signal);
    },
    beforeUnmount() {
        this.abortController?.abort();
        this.stopTimers?.forEach((stop) => stop());
    },
    methods: {
        showMessage(message, isError = false) {
            document.querySelector('.group-live-toast')?.remove();
            const toast = document.createElement('div');
            toast.className = `group-live-toast${isError ? ' is-error' : ''}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            window.setTimeout(() => toast.remove(), 2600);
        },
        async submitAsync(event) {
            const form = event.target.closest('form[data-group-async-action]');
            if (!form || event.defaultPrevented) return;
            event.preventDefault();

            const submitter = event.submitter || form.querySelector('button[type="submit"], button:not([type])');
            if (submitter) submitter.disabled = true;
            this.isMutating = true;

            try {
                const response = await fetch(form.action, {
                    method: form.method,
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                const error = page.querySelector('.alert-danger');
                if (!response.ok || error) {
                    this.showMessage(error?.textContent.trim() || 'Không thể cập nhật món. Vui lòng thử lại.', true);
                    return;
                }

                const isJoining = form.hasAttribute('data-group-join');
                if (isJoining) {
                    // Nạp lại layout để người vừa nhận link có cùng host chat
                    // nhóm + chi nhánh như chủ phòng ngay sau khi tham gia.
                    window.location.reload();
                    return;
                }
                this.replaceLiveSections(page, isJoining);
                await this.captureRoomFingerprint();
                this.showMessage(page.querySelector('.alert-success')?.textContent.trim() || 'Đã cập nhật đơn nhóm.');
            } catch {
                this.showMessage('Kết nối bị gián đoạn. Vui lòng thử lại.', true);
            } finally {
                this.isMutating = false;
                if (submitter?.isConnected) submitter.disabled = false;
            }
        },
        replaceLiveSections(page, includeParticipation = false) {
            const selectors = ['[data-group-order-heading]', '[data-group-members]', '[data-group-summary]'];
            if (includeParticipation) selectors.unshift('[data-group-participation]');
            selectors.forEach((selector) => {
                const current = this.root.querySelector(selector);
                const updated = page.querySelector(selector);
                if (current && updated && current.outerHTML !== updated.outerHTML) current.replaceWith(updated);
            });
        },
        async captureRoomFingerprint() {
            const stateUrl = this.root.dataset.stateUrl;
            if (!stateUrl) return;
            try {
                const response = await fetch(stateUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    signal: this.abortController.signal,
                });
                if (response.ok) this.roomFingerprint = (await response.json()).fingerprint || this.roomFingerprint;
            } catch (error) {
                if (error.name !== 'AbortError') return;
            }
        },
        setupLiveRoom(signal) {
            const refresh = async () => {
                if (document.hidden || this.isRefreshing || this.isMutating) return false;
                this.isRefreshing = true;
                try {
                    const response = await fetch(window.location.href, {
                        headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal,
                    });
                    // Không điều hướng theo redirect của request nền: trong môi trường
                    // chạy dưới thư mục con, middleware có thể trả về URL dashboard
                    // dù trang phòng hiện tại vẫn hợp lệ.
                    if (response.redirected) return false;
                    if (!response.ok) return false;
                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    this.replaceLiveSections(page, false);
                    return true;
                } catch (error) {
                    return false;
                } finally {
                    this.isRefreshing = false;
                }
            };

            const groupId = Number(this.root.dataset.groupId || 0);
            if (window.Echo && groupId > 0) {
                const roomChannel = window.Echo.private('group-order.' + groupId);
                roomChannel.listen('.group-order.updated', () => {
                    refresh().then((updated) => {
                        if (updated) this.captureRoomFingerprint();
                    });
                });
                signal.addEventListener('abort', () => {
                    roomChannel.stopListening('.group-order.updated');
                }, { once: true });
            }

            const checkForChanges = async () => {
                if (document.hidden || this.isRefreshing || this.isCheckingState || this.isMutating) return;
                const stateUrl = this.root.dataset.stateUrl;
                if (!stateUrl) {
                    await refresh();
                    return;
                }

                this.isCheckingState = true;
                try {
                    const response = await fetch(stateUrl, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store',
                        signal,
                    });
                    if (!response.ok) return;
                    const fingerprint = (await response.json()).fingerprint;
                    if (!fingerprint) return;
                    if (this.roomFingerprint === null) {
                        this.roomFingerprint = fingerprint;
                        return;
                    }
                    if (fingerprint !== this.roomFingerprint) {
                        if (await refresh()) this.roomFingerprint = fingerprint;
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') return;
                } finally {
                    this.isCheckingState = false;
                }
            };

            this.captureRoomFingerprint();
            // WebSocket là đường chính; polling 2,5 giây chỉ là dự phòng khi
            // Reverb bị ngắt, tránh hai cửa sổ cùng dồn request HTML vào PHP.
            this.stopTimers.push(createVisibleInterval(checkForChanges, 2500, false));
        },
        async copyLink(button) {
            const input = this.root.querySelector('#groupShareUrl');
            try {
                await navigator.clipboard.writeText(input.value);
            } catch {
                input.select();
                document.execCommand('copy');
            }
            button.innerHTML = '<i class="bi bi-check2 me-1"></i>Đã sao chép';
            window.setTimeout(() => { button.innerHTML = '<i class="bi bi-copy me-1"></i>Sao chép'; }, 1800);
        },
        setupProductPicker(signal) {
            const picker = this.root.querySelector('[data-product-picker]');
            const search = this.root.querySelector('[data-product-search]');
            const value = this.root.querySelector('[data-product-value]');
            if (!picker || !search || !value) return;

            const options = Array.from(picker.querySelectorAll('[data-product-option]'));
            const empty = picker.querySelector('[data-product-empty]');
            const labels = Array.from(this.root.querySelectorAll('[data-topping-id]'));
            const noToppings = this.root.querySelector('[data-no-toppings]');
            const chooseProduct = this.root.querySelector('[data-choose-product-for-toppings]');
            const help = this.root.querySelector('[data-topping-help]');
            const normalize = (text) => text.toLocaleLowerCase('vi').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/đ/g, 'd');
            const open = () => picker.classList.add('is-open');
            const showToppings = (option) => {
                const ids = new Set((option?.dataset.toppings || '').split(',').filter(Boolean).map(Number));
                chooseProduct?.classList.toggle('d-none', Boolean(option));
                if (help) help.textContent = option ? 'Chỉ hiển thị món thêm dùng được với đồ uống đã chọn.' : 'Hãy chọn đồ uống trước để xem món thêm phù hợp.';
                labels.forEach((label) => {
                    const allowed = ids.has(Number(label.dataset.toppingId));
                    label.classList.toggle('d-none', !allowed);
                    if (!allowed) label.querySelector('input').checked = false;
                });
                noToppings?.classList.toggle('d-none', !option || ids.size > 0);
            };

            search.addEventListener('focus', open, { signal });
            search.addEventListener('click', open, { signal });
            search.addEventListener('input', () => {
                const keyword = normalize(search.value.trim());
                value.value = '';
                showToppings(null);
                search.setCustomValidity('Vui lòng chọn một đồ uống trong danh sách.');
                const matches = options.filter((option) => normalize(option.dataset.search).includes(keyword));
                options.forEach((option) => { option.hidden = !matches.includes(option); });
                empty?.classList.toggle('is-visible', matches.length === 0);
                open();
            }, { signal });
            options.forEach((option) => option.addEventListener('click', () => {
                value.value = option.dataset.value;
                search.value = option.dataset.name;
                search.setCustomValidity('');
                search.classList.remove('is-invalid');
                picker.classList.remove('is-open');
                showToppings(option);
            }, { signal }));
            document.addEventListener('click', (event) => {
                if (!picker.contains(event.target)) picker.classList.remove('is-open');
            }, { signal });
            search.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') picker.classList.remove('is-open');
            }, { signal });
            search.closest('form')?.addEventListener('submit', (event) => {
                if (value.value) return;
                event.preventDefault();
                search.classList.add('is-invalid');
                search.setCustomValidity('Vui lòng tìm và chọn một đồ uống trong danh sách.');
                search.reportValidity();
                open();
            }, { signal });
        },
        setupCountdowns() {
            const countdown = this.root.querySelector('[data-group-countdown]');
            if (countdown) {
                const closesAt = new Date(countdown.dataset.closesAt).getTime();
                const tick = () => {
                    const remaining = formatRemaining(closesAt);
                    countdown.textContent = remaining.text;
                    if (remaining.seconds === 0) window.location.reload();
                };
                this.stopTimers.push(createVisibleInterval(tick, 1000));
            }
        },
        setupPresence(signal) {
            const presenceUrl = this.root.dataset.presenceUrl;
            const leaveUrl = this.root.dataset.leaveUrl;
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!presenceUrl || !csrf) return;

            let reloading = false;
            const sync = async () => {
                if (document.hidden) return;
                try {
                    const response = await fetch(presenceUrl, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        signal,
                    });
                    if (!response.ok) return;
                    const state = await response.json();
                    if (state.redirect_url) {
                        window.location.assign(state.redirect_url);
                        return;
                    }
                    if (!state.is_open && !reloading) {
                        reloading = true;
                        window.location.reload();
                    }
                } catch (error) {
                    if (error.name !== 'AbortError') return;
                }
            };
            this.stopTimers.push(createVisibleInterval(sync, 3000));
            const reportOwnerLeft = () => {
                if (!leaveUrl || this.suppressLeaveBeacon) return;
                const data = new FormData();
                data.append('_token', csrf);
                navigator.sendBeacon(leaveUrl, data);
            };
            window.addEventListener('pagehide', reportOwnerLeft, { signal });
        },
    },
};

const groupOrderChat = {
    props: {
        messagesUrl: { type: String, required: true },
        sendUrl: { type: String, required: true },
        readUrl: { type: String, required: true },
        groupId: { type: Number, required: true },
        memberId: { type: Number, required: true },
        groupIsOpen: { type: Boolean, required: true },
        initialMembers: { type: Array, required: true },
    },
    data() {
        return { messages: [], members: this.initialMembers, recipientId: null, content: '', loading: false, syncing: false, sending: false, error: '', timer: null, visibilityHandler: null, unifiedToggleHandler: null, unifiedCloseHandler: null, unifiedHostHandler: null, unifiedHostReady: false, isOpen: false, notificationsReady: false, notifications: [], seenPrivateMessageIds: {}, lastIncomingPrivateId: 0, lastIncomingGroupId: 0, lastMarkedReadId: 0, privateUnread: 0, groupUnread: 0, unreadCounts: {}, showContacts: false, memberSearch: '', groupIsOpen: this.groupIsOpen, echoChannel: null, showScrollToLatest: false };
    },
    computed: {
        currentMemberId() { return this.memberId; },
        otherMembers() { return this.members.filter((member) => Number(member.id) !== this.currentMemberId); },
        filteredMembers() { const keyword = this.memberSearch.trim().toLocaleLowerCase('vi'); return this.otherMembers.filter((member) => member.name.toLocaleLowerCase('vi').includes(keyword)); },
        totalPrivateUnread() { return Object.values(this.unreadCounts).reduce((total, count) => total + Number(count || 0), 0); },
        totalUnread() { return this.totalPrivateUnread + this.groupUnread; },
        title() {
            if (!this.recipientId) return 'Trò chuyện chung';
            return `Trò chuyện riêng với ${this.members.find((member) => Number(member.id) === Number(this.recipientId))?.name || 'thành viên'}`;
        },
    },
    watch: {
        totalUnread(value) {
            window.dispatchEvent(new CustomEvent('group-chat-unread', { detail: { count: Number(value || 0) } }));
        },
        isOpen() {
            this.scheduleSync(true);
        },
    },
    mounted() {
        // Báo cho chat hỗ trợ biết chat nhóm vừa xuất hiện (kể cả trường hợp
        // người dùng tham gia phòng bằng AJAX sau khi trang đã khởi tạo).
        window.dispatchEvent(new CustomEvent('group-chat-available'));
        this.unifiedHostReady = Boolean(window.__groupChatHostReady);
        this.unifiedToggleHandler = () => {
            this.isOpen = !this.isOpen;
            if (this.isOpen) window.dispatchEvent(new CustomEvent('group-chat-opened'));
            else window.dispatchEvent(new CustomEvent('group-chat-closed'));
        };
        this.unifiedCloseHandler = () => {
            this.isOpen = false;
            window.dispatchEvent(new CustomEvent('group-chat-closed'));
        };
        this.unifiedHostHandler = () => {
            this.unifiedHostReady = true;
        };
        window.addEventListener('group-chat-toggle', this.unifiedToggleHandler);
        window.addEventListener('group-chat-close', this.unifiedCloseHandler);
        window.addEventListener('group-chat-host-ready', this.unifiedHostHandler);
        this.visibilityHandler = () => {
            window.clearTimeout(this.timer);
            if (!document.hidden) this.scheduleSync(true);
        };
        document.addEventListener('visibilitychange', this.visibilityHandler);
        window.dispatchEvent(new CustomEvent('group-chat-unread', { detail: { count: this.totalUnread } }));
        this.loadMessages(true);
        this.scheduleSync();
    },
    beforeUnmount() {
        window.clearInterval(this.timer);
        this.leaveGroupOrderChannel();
        window.removeEventListener('group-chat-toggle', this.unifiedToggleHandler);
        window.removeEventListener('group-chat-close', this.unifiedCloseHandler);
        window.removeEventListener('group-chat-host-ready', this.unifiedHostHandler);
        document.removeEventListener('visibilitychange', this.visibilityHandler);
    },
    methods: {
        scheduleSync(immediate = false) {
            window.clearTimeout(this.timer);
            if (document.hidden) return;
            // Echo delivers public group messages immediately. Keep polling only as a
            // safety net (and for private messages) so two open tabs do not continuously
            // compete for PHP/MySQL resources every 500 ms.
            const delay = immediate ? 0 : (window.Echo ? 1500 : 800);
            this.timer = window.setTimeout(async () => {
                try {
                    await this.loadMessages(false);
                } catch (error) {
                    console.error("Lỗi đồng bộ tin nhắn nhóm:", error);
                } finally {
                    this.scheduleSync();
                }
            }, delay);
        },
        closeChat() {
            this.isOpen = false;
            window.dispatchEvent(new CustomEvent('group-chat-closed'));
        },
        openChatDirect() {
            this.isOpen = true;
            window.dispatchEvent(new CustomEvent('group-chat-opened'));
            this.$nextTick(() => this.scrollMessagesToBottom(true));
        },
        selectRecipient(id) {
            this.recipientId = id;
            this.messages = [];
            if (!id) this.groupUnread = 0;
            if (id) this.privateUnread = 0;
            if (id) { this.showContacts = false; this.unreadCounts[id] = 0; }
            this.loadMessages(true);
        },
        subscribeGroupOrderChannel() {
            if (!window.Echo || this.echoChannel || !this.groupId) return;
            this.echoChannel = window.Echo.private('group-order.' + this.groupId)
                .listen('.group-order.message.sent', (payload) => {
                    if (Number(payload.sender_id) === Number(this.currentMemberId)) {
                        return;
                    }

                    const alreadyExists = this.messages.some((message) => Number(message.id) === Number(payload.id));
                    if (alreadyExists) {
                        return;
                    }

                    const payloadMessage = {
                        id: payload.id,
                        sender_id: payload.sender_id,
                        sender_name: payload.sender_name,
                        recipient_id: payload.recipient_id,
                        content: payload.content,
                        attachment_name: payload.attachment_name,
                        attachment_mime: payload.attachment_mime,
                        attachment_size: payload.attachment_size,
                        attachment_url: payload.attachment_url,
                        read_at: payload.read_at,
                        created_at: payload.created_at,
                    };

                    if (!this.recipientId) {
                        this.messages.push(payloadMessage);
                        if (this.isOpen) this.$nextTick(() => this.scrollMessagesToBottom());
                    } else {
                        this.groupUnread += 1;
                    }

                    this.notifyIncoming(payloadMessage, 'Tin nhắn nhóm', false);
                });
        },
        leaveGroupOrderChannel() {
            if (!window.Echo || !this.echoChannel || !this.groupId) return;
            window.Echo.leave('group-order.' + this.groupId);
            this.echoChannel = null;
        },
        async loadMessages(showLoading) {
            if (this.syncing || (document.hidden && !showLoading)) return;
            this.syncing = true;
            const requestedRecipientId = this.recipientId ? Number(this.recipientId) : null;
            if (showLoading) this.loading = true;
            let conversationChangedWhileLoading = false;
            try {
                const query = requestedRecipientId ? `?recipient_id=${requestedRecipientId}` : '';
                const response = await fetch(this.messagesUrl + query, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) return;
                const data = await response.json();
                const activeRecipientId = this.recipientId ? Number(this.recipientId) : null;
                if (activeRecipientId !== requestedRecipientId) {
                    conversationChangedWhileLoading = true;
                    return;
                }
                if (Number(data.group_id) !== this.groupId) {
                    this.messages = [];
                    this.recipientId = null;
                    this.lastIncomingPrivateId = 0;
                    return;
                }
                const nextMembers = data.members || [];
                const membersChanged = nextMembers.length !== this.members.length || nextMembers.some((member, index) => Number(member.id) !== Number(this.members[index]?.id) || member.name !== this.members[index]?.name);
                if (membersChanged) this.members = nextMembers;
                this.unreadCounts = data.private_unread_counts || {};
                const incoming = data.latest_incoming_private;
                const latestGroup = data.latest_group_message;
                const recentGroupMessages = data.recent_group_messages || [];
                const privateBySender = data.latest_unread_private_by_sender || [];
                if (!this.notificationsReady) {
                    this.lastIncomingPrivateId = Number(incoming?.id || 0);
                    this.lastIncomingGroupId = Math.max(0, ...recentGroupMessages.map((message) => Number(message.id)));
                    privateBySender.forEach((message) => { this.seenPrivateMessageIds[message.sender_id] = Number(message.id); });
                    this.notificationsReady = true;
                } else {
                    privateBySender.forEach((message) => {
                        const previousId = Number(this.seenPrivateMessageIds[message.sender_id] || 0);
                        if (Number(message.id) > previousId) {
                            this.seenPrivateMessageIds[message.sender_id] = Number(message.id);
                            this.notifyIncoming(message, 'Tin nhắn riêng', true);
                        }
                    });
                    const newGroupMessages = recentGroupMessages.filter((message) => Number(message.id) > this.lastIncomingGroupId);
                    if (newGroupMessages.length) this.lastIncomingGroupId = Math.max(...newGroupMessages.map((message) => Number(message.id)));
                    newGroupMessages.filter((message) => Number(message.sender_id) !== this.currentMemberId).forEach((message) => {
                        if (this.recipientId || !this.isOpen) this.groupUnread += 1;
                        this.notifyIncoming(message, 'Tin nhắn nhóm', false);
                    });
                }
                if (incoming && Number(incoming.id) > this.lastIncomingPrivateId) {
                    this.lastIncomingPrivateId = Number(incoming.id);
                    if (Number(requestedRecipientId) !== Number(incoming.sender_id)) {
                        this.privateUnread += 1;
                    }
                }
                const nextMessages = data.messages || [];
                const currentLast = this.messages[this.messages.length - 1];
                const nextLast = nextMessages[nextMessages.length - 1];
                const currentReadState = this.messages.map((message) => `${message.id}:${message.read_at || ''}`).join('|');
                const nextReadState = nextMessages.map((message) => `${message.id}:${message.read_at || ''}`).join('|');
                const changed = this.messages.length !== nextMessages.length || currentLast?.id !== nextLast?.id || currentReadState !== nextReadState;
                if (changed) {
                    const hasNewMessage = nextLast?.id !== currentLast?.id;
                    this.messages = nextMessages;
                    if (hasNewMessage) this.$nextTick(() => this.scrollMessagesToBottom(showLoading));
                }
                const newestUnread = [...nextMessages].reverse().find((message) => Number(message.sender_id) === requestedRecipientId && !message.read_at);
                if (requestedRecipientId && this.isOpen && newestUnread && Number(newestUnread.id) > this.lastMarkedReadId) {
                    this.lastMarkedReadId = Number(newestUnread.id);
                    this.markAsRead(requestedRecipientId);
                }
            } finally {
                this.loading = false;
                this.syncing = false;
                const activeRecipientId = this.recipientId ? Number(this.recipientId) : null;
                if (conversationChangedWhileLoading || activeRecipientId !== requestedRecipientId) window.setTimeout(() => this.loadMessages(false), 0);
            }
        },
        async markAsRead(senderId) {
            await fetch(this.readUrl, {
                method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ sender_id: senderId }),
            }).catch(() => {});
        },
        notifyIncoming(message, type, isPrivate) {
            const preview = message.content || 'Tin nhắn mới';
            const text = `${type} từ ${message.sender_name}: ${preview}`;
            const notification = { id: `${isPrivate ? 'p' : 'g'}-${message.id}`, text, senderId: Number(message.sender_id), isPrivate };
            if (!this.notifications.some((item) => item.id === notification.id)) this.notifications.push(notification);
            window.setTimeout(() => this.dismissNotification(notification.id), 15000);
        },
        dismissNotification(id) { this.notifications = this.notifications.filter((item) => item.id !== id); },
        isNearMessageBottom() {
            const box = this.$refs.messages;
            return !box || (box.scrollHeight - box.scrollTop - box.clientHeight) < 72;
        },
        handleMessagesScroll() {
            this.showScrollToLatest = !this.isNearMessageBottom();
        },
        scrollMessagesToBottom(force = false) {
            const box = this.$refs.messages;
            if (!box) return;
            if (!force && !this.isNearMessageBottom()) {
                this.showScrollToLatest = true;
                return;
            }
            box.scrollTop = box.scrollHeight;
            this.showScrollToLatest = false;
        },
        openNotification(notification) {
            this.isOpen = true;
            window.dispatchEvent(new CustomEvent('group-chat-opened'));
            this.selectRecipient(notification.isPrivate ? notification.senderId : null);
            this.dismissNotification(notification.id);
        },
        async send() {
            const content = this.content.trim();
            if (!this.groupIsOpen) {
                this.error = 'Phòng đã đóng nên không thể gửi tin nhắn mới.';
                return;
            }
            if (!content || this.sending) return;
            this.sending = true;
            this.error = '';
            try {
                const form = new FormData();
                form.append('content', content);
                if (this.recipientId) form.append('recipient_id', this.recipientId);
                const response = await fetch(this.sendUrl, { method: 'POST', credentials: 'same-origin', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: form });
                const data = await response.json();
                if (!response.ok) { this.error = data.message || 'Không thể gửi tin nhắn.'; return; }
                this.messages.push(data.message);
                this.content = '';
                this.$nextTick(() => this.scrollMessagesToBottom(true));
            } catch { this.error = 'Kết nối bị gián đoạn.'; }
            finally { this.sending = false; }
        },
        time(value) { return new Date(value).toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }); },
    },
    template: `
        <div>
        <div class="group-chat-notification-stack">
            <button v-for="notification in notifications" :key="notification.id" type="button" class="group-chat-notification" @click="openNotification(notification)">
                <i class="bi" :class="notification.isPrivate ? 'bi-person-lock' : 'bi-people'"></i><span>{{ notification.text }}</span><i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <button v-show="!isOpen && !unifiedHostReady" type="button" class="group-chat-launcher" @click="openChatDirect" aria-label="Mở chat đơn nhóm" title="Chat đơn nhóm"><i class="bi bi-people-fill"></i><span v-if="totalUnread" class="group-chat-launcher-badge">{{ totalUnread > 99 ? '99+' : totalUnread }}</span></button>
        <section v-show="isOpen" class="group-card group-chat-panel">
            <header class="group-chat-head">
                <div><div class="group-eyebrow">Trò chuyện trong phòng</div><strong>{{ title }}</strong></div>
                <button type="button" class="btn btn-sm btn-light rounded-circle" @click="closeChat" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
            </header>
            <div class="group-chat-tools">
                    <button type="button" class="group-chat-tab" :class="{ 'is-active': !recipientId }" @click="selectRecipient(null)"><i class="bi bi-people me-1"></i>Cả nhóm</button>
                    <button type="button" class="group-chat-private-button" :class="{ 'is-active': recipientId }" @click="showContacts = !showContacts"><i class="bi bi-person-lock me-1"></i>Trò chuyện riêng <span v-if="totalPrivateUnread" class="badge bg-danger ms-1">{{ totalPrivateUnread }}</span><i class="bi bi-chevron-down ms-auto"></i></button>
            </div>
            <div v-show="showContacts" class="group-chat-contacts">
                <div class="group-chat-contact-search"><i class="bi bi-search"></i><input v-model="memberSearch" placeholder="Tìm thành viên..."></div>
                <div class="group-chat-contact-list">
                    <button v-for="member in filteredMembers" :key="member.id" type="button" class="group-chat-contact" :class="{ 'is-active': Number(recipientId) === Number(member.id) }" @click="selectRecipient(Number(member.id))"><span class="member-avatar">{{ member.name.charAt(0).toUpperCase() }}</span><strong>{{ member.name }}</strong><span v-if="Number(unreadCounts[member.id] || 0)" class="badge rounded-pill bg-danger">{{ unreadCounts[member.id] }}</span></button>
                    <div v-if="!filteredMembers.length" class="text-center text-secondary small py-3">Không tìm thấy thành viên.</div>
                </div>
            </div>
            <div v-if="recipientId && !showContacts" class="group-chat-recipient-bar" @click="showContacts = true">
                <i class="bi bi-chevron-left"></i>
                <span class="member-avatar" style="width:26px;height:26px;font-size:.7rem;">{{ (members.find(m => Number(m.id) === Number(recipientId))?.name || '?').charAt(0).toUpperCase() }}</span>
                <strong>{{ members.find(m => Number(m.id) === Number(recipientId))?.name || 'Thành viên' }}</strong>
            </div>
            <div class="group-chat-messages" ref="messages" @scroll.passive="handleMessagesScroll">
                <div v-if="loading && !messages.length" class="text-center text-secondary">Đang tải tin nhắn...</div>
                <div v-else-if="!messages.length" class="text-center text-secondary">Chưa có tin nhắn. Hãy bắt đầu trò chuyện!</div>
                <div v-for="message in messages" :key="message.id" class="group-chat-message" :class="{ 'is-mine': Number(message.sender_id) === currentMemberId }">
                    <div class="group-chat-bubble"><small class="d-block fw-bold mb-1">{{ message.sender_name }} · {{ time(message.created_at) }}</small><span>{{ message.content || 'Tin nhắn không có nội dung.' }}</span><small v-if="recipientId && Number(message.sender_id) === currentMemberId && message.read_at" class="group-chat-read"><i class="bi bi-check2-all"></i> Đã xem</small></div>
                </div>
            </div>
            <form class="group-chat-compose" @submit.prevent="send">
                <div class="flex-grow-1"><input :value="content" @input="content = $event.target.value" maxlength="1000" class="form-control group-input" :placeholder="recipientId ? 'Nhắn riêng...' : 'Nhắn cho cả nhóm...'" :disabled="!groupIsOpen"></div>
                <button class="btn btn-primary group-chat-send" :disabled="!groupIsOpen || sending || !content.trim()" aria-label="Gửi tin nhắn"><i class="bi bi-send"></i></button>
            </form>
            <button v-if="showScrollToLatest" type="button" class="group-chat-scroll-latest" @click="scrollMessagesToBottom(true)" aria-label="Cuộn xuống tin nhắn mới nhất" title="Tin nhắn mới nhất"><i class="bi bi-arrow-down"></i></button>
            <div v-if="error" class="text-danger small px-3 pb-3">{{ error }}</div>
        </section>
        </div>`,
};

const mountGroupChats = (scope = document) => {
    scope.querySelectorAll('[data-vue-group-chat]:not([data-v-app])').forEach((root) => createApp(groupOrderChat, {
        messagesUrl: root.dataset.messagesUrl,
        sendUrl: root.dataset.sendUrl,
        readUrl: root.dataset.readUrl,
        groupId: Number(root.dataset.groupId),
        memberId: Number(root.dataset.currentMemberId),
        groupIsOpen: root.dataset.groupIsOpen === '1',
        initialMembers: JSON.parse(root.dataset.members || '[]'),
    }).mount(root));
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-vue-group-branch]:not([data-v-app])').forEach((root) => {
        createApp(groupBranchPicker, {
            branches: JSON.parse(root.dataset.branches || '[]'),
            initialSelected: root.dataset.selected || '',
        }).mount(root);
    });

    document.querySelectorAll('[data-vue-group-datetime]:not([data-v-app])').forEach((root) => {
        createApp(groupDateTimePicker, {
            initialValue: root.dataset.value,
            minValue: root.dataset.min,
            maxValue: root.dataset.max,
        }).mount(root);
    });

    const createRoot = document.querySelector('[data-vue-group-order-create]');
    if (createRoot) {
        const mountPoint = document.createElement('span');
        mountPoint.hidden = true;
        createRoot.appendChild(mountPoint);
        createApp(groupOrderCreate, { rootElement: createRoot }).mount(mountPoint);
    }

    const indexRoot = document.querySelector('[data-vue-group-orders-index]');
    if (indexRoot) {
        const mountPoint = document.createElement('span');
        mountPoint.hidden = true;
        indexRoot.appendChild(mountPoint);
        createApp(groupOrderIndex, { rootElement: indexRoot }).mount(mountPoint);
    }

    const roomRoot = document.querySelector('[data-vue-group-order-room]');
    if (roomRoot) {
        const mountPoint = document.createElement('span');
        mountPoint.hidden = true;
        roomRoot.appendChild(mountPoint);
        createApp(groupOrderRoom, { rootElement: roomRoot }).mount(mountPoint);
    }

    mountGroupChats();
});
