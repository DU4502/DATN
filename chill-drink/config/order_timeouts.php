<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tự động hủy đơn bị treo
    |--------------------------------------------------------------------------
    |
    | - pending_hours: khách đã đặt nhưng quán không xác nhận trong thời gian này.
    | - inactive_hours: sau khi đã vào luồng xử lý, nếu không đổi trạng thái liên tục
    |   trong thời gian này thì hệ thống tự hủy.
    |
    */
    'pending_hours' => (int) env('ORDER_PENDING_TIMEOUT_HOURS', 2),
    'inactive_hours' => (int) env('ORDER_INACTIVE_TIMEOUT_HOURS', 24),

    // Số đơn tối đa xử lý trong một lượt scheduler để tránh giữ request quá lâu.
    'batch_limit' => (int) env('ORDER_TIMEOUT_BATCH_LIMIT', 200),
];
