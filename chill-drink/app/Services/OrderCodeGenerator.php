<?php

namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sinh mã đơn hàng theo định dạng:
 *   [MãChiNhánh]-[LoạiĐơn]-[YYYYMMDD]-[STT]
 *
 * Ví dụ: TH-ON-20260720-0001, HN-OF-20260720-0003
 *
 * Quy tắc:
 *  - MãChiNhánh : cột `code` của bảng branches (TH, HN, DN…)
 *  - LoạiĐơn    : ON = Online (delivery), OF = Offline (pickup)
 *  - YYYYMMDD   : ngày tạo đơn theo giờ Việt Nam (Asia/Ho_Chi_Minh)
 *  - STT        : số thứ tự 4 chữ số, reset mỗi ngày, riêng theo
 *                 tổ hợp (branch_code + type + date)
 */
class OrderCodeGenerator
{
    /**
     * Sinh mã đơn hàng duy nhất, an toàn trong môi trường concurrency cao.
     *
     * Phải được gọi bên trong một DB transaction đang mở (DB::beginTransaction).
     *
     * @param  int|null  $branchId      ID chi nhánh (nullable)
     * @param  string    $fulfillmentType  'delivery' | 'pickup'
     * @return string    Mã đơn hàng, e.g. "TH-ON-20260720-0001"
     */
    public static function generate(?int $branchId, string $fulfillmentType): string
    {
        $branchCode = static::resolveBranchCode($branchId);
        $typeCode   = static::resolveTypeCode($fulfillmentType);
        $dateStr    = now()->timezone('Asia/Ho_Chi_Minh')->format('Ymd');
        $prefix     = "{$branchCode}-{$typeCode}-{$dateStr}";

        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'order_code')) {
            return $prefix;
        }

        // Dùng SELECT ... FOR UPDATE để lock các row có cùng prefix trong ngày,
        // ngăn race condition khi nhiều request đồng thời.
        $lastCode = DB::table('orders')
            ->where('order_code', 'like', "{$prefix}-%")
            ->lockForUpdate()
            ->orderByDesc('order_code')
            ->value('order_code');

        $nextSeq = $lastCode
            ? (int) substr($lastCode, strrpos($lastCode, '-') + 1) + 1
            : 1;

        return sprintf('%s-%04d', $prefix, $nextSeq);
    }

    // -------------------------------------------------------------------------

    /**
     * Lấy mã chi nhánh từ DB. Nếu không tìm thấy chi nhánh thì fallback về 'XX'.
     */
    private static function resolveBranchCode(?int $branchId): string
    {
        if (! $branchId) {
            return 'XX';
        }

        $code = Branch::where('id', $branchId)->value('code');

        return $code ? strtoupper($code) : 'XX';
    }

    /**
     * Chuyển fulfillment_type sang mã 2 ký tự.
     *   pickup   → OF (Offline)
     *   delivery → ON (Online)
     */
    private static function resolveTypeCode(string $fulfillmentType): string
    {
        return match (strtolower($fulfillmentType)) {
            'pickup' => 'OF',
            default  => 'ON',
        };
    }
}
