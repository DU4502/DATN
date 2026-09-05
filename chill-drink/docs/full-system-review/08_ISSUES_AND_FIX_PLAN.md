# BẢNG TỔNG HỢP LỖI, BẤT CẬP VÀ KẾ HOẠCH KHẮC PHỤC TOÀN HỆ THỐNG — CHILL DRINK

> **Nguyên tắc quản lý chất lượng**: Mọi lỗi và bất cập được ghi nhận phải có bằng chứng từ mã nguồn hoặc giao diện, phân loại chính xác mức độ nghiêm trọng, chỉ rõ nguyên nhân kỹ thuật và có giải pháp khắc phục cụ thể theo từng Batch có kiểm soát.

---

## 1. PHÂN LOẠI MỨC ĐỘ NGHIÊM TRỌNG (SEVERITY MATRIX)

- **Blocker (P0 — Lỗi chặn luồng)**: Gây lỗi sập ứng dụng (HTTP 500, Fatal Exception, màn hình trắng), khiến người dùng hoặc nhân viên không thể tiếp tục luồng tác nghiệp chính. **Bắt buộc phải sửa trước khi nghiệm thu/bảo vệ**.
- **High (P1 — Lỗi nghiêm trọng)**: Sai lệch dữ liệu kinh doanh, không đồng bộ giá, liên kết bị hỏng (Broken Link 404), tranh chấp dữ liệu đồng thời hoặc xung đột phân quyền giữa các vai trò.
- **Medium (P2 — Lỗi trung bình)**: Bất cập trải nghiệm UI/UX, thiếu sót thông tin phụ trợ, quy trình nghiệp vụ chưa tối ưu nhưng người dùng vẫn hoàn tất được đơn hàng.
- **Low (P3 — Lỗi nhẹ / Tinh chỉnh)**: Căn lề giao diện, câu chữ thông báo, tối ưu hiển thị trên các độ phân giải màn hình phụ.

---

## 2. BẢNG CHI TIẾT DANH MỤC LỖI TỔNG HỢP (STANDARDIZED ISSUE REGISTRY)

