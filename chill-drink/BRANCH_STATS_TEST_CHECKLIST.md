# Branch Statistics E2E Test - Quick Checklist

## Quick Start

### Step 1: Run SQL Test (2 minutes)
```bash
# Open phpMyAdmin → SQL tab
# Copy-paste all queries from: tests/branch_stats_sql_test.sql
# Click Go
```

**After running:**
- ✅ 3 branches exist
- ✅ 2 orders created
- ✅ No SQL errors

---

### Step 2: Database Verification (1 minute)

Run these queries to verify:

```sql
-- Check branches
SELECT id, name, code, status FROM branches WHERE code IN ('TH001', 'BA001', 'HK001');
```
Expected:
```
1  Tây Hồ    TH001  1 (active)
2  Ba Đình   BA001  0 (inactive)
3  Hoàn Kiếm HK001  1 (active)
```

```sql
-- Check orders
SELECT id, delivery_type, branch_id, shipping_fee, total, payment_status, status 
FROM orders WHERE branch_id IS NOT NULL 
ORDER BY id DESC LIMIT 2;
```
Expected:
```
Order 2: pickup  3  0  50000  paid      completed
Order 1: pickup  1  0  50000  paid      completed
```

```sql
-- Check revenue calculation
SELECT 
  SUM(total) as total_revenue,
  SUM(CASE WHEN branch_id=1 THEN total ELSE 0 END) as tayho_revenue,
  SUM(CASE WHEN branch_id=3 THEN total ELSE 0 END) as hoankiem_revenue
FROM orders WHERE branch_id IS NOT NULL AND (payment_status='paid' OR status='completed');
```
Expected:
```
100000  50000  50000
```

---

### Step 3: Dashboard Verification (2 minutes)

1. **Login as Super Admin**
2. **Go to Dashboard:** `http://localhost:8000/admin/dashboard`
3. **Scroll to "Thống kê chi nhánh" section**

#### Check 8 Summary Cards:
- [ ] Tổng chi nhánh: **3**
- [ ] Chi nhánh hoạt động: **2**
- [ ] Tổng đơn chi nhánh: **2**
- [ ] Tổng doanh thu chi nhánh: **100,000đ**
- [ ] Đơn hôm nay: **2**
- [ ] Doanh thu hôm nay: **100,000đ**
- [ ] Doanh thu tháng: **100,000đ**
- [ ] Nhân viên chi nhánh: **0**

#### Check 4 Insight Cards:
- [ ] Top revenue branch: **Tây Hồ OR Hoàn Kiếm** (50% each)
- [ ] Top order branch: **Tây Hồ OR Hoàn Kiếm** (50% each)
- [ ] Highest cancelled: **Chưa có** (0%)
- [ ] Average revenue: **50,000đ**

#### Check 2 Charts:
**Revenue Chart:**
- [ ] Shows Tây Hồ bar
- [ ] Shows Hoàn Kiếm bar
- [ ] Does NOT show Ba Đình
- [ ] Total shows **100M** or **100,000đ**

**Order Chart:**
- [ ] Shows Tây Hồ: **1**
- [ ] Shows Hoàn Kiếm: **1**
- [ ] Does NOT show Ba Đình
- [ ] Total shows **2 đơn**

#### Check Ranking Table:
```
| Hạng | Chi nhánh    | Trạng thái | Đơn | Doanh thu | Hiệu suất |
|------|--------------|-----------|-----|-----------|----------|
| 1    | Tây Hồ       | Hoạt động | 1   | 50,000đ   | 50%      |
| 2    | Hoàn Kiếm    | Hoạt động | 1   | 50,000đ   | 50%      |
| 3    | Ba Đình      | Tạm ngưng | 0   | 0đ        | 0%       |
```

- [ ] Sorted by revenue (highest first)
- [ ] Top row (Tây Hồ) has light green background
- [ ] Ba Đình shown with 0 data

---

