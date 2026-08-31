@extends('layouts.client')

@section('title', 'Yêu cầu hỗ trợ đơn hàng')

@section('content')
<section class="py-5">
    <div class="container" style="max-width: 760px;">
        <a href="{{ route('orders.index') }}" class="text-primary text-decoration-none fw-semibold"><i class="bi bi-arrow-left me-1"></i>Quay lại đơn hàng</a>
        <div class="card border-0 shadow-sm rounded-4 mt-3"><div class="card-body p-4 p-md-5">
            <p class="text-primary fw-semibold mb-1">Hỗ trợ đơn hàng</p>
            <h1 class="h3 fw-bold">{{ $reports->isNotEmpty() ? 'Tiến trình xử lý yêu cầu' : 'Báo vấn đề cho đơn' }} {{ $order->order_code ?? '#'.$order->id }}</h1>
            <p class="text-secondary">{{ $reports->isNotEmpty() ? 'Yêu cầu đã được tiếp nhận. Bạn có thể theo dõi trạng thái xử lý tại đây.' : 'Gửi mô tả để nhân viên kiểm tra: thiếu món, sai món hoặc chất lượng đồ uống. Yêu cầu chỉ được gửi trong ngày đơn hoàn thành.' }}</p>
            @if(session('success'))<div class="alert alert-success rounded-4 border-0"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>@endif

            @if($reports->isEmpty())
                <form method="POST" action="{{ route('orders.issues.store', $order) }}" class="mt-4" enctype="multipart/form-data" data-evidence-form>
                    @csrf
                    <div class="mb-3"><label class="form-label fw-semibold">Loại vấn đề</label><select name="type" class="form-select @error('type') is-invalid @enderror" required><option value="" disabled hidden @selected(!old('type'))>Chọn vấn đề</option><option value="missing_item" @selected(old('type') === 'missing_item')>Thiếu món</option><option value="wrong_item" @selected(old('type') === 'wrong_item')>Sai món</option><option value="quality_issue" @selected(old('type') === 'quality_issue')>Chất lượng đồ uống chưa tốt</option><option value="other" @selected(old('type') === 'other')>Vấn đề khác</option></select>@error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="mb-4"><label for="issue-description" class="form-label fw-semibold">Mô tả chi tiết</label><textarea id="issue-description" name="description" rows="5" minlength="10" maxlength="1500" class="form-control @error('description') is-invalid @enderror" required data-issue-description>{{ old('description') }}</textarea><div class="d-flex justify-content-between gap-3 mt-1"><div class="form-text mt-0" data-description-help>Tối thiểu 10 ký tự.</div><div class="form-text mt-0 text-nowrap"><span data-description-count>{{ mb_strlen(old('description', '')) }}</span>/1.500</div></div><div class="text-danger small mt-1 d-none" data-description-error></div>@error('description')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror</div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Ảnh bằng chứng <span class="text-danger">*</span> <span class="text-secondary fw-normal">(chọn ít nhất 2 ảnh, tối đa 3 ảnh)</span></label>
                        <div class="evidence-upload-grid">
                            @for($slot = 0; $slot < 3; $slot++)
                                <label class="evidence-upload-slot" for="evidence-{{ $slot }}">
                                    <input id="evidence-{{ $slot }}" type="file" name="evidence[]" accept="image/jpeg,image/png,image/webp" data-evidence-input>
                                    <span class="evidence-upload-icon"><i class="bi bi-image"></i></span>
                                    <strong data-evidence-label>Chọn ảnh {{ $slot + 1 }}</strong>
                                    <small>Nhấn để chọn tệp</small>
                                </label>
                            @endfor
                        </div>
                        <div class="form-text">Ảnh món nhận được hoặc hóa đơn; JPG, PNG, WEBP, tối đa 5 MB mỗi ảnh.</div>
                        <div class="text-danger small mt-2 d-none" data-evidence-error><i class="bi bi-exclamation-circle me-1"></i>Vui lòng chọn ít nhất 2 ảnh bằng chứng trước khi gửi yêu cầu.</div>
                        @error('evidence')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('evidence.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-primary rounded-pill px-4"><i class="bi bi-send me-1"></i>Gửi yêu cầu</button>
                </form>
                <style>
                    .evidence-upload-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; }
                    .evidence-upload-slot { min-height:126px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:5px; padding:14px; border:1px dashed #9dccc0; border-radius:14px; color:#176d5c; background:#f7fcfa; cursor:pointer; text-align:center; transition:.18s ease; }
                    .evidence-upload-slot:hover { border-color:#009a7b; background:#edfaf6; transform:translateY(-1px); }
                    .evidence-upload-slot input { position:absolute; width:1px; height:1px; opacity:0; pointer-events:none; }
                    .evidence-upload-icon { display:grid; place-items:center; width:38px; height:38px; border-radius:11px; color:#008b70; background:#dff5ee; font-size:1.2rem; }
                    .evidence-upload-slot strong { color:#26342f; font-size:.9rem; }
                    .evidence-upload-slot small { color:#71817b; font-size:.76rem; }
                    .evidence-upload-slot.is-selected { border-style:solid; border-color:#00a17f; background:#edfaf6; }
                    @media (max-width:575px) { .evidence-upload-grid { grid-template-columns:1fr; } .evidence-upload-slot { min-height:82px; flex-direction:row; justify-content:flex-start; text-align:left; } }
                </style>
                <script>
                    const evidenceForm = document.querySelector('[data-evidence-form]');
                    const evidenceInputs = [...document.querySelectorAll('[data-evidence-input]')];
                    const evidenceError = document.querySelector('[data-evidence-error]');
                    const descriptionInput = document.querySelector('[data-issue-description]');
                    const descriptionCount = document.querySelector('[data-description-count]');
                    const descriptionHelp = document.querySelector('[data-description-help]');
                    const descriptionError = document.querySelector('[data-description-error]');
                    const selectedEvidenceCount = () => evidenceInputs.filter((input) => input.files?.length).length;
                    const refreshEvidenceError = () => evidenceError?.classList.toggle('d-none', selectedEvidenceCount() >= 2);
                    const refreshDescription = (showError = false) => {
                        const length = [...String(descriptionInput?.value || '').trim()].length;
                        if (descriptionCount) descriptionCount.textContent = String(length);
                        const missing = Math.max(0, 10 - length);
                        if (descriptionHelp) descriptionHelp.textContent = missing > 0 ? `Cần nhập thêm ${missing} ký tự.` : 'Nội dung mô tả đã hợp lệ.';
                        descriptionInput?.classList.toggle('is-invalid', showError && missing > 0);
                        if (descriptionError) {
                            descriptionError.textContent = missing > 0 ? `Mô tả chi tiết phải có ít nhất 10 ký tự (còn thiếu ${missing} ký tự).` : '';
                            descriptionError.classList.toggle('d-none', !showError || missing === 0);
                        }
                        return missing === 0;
                    };

                    descriptionInput?.addEventListener('input', () => refreshDescription(true));
                    refreshDescription(false);

                    evidenceInputs.forEach((input) => {
                        input.addEventListener('change', () => {
                            const slot = input.closest('.evidence-upload-slot');
                            const label = slot?.querySelector('[data-evidence-label]');
                            const file = input.files?.[0];
                            if (!slot || !label || !file) return;
                            slot.classList.add('is-selected');
                            label.textContent = file.name.length > 20 ? `${file.name.slice(0, 17)}...` : file.name;
                            refreshEvidenceError();
                        });
                    });
                    evidenceForm?.addEventListener('submit', (event) => {
                        if (!refreshDescription(true)) {
                            event.preventDefault();
                            descriptionInput?.focus();
                            descriptionInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            return;
                        }
                        if (selectedEvidenceCount() >= 2) return;
                        event.preventDefault();
                        refreshEvidenceError();
                        evidenceError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                    evidenceForm?.addEventListener('submit', () => {
                        evidenceInputs.filter((input) => !input.files?.length).forEach((input) => input.removeAttribute('name'));
                    });
                </script>
            @else
                @foreach($reports as $report)
                    @php
                        $level = match ($report->status) {
                            'resolved' => 4,
                            'awaiting_confirmation' => 3,
                            'processing' => 2,
                            default => 1,
                        };
                        $rejected = $report->status === 'rejected';
                        $steps = [
                            ['Tiếp nhận yêu cầu', 'Yêu cầu của bạn đã được gửi thành công.', 'bi-inbox', $report->received_at ?? $report->created_at],
                            ['Đang xử lý', 'Nhân viên đang kiểm tra và thực hiện phương án hỗ trợ.', 'bi-gear', $report->processing_at],
                            ['Chờ bạn xác nhận', 'Kiểm tra phương án hỗ trợ và xác nhận khi đã nhận đủ quyền lợi.', 'bi-person-check', $report->approved_at],
                            ['Hoàn tất xử lý', 'Bạn đã xác nhận và yêu cầu hỗ trợ đã hoàn tất.', 'bi-check2-circle', $report->resolved_at],
                        ];
                    @endphp
                    <div class="rounded-4 p-3 p-md-4 mt-4 js-issue-card" data-issue-id="{{ $report->id }}" style="background:#f6fbf9;border:1px solid #dcefe8;">
                        @php $statusTime = match ($report->status) {'processing' => $report->processing_at, 'awaiting_confirmation' => $report->approved_at, 'resolved' => $report->resolved_at, 'rejected' => $report->rejected_at, default => $report->received_at ?? $report->created_at}; $statusLabel = ['open' => 'Đang chờ xử lý', 'processing' => 'Đang xử lý', 'awaiting_confirmation' => 'Chờ bạn xác nhận', 'resolved' => 'Hoàn tất', 'rejected' => 'Không được chấp nhận'][$report->status] ?? 'Đang chờ xử lý'; @endphp
                        @if($report->resolution_type === 'redelivery' && $report->redeliveryOrder && \App\Support\OrderStatus::normalize((string) $report->redeliveryOrder->status) !== \App\Support\OrderStatus::COMPLETED)@php($statusLabel = 'Đang thực hiện giao bù')@endif
                        <div class="d-flex justify-content-between gap-3 mb-3"><div><strong>{{ ucfirst(str_replace('_', ' ', $report->type)) }}</strong><div class="small text-secondary mt-1">Gửi lúc {{ $report->created_at->format('d/m/Y H:i') }}</div></div><div class="text-end"><span class="badge rounded-pill px-3 py-2 js-issue-status {{ $rejected ? 'text-bg-danger' : ($report->status === 'resolved' ? 'text-bg-success' : 'text-bg-warning') }}">{{ $statusLabel }}</span><div class="small text-secondary mt-2 js-issue-time {{ $statusTime ? '' : 'd-none' }}"><i class="bi bi-clock me-1"></i>Cập nhật lúc <span>{{ $statusTime?->format('d/m/Y H:i') }}</span></div></div></div>
                        <p class="mb-3"><i class="bi bi-chat-square-text text-primary me-1"></i>{{ $report->description }}</p>
                        <div class="alert alert-info mt-3 js-resolution {{ $report->resolution_type ? '' : 'd-none' }}"><strong><i class="bi bi-gift me-1"></i>Phương án hỗ trợ:</strong> <span class="js-resolution-type">{{ ['redelivery' => 'Giao bù / đổi đúng món', 'voucher' => 'Tặng voucher bù', 'other' => 'Phương án khác'][$report->resolution_type] ?? 'Phương án khác' }}</span><span class="js-resolution-value">{{ $report->resolution_value ? ' — '.$report->resolution_value : '' }}</span></div>
                        @if($report->resolution_type === 'redelivery' && $report->estimated_at)<div class="alert alert-warning py-2"><i class="bi bi-truck me-1"></i>Dự kiến giao bù: <strong>{{ $report->estimated_at->format('d/m/Y H:i') }}</strong></div>@endif
                        @if($report->redeliveryOrder)
                            <div class="border rounded-4 bg-white p-3 mt-3 js-redelivery-order">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2"><div><div class="small text-secondary">Đơn giao bù miễn phí</div><strong>{{ $report->redeliveryOrder->displayCode() }}</strong><div class="small text-primary fw-semibold mt-1 js-redelivery-status">{{ \App\Support\OrderStatus::label((string) $report->redeliveryOrder->status) }}</div></div><a href="{{ route('orders.track', $report->redeliveryOrder) }}" class="btn btn-outline-primary btn-sm rounded-pill"><i class="bi bi-geo-alt me-1"></i>Theo dõi đơn giao bù</a></div>
                            </div>
                        @endif
                        @if($rejected)
                            <div class="alert alert-danger mb-0"><i class="bi bi-x-circle me-1"></i>Yêu cầu chưa được chấp nhận.@if($report->admin_note) {{ $report->admin_note }} @endif</div>
                        @else
                            <div class="d-grid gap-3">
                                @foreach($steps as $index => $step)
                                    <div class="d-flex gap-3 align-items-start js-issue-step" data-step="{{ $index + 1 }}"><span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 {{ $level >= $index + 1 ? 'bg-success text-white' : 'bg-light text-secondary' }}" style="width:32px;height:32px"><i class="bi {{ $level > $index + 1 ? 'bi-check-lg' : $step[2] }}"></i></span><div><strong class="{{ $level >= $index + 1 ? 'text-dark' : 'text-secondary' }}">{{ $step[0] }}</strong><div class="small text-secondary">{{ $step[1] }}</div>@if($step[3])<div class="small fw-semibold text-primary mt-1"><i class="bi bi-clock me-1"></i>{{ $step[3]->format('d/m/Y H:i') }}</div>@endif</div></div>
                                @endforeach
                            </div>
                            @if($report->admin_note)
                                <div class="alert alert-info mt-3 mb-0"><strong>Phản hồi từ nhân viên:</strong> {{ $report->admin_note }}</div>
                            @endif
                            @php($canConfirmSupport = $report->status === 'awaiting_confirmation' && ($report->resolution_type !== 'redelivery' || \App\Support\OrderStatus::normalize((string) $report->redeliveryOrder?->status) === \App\Support\OrderStatus::COMPLETED))
                            <form method="POST" action="{{ route('orders.issues.confirm', [$order, $report]) }}" class="mt-3 js-confirm-resolution {{ $canConfirmSupport ? '' : 'd-none' }}">@csrf<button class="btn btn-success rounded-pill px-4"><i class="bi bi-check2-circle me-1"></i>Xác nhận tôi đã nhận hỗ trợ</button><div class="small text-secondary mt-2">Chỉ xác nhận sau khi bạn đã nhận voucher, món giao bù hoặc phương án đã thỏa thuận.</div></form>
                        @endif
                    </div>
                @endforeach
            @endif
        </div></div>
    </div>
</section>
@endsection

@if($reports->isNotEmpty())
@push('scripts')
<script>
(() => {
    const labels = {open: 'Đang chờ xử lý', processing: 'Đang xử lý', awaiting_confirmation: 'Chờ bạn xác nhận', resolved: 'Hoàn tất', rejected: 'Không được chấp nhận'};
    const times = {processing: 'processing_at', awaiting_confirmation: 'approved_at', resolved: 'resolved_at', rejected: 'rejected_at'};
    const levels = {open: 1, processing: 2, awaiting_confirmation: 3, resolved: 4};
    const resolutionLabels = {redelivery: 'Giao bù / đổi đúng món', voucher: 'Tặng voucher bù', other: 'Phương án khác'};
    const refreshIssues = async () => {
        try {
            const response = await fetch('{{ route('orders.issues.status', $order) }}', {headers: {Accept: 'application/json'}});
            if (!response.ok) return;
            const {reports} = await response.json();
            reports.forEach((report) => {
                const card = document.querySelector(`.js-issue-card[data-issue-id="${report.id}"]`);
                if (!card) return;
                const badge = card.querySelector('.js-issue-status');
                badge.textContent = report.display_status_label || labels[report.status] || labels.open;
                badge.className = `badge rounded-pill px-3 py-2 js-issue-status ${report.status === 'rejected' ? 'text-bg-danger' : (report.status === 'resolved' ? 'text-bg-success' : 'text-bg-warning')}`;
                const time = report[times[report.status]] || '';
                const timeBox = card.querySelector('.js-issue-time');
                timeBox.classList.toggle('d-none', !time);
                timeBox.querySelector('span').textContent = time;
                const resolution = card.querySelector('.js-resolution');
                resolution.classList.toggle('d-none', !report.resolution_type);
                resolution.querySelector('.js-resolution-type').textContent = resolutionLabels[report.resolution_type] || '';
                resolution.querySelector('.js-resolution-value').textContent = report.resolution_value ? ` — ${report.resolution_value}` : '';
                const canConfirm = report.status === 'awaiting_confirmation' && (report.resolution_type !== 'redelivery' || report.redelivery_order_status === 'completed');
                card.querySelector('.js-confirm-resolution')?.classList.toggle('d-none', !canConfirm);
                const redeliveryStatus = card.querySelector('.js-redelivery-status');
                if (redeliveryStatus && report.redelivery_order_status_label) redeliveryStatus.textContent = report.redelivery_order_status_label;
                if (report.status === 'rejected') return;
                const level = levels[report.status] || 1;
                card.querySelectorAll('.js-issue-step').forEach((step) => {
                    const stepNumber = Number(step.dataset.step);
                    const active = level >= stepNumber;
                    const completed = level > stepNumber;
                    const circle = step.querySelector('span');
                    circle.className = `rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 ${active ? 'bg-success text-white' : 'bg-light text-secondary'}`;
                    step.querySelector('strong').className = active ? 'text-dark' : 'text-secondary';
                    step.querySelector('i').className = `bi ${completed ? 'bi-check-lg' : ['bi-inbox', 'bi-gear', 'bi-person-check', 'bi-check2-circle'][stepNumber - 1]}`;
                });
            });
        } catch (_) {}
    };
    window.setInterval(refreshIssues, 2000);
})();
</script>
@endpush
@endif
