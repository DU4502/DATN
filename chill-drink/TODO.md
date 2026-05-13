# 📋 TODO List - Chill Drink Project

## ✅ Đã Hoàn Thành (Base Project)

- [x] Setup Laravel 11 project
- [x] Cài đặt Laravel Breeze authentication
- [x] Tạo database migrations
- [x] Tạo Models với relationships
- [x] Tạo Factories và Seeders
- [x] Tạo Admin Middleware
- [x] Tạo Controllers (Admin & Client)
- [x] Setup Routes
- [x] Tạo Blade layouts
- [x] Tạo views cơ bản
- [x] Setup TailwindCSS
- [x] Tạo documentation

## 🚀 Cần Làm Tiếp (Theo Thứ Tự Ưu Tiên)

### Phase 1: Core Features (Sprint 1 - 2 tuần)

#### Admin Panel
- [ ] **Product Management**
  - [ ] Trang danh sách sản phẩm (index)
  - [ ] Form thêm sản phẩm (create)
  - [ ] Form sửa sản phẩm (edit)
  - [ ] Xóa sản phẩm (delete)
  - [ ] Upload hình ảnh sản phẩm
  - [ ] Validation form

- [ ] **Category Management**
  - [ ] Trang danh sách danh mục
  - [ ] CRUD operations
  - [ ] Validation

- [ ] **Order Management**
  - [ ] Danh sách đơn hàng
  - [ ] Chi tiết đơn hàng
  - [ ] Cập nhật trạng thái đơn hàng
  - [ ] In hóa đơn

- [ ] **User Management**
  - [ ] Danh sách người dùng
  - [ ] Xem chi tiết user
  - [ ] Khóa/mở khóa tài khoản

#### Client Features
- [ ] **Product Pages**
  - [ ] Hoàn thiện trang danh sách sản phẩm
  - [ ] Hoàn thiện trang chi tiết sản phẩm
  - [ ] Pagination
  - [ ] Sort (giá, tên, mới nhất)
  - [ ] Filter nâng cao

- [ ] **Cart System**
  - [ ] Hoàn thiện giỏ hàng
  - [ ] Cập nhật số lượng
  - [ ] Xóa sản phẩm
  - [ ] Tính tổng tiền

- [ ] **Checkout Process**
  - [ ] Form thông tin giao hàng
  - [ ] Chọn phương thức thanh toán
  - [ ] Xác nhận đơn hàng
  - [ ] Email thông báo

### Phase 2: Advanced Features (Sprint 2 - 2 tuần)

- [ ] **User Profile**
  - [ ] Xem/sửa thông tin cá nhân
  - [ ] Đổi mật khẩu
  - [ ] Lịch sử đơn hàng
  - [ ] Điểm tích lũy

- [ ] **Review System**
  - [ ] Đánh giá sản phẩm
  - [ ] Hiển thị reviews
  - [ ] Rating trung bình
  - [ ] Chỉ cho phép review sau khi mua

- [ ] **Voucher System**
  - [ ] Admin: CRUD vouchers
  - [ ] Client: Áp dụng voucher
  - [ ] Kiểm tra voucher hợp lệ
  - [ ] Tính giảm giá

- [ ] **Search & Filter**
  - [ ] Tìm kiếm nâng cao
  - [ ] Filter theo nhiều tiêu chí
  - [ ] Autocomplete search
  - [ ] Search history

### Phase 3: Enhancement (Sprint 3 - 1 tuần)

- [ ] **UI/UX Improvements**
  - [ ] Loading states
  - [ ] Error handling
  - [ ] Toast notifications
  - [ ] Skeleton loaders
  - [ ] Animations

- [ ] **Performance**
  - [ ] Image optimization
  - [ ] Lazy loading
  - [ ] Caching
  - [ ] Query optimization

- [ ] **Dashboard Analytics**
  - [ ] Biểu đồ doanh thu
  - [ ] Thống kê sản phẩm bán chạy
  - [ ] Thống kê theo thời gian
  - [ ] Export reports

### Phase 4: Optional Features (Nếu Còn Thời Gian)

- [ ] **Payment Integration**
  - [ ] VNPay integration
  - [ ] MoMo integration
  - [ ] ZaloPay integration

- [ ] **Notification System**
  - [ ] Email notifications
  - [ ] SMS notifications
  - [ ] Push notifications

- [ ] **Social Features**
  - [ ] Share sản phẩm
  - [ ] Wishlist
  - [ ] Recently viewed

- [ ] **Admin Reports**
  - [ ] Báo cáo doanh thu
  - [ ] Báo cáo tồn kho
  - [ ] Báo cáo khách hàng

## 🎯 Phân Công Công Việc (Gợi Ý)

### Member 1: Admin Product & Category
- Product Management (CRUD)
- Category Management (CRUD)
- Image upload
- Validation

### Member 2: Admin Order & User
- Order Management
- User Management
- Order status workflow
- Reports

### Member 3: Client Product Pages
- Product listing
- Product detail
- Search & Filter
- Pagination

### Member 4: Cart & Checkout
- Shopping cart
- Checkout process
- Order creation
- Email notifications

### Member 5: User Profile & Reviews
- User profile
- Review system
- Rating system
- Order history

### Member 6: UI/UX & Testing
- UI improvements
- Responsive design
- Testing
- Bug fixes

## 📊 Sprint Planning

### Sprint 1 (Tuần 1-2)
**Mục tiêu**: Hoàn thành core features
- Admin CRUD operations
- Client product pages
- Cart system
- Basic checkout

### Sprint 2 (Tuần 3-4)
**Mục tiêu**: Advanced features
- User profile
- Review system
- Voucher system
- Search improvements

### Sprint 3 (Tuần 5)
**Mục tiêu**: Polish & Testing
- UI/UX improvements
- Performance optimization
- Bug fixes
- Testing

### Sprint 4 (Tuần 6)
**Mục tiêu**: Final touches
- Documentation
- Deployment preparation
- Presentation slides
- Demo video

## 🐛 Known Issues

- [ ] Cần thêm validation cho tất cả forms
- [ ] Cần optimize queries (N+1 problem)
- [ ] Cần thêm error handling
- [ ] Cần improve mobile responsive

## 📝 Notes

### Conventions
- Luôn tạo branch mới cho mỗi feature
- Commit thường xuyên với message rõ ràng
- Test kỹ trước khi tạo PR
- Review code của nhau

### Resources
- [Laravel Docs](https://laravel.com/docs/11.x)
- [TailwindCSS Docs](https://tailwindcss.com)
- [Blade Components](https://laravel.com/docs/11.x/blade)

### Meetings
- **Daily Standup**: 9:00 AM (15 phút)
- **Sprint Planning**: Thứ 2 đầu sprint
- **Sprint Review**: Thứ 6 cuối sprint
- **Retrospective**: Sau mỗi sprint

---

**Last Updated**: 2026-05-13
**Next Review**: Sprint Planning Meeting
