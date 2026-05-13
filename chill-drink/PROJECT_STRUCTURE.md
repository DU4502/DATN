# 📂 Cấu Trúc Project Chill Drink

## 🌳 Tổng Quan

```
chill-drink/
├── app/                        # Application logic
├── bootstrap/                  # Framework bootstrap
├── config/                     # Configuration files
├── database/                   # Database files
├── public/                     # Public assets
├── resources/                  # Views, CSS, JS
├── routes/                     # Route definitions
├── storage/                    # Storage files
├── tests/                      # Test files
└── vendor/                     # Composer dependencies
```

## 📁 Chi Tiết Cấu Trúc

### `/app` - Application Core

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                      # Admin controllers
│   │   │   ├── DashboardController.php # Admin dashboard
│   │   │   ├── ProductController.php   # Quản lý sản phẩm
│   │   │   ├── CategoryController.php  # Quản lý danh mục
│   │   │   ├── OrderController.php     # Quản lý đơn hàng
│   │   │   └── UserController.php      # Quản lý người dùng
│   │   │
│   │   ├── Client/                     # Client controllers
│   │   │   ├── HomeController.php      # Trang chủ
│   │   │   ├── ProductController.php   # Sản phẩm
│   │   │   ├── CartController.php      # Giỏ hàng
│   │   │   └── CheckoutController.php  # Thanh toán
│   │   │
│   │   └── ProfileController.php       # User profile (Breeze)
│   │
│   └── Middleware/
│       └── AdminMiddleware.php         # Middleware phân quyền admin
│
└── Models/
    ├── User.php                        # User model
    ├── Category.php                    # Category model
    ├── Product.php                     # Product model
    ├── Order.php                       # Order model
    ├── OrderItem.php                   # OrderItem model
    ├── Review.php                      # Review model
    └── Voucher.php                     # Voucher model
```

### `/database` - Database Files

```
database/
├── factories/
│   ├── UserFactory.php                 # User factory
│   ├── CategoryFactory.php             # Category factory
│   ├── ProductFactory.php              # Product factory
│   ├── OrderFactory.php                # Order factory
│   ├── OrderItemFactory.php            # OrderItem factory
│   ├── ReviewFactory.php               # Review factory
│   └── VoucherFactory.php              # Voucher factory
│
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_cache_table.php
│   ├── 0001_01_01_000002_create_jobs_table.php
│   ├── 2026_05_13_create_categories_table.php
│   ├── 2026_05_13_create_products_table.php
│   ├── 2026_05_13_create_orders_table.php
│   ├── 2026_05_13_create_order_items_table.php
│   ├── 2026_05_13_create_reviews_table.php
│   └── 2026_05_13_create_vouchers_table.php
│
└── seeders/
    └── DatabaseSeeder.php              # Main seeder
```

### `/resources` - Frontend Resources

```
resources/
├── css/
│   └── app.css                         # Main CSS (TailwindCSS)
│
├── js/
│   ├── app.js                          # Main JavaScript
│   └── bootstrap.js                    # Bootstrap JS
│
└── views/
    ├── admin/                          # Admin views
    │   └── dashboard.blade.php         # Admin dashboard
    │
    ├── auth/                           # Authentication views (Breeze)
    │   ├── login.blade.php
    │   ├── register.blade.php
    │   └── ...
    │
    ├── client/                         # Client views
    │   ├── home.blade.php              # Homepage
    │   ├── products/
    │   │   ├── index.blade.php         # Product list
    │   │   └── show.blade.php          # Product detail
    │   ├── cart/
    │   │   └── index.blade.php         # Cart page
    │   └── checkout/
    │       └── index.blade.php         # Checkout page
    │
    ├── components/                     # Blade components
    │
    ├── layouts/
    │   ├── admin.blade.php             # Admin layout
    │   ├── client.blade.php            # Client layout
    │   ├── app.blade.php               # App layout (Breeze)
    │   └── guest.blade.php             # Guest layout (Breeze)
    │
    └── profile/                        # Profile views (Breeze)
