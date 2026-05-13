# 🤝 Quy Tắc Đóng Góp Code

## 📌 Nguyên Tắc Chung

1. **Clean Code**: Code phải sạch, dễ đọc, dễ hiểu
2. **Comment**: Comment đầy đủ cho các function phức tạp
3. **Validation**: Luôn validate input từ user
4. **Security**: Không để lộ thông tin nhạy cảm
5. **Testing**: Test kỹ trước khi commit

## 🌿 Git Branch Strategy

### Branch Types

```
main                    # Production code
├── develop            # Development code
├── feature/xxx        # Tính năng mới
├── bugfix/xxx         # Sửa lỗi
├── hotfix/xxx         # Sửa lỗi khẩn cấp
└── refactor/xxx       # Tái cấu trúc code
```

### Naming Convention

```bash
# Tính năng mới
feature/product-management
feature/user-authentication

# Sửa lỗi
bugfix/cart-calculation
bugfix/login-validation

# Hotfix
hotfix/security-patch
hotfix/payment-error
```

## 💬 Commit Message Convention

### Format

```
<type>: <subject>

<body> (optional)
```

### Types

- **Add**: Thêm tính năng mới
- **Fix**: Sửa lỗi
- **Update**: Cập nhật code hiện tại
- **Remove**: Xóa code/file
- **Refactor**: Tái cấu trúc code
- **Docs**: Cập nhật documentation
- **Style**: Format code (không thay đổi logic)
- **Test**: Thêm/sửa tests

### Examples

```bash
# Good ✅
git commit -m "Add: product search functionality"
git commit -m "Fix: cart total calculation error"
git commit -m "Update: improve product listing performance"

# Bad ❌
git commit -m "update"
git commit -m "fix bug"
git commit -m "asdfgh"
```

## 📝 Code Style Guide

### PHP/Laravel

```php
// ✅ Good
class ProductController extends Controller
{
    /**
     * Display product list
     */
    public function index()
    {
        $products = Product::where('status', true)
            ->with('category')
            ->paginate(12);
            
        return view('products.index', compact('products'));
    }
}

// ❌ Bad
class ProductController extends Controller{
public function index(){
$products=Product::where('status',true)->with('category')->paginate(12);
return view('products.index',compact('products'));
}
}
```

### Blade Templates

```blade
{{-- ✅ Good --}}
@foreach($products as $product)
    <div class="product-card">
        <h3>{{ $product->name }}</h3>
        <p>{{ number_format($product->price) }}đ</p>
    </div>
@endforeach

{{-- ❌ Bad --}}
@foreach($products as $product)<div class="product-card"><h3>{{$product->name}}</h3><p>{{$product->price}}đ</p></div>@endforeach
```

### JavaScript

```javascript
// ✅ Good
function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    
    fetch(`/cart/add/${productId}`, {
        method: 'POST',
        body: JSON.stringify({ quantity })
    });
}

// ❌ Bad
function addToCart(productId){const quantity=document.getElementById('quantity').value;fetch(`/cart/add/${productId}`,{method:'POST',body:JSON.stringify({quantity})})}
```

## 🔍 Code Review Checklist

### Trước Khi Tạo Pull Request

- [ ] Code chạy không lỗi
- [ ] Đã test các tính năng mới
- [ ] Đã xóa code không dùng
- [ ] Đã xóa console.log() / dd() / dump()
- [ ] Không commit file .env
- [ ] Comment đầy đủ
- [ ] Code format đúng chuẩn

### Pull Request Description

```markdown
## Mô tả
Thêm chức năng tìm kiếm sản phẩm theo tên và danh mục

## Thay đổi
- Thêm search form trong header
- Thêm filter theo category
- Cập nhật ProductController
- Thêm route mới

## Test
- [x] Tìm kiếm theo tên
- [x] Filter theo danh mục
- [x] Responsive trên mobile
- [x] Không có lỗi console

## Screenshots
[Đính kèm ảnh nếu có]
```

## 🚫 Những Điều Không Nên Làm

### ❌ Commit Trực Tiếp Vào Main

```bash
# KHÔNG BAO GIỜ làm thế này
git checkout main
git add .
git commit -m "update"
git push origin main
```

### ❌ Commit File Nhạy Cảm

```bash
# Không commit các file này
.env
.env.local
/vendor/
/node_modules/
*.log
```

### ❌ Code Logic Trong Blade

```blade
{{-- ❌ Bad --}}
@php
    $total = 0;
    foreach($items as $item) {
        $total += $item->price * $item->quantity;
    }
@endphp

{{-- ✅ Good - Làm trong Controller --}}
{{ $order->total_price }}
```

### ❌ Hardcode Values

```php
// ❌ Bad
if ($user->role == 'admin') {
    // ...
}

// ✅ Good
if ($user->isAdmin()) {
    // ...
}
```

## ✅ Best Practices

### 1. Validation

```php
// Luôn validate input
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'price' => 'required|numeric|min:0',
]);
```

### 2. Eloquent Relationships

```php
// Sử dụng eager loading
$products = Product::with('category', 'reviews')->get();

// Thay vì N+1 query
$products = Product::all();
foreach($products as $product) {
    echo $product->category->name; // N+1 problem
}
```

### 3. Route Naming

```php
// Đặt tên route rõ ràng
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
```

### 4. Security

```php
// Sử dụng CSRF protection
<form method="POST">
    @csrf
    <!-- form fields -->
</form>

// Escape output
{{ $product->name }}  // Safe
{!! $product->name !!}  // Unsafe - chỉ dùng khi cần HTML
```

## 📊 Workflow Example

```bash
# 1. Lấy code mới nhất
git checkout main
git pull origin main

# 2. Tạo branch mới
git checkout -b feature/product-filter

# 3. Làm việc và commit
git add .
git commit -m "Add: product filter by category"

# 4. Push lên GitHub
git push origin feature/product-filter

# 5. Tạo Pull Request trên GitHub

# 6. Sau khi được approve, merge vào main

# 7. Xóa branch local
git branch -d feature/product-filter
```

## 🎯 Team Responsibilities

### Team Lead
- Review Pull Requests
- Merge code vào main
- Giải quyết conflicts
- Hướng dẫn team members

### Developers
- Viết code theo chuẩn
- Test kỹ trước khi commit
- Tạo Pull Request rõ ràng
- Review code của nhau

## 📞 Khi Cần Giúp Đỡ

1. Đọc lại documentation
2. Google/Stack Overflow
3. Hỏi trong group chat
4. Hỏi team lead

---

**Remember: Code is read more often than it is written! 📖**
