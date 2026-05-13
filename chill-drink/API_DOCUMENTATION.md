# 🔌 API Documentation (Future Reference)

## 📝 Giới Thiệu

Tài liệu này mô tả các endpoints có thể được phát triển thành API trong tương lai nếu cần xây dựng mobile app hoặc SPA.

## 🔐 Authentication

Hiện tại project sử dụng session-based authentication (Laravel Breeze).
Nếu cần API, có thể sử dụng Laravel Sanctum hoặc Passport.

## 📍 Endpoints

### Products

#### Get All Products
```http
GET /api/products
```

**Query Parameters:**
- `category` (optional) - Filter by category ID
- `search` (optional) - Search by name
- `sort` (optional) - Sort by: price_asc, price_desc, name, newest
- `page` (optional) - Page number
- `per_page` (optional) - Items per page (default: 12)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Trà Sữa Trân Châu",
      "slug": "tra-sua-tran-chau",
      "price": 35000,
      "image": "https://...",
      "category": {
        "id": 1,
        "name": "Trà Sữa"
      },
      "stock": 100,
      "status": true
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 30,
    "per_page": 12
  }
}
```

#### Get Single Product
```http
GET /api/products/{slug}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Trà Sữa Trân Châu",
    "slug": "tra-sua-tran-chau",
    "price": 35000,
    "description": "...",
    "image": "https://...",
    "category": {
      "id": 1,
      "name": "Trà Sữa"
    },
    "stock": 100,
    "status": true,
    "reviews": [],
    "average_rating": 4.5
  }
}
```

### Categories

#### Get All Categories
```http
GET /api/categories
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Trà Sữa",
      "slug": "tra-sua",
      "description": "...",
      "products_count": 10
    }
  ]
}
```

### Cart

#### Get Cart
```http
GET /api/cart
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "items": [
      {
        "product_id": 1,
        "name": "Trà Sữa Trân Châu",
        "price": 35000,
        "quantity": 2,
        "subtotal": 70000
      }
    ],
    "total": 70000
  }
}
```

#### Add to Cart
```http
POST /api/cart
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

**Response:**
```json
{
  "message": "Product added to cart",
  "data": {
    "cart_count": 3
  }
}
```

#### Update Cart Item
```http
PUT /api/cart/{product_id}
```

**Body:**
```json
{
  "quantity": 3
}
```

#### Remove from Cart
```http
DELETE /api/cart/{product_id}
```

### Orders

#### Get User Orders
```http
GET /api/orders
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "total_price": 70000,
      "status": "pending",
      "payment_method": "cod",
      "created_at": "2026-05-13T10:00:00Z",
      "items": [
        {
          "product_name": "Trà Sữa Trân Châu",
          "quantity": 2,
          "price": 35000
        }
      ]
    }
  ]
}
```

#### Create Order
```http
POST /api/orders
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "payment_method": "cod",
  "note": "Giao giờ hành chính"
}
```

**Response:**
```json
{
  "message": "Order created successfully",
  "data": {
    "order_id": 1,
    "total_price": 70000,
    "status": "pending"
  }
}
```

#### Get Order Detail
```http
GET /api/orders/{id}
```

### Reviews

#### Get Product Reviews
```http
GET /api/products/{slug}/reviews
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "user": {
        "name": "Nguyễn Văn A"
      },
      "rating": 5,
      "comment": "Rất ngon!",
      "created_at": "2026-05-13T10:00:00Z"
    }
  ],
  "meta": {
    "average_rating": 4.5,
    "total_reviews": 10
  }
}
```

#### Create Review
```http
POST /api/products/{slug}/reviews
```

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "rating": 5,
  "comment": "Rất ngon!"
}
```

### User Profile

#### Get Profile
```http
GET /api/profile
```

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Nguyễn Văn A",
    "email": "user@example.com",
    "phone": "0123456789",
    "address": "Hà Nội",
    "points": 100
  }
}
```

#### Update Profile
```http
PUT /api/profile
```

**Body:**
```json
{
  "name": "Nguyễn Văn A",
  "phone": "0123456789",
  "address": "Hà Nội"
}
```

## 🔒 Admin Endpoints

### Dashboard Stats
```http
GET /api/admin/dashboard
```

**Headers:**
```
Authorization: Bearer {token}
X-Admin-Role: required
```

**Response:**
```json
{
  "data": {
    "total_users": 100,
    "total_products": 50,
    "total_orders": 200,
    "total_revenue": 10000000,
    "recent_orders": []
  }
}
```

### Product Management
```http
GET    /api/admin/products
POST   /api/admin/products
GET    /api/admin/products/{id}
PUT    /api/admin/products/{id}
DELETE /api/admin/products/{id}
```

### Category Management
```http
GET    /api/admin/categories
POST   /api/admin/categories
GET    /api/admin/categories/{id}
PUT    /api/admin/categories/{id}
DELETE /api/admin/categories/{id}
```

### Order Management
```http
GET    /api/admin/orders
GET    /api/admin/orders/{id}
PUT    /api/admin/orders/{id}/status
```

**Update Status Body:**
```json
{
  "status": "processing"
}
```

## 📊 Status Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## 🔧 Error Response Format

```json
{
  "message": "Error message",
  "errors": {
    "field": ["Error detail"]
  }
}
```

## 📝 Notes

1. **Authentication**: Sử dụng Laravel Sanctum cho API tokens
2. **Rate Limiting**: Giới hạn 60 requests/minute
3. **Pagination**: Default 12 items per page
4. **Versioning**: Có thể thêm `/api/v1/` prefix
5. **CORS**: Cần cấu hình cho mobile app

## 🚀 Implementation Steps

Nếu cần implement API:

1. Install Laravel Sanctum
```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

2. Tạo API Controllers trong `app/Http/Controllers/Api/`

3. Định nghĩa routes trong `routes/api.php`

4. Thêm API Resource classes

5. Setup CORS trong `config/cors.php`

6. Thêm API documentation tool (Swagger/OpenAPI)

---

**Note**: Đây là tài liệu tham khảo cho tương lai. Hiện tại project sử dụng web routes và session authentication.