### Step 4: Checkout Verification (1 minute)

1. **Logout Super Admin**
2. **Login as test customer:** `test-e2e@example.com` / `password`
3. **Go to cart, click Checkout**
4. **Select "Lấy tại chi nhánh"**

#### Check Branch Dropdown:
- [ ] Shows **Tây Hồ**
- [ ] Shows **Hoàn Kiếm**
- [ ] Does NOT show **Ba Đình**

#### Check Shipping Fee:
- [ ] When "Lấy tại chi nhánh" selected: **0đ**
- [ ] When "Giao đến địa chỉ" selected: **[some amount]đ**

---

## Expected Outcome Summary

| Component | Expected Result | ✅/❌ |
|-----------|-----------------|------|
| Branches | 3 created (2 active, 1 inactive) | |
| Orders | 2 created with branch_id and delivery_type=pickup | |
| Summary Cards | All 8 showing correct totals | |
| Insight Cards | All 4 showing correct data | |
| Revenue Chart | Shows Tây Hồ & Hoàn Kiếm, not Ba Đình | |
| Order Chart | Shows both branches with counts | |
| Ranking Table | Sorted by revenue, Ba Đình last | |
| Checkout Dropdown | Only active branches | |
| Shipping Fee | 0 for pickup, [amount] for delivery | |

---

## Troubleshooting Quick Fixes

| Problem | Solution |
|---------|----------|
| Branches don't show | Run SQL script again, verify status field |
| Revenue shows 0 | Orders must have `payment_status='paid'` OR `status='completed'` |
| Dropdown shows Ba Đình | Check `branches.status` - should be 0 (false) for Ba Đình |
| Charts empty | Refresh page, clear cache: `php artisan cache:clear` |
| Numbers not formatted | Page may be cached; hard refresh (Ctrl+F5) |

---

## Database Quick Queries

Copy-paste these to verify each component:

### All Branches
```sql
SELECT id, name, status FROM branches WHERE code IN ('TH001', 'BA001', 'HK001');
```

### All Orders with Branch
```sql
SELECT id, delivery_type, branch_id, total, payment_status, status FROM orders WHERE branch_id IS NOT NULL;
```

### Revenue by Branch
```sql
SELECT b.name, 
  COUNT(o.id) as orders,
  SUM(CASE WHEN o.payment_status='paid' OR o.status='completed' THEN o.total ELSE 0 END) as revenue
FROM branches b
LEFT JOIN orders o ON b.id = o.branch_id
WHERE b.code IN ('TH001', 'BA001', 'HK001')
GROUP BY b.id ORDER BY revenue DESC;
```

### Active Branches (for checkout)
```sql
SELECT id, name FROM branches WHERE status = 1 ORDER BY name;
```

---

## Time Estimates

- SQL Test: **2 min**
- DB Verification: **1 min**
- Dashboard Check: **2 min**
- Checkout Check: **1 min**

**Total: ~6 minutes**

---

## Success = All Checked ✅

- [ ] SQL test runs without errors
- [ ] Database has 3 branches, 2 orders
- [ ] Dashboard shows all statistics sections
- [ ] Numbers match expected values
- [ ] Checkout dropdown correct
- [ ] Shipping fee logic correct
- [ ] Charts display correctly
- [ ] Table renders correctly

**If all checked: READY FOR PRODUCTION ✅**

---

## Commands Reference

```bash
# Run SQL test
# → Open phpMyAdmin → SQL tab → paste tests/branch_stats_sql_test.sql

# Run Tinker test (optional)
php artisan tinker
> include 'tests/BranchStatisticsE2ETest.php'
> $test = new Tests\BranchStatisticsE2ETest()
> $test->runFullTest()

# Clear cache if needed
php artisan cache:clear
php artisan config:clear

# Check logs
tail -f storage/logs/laravel.log
```

---

**Status: READY FOR END-TO-END TESTING ✅**

Run the SQL script first, then follow the checklist above.
