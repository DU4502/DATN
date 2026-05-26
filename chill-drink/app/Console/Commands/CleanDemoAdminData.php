<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CleanDemoAdminData extends Command
{
    protected $signature = 'admin:clean-demo-data';

    protected $description = 'Xóa khách hàng demo (faker) trong admin, giữ tài khoản quản trị';

    public function handle(): int
    {
        $deleted = User::customers()->delete();

        $this->info("Đã xóa {$deleted} khách hàng demo.");
        $this->info('Còn lại: '.User::admins()->count().' quản trị viên, '.User::customers()->count().' khách hàng.');

        return self::SUCCESS;
    }
}
