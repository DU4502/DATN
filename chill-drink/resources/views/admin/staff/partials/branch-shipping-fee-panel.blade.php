{{-- V27: Cấu hình phí giao hàng theo từng chi nhánh. Chỉ hiện trong workspace Super Admin. --}}
<div id="branchShippingFeeV27" class="branch-fee-v27" hidden>
    <div class="bf-card">
        <div class="bf-head" data-bf-toggle>
            <div class="bf-head-main">
                <span class="bf-icon"><i class="bi bi-truck"></i></span>
                <div class="bf-title-wrap">
                    <div class="bf-title">Cài đặt phí giao hàng</div>
                    <div class="bf-subtitle" data-bf-summary>Đang tải cấu hình chi nhánh...</div>
                </div>
            </div>

            <div class="bf-head-actions">
                <label class="bf-branch-select-wrap" data-bf-stop-toggle>
                    <i class="bi bi-shop"></i>
                    <select class="bf-select" data-bf-branch aria-label="Chọn chi nhánh"></select>
                </label>
                <span class="bf-permission"><i class="bi bi-shield-check"></i> Super Admin</span>
                <button type="button" class="bf-toggle-btn" data-bf-toggle-button aria-expanded="false" title="Mở cài đặt">
                    <i class="bi bi-chevron-down"></i>
                </button>
            </div>
        </div>

        <div class="bf-body" data-bf-body hidden>
            <div class="bf-grid-top">
                <label class="bf-field">
                    <span>Miễn phí đầu tuyến</span>
                    <div class="bf-input-unit"><input type="number" min="0" max="15" step="0.1" data-bf-free-km><em>km</em></div>
                    <small>Chỉ số km vượt ngưỡng mới tính phí.</small>
                </label>

                <div class="bf-field">
                    <span>Phạm vi nhận đơn</span>
                    <div class="bf-locked"><strong>≤ 15 km đường bộ</strong><i class="bi bi-lock"></i></div>
                    <small>Giới hạn vận hành cố định toàn hệ thống.</small>
                </div>

                <label class="bf-field">
                    <span>Phụ phí giao nhanh</span>
                    <div class="bf-input-unit"><input type="number" min="0" step="1000" data-bf-fast-surcharge><em>đ</em></div>
                    <small>Cộng thêm sau phí theo km.</small>
                </label>
            </div>

            <div class="bf-section-head">
                <div>
                    <strong>Đơn giá theo số lượng cốc</strong>
                    <small>Phần vượt km miễn phí × đơn giá/km của bậc tương ứng.</small>
                </div>
                <button type="button" class="bf-add-tier" data-bf-add-tier><i class="bi bi-plus-lg"></i> Thêm bậc</button>
            </div>

            <div class="bf-tiers" data-bf-tiers></div>

            <div class="bf-bottom-row">
                <div class="bf-preview">
                    <div class="bf-preview-title"><i class="bi bi-calculator"></i> Thử nhanh</div>
                    <div class="bf-preview-controls">
                        <label>Khoảng cách <div class="bf-input-unit small"><input type="number" min="0" max="15" step="0.1" value="7.5" data-bf-preview-distance><em>km</em></div></label>
                        <label>Số cốc <input type="number" min="1" step="1" value="8" data-bf-preview-cups></label>
                        <div class="bf-preview-result"><span>Phí tiêu chuẩn</span><strong data-bf-preview-result>—</strong></div>
                    </div>
                    <small data-bf-preview-formula></small>
                </div>

                <div class="bf-save-wrap">
                    <div class="bf-message" data-bf-message></div>
                    <button type="button" class="bf-save" data-bf-save><i class="bi bi-floppy"></i> Lưu cho chi nhánh này</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.branch-fee-v27{margin-bottom:1rem;}
