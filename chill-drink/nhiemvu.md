Bạn có thể thay toàn bộ nội dung `nhiemvu.md` bằng bản chi tiết dưới đây. Mình đã bổ sung rõ phần **Admin cũng phải so sánh theo ngày, tuần, tháng, năm, khoảng tùy chọn và phân tích sản phẩm**.

````md
# NHIỆM VỤ HOÀN THIỆN DASHBOARD ADMIN & SUPER ADMIN

Ngày cập nhật: 2026-08-03

## 0. Mục tiêu chung

Hoàn thiện cả Frontend và Backend cho hai trang:

- Dashboard `ADMIN`
- Dashboard `SUPER ADMIN`

Hai trang phải:

- Gọn, dễ đọc, không nhồi quá nhiều chữ nhỏ.
- Không lặp thông tin giữa badge, label và control.
- Bộ lọc thời gian rõ ràng.
- Có kỳ hiện tại và kỳ đối chiếu.
- Số liệu FE và BE đồng nhất.
- Không sai phạm vi quyền.
- Không lỗi khi dữ liệu rỗng.
- Không chậm bất thường khi dữ liệu lớn.
- Không commit khi vẫn còn mục bắt buộc chưa hoàn thành.

---

# I. QUY TẮC LÀM VIỆC

## 1. Quy tắc chung

- [ ] Không commit khi còn mục bắt buộc chưa hoàn thành.
- [ ] Mỗi lần chỉ xử lý một phần rõ ràng.
- [ ] Làm xong phần nào phải cập nhật phần đó vào `dalamduoc.md`.
- [ ] Không sửa lan sang module không liên quan.
- [ ] Không xóa chức năng cũ nếu chưa xác nhận chức năng thay thế đầy đủ.
- [ ] Không thay đổi nghiệp vụ chỉ để giao diện đẹp hơn.
- [ ] Không thay đổi UI chỉ để test pass.
- [ ] Không sửa test để che lỗi nghiệp vụ.
- [ ] Không query dữ liệu trực tiếp trong Blade.
- [ ] Không để JavaScript tự tính lại số liệu kinh doanh.
- [ ] Không dùng `zoom`, `transform: scale()` để sửa tỷ lệ giao diện.
- [ ] Không dùng dữ liệu mock trên dashboard thật.
- [ ] Không tạo thêm request khi dữ liệu đã có sẵn trong payload hiện tại.

## 2. Quy tắc thứ tự thực hiện

Thứ tự bắt buộc:

1. Rà hiện trạng.
2. Chốt nghiệp vụ.
3. Chuẩn hóa request/query params.
4. Hoàn thiện Backend.
5. Viết và chạy test Backend.
6. Hoàn thiện Frontend.
7. Smoke test bằng browser.
8. Kiểm tra đồng bộ FE/BE.
9. Cập nhật `dalamduoc.md`.
10. Chỉ commit khi toàn bộ mục bắt buộc đã xong.

---

# II. QUY ƯỚC NGHIỆP VỤ DÙNG CHUNG

## 1. Đơn hàng hợp lệ

- [ ] Xác nhận toàn bộ dashboard dùng cùng một điều kiện đơn hợp lệ.
- [ ] Không tự viết lại điều kiện đơn hợp lệ ở nhiều controller/service.

Điều kiện hiện tại:

