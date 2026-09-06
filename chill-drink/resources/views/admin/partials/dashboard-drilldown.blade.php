<style>
    [data-drilldown] { cursor: pointer; }
    [data-drilldown]:focus-visible { outline: 3px solid rgba(13,147,115,.28); outline-offset: 2px; }
    .dashboard-trace-link { display:inline-flex; align-items:center; gap:.3rem; margin-top:.45rem; padding:0; border:0; background:transparent; color:var(--a-primary, #0d9373); font-size:.75rem; font-weight:700; }
    .dashboard-trace-modal .modal-dialog {
        width:min(1120px, calc(100vw - 2rem), calc(160dvh - 3.2rem));
        max-width:none;
        aspect-ratio:8/5;
        margin:1rem auto;
    }
    .dashboard-trace-modal .modal-content { width:100%; height:100%; max-height:none; overflow:hidden !important; display:flex; flex-direction:column; }
    .dashboard-trace-modal .modal-header { flex:0 0 auto; }
    .dashboard-trace-modal .modal-body { flex:1 1 auto; min-height:0; overflow:hidden; padding:.85rem; }
    .dashboard-trace-modal [data-trace-loading],
    .dashboard-trace-modal [data-trace-error] { height:100%; }
    .dashboard-trace-modal [data-trace-loading] { display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .dashboard-trace-modal [data-trace-content]:not(.d-none) { height:100%; min-height:0; display:grid; grid-template-rows:auto auto auto minmax(0,1fr) auto; gap:.65rem; }
    .dashboard-trace-summary { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.75rem; }
    .dashboard-trace-summary { grid-row:1; }
    .dashboard-trace-summary:not(.dashboard-trace-summary--product) { grid-template-columns:repeat(4,minmax(0,1fr)); }
    .dashboard-trace-summary-card { height:96px; min-width:0; overflow:hidden; padding:.65rem .72rem; border:1px solid #e2e8f0; border-radius:12px; background:linear-gradient(145deg,#fff,#f8fafc); display:flex; flex-direction:column; justify-content:space-between; }
    .dashboard-trace-summary-label { display:flex; align-items:center; gap:.42rem; min-width:0; }
    .dashboard-trace-summary-icon { width:22px; height:22px; flex:0 0 22px; border-radius:7px; background:#e8f7f2; color:var(--a-primary,#0d9373); display:inline-flex; align-items:center; justify-content:center; font-size:.72rem; }
    .dashboard-trace-summary small { min-width:0; overflow:hidden; color:#64748b; font-size:.68rem; font-weight:800; text-overflow:ellipsis; text-transform:uppercase; letter-spacing:.035em; white-space:nowrap; }
    .dashboard-trace-summary strong { color:#0f172a; font-size:.88rem; line-height:1.32; overflow-wrap:anywhere; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; overflow:hidden; }
    .dashboard-trace-summary [data-trace-period] { white-space:pre-line; }
    .dashboard-trace-summary [data-trace-value] { color:#087f5b; font-size:1.06rem; line-height:1.2; }
    .dashboard-trace-summary [data-trace-formula] { font-size:.82rem; white-space:pre-line; }
    .dashboard-trace-summary-card[data-summary-tone="result"] { border-color:#b7e4d5; background:linear-gradient(145deg,#f0fdf8,#e8f7f2); }
    .dashboard-trace-summary-card[data-summary-tone="formula"] { border-color:#dbe4f0; background:linear-gradient(145deg,#f8fafc,#f1f5f9); }
    .dashboard-trace-summary,
    .dashboard-trace-overview,
    .dashboard-trace-modal [data-trace-search-form] { margin-bottom:0 !important; }
    .dashboard-trace-results { min-height:0; overflow:hidden; position:relative; }
    .dashboard-trace-table-wrap { width:100%; height:100%; overflow:auto; }
    .dashboard-trace-table { min-width:900px; }
    .dashboard-trace-table thead th { position:sticky; top:0; z-index:1; }
    .dashboard-trace-table th { height:34px; padding:.35rem .5rem; white-space:nowrap; font-size:.75rem; color:#64748b; }
    .dashboard-trace-table td { height:34px; max-width:190px; padding:.35rem .5rem; overflow:hidden; font-size:.82rem; line-height:1.2; text-overflow:ellipsis; white-space:nowrap; vertical-align:middle; }
    .dashboard-trace-table td .small { display:inline; margin-left:.35rem; }
    .dashboard-trace-overview { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:.65rem; min-height:62px; padding:.55rem .7rem; border:1px solid #dbeafe; border-radius:12px; background:#eff6ff; }
    .dashboard-trace-overview small { display:block; color:#64748b; font-weight:700; }
    .dashboard-trace-overview strong { display:block; margin-top:.15rem; color:#0f172a; }
    .dashboard-trace-empty { height:100%; display:flex; align-items:center; justify-content:center; }
    .dashboard-trace-footer { min-height:31px; margin-top:0 !important; }
    .dashboard-trace-overview { grid-row:2; }
    .dashboard-trace-modal [data-trace-search-form] { grid-row:3; }
    .dashboard-trace-results { grid-row:4; }
    .dashboard-trace-footer { grid-row:5; }
</style>

<div class="modal fade dashboard-trace-modal" id="dashboardTraceModal" tabindex="-1" aria-labelledby="dashboardTraceTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <div class="small text-uppercase fw-bold text-success mb-1">Chi tiết nguồn dữ liệu</div>
                    <h2 class="modal-title fs-5 fw-bold" id="dashboardTraceTitle">Đang tải...</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div data-trace-loading class="py-5 text-center"><span class="spinner-border text-success" aria-hidden="true"></span><div class="mt-2 text-secondary">Đang tải dữ liệu...</div></div>
                <div data-trace-error class="alert alert-danger d-none" role="alert"></div>
                <div data-trace-content class="d-none">
                    <div class="dashboard-trace-summary mb-3">
                        <div data-trace-product-card class="dashboard-trace-summary-card d-none">
                            <div class="dashboard-trace-summary-label"><span class="dashboard-trace-summary-icon"><i class="bi bi-cup-straw"></i></span><small>Sản phẩm</small></div>
                            <strong data-trace-product></strong>
                        </div>
                        <div class="dashboard-trace-summary-card">
                            <div class="dashboard-trace-summary-label"><span class="dashboard-trace-summary-icon"><i class="bi bi-calendar3"></i></span><small>Thời gian thống kê</small></div>
                            <strong data-trace-period></strong>
                        </div>
                        <div class="dashboard-trace-summary-card">
                            <div class="dashboard-trace-summary-label"><span class="dashboard-trace-summary-icon"><i class="bi bi-shop"></i></span><small>Phạm vi áp dụng</small></div>
                            <strong data-trace-branch></strong>
                        </div>
                        <div class="dashboard-trace-summary-card" data-summary-tone="result">
                            <div class="dashboard-trace-summary-label"><span class="dashboard-trace-summary-icon"><i class="bi bi-check2-circle"></i></span><small>Kết quả ghi nhận</small></div>
                            <strong data-trace-value></strong>
                        </div>
                        <div class="dashboard-trace-summary-card" data-summary-tone="formula">
                            <div class="dashboard-trace-summary-label"><span class="dashboard-trace-summary-icon"><i class="bi bi-calculator"></i></span><small>Cách tính nhanh</small></div>
                            <strong data-trace-formula></strong>
                        </div>
                    </div>
                    <div data-trace-overview class="dashboard-trace-overview d-none mb-3" aria-label="Tổng quan sản phẩm tại chi nhánh">
                        <div><small>Số lượng bán</small><strong data-trace-overview-quantity></strong></div>
                        <div><small>Doanh thu</small><strong data-trace-overview-revenue></strong></div>
                        <div><small>Đơn hoàn thành</small><strong data-trace-overview-orders></strong></div>
                        <div><small>Tỷ lệ hủy</small><strong data-trace-overview-cancellation></strong></div>
                        <div><small>Đánh giá</small><strong data-trace-overview-rating></strong></div>
                    </div>
                    <form data-trace-search-form class="d-flex gap-2 mb-3" role="search">
                        <input data-trace-search class="form-control" type="search" maxlength="100" placeholder="Tìm mã đơn, khách hàng hoặc sản phẩm..." aria-label="Tìm trong dữ liệu nguồn">
                        <button class="btn btn-outline-success" type="submit">Tìm</button>
                    </form>
                    <div class="dashboard-trace-results">
                        <div class="table-responsive border rounded-3 dashboard-trace-table-wrap">
                            <table class="table table-hover mb-0 dashboard-trace-table">
                                <thead class="table-light" data-trace-head></thead>
                                <tbody data-trace-rows></tbody>
                            </table>
                        </div>
                        <div data-trace-empty class="dashboard-trace-empty d-none text-center text-secondary">Không có dữ liệu trong khoảng thời gian này.</div>
                    </div>
                    <div class="dashboard-trace-footer d-flex justify-content-between align-items-center gap-3">
                        <span class="small text-secondary" data-trace-count></span>
                        <div class="btn-group btn-group-sm" data-trace-pagination aria-label="Phân trang dữ liệu nguồn"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('dashboardTraceModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const endpoint = @json($drilldownEndpoint);
    let defaults = @json($drilldownDefaults ?? []);
    let active = null;
    let controller = null;

    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[char]));
    const money = value => `${new Intl.NumberFormat('vi-VN').format(Number(value || 0))}đ`;
    const number = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0));
    const metricValue = (metric, value, summary = {}) => {
        if (metric === 'average_order_value') return `${money(value)}/đơn`;
        if (metric === 'revenue' || metric === 'product_revenue') return money(value);
        if (metric === 'cancellation_rate' || metric === 'product_cancellation_rate') return `${number(value)}%`;
        if (metric === 'product_reviews') return Number(summary.review_count || 0) > 0 ? `${number(value)}/5` : 'Chưa có đánh giá';
        if (['customers', 'new_customers'].includes(metric)) return `${number(value)} khách hàng`;
        if (['items_sold', 'product_sales', 'products'].includes(metric)) return `${number(value)} sản phẩm`;
        return `${number(value)} đơn hàng`;
    };
    const compactPeriod = label => String(label || '').replace(/\s+[–—-]\s+(?=\d{2}\/\d{2}\/\d{4})/, '\nđến ');
    const compactFormula = (metric, value, summary = {}) => {
        const orderCount = Number(summary.order_count || 0);
        const cancelledCount = Number(summary.cancelled_count || 0);
        const denominatorCount = Number(summary.denominator_count || 0);
        const quantity = Number(summary.quantity || 0);

        switch (metric) {
            case 'revenue': return `${number(orderCount)} đơn hoàn thành → ${money(value)}`;
            case 'average_order_value': return orderCount > 0 ? `${money(summary.revenue)} ÷ ${number(orderCount)} đơn` : 'Chưa có đơn để tính trung bình';
            case 'orders':
            case 'completed_orders': return `Đếm ${number(orderCount)} đơn hoàn thành`;
            case 'total_orders': return `Đếm ${number(orderCount)} đơn đã tạo`;
            case 'cancelled_orders': return `Đếm ${number(cancelledCount)} đơn bị hủy`;
            case 'cancellation_rate':
            case 'product_cancellation_rate': return denominatorCount > 0 ? `${number(cancelledCount)} ÷ ${number(denominatorCount)} đơn × 100` : 'Chưa có đơn để tính tỷ lệ';
            case 'customers': return `Đếm ${number(summary.customer_count)} khách đã mua`;
            case 'new_customers': return `Đếm ${number(summary.customer_count)} khách hàng mới`;
            case 'items_sold': return `${number(quantity)} sản phẩm đã bán`;
            case 'product_sales': return `${number(quantity)} sản phẩm qua ${number(orderCount)} đơn`;
            case 'product_revenue': return `${number(orderCount)} đơn → ${money(summary.revenue)}`;
            case 'product_reviews': return Number(summary.review_count || 0) > 0 ? `Trung bình từ ${number(summary.review_count)} lượt đánh giá` : 'Chưa có lượt đánh giá';
            case 'products': return `Đếm ${number(summary.product_count)} sản phẩm trong menu`;
            default: return String(value ?? '');
        }
    };
    const localSqlDate = value => {
        if (!value) return '';
        if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value)) return value;
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        const parts = new Intl.DateTimeFormat('sv-SE', {timeZone:@json(config('app.timezone', 'Asia/Ho_Chi_Minh')), year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false}).formatToParts(date);
        const get = type => parts.find(part => part.type === type)?.value || '00';
        return `${get('year')}-${get('month')}-${get('day')} ${get('hour')}:${get('minute')}:${get('second')}`;
    };
    const requestParams = (page = 1) => {
        const params = new URLSearchParams();
        const merged = {...defaults, ...active, page, per_page:20, search:modalEl.querySelector('[data-trace-search]').value.trim()};
        Object.entries(merged).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') params.set(key, key === 'from' || key === 'to' ? localSqlDate(value) : value);
        });
        return params;
    };
    const normalizeConfig = config => {
        const normalized = {};
        const metric = config.metric ?? config.drilldown;
        const branchId = config.branch_id ?? config.branchId;
        const productId = config.product_id ?? config.productId;
        if (metric !== undefined) normalized.metric = metric;
        if (config.from !== undefined) normalized.from = config.from;
        if (config.to !== undefined) normalized.to = config.to;
        if (branchId !== undefined) normalized.branch_id = branchId;
        if (productId !== undefined) normalized.product_id = productId;
        return normalized;
    };
    const setState = state => {
        modalEl.querySelector('[data-trace-loading]').classList.toggle('d-none', state !== 'loading');
        modalEl.querySelector('[data-trace-error]').classList.toggle('d-none', state !== 'error');
        modalEl.querySelector('[data-trace-content]').classList.toggle('d-none', state !== 'content');
    };
    const renderRows = payload => {
        const type = payload.row_type;
        const rows = payload.data?.rows || [];
        const head = modalEl.querySelector('[data-trace-head]');
        const body = modalEl.querySelector('[data-trace-rows]');
        if (type === 'orders') {
            head.innerHTML = '<tr><th>Mã đơn</th><th>Thời gian</th><th>Khách hàng</th><th>Chi nhánh</th><th>Giảm giá</th><th>Phí giao</th><th>Giá trị tính</th><th>Trạng thái / lý do</th></tr>';
            body.innerHTML = rows.map(row => `<tr><td><a class="fw-bold text-success" href="${escapeHtml(row.order_url)}">${escapeHtml(row.order_code)}</a></td><td>${escapeHtml(row.created_at)}</td><td>${escapeHtml(row.customer_name)}</td><td>${escapeHtml(row.branch_name || '—')}</td><td>${money(row.discount)}</td><td>${money(row.shipping_fee)}</td><td class="fw-bold">${money(row.contribution)}</td><td>${escapeHtml(row.status_label || row.status)}${row.cancellation_reason ? `<div class="small text-danger">${escapeHtml(row.cancellation_reason)}</div>` : ''}</td></tr>`).join('');
        } else if (type === 'items') {
            head.innerHTML = '<tr><th>Sản phẩm</th><th>Mã đơn</th><th>Thời gian</th><th>Chi nhánh</th><th>Số lượng</th><th>Giá trị dòng</th><th>Trạng thái</th></tr>';
            body.innerHTML = rows.map(row => `<tr><td class="fw-bold">${escapeHtml(row.product_name)}</td><td><a class="text-success" href="${escapeHtml(row.order_url)}">${escapeHtml(row.order_code)}</a></td><td>${escapeHtml(row.created_at)}</td><td>${escapeHtml(row.branch_name || '—')}</td><td class="fw-bold">${escapeHtml(row.quantity)}</td><td>${money(row.contribution)}</td><td>${escapeHtml(row.status_label || 'Không xác định')}</td></tr>`).join('');
        } else if (type === 'customers') {
            head.innerHTML = '<tr><th>Khách hàng</th><th>Thời điểm</th><th>Số đơn hợp lệ</th><th>Doanh thu đóng góp</th></tr>';
            body.innerHTML = rows.map(row => `<tr><td class="fw-bold">${escapeHtml(row.name)}</td><td>${escapeHtml(row.first_order_at || row.created_at)}</td><td>${escapeHtml(row.order_count ?? '—')}</td><td>${row.revenue === undefined ? '—' : money(row.revenue)}</td></tr>`).join('');
        } else if (type === 'reviews') {
            head.innerHTML = '<tr><th>Thời gian</th><th>Khách hàng</th><th>Mã đơn</th><th>Chi nhánh</th><th>Điểm đánh giá</th><th>Nội dung</th></tr>';
            body.innerHTML = rows.map(row => `<tr><td>${escapeHtml(row.created_at)}</td><td class="fw-bold">${escapeHtml(row.customer_name)}</td><td>${row.order_url ? `<a class="text-success" href="${escapeHtml(row.order_url)}">${escapeHtml(row.order_code)}</a>` : escapeHtml(row.order_code)}</td><td>${escapeHtml(row.branch_name || '—')}</td><td class="fw-bold text-warning">${escapeHtml(row.rating)}/5 ★</td><td>${escapeHtml(row.comment || 'Không có nội dung')}</td></tr>`).join('');
        } else {
            head.innerHTML = '<tr><th>Sản phẩm</th><th>SKU</th><th>Ngày tạo</th><th>Trạng thái</th></tr>';
            body.innerHTML = rows.map(row => `<tr><td class="fw-bold">${escapeHtml(row.name)}</td><td>${escapeHtml(row.sku || '—')}</td><td>${escapeHtml(row.created_at)}</td><td>${row.status ? 'Đang bán' : 'Ngừng bán'}</td></tr>`).join('');
        }
        modalEl.querySelector('[data-trace-empty]').classList.toggle('d-none', rows.length > 0);
        modalEl.querySelector('.dashboard-trace-table').classList.toggle('d-none', rows.length === 0);
    };
    const load = async (page = 1) => {
        if (!active) return;
        controller?.abort();
        controller = new AbortController();
        setState('loading');
        try {
            const response = await fetch(`${endpoint}?${requestParams(page)}`, {headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}, signal:controller.signal});
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Không thể tải dữ liệu chi tiết.');
            modalEl.querySelector('#dashboardTraceTitle').textContent = payload.title;
            const productCard = modalEl.querySelector('[data-trace-product-card]');
            productCard.classList.toggle('d-none', !payload.product);
            modalEl.querySelector('[data-trace-product]').textContent = payload.product ? `${payload.product.name}${payload.product.sku ? ` (${payload.product.sku})` : ''}` : '';
            const periodEl = modalEl.querySelector('[data-trace-period]');
            periodEl.textContent = compactPeriod(payload.period.label);
            periodEl.title = payload.period.label;
            modalEl.querySelector('[data-trace-branch]').textContent = payload.branch ? `${payload.branch.name}${payload.branch.code ? ` (${payload.branch.code})` : ''}` : 'Toàn hệ thống';
            modalEl.querySelector('[data-trace-value]').textContent = metricValue(active.metric, payload.value, payload.summary);
            const formulaEl = modalEl.querySelector('[data-trace-formula]');
            formulaEl.textContent = compactFormula(active.metric, payload.value, payload.summary);
            formulaEl.title = payload.formula;
            formulaEl.setAttribute('aria-label', payload.formula);
            modalEl.querySelector('.dashboard-trace-summary').classList.toggle('dashboard-trace-summary--product', Boolean(payload.product));
            const overview = payload.overview;
            const overviewBox = modalEl.querySelector('[data-trace-overview]');
            overviewBox.classList.toggle('d-none', !overview);
            if (overview) {
                modalEl.querySelector('[data-trace-overview-quantity]').textContent = `${number(overview.quantity)} sản phẩm`;
                modalEl.querySelector('[data-trace-overview-revenue]').textContent = money(overview.revenue);
                modalEl.querySelector('[data-trace-overview-orders]').textContent = `${number(overview.completed_order_count)} đơn`;
                modalEl.querySelector('[data-trace-overview-cancellation]').textContent = `${number(overview.cancellation_rate)}% (${number(overview.cancelled_order_count)}/${number(overview.related_order_count)} đơn)`;
                modalEl.querySelector('[data-trace-overview-rating]').textContent = Number(overview.review_count || 0) > 0
                    ? `${number(overview.average_rating)}/5 (${number(overview.review_count)} lượt)`
                    : 'Chưa có đánh giá';
            }
            modalEl.querySelector('[data-trace-empty]').textContent = payload.product && payload.branch
                ? `Không có dữ liệu của ${payload.product.name} tại ${payload.branch.name} trong khoảng thời gian đang xem.`
                : 'Không có dữ liệu trong khoảng thời gian này.';
            renderRows(payload);
            const data = payload.data || {};
            const firstResult = data.total > 0 ? ((data.current_page - 1) * data.per_page) + 1 : 0;
            const lastResult = data.total > 0 ? firstResult + (data.rows?.length || 0) - 1 : 0;
            modalEl.querySelector('[data-trace-count]').textContent = `Hiển thị ${new Intl.NumberFormat('vi-VN').format(firstResult)}–${new Intl.NumberFormat('vi-VN').format(lastResult)} trên tổng số ${new Intl.NumberFormat('vi-VN').format(data.total || 0)} kết quả`;
            const pagination = modalEl.querySelector('[data-trace-pagination]');
            pagination.replaceChildren();
            [['Trang trước', data.current_page - 1, data.current_page <= 1], ['Trang sau', data.current_page + 1, data.current_page >= data.last_page]].forEach(([label,target,disabled]) => {
                const button = document.createElement('button'); button.type='button'; button.className='btn btn-outline-secondary'; button.textContent=label; button.disabled=disabled; button.setAttribute('aria-label', label); button.addEventListener('click', () => load(target)); pagination.appendChild(button);
            });
            setState('content');
        } catch (error) {
            if (error.name === 'AbortError') return;
            const box = modalEl.querySelector('[data-trace-error]'); box.textContent = error.message || 'Có lỗi xảy ra. Vui lòng thử lại.'; setState('error');
        }
    };
    window.openDashboardDrilldown = (config) => {
        active = normalizeConfig(config);
        modalEl.querySelector('[data-trace-search]').value = '';
        modal.show();
        load(1);
    };
    window.setDashboardDrilldownDefaults = config => { defaults = {...defaults, ...normalizeConfig(config)}; };
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-drilldown]');
        if (!trigger) return;
        event.preventDefault(); event.stopPropagation();
        window.openDashboardDrilldown({...trigger.dataset, metric:trigger.dataset.drilldown});
    });
    document.addEventListener('keydown', event => {
        const trigger = event.target.closest('[data-drilldown]');
        if (trigger && (event.key === 'Enter' || event.key === ' ')) { event.preventDefault(); trigger.click(); }
    });
    modalEl.querySelector('[data-trace-search-form]').addEventListener('submit', event => { event.preventDefault(); load(1); });
    modalEl.addEventListener('hidden.bs.modal', () => controller?.abort());
});
</script>
