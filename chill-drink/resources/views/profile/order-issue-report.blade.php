@extends('layouts.client')

@section('title', 'Yêu cầu hỗ trợ đơn hàng')

@section('content')
<section class="py-5">
    <div class="container" style="max-width: 760px;">
        <a href="{{ route('orders.index') }}" class="text-primary text-decoration-none fw-semibold"><i class="bi bi-arrow-left me-1"></i>Quay lại đơn hàng</a>
        <div class="card border-0 shadow-sm rounded-4 mt-3"><div class="card-body p-4 p-md-5">
            <p class="text-primary fw-semibold mb-1">Hỗ trợ đơn hàng</p>
            <h1 class="h3 fw-bold">{{ $reports->isNotEmpty() ? 'Tiến trình xử lý yêu cầu' : 'Báo vấn đề cho đơn' }} {{ $order->order_code ?? '#'.$order->id }}</h1>
            <p class="text-secondary">{{ $reports->isNotEmpty() ? 'Yêu cầu đã được tiếp nhận. Bạn có thể theo dõi trạng thái xử lý tại đây.' : 'Gửi mô tả để nhân viên kiểm tra: thiếu món, sai món, chất lượng đồ uống hoặc yêu cầu hoàn tiền.' }}</p>
            @if(session('success'))<div class="alert alert-success rounded-4 border-0"><i class="bi bi-check-circle me-1"></i>{{ session('success') }}</div>@endif

            @if($reports->isEmpty())
                <form method="POST" action="{{ route('orders.issues.store', $order) }}" class="mt-4" enctype="multipart/form-data" data-evidence-form>
                    @csrf
                    <div class="mb-3"><label class="form-label fw-semibold">Loại vấn đề</label><select name="type" class="form-select" required><option value="">Chọn vấn đề</option><option value="missing_item" @selected(old('type') === 'missing_item')>Thiếu món</option><option value="wrong_item" @selected(old('type') === 'wrong_item')>Sai món</option><option value="quality_issue" @selected(old('type') === 'quality_issue')>Chất lượng đồ uống chưa tốt</option><option value="refund_request" @selected(old('type') === 'refund_request')>Yêu cầu hoàn tiền</option><option value="other" @selected(old('type') === 'other')>Vấn đề khác</option></select></div>
                    <div class="mb-4"><label class="form-label fw-semibold">Mô tả chi tiết</label><textarea name="description" rows="5" class="form-control" required>{{ old('description') }}</textarea><div class="form-text">Tối thiểu 10 ký tự, tối đa 1.500 ký tự.</div></div>
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
                    const selectedEvidenceCount = () => evidenceInputs.filter((input) => input.files?.length).length;
                    const refreshEvidenceError = () => evidenceError?.classList.toggle('d-none', selectedEvidenceCount() >= 2);

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
                        if (selectedEvidenceCount() >= 2) return;
                        event.preventDefault();
                        refreshEvidenceError();
                        evidenceError?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    });
                </script>
            @else
                @foreach($reports as $report)
                    @php
                        $level = match ($report->status) {
                            'resolved' => 5,
                            'awaiting_customer' => 4,
                            'approved', 'remedy_in_progress' => 3,
                            'processing' => 2,
                            default => 1,
                        };
                        $rejected = $report->status === 'rejected';
                        $steps = [
                            ['Tiếp nhận yêu cầu', 'Yêu cầu của bạn đã được gửi thành công.', 'bi-inbox', $report->received_at ?? $report->created_at],
                            ['Đang kiểm tra', 'Nhân viên đang kiểm tra và xử lý vấn đề.', 'bi-search', $report->processing_at],
                            ['Đã duyệt phương án', 'Nhân viên đã thống nhất quyền lợi hỗ trợ cho bạn.', 'bi-clipboard-check', $report->approved_at],
                            ['Chờ bạn xác nhận', 'Vui lòng xác nhận khi đã nhận đủ hỗ trợ.', 'bi-person-check', null],
                            ['Hoàn tất xử lý', 'Yêu cầu hỗ trợ đã được hoàn tất.', 'bi-check2-circle', $report->customer_confirmed_at ?? $report->resolved_at],
                        ];
                    @endphp
                    <div class="rounded-4 p-3 p-md-4 mt-4 js-issue-card" data-issue-id="{{ $report->id }}" style="background:#f6fbf9;border:1px solid #dcefe8;">
                        @php $statusTime = match ($report->status) {'processing' => $report->processing_at, 'approved' => $report->approved_at, 'remedy_in_progress' => $report->remedy_started_at, 'awaiting_customer' => $report->updated_at, 'resolved' => $report->customer_confirmed_at ?? $report->resolved_at, 'rejected' => $report->rejected_at, default => $report->received_at ?? $report->created_at}; $statusLabel = ['open' => 'Đang chờ xử lý', 'processing' => 'Đang kiểm tra', 'approved' => 'Đã duyệt hỗ trợ', 'remedy_in_progress' => 'Đang khắc phục', 'awaiting_customer' => 'Chờ bạn xác nhận', 'resolved' => 'Hoàn tất', 'rejected' => 'Không được chấp nhận'][$report->status] ?? 'Đang chờ xử lý'; @endphp
                        <div class="d-flex justify-content-between gap-3 mb-3"><div><strong>{{ ucfirst(str_replace('_', ' ', $report->type)) }}</strong><div class="small text-secondary mt-1">Gửi lúc {{ $report->created_at->format('d/m/Y H:i') }}</div></div><div class="text-end"><span class="badge rounded-pill px-3 py-2 js-issue-status {{ $rejected ? 'text-bg-danger' : ($report->status === 'resolved' ? 'text-bg-success' : 'text-bg-warning') }}">{{ $statusLabel }}</span><div class="small text-secondary mt-2 js-issue-time {{ $statusTime ? '' : 'd-none' }}"><i class="bi bi-clock me-1"></i>Cập nhật lúc <span>{{ $statusTime?->format('d/m/Y H:i') }}</span></div></div></div>
                        <p class="mb-3"><i class="bi bi-chat-square-text text-primary me-1"></i>{{ $report->description }}</p>
                        <div class="alert alert-info mt-3 js-resolution {{ $report->resolution_type ? '' : 'd-none' }}"><strong><i class="bi bi-gift me-1"></i>Phương án hỗ trợ:</strong> <span class="js-resolution-type">{{ ['redelivery' => 'Giao bù / đổi đúng món', 'refund' => 'Hoàn tiền', 'voucher' => 'Tặng voucher bù', 'other' => 'Phương án khác'][$report->resolution_type] ?? '' }}</span><span class="js-resolution-value">{{ $report->resolution_value ? ' — '.$report->resolution_value : '' }}</span><div class="small mt-1 js-estimated {{ $report->estimated_at ? '' : 'd-none' }}">Dự kiến hoàn tất: <span>{{ $report->estimated_at?->format('d/m/Y H:i') }}</span></div></div>
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
                            <form method="POST" action="{{ route('orders.issues.confirm', [$order, $report]) }}" class="mt-3 js-confirm-resolution {{ $report->status === 'awaiting_customer' ? '' : 'd-none' }}">@csrf<button class="btn btn-success rounded-pill px-4"><i class="bi bi-check2-circle me-1"></i>Tôi đã nhận đủ hỗ trợ</button><div class="small text-secondary mt-2">Chỉ xác nhận khi bạn đã nhận món giao bù, tiền hoàn hoặc voucher theo phương án trên.</div></form>
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
    const labels = {open: 'Đang chờ xử lý', processing: 'Đang kiểm tra', approved: 'Đã duyệt hỗ trợ', remedy_in_progress: 'Đang khắc phục', awaiting_customer: 'Chờ bạn xác nhận', resolved: 'Hoàn tất', rejected: 'Không được chấp nhận'};
    const times = {processing: 'processing_at', approved: 'approved_at', remedy_in_progress: 'remedy_started_at', awaiting_customer: 'remedy_started_at', resolved: 'customer_confirmed_at', rejected: 'rejected_at'};
    const levels = {open: 1, processing: 2, approved: 3, remedy_in_progress: 3, awaiting_customer: 4, resolved: 5};
    const resolutionLabels = {redelivery: 'Giao bù / đổi đúng món', refund: 'Hoàn tiền', voucher: 'Tặng voucher bù', other: 'Phương án khác'};
    const refreshIssues = async () => {
        try {
            const response = await fetch('{{ route('orders.issues.status', $order) }}', {headers: {Accept: 'application/json'}});
            if (!response.ok) return;
            const {reports} = await response.json();
            reports.forEach((report) => {
                const card = document.querySelector(`.js-issue-card[data-issue-id="${report.id}"]`);
                if (!card) return;
                const badge = card.querySelector('.js-issue-status');
                badge.textContent = labels[report.status] || labels.open;
                badge.className = `badge rounded-pill px-3 py-2 js-issue-status ${report.status === 'rejected' ? 'text-bg-danger' : (report.status === 'resolved' ? 'text-bg-success' : 'text-bg-warning')}`;
                const time = report[times[report.status]] || '';
                const timeBox = card.querySelector('.js-issue-time');
                timeBox.classList.toggle('d-none', !time);
                timeBox.querySelector('span').textContent = time;
                const resolution = card.querySelector('.js-resolution');
                resolution.classList.toggle('d-none', !report.resolution_type);
                resolution.querySelector('.js-resolution-type').textContent = resolutionLabels[report.resolution_type] || '';
                resolution.querySelector('.js-resolution-value').textContent = report.resolution_value ? ` — ${report.resolution_value}` : '';
                const estimated = resolution.querySelector('.js-estimated');
                estimated.classList.toggle('d-none', !report.estimated_at);
                estimated.querySelector('span').textContent = report.estimated_at || '';
                const confirmButton = card.querySelector('.js-confirm-resolution');
                if (confirmButton) confirmButton.classList.toggle('d-none', report.status !== 'awaiting_customer');
                if (report.status === 'rejected') return;
                const level = levels[report.status] || 1;
                card.querySelectorAll('.js-issue-step').forEach((step) => {
                    const stepNumber = Number(step.dataset.step);
                    const active = level >= stepNumber;
                    const completed = level > stepNumber;
                    const circle = step.querySelector('span');
                    circle.className = `rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 ${active ? 'bg-success text-white' : 'bg-light text-secondary'}`;
                    step.querySelector('strong').className = active ? 'text-dark' : 'text-secondary';
                    step.querySelector('i').className = `bi ${completed ? 'bi-check-lg' : ['bi-inbox', 'bi-search', 'bi-clipboard-check', 'bi-person-check', 'bi-check2-circle'][stepNumber - 1]}`;
                });
            });
        } catch (_) {}
    };
    window.setInterval(refreshIssues, 2000);
})();
</script>
@endpush
@endif
