# 📖 Hướng Dẫn Setup Project Chill Drink

## 🎯 Mục Đích

Tài liệu này hướng dẫn chi tiết cách setup project cho các thành viên trong team.

## ✅ Yêu Cầu Trước Khi Bắt Đầu

### 1. Cài Đặt Phần Mềm

- **XAMPP** hoặc **Laragon** (đã bao gồm PHP, MySQL, Apache)
- **Composer** - [Download tại đây](https://getcomposer.org/download/)
- **Node.js & NPM** - [Download tại đây](https://nodejs.org/)
- **Git** - [Download tại đây](https://git-scm.com/)
- **VS Code** (khuyến nghị) - [Download tại đây](https://code.visualstudio.com/)

### 2. Kiểm Tra Phiên Bản

Mở terminal/cmd và chạy:

```bash
php -v        # Phải >= 8.2
composer -V   # Kiểm tra Composer
node -v       # Kiểm tra Node.js
npm -v        # Kiểm tra NPM
git --version # Kiểm tra Git
```

## 🚀 Các Bước Setup

### Bước 1: Clone Project

```bash
# Clone từ GitHub
git clone <repository-url>

# Di chuyển vào thư mục project
cd chill-drink
```

### Bước 2: Cài Đặt Dependencies

```bash
# Cài đặt PHP dependencies
composer install

# Cài đặt Node dependencies
npm install
```

**Lưu ý:** Quá trình này có thể mất 5-10 phút tùy vào tốc độ mạng.

### Bước 3: Cấu Hình Environment

```bash
# Copy file .env.example thành .env
cp .env.example .env

# Hoặc trên Windows
copy .env.example .env

# Generate application key
php artisan key:generate
```

### Bước 4: Tạo Database

1. Mở **phpMyAdmin** (http://localhost/phpmyadmin)
2. Tạo database mới tên: `chill_drink`
3. Hoặc dùng MySQL command:

```sql
CREATE DATABASE chill_drink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Bước 5: Cấu Hình Database

Mở file `.env` và cập nhật:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chill_drink
DB_USERNAME=root
DB_PASSWORD=          # Để trống nếu không có password
```

### Bước 6: Chạy Migration & Seeder

```bash
# Tạo các bảng trong database
php artisan migrate

# Thêm dữ liệu mẫu
php artisan db:seed
```

**Kết quả:** Database sẽ có:
- 1 tài khoản Admin
- 10 tài khoản User
- 6 Categories
- 30 Products

### Bước 7: Build Frontend Assets

```bash
# Development mode (tự động compile khi có thay đổi)
npm run dev

# Hoặc build một lần
npm run build
```

### Bước 8: Chạy Server

Mở terminal mới và chạy:

```bash
php artisan serve
```

Truy cập: **http://localhost:8000**

## 🔑 Tài Khoản Test

### Admin
- **URL**: http://localhost:8000/admin/dashboard
- **Email**: admin@chilldrink.com
- **Password**: password

### User
- Các user được tạo tự động với email: user1@example.com, user2@example.com, ...
- **Password**: password

## 🛠️ Troubleshooting

### Lỗi: "Class not found"

```bash
composer dump-autoload
```

### Lỗi: "No application encryption key"

```bash
php artisan key:generate
```

### Lỗi: "SQLSTATE[HY000] [1045] Access denied"

- Kiểm tra lại thông tin database trong file `.env`
- Đảm bảo MySQL đang chạy

### Lỗi: "npm ERR!"

```bash
# Xóa node_modules và cài lại
rm -rf node_modules
npm install

# Hoặc trên Windows
rmdir /s node_modules
npm install
```

### Lỗi: "Storage not writable"

```bash
# Linux/Mac
chmod -R 775 storage bootstrap/cache

# Windows: Chuột phải folder -> Properties -> Security -> Edit -> Full Control
```

## 📝 Git Workflow Cho Team

### 1. Lần Đầu Setup

```bash
# Clone project
git clone <repository-url>
cd chill-drink

# Tạo branch riêng cho mình
git checkout -b feature/ten-cua-ban
```

### 2. Làm Việc Hàng Ngày

```bash
# Trước khi bắt đầu làm việc, pull code mới nhất
git checkout main
git pull origin main

# Chuyển về branch của mình
git checkout feature/ten-cua-ban

# Merge code mới từ main
git merge main

# Làm việc và commit
git add .
git commit -m "Add: mô tả công việc"

# Push lên GitHub
git push origin feature/ten-cua-ban
```

### 3. Tạo Pull Request

1. Vào GitHub repository
2. Click "Pull requests" -> "New pull request"
3. Chọn branch của bạn
4. Điền mô tả và tạo PR
5. Đợi review từ team lead

## 📋 Checklist Sau Khi Setup

- [ ] Project chạy được tại http://localhost:8000
- [ ] Đăng nhập admin thành công
- [ ] Xem được danh sách sản phẩm
- [ ] Thêm sản phẩm vào giỏ hàng
- [ ] Database có đầy đủ dữ liệu mẫu
- [ ] Git đã được cấu hình đúng

## 💡 Tips

1. **Luôn pull code mới trước khi làm việc**
2. **Commit thường xuyên với message rõ ràng**
3. **Không commit file .env**
4. **Test kỹ trước khi tạo Pull Request**
5. **Hỏi team lead nếu gặp vấn đề**

## 📞 Liên Hệ

Nếu gặp vấn đề, liên hệ:
- Team Lead: [Tên và contact]
- Group Chat: [Link group]

---

**Chúc các bạn làm việc hiệu quả! 🚀**
