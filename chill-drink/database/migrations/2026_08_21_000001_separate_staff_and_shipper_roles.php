<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->updateOrInsert(
            ['id' => User::SHIPPER_ROLE_ID],
            ['name' => 'shipper', 'description' => 'Nhân viên giao hàng'],
        );

        if (! Schema::hasTable('shippers')) {
            return;
        }

        DB::table('users')
            ->where('role_id', User::STAFF_ROLE_ID)
            ->whereIn('id', DB::table('shippers')->select('user_id'))
            ->update(['role_id' => User::SHIPPER_ROLE_ID]);
    }

    public function down(): void
    {
        if (Schema::hasTable('shippers')) {
            DB::table('users')
                ->where('role_id', User::SHIPPER_ROLE_ID)
                ->whereIn('id', DB::table('shippers')->select('user_id'))
                ->update(['role_id' => User::STAFF_ROLE_ID]);
        }

        DB::table('roles')->where('id', User::SHIPPER_ROLE_ID)->delete();
    }
};
