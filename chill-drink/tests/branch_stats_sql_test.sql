-- ============================================================================
-- BRANCH STATISTICS END-TO-END TEST - SQL VERIFICATION SCRIPT
-- ============================================================================
-- Run these queries step-by-step to verify all branch statistics components
-- Copy and paste into phpMyAdmin or your SQL client
-- ============================================================================

-- STEP 1: Create/Verify 3 Branches
-- ============================================================================
PRINT 'STEP 1: Creating/Verifying 3 branches...';

-- Delete and recreate for clean test (optional)
-- DELETE FROM branches WHERE code IN ('TH001', 'BA001', 'HK001');

INSERT IGNORE INTO branches (name, code, email, phone, address, status, created_at, updated_at)
VALUES 
  ('Tây Hồ', 'TH001', 'tayho@branch.local', '0243456789', 'Tây Hồ, Hà Nội', 1, NOW(), NOW()),
  ('Ba Đình', 'BA001', 'bading@branch.local', '0243456789', 'Ba Đình, Hà Nội', 0, NOW(), NOW()),
  ('Hoàn Kiếm', 'HK001', 'hoankiem@branch.local', '0243456789', 'Hoàn Kiếm, Hà Nội', 1, NOW(), NOW());

SELECT 'Active Branches:' as status;
SELECT id, name, code, status FROM branches WHERE code IN ('TH001', 'BA001', 'HK001') ORDER BY id;

SELECT '' as '';
SELECT 'Branch IDs for later use:' as note;
SELECT 
  (SELECT id FROM branches WHERE code = 'TH001') as tayho_id,
  (SELECT id FROM branches WHERE code = 'BA001') as bading_id,
  (SELECT id FROM branches WHERE code = 'HK001') as hoankiem_id;


-- STEP 2: Verify Test Customer and Product
-- ============================================================================
PRINT '';
PRINT 'STEP 2: Creating/Verifying test customer and product...';

-- Ensure test customer exists
INSERT IGNORE INTO users (name, email, phone, address, area, password, role_id, is_active, created_at, updated_at)
VALUES ('Test E2E Customer', 'test-e2e@example.com', '0987654321', 'Test Addr', 'Test Area', 
        MD5('password'), 1, 1, NOW(), NOW());

SELECT 'Test Customer:' as status;
SELECT id, name, email FROM users WHERE email = 'test-e2e@example.com' LIMIT 1;

-- Ensure test category exists
INSERT IGNORE INTO categories (name, description, status, created_at, updated_at)
VALUES ('E2E Test Category', 'For E2E Testing', 1, NOW(), NOW());

-- Ensure test product exists
INSERT IGNORE INTO products (category_id, name, code, description, price, status, created_at, updated_at)
SELECT 
  (SELECT id FROM categories WHERE name = 'E2E Test Category' LIMIT 1),
  'E2E Test Product',
  'E2E-TEST-PRODUCT',
  'Test product for E2E',
  50000,
  1,
  NOW(),
  NOW()
WHERE NOT EXISTS (SELECT 1 FROM products WHERE code = 'E2E-TEST-PRODUCT');

SELECT 'Test Product:' as status;
SELECT id, name, code, price FROM products WHERE code = 'E2E-TEST-PRODUCT' LIMIT 1;


-- STEP 3-4: Create Order 1 (Pickup at Tây Hồ, Initially Pending)
-- ============================================================================
PRINT '';
PRINT 'STEP 3-4: Creating pickup order for Tây Hồ (Initially Pending)...';

-- Get IDs
SET @tayho_id = (SELECT id FROM branches WHERE code = 'TH001');
SET @customer_id = (SELECT id FROM users WHERE email = 'test-e2e@example.com');
SET @product_id = (SELECT id FROM products WHERE code = 'E2E-TEST-PRODUCT');

-- Create Order 1
INSERT INTO orders (
  user_id, delivery_type, branch_id, payment_method, 
  payment_status, status, subtotal, shipping_fee, discount, total, total_price, 
  note, created_at, updated_at
) VALUES (
  @customer_id, 'pickup', @tayho_id, 'cod',
  'pending', 'pending', 50000, 0, 0, 50000, 50000,
  'E2E Test Order 1', NOW(), NOW()
);

