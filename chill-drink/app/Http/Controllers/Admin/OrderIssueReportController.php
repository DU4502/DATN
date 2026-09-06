<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderIssueReport;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemTopping;
use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Notifications\OrderIssueReportStatusNotification;
use App\Services\OrderCodeGenerator;
use App\Support\OrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderIssueReportController extends Controller
{
    public function pendingCount(Request $request): JsonResponse
    {
        $query = OrderIssueReport::query()->where('status', 'open');
        $user = $request->user();
        $branchId = $user->isSuperAdmin() && $user->isViewingAdminWorkspace()
            ? $user->adminWorkspaceBranchId()
            : ($user->isSuperAdmin() ? null : $user->branch_id);

        if ($branchId !== null) {
            $query->whereHas('order', fn ($orders) => $orders->where('branch_id', $branchId));
        }

        $latest = (clone $query)->with(['order:id,order_code', 'user:id,name'])->latest()->first();

        return response()->json([
            'count' => (clone $query)->count(),
            'latest_id' => $latest?->id,
            'message' => $latest
                ? 'Khách '.($latest->user?->name ?? 'hàng').' vừa gửi yêu cầu cho đơn '.($latest->order?->order_code ?? '#'.$latest->order_id).'.'
                : null,
        ]);
    }

    public function index(Request $request): View
    {
        $query = OrderIssueReport::with(['order.branch', 'order.orderItems.product', 'order.orderItems.productSize', 'redeliveryOrder.orderItems.product', 'user', 'handler', 'voucher'])->latest();
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
            'status' => ['required', 'in:open,processing,awaiting_confirmation,resolved,rejected'],
            'resolution_type' => ['nullable', 'in:redelivery,voucher,other'],
            'resolution_value' => ['nullable', 'string', 'max:255'],
            'estimated_at' => ['nullable', 'date', 'after:now'],
            'redelivery_items' => ['nullable', 'array'],
            'redelivery_items.*' => ['nullable', 'integer', 'min:0', 'max:99'],
            'admin_note' => ['nullable', 'string', 'max:1500'],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái xử lý.',
            'status.in' => 'Trạng thái xử lý không hợp lệ.',
            'resolution_type.in' => 'Phương án hỗ trợ không hợp lệ.',
            'resolution_value.max' => 'Nội dung phương án không được vượt quá 255 ký tự.',
            'estimated_at.date' => 'Thời gian dự kiến giao bù không hợp lệ.',
            'estimated_at.after' => 'Thời gian dự kiến giao bù phải ở tương lai.',
            'admin_note.max' => 'Phản hồi cho khách không được vượt quá 1.500 ký tự.',
        ]);

        [$issue, $changed] = DB::transaction(function () use ($issue, $request, $data): array {
            $lockedIssue = OrderIssueReport::query()->lockForUpdate()->with(['order.orderItems.toppingLines', 'user'])->findOrFail($issue->id);
            $user = $request->user();
            $branchId = $user->isSuperAdmin() && $user->isViewingAdminWorkspace()
                ? $user->adminWorkspaceBranchId()
                : ($user->isSuperAdmin() ? null : $user->branch_id);
            abort_unless($branchId === null || (int) $lockedIssue->order->branch_id === (int) $branchId, 403);

            // Trang quản trị có thể đang cũ trong lúc khách vừa xác nhận phương án.
            // Yêu cầu đã kết thúc thì bỏ qua thao tác lặp thay vì báo lỗi chuyển trạng thái.
            if (in_array($lockedIssue->status, ['resolved', 'rejected'], true)) {
                return [$lockedIssue->fresh(['order', 'user']), false];
            }

            $allowedTransitions = [
                'open' => ['open', 'processing', 'rejected'],
                'processing' => ['processing', 'awaiting_confirmation', 'rejected'],
                'awaiting_confirmation' => ['awaiting_confirmation', 'processing'],
                'resolved' => ['resolved'],
                'rejected' => ['rejected'],
            ];

            if (! in_array($data['status'], $allowedTransitions[$lockedIssue->status] ?? [$lockedIssue->status], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Không thể chuyển lùi hoặc bỏ qua bước xử lý của yêu cầu.',
                ]);
            }

            if ($data['status'] === 'resolved' && $lockedIssue->status !== 'resolved') {
                throw ValidationException::withMessages([
                    'status' => 'Chỉ khách hàng mới có thể xác nhận và hoàn tất yêu cầu hỗ trợ.',
                ]);
            }

            if ($data['status'] === 'awaiting_confirmation' && empty($data['resolution_type'])) {
                throw ValidationException::withMessages([
                    'resolution_type' => 'Hãy chọn phương án hỗ trợ cho khách trước khi hoàn tất.',
                ]);
            }

            if ($data['status'] === 'awaiting_confirmation'
                && in_array($data['resolution_type'] ?? null, ['redelivery', 'other'], true)
                && blank(trim((string) ($data['resolution_value'] ?? '')))) {
                throw ValidationException::withMessages([
                    'resolution_value' => 'Hãy nhập nội dung phương án hỗ trợ cho khách.',
                ]);
            }

            if ($data['status'] === 'awaiting_confirmation'
                && ($data['resolution_type'] ?? null) === 'redelivery'
                && empty($data['estimated_at'])) {
                throw ValidationException::withMessages([
                    'estimated_at' => 'Hãy chọn thời gian dự kiến giao bù cho khách.',
                ]);
            }

            $selectedQuantities = collect($data['redelivery_items'] ?? [])
                ->map(fn ($quantity) => (int) $quantity)
                ->filter(fn (int $quantity) => $quantity > 0);

            if (($data['resolution_type'] ?? null) === 'redelivery' && ! $lockedIssue->redelivery_order_id) {
                $originalItems = $lockedIssue->order->orderItems->keyBy('id');
                foreach ($selectedQuantities as $itemId => $quantity) {
                    $originalItem = $originalItems->get((int) $itemId);
                    if (! $originalItem || $quantity > (int) $originalItem->quantity) {
                        throw ValidationException::withMessages([
                            'redelivery_items' => 'Số lượng giao bù không hợp lệ hoặc vượt quá số lượng của món trong đơn gốc.',
                        ]);
                    }
                }
                $data['redelivery_items'] = $selectedQuantities->all();
            } elseif (($data['resolution_type'] ?? null) !== 'redelivery') {
                $data['redelivery_items'] = null;
            }

            if ($data['status'] === 'awaiting_confirmation'
                && ($data['resolution_type'] ?? null) === 'redelivery'
                && ! $lockedIssue->redelivery_order_id) {
                $selectedItems = $lockedIssue->order->orderItems
                    ->filter(fn (OrderItem $item) => $selectedQuantities->has((string) $item->id) || $selectedQuantities->has($item->id));

                if ($selectedItems->isEmpty()) {
                    throw ValidationException::withMessages([
                        'redelivery_items' => 'Hãy chọn ít nhất một món cần giao bù.',
                    ]);
                }

                foreach ($selectedItems as $selectedItem) {
                    $quantity = (int) ($selectedQuantities->get((string) $selectedItem->id) ?? $selectedQuantities->get($selectedItem->id));
                    if ($quantity > (int) $selectedItem->quantity) {
                        throw ValidationException::withMessages([
                            'redelivery_items' => 'Số lượng giao bù không được vượt quá số lượng của món trong đơn gốc.',
                        ]);
                    }
                }

                $original = $lockedIssue->order;
                $redeliveryFulfillment = $original->fulfillment_type === 'pickup' ? 'pickup' : 'delivery';
                $redelivery = Order::create([
                    'order_code' => OrderCodeGenerator::generate((int) $original->branch_id, $redeliveryFulfillment),
                    'support_issue_id' => $lockedIssue->id,
                    'user_id' => $original->user_id,
                    'branch_id' => $original->branch_id,
                    'fulfillment_type' => $redeliveryFulfillment,
                    'delivery_type' => 'scheduled',
                    'scheduled_delivery_time' => $data['estimated_at'],
                    'scheduled_at' => $data['estimated_at'],
                    'shipping_address_text' => $original->shipping_address_text,
                    'shipping_latitude' => $original->shipping_latitude,
                    'shipping_longitude' => $original->shipping_longitude,
                    'contact_phone' => $original->contact_phone,
                    'subtotal' => 0,
                    'shipping_fee' => 0,
                    'discount' => 0,
                    'total' => 0,
                    'payment_method' => 'cod',
                    'payment_status' => 'paid',
                    'status' => OrderStatus::PENDING,
                    'note' => mb_substr('ĐƠN GIAO BÙ MIỄN PHÍ cho '.$original->displayCode().' — '.trim((string) $data['resolution_value']), 0, 500),
                    'status_changed_at' => now(),
                    'status_changed_by' => $request->user()->id,
                ]);

                foreach ($selectedItems as $selectedItem) {
                    $quantity = (int) ($selectedQuantities->get((string) $selectedItem->id) ?? $selectedQuantities->get($selectedItem->id));
                    $replacementItem = OrderItem::create([
                        'order_id' => $redelivery->id,
                        'product_id' => $selectedItem->product_id,
                        'product_size_id' => $selectedItem->product_size_id,
                        'ice_level' => $selectedItem->ice_level,
                        'sugar_level' => $selectedItem->sugar_level,
                        'quantity' => $quantity,
                        'unit_price' => 0,
                        'total_price' => 0,
                    ]);
                    foreach ($selectedItem->toppingLines as $toppingLine) {
                        OrderItemTopping::create([
                            'order_item_id' => $replacementItem->id,
                            'topping_id' => $toppingLine->topping_id,
                            'price' => 0,
                        ]);
                    }
                }

                $lockedIssue->redelivery_order_id = $redelivery->id;
            }

            if ($data['status'] === 'rejected') {
                if (mb_strlen(trim((string) ($data['admin_note'] ?? ''))) < 10) {
                    throw ValidationException::withMessages([
                        'admin_note' => 'Hãy nêu rõ lý do từ chối cho khách (ít nhất 10 ký tự).',
                    ]);
                }

                // Từ chối không phải là một phương án bồi hoàn.
                $data['resolution_type'] = null;
                $data['resolution_value'] = null;
            }

            if (($data['resolution_type'] ?? null) === 'voucher' && $data['status'] === 'awaiting_confirmation') {
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
                'awaiting_confirmation' => [
                    'approved_at' => $lockedIssue->approved_at ?? now(),
                    'remedy_started_at' => $lockedIssue->remedy_started_at ?? now(),
                ],
                'resolved' => ['resolved_at' => $lockedIssue->resolved_at ?? now()],
                'rejected' => ['rejected_at' => $lockedIssue->rejected_at ?? now()],
                default => [],
            };
            if (($data['resolution_type'] ?? null) !== 'redelivery') {
                $data['estimated_at'] = null;
            }
            $lockedIssue->fill([...$data, ...$timestamps, 'handled_by' => $request->user()->id]);
            $changed = $lockedIssue->isDirty();
            $lockedIssue->save();

            return [$lockedIssue->fresh(['order', 'user', 'redeliveryOrder']), $changed];
        });
        if ($changed) {
            \App\Support\ChatHelper::notifyOrderIssueStatus($issue, $request->user());
            $issue->user->notify(new OrderIssueReportStatusNotification($issue));
        }
        return $changed
            ? back()->with('success', 'Đã cập nhật yêu cầu hỗ trợ.')
            : back()->with('info', 'Yêu cầu đã được xử lý trước đó, hệ thống không thực hiện lại thao tác.');
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
