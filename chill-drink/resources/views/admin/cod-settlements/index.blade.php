@extends(auth()->user()->preferredAdminLayout())

@section('title', 'Đối soát COD')
@section('page-title', 'Đối soát COD')
@section('hide-topbar-search', true)

@section('content')
@php
    $indexRoute = $rootMode
        ? route('admin.super-admin.manage.cod-settlements.index')
        : route('admin.cod-settlements.index');
    $pinSendRoute = $rootMode
        ? route('admin.super-admin.manage.cod-settlements.pin.send')
        : route('admin.cod-settlements.pin.send');
    $pinSaveRoute = $rootMode
        ? route('admin.super-admin.manage.cod-settlements.pin.save')
        : route('admin.cod-settlements.pin.save');
    $adminEmail = (string) (auth()->user()->email ?? '');
    $hasCodSettlementPin = auth()->user()->hasCodSettlementPin();
    $pinSetupShouldOpen = $errors->hasAny(['verification_code', 'new_pin', 'new_pin_confirmation'])
        || filled(old('verification_code'));
    $maskedAdminEmail = $adminEmail !== ''
        ? (function (string $email): string {
            [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
            if ($domain === '') {
                return $email;
            }
            if (mb_strlen($local) <= 2) {
                return mb_substr($local, 0, 1).'*@'.$domain;
            }

            return mb_substr($local, 0, 2).str_repeat('*', max(mb_strlen($local) - 3, 1)).mb_substr($local, -1).'@'.$domain;
        })($adminEmail)
        : null;
@endphp

<style>
    .cod-stat,.cod-card{background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:18px;box-shadow:0 8px 28px rgba(15,23,42,.045)}
    .cod-money{font-variant-numeric:tabular-nums;color:#b45309}
    .cod-order-list{background:#f8fafc;border-radius:12px;padding:.75rem 1rem}
    .cod-order-row{display:flex;justify-content:space-between;gap:1rem;padding:.45rem 0;border-bottom:1px dashed #e2e8f0;font-size:.84rem}
    .cod-order-row:last-child{border-bottom:0}
    .cod-pin-input{text-align:center;letter-spacing:.28em;font-weight:700}
    .cod-pin-status{min-height:1.25rem}
    .cod-pin-modal-note{background:linear-gradient(180deg,#fcfffe 0%,#f7fbfa 100%);border:1px solid rgba(15,23,42,.08);border-radius:16px;padding:1rem 1.1rem}
</style>

<section class="d-grid gap-4">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <div class="small text-secondary mb-1">Tiền mặt tài xế đang giữ hộ công ty</div>
            <h2 class="h4 fw-bold mb-1">Đối soát COD shipper</h2>
            <div class="small text-secondary">
                @if($rootMode)
                    Super Admin xem công nợ COD toàn hệ thống. Mỗi shipper chỉ nộp tiền về home branch cố định của mình.
                @else
                    Chỉ hiển thị shipper có home branch là chi nhánh của bạn. Chỉ xác nhận sau khi đã nhận tiền mặt thực tế.
                @endif
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <button
                type="button"
                class="btn btn-sm {{ $hasCodSettlementPin ? 'btn-outline-secondary' : 'btn-warning text-dark' }}"
                data-bs-toggle="modal"
                data-bs-target="#codPinSetupModal"
            >
                <i class="bi bi-gear me-1"></i>Cài đặt PIN
            </button>
            <a href="{{ $indexRoute }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Làm mới</a>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4"><div class="cod-stat p-4"><div class="small text-secondary">Tổng tiền COD chưa nộp</div><div class="fs-3 fw-bold cod-money mt-1">{{ number_format($totalPending,0,',','.') }}đ</div></div></div>
        <div class="col-lg-4"><div class="cod-stat p-4"><div class="small text-secondary">Shipper đang có công nợ</div><div class="fs-3 fw-bold mt-1">{{ $pendingRows->count() }}</div></div></div>
        <div class="col-lg-4"><div class="cod-stat p-4"><div class="small text-secondary">Đơn COD chưa đối soát</div><div class="fs-3 fw-bold mt-1">{{ $totalPendingOrders }}</div></div></div>
    </div>

    <div class="d-grid gap-3">
        @forelse($pendingRows as $row)
            @php
                $shipper = $row['shipper'];
                $confirmRoute = $rootMode
                    ? route('admin.super-admin.manage.cod-settlements.confirm', $shipper)
                    : route('admin.cod-settlements.confirm', $shipper);
                $locationLabel = 'Home branch: '.($shipper->user?->branch?->name ?? 'Chưa gán');
            @endphp
            <article class="cod-card p-4">
                <div class="row g-4 align-items-start">
                    <div class="col-xl-3">
                        <div class="small text-secondary fw-semibold mb-2">SHIPPER</div>
                        <div class="fw-bold">{{ $shipper->user?->name ?? 'Shipper #'.$shipper->id }}</div>
                        <div class="small text-secondary">{{ $shipper->code }} · {{ $shipper->phone ?: $shipper->user?->phone }}</div>
                        <div class="small mt-2"><i class="bi bi-geo-alt me-1 text-primary"></i>{{ $locationLabel }}</div>
                    </div>
                    <div class="col-xl-2">
                        <div class="small text-secondary fw-semibold">PHẢI NỘP</div>
                        <div class="fs-4 fw-bold cod-money mt-1">{{ number_format($row['pending_amount'],0,',','.') }}đ</div>
                        <div class="small text-secondary">{{ $row['pending_order_count'] }} đơn COD</div>
                    </div>
                    <div class="col-xl-4">
                        <details>
                            <summary class="small fw-semibold" style="cursor:pointer">Xem các đơn đang giữ tiền</summary>
                            <div class="cod-order-list mt-2">
                                @foreach($row['items'] as $item)
                                    <div class="cod-order-row">
                                        <span><strong>{{ $item->order_code ?: '#'.$item->order_id }}</strong><br><span class="text-secondary">{{ $item->collected_at?->format('d/m H:i') }} · {{ $item->order?->branch?->name ?? 'Chi nhánh cũ' }}</span></span>
                                        <strong>{{ number_format((int)$item->amount,0,',','.') }}đ</strong>
                                    </div>
                                @endforeach
                                @if($row['pending_order_count'] > $row['items']->count())
                                    <div class="small text-secondary pt-2">Và {{ $row['pending_order_count'] - $row['items']->count() }} đơn khác.</div>
                                @endif
                            </div>
                        </details>
                    </div>
                    <div class="col-xl-3">
                        <form
                            class="cod-confirm-form"
                            method="POST"
                            action="{{ $confirmRoute }}"
                            onsubmit="return confirm('Bạn đã thực nhận đủ {{ number_format($row['pending_amount'],0,',','.') }}đ tiền mặt từ shipper này?');"
                        >
                            @csrf
                            <div class="alert alert-light border py-2 px-3 small mb-2">
                                <i class="bi bi-building me-1"></i> Nộp tại <strong>{{ $row['home_branch_name'] ?? 'Home branch chưa xác định' }}</strong>
                            </div>
                            @if(! $hasCodSettlementPin)
                                <div class="alert alert-warning small py-2 px-3 mb-2">
                                    Bạn chưa tạo PIN đối soát COD. Hãy bấm nút Cài đặt PIN trước khi xác nhận nhận tiền.
                                </div>
                            @endif
                            <input type="text" name="note" class="form-control form-control-sm mb-2" maxlength="500" placeholder="Ghi chú (không bắt buộc)">
                            <div class="mb-2">
                                <label class="form-label small text-secondary mb-1">PIN đối soát 4 số</label>
                                <input
                                    type="password"
                                    name="pin"
                                    class="form-control form-control-sm cod-pin-input"
                                    maxlength="4"
                                    inputmode="numeric"
                                    pattern="[0-9]{4}"
                                    placeholder="••••"
                                >
                            </div>
                            <div class="small text-secondary mb-2">
                                Nhập PIN đối soát cố định của bạn. Gmail code chỉ dùng lúc tạo hoặc đổi PIN.
                            </div>
                            <button type="submit" class="btn btn-success w-100" @disabled(! $hasCodSettlementPin)><i class="bi bi-cash-coin me-1"></i>Xác nhận đã nhận tiền</button>
                            <div class="small text-secondary mt-2">Thao tác sẽ chốt toàn bộ {{ $row['pending_order_count'] }} đơn COD chưa đối soát của shipper tại home branch.</div>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="cod-card p-5 text-center">
                <i class="bi bi-cash-coin text-success" style="font-size:2.4rem"></i>
                <div class="fw-bold mt-3">Không có tiền COD đang chờ nộp</div>
                <div class="small text-secondary mt-1">Khi shipper giao một đơn COD và thu tiền khách, công nợ sẽ tự xuất hiện ở đây.</div>
            </div>
        @endforelse
    </div>

    <div class="cod-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><div class="fw-bold">Lịch sử nộp COD</div><div class="small text-secondary">50 lần đối soát gần nhất trong phạm vi bạn đang quản lý.</div></div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Thời gian</th><th>Shipper</th><th>Chi nhánh nhận</th><th>Số đơn</th><th>Số tiền</th><th>Người xác nhận</th><th>Ghi chú</th></tr></thead>
                <tbody>
                    @forelse($history as $settlement)
                        <tr>
                            <td>{{ $settlement->confirmed_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td><strong>{{ $settlement->shipper?->user?->name ?? 'Shipper #'.$settlement->shipper_id }}</strong><br><span class="small text-secondary">{{ $settlement->shipper?->code }}</span></td>
                            <td>{{ $settlement->branch?->name ?? '-' }}</td>
                            <td>{{ $settlement->order_count }}</td>
                            <td class="fw-bold text-success">{{ number_format((int)$settlement->amount,0,',','.') }}đ</td>
                            <td>{{ $settlement->confirmer?->name ?? 'Hệ thống' }}</td>
                            <td class="small text-secondary">{{ $settlement->note ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">Chưa có lịch sử đối soát COD.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>

<div class="modal fade" id="codPinSetupModal" tabindex="-1" aria-labelledby="codPinSetupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title h5 fw-bold mb-1" id="codPinSetupModalLabel">Cài đặt PIN đối soát COD</h2>
                    <div class="small text-secondary">PIN 4 số dùng để xác nhận nhận tiền COD. Tạo hoặc đổi PIN phải xác minh bằng Gmail.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <div class="cod-pin-modal-note mb-3">
                    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                        <div>
                            <div class="small text-secondary mb-1">TRẠNG THÁI PIN</div>
                            <div class="fw-semibold">
                                @if($hasCodSettlementPin)
                                    Đã cài PIN đối soát
                                @else
                                    Chưa cài PIN đối soát
                                @endif
                            </div>
                        </div>
                        @if($hasCodSettlementPin)
                            <span class="badge text-bg-success">Đã cài PIN</span>
                        @else
                            <span class="badge text-bg-warning text-dark">Chưa cài PIN</span>
                        @endif
                    </div>
                    <div class="small {{ $maskedAdminEmail ? 'text-secondary' : 'text-danger' }} mt-2">
                        {{ $maskedAdminEmail ? 'Mã xác minh sẽ gửi tới '.$maskedAdminEmail.'.' : 'Tài khoản admin chưa có email nên chưa thể tạo PIN.' }}
                    </div>
                </div>

                <form method="POST" action="{{ $pinSaveRoute }}" id="codPinSetupForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-5">
                            <label class="form-label small text-secondary mb-1">Mã xác minh Gmail</label>
                            <div class="input-group input-group-sm">
                                <input
                                    type="text"
                                    name="verification_code"
                                    class="form-control cod-pin-input"
                                    maxlength="6"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    pattern="[0-9]{6}"
                                    placeholder="123456"
                                    value="{{ old('verification_code') }}"
                                >
                                <button type="button" class="btn btn-outline-primary" id="codSendSetupCodeBtn" @disabled(blank($adminEmail))>
                                    Gửi mã Gmail
                                </button>
                            </div>
                            @error('verification_code')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <div class="small text-secondary cod-pin-status mt-2" id="codPinSetupStatus">
                                {{ $maskedAdminEmail ? 'Gmail code 6 số chỉ dùng để tạo hoặc đổi PIN.' : 'Cần email admin để gửi mã xác minh.' }}
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label small text-secondary mb-1">PIN mới</label>
                            <input
                                type="password"
                                name="new_pin"
                                class="form-control form-control-sm cod-pin-input"
                                maxlength="4"
                                inputmode="numeric"
                                pattern="[0-9]{4}"
                                placeholder="4 số"
                            >
                            @error('new_pin')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label small text-secondary mb-1">Nhập lại PIN</label>
                            <input
                                type="password"
                                name="new_pin_confirmation"
                                class="form-control form-control-sm cod-pin-input"
                                maxlength="4"
                                inputmode="numeric"
                                pattern="[0-9]{4}"
                                placeholder="4 số"
                            >
                            @error('new_pin_confirmation')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-success btn-sm w-100" @disabled(blank($adminEmail))>
                                Lưu
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[name="verification_code"], input[name="new_pin"], input[name="new_pin_confirmation"], input[name="pin"]').forEach((input) => {
        const maxLength = Number.parseInt(input.getAttribute('maxlength') || '0', 10);
        input.addEventListener('input', () => {
            const value = input.value.replace(/\D/g, '');
            input.value = maxLength > 0 ? value.slice(0, maxLength) : value;
        });
    });

    const sendButton = document.getElementById('codSendSetupCodeBtn');
    const statusNode = document.getElementById('codPinSetupStatus');
    const setupForm = document.getElementById('codPinSetupForm');
    const verificationInput = setupForm?.querySelector('input[name="verification_code"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const sendPinUrl = @json($pinSendRoute);
    const shouldOpenPinModal = @json($pinSetupShouldOpen);
    const pinModalElement = document.getElementById('codPinSetupModal');

    if (shouldOpenPinModal && pinModalElement && window.bootstrap?.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(pinModalElement).show();
    }

    if (sendButton && statusNode && sendPinUrl) {
        sendButton.addEventListener('click', async () => {
            sendButton.disabled = true;
            statusNode.classList.remove('text-danger', 'text-success');
            statusNode.classList.add('text-secondary');
            statusNode.textContent = 'Đang gửi mã xác minh Gmail...';

            try {
                const response = await fetch(sendPinUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Không gửi được mã xác minh Gmail.');
                }

                statusNode.classList.remove('text-secondary', 'text-danger');
                statusNode.classList.add('text-success');
                statusNode.textContent = `${payload.message} Mã hết hạn sau ${payload.ttl_minutes} phút.`;
                verificationInput?.focus();
            } catch (error) {
                statusNode.classList.remove('text-secondary', 'text-success');
                statusNode.classList.add('text-danger');
                statusNode.textContent = error.message || 'Không gửi được mã xác minh Gmail.';
            } finally {
                sendButton.disabled = false;
            }
        });
    }
});
</script>
@endsection
