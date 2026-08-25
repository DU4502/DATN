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
use Illuminate\Validation\ValidationException;
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

        [$issue, $changed] = DB::transaction(function () use ($issue, $request, $data): array {
            $lockedIssue = OrderIssueReport::query()->lockForUpdate()->with(['order', 'user'])->findOrFail($issue->id);
            $user = $request->user();
            $branchId = $user->isSuperAdmin() && $user->isViewingAdminWorkspace()
                ? $user->adminWorkspaceBranchId()
                : ($user->isSuperAdmin() ? null : $user->branch_id);
            abort_unless($branchId === null || (int) $lockedIssue->order->branch_id === (int) $branchId, 403);

            $allowedTransitions = [
                'open' => ['open', 'processing', 'rejected'],
                'processing' => ['processing', 'resolved', 'rejected'],
                'resolved' => ['resolved'],
                'rejected' => ['rejected'],
            ];

            if (! in_array($data['status'], $allowedTransitions[$lockedIssue->status] ?? [$lockedIssue->status], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể chuyển lùi hoặc bỏ qua bước xử lý của yêu cầu.',
                ]);
            }
            if ($data['status'] === 'resolved' && empty($data['resolution_type'])) {
                throw ValidationException::withMessages([
                    'resolution_type' => 'Hãy chọn phương án hỗ trợ cho khách trước khi hoàn tất.',
                ]);
            }

            if (($data['resolution_type'] ?? null) === 'voucher' && $data['status'] === 'resolved') {
                $voucherAmount = (int) $lockedIssue->order->total;
                if ($voucherAmount <= 0) {
                    throw ValidationException::withMessages([
                        'resolution_type' => 'Không thể cấp voucher vì tổng tiền đơn không hợp lệ.',
                    ]);
                }

                if (! $lockedIssue->voucher_coupon_id) {
                    $code = 'HT'.str_pad((string) $lockedIssue->id, 6, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(6));
                    $voucher = Voucher::create([
                        'code' => $code,
                        'type' => Voucher::TYPE_FIXED,
                        'value' => $voucherAmount,
                        'max_discount' => 0,
                        'description' => 'Voucher hỗ trợ cho đơn '.($lockedIssue->order->order_code ?? '#'.$lockedIssue->order_id),
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
                        'user_id' => $lockedIssue->user_id,
                        'coupon_id' => $voucher->id,
                        'code' => $voucher->code,
                        'is_used' => false,
                        'expires_at' => $voucher->expires_at,
                        'redeemed_at' => now(),
                    ]);
                    $lockedIssue->voucher_coupon_id = $voucher->id;
                    $data['resolution_value'] = 'Mã '.$voucher->code.' giảm '.number_format($voucherAmount, 0, ',', '.').'đ, dùng đến '.$voucher->expires_at->format('d/m/Y');
                } else {
                    $data['resolution_value'] = $lockedIssue->resolution_value;
                }
            }

            $timestamps = match ($data['status']) {
                'processing' => ['processing_at' => $lockedIssue->processing_at ?? now()],
                'resolved' => ['resolved_at' => $lockedIssue->resolved_at ?? now()],
                'rejected' => ['rejected_at' => $lockedIssue->rejected_at ?? now()],
                default => [],
            };
            $lockedIssue->fill([...$data, ...$timestamps, 'estimated_at' => null, 'handled_by' => $request->user()->id]);
            $changed = $lockedIssue->isDirty();
            $lockedIssue->save();

            return [$lockedIssue->fresh(['order', 'user']), $changed];
        });
        if ($changed) {
            \App\Support\ChatHelper::notifyOrderIssueStatus($issue, $request->user());
            $issue->user->notify(new OrderIssueReportStatusNotification($issue));
        }
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