```php
status != 'cancelled'
AND
(
    payment_status = 'paid'
    OR status = 'completed'
)
````

## 2. Định nghĩa số liệu

* [ ] Doanh thu đơn hàng dùng `SUM(orders.total)`.
* [ ] Doanh thu sản phẩm dùng `SUM(order_items.total_price)`.
* [ ] Sản phẩm bán ra dùng `SUM(order_items.quantity)`.
* [ ] Đơn hàng dùng `COUNT DISTINCT orders.id`.
* [ ] Khách hàng thành viên dùng `COUNT DISTINCT orders.user_id`.
* [ ] Không tính `user_id = null` thành một khách hàng.
* [ ] Trung bình mỗi đơn dùng:

```text
Doanh thu / số đơn hợp lệ
```

* [ ] Nếu không có đơn, trung bình mỗi đơn bằng `0`.
* [ ] Không trả `NaN`, `Infinity` hoặc lỗi chia cho 0.

## 3. Cột thời gian

* [ ] Dashboard hiện dùng `orders.created_at`.
* [ ] Không tự đổi sang `paid_at`, `completed_at` hoặc thời gian khác.
* [ ] Nếu sau này đổi cột thời gian phải làm thành nhiệm vụ riêng.

---

# III. BỘ LỌC THỜI GIAN DÙNG CHUNG

Cả `ADMIN` và `SUPER ADMIN` đều phải hỗ trợ:

* [ ] Hôm nay.
* [ ] Một ngày cụ thể.
* [ ] Một tuần cụ thể.
* [ ] Một tháng cụ thể.
* [ ] Một năm cụ thể.
* [ ] Khoảng ngày tùy chọn.
* [ ] Tất cả thời gian nếu nghiệp vụ cần.
* [ ] Không so sánh.
* [ ] So với kỳ liền trước.
* [ ] So với cùng kỳ năm trước.
* [ ] So với khoảng tùy chọn.

## 1. Quy tắc kỳ hiện tại

### Ngày

* [ ] Bắt đầu từ `00:00:00`.
* [ ] Kết thúc tại `23:59:59`.
* [ ] Nếu là hôm nay thì không lấy thời gian tương lai.

### Tuần

* [ ] Chốt tuần bắt đầu từ thứ Hai.
* [ ] Kết thúc Chủ nhật.
* [ ] Nếu là tuần hiện tại thì kết thúc tại thời điểm hiện tại.

### Tháng

* [ ] Từ ngày đầu tháng.
* [ ] Đến ngày cuối tháng.
* [ ] Nếu là tháng hiện tại thì kết thúc tại thời điểm hiện tại.

### Năm

* [ ] Từ ngày đầu năm.
* [ ] Đến ngày cuối năm.
* [ ] Nếu là năm hiện tại thì kết thúc tại thời điểm hiện tại.

### Khoảng tùy chọn

* [ ] Bao gồm toàn bộ ngày bắt đầu.
* [ ] Bao gồm toàn bộ ngày kết thúc.
* [ ] Không cho `start_date > end_date`.
* [ ] Không cho kỳ nằm hoàn toàn trong tương lai.

## 2. Quy tắc kỳ đối chiếu

* [ ] Kỳ liền trước có cùng độ dài với kỳ hiện tại.
* [ ] Kỳ đang chạy dở phải so với kỳ trước có cùng độ dài thực tế.
* [ ] Ngày hiện tại so với ngày trước.
* [ ] Tháng hiện tại đang chạy dở không được so với toàn bộ tháng trước.
* [ ] Cùng kỳ năm trước phải giữ cùng khoảng thời lượng.
* [ ] Custom comparison phải validate rõ.
* [ ] Không âm thầm so hai khoảng có độ dài khác nhau.

## 3. Quy tắc phần trăm thay đổi

* [ ] `current = 0`, `compare = 0` → `unchanged`.
* [ ] `current > 0`, `compare = 0` → `new_activity`.
* [ ] Không comparison → `unavailable`.
* [ ] Không trả `Infinity`.
* [ ] Phần trăm làm tròn thống nhất.

---

# IV. DASHBOARD ADMIN — PHẠM VI MỘT CHI NHÁNH

Dashboard `ADMIN` chỉ được xem dữ liệu của chi nhánh được phân quyền.

## 1. Phạm vi quyền

* [ ] Admin chỉ xem được chi nhánh của mình.
* [ ] Admin không được truyền `branch_id` khác để xem dữ liệu chi nhánh khác.
* [ ] Backend phải tự áp branch scope từ tài khoản đăng nhập.
* [ ] Không tin `branch_id` gửi từ Frontend.
* [ ] CSKH không được truy cập dashboard Admin nếu không có quyền.
* [ ] Super Admin có quyền riêng, không dùng nhầm quyền Admin chi nhánh.

---

# V. FRONTEND DASHBOARD ADMIN

## 1. Header Admin

* [ ] Tiêu đề rõ đây là dashboard chi nhánh.
* [ ] Hiển thị tên chi nhánh đang quản lý.
* [ ] Nút hành động nằm cùng một hàng khi đủ chỗ.
* [ ] Không có chữ giải thích kỹ thuật.
* [ ] Không lặp tên chi nhánh nhiều lần trong cùng một vùng.

## 2. Bộ lọc Admin

Admin phải có:

* [ ] Ngày.
* [ ] Tuần.
* [ ] Tháng.
* [ ] Năm.
* [ ] Khoảng tùy chọn.
* [ ] Kỳ đối chiếu.
* [ ] Nút Xóa lọc.
* [ ] Nút Áp dụng.

Yêu cầu UI:

* [ ] Control cùng chiều cao.
* [ ] Control cùng một hàng khi đủ chỗ.
* [ ] Không để nút trên nút dưới nếu vẫn còn chiều ngang.
* [ ] Không có nhiều badge lặp lại giá trị trong input.
* [ ] Không hiển thị ghi chú nhỏ không cần thiết.
* [ ] Label rõ và ngắn.
* [ ] Reload vẫn giữ trạng thái filter.
* [ ] Back/forward đồng bộ URL và UI.

## 3. KPI Admin

Admin cần tối thiểu:

* [ ] Tổng doanh thu.
* [ ] Đơn hàng.
* [ ] Khách hàng thành viên.
* [ ] Sản phẩm bán ra.
* [ ] Trung bình mỗi đơn.

Mỗi KPI chỉ giữ:

* [ ] Icon.
* [ ] Tên chỉ số.
* [ ] Giá trị.
* [ ] Một dòng so sánh.

Không được:

* [ ] Lặp “Không đối chiếu” hai lần.
* [ ] Hiển thị ghi chú kỹ thuật trong card.
* [ ] Dùng nhiều dòng chữ nhỏ không cần thiết.

## 4. Phân tích sản phẩm trên Admin

Admin phải phân tích được sản phẩm trong chính chi nhánh của mình.

### 4.1. Top sản phẩm trong kỳ

* [ ] Top sản phẩm theo số lượng bán.
* [ ] Top sản phẩm theo doanh thu.
* [ ] Cho chọn cách sắp xếp:

  * số lượng;
  * doanh thu.
* [ ] Dùng đúng kỳ đang chọn.
* [ ] Dùng đúng kỳ đối chiếu.
* [ ] Không tính đơn hủy.
* [ ] Không dùng giá sản phẩm hiện tại để tính lịch sử.

Mỗi sản phẩm hiển thị tối thiểu:

* [ ] Tên sản phẩm.
* [ ] Ảnh sản phẩm nếu có.
* [ ] Số lượng bán.
* [ ] Doanh thu sản phẩm.
* [ ] Tỷ trọng số lượng.
* [ ] Tỷ trọng doanh thu.
* [ ] Thay đổi so với kỳ đối chiếu.

### 4.2. So sánh sản phẩm theo ngày/tháng/năm

Admin phải có thể:

* [ ] Chọn ngày cụ thể và xem sản phẩm bán chạy trong ngày.
* [ ] Chọn tuần cụ thể.
* [ ] Chọn tháng cụ thể.
* [ ] Chọn năm cụ thể.
* [ ] Chọn khoảng ngày tùy chọn.
* [ ] So với kỳ liền trước.
* [ ] So với cùng kỳ năm trước.
* [ ] So với khoảng tùy chọn.

Ví dụ bắt buộc test:

* [ ] Ngày 26/05/2026 so với 25/05/2026.
* [ ] Tháng 05/2026 so với tháng 04/2026.
* [ ] Tháng 05/2026 so với tháng 05/2025.
* [ ] Khoảng 10/05–20/05 so với 11 ngày liền trước.

### 4.3. Một sản phẩm cụ thể

Admin phải có thể chọn một sản phẩm và xem:

* [ ] Tổng số lượng bán trong kỳ.
* [ ] Tổng doanh thu sản phẩm trong kỳ.
* [ ] Số đơn có sản phẩm.
* [ ] Tỷ trọng trong tổng sản phẩm của chi nhánh.
* [ ] Tăng/giảm so với kỳ đối chiếu.
* [ ] Các size/biến thể nếu dữ liệu hiện có hỗ trợ.
* [ ] Sản phẩm soft-deleted vẫn xem được báo cáo lịch sử.
* [ ] Sản phẩm mất record có fallback an toàn.

### 4.4. Danh mục sản phẩm

* [ ] Có thể lọc theo danh mục nếu dữ liệu hiện có hỗ trợ.
* [ ] Không để danh mục lọc Frontend nhưng Backend không áp dụng.
* [ ] Không cho Admin xem sản phẩm ngoài phạm vi được giao nếu nghiệp vụ có giới hạn.

## 5. Biểu đồ Admin

* [ ] Không dùng quá nhiều biểu đồ.
* [ ] Không dùng line chart nếu thông tin có thể đọc tốt hơn bằng cột hoặc bảng.
* [ ] Biểu đồ phải chạy theo filter hiện tại.
* [ ] Không có biểu đồ dùng kỳ khác với KPI.
* [ ] Dữ liệu 0 phải có empty state.
* [ ] Không để card biểu đồ quá cao khi ít dữ liệu.

## 6. Responsive Admin

Kiểm tra tối thiểu:

* [ ] 1920 × 1080.
* [ ] 1440 × 900.
* [ ] 1366 × 768.
* [ ] Mobile 390 × 844.

Yêu cầu:

* [ ] Không tràn ngang toàn body.
* [ ] Filter xuống hàng có chủ ý.
* [ ] KPI không bị bó chữ.
* [ ] Bảng scroll trong container.
* [ ] Modal không vượt viewport.
* [ ] Nút đủ lớn để bấm.

---

# VI. BACKEND DASHBOARD ADMIN

## 1. Service dùng chung

* [ ] Rà xem Admin đang query trực tiếp trong controller hay chưa.
* [ ] Tách analytics service nếu logic đang bị lặp.
* [ ] Tái sử dụng valid-sales rule chung.
* [ ] Tái sử dụng period resolver chung nếu phù hợp.
* [ ] Không copy công thức comparison ở nhiều nơi.

## 2. Summary Admin

Backend phải trả:

```text
summary:
- revenue
- valid_order_count
- unique_customer_count
- items_sold
- average_order_value
```

Mỗi metric có:

```text
- current_value
- compare_value
- percentage_change
- change_state
- comparison_label
```

## 3. Product analytics Admin

Backend phải có dữ liệu cho:

* [ ] Top sản phẩm theo quantity.
* [ ] Top sản phẩm theo revenue.
* [ ] Product summary.
* [ ] Product comparison.
* [ ] Category filter nếu FE có.
* [ ] Product selector có search.
* [ ] Pagination nếu danh sách dài.
* [ ] Soft-delete fallback.
* [ ] Không N+1 Product/OrderItem.

## 4. Query Admin

* [ ] Tất cả query phải áp branch scope từ user đăng nhập.
* [ ] Áp đúng current date range.
* [ ] Áp đúng comparison date range.
* [ ] Không query một lần cho từng sản phẩm.
* [ ] Không lazy load trong Blade.
* [ ] Không nhân đôi doanh thu khi join order_items.
* [ ] Không nhân đôi số đơn khi group product.
* [ ] Không load toàn bộ sản phẩm rồi sort bằng PHP nếu DB làm được.

---

# VII. TEST DASHBOARD ADMIN

## 1. Test quyền

* [ ] Admin đúng chi nhánh truy cập được.
* [ ] Admin không xem được chi nhánh khác.
* [ ] Super Admin không bị chặn sai.
* [ ] CSKH bị chặn nếu không có quyền.
* [ ] Guest bị redirect login.

## 2. Test thời gian

* [ ] Exact day.
* [ ] Exact week.
* [ ] Exact month.
* [ ] Exact year.
* [ ] Custom range.
* [ ] Previous period.
* [ ] Previous year.
* [ ] Custom comparison.
* [ ] Chặn ngày tương lai.
* [ ] Chặn start > end.

## 3. Test KPI

* [ ] Revenue đúng.
* [ ] Order count đúng.
* [ ] Customer distinct đúng.
* [ ] Item quantity đúng.
* [ ] AOV đúng.
* [ ] Cancelled order bị loại.
* [ ] Completed unpaid được tính theo valid-sales hiện tại.
* [ ] Paid processing được tính.

## 4. Test sản phẩm Admin

* [ ] Top product theo quantity đúng.
* [ ] Top product theo revenue đúng.
* [ ] Tie-break ổn định.
* [ ] Product revenue dùng order_items.total_price.
* [ ] Không dùng products.price hiện tại.
* [ ] Product soft-delete vẫn hiện.
* [ ] Product missing fallback.
* [ ] Comparison sản phẩm đúng.
* [ ] Category filter đúng.
* [ ] Product search đúng.
* [ ] Pagination đúng.
* [ ] Không N+1.
* [ ] Query count không tăng theo số sản phẩm.

## 5. Feature test Admin

* [ ] Trang render.
* [ ] Bộ lọc render.
* [ ] 5 KPI render.
* [ ] Top sản phẩm render.
* [ ] Product comparison render.
* [ ] Empty state render.
* [ ] Filter giữ query params.
* [ ] Pagination giữ filter.
* [ ] Modal vẫn hoạt động.
* [ ] Không lỗi 500.

---

# VIII. FRONTEND DASHBOARD SUPER ADMIN

## 1. Header và filter

* [ ] Header gọn.
* [ ] Nút cùng nhóm nằm cùng hàng khi đủ chỗ.
* [ ] Bộ lọc không lặp thông tin.
* [ ] Giữ period, comparison và multi-branch.
* [ ] Xóa ghi chú kỹ thuật.
* [ ] Chỉ giữ thông tin người dùng cần thao tác.

## 2. KPI Super Admin

* [ ] Tổng doanh thu.
* [ ] Đơn hàng.
* [ ] Khách hàng.
* [ ] Sản phẩm bán ra.
* [ ] Trung bình mỗi đơn.
* [ ] Không lặp comparison.
* [ ] Multi-branch áp dụng đồng bộ.

## 3. Tổng quan nhanh

* [ ] Top chi nhánh.
* [ ] Bán chạy toàn hệ thống.
* [ ] Không query thêm dữ liệu đã có.
* [ ] Không hiển thị câu kỹ thuật kiểu “không query thêm”.
* [ ] Nút và toggle cùng hàng khi đủ chỗ.

## 4. Phân tích kinh doanh

Giữ đủ ba tab:

* [ ] So sánh chi nhánh.
* [ ] Bán chạy theo chi nhánh.
* [ ] Một món bán tốt ở đâu.

Yêu cầu:

* [ ] Tab không tạo request mới khi chỉ đổi tab.
* [ ] Hash hoạt động.
* [ ] Back/forward hoạt động.
* [ ] Partial fetch cập nhật đúng vùng.
* [ ] Không mất multi-branch scope.
* [ ] Không reset filter ngoài ý muốn.

## 5. Bảng chi nhánh

Bảng phải vừa:

* [ ] Xếp hạng chi nhánh.
* [ ] Quản lý chi nhánh.

Giữ:

* [ ] Hạng.
* [ ] Chi nhánh.
* [ ] Doanh thu.
* [ ] Đơn hàng.
* [ ] Trung bình/đơn.
* [ ] Sản phẩm bán ra.
* [ ] Tăng trưởng.
* [ ] Món bán chạy nhất.
* [ ] Tỷ lệ hủy.
* [ ] Trạng thái.
* [ ] Quản trị viên.
* [ ] Nhân viên.
* [ ] Đơn hoàn thành.
* [ ] Đơn hủy.
* [ ] Thao tác.

## 6. Multi-branch

* [ ] Chọn một chi nhánh.
* [ ] Chọn nhiều chi nhánh.
* [ ] Chọn tất cả.
* [ ] Bỏ chọn tất cả.
* [ ] Search.
* [ ] Empty state.
* [ ] Chip giới hạn số lượng.
* [ ] URL dùng repeated params.
* [ ] Reload giữ lựa chọn.
* [ ] Back/forward giữ lựa chọn.
* [ ] Reset sạch hidden input cũ.

## 7. Mật độ thông tin

* [ ] Xóa chữ mô tả kỹ thuật.
* [ ] Xóa trạng thái lặp.
* [ ] Chuyển giải thích ít dùng vào tooltip.
* [ ] Giữ thông tin chính ngoài.
* [ ] Không để mỗi card có quá nhiều dòng chữ mờ.
* [ ] Control và nút cùng hàng khi đủ chỗ.
* [ ] Không để nút trên nút dưới ngoài ý muốn.

---

# IX. BACKEND DASHBOARD SUPER ADMIN

## 1. Kiểm tra lại các module

* [ ] `businessSummary()`.
* [ ] `topProducts()`.
* [ ] `branchComparison()`.
* [ ] `branchProductDetail()`.
* [ ] `productBranchPerformance()`.

## 2. branchComparison

* [ ] Pagination đúng.
* [ ] Total đúng.
* [ ] Last page đúng.
* [ ] Search đúng.
* [ ] Sort đúng.
* [ ] Performance filter đúng.
* [ ] Multi-branch đúng.
* [ ] Branch không đơn vẫn xuất hiện.
* [ ] Không N+1.
* [ ] Không nhân đôi totals.
* [ ] Không trả sai rank sau pagination.

## 3. Performance

* [ ] Route không tăng query count sau sửa FE.
* [ ] Kiểm tra lại với dữ liệu lớn.
* [ ] Exact day.
* [ ] Exact month.
* [ ] Previous period.
* [ ] 1 branch.
* [ ] 3 branches.
* [ ] 20 branches.
* [ ] Không để benchmark data trong DB sau khi đo.
* [ ] Không thêm index mới nếu chưa có bằng chứng.

---

# X. ĐỒNG BỘ FRONTEND VÀ BACKEND

## 1. Query params

Đối chiếu toàn bộ query params giữa:

* Form Blade.
* JavaScript.
* FormRequest.
* Period Resolver.
* Controller.
* Service.
* Pagination link.
* Partial fetch.
* Browser history.

Kiểm tra:

* [ ] Period type.
* [ ] Date.
* [ ] Week.
* [ ] Month.
* [ ] Year.
* [ ] Start date.
* [ ] End date.
* [ ] Compare type.
* [ ] Compare period.
* [ ] Branch ID.
* [ ] Multi-branch IDs.
* [ ] Product ID.
* [ ] Category ID.
* [ ] Sort.
* [ ] Direction.
* [ ] Search.
* [ ] Page.

## 2. Default value

* [ ] FE và BE cùng default period.
* [ ] FE và BE cùng default comparison.
* [ ] FE và BE cùng default sort.
* [ ] FE và BE cùng default pagination.
* [ ] Empty branch array được hiểu đúng.
* [ ] Empty product filter được hiểu đúng.
* [ ] Reset không làm giá trị legacy quay lại.

## 3. Field và payload

* [ ] Tên field Blade khớp request.
* [ ] Tên key payload khớp Blade.
* [ ] Không dùng key cũ đã bỏ.
* [ ] Không để Blade truy cập field có thể thiếu mà không fallback.
* [ ] Product/Branch missing metadata có fallback.

---

# XI. RESPONSIVE VÀ BROWSER SMOKE TEST

## 1. Admin

* [ ] 1920 × 1080.
* [ ] 1440 × 900.
* [ ] 1366 × 768.
* [ ] 390 × 844.

## 2. Super Admin

* [ ] 1920 × 1080.
* [ ] 1440 × 900.
* [ ] 1366 × 768.
* [ ] 390 × 844.

## 3. Kiểm tra chung

* [ ] Chrome zoom 100%.
* [ ] Không body horizontal overflow.
* [ ] Dropdown không bị cắt.
* [ ] Modal không bị cắt.
* [ ] Table scroll trong container.
* [ ] Tab hoạt động.
* [ ] Hash hoạt động.
* [ ] Back/forward hoạt động.
* [ ] Reload giữ filter.
* [ ] Không console error.
* [ ] Không request trùng.
* [ ] Không full reload khi partial fetch đủ dùng.

---

# XII. TEST BẮT BUỘC TRƯỚC KHI ĐÓNG VIỆC

## 1. Test Admin

* [ ] Admin dashboard feature test.
* [ ] Admin analytics service test.
* [ ] Admin period/filter test.
* [ ] Admin authorization test.
* [ ] Admin product analytics test.
* [ ] Admin pagination test.

## 2. Test Super Admin

* [ ] SuperAdminDashboardTest.
* [ ] SuperAdminAnalyticsPeriodTest.
* [ ] SuperAdminAnalyticsServiceTest.
* [ ] Multi-branch tests.
* [ ] Branch comparison pagination tests.
* [ ] Branch detail tests.
* [ ] Product branch performance tests.
* [ ] Authorization tests.

## 3. Kiểm tra kỹ thuật

* [ ] `php artisan view:clear`.
* [ ] `php artisan view:cache`.
* [ ] `php -l` các file PHP sửa.
* [ ] `git diff --check`.
* [ ] Không còn file quan trọng ở trạng thái `??`.
* [ ] Không còn benchmark data.
* [ ] Không còn bug 500.
* [ ] Không còn bug nghiệp vụ đã biết.

---

# XIII. CẬP NHẬT `dalamduoc.md`

Sau mỗi phần hoàn thành phải ghi:

```md
## Tên phần đã làm

