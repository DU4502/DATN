<?php $__env->startSection('content'); ?>

<?php
use App\Models\LoyaltyPoint;

$loyaltyPoint = $loyaltyPoint ?? LoyaltyPoint::getOrCreateForUser(auth()->id());
?>

<style>
    .loyalty-card {
        background: linear-gradient(135deg, #0D9373, #16a34a);
        border-radius: 24px;
        padding: 2rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        color: #ffffff;
        position: relative;
        overflow: hidden;
    }

    .loyalty-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .points-display {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        color: #ffffff;
    }

    .transaction-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 1.25rem;
        transition: all 0.2s ease;
    }

    .transaction-card:hover {
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        transform: translateY(-2px);
    }

    .voucher-redeem-card {
        background: linear-gradient(135deg, #fff, #f9fafb);
        border: 2px solid #e5e7eb;
        border-radius: 20px;
        padding: 1.5rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .voucher-redeem-card:hover {
        border-color: #0D9373;
        box-shadow: 0 12px 32px rgba(13, 147, 115, 0.15);
        transform: translateY(-4px);
    }

    .voucher-redeem-card.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .voucher-redeem-card.disabled:hover {
        transform: none;
        box-shadow: none;
    }

    .point-cost-badge {
        background: linear-gradient(135deg, #0D9373, #16a34a);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.95rem;
    }
</style>

<div class="container-fluid py-4">
    <!-- Loyalty Card -->
    <div class="loyalty-card mb-4">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="mb-3">
                    <div class="text-white mb-2" style="font-size: 1.1rem; opacity: 0.9;">Điểm tích lũy của bạn</div>
                    <div class="points-display"><?php echo e(number_format($loyaltyPoint->total_points, 0, ',', '.')); ?></div>
                    <div class="text-white fw-semibold" style="font-size: 1.1rem; opacity: 0.9;">Điểm hiện tại</div>
                </div>
            </div>

            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <div class="d-flex flex-column gap-3">
                    <div class="bg-white bg-opacity-25 backdrop-blur rounded-3 p-3 shadow-sm">
                        <div class="text-white small mb-1" style="opacity: 0.9;">Điểm tích lũy tháng này</div>
                        <div class="h4 fw-bold text-white mb-0"><?php echo e(number_format($loyaltyPoint->monthly_points, 0, ',', '.')); ?></div>
                    </div>
                    <div class="bg-white bg-opacity-25 backdrop-blur rounded-3 p-3 shadow-sm">
                        <div class="text-white small mb-1" style="opacity: 0.9;">Tổng điểm tích lũy</div>
                        <div class="h4 fw-bold text-white mb-0"><?php echo e(number_format($loyaltyPoint->lifetime_points, 0, ',', '.')); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="alert alert-info d-flex align-items-start gap-3 mb-4" style="border-radius: 16px; border: none; background: linear-gradient(135deg, #e0f2fe, #ffffff);">
        <i class="bi bi-info-circle-fill" style="font-size: 1.5rem;"></i>
        <div>
            <div class="fw-bold mb-1">Cách tích điểm:</div>
            <div>Mỗi 10.000đ chi tiêu = 1 điểm thưởng. Điểm được cộng tự động khi đơn hàng hoàn thành.</div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" style="border-bottom: 2px solid #e5e7eb;">
        <li class="nav-item">
            <a class="nav-link active fw-semibold" data-bs-toggle="tab" href="#redeemVouchers">
                <i class="bi bi-gift me-2"></i>Đổi điểm lấy voucher
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold" data-bs-toggle="tab" href="#transactionHistory">
                <i class="bi bi-clock-history me-2"></i>Lịch sử giao dịch
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Redeem Vouchers Tab -->
        <div class="tab-pane fade show active" id="redeemVouchers">
            <div class="row g-4">
                <?php $__empty_1 = true; $__currentLoopData = $redeemableVouchers ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $canRedeem = $loyaltyPoint->total_points >= $voucher->point_cost;
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="voucher-redeem-card <?php echo e(!$canRedeem ? 'disabled' : ''); ?>" 
                         onclick="<?php echo e($canRedeem ? "document.getElementById('redeem-form-{$voucher->id}').submit()" : ''); ?>">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="point-cost-badge">
                                <i class="bi bi-coin me-1"></i><?php echo e(number_format($voucher->point_cost, 0, ',', '.')); ?> điểm
                            </div>
                        </div>

                        <div class="mb-3">
                            <h5 class="fw-bold mb-2"><?php echo e($voucher->code); ?></h5>
                            <p class="text-secondary small mb-2"><?php echo e($voucher->description); ?></p>
                            <div class="fw-bold text-primary">
                                Giảm <?php echo e($voucher->type === 'percent' ? $voucher->value . '%' : number_format($voucher->value, 0, ',', '.') . 'đ'); ?>

                                <?php if($voucher->max_discount): ?>
                                <span class="text-secondary small">(Tối đa <?php echo e(number_format($voucher->max_discount, 0, ',', '.')); ?>đ)</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if(!$canRedeem): ?>
                        <div class="alert alert-danger mb-0 p-2 small">
                            <i class="bi bi-x-circle me-1"></i>Không đủ điểm (thiếu <?php echo e(number_format($voucher->point_cost - $loyaltyPoint->total_points, 0, ',', '.')); ?> điểm)
                        </div>
                        <?php else: ?>
                        <form id="redeem-form-<?php echo e($voucher->id); ?>" method="POST" action="<?php echo e(route('loyalty.redeem-voucher', $voucher)); ?>" style="display: none;">
                            <?php echo csrf_field(); ?>
                        </form>
                        <button type="button" class="btn btn-primary w-100" onclick="event.stopPropagation(); document.getElementById('redeem-form-<?php echo e($voucher->id); ?>').submit();">
                            <i class="bi bi-gift me-2"></i>Đổi ngay
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="display-6 mb-3">🎁</div>
                        <h5 class="fw-bold mb-2">Chưa có voucher khả dụng</h5>
                        <p class="text-secondary">Các voucher đổi điểm sẽ xuất hiện tại đây.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Transaction History Tab -->
        <div class="tab-pane fade" id="transactionHistory">
            <div class="d-flex flex-column gap-3">
                <?php $__empty_1 = true; $__currentLoopData = $transactions ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="transaction-card">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0">
                                <i class="<?php echo e($transaction->typeIcon()); ?>" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <div class="fw-bold mb-1"><?php echo e($transaction->typeDisplayName()); ?></div>
                                <div class="text-secondary small mb-1"><?php echo e($transaction->description ?? 'Không có mô tả'); ?></div>
                                <div class="text-muted small"><?php echo e($transaction->created_at->format('d/m/Y H:i')); ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold <?php echo e($transaction->points > 0 ? 'text-success' : 'text-danger'); ?>" style="font-size: 1.25rem;">
                                <?php echo e($transaction->formattedPoints()); ?>

                            </div>
                            <div class="text-secondary small">Số dư: <?php echo e(number_format($transaction->balance_after, 0, ',', '.')); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center py-5">
                    <div class="display-6 mb-3">📋</div>
                    <h5 class="fw-bold mb-2">Chưa có giao dịch nào</h5>
                    <p class="text-secondary">Lịch sử tích điểm và tiêu điểm sẽ hiển thị tại đây.</p>
                </div>
                <?php endif; ?>
            </div>

            <?php if(isset($transactions) && $transactions->hasPages()): ?>
            <div class="mt-4">
                <?php echo e($transactions->links('pagination::bootstrap-5')); ?>

            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Auto-refresh on point updates via realtime
    document.addEventListener('loyalty:points-updated', function(event) {
        location.reload();
    });
</script>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.client', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\DATN\chill-drink\resources\views/profile/partials/loyalty-points.blade.php ENDPATH**/ ?>