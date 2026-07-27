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
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Support\OrderStatus;

class OrderIssueReportController extends Controller
{
    public function create(Request $request, Order $order): View|RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        if (OrderStatus::normalize((string) $order->status) !== OrderStatus::COMPLETED) {
            return redirect()->route('orders.index')->with('error', 'Chỉ có thể báo vấn đề sau khi đơn hàng đã hoàn thành.');
        }

        $reports = $order->issueReports()->where('user_id', $request->user()->id)->latest()->get();

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
        if ($order->issueReports()->where('user_id', $request->user()->id)->whereNotIn('status', ['resolved', 'rejected'])->exists()) {
            return redirect()->route('orders.issues.create', $order)->with('error', 'Đơn hàng này đã có một yêu cầu hỗ trợ đang được xử lý.');
        }

        $data = $request->validate([
            'type' => ['required', 'in:missing_item,wrong_item,quality_issue,refund_request,other'],
            'description' => ['required', 'string', 'min:10', 'max:1500'],
            'evidence' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $report = OrderIssueReport::create([
            'order_id' => $order->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'description' => trim($data['description']),
            'evidence_path' => $request->file('evidence')?->store('order-issue-evidence'),
            'evidence_name' => $request->file('evidence')?->getClientOriginalName(),
            'received_at' => now(),
        ]);

        $report->load('order');
        \App\Support\ChatHelper::notifyOrderIssue($report);
        $request->user()->notify(new OrderIssueReportCreatedNotification($report));
        User::query()->whereIn('role_id', [2, 3, 4])->get()
            ->each(fn (User $staff) => $staff->notify(new OrderIssueReportCreatedNotification($report)));

        return redirect()->route('orders.issues.create', $order)->with('success', 'Đã gửi yêu cầu hỗ trợ. Bạn có thể theo dõi tiến trình xử lý ngay bên dưới.');
    }

    public function status(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        $reports = $order->issueReports()
            ->where('user_id', $request->user()->id)
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
            ]),
        ]);
    }

    public function confirmResolution(Request $request, Order $order, OrderIssueReport $issue): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()->id && $issue->order_id === $order->id && $issue->user_id === $request->user()->id, 403);

        if ($issue->status !== 'awaiting_customer') {
            return back()->with('error', 'Yêu cầu này chưa ở bước chờ bạn xác nhận.');
        }

        $issue->update([
            'status' => 'resolved',
            'customer_confirmed_at' => now(),
            'resolved_at' => now(),
        ]);
        User::query()->whereIn('role_id', [2, 3, 4])->get()
            ->filter(fn (User $staff) => $staff->isSuperAdmin() || (int) $staff->branch_id === (int) $order->branch_id)
            ->each(fn (User $staff) => $staff->notify(new \App\Notifications\OrderIssueReportStatusNotification($issue)));

        return back()->with('success', 'Cảm ơn bạn đã xác nhận. Yêu cầu hỗ trợ đã hoàn tất.');
    }
}