SET @order1_id = LAST_INSERT_ID();

-- Create Order Item
INSERT INTO order_items (order_id, product_id, quantity, price, total_price, created_at, updated_at)
VALUES (@order1_id, @product_id, 1, 50000, 50000, NOW(), NOW());

SELECT 'Order 1 Created (Pending):' as status;
SELECT 
  id, user_id, delivery_type, branch_id, shipping_fee, 
  total, payment_status, status 
FROM orders WHERE id = @order1_id;


-- STEP 5: Verify Order Statistics Before Completion
-- ============================================================================
PRINT '';
PRINT 'STEP 5: Verifying order statistics BEFORE completion...';

SELECT 'Order Count by Branch (Before Completion):' as status;
SELECT 
  b.name as branch_name,
  COUNT(o.id) as order_count
FROM branches b
LEFT JOIN orders o ON b.id = o.branch_id AND o.branch_id IS NOT NULL
WHERE b.code IN ('TH001', 'BA001', 'HK001')
GROUP BY b.id, b.name
ORDER BY b.id;

SELECT 'Total Orders with branch_id:' as status;
SELECT COUNT(*) as total_orders FROM orders WHERE branch_id IS NOT NULL;


-- STEP 6: Mark Order 1 as Completed and Paid
-- ============================================================================
PRINT '';
PRINT 'STEP 6: Marking Order 1 as completed and paid...';

UPDATE orders SET 
  status = 'completed', 
  payment_status = 'paid',
  updated_at = NOW()
WHERE id = @order1_id;

SELECT 'Order 1 Updated (Completed & Paid):' as status;
SELECT 
  id, status, payment_status, total 
FROM orders WHERE id = @order1_id;


-- STEP 7: Verify Revenue Statistics After Completion
-- ============================================================================
PRINT '';
PRINT 'STEP 7: Verifying revenue statistics AFTER completion...';

SELECT 'Revenue by Branch (Paid/Completed Only):' as status;
SELECT 
  b.name as branch_name,
  COUNT(DISTINCT o.id) as order_count,
  SUM(o.total) as total_revenue
FROM branches b
LEFT JOIN orders o ON b.id = o.branch_id 
  AND o.branch_id IS NOT NULL
  AND (o.payment_status = 'paid' OR o.status = 'completed')
WHERE b.code IN ('TH001', 'BA001', 'HK001')
GROUP BY b.id, b.name
ORDER BY total_revenue DESC;

SELECT 'Total Branch Revenue (Paid/Completed):' as status;
SELECT 
  COUNT(*) as total_orders,
  SUM(total) as total_revenue
FROM orders 
WHERE branch_id IS NOT NULL AND (payment_status = 'paid' OR status = 'completed');


-- STEP 8: Create Order 2 (Pickup at Hoàn Kiếm, Completed & Paid)
-- ============================================================================
PRINT '';
PRINT 'STEP 8: Creating completed pickup order for Hoàn Kiếm...';

SET @hoankiem_id = (SELECT id FROM branches WHERE code = 'HK001');

INSERT INTO orders (
  user_id, delivery_type, branch_id, payment_method, 
  payment_status, status, subtotal, shipping_fee, discount, total, total_price, 
  note, created_at, updated_at
) VALUES (
  @customer_id, 'pickup', @hoankiem_id, 'cod',
  'paid', 'completed', 50000, 0, 0, 50000, 50000,
  'E2E Test Order 2', NOW(), NOW()
);

SET @order2_id = LAST_INSERT_ID();

INSERT INTO order_items (order_id, product_id, quantity, price, total_price, created_at, updated_at)
VALUES (@order2_id, @product_id, 1, 50000, 50000, NOW(), NOW());

SELECT 'Order 2 Created (Completed & Paid):' as status;
SELECT 
  id, user_id, delivery_type, branch_id, shipping_fee, 
  total, payment_status, status 
FROM orders WHERE id = @order2_id;


-- STEP 9: Verify Final Branch Comparison
-- ============================================================================
PRINT '';
PRINT 'STEP 9: Verifying final branch comparison...';