.branch-fee-v27 .bf-card{border:1px solid #caeee4;border-radius:14px;background:#f8fffd;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.02)}
.branch-fee-v27 .bf-head{min-height:62px;padding:.72rem .9rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;cursor:pointer;background:linear-gradient(180deg,#fbfffe 0%,#f6fffc 100%)}
.branch-fee-v27 .bf-head-main,.branch-fee-v27 .bf-head-actions{display:flex;align-items:center;gap:.65rem;min-width:0}
.branch-fee-v27 .bf-icon{width:34px;height:34px;border-radius:9px;background:#e5f8f2;color:#0a8e6e;display:grid;place-items:center;flex:0 0 auto}
.branch-fee-v27 .bf-title-wrap{min-width:0}.branch-fee-v27 .bf-title{font-weight:800;color:#17202b;font-size:.91rem}.branch-fee-v27 .bf-subtitle{font-size:.68rem;color:#6b7280;margin-top:.12rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:620px}
.branch-fee-v27 .bf-branch-select-wrap{height:32px;display:flex;align-items:center;gap:.35rem;border:1px solid #d7e3df;border-radius:8px;padding:0 .55rem;background:white;color:#0a8e6e;cursor:default}
.branch-fee-v27 .bf-select{border:0;outline:0;background:transparent;min-width:155px;font-size:.73rem;font-weight:700;color:#1f2937;padding-right:.2rem}
.branch-fee-v27 .bf-permission{display:inline-flex;align-items:center;gap:.3rem;border-radius:999px;background:#dcfce7;color:#166534;padding:.26rem .55rem;font-size:.64rem;font-weight:700;white-space:nowrap}
.branch-fee-v27 .bf-toggle-btn{width:32px;height:32px;border:1px solid #d7e3df;border-radius:8px;background:white;color:#60706b;display:grid;place-items:center;transition:.18s ease}
.branch-fee-v27.is-open .bf-toggle-btn{transform:rotate(180deg)}
.branch-fee-v27 .bf-body{border-top:1px solid #dcece7;padding:.82rem .9rem .9rem;background:#fbfffe}
.branch-fee-v27 .bf-grid-top{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.65rem}
.branch-fee-v27 .bf-field{display:block}.branch-fee-v27 .bf-field>span{display:block;font-size:.7rem;font-weight:800;color:#1f2937;margin-bottom:.3rem}.branch-fee-v27 .bf-field small,.branch-fee-v27 .bf-section-head small,.branch-fee-v27 .bf-preview small{display:block;color:#7a8581;font-size:.61rem;margin-top:.22rem}
.branch-fee-v27 input{height:32px;border:1px solid #d7e3df;border-radius:7px;background:#fff;padding:0 .52rem;font-size:.74rem;outline:0;width:100%}.branch-fee-v27 input:focus{border-color:#18a580;box-shadow:0 0 0 2px rgba(24,165,128,.09)}
.branch-fee-v27 .bf-input-unit{display:flex;align-items:center;border:1px solid #d7e3df;border-radius:7px;background:#fff;overflow:hidden}.branch-fee-v27 .bf-input-unit input{border:0;border-radius:0;box-shadow:none}.branch-fee-v27 .bf-input-unit em{font-style:normal;font-size:.67rem;color:#66736f;border-left:1px solid #e4ebe9;padding:0 .5rem;white-space:nowrap}.branch-fee-v27 .bf-input-unit.small{height:30px}.branch-fee-v27 .bf-input-unit.small input{height:28px;width:78px}
.branch-fee-v27 .bf-locked{height:32px;border:1px solid #d9e2df;border-radius:7px;background:#eef2f1;display:flex;align-items:center;justify-content:space-between;padding:0 .6rem;color:#45514d;font-size:.72rem}
.branch-fee-v27 .bf-section-head{display:flex;align-items:end;justify-content:space-between;gap:1rem;margin-top:.82rem;margin-bottom:.4rem}.branch-fee-v27 .bf-section-head strong{display:block;font-size:.72rem}.branch-fee-v27 .bf-add-tier{border:1px solid #18a580;border-radius:999px;background:#fff;color:#0b8f70;height:29px;padding:0 .68rem;font-size:.66rem;font-weight:700}
.branch-fee-v27 .bf-tiers{border:1px solid #dce7e3;border-radius:9px;overflow:hidden;background:#fff}.branch-fee-v27 .bf-tier{display:grid;grid-template-columns:160px minmax(110px,1fr) minmax(150px,1.35fr) 36px;gap:.5rem;align-items:center;padding:.43rem .55rem;border-top:1px solid #edf1f0}.branch-fee-v27 .bf-tier:first-child{border-top:0}.branch-fee-v27 .bf-tier-range{font-size:.69rem;font-weight:800;color:#26332f}.branch-fee-v27 .bf-tier label{font-size:.61rem;color:#7a8581}.branch-fee-v27 .bf-tier label input{margin-top:.18rem}.branch-fee-v27 .bf-remove-tier{width:30px;height:30px;border:1px solid #fca5a5;border-radius:50%;background:#fff;color:#ef4444;display:grid;place-items:center}
.branch-fee-v27 .bf-bottom-row{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:.75rem;align-items:stretch;margin-top:.72rem}.branch-fee-v27 .bf-preview{border:1px dashed #9ad8c8;border-radius:9px;padding:.58rem .65rem;background:#f4fffb}.branch-fee-v27 .bf-preview-title{font-size:.68rem;font-weight:800;color:#26332f;margin-bottom:.35rem}.branch-fee-v27 .bf-preview-controls{display:flex;align-items:end;gap:.55rem;flex-wrap:wrap}.branch-fee-v27 .bf-preview-controls>label{font-size:.61rem;color:#66736f}.branch-fee-v27 .bf-preview-controls>label>input{width:94px;margin-top:.18rem}.branch-fee-v27 .bf-preview-result{margin-left:auto;text-align:right}.branch-fee-v27 .bf-preview-result span{display:block;font-size:.6rem;color:#7a8581}.branch-fee-v27 .bf-preview-result strong{font-size:.86rem;color:#08775e}
.branch-fee-v27 .bf-save-wrap{display:flex;flex-direction:column;justify-content:flex-end;gap:.35rem}.branch-fee-v27 .bf-save{height:34px;border:0;border-radius:9px;background:#159468;color:#fff;font-size:.7rem;font-weight:800}.branch-fee-v27 .bf-save:disabled{opacity:.55}.branch-fee-v27 .bf-message{min-height:18px;font-size:.64rem;text-align:right}.branch-fee-v27 .bf-message.ok{color:#0a7e60}.branch-fee-v27 .bf-message.err{color:#dc2626}
@media(max-width:1000px){.branch-fee-v27 .bf-head{align-items:flex-start;flex-direction:column}.branch-fee-v27 .bf-head-actions{width:100%;flex-wrap:wrap}.branch-fee-v27 .bf-grid-top{grid-template-columns:1fr}.branch-fee-v27 .bf-bottom-row{grid-template-columns:1fr}.branch-fee-v27 .bf-tier{grid-template-columns:1fr 1fr 1fr 32px}.branch-fee-v27 .bf-tier-range{grid-column:1/-1}.branch-fee-v27 .bf-subtitle{max-width:90vw}}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('branchShippingFeeV27');
    if (!root || !window.location.pathname.includes('/super-admin/staff')) return;

    const apiBase = @json(url('/admin/super-admin/shipping-fees'));
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const body = root.querySelector('[data-bf-body]');
    const toggleButton = root.querySelector('[data-bf-toggle-button]');
    const branchSelect = root.querySelector('[data-bf-branch]');
    const summary = root.querySelector('[data-bf-summary]');
    const tiersBox = root.querySelector('[data-bf-tiers]');
    const freeKmInput = root.querySelector('[data-bf-free-km]');
    const fastInput = root.querySelector('[data-bf-fast-surcharge]');
    const message = root.querySelector('[data-bf-message]');
    const saveButton = root.querySelector('[data-bf-save]');
    const previewDistance = root.querySelector('[data-bf-preview-distance]');
    const previewCups = root.querySelector('[data-bf-preview-cups]');
    const previewResult = root.querySelector('[data-bf-preview-result]');
    const previewFormula = root.querySelector('[data-bf-preview-formula]');
    let currentSettings = null;

    function formatMoney(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value || 0)) + 'đ';
    }

    function legacyFeeCard() {
        const nodes = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6,strong,span,div')]
            .filter(el => !root.contains(el) && (el.textContent || '').trim() === 'Cài đặt phí giao hàng');
        for (const title of nodes) {
            let node = title;
            while (node && node !== document.body) {
                const text = (node.innerText || '');
                if (text.includes('Đơn giá theo số lượng cốc') && (text.includes('Lưu & áp dụng ngay') || text.includes('Thử nhanh công thức'))) {
                    return node;
                }
                node = node.parentElement;
            }
        }
        return null;
    }

    function setOpen(open) {
        root.classList.toggle('is-open', open);
        body.hidden = !open;
        toggleButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggleButton.title = open ? 'Đóng cài đặt' : 'Mở cài đặt';
    }

    root.querySelectorAll('[data-bf-toggle]').forEach(el => el.addEventListener('click', () => setOpen(!root.classList.contains('is-open'))));
    root.querySelectorAll('[data-bf-stop-toggle]').forEach(el => el.addEventListener('click', e => e.stopPropagation()));
    toggleButton.addEventListener('click', e => { e.stopPropagation(); setOpen(!root.classList.contains('is-open')); });

    function normalizeTiers() {
        return [...tiersBox.querySelectorAll('.bf-tier')].map((row, index, rows) => ({
            max_cups: index === rows.length - 1 ? null : (row.querySelector('[data-tier-max]').value || null),
            per_km_fee: Number(row.querySelector('[data-tier-fee]').value || 0),
        }));
    }

    function tierRangeLabel(index, tiers) {
        const previous = index === 0 ? 0 : Number(tiers[index - 1]?.max_cups || 0);
        const current = tiers[index];
        if (index === tiers.length - 1 || current.max_cups === null || current.max_cups === '') return `Từ ${previous + 1} cốc`;
        const max = Number(current.max_cups || previous + 1);
        return `${previous + 1} - ${max} cốc`;
    }

    function renderTiers(tiers) {
        const safe = Array.isArray(tiers) && tiers.length ? tiers : [
            {max_cups:5,per_km_fee:5000},{max_cups:10,per_km_fee:6000},{max_cups:15,per_km_fee:7000},{max_cups:null,per_km_fee:8000}
        ];
        tiersBox.innerHTML = safe.map((tier, index) => `
            <div class="bf-tier">
                <div class="bf-tier-range" data-tier-range>${tierRangeLabel(index, safe)}</div>
                <label>Đến … cốc
                    <input type="number" min="1" step="1" data-tier-max value="${index === safe.length - 1 ? '' : (tier.max_cups ?? '')}" ${index === safe.length - 1 ? 'placeholder="Không giới hạn" disabled' : ''}>
                </label>
                <label>Giá / km vượt ngưỡng
                    <div class="bf-input-unit"><input type="number" min="0" step="500" data-tier-fee value="${Number(tier.per_km_fee || 0)}"><em>đ/km</em></div>
                </label>
                <button type="button" class="bf-remove-tier" data-tier-remove title="Xóa bậc" ${safe.length <= 1 ? 'disabled' : ''}><i class="bi bi-trash"></i></button>
            </div>`).join('');
        refreshTierLabels();
        updatePreviewLocal();
    }

    function refreshTierLabels() {
        const rows = [...tiersBox.querySelectorAll('.bf-tier')];
        const tiers = rows.map((row, index) => ({max_cups:index === rows.length-1?null:(row.querySelector('[data-tier-max]').value||null)}));
        rows.forEach((row,index)=> row.querySelector('[data-tier-range]').textContent = tierRangeLabel(index, tiers));
    }

    function updateSummary() {
        if (!currentSettings) return;
        const branchName = branchSelect.options[branchSelect.selectedIndex]?.text || 'Chi nhánh';
        summary.textContent = `${branchName} · miễn phí ${Number(currentSettings.free_km || 0)} km · ${currentSettings.tiers?.length || 0} bậc · giao nhanh +${formatMoney(currentSettings.fast_surcharge)}`;
    }

    function updatePreviewLocal() {
        const distance = Math.max(0, Math.min(15, Number(previewDistance.value || 0)));
        const cups = Math.max(1, Number(previewCups.value || 1));
        const freeKm = Math.max(0, Number(freeKmInput.value || 0));
        const tiers = normalizeTiers();
        let tier = tiers[tiers.length - 1] || {per_km_fee:0};
        for (const candidate of tiers) {
            if (candidate.max_cups === null || cups <= Number(candidate.max_cups)) { tier = candidate; break; }
        }
        const chargedKm = Math.max(0, distance - freeKm);
        const fee = Math.round(chargedKm * Number(tier.per_km_fee || 0));
        previewResult.textContent = formatMoney(fee);
        previewFormula.textContent = `(${distance.toLocaleString('vi-VN')} - ${freeKm.toLocaleString('vi-VN')}) km × ${formatMoney(tier.per_km_fee)}/km = ${formatMoney(fee)} · ${cups} cốc.`;
    }

    async function loadBranches() {
        const response = await fetch(apiBase, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        if (!response.ok) throw new Error('Không tải được danh sách chi nhánh.');
        const payload = await response.json();
        const branches = payload.branches || [];
        branchSelect.innerHTML = branches.map(b => `<option value="${b.id}">${escapeHtml(b.name)}</option>`).join('');
        if (!branches.length) throw new Error('Chưa có chi nhánh để cấu hình.');
        return Number(branches[0].id);
    }

    async function loadSettings(branchId) {
        message.textContent = '';
        const response = await fetch(`${apiBase}/${branchId}`, {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
        if (!response.ok) throw new Error('Không tải được cấu hình phí của chi nhánh.');
        const payload = await response.json();
        currentSettings = payload.settings;
        freeKmInput.value = Number(currentSettings.free_km ?? 5);
        fastInput.value = Number(currentSettings.fast_surcharge ?? 8000);
        renderTiers(currentSettings.tiers || []);
        updateSummary();
        updatePreviewLocal();
    }

    async function saveSettings() {
        const branchId = Number(branchSelect.value || 0);
        if (!branchId) return;
        const payload = {
            free_km: Number(freeKmInput.value || 0),
            fast_surcharge: Number(fastInput.value || 0),
            tiers: normalizeTiers(),
        };
        saveButton.disabled = true;
        message.className = 'bf-message';
        message.textContent = 'Đang lưu...';
        try {
            const response = await fetch(`${apiBase}/${branchId}`, {
                method:'PUT',
                headers:{'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},
                body:JSON.stringify(payload),
            });
            const data = await response.json().catch(()=>({}));
            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(firstError || data.message || 'Không lưu được cấu hình.');
            }
            currentSettings = data.settings;
            message.classList.add('ok');
            message.textContent = data.message || 'Đã lưu.';
            updateSummary();
            updatePreviewLocal();
        } catch (error) {
            message.classList.add('err');
            message.textContent = error.message || 'Có lỗi khi lưu.';
        } finally {
            saveButton.disabled = false;
        }
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
    }

    root.querySelector('[data-bf-add-tier]').addEventListener('click', () => {
        const tiers = normalizeTiers();
        if (tiers.length >= 10) return;
        if (tiers.length) {
            const last = tiers[tiers.length - 1];
            const prev = tiers.length > 1 ? Number(tiers[tiers.length - 2].max_cups || 0) : 0;
            last.max_cups = Math.max(prev + 5, prev + 1);
        }
        const lastFee = Number(tiers[tiers.length - 1]?.per_km_fee || 8000);
        tiers.push({max_cups:null, per_km_fee:lastFee});
        renderTiers(tiers);
    });

    tiersBox.addEventListener('click', e => {
        const button = e.target.closest('[data-tier-remove]');
        if (!button) return;
        const rows = [...tiersBox.querySelectorAll('.bf-tier')];
        if (rows.length <= 1) return;
        button.closest('.bf-tier').remove();
        const tiers = normalizeTiers();
        if (tiers.length) tiers[tiers.length - 1].max_cups = null;
        renderTiers(tiers);
    });
    tiersBox.addEventListener('input', () => { refreshTierLabels(); updatePreviewLocal(); });
    freeKmInput.addEventListener('input', updatePreviewLocal);
    fastInput.addEventListener('input', () => { if (currentSettings) { currentSettings.fast_surcharge = Number(fastInput.value||0); updateSummary(); } });
    previewDistance.addEventListener('input', updatePreviewLocal);
    previewCups.addEventListener('input', updatePreviewLocal);
    saveButton.addEventListener('click', saveSettings);
    branchSelect.addEventListener('change', async () => {
        try { await loadSettings(branchSelect.value); }
        catch (error) { message.className='bf-message err'; message.textContent=error.message; }
    });

    (async () => {
        try {
            const firstBranchId = await loadBranches();
            branchSelect.value = String(firstBranchId);
            await loadSettings(firstBranchId);
            const legacy = legacyFeeCard();
            if (legacy && legacy !== root && !root.contains(legacy)) {
                legacy.style.display = 'none';
                legacy.setAttribute('data-branch-fee-v27-legacy-hidden', 'true');
                legacy.parentNode.insertBefore(root, legacy);
            }
            root.hidden = false;
            setOpen(false);
        } catch (error) {
            root.hidden = false;
            summary.textContent = error.message || 'Không tải được cấu hình.';
            message.className='bf-message err';
            message.textContent=error.message || '';
        }
    })();
});
</script>