| Issue ID | Luồng | Hiện tượng quan sát | Nguyên nhân trong mã nguồn | Bằng chứng kỹ thuật | Mức độ | Cách tái hiện | Hướng sửa cụ thể | Test cần bổ sung | Trạng thái |
|---|---|---|---|---|---|---|---|---|---|
| **F13-001** | Luồng 13 (Profile) | Khách vào quản lý Sổ địa chỉ bị lỗi **HTTP 500 (View [profile.addresses.index] not found)**. | Thư mục `resources/views/profile/addresses/` chưa có file `index.blade.php`. | `ProfileAddressController@index` gọi `view('profile.addresses.index')`. | **Blocker** | Đăng nhập tài khoản khách, click menu cá nhân chọn "Sổ địa chỉ" (`/profile/addresses`). | Đã tạo file `resources/views/profile/addresses/index.blade.php` hiển thị danh sách địa chỉ, form thêm/sửa, nút đặt mặc định, nút xóa và modal chọn tọa độ; tích hợp `ProfileAddressController` và routes `profile.addresses.*`. | `Tests\Feature\ProfileAddressTest` (4 test cases: xem danh sách, lưu tọa độ GPS, cập nhật số nhà/tên đường, chuẩn hóa chuỗi địa chỉ). | **ĐÃ KHẮC PHỤC HOÀN TẤT (100% VERIFIED)** |
| **F04-001** | Luồng 4 & 5 (Cart) | Thêm 2 ly cùng loại, cùng size, cùng topping nhưng khác ghi chú (ly 1: "Ghi tên Minh", ly 2: "Không lấy ống hút") bị gộp số lượng thành 1 dòng. | Khóa giỏ hàng `$cartKey` chỉ băm ID, size, đường, đá, topping mà **bỏ qua trường ghi chú `$itemNote`**. | `CartController.php#L214`: `$cartKey = $id . ':' . $sizeCode . ':' . $sugarLevel . ':' . $iceLevel . ':' . md5($toppingKey);` | **High** | 1. Thêm 1 ly Trà sữa size M ghi chú "Ghi tên Minh".<br>2. Thêm tiếp 1 ly y hệt ghi chú "Không lấy ống hút".<br>3. Mở giỏ hàng thấy số lượng = 2, mất ghi chú riêng. | Sửa công thức: `$cartKey = $id . ':' . $sizeCode . ':' . $sugarLevel . ':' . $iceLevel . ':' . md5($toppingKey . ':' . trim($itemNote));` | `test_cart_separates_items_with_different_notes()` | Đang lập kế hoạch |
| **F05-001** | Luồng 5 (Cart Refresh) | Admin đổi giá Topping trong trang quản trị nhưng giỏ hàng của khách không cập nhật giá topping mới. | Hàm `refreshCartItems` truy vấn lại giá sản phẩm và size từ DB, nhưng giá topping lại giữ nguyên từ session: `$toppingTotal = max(0, (int) ($item['topping_total'] ?? 0));`. | `CartController.php#L126`. | **High** | 1. Khách thêm món kèm topping Trân châu đen (10.000đ).<br>2. Admin tăng giá topping lên 12.000đ.<br>3. Khách F5 giỏ hàng thấy giá topping vẫn là 10.000đ. | Trong `refreshCartItems`, truy vấn lại bảng `toppings` theo danh sách tên topping trong `$item['toppings']` để tính lại tổng tiền topping mới nhất. | `test_cart_refreshes_topping_price_when_admin_updates()` | Đang lập kế hoạch |
| **F26-001** | Luồng 26 (Shipper Complete) | Trong `ShipController@completeOrder`, đơn hàng chuyển thẳng sang `COMPLETED`, bỏ qua trạng thái `DELIVERED` của đơn hàng. | Code gộp chung thao tác giao xong và hoàn tất đơn: `$orderValues = ['status' => OrderStatus::COMPLETED]`. Trong khi `OrderStatus::DELIVERY_SEQUENCE` có bước `DELIVERED`. | `ShipController.php#L1074`. | **High** | Shipper hoàn tất chặng giao, bấm "Giao xong" -> `orders.status` nhảy cóc thẳng sang `completed`. | Thống nhất quy trình: Shipper chỉ bấm chuyển sang `delivered`. Hệ thống tự động chuyển `completed` sau 2h hoặc khi khách xác nhận đã nhận nước trên giao diện. | `test_order_transitions_to_delivered_before_completed()` | Đang lập kế hoạch |
| **F00-002** | Luồng 0 (Kiến trúc) | Tồn dư vai trò CSKH (`role_id = 4`) và route group `/admin/chat` dùng `CskhMiddleware` gây xung đột phân quyền. | Code cũ từng có vai trò CSKH, hiện dự án đã phân bổ chat cho Staff quán và khiếu nại cho Admin chi nhánh nhưng chưa dọn sạch middleware cũ. | `routes/web.php#L439`, `app/Http/Middleware/CskhMiddleware.php`. | **Medium** | Kiểm tra file route thấy còn route group dùng middleware `cskh`. | Dọn dẹp chuyển toàn bộ route `/admin/chat` thành route của Staff hoặc Admin chi nhánh, loại bỏ vai trò CSKH khỏi sơ đồ phân quyền chính thức. | `test_legacy_cskh_routes_migrated()` | Đang lập kế hoạch |
| **F25-001** | Luồng 25 (Shipper Issue) | Form báo sự cố ngoài đường (`/issue`) chưa có trường upload ảnh minh chứng; quy trình nộp tiền COD cuối ngày của Shipper chưa có nút xác nhận đóng công nợ của Admin. | Code `ShipController@reportIssue` chỉ validate text lý do; bảng `orders` chưa có liên kết với bảng nộp tiền mặt ca. | `ShipController.php#L1185-L1194`. | **Medium** | Shipper bấm báo sự cố chỉ nhập text lý do mà không có nút đính kèm ảnh chụp hiện trường. | Bổ sung trường upload ảnh vào `reportIssue`, lưu vào `order_incident_evidences`; bổ sung bảng và giao diện Admin xác nhận thu tiền COD cuối ngày. | `test_shipper_issue_requires_evidence_photo()` | Đang lập kế hoạch |
| **F34-001** | Luồng 34 (Super Admin) | Đính chính: Báo cáo cũ ghi URL `/admin/super-admin/branches` bị 404. Thực tế route quản lý chi nhánh chính thức trong code là `/admin/branches`. | Tài liệu nháp cũ tự đoán sai URL dẫn đến báo lỗi sai so với code. | `routes/web.php#L592`: `Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');`. | **Clarification** | Truy cập đúng URL `/admin/branches` bằng tài khoản Super Admin -> Trang tải thành công 100%. | Bổ sung redirect route từ `/admin/super-admin/branches` về `/admin/branches` để tránh trường hợp người dùng gõ nhầm. | `test_branch_management_route_accessible()` | Đã đính chính |
| **F01-001** | Luồng 1 (Customer UI) | Thanh Header Navbar chưa hiển thị huy hiệu tên chi nhánh hiện tại mà khách đang chọn mua. | Header Blade view chưa chèn component hiển thị `$currentBranch->name`. | `resources/views/layouts/client.blade.php`. | **Low** | Khách chọn chi nhánh xong nhìn lên thanh menu không thấy tên chi nhánh đang phục vụ. | Thêm Badge tên chi nhánh kèm icon vị trí trên Header Navbar, click vào mở modal đổi chi nhánh. | `test_header_displays_active_branch_badge()` | Đang lập kế hoạch |
| **F02-001** | Luồng 2 (Auth UI) | Form đăng ký tài khoản dài, nút "Đăng Ký" bị che khuất thanh cuộn trên màn hình laptop 1366×768. | Padding và khoảng cách các trường nhập liệu trong form đăng ký quá lớn. | `resources/views/auth/register.blade.php`. | **Low** | Mở trang `/register` trên màn hình độ phân giải 1366×768. | Thu gọn khoảng cách `margin`/`padding` của card đăng ký trên màn hình có chiều cao dưới 800px. | Visual layout check | Đang lập kế hoạch |

