@extends('layouts.client')
@section('title', 'Tạo đơn nhóm')
@section('content')
@include('client.group-orders._styles')
<section class="group-page">
    <div class="container" style="max-width: 900px">
        <a href="{{ route('group-orders.index') }}" class="text-decoration-none text-secondary d-inline-flex align-items-center gap-2 mb-4"><i class="bi bi-arrow-left"></i>Quay lại danh sách</a>
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="group-card group-hero h-100 d-flex flex-column justify-content-between">
                    <div><div class="group-eyebrow text-white-50 mb-3">Chill Drink Together</div><h1 class="display-6 fw-bold mb-3">Một link,<br>cả nhóm cùng chọn.</h1><p class="text-white-50">Không cần nhắn từng món. Hệ thống tự chia tiền và gom đơn cho bạn.</p></div>
                    <div class="d-grid gap-3 mt-4"><div><i class="bi bi-link-45deg me-2"></i>Chia sẻ link riêng</div><div><i class="bi bi-person-check me-2"></i>Biết chính xác ai đặt món gì</div><div><i class="bi bi-receipt me-2"></i>Tính tiền tự động</div></div>
                </div>
            </div>
            <div class="col-lg-7">
                <form method="POST" action="{{ route('group-orders.store') }}" class="group-card p-4 p-md-5 h-100" data-group-create-form>@csrf
                    <div class="group-eyebrow mb-2">Tạo phòng mới</div><h2 class="h3 fw-bold mb-4">Thông tin đơn nhóm</h2>
                    @if($errors->any())<div class="alert alert-danger rounded-4"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>@endif
                    <div class="mb-4"><label class="group-form-label" for="groupName">Tên nhóm</label><input id="groupName" name="name" value="{{ old('name') }}" class="form-control group-input" required maxlength="120" placeholder="Ví dụ: Team Marketing đặt trà chiều"></div>
                    <div class="mb-4"><label class="group-form-label" for="closesAt">Thời gian chốt đơn</label><input id="closesAt" type="datetime-local" name="closes_at" value="{{ old('closes_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}" class="form-control group-input @error('closes_at') is-invalid @enderror" required data-closes-at><div class="invalid-feedback" data-closes-at-error>@error('closes_at'){{ $message }}@else Thời gian chốt đơn phải ở tương lai. @enderror</div><div class="form-text">Sau thời gian này, thành viên không thể thêm món.</div></div>
                    <div class="mb-4"><label class="group-form-label" for="groupNote">Ghi chú chung <span class="text-secondary fw-normal">(không bắt buộc)</span></label><textarea id="groupNote" name="note" class="form-control group-input" maxlength="500" rows="4" placeholder="Ví dụ: Giao tại lễ tân tầng 5">{{ old('note') }}</textarea></div>
                    <button class="btn btn-primary group-btn w-100"><i class="bi bi-people me-2"></i>Tạo phòng đặt hàng</button>
                </form>
            </div>
        </div>
    </div>
</section>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-group-create-form]');
    const input = document.querySelector('[data-closes-at]');
    if (!form || !input) return;

    const toLocalDateTime = function (date) {
        const offset = date.getTimezoneOffset();
        return new Date(date.getTime() - offset * 60000).toISOString().slice(0, 16);
    };
    const refreshMinimum = function () {
        const minimum = new Date(Date.now() + 60000);
        input.min = toLocalDateTime(minimum);
        return minimum;
    };
    refreshMinimum();

    input.addEventListener('change', function () {
        const valid = input.value && new Date(input.value).getTime() > Date.now();
        input.classList.toggle('is-invalid', !valid);
        input.setCustomValidity(valid ? '' : 'Thời gian chốt đơn phải ở tương lai.');
    });

    form.addEventListener('submit', function (event) {
        const minimum = refreshMinimum();
        const selected = input.value ? new Date(input.value) : null;
        if (!selected || selected < minimum) {
            event.preventDefault();
            input.classList.add('is-invalid');
            input.setCustomValidity('Thời gian chốt đơn phải ở tương lai.');
            input.reportValidity();
        }
    });
});
</script>
@endsection
