<?php
/**
 * Database Migration Fixer
 * Run this from VS Code terminal: php fix-migrations.php
 * This script diagnoses and fixes common migration issues
 */

// Load Laravel bootstrap
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════\n";
echo "🔧 LEUMAS ONE POS - Migration Fixer\n";
echo "═══════════════════════════════════════════\n\n";

// Check current database status
echo "📋 Checking Database Status...\n";
echo "Database: " . env('DB_DATABASE') . "\n";

try {
    $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [env('DB_DATABASE')]);
    echo "Existing tables: " . count($tables) . "\n\n";
    
    // Check for critical tables
    $tableNames = array_map(fn($t) => $t->TABLE_NAME, $tables);
    
    $criticalTables = [
        'users',
        'products',
        'chat_messages',
        'companies',
        'shops',
        'sales',
        'expenses',
        'suppliers',
        'stock_requests'
    ];
    
    echo "Critical Tables Status:\n";
    foreach ($criticalTables as $table) {
        $exists = in_array($table, $tableNames);
        $status = $exists ? "✅ EXISTS" : "❌ MISSING";
        echo "  $table: $status\n";
    }
    
    echo "\nColumn Checks (if tables exist):\n";
    
    // Check if products table has deleted_at
    if (in_array('products', $tableNames)) {
        $hasDeletedAt = Schema::hasColumn('products', 'deleted_at');
        echo "  products.deleted_at: " . ($hasDeletedAt ? "✅ EXISTS" : "❌ MISSING") . "\n";
        
        $hasCompanyId = Schema::hasColumn('products', 'company_id');
        echo "  products.company_id: " . ($hasCompanyId ? "✅ EXISTS" : "❌ MISSING") . "\n";
    }
    
    // Check if chat_messages exists
    if (in_array('chat_messages', $tableNames)) {
        $hasCompanyId = Schema::hasColumn('chat_messages', 'company_id');
        echo "  chat_messages.company_id: " . ($hasCompanyId ? "✅ EXISTS" : "❌ MISSING") . "\n";
    }
    
    // Check migrations table status
    echo "\n📅 Recent Migrations:\n";
    $migrations = DB::select("SELECT migration FROM migrations ORDER BY batch DESC, migration DESC LIMIT 10");
    foreach ($migrations as $m) {
        echo "  ✓ " . $m->migration . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure database '" . env('DB_DATABASE') . "' exists\n";
    echo "2. Check .env file DB_* settings\n";
    echo "3. Ensure MySQL is running\n";
    echo "4. Try: php artisan migrate:fresh\n";
}

echo "\n═══════════════════════════════════════════\n";
echo "✅ Check Complete\n";
echo "═══════════════════════════════════════════\n\n";
