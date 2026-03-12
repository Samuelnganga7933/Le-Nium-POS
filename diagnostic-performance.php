<?php
/**
 * Performance Diagnostic Tool
 * Identifies slow queries and missing indexes
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== PERFORMANCE DIAGNOSTIC REPORT ===\n\n";

// Check for missing indexes on foreign keys
echo "1. CHECKING FOREIGN KEY INDEXES:\n";
echo str_repeat("-", 60) . "\n";

$tables = ['sales', 'products', 'users', 'sale_items', 'clients', 'shops'];
$indexesToAdd = [];

foreach ($tables as $table) {
    echo "\nTable: $table\n";
    
    $columns = Schema::getColumns($table);
    $indexes = DB::select("SHOW INDEXES FROM {$table}");
    $indexedColumns = array_map(fn($idx) => $idx->Column_name, $indexes);
    
    // Check for foreign key columns
    foreach ($columns as $col) {
        if (strpos($col['name'], '_id') !== false && $col['name'] !== 'id') {
            $hasIndex = in_array($col['name'], $indexedColumns);
            $status = $hasIndex ? '✓ INDEXED' : '✗ MISSING INDEX';
            echo "  - {$col['name']}: $status\n";
            
            if (!$hasIndex) {
                $indexesToAdd[] = [
                    'table' => $table,
                    'column' => $col['name']
                ];
            }
        }
    }
}

// Check for indexes on filtering columns
echo "\n\n2. CHECKING FILTERING COLUMN INDEXES:\n";
echo str_repeat("-", 60) . "\n";

$filteringColumns = [
    'sales' => ['status', 'created_at', 'shop_id', 'company_id'],
    'products' => ['is_active', 'stock', 'shop_id'],
    'users' => ['role', 'company_id', 'shop_id'],
    'clients' => ['status'],
];

foreach ($filteringColumns as $table => $cols) {
    echo "\nTable: $table\n";
    $indexes = DB::select("SHOW INDEXES FROM {$table}");
    $indexedColumns = array_map(fn($idx) => $idx->Column_name, $indexes);
    
    foreach ($cols as $col) {
        if (!Schema::hasColumn($table, $col)) {
            echo "  - $col: ✗ COLUMN DOESN'T EXIST\n";
            continue;
        }
        
        $hasIndex = in_array($col, $indexedColumns);
        $status = $hasIndex ? '✓ INDEXED' : '✗ MISSING INDEX';
        echo "  - $col: $status\n";
        
        if (!$hasIndex && strpos($table, 'id') === false) {
            $indexesToAdd[] = [
                'table' => $table,
                'column' => $col
            ];
        }
    }
}

// Summary
echo "\n\n3. SUMMARY:\n";
echo str_repeat("-", 60) . "\n";

if (empty($indexesToAdd)) {
    echo "✓ All critical indexes are present!\n";
} else {
    echo "✗ Missing " . count($indexesToAdd) . " indexes:\n\n";
    foreach ($indexesToAdd as $idx) {
        echo "ALTER TABLE {$idx['table']} ADD INDEX idx_{$idx['table']}_{$idx['column']} ({$idx['column']});\n";
    }
}

echo "\n\n4. TABLE SIZES:\n";
echo str_repeat("-", 60) . "\n";

$tableStats = DB::select("
    SELECT 
        TABLE_NAME,
        ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) as size_mb,
        TABLE_ROWS as row_count
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
    ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC
    LIMIT 20
");

foreach ($tableStats as $stat) {
    echo sprintf("%-30s %10s MB  %12s rows\n", 
        $stat->TABLE_NAME, 
        $stat->size_mb,
        $stat->row_count
    );
}

echo "\nDone!\n";
