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
use Illuminate\View\View;

class OrderIssueReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = OrderIssueReport::with(['order.branch', 'order.orderItems.product', 'order.orderItems.productSize', 'user', 'handler'])->latest();
        $user = $request->user();
        $branchId = $user->isSuperAdmin() && $user->isViewingAdminWorkspace()
            ? $user->adminWorkspaceBranchId()
            : ($user->isSuperAdmin() ? null : $user->branch_id);

        if ($branchId !== null) {
            $query->whereHas('order', fn ($orderQuery) => $orderQuery->where('branch_id', $branchId));
        }
        $reports = $query->paginate(20);
        return view('admin.order-issues.index', compact('reports'));
    }

    public function update(Request $request, OrderIssueReport $issue): RedirectResponse
    {
        $issue->load('order');
        $user = $request->user();
        $branchId = $user->isSuperAdmin() && $user->isViewingAdminWorkspace()
            ? $user->adminWorkspaceBranchId()
            : ($user->isSuperAdmin() ? null : $user->branch_id);
        abort_unless($branchId === null || (int) $issue->order->branch_id === (int) $branchId, 403);

        $data = $request->validate([
            'status' => ['required', 'in:open,processing,resolved,rejected'],
            'resolution_type' => ['nullable', 'in:redelivery,voucher,other'],
            'resolution_value' => ['nullable', 'string', 'max:255'],
            'admin_note' => ['nullable', 'string', 'max:1500'],
        ]);

        $allowedTransitions = [
            'open' => ['open', 'processing', 'rejected'],
            'processing' => ['processing', 'resolved', 'rejected'],
            'resolved' => ['resolved'],
            'rejected' => ['rejected'],
        ];
        if (! in_array($data['status'], $allowedTransitions[$issue->status] ?? [$issue->status], true)) {
            return back()->withErrors(['status' => 'Không thể chuyển lùi hoặc bỏ qua bước xử lý của yêu cầu.'])->withInput();
        }
        if ($data['status'] === 'resolved' && empty($data['resolution_type'])) {
            return back()->withErrors(['resolution_type' => 'Hãy chọn phương án hỗ trợ cho khách trước khi hoàn tất.'])->withInput();
        }

        if ($data['resolution_type'] === 'voucher' && $data['status'] === 'resolved' && ! $issue->voucher_coupon_id) {
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
                    'max_discount' => 0,
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
            'resolved' => ['resolved_at' => $issue->resolved_at ?? now()],
            'rejected' => ['rejected_at' => $issue->rejected_at ?? now()],
            default => [],
        };
        $issue->update([...$data, ...$timestamps, 'estimated_at' => null, 'handled_by' => $request->user()->id]);
        $issue->load('order');
        \App\Support\ChatHelper::notifyOrderIssueStatus($issue, $request->user());
        $issue->user->notify(new OrderIssueReportStatusNotification($issue));
        return back()->with('success', 'Đã cập nhật yêu cầu hỗ trợ.');
    }

    public function evidence(Request $request, OrderIssueReport $issue)
    {
        $issue->load('order');
        $user = $request->user();
        $branchId = $user->isSuperAdmin() && $user->isViewingAdminWorkspace()
            ? $user->adminWorkspaceBranchId()
            : ($user->isSuperAdmin() ? null : $user->branch_id);
        abort_unless($branchId === null || (int) $issue->order->branch_id === (int) $branchId, 403);

        $evidenceFiles = collect($issue->evidence_files ?? [])
            ->filter(fn ($file) => filled($file['path'] ?? null))
            ->values();
        $fileIndex = max(0, (int) $request->query('file', 0));
        $selectedFile = $evidenceFiles->get($fileIndex);
        $path = $selectedFile['path'] ?? $issue->evidence_path;
        $name = $selectedFile['name'] ?? $issue->evidence_name;

        abort_unless(filled($path), 404);

        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->response($path, $name);
        }

        abort_unless(Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path, $name);
    }

}
