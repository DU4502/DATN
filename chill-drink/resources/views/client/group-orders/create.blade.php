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
                    <div class="group-option-box mb-4">
                        <div class="d-flex align-items-start gap-3">
                            <i class="bi bi-calendar2-check fs-3 text-primary mt-1"></i>
                            <div class="flex-grow-1">
                                <label class="group-form-label d-block" for="groupClosesAt">Ngày và giờ kết thúc</label>
                                <input id="groupClosesAt" type="datetime-local" name="closes_at" value="{{ old('closes_at', now()->addMinutes(30)->format('Y-m-d\TH:i')) }}" min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}" max="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}" class="form-control group-input" required>
                                <div class="form-text mt-2"><i class="bi bi-info-circle me-1"></i>Phòng bắt đầu ngay sau khi tạo và tự đóng vào thời gian này.</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4"><label class="group-form-label" for="groupNote">Ghi chú chung <span class="text-secondary fw-normal">(không bắt buộc)</span></label><textarea id="groupNote" name="note" class="form-control group-input" maxlength="500" rows="4" placeholder="Ví dụ: Giao tại lễ tân tầng 5">{{ old('note') }}</textarea></div>
                    <button class="btn btn-primary group-btn w-100"><i class="bi bi-people me-2"></i>Tạo phòng đặt hàng</button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
