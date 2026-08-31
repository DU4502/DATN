<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderIssueReport;
use App\Models\User;
use App\Notifications\OrderIssueReportCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\OrderStatus;
use Throwable;

class OrderIssueReportController extends Controller
{
    public function create(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if (OrderStatus::normalize((string) $order->status) !== OrderStatus::COMPLETED) {
            return redirect()->route('orders.index')->with('error', 'Chỉ có thể báo vấn đề sau khi đơn hàng đã hoàn thành.');
        }

        $reports = $order->issueReports()->where('user_id', $request->user()->id)->latest()->get();
        if ($reports->isEmpty() && ! $order->canSubmitIssueReport()) {
            return redirect()->route('orders.index')->with('error', 'Yêu cầu hỗ trợ chỉ được gửi trong vòng 2 giờ kể từ khi bạn xác nhận đã nhận đơn.');
        }

        return view('profile.order-issue-report', [
            'order' => $order,
            'reports' => $reports,
        ]);
    }

    public function store(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if (OrderStatus::normalize((string) $order->status) !== OrderStatus::COMPLETED) {
            return redirect()->route('orders.index')->with('error', 'Chỉ có thể báo vấn đề sau khi đơn hàng đã hoàn thành.');
        }
        if (! $order->canSubmitIssueReport()) {
            return redirect()->route('orders.index')->with('error', 'Yêu cầu hỗ trợ chỉ được gửi trong vòng 2 giờ kể từ khi bạn xác nhận đã nhận đơn.');
        }
        if ($order->issueReports()->where('user_id', $request->user()->id)->whereNotIn('status', ['resolved', 'rejected'])->exists()) {
            return redirect()->route('orders.issues.create', $order)->with('error', 'Đơn hàng này đã có một yêu cầu hỗ trợ đang được xử lý.');
        }

        $data = $request->validate([
            'type' => ['required', 'in:missing_item,wrong_item,quality_issue,other'],
            'description' => ['required', 'string', 'min:10', 'max:1500'],
        ], [
            'type.required' => 'Vui lòng chọn loại vấn đề cần hỗ trợ.',
            'type.in' => 'Loại vấn đề đã chọn không hợp lệ.',
            'description.required' => 'Vui lòng nhập mô tả chi tiết.',
            'description.min' => 'Mô tả chi tiết phải có ít nhất 10 ký tự.',
            'description.max' => 'Mô tả chi tiết không được vượt quá 1.500 ký tự.',
        ]);

        // Chỉ kiểm tra các tệp thực sự đã được chọn, nên 2 trong 3 ô ảnh là hợp lệ.
        $evidenceUploads = collect($request->file('evidence', []))->filter()->values();
        Validator::make(['evidence' => $evidenceUploads->all()], [
            'evidence' => ['required', 'array', 'min:2', 'max:3'],
            'evidence.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ])->validate();

        $storedPaths = [];
        try {
            $report = DB::transaction(function () use ($order, $request, $data, $evidenceUploads, &$storedPaths): OrderIssueReport {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
                abort_unless($lockedOrder->user_id === $request->user()->id, 403);

                if (OrderStatus::normalize((string) $lockedOrder->status) !== OrderStatus::COMPLETED || ! $lockedOrder->canSubmitIssueReport()) {
                    throw ValidationException::withMessages([
                        'order' => 'Đơn hàng không còn đủ điều kiện gửi yêu cầu hỗ trợ.',
                    ]);
                }

                if ($lockedOrder->issueReports()->where('user_id', $request->user()->id)->whereNotIn('status', ['resolved', 'rejected'])->exists()) {
                    throw ValidationException::withMessages([
                        'order' => 'Đơn hàng này đã có một yêu cầu hỗ trợ đang được xử lý.',
                    ]);
                }

                $evidenceFiles = $evidenceUploads
                    ->map(function ($file) use (&$storedPaths): array {
                        $path = $file->store('order-issue-evidence');
                        $storedPaths[] = $path;

                        return [
                            'path' => $path,
                            'name' => $file->getClientOriginalName(),
                        ];
                    })
                    ->values()
                    ->all();

                return $lockedOrder->issueReports()->create([
                    'user_id' => $request->user()->id,
                    'type' => $data['type'],
                    'description' => trim($data['description']),
                    'evidence_path' => $evidenceFiles[0]['path'] ?? null,
                    'evidence_name' => $evidenceFiles[0]['name'] ?? null,
                    'evidence_files' => $evidenceFiles,
                    'received_at' => now(),
                ]);
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                Storage::delete($storedPath);
            }

            throw $exception;
        }

        $report->load('order');
        \App\Support\ChatHelper::notifyOrderIssue($report);
        $request->user()->notify(new OrderIssueReportCreatedNotification($report));
        User::query()->whereIn('role_id', [2, 3, 4])->get()
            ->filter(fn (User $staff) => $staff->isSuperAdmin() || (int) $staff->branch_id === (int) $order->branch_id)
            ->each(fn (User $staff) => $staff->notify(new OrderIssueReportCreatedNotification($report)));

        return redirect()->route('orders.issues.create', $order)->with('success', 'Đã gửi yêu cầu hỗ trợ. Bạn có thể theo dõi tiến trình xử lý ngay bên dưới.');
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $reports = $order->issueReports()
            ->where('user_id', $request->user()->id)
            ->with('redeliveryOrder')
            ->latest()
            ->get();

        return response()->json([
            'reports' => $reports->map(fn (OrderIssueReport $report) => [
                'id' => $report->id,
                'status' => $report->status,
                'admin_note' => $report->admin_note,
                'resolution_type' => $report->resolution_type,
                'resolution_value' => $report->resolution_value,
                'estimated_at' => $report->estimated_at?->format('d/m/Y H:i'),
                'approved_at' => $report->approved_at?->format('d/m/Y H:i'),
                'remedy_started_at' => ($report->remedy_started_at ?? $report->approved_at)?->format('d/m/Y H:i'),
                'customer_confirmed_at' => $report->customer_confirmed_at?->format('d/m/Y H:i'),
                'processing_at' => $report->processing_at?->format('d/m/Y H:i'),
                'resolved_at' => $report->resolved_at?->format('d/m/Y H:i'),
                'rejected_at' => $report->rejected_at?->format('d/m/Y H:i'),
                'redelivery_order_id' => $report->redelivery_order_id,
                'redelivery_order_code' => $report->redeliveryOrder?->displayCode(),
                'redelivery_order_status' => $report->redeliveryOrder?->status,
                'redelivery_order_status_label' => $report->redeliveryOrder ? OrderStatus::label((string) $report->redeliveryOrder->status) : null,
                'display_status_label' => $report->resolution_type === 'redelivery'
                    && $report->redeliveryOrder
                    && OrderStatus::normalize((string) $report->redeliveryOrder->status) !== OrderStatus::COMPLETED
                        ? 'Đang thực hiện giao bù'
                        : null,
            ]),
        ]);
    }

    public function confirmResolution(Request $request, Order $order, OrderIssueReport $issue): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id && $issue->order_id === $order->id && $issue->user_id === $request->user()->id, 403);

        $confirmed = DB::transaction(function () use ($request, $order, $issue): ?bool {
            $lockedIssue = OrderIssueReport::query()->lockForUpdate()->findOrFail($issue->id);
            abort_unless($order->user_id === $request->user()->id && $lockedIssue->order_id === $order->id && $lockedIssue->user_id === $request->user()->id, 403);

            if ($lockedIssue->status !== 'awaiting_confirmation' || blank($lockedIssue->resolution_type)) {
                return null;
            }

            if ($lockedIssue->resolution_type === 'redelivery') {
                $redeliveryOrder = $lockedIssue->redeliveryOrder()->lockForUpdate()->first();
                if (! $redeliveryOrder || OrderStatus::normalize((string) $redeliveryOrder->status) !== OrderStatus::COMPLETED) {
                    return null;
                }
            }

            if ($lockedIssue->customer_confirmed_at !== null) {
                return false;
            }

            $lockedIssue->update([
                'status' => 'resolved',
                'customer_confirmed_at' => now(),
                'resolved_at' => now(),
            ]);

            return true;
        });

        if ($confirmed === null) {
            return back()->with('error', 'Yêu cầu chưa có phương án hoàn tất để xác nhận.');
        }

        $issue->refresh();
        if ($confirmed) {
            User::query()->whereIn('role_id', [2, 3, 4])->get()
                ->filter(fn (User $staff) => $staff->isSuperAdmin() || (int) $staff->branch_id === (int) $order->branch_id)
                ->each(fn (User $staff) => $staff->notify(new \App\Notifications\OrderIssueReportStatusNotification($issue)));
        }

        return back()->with('success', 'Cảm ơn bạn đã xác nhận. Yêu cầu hỗ trợ đã hoàn tất.');
    }

}