Ngày:
Phạm vi:

### Đã làm
- ...

### File tạo
- ...

### File sửa
- ...

### Nghiệp vụ đã chốt
- ...

### Test đã chạy
- ...

### Kết quả
- ...

### Browser smoke test
- ...

### Phần chưa làm
- ...

### Lưu ý
- ...
```

Không được chỉ ghi “đã xong” mà không có:

* file;
* test;
* kết quả;
* phần còn lại.

---

# XIV. ĐIỀU KIỆN ĐƯỢC COMMIT

Chỉ được commit khi:

* [ ] Tất cả mục bắt buộc đã tick.
* [ ] Admin dashboard hoạt động.
* [ ] Super Admin dashboard hoạt động.
* [ ] Admin có filter ngày/tuần/tháng/năm/range.
* [ ] Admin có so sánh kỳ.
* [ ] Admin có phân tích sản phẩm.
* [ ] Super Admin có multi-branch.
* [ ] branchComparison pagination đúng.
* [ ] Phân quyền Admin/Super Admin/CSKH đúng.
* [ ] Tất cả test liên quan pass.
* [ ] Không còn lỗi nghiệp vụ đã biết.
* [ ] Không còn lỗi 500.
* [ ] Không còn horizontal overflow nghiêm trọng.
* [ ] Không còn query N+1 đã biết.
* [ ] `dalamduoc.md` đã cập nhật đầy đủ.
* [ ] Không còn file bắt buộc ở trạng thái untracked.
* [ ] `git diff --check` sạch hoặc chỉ còn warning cũ không liên quan.

---

# XV. THỨ TỰ THỰC HIỆN ĐỀ XUẤT

## Giai đoạn 1 — Rà hiện trạng Admin

* [ ] Đọc route/controller/view/service Admin.
* [ ] Lập danh sách số liệu hiện có.
* [ ] Lập danh sách filter hiện có.
* [ ] Xác định dữ liệu sản phẩm đang thiếu.
* [ ] Ghi kết quả vào `dalamduoc.md`.

## Giai đoạn 2 — Backend Admin

* [ ] Period context.
* [ ] Comparison.
* [ ] KPI.
* [ ] Top products.
* [ ] Product detail.
* [ ] Category filter.
* [ ] Authorization.
* [ ] Test.

## Giai đoạn 3 — Frontend Admin

* [ ] Header.
* [ ] Filter.
* [ ] KPI.
* [ ] Product analytics.
* [ ] Table/chart.
* [ ] Responsive.
* [ ] Browser smoke.

## Giai đoạn 4 — Rà lại Super Admin

* [ ] Dọn từng layout.
* [ ] Xóa microcopy thừa.
* [ ] Căn nút/control cùng hàng.
* [ ] Kiểm tra tab.
* [ ] Kiểm tra branch ranking.
* [ ] Kiểm tra multi-branch.
* [ ] Kiểm tra responsive.

## Giai đoạn 5 — Đồng bộ và regression

* [ ] Đối chiếu FE/BE.
* [ ] Chạy toàn bộ test.
* [ ] Chạy smoke test.
* [ ] Kiểm tra performance.
* [ ] Cập nhật log cuối.
* [ ] Kiểm tra điều kiện commit.

```

Điểm bổ sung quan trọng nhất trong bản này là:

> **Dashboard Admin không chỉ xem tổng số liệu của chi nhánh, mà phải chọn được ngày/tuần/tháng/năm/khoảng tùy chọn, có kỳ đối chiếu và phân tích sản phẩm theo đúng kỳ đó.**

Nên làm phần `ADMIN` theo thứ tự: **Backend thời gian → KPI → sản phẩm → test → Frontend**, rồi mới quay lại dọn từng layout của `SUPER ADMIN`.
```
