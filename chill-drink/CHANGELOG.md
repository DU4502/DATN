# 📝 Changelog

Tất cả các thay đổi quan trọng của project sẽ được ghi lại ở đây.

## [Unreleased]

### To Do
- Admin CRUD views
- Product detail page
- Checkout page
- User profile
- Review system
- Voucher system

## [0.1.0] - 2026-05-13

### Added - Base Project Setup

#### Core
- ✅ Laravel 11 project initialization
- ✅ Laravel Breeze authentication
- ✅ TailwindCSS integration
- ✅ Vite build tool setup

#### Database
- ✅ Users table (with role, phone, address, points)
- ✅ Categories table
- ✅ Products table
- ✅ Orders table
- ✅ Order_items table
- ✅ Reviews table
- ✅ Vouchers table
- ✅ All migrations with relationships
- ✅ Model factories
- ✅ Database seeder with sample data

#### Authentication & Authorization
- ✅ Login/Register (Laravel Breeze)
- ✅ Forgot Password
- ✅ Admin Middleware
- ✅ Role-based access control

#### Controllers
**Admin:**
- ✅ DashboardController (statistics)
- ✅ ProductController (resource)
- ✅ CategoryController (resource)
- ✅ OrderController (resource)
- ✅ UserController (resource)

**Client:**
- ✅ HomeController (homepage)
- ✅ ProductController (list, detail)
- ✅ CartController (CRUD operations)
- ✅ CheckoutController (order creation)

#### Models
- ✅ User model with relationships
- ✅ Category model
- ✅ Product model with relationships
- ✅ Order model with relationships
- ✅ OrderItem model
- ✅ Review model
- ✅ Voucher model

#### Views & UI
- ✅ Admin layout (sidebar, navbar)
- ✅ Client layout (header, footer)
- ✅ Homepage with hero section
- ✅ Product listing page
- ✅ Cart page
- ✅ Admin dashboard with statistics
- ✅ Responsive design

#### Features
- ✅ Product listing with pagination
- ✅ Product search
- ✅ Category filter
- ✅ Shopping cart (session-based)
- ✅ Add to cart functionality
- ✅ Cart update/remove
- ✅ Checkout process
- ✅ Order creation
- ✅ Admin dashboard statistics

#### Routes
- ✅ Client routes (home, products, cart, checkout)
- ✅ Admin routes (dashboard, CRUD resources)
- ✅ Auth routes (login, register, password reset)
- ✅ Route groups with middleware

#### Documentation
- ✅ README.md - Project overview
- ✅ SETUP.md - Detailed setup guide
- ✅ QUICK_START.md - Quick start guide
- ✅ CONTRIBUTING.md - Code standards & workflow
- ✅ TODO.md - Task list & sprint planning
- ✅ PROJECT_STRUCTURE.md - Project structure
- ✅ API_DOCUMENTATION.md - API reference
- ✅ DEPLOYMENT.md - Deployment guide
- ✅ PROJECT_SUMMARY.md - Project summary
- ✅ CHANGELOG.md - This file
- ✅ LICENSE - MIT License

#### Configuration
- ✅ .env.example with proper defaults
- ✅ .gitignore for Laravel
- ✅ Composer dependencies
- ✅ NPM dependencies
- ✅ TailwindCSS configuration
- ✅ Vite configuration

### Technical Details

**Backend:**
- Laravel 11.x
- PHP 8.2+
- MySQL database
- Eloquent ORM
- Blade templating

**Frontend:**
- TailwindCSS 3.x
- Alpine.js (from Breeze)
- Vite build tool
- Responsive design

**Architecture:**
- MVC pattern
- RESTful structure
- Repository pattern ready
- Service layer ready

### Database Schema

```
users (id, name, email, password, role, phone, address, points)
├── orders (user_id)
└── reviews (user_id)

categories (id, name, slug, description, status)
└── products (category_id)

products (id, category_id, name, slug, image, price, stock, status)
├── order_items (product_id)
└── reviews (product_id)

orders (id, user_id, total_price, payment_method, status, note)
└── order_items (order_id)

order_items (id, order_id, product_id, quantity, price)

reviews (id, user_id, product_id, rating, comment)

vouchers (id, code, discount_percent, quantity, expired_date, status)
```

### Sample Data

- 1 Admin account (admin@chilldrink.com)
- 10 User accounts
- 6 Product categories
- 30 Products

### Known Issues

- Admin CRUD views need to be implemented
- Product detail page needs completion
- Checkout page needs completion
- Image upload not yet implemented
- Email notifications not configured
- Payment gateway not integrated

### Notes

This is the base project setup. All core functionality is in place and ready for team development. Next phase will focus on completing the CRUD views and advanced features.

---

## Version Format

Format: [MAJOR.MINOR.PATCH]
- MAJOR: Breaking changes
- MINOR: New features
- PATCH: Bug fixes

## Categories

- **Added**: New features
- **Changed**: Changes in existing functionality
- **Deprecated**: Soon-to-be removed features
- **Removed**: Removed features
- **Fixed**: Bug fixes
- **Security**: Security fixes

---

**Last Updated**: 2026-05-13
