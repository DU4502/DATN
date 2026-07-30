<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Illuminate\Support\Facades\DB::table('roles')->updateOrInsert(
    ['id' => 5],
    ['name' => 'staff', 'description' => 'Nhân viên cửa hàng']
);

echo "SUCCESS DB UPDATE";
