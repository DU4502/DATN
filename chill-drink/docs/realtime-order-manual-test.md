# Kiểm tra realtime đơn hàng hai chiều

## Chuẩn bị

- Chạy web app, Reverb và Vite; cấu hình `BROADCAST_CONNECTION=reverb` cùng bộ `REVERB_*`/`VITE_REVERB_*` khớp nhau.
- Tạo hai khách hàng, hai đơn thuộc cùng hoặc khác chi nhánh, một Nhân viên đúng chi nhánh và một Admin chi nhánh.
- Mở DevTools/Network và xác nhận kết nối WebSocket hoạt động. Không bật auto-refresh của trình duyệt.

## CASE A–D — Chuyển modal sang Nhân viên

1. Admin chi nhánh 4 mở Dashboard, sau đó khách tạo đơn chi nhánh 4: Admin không hiện modal; Dashboard/danh sách và các event trạng thái khác vẫn hoạt động.
2. Nhân viên chi nhánh 4 đang mở bất kỳ màn hình Staff nào, khách tạo đơn chi nhánh 4: modal đầy đủ phải tự hiện qua `private-staff-orders.4`, không reload.
3. Nhân viên chi nhánh 3 đang online khi có đơn chi nhánh 4: request authorize `private-staff-orders.4` phải bị từ chối và không có modal.
4. Khi Admin và Nhân viên cùng online: chỉ Nhân viên hiện modal; Admin vẫn nghe `admin-notifications.4` cho `order.created` (dữ liệu danh sách/dashboard, không mở modal) và `order.status.updated`.

## CASE E — Nhân viên nhận đơn

1. Trình duyệt A đăng nhập Khách A, tạo đơn A và giữ nguyên `/checkout/success/{order}` hoặc trang tracking.
2. Trình duyệt B đăng nhập Nhân viên thuộc đúng chi nhánh của đơn A.
3. Xác nhận B hiện modal **Cảnh báo đơn mới**.
4. Tại B bấm **Nhận / Xác nhận đơn**.
5. Không reload A. Xác nhận trạng thái/stage tự đổi sang nội dung quán đang pha chế, chuẩn bị giao theo workflow hiện tại.

## CASE F — Đi qua toàn bộ workflow

1. Tại B lần lượt chuyển các trạng thái hợp lệ: `confirmed` → `preparing` → `ready_for_delivery`.
2. Tiếp tục thao tác bằng tài khoản Shipper: nhận nhiệm vụ, lấy hàng, bắt đầu giao và hoàn thành.
3. Sau từng thao tác, xác nhận A đổi badge, stage, message, timeline và thông tin tài xế ngay mà không F5/reload.
4. Trong giai đoạn trước khi Shipper chạy GPS, Network không được có request lặp theo chu kỳ để dò trạng thái; snapshot chỉ tải lần đầu và khi event WebSocket tới.

## CASE G — Cô lập từng đơn

1. Giữ trang tracking đơn A ở trình duyệt A và đơn B ở trình duyệt C.
2. Nhân viên đổi trạng thái đơn A.
3. Xác nhận A nhận `.order.status.updated` với `order_id` của A và cập nhật giao diện.
4. Xác nhận C không cập nhật và không nhận event trên channel của A.
5. Lặp lại với đơn guest; kiểm tra channel có dạng `guest-order.<sha256>` và không chứa id/token gốc.

## CASE H — Ba vai trò cùng mở

1. Mở Dashboard Admin chi nhánh, màn hình Nhân viên và tracking Khách cùng lúc.
2. Tạo đơn mới: Nhân viên phải nhận modal; Admin không được nhận modal.
3. Nhân viên xác nhận/chuyển trạng thái: Khách cập nhật tức thời.
4. Xác nhận Admin vẫn nhận dữ liệu `order.status.updated` trên channel chi nhánh và các bảng/dashboard đang có tiếp tục đồng bộ, nhưng không hiện cảnh báo đơn mới.
