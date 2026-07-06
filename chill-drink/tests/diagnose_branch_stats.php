<?php

/**
 * Branch Statistics Diagnostic Script
 * 
 * Quickly diagnose if all components are in place and working
 * 
 * Run in Laravel Tinker:
 * > include 'tests/diagnose_branch_stats.php'
 * 
 * Or run directly:
 * php tests/diagnose_branch_stats.php
 */

use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class DiagnoseBranchStats
{
    public function run()
    {
        echo "\n=== BRANCH STATISTICS DIAGNOSTIC ===\n\n";
        
        $results = [];
        
        // 1. Database Tables
        echo "[CHECK 1] Database Tables\n";
        $results['branches_table'] = Schema::hasTable('branches');
        $results['orders_table'] = Schema::hasTable('orders');
        $results['products_table'] = Schema::hasTable('products');
        $results['users_table'] = Schema::hasTable('users');
        
        echo "  - branches table: " . ($results['branches_table'] ? "✅" : "❌") . "\n";
        echo "  - orders table: " . ($results['orders_table'] ? "✅" : "❌") . "\n";
        echo "  - products table: " . ($results['products_table'] ? "✅" : "❌") . "\n";
        echo "  - users table: " . ($results['users_table'] ? "✅" : "❌") . "\n";
        
        // 2. Order Table Columns
        echo "\n[CHECK 2] Order Table Columns\n";
        $results['order_delivery_type'] = Schema::hasColumn('orders', 'delivery_type');
        $results['order_branch_id'] = Schema::hasColumn('orders', 'branch_id');
        $results['order_payment_status'] = Schema::hasColumn('orders', 'payment_status');
        $results['order_status'] = Schema::hasColumn('orders', 'status');
        $results['order_shipping_fee'] = Schema::hasColumn('orders', 'shipping_fee');
        
        echo "  - delivery_type column: " . ($results['order_delivery_type'] ? "✅" : "❌") . "\n";
        echo "  - branch_id column: " . ($results['order_branch_id'] ? "✅" : "❌") . "\n";
        echo "  - payment_status column: " . ($results['order_payment_status'] ? "✅" : "❌") . "\n";
        echo "  - status column: " . ($results['order_status'] ? "✅" : "❌") . "\n";
        echo "  - shipping_fee column: " . ($results['order_shipping_fee'] ? "✅" : "❌") . "\n";
        
        // 3. Models
        echo "\n[CHECK 3] Model Relationships\n";
        try {
            $branch = Branch::first();
            $results['branch_model'] = $branch !== null;
            if ($branch) {
                echo "  - Branch model exists: ✅\n";
                $results['branch_users_relation'] = method_exists($branch, 'users');
                $results['branch_orders_relation'] = method_exists($branch, 'orders');
                echo "    - users() relationship: " . ($results['branch_users_relation'] ? "✅" : "❌") . "\n";
                echo "    - orders() relationship: " . ($results['branch_orders_relation'] ? "✅" : "❌") . "\n";
            } else {
                echo "  - Branch model exists: ✅ (but no branches created yet)\n";
            }
        } catch (Exception $e) {
            echo "  - Branch model exists: ❌ ({$e->getMessage()})\n";
            $results['branch_model'] = false;
        }
        
        try {
            $order = Order::first();
            $results['order_model'] = true;
            echo "  - Order model exists: ✅\n";
            if ($order) {
                $results['order_branch_relation'] = method_exists($order, 'branch');
                echo "    - branch() relationship: " . ($results['order_branch_relation'] ? "✅" : "❌") . "\n";
            }
        } catch (Exception $e) {
            echo "  - Order model exists: ❌ ({$e->getMessage()})\n";
            $results['order_model'] = false;
        }
        
        // 4. Model Fillable Fields
        echo "\n[CHECK 4] Model Fillable Fields\n";
        try {
            $order = new Order();
            $fillable = $order->getFillable();
            $results['order_fillable_delivery_type'] = in_array('delivery_type', $fillable);
            $results['order_fillable_branch_id'] = in_array('branch_id', $fillable);
            
            echo "  - Order fillable includes 'delivery_type': " . ($results['order_fillable_delivery_type'] ? "✅" : "❌") . "\n";
            echo "  - Order fillable includes 'branch_id': " . ($results['order_fillable_branch_id'] ? "✅" : "❌") . "\n";
        } catch (Exception $e) {
            echo "  - Error checking fillable: ❌\n";
        }
        
        // 5. SuperAdminController Methods
        echo "\n[CHECK 5] SuperAdminController Methods\n";
        try {
            $controller = app('App\Http\Controllers\Admin\SuperAdminController');
            $results['superadmin_branchSummaryStats'] = method_exists($controller, 'branchSummaryStats');
            $results['superadmin_branchInsightStats'] = method_exists($controller, 'branchInsightStats');
            $results['superadmin_branchRevenueChart'] = method_exists($controller, 'branchRevenueChart');
            $results['superadmin_branchOrderChart'] = method_exists($controller, 'branchOrderChart');
            $results['superadmin_branchRankingStats'] = method_exists($controller, 'branchRankingStats');
            
            echo "  - branchSummaryStats() method: " . ($results['superadmin_branchSummaryStats'] ? "✅" : "❌") . "\n";
            echo "  - branchInsightStats() method: " . ($results['superadmin_branchInsightStats'] ? "✅" : "❌") . "\n";
            echo "  - branchRevenueChart() method: " . ($results['superadmin_branchRevenueChart'] ? "✅" : "❌") . "\n";
            echo "  - branchOrderChart() method: " . ($results['superadmin_branchOrderChart'] ? "✅" : "❌") . "\n";
            echo "  - branchRankingStats() method: " . ($results['superadmin_branchRankingStats'] ? "✅" : "❌") . "\n";
        } catch (Exception $e) {
            echo "  - Error loading controller: ❌ ({$e->getMessage()})\n";
        }
        
        // 6. BranchController
        echo "\n[CHECK 6] BranchController\n";
        try {
            $controllerPath = app_path('Http/Controllers/Admin/BranchController.php');
            $results['branchcontroller_exists'] = file_exists($controllerPath);
            echo "  - BranchController exists: " . ($results['branchcontroller_exists'] ? "✅" : "❌") . "\n";
        } catch (Exception $e) {
            echo "  - Error checking BranchController: ❌\n";
        }
        
        // 7. Routes
        echo "\n[CHECK 7] Branch Routes\n";
        $routes = collect(app('router')->getRoutes())->map(fn($route) => $route->getName())->filter();
        $results['routes_branches_index'] = $routes->contains('admin.branches.index');
        $results['routes_branches_store'] = $routes->contains('admin.branches.store');
        $results['routes_branches_edit'] = $routes->contains('admin.branches.edit');
        $results['routes_branches_update'] = $routes->contains('admin.branches.update');
        $results['routes_branches_destroy'] = $routes->contains('admin.branches.destroy');
        $results['routes_branches_toggle'] = $routes->contains('admin.branches.toggle-status');
        
        echo "  - admin.branches.index: " . ($results['routes_branches_index'] ? "✅" : "❌") . "\n";
        echo "  - admin.branches.store: " . ($results['routes_branches_store'] ? "✅" : "❌") . "\n";
        echo "  - admin.branches.edit: " . ($results['routes_branches_edit'] ? "✅" : "❌") . "\n";
        echo "  - admin.branches.update: " . ($results['routes_branches_update'] ? "✅" : "❌") . "\n";
        echo "  - admin.branches.destroy: " . ($results['routes_branches_destroy'] ? "✅" : "❌") . "\n";
        echo "  - admin.branches.toggle-status: " . ($results['routes_branches_toggle'] ? "✅" : "❌") . "\n";
        
        // 8. Data Statistics
        echo "\n[CHECK 8] Data Statistics\n";
        try {
            $branchCount = Branch::count();
            $orderCount = Order::count();
            $ordersWithBranchId = Order::whereNotNull('branch_id')->count();
            $totalRevenue = Order::where(function($q) {
                $q->where('payment_status', 'paid')->orWhere('status', 'completed');
            })->whereNotNull('branch_id')->sum('total');
            
            echo "  - Total branches: {$branchCount}\n";
            echo "  - Total orders: {$orderCount}\n";
            echo "  - Orders with branch_id: {$ordersWithBranchId}\n";
            echo "  - Total branch revenue (paid/completed): " . number_format($totalRevenue) . "đ\n";
            
            $results['has_branches'] = $branchCount > 0;
            $results['has_orders'] = $ordersWithBranchId > 0;
            $results['has_revenue'] = $totalRevenue > 0;
        } catch (Exception $e) {
            echo "  - Error fetching data: ❌ ({$e->getMessage()})\n";
        }
        
        // 9. Views
        echo "\n[CHECK 9] View Files\n";
        $results['view_super_admin'] = file_exists(resource_path('views/admin/super-admin.blade.php'));
        $results['view_checkout'] = file_exists(resource_path('views/client/checkout/index.blade.php'));
        $results['view_branches_index'] = file_exists(resource_path('views/admin/branches/index.blade.php'));
        $results['view_branches_edit'] = file_exists(resource_path('views/admin/branches/edit.blade.php'));
        
        echo "  - super-admin.blade.php: " . ($results['view_super_admin'] ? "✅" : "❌") . "\n";
        echo "  - checkout/index.blade.php: " . ($results['view_checkout'] ? "✅" : "❌") . "\n";
        echo "  - branches/index.blade.php: " . ($results['view_branches_index'] ? "✅" : "❌") . "\n";
        echo "  - branches/edit.blade.php: " . ($results['view_branches_edit'] ? "✅" : "❌") . "\n";
        
        // Summary
        echo "\n=== DIAGNOSTIC SUMMARY ===\n\n";
        
        $passed = count(array_filter($results, fn($v) => $v === true));
        $failed = count(array_filter($results, fn($v) => $v === false));
        $total = count($results);
        
        echo "Passed: {$passed}/{$total}\n";
        echo "Failed: {$failed}/{$total}\n\n";
        
        if ($failed === 0) {
            echo "✅ ALL CHECKS PASSED - System is ready!\n\n";
        } else {
            echo "❌ {$failed} CHECKS FAILED - See details above\n\n";
            echo "Failed checks:\n";
            foreach ($results as $check => $result) {
                if ($result === false) {
                    echo "  - {$check}\n";
                }
            }
        }
        
        echo "\n=== DIAGNOSTIC COMPLETE ===\n";
        
        return $results;
    }
}

// Run the diagnostic
$diagnostic = new DiagnoseBranchStats();
$results = $diagnostic->run();

// Return results for Tinker
return $results;
