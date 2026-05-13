# ⚡ Quick Start Guide

## 🚀 Bắt Đầu Trong 5 Phút

### 1. Clone & Install
```bash
git clone <repository-url>
cd chill-drink
composer install
npm install
```

### 2. Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Database
Mở `.env` và sửa:
```env
DB_DATABASE=chill_drink
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Create Database
```sql
CREATE DATABASE chill_drink;
```

### 5. Migrate & Seed
```bash
php artisan migrate
php artisan db:seed
```

### 6. Run
```bash
# Terminal 1
npm run dev

# Terminal 2
php artisan serve
```

### 7. Login
- URL: http://localhost:8000
- Admin: admin@chilldrink.com / password

## ✅ Done!

Xem thêm chi tiết tại [SETUP.md](SETUP.md)

## 📝 Daily Workflow

```bash
# Trước khi làm việc
git pull origin main
git checkout -b feature/your-feature

# Sau khi làm xong
git add .
git commit -m "Add: your feature"
git push origin feature/your-feature
```

## 🆘 Lỗi Thường Gặp

**"Class not found"**
```bash
composer dump-autoload
```

**"No encryption key"**
```bash
php artisan key:generate
```

**"Access denied for user"**
- Kiểm tra lại DB_USERNAME và DB_PASSWORD trong .env

**"npm ERR!"**
```bash
rm -rf node_modules
npm install
```

## 📚 Docs

- [SETUP.md](SETUP.md) - Chi tiết setup
- [CONTRIBUTING.md](CONTRIBUTING.md) - Quy tắc code
- [TODO.md](TODO.md) - Task list
- [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) - Cấu trúc

## 💡 Tips

1. Luôn pull code mới trước khi làm việc
2. Tạo branch riêng cho mỗi feature
3. Commit thường xuyên
4. Test trước khi push
5. Hỏi khi gặp vấn đề

---

**Happy Coding! 🎉**
