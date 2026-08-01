<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Các yêu cầu đang ở bước trung gian tiếp tục được nhân viên xử lý.
        DB::table('order_issue_reports')
            ->whereIn('status', ['approved', 'remedy_in_progress', 'awaiting_customer'])
            ->update(['status' => 'processing']);

        // Ngừng hoàn tiền trong luồng hỗ trợ, giữ nội dung cũ dưới dạng phương án khác.
        DB::table('order_issue_reports')
            ->where('resolution_type', 'refund')
            ->update(['resolution_type' => 'other']);

        DB::table('order_issue_reports')
            ->where('type', 'refund_request')
            ->update(['type' => 'other']);
    }

    public function down(): void
    {
        // Không thể khôi phục chính xác trạng thái cũ sau khi đã rút gọn quy trình.
    }
};
