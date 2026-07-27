<?php
namespace App\Notifications;
use App\Models\OrderIssueReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
class OrderIssueReportStatusNotification extends Notification
{
    use Queueable;
    public function __construct(private readonly OrderIssueReport $report) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        $labels = ['open' => 'Đang chờ xử lý', 'processing' => 'Đang kiểm tra', 'approved' => 'Đã duyệt hỗ trợ', 'remedy_in_progress' => 'Đang khắc phục', 'awaiting_customer' => 'Chờ bạn xác nhận', 'resolved' => 'Hoàn tất', 'rejected' => 'Không được chấp nhận'];
        $order = $this->report->order;
        return ['type' => 'order_issue_status', 'title' => 'Cập nhật yêu cầu hỗ trợ', 'message' => 'Yêu cầu cho đơn '.($order->order_code ?? '#'.$order->id).' – '.($labels[$this->report->status] ?? $this->report->status), 'order_id' => $order->id, 'status' => $this->report->status, 'status_label' => $labels[$this->report->status] ?? $this->report->status];
    }
}
