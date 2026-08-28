# Branch Statistics End-to-End Runtime Test Guide

## Overview
This guide walks you through a complete end-to-end test of the Branch Statistics feature with real database records and application logic verification.

---

## Prerequisites
- ✅ Application is running
- ✅ Database is accessible
- ✅ phpMyAdmin or SQL client available
- ✅ Laravel Tinker or PHP CLI access

---

## Test Flow

### STEP 0: Run Diagnostic (Optional)

Use the diagnostic script to verify the required tables, models, controllers,
routes, and views before creating E2E data:

```bash
php artisan tinker
> include 'tests/diagnose_branch_stats.php'
```

Expected output:

```text
ALL CHECKS PASSED - System is ready!
```

### STEP 1: Run SQL Test Script
**File**: `tests/branch_stats_sql_test.sql`

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select your database
3. Click "SQL" tab
4. Copy all content from `branch_stats_sql_test.sql`
5. Paste into SQL editor
6. Click "Go" to execute

**Option B: Using MySQL CLI**
```bash
cd chill-drink
mysql -u root -p[password] [database_name] < tests/branch_stats_sql_test.sql
```

**Expected Output:**
```
✅ 3 branches created (Tây Hồ, Ba Đình, Hoàn Kiếm)
✅ Test customer created
✅ Test product created
✅ Order 1 created (pending, pickup, branch_id=Tây Hồ, shipping_fee=0)
✅ Order 1 marked as completed and paid
✅ Order 2 created (completed, pickup, branch_id=Hoàn Kiếm)
✅ Final statistics show both branches with correct order counts and revenue
```

---

### STEP 2: Run Laravel Tinker Test (Optional - for detailed verification)

**Command:**
```bash
# Navigate to project
cd c:\xampp\htdocs\DATN\DATN\chill-drink

# Start Laravel Tinker
php artisan tinker

# Include and run the test
> include 'tests/BranchStatisticsE2ETest.php'
> $test = new Tests\BranchStatisticsE2ETest()
> $result = $test->runFullTest()
```

**Expected Output:**
```
=== BRANCH STATISTICS E2E TEST ===

[STEP 1] Creating 3 branches...
✅ Branches created:
  - Tây Hồ (ID: 1, Status: 1)
  - Ba Đình (ID: 2, Status: 0)
  - Hoàn Kiếm (ID: 3, Status: 1)

[STEP 2] Creating test customer and product...
✅ Test data created:
  - Customer ID: 5, Email: test-e2e@example.com
  - Product ID: 10, Name: E2E Test Product, Price: 50,000đ

[STEP 3] Simulating authenticated pickup checkout (Tây Hồ)...
✅ Order 1 created (ID: 150):
  - User: 5
  - Delivery Type: pickup
  - Branch ID: 1
  - Shipping Fee: 0đ
  - Total: 50,000đ
  - Payment Status: pending
  - Status: pending

[STEP 4] Verifying order 1 in database...
✅ Database verification:
  - delivery_type = 'pickup': ✅
  - branch_id = 1: ✅
  - shipping_fee = 0: ✅
  - status = 'pending': ✅
  - payment_status = 'pending': ✅

[STEP 5] Verifying branch statistics BEFORE order completion...
✅ Statistics (Order 1 Pending):
  - Total Branch Orders: 1
  - Tây Hồ Order Count: 1
  - Total Branch Revenue (paid/completed): 0đ
  - Tây Hồ Revenue: 0đ

[STEP 6] Marking order 1 as completed and paid...
✅ Order 1 marked as completed and paid

[STEP 7] Verifying revenue statistics AFTER order 1 completion...
✅ Statistics (Order 1 Completed & Paid):
  - Total Branch Orders: 1
  - Tây Hồ Order Count: 1
  - Total Branch Revenue: 50,000đ
  - Tây Hồ Revenue: 50,000đ
  - Revenue Increase: 50,000đ ✅

[STEP 8] Creating second completed pickup order for Hoàn Kiếm...
✅ Order 2 created and marked as completed (ID: 151):
  - Branch: Hoàn Kiếm (ID: 3)
  - Total: 50,000đ

[STEP 9] Verifying final branch comparison...
✅ Final Statistics:
  - Total Orders: 2
  - Tây Hồ Orders: 1, Revenue: 50,000đ
  - Hoàn Kiếm Orders: 1, Revenue: 50,000đ
  - Ba Đình Orders: 0, Revenue: 0đ

[STEP 10] Verifying checkout branch dropdown logic...
✅ Active branches (should appear in checkout):
  - Hoàn Kiếm (ID: 3)
  - Tây Hồ (ID: 1)
✅ Inactive branches (should NOT appear in checkout):
  - Ba Đình (ID: 2)

[SUMMARY] Test Verification
✅ Order 1: delivery_type='pickup', branch_id=1, shipping_fee=0, status='completed', payment_status='paid'
✅ Order 2: delivery_type='pickup', branch_id=3, shipping_fee=0, status='completed', payment_status='paid'
✅ Total Orders with branch_id: 2
✅ Total Revenue (completed/paid): 100,000đ
✅ Tây Hồ appears in ranking: Yes
✅ Hoàn Kiếm appears in ranking: Yes
✅ Ba Đình appears in ranking but with 0 data: Yes
✅ Ba Đình appears in checkout dropdown: No ✅

=== TEST COMPLETE ===
```

