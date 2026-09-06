<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Conversations:\n";
echo json_encode(\App\Models\Conversation::select('id', 'branch_id', 'user_id', 'cskh_id')->get()) . "\n";
echo "Users with branch_id:\n";
echo json_encode(\App\Models\User::whereNotNull('branch_id')->get(['id', 'name', 'role_id', 'branch_id'])) . "\n";
