<?php
// Start fresh
$output = "Test started\n";

try {
    require 'vendor/autoload.php';
    $output .= "Autoload OK\n";
    
    $app = require_once 'bootstrap/app.php';
    $output .= "App OK\n";
    
    $app->make('Illuminate\Contracts\Http\Kernel');
    $output .= "Kernel OK\n";
    
    $count = \App\Models\Product::where('is_active', true)->count();
    $output .= "Active products: $count\n";
    
} catch (Exception $e) {
    $output .= "Error: " . $e->getMessage() . "\n";
}

file_put_contents('test_output.txt', $output);
echo "Output written to test_output.txt\n";