---

### STEP 3: Verify Dashboard Statistics

**Manual Verification:**
1. Navigate to Super Admin Dashboard: `http://localhost:8000/admin/dashboard`
2. Scroll down to "Branch Statistics" section
3. Verify the following:

#### A. 8 Branch Summary Cards
```
✅ Tổng chi nhánh: 3
✅ Chi nhánh hoạt động: 2 (Tây Hồ, Hoàn Kiếm)
✅ Tổng đơn chi nhánh: 2
✅ Tổng doanh thu chi nhánh: 100,000đ
✅ Đơn hôm nay: 2
✅ Doanh thu hôm nay: 100,000đ
✅ Doanh thu tháng: 100,000đ
✅ Nhân viên chi nhánh: 0
```

#### B. 4 Branch Insight Cards
```
✅ Chi nhánh doanh thu cao nhất: Tây Hồ (50%)
   OR Hoàn Kiếm (50%)  — whichever ranks first by revenue
✅ Chi nhánh nhiều đơn nhất: Tây Hồ (50%)
   OR Hoàn Kiếm (50%)  — whichever ranks first by order count
✅ Chi nhánh hủy đơn cao nhất: Chưa có (0%)
✅ Doanh thu bình quân/chi nhánh: 50,000đ
```

#### C. 2 Branch Comparison Charts

**Revenue Chart (Doanh thu theo chi nhánh):**
```
✅ Tây Hồ: 50M (50,000,000 = 50 triệu)
✅ Hoàn Kiếm: 50M
✅ Ba Đình: Not displayed (status = inactive)
✅ Total: 100M
```

**Order Chart (Đơn hàng theo chi nhánh):**
```
✅ Tây Hồ: 1
✅ Hoàn Kiếm: 1
✅ Ba Đình: Not displayed
✅ Total: 2
```

#### D. Branch Ranking Table

```
| Hạng | Chi nhánh    | Trạng thái   | Nhân viên | Đơn | Hoàn | Hủy | Doanh thu | GTB/đơn | Hiệu suất |
|------|--------------|-------------|----------|-----|------|-----|-----------|---------|----------|
| 1    | Tây Hồ       | Hoạt động   | 0/0      | 1   | 1    | 0   | 50,000đ   | 50,000đ | 50%      |
| 2    | Hoàn Kiếm    | Hoạt động   | 0/0      | 1   | 1    | 0   | 50,000đ   | 50,000đ | 50%      |
| 3    | Ba Đình      | Tạm ngưng   | 0/0      | 0   | 0    | 0   | 0đ        | 0đ      | 0%       |
```

✅ **Sorted by revenue (descending)**
✅ **Top branch (Tây Hồ) has light green background**
✅ **Ba Đình appears but with 0 data**

---

### STEP 4: Verify Checkout Branch Dropdown

**Flow:**
1. Logout from Super Admin
2. Login as the test customer: `test-e2e@example.com` / `password`
3. Add a product to cart
4. Click "Checkout"
5. Select "Lấy tại chi nhánh" (Pickup)
6. Look at branch dropdown

**Expected:**
```
✅ Branches shown: Hoàn Kiếm, Tây Hồ (only ACTIVE)
✅ Ba Đình NOT shown (status = 0/inactive)
```

**Database Verification Query:**
```sql
SELECT id, name, status FROM branches 
WHERE status = 1 AND code IN ('TH001', 'BA001', 'HK001')
ORDER BY name;
```

**Expected Result:**
```
| id | name      | status |
|----|-----------|--------|
| 1  | Tây Hồ    | 1      |
| 3  | Hoàn Kiếm | 1      |
```

---

## Database Verification Queries

### Query 1: Verify Order Data
```sql
SELECT 
  id, user_id, delivery_type, branch_id, shipping_fee, 
  total, payment_status, status, created_at
FROM orders 
WHERE branch_id IS NOT NULL 
ORDER BY id DESC 
LIMIT 5;
```

**Expected:**
```
Order 1: delivery_type=pickup, branch_id=1, shipping_fee=0, total=50000, payment_status=paid, status=completed
Order 2: delivery_type=pickup, branch_id=3, shipping_fee=0, total=50000, payment_status=paid, status=completed
```

