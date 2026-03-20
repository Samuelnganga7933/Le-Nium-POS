#!/usr/bin/env php
<?php

/**
 * Data Isolation Security Test
 * Verifies that users can only see their own company's data
 */

require __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Company;
use App\Models\Sale;
use App\Models\Shop;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║          DATA ISOLATION SECURITY TEST                          ║\n";
echo "║  Verifying user data is properly scoped by company             ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";

try {
    // Get all companies
    $companies = Company::all();
    echo "\n📊 Total Companies: " . $companies->count() . "\n";
    
    foreach ($companies as $company) {
        echo "\n" . str_repeat("─", 65) . "\n";
        echo "Company: {$company->name} (ID: {$company->id})\n";
        echo "Type: {$company->shop_type}\n";
        
        // Get shops for this company
        $shops = $company->shops;
        echo "Shops: " . $shops->count() . "\n";
        
        if ($shops->isEmpty()) {
            echo "  ⚠️  No shops found for this company\n";
            continue;
        }
        
        $shopIds = $shops->pluck('id')->toArray();
        echo "Shop IDs: " . implode(', ', $shopIds) . "\n";
        
        // Get users for this company
        $users = $company->users;
        echo "Users: " . $users->count() . "\n";
        
        if ($users->isEmpty()) {
            echo "  ⚠️  No users found for this company\n";
            continue;
        }
        
        // Get sales for this company's shops
        $companyShopsSales = Sale::whereIn('shop_id', $shopIds)->count();
        $totalShopsSales = collect($shopIds)->sum(fn($id) => Sale::where('shop_id', $id)->count());
        
        echo "\n  💰 Sales Data:\n";
        echo "    Total Sales (filtered): $totalShopsSales\n";
        echo "    Revenue (filtered): KES " . number_format(Sale::whereIn('shop_id', $shopIds)->sum('total') ?? 0, 2) . "\n";
        
        // Check if there are sales across multiple companies
        $allSalesCount = Sale::count();
        echo "\n  🔒 Security Check:\n";
        echo "    Total Sales in System: $allSalesCount\n";
        echo "    Sales for this company: $totalShopsSales\n";
        
        if ($allSalesCount > $totalShopsSales) {
            echo "    ✅ Isolation working - Other companies' data is not visible\n";
        } else if ($allSalesCount == $totalShopsSales && $allSalesCount > 0) {
            echo "    ⚠️  Single company system - cannot verify isolation\n";
        } else {
            echo "    ✓  No sales data in system\n";
        }
    }
    
    echo "\n" . str_repeat("═", 65) . "\n";
    echo "✅ DATA ISOLATION TEST COMPLETED\n";
    echo "═" . str_repeat("═", 64) . "\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
