# Chill Drink

Hệ thống bán đồ uống đa chi nhánh xây dựng trên Laravel 11, hỗ trợ khách hàng, quản trị, nhân viên cửa hàng, CSKH và giao hàng.

## Công nghệ

- PHP 8.2+, Laravel 11, MySQL
- Blade, Alpine.js, Vue 3, Tailwind CSS, Vite
- Laravel Breeze, Socialite
- Laravel Reverb, Echo và queue cho cập nhật realtime

## Cài đặt

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Cấu hình database, mail, broadcast/Reverb và các dịch vụ ngoài trong `.env`. Trước khi chạy migration trên database đã có dữ liệu, cần backup và xem trước bằng:

```bash
php artisan migrate:status
```

Chỉ chạy các migration đã được kiểm tra phù hợp với schema của môi trường. Không dùng `migrate:fresh` trên database có dữ liệu cần bảo toàn.

## Chạy dự án

Lệnh sau khởi động web server, queue worker, Reverb, log viewer và Vite:

```bash
composer dev
```

Lệnh `composer dev` phù hợp với Linux/macOS và giữ nguyên workflow hiện tại của dự án.

Trên Windows, dùng workflow không phụ thuộc `pcntl`/Laravel Pail:

```bash
composer dev:windows
```

Lệnh này chạy Laravel server, Reverb và Vite. Log Laravel có thể xem trực tiếp tại `storage/logs/laravel.log`.

Nếu cần chạy thủ công trên Windows, mở ba terminal riêng:

```bash
php artisan serve
php artisan reverb:start
npm run dev
```

Có thể chạy riêng từng tiến trình khi cần:

```bash
php artisan serve
php artisan queue:listen --tries=1
php artisan reverb:start
npm run dev
```

## Piper Navigation TTS

Giọng dẫn đường của Shipper chạy bằng Piper local trên server. Trên Windows, cài runtime và model tiếng Việt bằng script có sẵn:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/install_piper_tts.ps1
```

Sau khi cài, có thể kiểm tra độc lập bằng:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/test_piper_tts.ps1
```

Các đường dẫn binary, model, config và cache lấy từ nhóm biến `NAV_TTS_*`/`PIPER_*` trong `.env.example`; không hardcode đường dẫn máy cá nhân. Nếu Piper chưa sẵn sàng, endpoint giọng nói trả `503` có kiểm soát và bản đồ vẫn tiếp tục hoạt động bằng hướng dẫn chữ.

## Vai trò

| ID | Vai trò |
|---:|---|
| 1 | Khách hàng |
| 2 | Admin chi nhánh |
| 3 | Super Admin |
| 4 | CSKH |
| 5 | Staff |
| 6 | Shipper |

Staff và Shipper là hai vai trò riêng biệt. Các tài khoản vận hành phải được gán đúng `role_id` và chi nhánh theo luồng quản trị hiện tại.

## Tài khoản mẫu

Seeder `Database\Seeders\AuthAccountSeeder` tạo các tài khoản phát triển, gồm:

- `user@chilldrink.com` / `12345678`
- `admin@chilldrink.com` / `12345678`
- `superadmin@chilldrink.com` / `SuperAdmin@2026`
- `cskh@chilldrink.com` / `Cskh@123`

Chỉ dùng các thông tin này trong môi trường phát triển và thay đổi thông tin đăng nhập ở môi trường triển khai.

## Kiểm tra

```bash
php artisan test
npm run build
composer validate
php artisan route:list
```

## Cấu trúc chính

- `app/Http/Controllers`: controller theo khu vực Admin, Client, Staff, Shipper và CSKH
- `app/Models`: Eloquent models và relations
- `app/Services`: nghiệp vụ analytics, checkout, giao hàng và các dịch vụ dùng chung
- `database/migrations`: lịch sử schema; cần kiểm tra trạng thái trước khi chạy
- `database/seeders`: dữ liệu nền và tài khoản phát triển
- `resources/views`: giao diện Blade
- `resources/js`: frontend, Echo/Reverb và Vite entrypoints
- `routes`: web, API, console và private broadcast channels

## Realtime

Realtime sử dụng Laravel Reverb/Echo. Đảm bảo các biến `BROADCAST_CONNECTION`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT` và các biến `VITE_REVERB_*` khớp nhau. Queue worker phải chạy để xử lý các broadcast event dạng queued.
