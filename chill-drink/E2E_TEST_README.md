# Branch Statistics End-to-End Test - Complete Guide

## What's Included

This package contains 3 comprehensive testing files:

### 1. **SQL Test Script** (`tests/branch_stats_sql_test.sql`)
- **Purpose**: Creates test data and verifies all database operations
- **What it does**:
  - Creates 3 branches (2 active, 1 inactive)
  - Creates test customer and product
  - Creates 2 pickup orders with branch_id
  - Marks them as completed and paid
  - Verifies all statistics calculations
  - Outputs verification summary

**Use this to verify database layer** ✅

### 2. **Laravel Tinker Test** (`tests/BranchStatisticsE2ETest.php`)
- **Purpose**: Runs full end-to-end test via Laravel application layer
- **What it does**:
  - Creates branches, customers, products
  - Simulates authenticated checkout
  - Creates real Order and OrderItem records
  - Verifies all backend calculations
  - Returns detailed test results

**Use this if you want programmatic verification** ✅

### 3. **Test Guide & Checklist** (`BRANCH_STATS_E2E_TEST_GUIDE.md` & `BRANCH_STATS_TEST_CHECKLIST.md`)
- **Purpose**: Step-by-step instructions with expected outputs
- **What they provide**:
  - Detailed test procedures
  - Expected outputs for each step
  - Database verification queries
  - Dashboard verification steps
  - Troubleshooting guide
  - Quick reference checklist

**Use these as your testing manual** ✅

---

## Quick Start (5 minutes)

### Option 1: SQL Test (Fastest)

1. **Open phpMyAdmin** or MySQL client
2. **Run the SQL script**:
   ```
   File: tests/branch_stats_sql_test.sql
   ```
3. **Verify output** shows ✅ checks for all steps

### Option 2: Laravel Tinker (Most Thorough)

1. **Open terminal**
2. **Run Tinker**:
   ```bash
   cd c:\xampp\htdocs\DATN\DATN\chill-drink
   php artisan tinker
   ```
3. **Execute test**:
   ```
   > include 'tests/BranchStatisticsE2ETest.php'
   > $test = new Tests\BranchStatisticsE2ETest()
   > $result = $test->runFullTest()
   ```
4. **Review output** for all ✅ checks

### Option 3: Manual Verification (Best for Demo)

1. **Follow the Step-by-Step Guide**: `BRANCH_STATS_E2E_TEST_GUIDE.md`
2. **Check Dashboard** against expected values
3. **Test Checkout** dropdown behavior
4. **Verify Database** with provided queries

---

## What Gets Tested

### ✅ Branch Management
- Branch CRUD operations
- Status toggle (active/inactive)
- Branch filtering in queries

### ✅ Checkout System
- delivery_type field support (pickup vs delivery)
- branch_id saving for pickup orders
- Shipping fee = 0 for pickup
- Shipping fee calculated for delivery
- Branch dropdown shows only active branches
- Validation rules working

### ✅ Order System
- Orders created with delivery_type and branch_id
- Payment status and order status tracking
- Revenue only counted for paid/completed orders
- Order items related correctly

### ✅ Statistics Backend
- 8 Branch Summary Cards calculations
- 4 Branch Insight Cards calculations
- Branch Revenue Chart data
- Branch Order Chart data
- Branch Ranking Stats data

### ✅ Dashboard UI
- All 4 sections render (cards, insights, charts, table)
- Numbers display correctly
- Vietnamese currency formatting
- Number formatting with thousand separators
- Empty states work
- Charts display correctly

### ✅ Data Integrity
- Revenue calculation correct (only paid/completed)
- Order counts accurate
- Branch status filtering works
- Inactive branches excluded from checkout

---

## Expected Test Results

### Database Level
```
✅ 3 branches: Tây Hồ (active), Ba Đình (inactive), Hoàn Kiếm (active)
✅ 2 orders: Both pickup, branch_id set, shipping_fee=0
✅ Both orders: status='completed', payment_status='paid'
✅ Total revenue: 100,000đ
✅ Tây Hồ revenue: 50,000đ
✅ Hoàn Kiếm revenue: 50,000đ
```

