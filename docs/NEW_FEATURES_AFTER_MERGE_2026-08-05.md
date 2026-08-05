# Các nội dung mới sau khi merge

Ngày cập nhật: 05/08/2026

## Chức năng mới

- Bổ sung dashboard và dịch vụ phân tích cho Super Admin.
- Thêm thống kê KPI, doanh thu, đơn hàng và xếp hạng theo chi nhánh.
- Thêm bộ lọc analytics theo ngày, tuần, tháng, năm, khoảng thời gian và nhiều chi nhánh.
- Thêm so sánh kỳ hiện tại với kỳ trước, cùng kỳ năm trước hoặc không so sánh.
- Thêm xem chi tiết sản phẩm theo từng chi nhánh.
- Thêm bảng thời gian hoạt động và xuất dữ liệu analytics.
- Thêm bộ chọn liên kết Google Maps khi quản lý chi nhánh.
- Thêm chức năng Super Admin xem trước workspace của Admin theo chi nhánh.
- Thêm quản lý Staff: tạo, cập nhật chi nhánh, khóa/mở khóa và xóa nhân viên.
- Thêm xác thực email bằng mã xác nhận.
- Thêm đăng nhập Facebook OAuth.
- Thêm đăng ký và đăng nhập bằng mã OTP điện thoại.
- Thêm học địa chỉ giao hàng và lưu tọa độ địa chỉ đã xác thực.
- Thêm hỗ trợ đơn nhóm và chat nhóm giữa các thành viên.

## File và thành phần mới

- `app/Services/SuperAdminAnalyticsService.php`
- `app/Services/SuperAdminAnalyticsPeriodResolver.php`
- `app/Services/AnalyticsPeriodContext.php`
- `app/Services/EmailVerificationCodeService.php`
- `app/Support/AddressLearning.php`
- `app/Support/OrderDistancePolicy.php`
- `app/Support/SimpleXlsxWriter.php`
- `app/Models/AddressObservation.php`
- `app/Models/Landmark.php`
- `app/Models/VerifiedAddressPoint.php`
- `app/Notifications/EmailVerificationCodeNotification.php`
- `app/Events/GroupOrderGroupMessageSent.php`
- `app/Http/Controllers/Api/AddressLookupController.php`
- `app/Http/Controllers/Auth/EmailVerificationCodeController.php`
- `app/Console/Commands/ImportLandmarkCsv.php`
- `app/Console/Commands/SuperAdminAnalyticsBenchmark.php`
- Các partial Super Admin trong `resources/views/admin/super-admin/partials/`
- Các migration tạo bảng jobs, landmarks, address learning và index analytics.
- Các test cho analytics, group order, nearest branch và tiện ích xuất XLSX.

## Route mới

- Route quản lý và preview workspace Admin theo chi nhánh.
- Route quản lý Staff của Super Admin.
- Route analytics và export dữ liệu analytics.
- Route xác nhận email bằng mã.
- Route Facebook OAuth.
- Route xác thực OTP điện thoại.
- Route tra cứu địa chỉ và chi nhánh gần nhất.

## Ghi chú cấu hình

Facebook OAuth, Google Maps, SMS OTP và các dịch vụ bên ngoài cần được cấu hình thêm trong file `.env` trước khi sử dụng trên môi trường thật.
