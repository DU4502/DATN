@props([
    'order',
    'modalId' => null,
])
@php
    $modalId = $modalId ?? ('orderHistoryModal-' . $order->id);
    $histories = $order->statusHistories ?? collect();
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-bottom pb-3">
                <div class="d-flex align-items-center gap-2">
                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 38px; height: 38px; font-size: 1.1rem;">
                        <i class="bi bi-clock-history"></i>
                    </span>
                    <div>
                        <h6 class="modal-title fw-bold mb-0" id="{{ $modalId }}Label">Lịch sử xử lý đơn hàng</h6>
                        <small class="text-secondary">Mã đơn: <span class="text-primary fw-bold">{{ $order->displayCode() }}</span></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                @if($histories->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-inbox fs-2 text-secondary mb-2 d-block"></i>
                        <p class="mb-0 small">Chưa có bản ghi lịch sử trạng thái cho đơn hàng này.</p>
                    </div>
                @else
                    <div class="order-status-timeline">
                        @foreach($histories as $index => $history)
                            @php
                                $historyActor = $history->actor;
                                $roleText = $historyActor ? ($historyActor->isAdmin() || $historyActor->isSuperAdmin() ? 'Quản lý' : ($historyActor->isStaffOnly() ? 'Nhân viên' : ($historyActor->isShipper() ? 'Tài xế' : 'Khách hàng'))) : 'Hệ thống';
                                $roleClass = $historyActor ? ($historyActor->isAdmin() || $historyActor->isSuperAdmin() ? 'bg-primary' : ($historyActor->isStaffOnly() ? 'bg-success' : ($historyActor->isShipper() ? 'bg-info text-dark' : 'bg-secondary'))) : 'bg-dark';
                                $badgeColor = \App\Support\OrderStatus::badgeColorMap()[$history->to_status] ?? 'secondary';
                            @endphp
                            <div class="d-flex gap-3 {{ !$loop->last ? 'pb-4' : '' }}" style="position: relative;">
                                @if(!$loop->last)
                                    <div style="position: absolute; left: 16px; top: 34px; bottom: 0; width: 2px; background: #e2e8f0;"></div>
                                @endif
                                <div class="flex-shrink-0" style="z-index: 1;">
                                    <span class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $badgeColor }}-subtle text-{{ $badgeColor }} shadow-sm"
                                          style="width: 34px; height: 34px; font-size: 1rem; border: 2px solid #fff;">
                                        <i class="bi {{ \App\Support\OrderStatus::notificationIcon($history->to_status) }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                                        <span class="badge bg-{{ $badgeColor }} text-white px-2 py-1" style="font-size: .75rem; letter-spacing: 0.02em;">
                                            {{ \App\Support\OrderStatus::label($history->to_status) }}
                                        </span>
                                        <small class="text-muted fw-semibold" style="font-size: .78rem;">
                                            {{ $history->created_at?->format('H:i · d/m/Y') }}
                                        </small>
                                    </div>
                                    <div class="small text-secondary d-flex align-items-center gap-1 mt-1">
                                        <i class="bi bi-person-circle text-muted"></i>
                                        <span class="fw-semibold text-dark">{{ $historyActor?->name ?? 'Hệ thống' }}</span>
                                        <span class="badge {{ $roleClass }} text-white ms-1" style="font-size: .62rem; padding: 2px 7px; border-radius: 4px;">{{ $roleText }}</span>
                                    </div>
                                    @if($history->note)
                                        <div class="small text-dark mt-2 p-2 rounded-2 bg-light border-start border-3 border-{{ $badgeColor }}">
                                            {{ $history->note }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer border-top py-2 bg-light bg-opacity-50" style="border-radius: 0 0 20px 20px;">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