### Query 2: Verify Branch Statistics Calculation
```sql
SELECT 
  b.id, b.name,
  COUNT(DISTINCT o.id) as total_orders,
  SUM(CASE WHEN o.payment_status = 'paid' OR o.status = 'completed' THEN o.total ELSE 0 END) as revenue
FROM branches b
LEFT JOIN orders o ON b.id = o.branch_id AND o.branch_id IS NOT NULL
WHERE b.code IN ('TH001', 'BA001', 'HK001')
GROUP BY b.id, b.name
ORDER BY revenue DESC;
```

**Expected:**
```
| id | name      | total_orders | revenue |
|----|-----------|--------------|---------|
| 1  | Tây Hồ    | 1            | 50000   |
| 3  | Hoàn Kiếm | 1            | 50000   |
| 2  | Ba Đình   | 0            | 0       |
```

### Query 3: Verify Revenue Only Counts Paid/Completed
```sql
SELECT 
  COUNT(*) as total_orders,
  SUM(CASE WHEN payment_status = 'paid' OR status = 'completed' THEN 1 ELSE 0 END) as paid_completed_orders,
  SUM(total) as total_if_all_counted,
  SUM(CASE WHEN payment_status = 'paid' OR status = 'completed' THEN total ELSE 0 END) as revenue_only_paid
FROM orders 
WHERE branch_id IS NOT NULL;
```

**Expected:**
```
| total_orders | paid_completed_orders | total_if_all_counted | revenue_only_paid |
|--------------|----------------------|-------------------|------------------|
| 2            | 2                    | 100000            | 100000           |
```

### Query 4: Verify Checkout Branch Dropdown Query
```sql
SELECT id, name, status FROM branches 
WHERE status = 1 
ORDER BY name;
```

**Expected:** Only active branches (Tây Hồ, Hoàn Kiếm), NOT Ba Đình

---

## Troubleshooting

### ❌ "Branches not showing in dropdown"
**Check:**
```sql
SELECT id, name, status FROM branches WHERE code IN ('TH001', 'BA001', 'HK001');
```
All 3 should exist. Active ones should have status=1.

### ❌ "Revenue shows 0 for completed orders"
**Check:**
```sql
SELECT id, payment_status, status, total FROM orders WHERE branch_id IS NOT NULL;
```
Verify orders have either `payment_status='paid'` OR `status='completed'`.

### ❌ "Charts/Table not showing data"
**Check:**
1. Orders exist with `branch_id IS NOT NULL`
2. At least one order has `payment_status='paid'` OR `status='completed'`
3. Cache might need clearing:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### ❌ "Dropdown shows Ba Đình (inactive)"
**Issue:** Status query broken
**Check:**
```sql
SELECT * FROM branches WHERE code = 'BA001';
```
Verify `status = 0` (false/inactive)

---

## Final Verification Checklist

### ✅ Order Data
- [ ] Order 1 has `delivery_type='pickup'`
- [ ] Order 1 has `branch_id=Tây Hồ ID`
- [ ] Order 1 has `shipping_fee=0`
- [ ] Order 1 has `payment_status='paid'` or `status='completed'`
- [ ] Order 2 has `branch_id=Hoàn Kiếm ID`

### ✅ Branch Configuration
- [ ] Tây Hồ: status=1 (active)
- [ ] Ba Đình: status=0 (inactive)
- [ ] Hoàn Kiếm: status=1 (active)

### ✅ Dashboard Statistics
- [ ] 8 Summary Cards show correct totals
- [ ] 4 Insight Cards show correct tops
- [ ] 2 Charts show both branches with data
- [ ] Ranking Table shows correct order and revenue

### ✅ Checkout Behavior
- [ ] Active branches appear in dropdown
- [ ] Ba Đình does NOT appear in dropdown
- [ ] Shipping fee = 0 when pickup selected
- [ ] Address field hides when pickup selected

### ✅ Database Integrity
- [ ] Total branch orders: 2
- [ ] Total branch revenue (paid/completed): 100,000đ
- [ ] Tây Hồ revenue: 50,000đ
- [ ] Hoàn Kiếm revenue: 50,000đ
- [ ] Ba Đình revenue: 0đ

---

## Success Criteria

✅ **All of the following must be true:**

1. SQL test script runs without errors
2. 3 branches created (1 active, 1 inactive, 1 active)
3. 2 orders created with correct delivery_type and branch_id
4. Dashboard displays all 4 sections (cards, insights, charts, table)
5. Statistics update correctly after orders are marked paid/completed
6. Checkout dropdown only shows active branches
7. Revenue calculation only counts paid/completed orders
8. All numbers format correctly in Vietnamese style (đ, thousand separators)

---

## Commands Quick Reference

```bash
# Start Laravel Tinker
php artisan tinker

# Clear cache if needed
php artisan cache:clear
php artisan config:clear

# View application log
tail -f storage/logs/laravel.log

# Run tests
php artisan test

# Check database connection
php artisan tinker
> DB::connection()->getPdo()
```

---

**Last Updated:** 2024
**Status:** ✅ Ready for Testing
