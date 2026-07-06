<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== Kiểm tra tài khoản Admin/Super Admin ===\n\n";

$admins = User::whereIn('role_id', [2, 3])->get();

if ($admins->isEmpty()) {
    echo "❌ Không có tài khoản Admin hoặc Super Admin!\n";
} else {
    echo "✓ Tìm thấy " . $admins->count() . " tài khoản:\n\n";
    foreach ($admins as $admin) {
        echo "Email: {$admin->email}\n";
        echo "Tên: {$admin->name}\n";
        echo "Role ID: {$admin->role_id}\n";
        echo "---\n";
    }
}

echo "\n=== Kiểm tra vai trò (Roles) ===\n\n";
$roles = DB::table('roles')->get();
foreach ($roles as $role) {
    echo "ID: {$role->id}, Name: {$role->name}, Desc: {$role->description}\n";
}
