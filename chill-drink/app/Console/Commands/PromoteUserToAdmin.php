<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUserToAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:promote {email : Email của user cần promote}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Promote user thành Super Admin (role_id = 2)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("Không tìm thấy user với email: {$email}");
            return 1;
        }
        
        if ($user->role_id === 2) {
            $this->info("User {$email} đã là Super Admin rồi!");
            return 0;
        }
        
        $user->role_id = 2;
        $user->save();
        
        $this->info("✅ Đã promote user {$email} thành Super Admin!");
        $this->info("Bạn có thể login và truy cập /admin/super-admin");
        
        return 0;
    }
}
