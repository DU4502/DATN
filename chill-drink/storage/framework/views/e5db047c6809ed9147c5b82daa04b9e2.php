<?php $__env->startSection('title', 'Đơn Hàng Của Tôi'); ?>

<?php $__env->startSection('content'); ?>
<section class="orders-page py-5">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
            <div>
                <p class="text-primary fw-semibold mb-1">Đơn hàng</p>
                <h1 class="h2 fw-bold mb-1">Đơn hàng của tôi</h1>
                <p class="text-secondary mb-0">Theo dõi lịch sử mua hàng, trạng thái xử lý và tổng thanh toán.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo e(route('profile.edit')); ?>" class="btn btn-outline-primary rounded-pill px-4">
                    <i class="bi bi-person me-1"></i>Tài khoản
                </a>
                <a href="<?php echo e(route('products.index')); ?>" class="btn btn-primary rounded-pill px-4">
                    <i class="bi bi-cup-straw me-1"></i>Mua thêm
                </a>
            </div>
        </div>

        <nav class="profile-tabs" aria-label="Mục tài khoản">
            <a href="<?php echo e(route('profile.edit')); ?>" class="profile-tab">Thông tin</a>
            <a href="<?php echo e(route('profile.orders')); ?>" class="profile-tab active">Đơn hàng của tôi</a>
        </nav>

        <?php if(session('success')): ?>
            <div class="alert alert-success rounded-4 border-0 mb-4"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php echo $__env->make('profile.partials.my-orders', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.client', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\DATN\chill-drink\resources\views/profile/orders.blade.php ENDPATH**/ ?>