```

### `/routes` - Route Definitions

```
routes/
├── web.php                             # Web routes
├── auth.php                            # Auth routes (Breeze)
└── console.php                         # Console routes
```

### `/public` - Public Assets

```
public/
├── build/                              # Compiled assets (Vite)
├── .htaccess                           # Apache config
├── favicon.ico                         # Favicon
├── index.php                           # Entry point
└── robots.txt                          # Robots file
```

### `/config` - Configuration Files

```
config/
├── app.php                             # App config
├── auth.php                            # Auth config
├── database.php                        # Database config
├── filesystems.php                     # Filesystem config
└── ...                                 # Other configs
```

## 🔗 Relationships

### User Model
```php
- hasMany(Order)
- hasMany(Review)
```

### Category Model
```php
- hasMany(Product)
```

### Product Model
```php
- belongsTo(Category)
- hasMany(OrderItem)
- hasMany(Review)
```

### Order Model
```php
- belongsTo(User)
- hasMany(OrderItem)
```

### OrderItem Model
```php
- belongsTo(Order)
- belongsTo(Product)
```

### Review Model
```php
- belongsTo(User)
- belongsTo(Product)
```

## 🛣️ Route Structure

### Client Routes
```
GET  /                          # Home
GET  /products                  # Product list
GET  /products/{slug}           # Product detail
GET  /cart                      # Cart
POST /cart/add/{id}             # Add to cart
GET  /checkout                  # Checkout (auth)
POST /checkout/process          # Process order (auth)
```

### Admin Routes (Prefix: /admin, Middleware: auth, admin)
```
GET  /admin/dashboard           # Dashboard
Resource /admin/products        # Product CRUD
Resource /admin/categories      # Category CRUD
Resource /admin/orders          # Order CRUD
Resource /admin/users           # User CRUD
```

### Auth Routes (Laravel Breeze)
```
GET  /login                     # Login page
POST /login                     # Login process
GET  /register                  # Register page
POST /register                  # Register process
POST /logout                    # Logout
GET  /forgot-password           # Forgot password
POST /forgot-password           # Send reset link
GET  /reset-password/{token}    # Reset password page
POST /reset-password            # Reset password process
```

## 📦 Key Files

### Configuration
- `.env` - Environment variables (không commit)
- `.env.example` - Environment template
- `composer.json` - PHP dependencies
- `package.json` - Node dependencies
- `tailwind.config.js` - TailwindCSS config
- `vite.config.js` - Vite config

### Documentation
- `README.md` - Project overview
- `SETUP.md` - Setup instructions
- `CONTRIBUTING.md` - Contributing guidelines
- `TODO.md` - Task list
- `PROJECT_STRUCTURE.md` - This file

### Bootstrap
- `bootstrap/app.php` - Application bootstrap
- `bootstrap/providers.php` - Service providers

## 🎨 Frontend Stack

- **CSS Framework**: TailwindCSS
- **Build Tool**: Vite
- **Template Engine**: Blade
- **JavaScript**: Vanilla JS + Alpine.js (from Breeze)

## 🗄️ Database Tables

1. **users** - Người dùng
2. **categories** - Danh mục sản phẩm
3. **products** - Sản phẩm
4. **orders** - Đơn hàng
5. **order_items** - Chi tiết đơn hàng
6. **reviews** - Đánh giá sản phẩm
7. **vouchers** - Mã giảm giá
8. **cache** - Cache (Laravel)
9. **jobs** - Queue jobs (Laravel)
10. **sessions** - Sessions (Laravel)
11. **password_reset_tokens** - Password reset (Laravel)

## 🔐 Middleware

- `auth` - Require authentication
- `admin` - Require admin role (custom)
- `guest` - Guest only
- `verified` - Email verified

## 📝 Naming Conventions

### Controllers
- `{Name}Controller.php` - Singular, PascalCase
- Example: `ProductController.php`

### Models
- `{Name}.php` - Singular, PascalCase
- Example: `Product.php`

### Views
- `{name}.blade.php` - Lowercase, kebab-case
- Example: `product-list.blade.php`

### Routes
- `{resource}.{action}` - Lowercase, dot notation
- Example: `products.index`, `products.show`

### Database
- Tables: `{names}` - Plural, snake_case
- Columns: `{name}` - Singular, snake_case
- Example: `products` table, `category_id` column

---

**Note**: Cấu trúc này có thể thay đổi theo quá trình phát triển project.