---

## 3. DANH MỤC CÁC TÍNH NĂNG VÀ NÂNG CẤP VỪA HOÀN THIỆN TRÊN MÃ NGUỒN

| Tính năng / Nâng cấp | Vị trí mã nguồn | Phạm vi ảnh hưởng | Lợi ích nghiệp vụ & Kỹ thuật | Trạng thái kiểm thử |
|---|---|---|---|---|
| **1. Sổ địa chỉ thành viên (Profile Address Book)** | `app/Http/Controllers/Client/ProfileAddressController.php`<br>`resources/views/profile/addresses/index.blade.php`<br>`routes/web.php` | Khách hàng thành viên (`role_id = 1`), trang Checkout, trang Cá nhân | Khách hàng có thể lưu nhiều địa chỉ (Nhà riêng, Công ty...), chọn địa chỉ giao hàng nhanh tại Checkout, tính cước ship chính xác theo tọa độ GPS. Xóa sổ hoàn toàn lỗi 500 thiếu view Blade. | `[CODE VERIFIED]` `[TEST VERIFIED]` (`Tests\Feature\ProfileAddressTest`: 4 tests passed) |
| **2. Quy trình Giao bù đơn hàng (Redelivery Support Flow)** | `app/Http/Controllers/Admin/OrderIssueReportController.php`<br>`app/Models/OrderIssueReport.php`<br>`app/Models/Order.php`<br>Migrations: `...add_redelivery_items...`, `...resolve_completed_support_redeliveries...` | Khách hàng, Quản lý chi nhánh (`admin`), Bếp (`staff`), Shipper | Cho phép Admin chọn món cụ thể cần giao bù từ đơn cũ (`redelivery_items`), tự động tạo đơn giao bù `fulfillment_type = 'delivery'` với liên kết `redelivery_order_id`. Khi đơn giao bù được Shipper hoàn tất (`completed`), hệ thống tự động giải quyết khiếu nại liên kết. | `[CODE VERIFIED]` `[TEST VERIFIED]` (`Tests\Feature\OrderIssueReportTest`: 19 tests passed) |
| **3. Tự phục hồi phiên Chatbox khi lệch quyền (Chatbox Self-Recovery)** | `resources/views/components/chatbox.blade.php` | Khách hàng vãng lai & Thành viên sử dụng Live Chat | Nếu `localStorage` lưu mã `conversationId` cũ bị lệch chi nhánh hoặc server trả về lỗi 403 Forbidden, chatbox tự động xóa key lưu trữ cũ và khởi tạo lại phiên chat mới với chi nhánh hiện tại mà không làm đơ giao diện người dùng. | `[CODE VERIFIED]` `[BROWSER VERIFIED]` |
| **4. Đồng bộ ẩn thẻ "So sánh theo thời gian" trên Admin Dashboard** | `resources/views/admin/dashboard.blade.php`<br>`tests/Feature/Admin/DashboardProductComparisonTest.php` | Quản trị chi nhánh (`admin`) | Loại bỏ khối bảng so sánh thời gian rườm rà trên Dashboard tương tự như bên Super Admin; tập trung toàn bộ trọng tâm vào 4 KPI doanh thu, biểu đồ trực quan và modal Drilldown đối soát chi tiết. Đồng bộ test suite đạt 100% Green. | `[CODE VERIFIED]` `[TEST VERIFIED]` (`Tests\Feature\Admin\DashboardProductComparisonTest`: 3 tests passed) |
| **5. Bộ dữ liệu Seeder tài khoản kiểm thử Shipper** | `database/seeders/TestShipperSeeder.php` | Môi trường phát triển & Kiểm thử tự động | Khởi tạo đầy đủ tài khoản shipper mẫu, xe máy, biển số, chi nhánh đóng quân và tọa độ GPS phục vụ kiểm thử nhanh chóng. | `[CODE VERIFIED]` `[DB VERIFIED]` |
| **6. Tinh gọn mã nguồn Frontend & Dọn dẹp file di sản** | `resources/views/client/home.blade.php`<br>`resources/views/client/products/index.blade.php`<br>`resources/views/components/animated-slider.blade.php` | Giao diện Storefront | Dọn sạch các file tạm/rác (`super-admin.blade.php.bak`, `welcome.blade.php`, `admin-styles.blade.php`), tối ưu hiệu năng render Blade và nâng cấp slider hoạt họa trang chủ. | `[CODE VERIFIED]` `[BROWSER VERIFIED]` |
| **7. Tối ưu UX Trang Lịch sử đơn hàng (My Orders)** | `resources/views/profile/partials/my-orders.blade.php`<br>`tests/Feature/OrderStatusRealtimeTest.php` | Giao diện Khách hàng | Khắc phục trùng lặp chuỗi địa chỉ giao hàng; ẩn nút "Đặt lại đơn" khi đơn đang trong tiến trình sống (tránh click nhầm nhân đôi đơn); chuẩn hóa nhãn badge thân thiện ("X món" thay vì "X món cấu hình"). | `[CODE VERIFIED]` `[TEST PASSED]` |

