<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = __DIR__;

if (!is_file($root . '/vendor/autoload.php') || !is_file($root . '/bootstrap/app.php')) {
    fwrite(STDERR, "[V27.1] Khong tim thay Laravel project tai: {$root}\n");
    exit(1);
}

require $root . '/vendor/autoload.php';
$app = require_once $root . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$migration = '2026_08_19_180000_create_branch_shipping_fee_settings_table';

try {
    if (Schema::hasTable('branch_shipping_fee_settings')) {
        Schema::disableForeignKeyConstraints();
        Schema::drop('branch_shipping_fee_settings');
        Schema::enableForeignKeyConstraints();
        echo "[V27.1] Da xoa bang dang do do migration V27 bi fail.\n";
    }

    if (Schema::hasTable('migrations')) {
        DB::table('migrations')->where('migration', $migration)->delete();
    }

    echo "[V27.1] Da lam sach trang thai migration loi.\n";
} catch (Throwable $e) {
    try {
        Schema::enableForeignKeyConstraints();
    } catch (Throwable $ignored) {
    }

    fwrite(STDERR, "[V27.1] Loi khi don migration cu: " . $e->getMessage() . "\n");
    exit(1);
}