### Dashboard Level
```
✅ 8 Summary Cards: All totals show correct numbers
✅ 4 Insight Cards: Top branches identified correctly
✅ Revenue Chart: Shows both active branches
✅ Order Chart: Shows correct order counts
✅ Ranking Table: Sorted by revenue, formatted correctly
```

### Checkout Level
```
✅ Branch dropdown: Shows Tây Hồ, Hoàn Kiếm (not Ba Đình)
✅ Shipping fee: 0đ when pickup, [amount]đ when delivery
✅ Validation: branch_id required for pickup
```

---

## Troubleshooting

### Issue: SQL Script Errors
**Solution:**
1. Verify database tables exist
2. Check MySQL version compatibility
3. Review error message and adjust schema if needed

### Issue: Dashboard Not Showing Data
**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
```
Then refresh browser (Ctrl+F5).

### Issue: Revenue Shows 0
**Solution:**
Check that orders have `payment_status='paid'` OR `status='completed'`. Pending orders don't count toward revenue.

### Issue: Ba Đình Appears in Checkout
**Solution:**
Verify `branches.status = 0` for Ba Đình:
```sql
SELECT status FROM branches WHERE code = 'BA001';
```

### Issue: Chart Data Not Showing
**Solution:**
1. Verify orders exist: `SELECT COUNT(*) FROM orders WHERE branch_id IS NOT NULL;`
2. Verify at least one order is paid/completed
3. Hard refresh browser (Ctrl+Shift+Delete cache)

---

## Verification Order

1. **Run SQL Test** (2 min)
   - Confirms database layer works
   - Creates test data
   - Verifies statistics calculations

2. **Check Dashboard** (2 min)
   - Confirms UI renders correctly
   - Verifies numbers display
   - Confirms formatting

3. **Test Checkout** (1 min)
   - Confirms branch dropdown works
   - Verifies shipping fee logic
   - Tests validation

**Total Time: ~5 minutes**

---

## Files Reference

| File | Purpose | Run How |
|------|---------|---------|
| `tests/branch_stats_sql_test.sql` | Database verification | phpMyAdmin or MySQL CLI |
| `tests/BranchStatisticsE2ETest.php` | Application layer test | `php artisan tinker` |
| `BRANCH_STATS_E2E_TEST_GUIDE.md` | Detailed procedures | Read and follow steps |
| `BRANCH_STATS_TEST_CHECKLIST.md` | Quick checklist | Use as verification sheet |

---

## Success Criteria

✅ **All of these must be true:**

- [ ] SQL test completes without errors
- [ ] 3 branches created (status values correct)
- [ ] 2 orders created with branch_id
- [ ] Revenue appears on dashboard
- [ ] Charts display both branches
- [ ] Ranking table shows correct order
- [ ] Checkout dropdown excludes inactive branch
- [ ] Shipping fee = 0 for pickup
- [ ] All numbers format correctly (đ, commas)
- [ ] No JavaScript console errors

**If all TRUE: Feature is PRODUCTION READY ✅**

---

## Commands Quick Reference

```bash
# Start Tinker
php artisan tinker

# Exit Tinker
exit

# Clear cache
php artisan cache:clear
php artisan config:clear

# View logs
tail -f storage/logs/laravel.log

# Run tests
php artisan test
```

---

## Support

If you encounter issues:

1. **Check troubleshooting section above**
2. **Verify database tables exist**
3. **Check Laravel logs**: `storage/logs/laravel.log`
4. **Review expected outputs** for each step

---

## Next Steps After Testing

✅ **After all tests pass:**
1. Feature is ready for demo
2. Feature is ready for production deployment
3. Branch management system is operational
4. Dashboard statistics are accurate and real-time

---

**Last Updated:** 2024
**Status:** ✅ READY FOR TESTING
**Estimated Time:** ~5 minutes to verify
