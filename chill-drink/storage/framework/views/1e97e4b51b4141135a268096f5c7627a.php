<?php $__env->startSection('title', 'Đăng Nhập'); ?>

<?php $__env->startSection('content'); ?>
<style>
    .auth-page {
        min-height: calc(100vh - 80px);
        display: flex;
        align-items: center;
        background:
            linear-gradient(90deg, rgba(255, 255, 255, 0.78) 0%, rgba(255, 255, 255, 0.34) 46%, rgba(255, 255, 255, 0.08) 100%),
            url('https://png.pngtree.com/background/20250106/original/pngtree-bubble-tea-cup-with-splashing-milk-summer-drinks-background-picture-image_15464755.jpg') center/cover no-repeat;
        position: relative;
    }
    .auth-page::before {
        content: ''; position: absolute; inset: 0;
        background: linear-gradient(135deg, rgba(0, 139, 122, 0.10) 0%, rgba(255, 246, 225, 0.22) 100%);
    }
    
    .auth-container { position: relative; z-index: 1; padding: 4rem 0; width: 100%; }

    .auth-card {
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border: 1px solid rgba(255, 255, 255, 0.72);
        border-radius: var(--radius-2xl);
        box-shadow: 0 24px 58px rgba(12, 54, 47, 0.22);
        overflow: hidden;
    }

    .auth-header {
        text-align: center; margin-bottom: 2rem;
    }
    .auth-logo {
        width: 64px; height: 64px; border-radius: var(--radius-lg);
        background: #fff; display: inline-flex; align-items: center; justify-content: center;
        box-shadow: var(--shadow-md); margin-bottom: 1.5rem; border: 1px solid var(--c-border);
    }
    .auth-logo img { width: 44px; height: 44px; object-fit: contain; }

    .form-floating > .form-control {
        border-radius: var(--radius-md); border-color: var(--c-border);
    }
    .form-floating > .form-control:focus {
        border-color: var(--c-primary); box-shadow: 0 0 0 4px var(--c-primary-glow);
    }

    .btn-auth-submit {
        height: 54px; border-radius: var(--radius-md); font-size: 1rem; font-weight: 700;
        background: linear-gradient(135deg, var(--c-primary) 0%, var(--c-primary-dark) 100%);
        border: none; color: #fff; box-shadow: 0 8px 16px rgba(13,147,115,0.3); transition: all 0.3s ease;
    }
    .btn-auth-submit:hover { transform: translateY(-3px); box-shadow: 0 12px 20px rgba(13,147,115,0.4); color: #fff; }

    .auth-divider { color: var(--c-subtle); margin: 2rem 0; font-size: 0.8125rem; font-weight: 600; text-transform: uppercase; }
    
    .social-btn {
        height: 48px; border-radius: var(--radius-md); font-weight: 600; font-size: 0.95rem;
        background: var(--c-bg); border: 1.5px solid var(--c-border); color: var(--c-ink-secondary);
        display: flex; align-items: center; justify-content: center; gap: 0.75rem; transition: all 0.2s;
    }
    .social-btn:hover { background: var(--c-surface); transform: translateY(-2px); box-shadow: var(--shadow-sm); }
</style>

<section class="auth-page">
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-5 col-xl-4">
                    <div class="auth-card">
                        <div class="p-4 p-md-5">
                            <div class="auth-header">
                                <div class="auth-logo"><img src="<?php echo e(asset('images/logo.png')); ?>" alt="Chill Drink"></div>
                                <h1 class="h3 fw-bold mb-2">Đăng nhập tài khoản</h1>
                                <p class="text-secondary">Chào mừng bạn trở lại với Chill Drink</p>
                            </div>

                            <?php if(session('status')): ?>
                                <div class="alert alert-success d-flex align-items-center mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?php echo e(session('status')); ?></div>
                            <?php endif; ?>
                            <?php if(session('error')): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo e(session('error')); ?></div>
                            <?php endif; ?>

                            <?php if(session('oauth_error')): ?>
                                <div class="alert alert-danger d-flex align-items-center mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo e(session('oauth_error')); ?></div>
                            <?php endif; ?>

                            <form method="POST" action="<?php echo e(route('login')); ?>">
                                <?php echo csrf_field(); ?>

                                <div class="form-floating mb-3">
                                    <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="name@example.com" required autofocus autocomplete="username">
                                    <label for="email">Địa chỉ Email</label>
                                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="form-floating mb-4">
                                    <input id="password" type="password" name="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="Mật khẩu" required autocomplete="current-password">
                                    <label for="password">Mật khẩu</label>
                                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <div class="invalid-feedback"><?php echo e($message); ?></div> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div class="form-check">
                                        <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                        <label class="form-check-label fw-medium text-secondary" for="remember_me" style="font-size: 0.9rem;">Ghi nhớ đăng nhập</label>
                                    </div>
                                    <?php if(Route::has('password.request')): ?>
                                        <a href="<?php echo e(route('password.request')); ?>" class="text-primary fw-semibold text-decoration-none" style="font-size: 0.9rem;">Quên mật khẩu?</a>
                                    <?php endif; ?>
                                </div>

                                <button type="submit" class="btn btn-auth-submit w-100">Đăng Nhập</button>
                            </form>

                            <div class="auth-divider d-flex align-items-center gap-3">
                                <hr class="flex-grow-1 m-0"><span>Hoặc</span><hr class="flex-grow-1 m-0">
                            </div>

                            <div class="d-flex flex-column gap-3 mb-4">
                                <a href="<?php echo e(route('auth.google.redirect')); ?>" class="btn social-btn"><i class="bi bi-google text-danger fs-5"></i> Tiếp tục với Google</a>
                                <a href="<?php echo e(route('auth.facebook.redirect')); ?>" class="btn social-btn"><i class="bi bi-facebook text-primary fs-5"></i> Tiếp tục với Facebook</a>
                            </div>

                            <p class="text-center text-secondary mb-0">
                                Chưa có tài khoản? <a href="<?php echo e(route('register')); ?>" class="text-primary fw-bold text-decoration-none ms-1">Đăng ký ngay</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.client', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\DATN\chill-drink\resources\views/auth/login.blade.php ENDPATH**/ ?>