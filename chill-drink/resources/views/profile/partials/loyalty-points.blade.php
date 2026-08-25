@extends('layouts.client')

@section('content')

@php
use App\Models\LoyaltyPoint;

$loyaltyPoint = $loyaltyPoint ?? LoyaltyPoint::getOrCreateForUser(auth()->id());
@endphp

<style>
    .loyalty-page {
        position: relative;
        isolation: isolate;
    }

    .loyalty-page::before {
        content: '';
        position: absolute;
        inset: 0;
        z-index: -1;
        background:
            radial-gradient(circle at top left, rgba(13, 147, 115, 0.08), transparent 34%),
            radial-gradient(circle at top right, rgba(16, 185, 129, 0.05), transparent 28%);
        pointer-events: none;
    }

    .loyalty-shell {
        display: grid;
        gap: 0.9rem;
    }

    .loyalty-hero {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 1rem 1.1rem;
        background: linear-gradient(135deg, #0D9373 0%, #0f9b63 48%, #16a34a 100%);
        color: #fff;
        box-shadow: 0 18px 36px rgba(13, 147, 115, 0.18);
    }

    .loyalty-hero::before,
    .loyalty-hero::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
        pointer-events: none;
    }

    .loyalty-hero::before {
        width: 220px;
        height: 220px;
        top: -100px;
        right: -70px;
    }

    .loyalty-hero::after {
        width: 140px;
        height: 140px;
        bottom: -46px;
        left: 46%;
        opacity: 0.45;
    }

    .loyalty-hero__grid {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(250px, 0.8fr);
        gap: 0.8rem 1rem;
        align-items: stretch;
    }

    .loyalty-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        margin-bottom: 0.35rem;
        padding: 0.22rem 0.6rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .loyalty-title {
        margin: 0;
        max-width: 12ch;
        color: #fff;
        font-size: clamp(1.4rem, 2vw, 2rem);
        font-weight: 900;
        letter-spacing: -0.045em;
        line-height: 0.99;
    }

    .loyalty-description {
        margin: 0.55rem 0 0.8rem;
        max-width: 52ch;
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .loyalty-points-block {
        display: grid;
        gap: 0.1rem;
        margin-bottom: 0.7rem;
    }

    .loyalty-points-number {
        color: #fff;
        font-size: clamp(2rem, 2.8vw, 2.7rem);
        font-weight: 900;
        line-height: 0.95;
        letter-spacing: -0.05em;
        text-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
    }

    .loyalty-points-label {
        color: rgba(255, 255, 255, 0.92);
        font-size: 0.84rem;
        font-weight: 700;
    }

    .loyalty-rule {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.35rem 0.6rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.14);
        color: rgba(255, 255, 255, 0.95);
        font-size: 0.72rem;
        font-weight: 700;
    }

    .loyalty-rule i {
        font-size: 0.95rem;
    }

    .loyalty-stats {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.65rem;
        align-content: stretch;
    }

    .loyalty-stat {
        min-height: 92px;
        padding: 0.78rem 0.85rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.16);
        border: 1px solid rgba(255, 255, 255, 0.14);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        display: grid;
        align-content: center;
        gap: 0.35rem;
    }

    .loyalty-stat--wide {
        grid-column: 1 / -1;
        min-height: 84px;
    }

    .loyalty-stat__label {
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.66rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .loyalty-stat__value {
        color: #fff;
        font-size: 1.35rem;
        font-weight: 900;
        letter-spacing: -0.035em;
        line-height: 1.02;
    }

    .loyalty-stat__copy {
        color: rgba(255, 255, 255, 0.84);
        font-size: 0.72rem;
        line-height: 1.35;
    }

    .loyalty-info {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 0.8rem 0.9rem;
        border: 1px solid #dbeafe;
        border-radius: 16px;
        background: linear-gradient(135deg, #eff8ff 0%, #ffffff 100%);
        color: #0f172a;
        box-shadow: 0 10px 22px rgba(15, 23, 42, 0.04);
    }

    .loyalty-info__icon {
        width: 1.95rem;
        height: 1.95rem;
        flex: 0 0 auto;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #0ea5b7;
        color: #fff;
        font-size: 0.92rem;
    }

    .loyalty-info__title {
        margin: 0 0 0.2rem;
        color: #0f172a;
        font-size: 0.82rem;
        font-weight: 800;
    }

    .loyalty-info__copy {
        color: #475569;
        font-size: 0.8rem;
        line-height: 1.45;
    }

    .loyalty-panel {
        overflow: hidden;
        border: 1px solid rgba(229, 231, 235, 0.95);
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.84);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
    }

    .loyalty-tabs {
        gap: 0.5rem;
        padding: 0.75rem 0.75rem 0 0.75rem;
        border-bottom: 1px solid rgba(229, 231, 235, 0.85);
    }

    .loyalty-tabs .nav-link {
        border: 1px solid transparent;
        border-bottom: 0;
        border-radius: 12px 12px 0 0;
        padding: 0.62rem 0.85rem;
        color: #64748b !important;
        font-size: 0.8rem;
        font-weight: 800;
    }

    .loyalty-tabs .nav-link:hover {
        background: rgba(13, 147, 115, 0.05);
        color: #0f172a !important;
    }

    .loyalty-tabs .nav-link.active {
        border-color: rgba(13, 147, 115, 0.18);
        background: #fff;
        color: #0d9373 !important;
        box-shadow: 0 -1px 0 #fff, 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .loyalty-panel__body {
        padding: 0.85rem;
    }

    .loyalty-voucher-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 0.8rem;
    }

    .loyalty-voucher-card {
        position: relative;
        overflow: hidden;
        min-height: 100%;
        padding: 0.9rem;
        border: 1px solid rgba(229, 231, 235, 0.95);
        border-radius: 18px;
        background:
            linear-gradient(180deg, rgba(13, 147, 115, 0.03), rgba(255, 255, 255, 0.98)),
            #fff;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.04);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        cursor: pointer;
    }

    .loyalty-voucher-card:hover {
        transform: translateY(-3px);
        border-color: rgba(13, 147, 115, 0.28);
        box-shadow: 0 18px 38px rgba(13, 147, 115, 0.12);
    }

    .loyalty-voucher-card.is-disabled {
        cursor: not-allowed;
        opacity: 0.72;
    }

    .loyalty-voucher-card.is-disabled:hover {
        transform: none;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.04);
    }

    .loyalty-voucher-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .loyalty-voucher-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.45rem 0.75rem;
        border-radius: 999px;
        background: rgba(13, 147, 115, 0.09);
        color: #0d9373;
        font-size: 0.8rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .loyalty-voucher-chip.is-muted {
        background: #f1f5f9;
        color: #64748b;
    }

    .loyalty-voucher-code {
        margin: 0;
        color: #0f172a;
        font-size: 1rem;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .loyalty-voucher-desc {
        margin: 0.3rem 0 0.65rem;
        color: #64748b;
        font-size: 0.8rem;
        line-height: 1.45;
    }

    .loyalty-voucher-highlight {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.42rem 0.7rem;
        border-radius: 12px;
        background: #ecfdf5;
        color: #047857;
        font-size: 0.78rem;
        font-weight: 800;
    }

    .loyalty-voucher-highlight small {
        color: #64748b;
        font-weight: 700;
    }

    .loyalty-voucher-footer {
        margin-top: auto;
    }

    .loyalty-voucher-btn {
        min-height: 40px;
        border: 0;
        border-radius: 14px;
        background: linear-gradient(135deg, #0D9373, #10b981);
        box-shadow: 0 8px 16px rgba(13, 147, 115, 0.18);
        font-weight: 700;
        font-size: 0.84rem;
    }

    .loyalty-voucher-btn:hover {
        background: linear-gradient(135deg, #0b8267, #0fa36f);
    }

    .loyalty-empty {
        min-height: 190px;
        display: grid;
        place-items: center;
        text-align: center;
        color: #64748b;
    }

    .loyalty-empty__icon {
        width: 3.2rem;
        height: 3.2rem;
        margin: 0 auto 0.85rem;
        border-radius: 999px;
        display: grid;
        place-items: center;
        background: #ecfdf5;
        color: #0d9373;
        font-size: 1.35rem;
    }

    .loyalty-transaction-list {
        display: grid;
        gap: 0.85rem;
    }

    .loyalty-transaction-card {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        gap: 0.9rem;
        align-items: center;
        padding: 0.85rem 0.9rem;
        border: 1px solid rgba(229, 231, 235, 0.95);
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
    }

    .loyalty-transaction-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #ecfdf5, #f0fdf4);
        color: #0d9373;
        font-size: 1rem;
        flex: 0 0 auto;
    }

    .loyalty-transaction-title {
        color: #0f172a;
        font-size: 0.86rem;
        font-weight: 800;
    }

    .loyalty-transaction-desc {
        margin-top: 0.2rem;
        color: #64748b;
        font-size: 0.78rem;
        line-height: 1.5;
    }

    .loyalty-transaction-time {
        margin-top: 0.25rem;
        color: #94a3b8;
        font-size: 0.72rem;
    }

    .loyalty-transaction-summary {
        text-align: right;
    }

    .loyalty-transaction-points {
        font-size: 1rem;
        font-weight: 900;
        letter-spacing: -0.03em;
    }

    .loyalty-transaction-balance {
        margin-top: 0.2rem;
        color: #64748b;
        font-size: 0.74rem;
    }

    @media (max-width: 991.98px) {
        .loyalty-hero {
            padding: 0.95rem;
            border-radius: 18px;
        }

        .loyalty-hero__grid {
            grid-template-columns: 1fr;
        }

        .loyalty-title {
            max-width: none;
        }

        .loyalty-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .loyalty-page {
            padding-top: 0.75rem !important;
            padding-bottom: 1rem !important;
        }

        .loyalty-panel__body {
            padding: 0.75rem;
        }

        .loyalty-tabs {
            padding-inline: 0.75rem;
        }

        .loyalty-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .loyalty-transaction-card {
            grid-template-columns: auto 1fr;
        }

        .loyalty-transaction-summary {
            grid-column: 2 / -1;
            text-align: left;
            padding-left: 3.95rem;
        }
    }

    @media (max-width: 575.98px) {
        .loyalty-hero {
            padding: 0.85rem;
            border-radius: 16px;
        }

        .loyalty-stats {
            grid-template-columns: 1fr;
        }

        .loyalty-stat--wide {
            grid-column: auto;
        }

        .loyalty-voucher-grid {
            grid-template-columns: 1fr;
        }

        .loyalty-tabs {
            overflow-x: auto;
            overflow-y: hidden;
            flex-wrap: nowrap;
            white-space: nowrap;
        }

        .loyalty-tabs .nav-item {
            flex: 0 0 auto;
        }
    }
</style>

<div class="container loyalty-page py-3 py-lg-4">
    <div class="loyalty-shell">
        <section class="loyalty-hero" aria-labelledby="loyalty-title">
            <div class="loyalty-hero__grid">
                <div class="loyalty-hero__copy">
                    <div class="loyalty-kicker">
                        <i class="bi bi-star-fill"></i>
                        Điểm thưởng
                    </div>
                    <h1 class="loyalty-title" id="loyalty-title">Tích điểm mỗi đơn và đổi voucher ngay trong tài khoản</h1>
                    <p class="loyalty-description">
                        Điểm được cộng tự động khi đơn hàng hoàn thành. Dùng điểm để đổi voucher ưu đãi cho lần đặt tiếp theo.
                    </p>

                    <div class="loyalty-points-block">
                        <div class="loyalty-points-number">{{ number_format($loyaltyPoint->total_points, 0, ',', '.') }}</div>
                        <div class="loyalty-points-label">Điểm hiện tại</div>
                    </div>

                    <div class="loyalty-rule">
                        <i class="bi bi-lightning-charge-fill"></i>
                        Mỗi 10.000đ chi tiêu = 1 điểm thưởng
                    </div>
                </div>

                <div class="loyalty-stats" aria-label="Tóm tắt điểm thưởng">
                    <div class="loyalty-stat">
                        <div class="loyalty-stat__label">Điểm tháng này</div>
                        <div class="loyalty-stat__value">{{ number_format($loyaltyPoint->monthly_points, 0, ',', '.') }}</div>
                        <div class="loyalty-stat__copy">Điểm tích lũy trong tháng hiện tại.</div>
                    </div>

                    <div class="loyalty-stat">
                        <div class="loyalty-stat__label">Tổng tích lũy</div>
                        <div class="loyalty-stat__value">{{ number_format($loyaltyPoint->lifetime_points, 0, ',', '.') }}</div>
                        <div class="loyalty-stat__copy">Toàn bộ điểm bạn đã kiếm được.</div>
                    </div>

                    <div class="loyalty-stat loyalty-stat--wide">
                        <div class="loyalty-stat__label">Quy đổi nhanh</div>
                        <div class="loyalty-stat__value">10k = 1</div>
                        <div class="loyalty-stat__copy">Mỗi 10.000đ chi tiêu tương ứng 1 điểm thưởng.</div>
                    </div>
                </div>
            </div>
        </section>

        <div class="loyalty-info">
            <div class="loyalty-info__icon">
                <i class="bi bi-info-lg"></i>
            </div>
            <div>
                <div class="loyalty-info__title">Cách tích điểm</div>
                <div class="loyalty-info__copy">
                    Điểm được cộng tự động khi đơn hàng hoàn thành. Voucher đổi điểm sẽ được thêm vào tài khoản ngay sau khi đổi thành công.
                </div>
            </div>
        </div>

        <section class="loyalty-panel" aria-label="Điểm thưởng và lịch sử">
            <ul class="nav nav-tabs loyalty-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#redeemVouchers" type="button" role="tab" aria-controls="redeemVouchers" aria-selected="true">
                        <i class="bi bi-gift me-2"></i>Đổi điểm lấy voucher
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#transactionHistory" type="button" role="tab" aria-controls="transactionHistory" aria-selected="false">
                        <i class="bi bi-clock-history me-2"></i>Lịch sử giao dịch
                    </button>
                </li>
            </ul>

            <div class="tab-content loyalty-panel__body">
                <div class="tab-pane fade show active" id="redeemVouchers" role="tabpanel">
                    <div class="loyalty-voucher-grid">
                        @forelse($redeemableVouchers ?? [] as $voucher)
                            @php
                                $canRedeem = $loyaltyPoint->total_points >= $voucher->point_cost;
                                $missingPoints = max(0, (int) $voucher->point_cost - (int) $loyaltyPoint->total_points);
                            @endphp

                            <div
                                class="loyalty-voucher-card {{ !$canRedeem ? 'is-disabled' : '' }}"
                                @if($canRedeem) onclick="document.getElementById('redeem-form-{{ $voucher->id }}').submit()" role="button" tabindex="0" @endif
                            >
                                <div class="loyalty-voucher-card__top">
                                    <span class="loyalty-voucher-chip">
                                        <i class="bi bi-coin"></i>
                                        {{ number_format($voucher->point_cost, 0, ',', '.') }} điểm
                                    </span>
                                    <span class="loyalty-voucher-chip {{ $canRedeem ? '' : 'is-muted' }}">
                                        {{ $canRedeem ? 'Sẵn sàng đổi' : 'Thiếu '.number_format($missingPoints, 0, ',', '.').' điểm' }}
                                    </span>
                                </div>

                                <div>
                                    <h3 class="loyalty-voucher-code">{{ $voucher->code }}</h3>
                                    <p class="loyalty-voucher-desc">{{ $voucher->description }}</p>
                                    <div class="loyalty-voucher-highlight">
                                        Giảm {{ $voucher->type === 'percent' ? $voucher->value . '%' : number_format($voucher->value, 0, ',', '.') . 'đ' }}
                                        @if($voucher->max_discount)
                                            <small>(Tối đa {{ number_format($voucher->max_discount, 0, ',', '.') }}đ)</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="loyalty-voucher-footer">
                                    @if(! $canRedeem)
                                        <div class="alert alert-warning mb-0 p-2 small" style="border-radius: 14px; border: 0; background: #fff7ed; color: #9a3412;">
                                            <i class="bi bi-lock-fill me-1"></i>Chưa đủ điểm để đổi voucher này.
                                        </div>
                                    @else
                                        <form id="redeem-form-{{ $voucher->id }}" method="POST" action="{{ route('loyalty.redeem-voucher', $voucher) }}" style="display: none;">
                                            @csrf
                                        </form>
                                        <button
                                            type="button"
                                            class="btn btn-primary w-100 loyalty-voucher-btn"
                                            onclick="event.stopPropagation(); document.getElementById('redeem-form-{{ $voucher->id }}').submit();"
                                        >
                                            <i class="bi bi-gift me-2"></i>Đổi ngay
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="loyalty-empty">
                                    <div>
                                        <div class="loyalty-empty__icon">
                                            <i class="bi bi-gift"></i>
                                        </div>
                                        <h3 class="h5 fw-bold mb-2">Chưa có voucher khả dụng</h3>
                                        <p class="mb-0">Các voucher đổi điểm sẽ xuất hiện tại đây khi hệ thống có ưu đãi phù hợp.</p>
                                    </div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="tab-pane fade" id="transactionHistory" role="tabpanel">
                    <div class="loyalty-transaction-list">
                        @forelse($transactions ?? [] as $transaction)
                            <article class="loyalty-transaction-card">
                                <div class="loyalty-transaction-icon">
                                    <i class="{{ $transaction->typeIcon() }}"></i>
                                </div>

                                <div>
                                    <div class="loyalty-transaction-title">{{ $transaction->typeDisplayName() }}</div>
                                    <div class="loyalty-transaction-desc">{{ $transaction->description ?? 'Không có mô tả' }}</div>
                                    <div class="loyalty-transaction-time">{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
                                </div>

                                <div class="loyalty-transaction-summary">
                                    <div class="loyalty-transaction-points {{ $transaction->points > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->formattedPoints() }}
                                    </div>
                                    <div class="loyalty-transaction-balance">
                                        Số dư: {{ number_format($transaction->balance_after, 0, ',', '.') }}
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="loyalty-empty">
                                <div>
                                    <div class="loyalty-empty__icon">
                                        <i class="bi bi-receipt"></i>
                                    </div>
                                    <h3 class="h5 fw-bold mb-2">Chưa có giao dịch nào</h3>
                                    <p class="mb-0">Lịch sử tích điểm và tiêu điểm sẽ hiển thị tại đây.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if(isset($transactions) && $transactions->hasPages())
                        <div class="mt-4">
                            {{ $transactions->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    document.addEventListener('loyalty:points-updated', function () {
        location.reload();
    });
</script>

@endsection