SELECT 'Final Ranking - All Metrics:' as status;
SELECT 
  b.id,
  b.name as branch_name,
  b.status as active,
  COUNT(DISTINCT CASE WHEN o.branch_id IS NOT NULL THEN o.id END) as total_orders,
  COUNT(DISTINCT CASE WHEN o.status = 'completed' THEN o.id END) as completed_orders,
  COUNT(DISTINCT CASE WHEN o.status = 'cancelled' THEN o.id END) as cancelled_orders,
  SUM(CASE WHEN o.payment_status = 'paid' OR o.status = 'completed' THEN o.total ELSE 0 END) as revenue,
  ROUND(COALESCE(SUM(CASE WHEN o.payment_status = 'paid' OR o.status = 'completed' THEN o.total ELSE 0 END) / 
    NULLIF(COUNT(DISTINCT CASE WHEN o.branch_id IS NOT NULL THEN o.id END), 0), 0)) as avg_order_value
FROM branches b
LEFT JOIN orders o ON b.id = o.branch_id
WHERE b.code IN ('TH001', 'BA001', 'HK001')
GROUP BY b.id, b.name, b.status
ORDER BY revenue DESC;


-- STEP 10: Verify Checkout Branch Dropdown Logic
-- ============================================================================
PRINT '';
PRINT 'STEP 10: Verifying checkout branch dropdown logic...';

SELECT 'Active Branches (Should appear in checkout dropdown):' as status;
SELECT id, name, code, status FROM branches WHERE status = 1 AND code IN ('TH001', 'BA001', 'HK001');

SELECT 'Inactive Branches (Should NOT appear in checkout dropdown):' as status;
SELECT id, name, code, status FROM branches WHERE status = 0 AND code IN ('TH001', 'BA001', 'HK001');


-- FINAL VERIFICATION SUMMARY
-- ============================================================================
PRINT '';
PRINT 'FINAL VERIFICATION SUMMARY:';

SELECT 'Test Result Summary:' as status;
SELECT 
  'Order 1' as test,
  CONCAT('delivery_type=pickup: ', 
    IF((SELECT delivery_type FROM orders WHERE id = @order1_id) = 'pickup', '✅', '❌')) as result
UNION ALL SELECT 'Order 1' as test,
  CONCAT('branch_id=Tây Hồ: ',
    IF((SELECT branch_id FROM orders WHERE id = @order1_id) = @tayho_id, '✅', '❌'))
UNION ALL SELECT 'Order 1' as test,
  CONCAT('shipping_fee=0: ',
    IF((SELECT shipping_fee FROM orders WHERE id = @order1_id) = 0, '✅', '❌'))
UNION ALL SELECT 'Order 1' as test,
  CONCAT('status=completed: ',
    IF((SELECT status FROM orders WHERE id = @order1_id) = 'completed', '✅', '❌'))
UNION ALL SELECT 'Order 1' as test,
  CONCAT('payment_status=paid: ',
    IF((SELECT payment_status FROM orders WHERE id = @order1_id) = 'paid', '✅', '❌'))
UNION ALL SELECT 'Order 2' as test,
  CONCAT('branch_id=Hoàn Kiếm: ',
    IF((SELECT branch_id FROM orders WHERE id = @order2_id) = @hoankiem_id, '✅', '❌'))
UNION ALL SELECT 'Branches' as test,
  CONCAT('Ba Đình inactive: ',
    IF((SELECT status FROM branches WHERE code = 'BA001') = 0, '✅', '❌'))
UNION ALL SELECT 'Statistics' as test,
  CONCAT('Total revenue > 0: ',
    IF((SELECT SUM(total) FROM orders WHERE branch_id IS NOT NULL AND (payment_status = 'paid' OR status = 'completed')) > 0, '✅', '❌'));

-- ============================================================================
-- FINAL ORDER AND STATISTICS
-- ============================================================================
PRINT '';
PRINT 'All Orders (for reference):';
SELECT id, user_id, delivery_type, branch_id, total, payment_status, status FROM orders WHERE branch_id IS NOT NULL ORDER BY id DESC;

PRINT '';
PRINT 'All Branches (for reference):';
SELECT id, name, code, status FROM branches WHERE code IN ('TH001', 'BA001', 'HK001');

PRINT '';
PRINT '=== TEST COMPLETE ===';
