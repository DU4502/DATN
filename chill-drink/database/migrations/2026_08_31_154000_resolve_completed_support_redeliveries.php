<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $completedIssueIds = DB::table('orders')
            ->whereNotNull('support_issue_id')
            ->where('status', 'completed')
            ->pluck('support_issue_id');

        if ($completedIssueIds->isEmpty()) {
            return;
        }

        DB::table('order_issue_reports')
            ->whereIn('id', $completedIssueIds)
            ->whereNotIn('status', ['resolved', 'rejected'])
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Không chuyển lùi yêu cầu đã hoàn tất vì có thể khách đã xác nhận sau migration.
    }
};
