<?php $__env->startSection('page-title', 'Phiếu ưu đãi'); ?>
<?php $__env->startSection('hide-topbar-search', true); ?>

<?php $__env->startSection('content'); ?>
<section class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
    <div>
        <p class="admin-kicker mb-1">Quản lý khuyến mãi</p>
        <h2 class="h2 fw-bold mb-1">Phiếu ưu đãi</h2>
        <p class="text-secondary mb-0">Quản lý mã giảm giá và mã đổi điểm.</p>
    </div>
    <a href="<?php echo e(route('admin.vouchers.create')); ?>" class="btn btn-primary align-self-start align-self-lg-auto">
        <i class="bi bi-plus-lg me-1"></i>Thêm mã
    </a>
</section>

<section class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Tổng mã</p>
            <p class="admin-value text-primary mb-0"><?php echo e($stats['total']); ?></p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Đang hoạt động</p>
            <p class="admin-value mb-0"><?php echo e($stats['active']); ?></p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Đã lên lịch</p>
            <p class="admin-value mb-0"><?php echo e($stats['scheduled']); ?></p>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="admin-card p-4 h-100">
            <p class="admin-kicker mb-1">Lượt đã dùng</p>
            <p class="admin-value mb-0" style="color: var(--a-danger);"><?php echo e($stats['used']); ?></p>
        </div>
    </div>
</section>

<section class="admin-card p-4 mb-4">
    <form method="GET" action="<?php echo e(route('admin.vouchers.index')); ?>" class="row g-3 align-items-end">
        <div class="col-lg-6">
            <label for="q" class="admin-kicker mb-2 d-block">Tìm kiếm</label>
            <input id="q" type="search" name="q" value="<?php echo e(request('q')); ?>" class="admin-input" placeholder="Tìm mã hoặc mô tả voucher">
        </div>
        <div class="col-sm-7 col-lg-3">
            <label for="status" class="admin-kicker mb-2 d-block">Trạng thái</label>
            <select id="status" name="status" class="admin-filter">
                <option value="">Tất cả trạng thái</option>
                <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php if(request('status') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="col-sm-5 col-lg-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search me-1"></i>Lọc</button>
            <a href="<?php echo e(route('admin.vouchers.index')); ?>" class="btn btn-outline-primary" title="Xóa lọc"><i class="bi bi-x-lg"></i></a>
        </div>
    </form>
</section>

<section class="admin-card admin-table-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle" style="min-width: 1120px;">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Mô tả</th>
                    <th>Giá trị</th>
                    <th>Giảm tối đa</th>
                    <th>Điểm đổi</th>
                    <th>Sử dụng</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $isScheduled = $voucher->status && $voucher->starts_at && $voucher->starts_at->gt(now());
                        $isExpired = $voucher->expires_at && $voucher->expires_at->lt(now());
                        $isOutOfUses = ! $voucher->hasRemainingUses();
                    ?>
                    <tr>
                        <td>
                            <div class="fw-bold text-primary"><?php echo e($voucher->code); ?></div>
                            <small class="text-secondary"><?php echo e($voucher->type === 'percent' ? 'Phần trăm' : 'Cố định'); ?></small>
                        </td>
                        <td style="max-width: 260px;">
                            <span class="d-block text-truncate"><?php echo e($voucher->description ?: '-'); ?></span>
                            <?php if($voucher->min_order > 0): ?>
                                <small class="text-secondary">Đơn từ <?php echo e(number_format($voucher->min_order, 0, ',', '.')); ?>đ</small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-soft-muted"><?php echo e($voucher->formattedValue()); ?></span></td>
                        <td class="fw-bold">
                            <?php echo e($voucher->max_discount ? number_format($voucher->max_discount, 0, ',', '.') . 'đ' : '-'); ?>

                        </td>
                        <td class="fw-bold">
                            <?php echo e($voucher->point_cost > 0 ? number_format($voucher->point_cost, 0, ',', '.') . ' điểm' : '-'); ?>

                        </td>
                        <td><?php echo e($voucher->usageText()); ?></td>
                        <td>
                            <?php if(! $voucher->status): ?>
                                <span class="badge badge-soft-danger">Đã tắt</span>
                            <?php elseif($isScheduled): ?>
                                <span class="badge badge-soft-muted">Đã lên lịch</span>
                            <?php elseif($isExpired): ?>
                                <span class="badge badge-soft-danger">Hết hạn</span>
                            <?php elseif($isOutOfUses): ?>
                                <span class="badge badge-soft-danger">Hết lượt</span>
                            <?php else: ?>
                                <span class="badge badge-soft-primary">Hoạt động</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-secondary"><?php echo e(optional($voucher->created_at)->format('d/m/Y H:i') ?: '-'); ?></td>
                        <td class="text-end text-nowrap">
                            <a href="<?php echo e(route('admin.vouchers.edit', $voucher)); ?>" class="admin-action text-decoration-none" title="Sửa"><i class="bi bi-pencil"></i></a>
                            <form action="<?php echo e(route('admin.vouchers.destroy', $voucher)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Xóa voucher <?php echo e($voucher->code); ?>?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="admin-action" title="Xóa" style="color: var(--a-danger);"><i class="bi bi-trash3"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">
                            <div class="fw-bold text-dark mb-1">Chưa có voucher phù hợp</div>
                            <div>Hãy tạo mã mới hoặc xóa bộ lọc hiện tại.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="admin-pagination-footer">
        <p class="text-secondary mb-0">Đang hiển thị <?php echo e($vouchers->count()); ?> / <?php echo e($vouchers->total()); ?> voucher</p>
        <?php echo e($vouchers->onEachSide(1)->links()); ?>

    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make(auth()->user()?->isSuperAdmin() ? 'layouts.super-admin' : 'layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\DATN\chill-drink\resources\views/admin/vouchers/index.blade.php ENDPATH**/ ?>