@extends('layouts.client')
@section('title', 'Tạo đơn nhóm')
@section('content')
@include('client.group-orders._styles')
<section class="group-page" data-vue-group-order-create>
    <div class="container group-create-shell">
        <a href="{{ route('group-orders.index') }}" class="group-create-back"><i class="bi bi-arrow-left"></i>Quay lại danh sách</a>
        <div class="row g-4 align-items-stretch group-create-layout">
            <div class="col-lg-4">
                <div class="group-card group-hero group-create-hero h-100">
                    <div>
                        <div class="group-eyebrow text-white-50 mb-2">Chill Drink Together</div>
                        <h1 class="group-create-title mb-3">Một link, cả nhóm cùng chọn.</h1>
                        <p class="text-white-50 mb-0">Tạo phòng trong vài giây, gửi link và để mọi người tự chọn món.</p>
                    </div>
                    <div class="group-create-benefits">
                        <div><span><i class="bi bi-link-45deg"></i></span><strong>Chia sẻ link riêng</strong></div>
                        <div><span><i class="bi bi-person-check"></i></span><strong>Theo dõi từng thành viên</strong></div>
                        <div><span><i class="bi bi-receipt"></i></span><strong>Tự động tổng hợp tiền</strong></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <form method="POST" action="{{ route('group-orders.store') }}" class="group-card group-create-form h-100" data-group-create-form>@csrf
                    <div class="group-create-form-head">
                        <div><div class="group-eyebrow mb-1">Tạo phòng mới</div><h2 class="h3 fw-bold mb-1">Thông tin đơn nhóm</h2></div>
                        <span class="group-create-step"><i class="bi bi-lightning-charge-fill"></i> Chỉ mất khoảng 1 phút</span>
                    </div>
                    @if($errors->any())<div class="alert alert-danger rounded-4"><i class="bi bi-exclamation-circle me-2"></i>{{ $errors->first() }}</div>@endif
                    <div class="alert alert-danger rounded-4 d-none" data-group-create-errors role="alert"></div>
                    <div class="mb-3"><label class="group-form-label" for="groupName">Tên nhóm</label><input id="groupName" name="name" value="{{ old('name') }}" class="form-control group-input" required maxlength="120" placeholder="Ví dụ: Team Marketing đặt trà chiều"></div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="group-option-box group-create-option h-100">
                                <div class="group-create-option-head"><span><i class="bi bi-shop"></i></span><label class="group-form-label mb-0" for="groupBranch">Chi nhánh phục vụ</label></div>
                                <div data-vue-group-branch
                                     data-selected="{{ $selectedBranchId }}"
                                     data-branches="{{ $branches->map(fn ($branch) => ['id' => $branch->id, 'name' => $branch->name, 'address' => $branch->address, 'distance_km' => $branch->distance_km, 'is_nearest' => (bool)$branch->is_nearest, 'is_too_far' => (bool)$branch->is_too_far])->values()->toJson() }}">
                                    {{-- Fallback để vẫn chọn được chi nhánh nếu máy chưa build lại bundle Vue. --}}
                                    <select name="branch_id" class="form-select group-branch-fallback" aria-label="Chọn chi nhánh">
                                        <option value="">Chọn chi nhánh</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>
                                                {{ $branch->name }}
                                                @if($branch->distance_km !== null)
                                                    ({{ $branch->distance_km }} km{{ $branch->is_nearest ? ' · Gần bạn nhất' : '' }})
                                                @endif
                                                {{ $branch->address ? ' — '.$branch->address : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-text mt-2 text-muted"><i class="bi bi-shop me-1"></i>Nơi chuẩn bị đồ uống cho cả nhóm.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="group-option-box group-create-option h-100">
                                <div class="group-create-option-head"><span><i class="bi bi-calendar2-check"></i></span><label class="group-form-label mb-0" for="groupClosesAt">Thời gian kết thúc</label></div>
                                <div data-vue-group-datetime
                                     data-value="{{ old('closes_at', now()->addMinutes(30)->format('Y-m-d\TH:i')) }}"
                                     data-min="{{ now()->addMinutes(5)->format('Y-m-d\TH:i') }}"
                                     data-max="{{ now()->addDays(7)->format('Y-m-d\TH:i') }}"></div>
                                <div class="form-text mt-2">Phòng tự đóng vào thời gian này.</div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3"><label class="group-form-label" for="groupNote">Ghi chú chung <span class="text-secondary fw-normal">(không bắt buộc)</span></label><textarea id="groupNote" name="note" class="form-control group-input group-create-note" maxlength="500" rows="3" placeholder="Ví dụ: Giao tại lễ tân tầng 5">{{ old('note') }}</textarea></div>
                    <button class="btn btn-primary group-btn w-100" data-group-create-submit><i class="bi bi-people me-2"></i><span data-group-create-submit-label>Tạo phòng đặt hàng</span></button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