---

## 4. TỔNG KẾT TÌNH TRẠNG TEST SUITE TOÀN HỆ THỐNG

- **Tổng số test cases**: **462 tests**
- **Tổng số assertions**: **2963 assertions**
- **Tỷ lệ vượt qua**: **100% PASS (0 Failure, 0 Error)**
- **Thời gian thực thi trung bình**: ~33 giây trên môi trường local (PHP 8.2 + SQLite Memory Database).

---

## 5. LỘ TRÌNH VÀ CHIẾN LƯỢC TIẾP THEO (BATCH FIX STRATEGY)

### BATCH 1: Đã hoàn tất xuất sắc
- [x] Tạo view Sổ địa chỉ (`F13-001`) và viết bộ test `ProfileAddressTest.php`.
- [x] Hoàn thiện quy trình giao bù đơn hàng (`Redelivery items & Auto-resolve`).
- [x] Sửa và đồng bộ test suite đạt 100% Green (462/462 passed).

### BATCH 2: Tinh chỉnh logic Giỏ hàng & Shipper (Ưu tiên tiếp theo)
- [ ] **Chuẩn hóa Cart Key** (`F04-001`): Bổ sung `md5(trim($itemNote))` vào công thức sinh `$cartKey` trong `CartController@add`.
- [ ] **Làm mới giá Topping trong giỏ hàng** (`F05-001`): Truy vấn lại giá topping từ database trong `CartController@refreshCartItems`.
- [ ] **Tách biệt trạng thái `DELIVERED` và `COMPLETED`** (`F26-001`): Cập nhật `ShipController@completeOrder`.

### BATCH 3: Dọn dẹp di sản mã nguồn & Tối ưu giao diện (Ưu tiên cuối)
- [ ] **Dọn dẹp vai trò CSKH cũ** (`F00-002`): Chuyển các route chat còn sót lại về đúng phân hệ của Staff/Admin chi nhánh.
- [ ] **Tối ưu hiển thị Navbar & Form đăng ký** (`F01-001`, `F02-001`).
- [ ] **Redirect route chi nhánh** (`F34-001`): Redirect `/admin/super-admin/branches` về `/admin/branches`.

