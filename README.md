# 🥤 Chill Drink - Hệ Thống Bán Đồ Uống Online

## 📋 Giới Thiệu

**Chill Drink** là hệ thống bán đồ uống online được xây dựng bằng Laravel 11, phục vụ cho đồ án tốt nghiệp.

## 🛠️ Công Nghệ

- Laravel 11, PHP 8.2+, MySQL
- Blade Template, TailwindCSS
- Laravel Breeze Authentication
- MVC Architecture, RESTful API

## 🚀 Cài Đặt

```bash
# Clone & Install
composer install
npm install

# Setup Environment
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Run
npm run dev
php artisan serve
```

## 👤 Tài Khoản Admin

- Email: admin@chilldrink.com
- Password: password

## 📁 Cấu Trúc

```
app/Http/Controllers/
├── Admin/          # Admin controllers
└── Client/         # Client controllers

app/Models/         # Eloquent models
database/
├── migrations/     # Database schema
├── factories/      # Test data factories
└── seeders/        # Data seeders

resources/views/
├── admin/          # Admin views
├── client/         # Client views
└── layouts/        # Layout templates
```

## 🗄️ Database

- Users (role, phone, address, points)
- Categories (name, slug, status)
- Products (category_id, price, stock)
- Orders (user_id, total_price, status)
- Order_Items (order_id, product_id, quantity)
- Reviews (user_id, product_id, rating)
- Vouchers (code, discount_percent)

## 🌐 Routes

**Client:**
- / - Trang chủ
- /products - Danh sách sản phẩm
- /cart - Giỏ hàng
- /checkout - Thanh toán

**Admin (prefix: /admin):**
- /dashboard - Thống kê
- /products - Quản lý sản phẩm
- /categories - Quản lý danh mục
- /orders - Quản lý đơn hàng
- /users - Quản lý người dùng

## 👥 Git Workflow

```bash
# Tạo branch mới
git checkout -b feature/ten-feature

# Commit
git add .
git commit -m "Add: mô tả"

# Push
git push origin feature/ten-feature
```

**Branch naming:**
- feature/ - Tính năng mới
- bugfix/ - Sửa lỗi
- hotfix/ - Khẩn cấp

**Commit convention:**
- Add: Thêm mới
- Fix: Sửa lỗi
- Update: Cập nhật
- Remove: Xóa

## 🔧 Commands

```bash
php artisan migrate:fresh --seed  # Reset DB
php artisan cache:clear           # Clear cache
php artisan make:controller Name  # Tạo controller
php artisan make:model Name -m    # Tạo model + migration
```

## 📚 Docs

- [Laravel 11](https://laravel.com/docs/11.x)
- [TailwindCSS](https://tailwindcss.com)
- [Breeze](https://laravel.com/docs/11.x/starter-kits#breeze)
