<?php
require __DIR__ .'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "\n=== DATABASE TEST ===\n\n";

try {
    // Test connection
    echo "Testing connection to: " . env('DB_HOST') . "/" . env('DB_DATABASE') . "\n";
    DB::connection()->getPdo();
    echo "✅ Connection successful\n\n";
    
    // Get all tables
    $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ?", [env('DB_DATABASE')]);
    echo "Total tables: " . count($tables) . "\n";
    
    if (count($tables) > 0) {
        echo "\nTables found:\n";
        foreach ($tables as $t) {
            echo "  - " . $t->TABLE_NAME . "\n";
        }
    } else {
        echo "\n⚠️  NO TABLES FOUND!\n";
        echo "Database exists but is empty\n";
    }
    
    // Check migrations table
    if (Schema::hasTable('migrations')) {
        $migrationCount = DB::table('migrations')->count();
        echo "\n✅ Migrations table exists with $migrationCount entries\n";
    } else {
        echo "\n❌ Migrations table doesn't exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    die();
}
?>
