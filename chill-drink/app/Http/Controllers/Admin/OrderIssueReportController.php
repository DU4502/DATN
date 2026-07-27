<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderIssueReport;
use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Notifications\OrderIssueReportStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class OrderIssueReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrderIssueReport::with(['order.branch', 'order.orderItems.product', 'order.orderItems.productSize', 'user', 'handler'])->latest();
        if (! $request->user()->isSuperAdmin()) {
            $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('branch_id', $request->user()->branch_id));
        }
        $reports = $query->paginate(20);
        return view('admin.order-issues.index', compact('reports'));
    }

    public function update(Request $request, OrderIssueReport $issue): RedirectResponse
    {
        $issue->load('order');
        abort_unless($request->user()->isSuperAdmin() || (int) $issue->order->branch_id === (int) $request->user()->branch_id, 403);
        $data = $request->validate([
            'status' => ['required', 'in:open,processing,approved,remedy_in_progress,awaiting_customer,resolved,rejected'],
            'resolution_type' => ['nullable', 'in:redelivery,refund,voucher,other'],
            'resolution_value' => ['nullable', 'string', 'max:255'],
            'estimated_at' => ['nullable', 'date'],
            'admin_note' => ['nullable', 'string', 'max:1500'],
        ]);

        $allowedTransitions = [
            'open' => ['open', 'processing', 'rejected'],
            'processing' => ['processing', 'approved', 'rejected'],
            'approved' => ['approved', 'remedy_in_progress', 'rejected'],
            'remedy_in_progress' => ['remedy_in_progress', 'awaiting_customer'],
            'awaiting_customer' => ['awaiting_customer', 'resolved'],
            'resolved' => ['resolved'],
            'rejected' => ['rejected'],
        ];
        if (! in_array($data['status'], $allowedTransitions[$issue->status] ?? [$issue->status], true)) {
            return back()->withErrors(['status' => 'Không thể chuyển lùi hoặc bỏ qua bước xử lý của yêu cầu.'])->withInput();
        }
        if (! empty($data['estimated_at'])) {
            $estimatedAt = Carbon::parse($data['estimated_at']);
            $isChangingEstimate = ! $issue->estimated_at || ! $issue->estimated_at->equalTo($estimatedAt);
            if ($isChangingEstimate && $estimatedAt->isPast()) {
                return back()->withErrors(['estimated_at' => 'Thời gian dự kiến phải từ thời điểm hiện tại trở đi.'])->withInput();
            }
        }

        if (in_array($data['status'], ['approved', 'remedy_in_progress', 'awaiting_customer', 'resolved'], true) && empty($data['resolution_type'])) {
            return back()->withErrors(['resolution_type' => 'Hãy chọn phương án hỗ trợ cho khách trước khi duyệt.'])->withInput();
        }

        if ($data['resolution_type'] === 'voucher' && in_array($data['status'], ['approved', 'remedy_in_progress', 'awaiting_customer', 'resolved'], true) && ! $issue->voucher_coupon_id) {
            $voucherAmount = (int) $issue->order->total;
            if ($voucherAmount <= 0) {
                return back()->withErrors(['resolution_type' => 'Không thể cấp voucher vì tổng tiền đơn không hợp lệ.'])->withInput();
            }

            DB::transaction(function () use ($issue, $voucherAmount, &$data): void {
                $issue = OrderIssueReport::query()->lockForUpdate()->with('order')->findOrFail($issue->id);
                if ($issue->voucher_coupon_id) {
                    $data['resolution_value'] = $issue->resolution_value;
                    return;
                }
                $code = 'HT'.str_pad((string) $issue->id, 6, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(6));
                $voucher = Voucher::create([
                    'code' => $code,
                    'type' => Voucher::TYPE_FIXED,
                    'value' => $voucherAmount,
                    'description' => 'Voucher hỗ trợ cho đơn '.($issue->order->order_code ?? '#'.$issue->order_id),
                    'min_order' => 0,
                    'usage_limit' => 1,
                    'used_count' => 0,
                    'starts_at' => now(),
                    'expires_at' => now()->addDays(30),
                    'status' => true,
                    'point_cost' => 0,
                    'is_redeemable' => false,
                    'created_at' => now(),
                ]);
                UserVoucher::create([
                    'user_id' => $issue->user_id,
                    'coupon_id' => $voucher->id,
                    'code' => $voucher->code,
                    'is_used' => false,
                    'expires_at' => $voucher->expires_at,
                    'redeemed_at' => now(),
                ]);
                $issue->update(['voucher_coupon_id' => $voucher->id]);
                $data['resolution_value'] = 'Mã '.$voucher->code.' giảm '.number_format($voucherAmount, 0, ',', '.').'đ, dùng đến '.$voucher->expires_at->format('d/m/Y');
            });
            $issue->refresh();
        }
        if ($data['resolution_type'] === 'voucher' && $issue->voucher_coupon_id) {
            $data['resolution_value'] = $issue->resolution_value;
        }

        $timestamps = match ($data['status']) {
            'processing' => ['processing_at' => $issue->processing_at ?? now()],
            'approved' => ['approved_at' => $issue->approved_at ?? now()],
            'remedy_in_progress' => ['remedy_started_at' => $issue->remedy_started_at ?? now()],
            'resolved' => ['resolved_at' => $issue->resolved_at ?? now()],
            'rejected' => ['rejected_at' => $issue->rejected_at ?? now()],
            default => [],
        };
        $refundTimestamp = $data['resolution_type'] === 'refund'
            && $issue->order->payment_method === 'vnpay'
            && $issue->order->payment_status === 'paid'
            ? ['refund_requested_at' => $issue->refund_requested_at ?? now()]
            : [];
        $issue->update([...$data, ...$timestamps, ...$refundTimestamp, 'handled_by' => $request->user()->id]);
        $issue->load('order');
        \App\Support\ChatHelper::notifyOrderIssueStatus($issue, $request->user());
        $issue->user->notify(new OrderIssueReportStatusNotification($issue));
        return back()->with('success', 'Đã cập nhật yêu cầu hỗ trợ.');
    }

    public function evidence(Request $request, OrderIssueReport $issue)
    {
        $issue->load('order');
        abort_unless($request->user()->isSuperAdmin() || (int) $issue->order->branch_id === (int) $request->user()->branch_id, 403);
        abort_unless(filled($issue->evidence_path), 404);

        if (Storage::disk('local')->exists($issue->evidence_path)) {
            return Storage::disk('local')->response($issue->evidence_path, $issue->evidence_name);
        }

        abort_unless(Storage::disk('public')->exists($issue->evidence_path), 404);
        return Storage::disk('public')->response($issue->evidence_path, $issue->evidence_name);
    }

}
