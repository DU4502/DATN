@extends(auth()->user()?->preferredAdminLayout() ?? 'layouts.admin')
@section('title', 'Khiếu nại đơn hàng')
@section('page-title', 'Khiếu nại đơn hàng')
@section('content')
@php
    $rootMode = auth()->user()?->isSuperAdmin() && ! auth()->user()?->isViewingAdminWorkspace();
    $cskhMode = auth()->user()?->isCskh();
    $issueRoute = fn (string $action) => $cskhMode
        ? 'admin.chat.order-issues.'.$action
        : ($rootMode ? 'admin.super-admin.manage.order-issues.'.$action : 'admin.order-issues.'.$action);
    $types = ['missing_item' => 'Thiếu món', 'wrong_item' => 'Sai món', 'quality_issue' => 'Chất lượng đồ uống', 'other' => 'Vấn đề khác'];
    $statuses = ['open' => ['Đang chờ', 'warning'], 'processing' => ['Đang xử lý', 'primary'], 'awaiting_confirmation' => ['Chờ khách xác nhận', 'info'], 'resolved' => ['Hoàn tất', 'success'], 'rejected' => ['Từ chối', 'danger']];
    $nextStatuses = ['open' => ['open', 'processing', 'rejected'], 'processing' => ['processing', 'awaiting_confirmation', 'rejected'], 'awaiting_confirmation' => ['awaiting_confirmation', 'processing'], 'resolved' => ['resolved'], 'rejected' => ['rejected']];
