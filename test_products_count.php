<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel');

$count = App\Models\Product::where('is_active', true)->count();
echo "Active products: " . $count . "\n";

// Get first 5 product names
$products = App\Models\Product::where('is_active', true)->limit(5)->get();
foreach ($products as $p) {
    echo "- " . $p->name . "\n";
}