@endphp
<style>
    .issue-board{max-width:1320px;margin:auto}.issue-card{background:#fff;border:1px solid #e1ebe7;border-radius:16px;padding:18px;margin-bottom:14px;box-shadow:0 5px 18px rgba(18,66,52,.04)}.issue-grid{display:grid;grid-template-columns:1.15fr 1fr 1.25fr 360px;gap:18px;align-items:start}.issue-label{font-size:.72rem;letter-spacing:.04em;color:#71817b;text-transform:uppercase;font-weight:800;margin-bottom:6px}.issue-value{font-size:1rem;font-weight:750;color:#16231f}.issue-meta{font-size:.88rem;color:#65756f;margin-top:5px}.order-box,.issue-desc,.issue-action,.issue-result{border:1px solid #e1ece8;background:#f8fbfa;border-radius:12px}.order-box{margin-top:12px;padding:10px 12px}.order-row{display:flex;justify-content:space-between;gap:8px;padding:7px 0;border-bottom:1px dashed #d7e7e1;font-size:.88rem}.order-row:last-of-type{border-bottom:0}.order-row .muted{font-size:.78rem;color:#70817a}.order-total{display:flex;justify-content:space-between;padding-top:9px;margin-top:2px;border-top:1px solid #dceae5;font-weight:800}.issue-desc{padding:12px;line-height:1.5;min-height:64px}.issue-evidence{display:inline-flex;align-items:center;gap:6px;margin-top:10px;color:#008b70;font-weight:700;text-decoration:none}.issue-action{padding:13px}.issue-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.issue-action select,.issue-action input{border-radius:9px;font-size:.88rem}.issue-action .full{grid-column:1/-1}.issue-help{font-size:.78rem;color:#6b7b75;line-height:1.35}.issue-help.primary{color:#008b70}.issue-action button{margin-top:10px}.issue-result{padding:14px;background:linear-gradient(145deg,#f4fcf9,#fff);border-color:#cfe9df}.issue-result-head{display:flex;align-items:center;gap:10px;padding-bottom:11px;margin-bottom:11px;border-bottom:1px solid #dcece6}.issue-result-icon{width:34px;height:34px;border-radius:50%;display:grid;place-items:center;background:#dff7ed;color:#078568;font-size:1.05rem;flex:0 0 auto}.issue-result-title{font-weight:800;color:#153d32}.issue-result-method{font-size:.92rem;font-weight:750;color:#1b2e28}.issue-result-voucher{margin-top:9px;padding:10px 11px;border-radius:10px;background:#eaf9f4;border:1px dashed #9fd6c5}.issue-result-code{font-weight:850;color:#087b63;overflow-wrap:anywhere}.issue-result-note{margin-top:9px;color:#566a63;font-size:.82rem;line-height:1.45}.issue-result-foot{display:flex;align-items:center;gap:5px;margin-top:10px;color:#75857f;font-size:.74rem}.issue-result.is-rejected{background:linear-gradient(145deg,#fff7f7,#fff);border-color:#f2cccc}.issue-result.is-rejected .issue-result-head{border-bottom-color:#f2d9d9}.issue-result.is-rejected .issue-result-icon{background:#fee2e2;color:#dc3545}.issue-result.is-rejected .issue-result-title{color:#9f2634}.issue-rejection-box{margin-top:9px;padding:10px 11px;border-radius:10px;background:#fff1f2;border:1px solid #f4c5cb}.issue-rejection-box .issue-result-method{color:#8f2632;overflow-wrap:anywhere}@media(max-width:1150px){.issue-grid{grid-template-columns:repeat(2,1fr)}.issue-action,.issue-result{grid-column:span 2}}@media(max-width:680px){.issue-grid,.issue-form-grid{grid-template-columns:1fr}.issue-action,.issue-result{grid-column:auto}.issue-action .full{grid-column:auto}}
    .order-box{max-height:none;overflow:visible}.order-total{position:static;background:transparent}.order-more{border:0;background:transparent;color:#008b70;font-size:.82rem;font-weight:700;padding:7px 0 2px;cursor:pointer}.redelivery-items{grid-column:1/-1;padding:10px;border:1px solid #dbe9e4;border-radius:10px;background:#fff}.redelivery-item{display:grid;grid-template-columns:1fr 72px;align-items:center;gap:8px;padding:6px 0}.redelivery-item+.redelivery-item{border-top:1px dashed #dce8e4}.redelivery-item input{min-width:0}
</style>
<div class="container-fluid py-4 issue-board">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4"><div><p class="text-primary fw-semibold mb-1">HỖ TRỢ KHÁCH HÀNG</p><h1 class="h3 fw-bold mb-1">Khiếu nại & hỗ trợ đơn hàng</h1><p class="text-secondary mb-0">Tiếp nhận phản hồi từ khách, kiểm tra đơn và chọn phương án hỗ trợ phù hợp.</p></div><span class="badge rounded-pill text-bg-primary px-3 py-2">{{ $reports->total() }} yêu cầu</span></div>
    @if(session('success'))<div class="alert alert-success border-0 rounded-4">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="alert alert-info border-0 rounded-4"><i class="bi bi-info-circle me-1"></i>{{ session('info') }}</div>@endif
    @if($errors->any())
        <div class="alert alert-danger border-0 rounded-4" role="alert" data-issue-validation-summary>
            <strong><i class="bi bi-exclamation-triangle me-1"></i>Chưa thể lưu phương án:</strong>
            <ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @forelse($reports as $issue)
        @php [$statusLabel, $statusColor] = $statuses[$issue->status] ?? ['Đang chờ', 'secondary']; $availableStatuses = $nextStatuses[$issue->status] ?? [$issue->status]; @endphp
        <article class="issue-card"><div class="issue-grid">
            <section><div class="issue-label">Đơn hàng</div><div class="issue-value">{{ $issue->order->order_code ?? '#'.$issue->order_id }}</div><div class="issue-meta"><i class="bi bi-shop me-1"></i>{{ $issue->order->branch?->name ?? 'Chưa xác định chi nhánh' }}</div><div class="issue-meta"><i class="bi bi-credit-card me-1"></i><strong>{{ $issue->order->payment_method === 'vnpay' ? 'VNPay' : 'Tiền mặt (COD)' }}</strong> · {{ $issue->order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' }}</div>@if($issue->order->payment_method === 'vnpay' && $issue->order->vnpay_transaction_id)<div class="issue-meta">Mã GD: {{ $issue->order->vnpay_transaction_id }}</div>@endif<div class="issue-meta"><i class="bi bi-clock me-1"></i>{{ $issue->created_at->format('d/m/Y H:i') }}</div><div class="order-box"><div class="issue-label">Món trong đơn</div>@forelse($issue->order->orderItems as $item)<div class="order-row"><div><strong>{{ $item->quantity }}× {{ $item->product?->name ?? 'Sản phẩm đã xoá' }}</strong><div class="muted">{{ $item->productSize?->size_name ?? $item->productSize?->name ?? '' }}{{ $item->sugar_level !== null ? ' · Đường '.$item->sugar_level.'%' : '' }}{{ $item->ice_level !== null ? ' · Đá '.$item->ice_level.'%' : '' }}</div></div><strong>{{ number_format((int) $item->getSubtotal(), 0, ',', '.') }}đ</strong></div>@empty<div class="issue-meta">Không tìm thấy món trong đơn.</div>@endforelse<div class="order-total"><span>Tổng đơn</span><span class="text-primary">{{ number_format((int) $issue->order->total, 0, ',', '.') }}đ</span></div></div></section>
            <section><div class="issue-label">Khách hàng</div><div class="issue-value">{{ $issue->user->name }}</div><div class="issue-meta">{{ $issue->user->email }}</div><span class="badge text-bg-{{ $statusColor }} mt-3">{{ $statusLabel }}</span></section>
            <section><div class="issue-label">{{ $types[$issue->type] ?? 'Vấn đề khác' }}</div><div class="issue-desc">{{ $issue->description }}</div>@php($evidenceCount = count($issue->evidence_files ?? ($issue->evidence_path ? [['path' => $issue->evidence_path]] : [])))@if($evidenceCount)<div class="d-flex flex-wrap gap-2 mt-2">@for($fileIndex = 0; $fileIndex < $evidenceCount; $fileIndex++)<a class="issue-evidence mt-0" href="{{ route($issueRoute('evidence'), ['issue' => $issue, 'file' => $fileIndex]) }}" target="_blank"><i class="bi bi-image"></i>Ảnh {{ $fileIndex + 1 }}</a>@endfor</div>@endif</section>
            @if($issue->status === 'resolved')
                @php($resolutionLabels = ['redelivery' => 'Giao bù / đổi món', 'voucher' => 'Voucher toàn bộ đơn', 'other' => 'Phương án khác'])
                <section class="issue-result" aria-label="Kết quả xử lý khiếu nại">
                    <div class="issue-result-head"><span class="issue-result-icon"><i class="bi bi-check2"></i></span><div><div class="issue-result-title">Đã hoàn tất hỗ trợ</div><div class="issue-help">Không cần thao tác thêm</div></div></div>
                    <div class="issue-label">Phương án đã thực hiện</div>
                    <div class="issue-result-method">{{ $resolutionLabels[$issue->resolution_type] ?? 'Đã xử lý theo thỏa thuận' }}</div>
                    @if($issue->resolution_type === 'voucher' && $issue->voucher)
                        <div class="issue-result-voucher"><div class="issue-help">Voucher đã cấp cho khách</div><div class="issue-result-code">{{ $issue->voucher->code }}</div><div class="issue-meta mt-1">Giá trị {{ $issue->voucher->formattedValue() }} · HSD {{ optional($issue->voucher->expires_at)->format('d/m/Y') ?: 'không giới hạn' }}</div></div>
                    @elseif($issue->resolution_value)
                        <div class="issue-result-note">{{ $issue->resolution_value }}</div>
                    @endif
                    @if($issue->admin_note)<div class="issue-result-note"><strong>Phản hồi:</strong> {{ $issue->admin_note }}</div>@endif
                    <div class="issue-result-foot"><i class="bi bi-person-check"></i><span>{{ $issue->handler?->name ?? 'Quản trị viên' }}</span>@if($issue->resolved_at)<span>· {{ $issue->resolved_at->format('d/m/Y H:i') }}</span>@endif</div>
                </section>
            @elseif($issue->status === 'rejected')
                <section class="issue-result is-rejected" aria-label="Kết quả từ chối yêu cầu hỗ trợ">
                    <div class="issue-result-head"><span class="issue-result-icon"><i class="bi bi-x-lg"></i></span><div><div class="issue-result-title">Yêu cầu đã bị từ chối</div><div class="issue-help">Quy trình xử lý đã kết thúc</div></div></div>
                    <div class="issue-label">Kết luận xử lý</div>
                    <div class="issue-rejection-box"><div class="issue-result-method">{{ $issue->resolution_value ?: 'Yêu cầu không đủ điều kiện hỗ trợ.' }}</div></div>
                    <div class="issue-result-note"><strong>Phản hồi gửi khách:</strong><br>{{ $issue->admin_note ?: 'Yêu cầu đã được kiểm tra nhưng chưa đủ điều kiện để áp dụng phương án hỗ trợ.' }}</div>
                    <div class="issue-result-foot"><i class="bi bi-person-x"></i><span>{{ $issue->handler?->name ?? 'Quản trị viên' }}</span>@if($issue->rejected_at)<span>· {{ $issue->rejected_at->format('d/m/Y H:i') }}</span>@endif</div>
                </section>
            @else
                <form class="issue-action" method="POST" action="{{ route($issueRoute('update'), $issue) }}" data-order-total="{{ (int) $issue->order->total }}" data-voucher-issued="{{ $issue->voucher_coupon_id ? '1' : '0' }}" data-redelivery-created="{{ $issue->redelivery_order_id ? '1' : '0' }}">
                    @csrf @method('PATCH')
                    <div class="issue-label">Phương án hỗ trợ</div>
                    <div class="issue-form-grid">
                        <select name="status" class="form-select js-issue-status">@foreach($availableStatuses as $availableStatus)<option value="{{ $availableStatus }}" @selected($issue->status === $availableStatus)>{{ $statuses[$availableStatus][0] }}</option>@endforeach</select>
                        <select name="resolution_type" class="form-select js-resolution-type"><option value="">Chọn cách hỗ trợ</option><option value="redelivery" @selected($issue->resolution_type==='redelivery')>Giao bù / đổi món</option><option value="voucher" @selected($issue->resolution_type==='voucher')>Voucher toàn bộ đơn</option><option value="other" @selected($issue->resolution_type==='other')>Phương án khác</option></select>
                        <input name="resolution_value" maxlength="255" class="form-control full js-resolution-value" value="{{ $issue->resolution_value }}" placeholder="Nội dung phương án hỗ trợ">
                        <input type="datetime-local" name="estimated_at" min="{{ now()->addMinute()->format('Y-m-d\\TH:i') }}" class="form-control full js-estimated-at" value="{{ $issue->estimated_at?->format('Y-m-d\\TH:i') }}">
                        <div class="redelivery-items js-redelivery-items d-none">
                            <div class="issue-help fw-bold mb-1">Chọn món và số lượng cần giao bù</div>
                            @foreach($issue->order->orderItems as $redeliveryItem)
                                <label class="redelivery-item"><span>{{ $redeliveryItem->product?->name ?? 'Sản phẩm' }} <small class="text-secondary">(tối đa {{ $redeliveryItem->quantity }})</small></span><input type="number" name="redelivery_items[{{ $redeliveryItem->id }}]" min="0" max="{{ $redeliveryItem->quantity }}" value="0" class="form-control form-control-sm js-redelivery-quantity" aria-label="Số lượng giao bù {{ $redeliveryItem->product?->name }}"></label>
                            @endforeach
                        </div>
                        <div class="text-danger small full d-none js-form-error" role="alert"></div>
                        <div class="issue-help primary full js-redelivery-hint d-none"><i class="bi bi-truck me-1"></i>Chọn thời gian dự kiến giao bù và mô tả rõ món cần giao.</div>
                        <div class="issue-help primary full js-voucher-hint d-none"><i class="bi bi-lightning-charge-fill me-1"></i>Voucher tự lấy toàn bộ tổng tiền đơn.</div>
                        <input name="admin_note" class="form-control full js-admin-note" value="{{ $issue->admin_note }}" placeholder="Phản hồi chi tiết cho khách">
                    </div>
                    <div class="issue-help mt-2 js-voucher-account-help">Sau khi lưu phương án, yêu cầu sẽ chờ khách xác nhận rồi mới hoàn tất.</div>
                    @if($issue->redeliveryOrder)<div class="alert alert-info py-2 px-3 mt-2 mb-0"><i class="bi bi-truck me-1"></i>Đơn giao bù <strong>{{ $issue->redeliveryOrder->displayCode() }}</strong> · {{ \App\Support\OrderStatus::label((string) $issue->redeliveryOrder->status) }}</div>@endif
                    <button class="btn btn-primary btn-sm w-100 rounded-pill"><i class="bi bi-check2-circle me-1"></i>Lưu phương án</button>
                </form>
            @endif
        </div></article>
    @empty
        <div class="text-center bg-white border rounded-4 py-5 text-secondary"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Chưa có yêu cầu hỗ trợ.</div>
    @endforelse
    <div class="mt-3">{{ $reports->links() }}</div>
</div>
<script>
document.querySelectorAll('.issue-action').forEach((form) => {
    const status=form.querySelector('.js-issue-status'), type=form.querySelector('.js-resolution-type'), value=form.querySelector('.js-resolution-value'), estimated=form.querySelector('.js-estimated-at'), note=form.querySelector('.js-admin-note'), formError=form.querySelector('.js-form-error'), voucherHint=form.querySelector('.js-voucher-hint'), redeliveryHint=form.querySelector('.js-redelivery-hint'), redeliveryItems=form.querySelector('.js-redelivery-items'), quantities=[...form.querySelectorAll('.js-redelivery-quantity')], accountHelp=form.querySelector('.js-voucher-account-help');
    const sync=()=>{
        const rejected=status.value==='rejected', awaiting=status.value==='awaiting_confirmation';
        if(rejected) type.value='';
        type.disabled=rejected;
        value.readOnly=!rejected&&type.value==='voucher';
        value.placeholder=rejected?'Nêu rõ kết luận hoặc lý do từ chối':'Nội dung phương án hỗ trợ';
        note.placeholder=rejected?'Phản hồi giải thích rõ cho khách':'Phản hồi chi tiết cho khách';
        const voucher=!rejected&&type.value==='voucher', redelivery=!rejected&&type.value==='redelivery';
        type.required=awaiting; value.required=awaiting&&!voucher; estimated.required=awaiting&&redelivery;
        voucherHint.classList.toggle('d-none',!voucher); redeliveryHint.classList.toggle('d-none',!redelivery);
        estimated.classList.toggle('d-none',!redelivery); estimated.disabled=!redelivery;
        const needsItems=redelivery&&form.dataset.redeliveryCreated!=='1';
        redeliveryItems.classList.toggle('d-none',!needsItems); quantities.forEach((input)=>input.disabled=!needsItems);
        accountHelp.classList.toggle('d-none',rejected); formError.classList.add('d-none'); formError.textContent='';
        if(voucher&&form.dataset.voucherIssued!=='1') value.value=`${Number(form.dataset.orderTotal||0).toLocaleString('vi-VN')}đ (toàn bộ giá trị đơn)`;
    };
    const validate=()=>{
        if(status.value!=='awaiting_confirmation') return true;
        if(!type.value){formError.textContent='Vui lòng chọn phương án hỗ trợ.';type.focus();return false}
        if(type.value!=='voucher'&&!value.value.trim()){formError.textContent='Vui lòng nhập nội dung phương án hỗ trợ.';value.focus();return false}
        if(type.value==='redelivery'&&form.dataset.redeliveryCreated!=='1'&&!quantities.some((input)=>Number(input.value)>0)){formError.textContent='Vui lòng chọn ít nhất một món cần giao bù.';quantities[0]?.focus();return false}
        if(type.value==='redelivery'&&!estimated.value){formError.textContent='Vui lòng chọn thời gian dự kiến giao bù.';estimated.focus();return false}
        return true;
    };
    form.addEventListener('submit',(event)=>{if(validate())return;event.preventDefault();formError.classList.remove('d-none');formError.scrollIntoView({behavior:'smooth',block:'center'});});
    status.addEventListener('change',sync); type.addEventListener('change',sync); value.addEventListener('input',()=>formError.classList.add('d-none')); estimated.addEventListener('input',()=>formError.classList.add('d-none')); quantities.forEach((input)=>input.addEventListener('input',()=>formError.classList.add('d-none'))); sync();
});
document.querySelectorAll('.order-box').forEach((box) => { const rows=[...box.querySelectorAll('.order-row')]; if(rows.length<=2)return; rows.slice(2).forEach((row)=>row.hidden=true); const button=document.createElement('button'); button.type='button'; button.className='order-more'; button.textContent=`Xem thêm ${rows.length-2} món`; button.addEventListener('click',()=>{const expanded=button.dataset.expanded==='1'; rows.slice(2).forEach((row)=>row.hidden=expanded); button.dataset.expanded=expanded?'0':'1'; button.textContent=expanded?`Xem thêm ${rows.length-2} món`:'Thu gọn danh sách';}); box.querySelector('.order-total').before(button); });
</script>
@endsection
