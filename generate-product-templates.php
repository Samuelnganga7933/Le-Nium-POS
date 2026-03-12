<?php

// Simple script to generate 100+ placeholder product images
// Place in: php generate-product-templates.php

$outputDir = __DIR__ . '/public/img/products';
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

// Define helper functions first
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return ['r' => $r, 'g' => $g, 'b' => $b];
}

function imagettextcolor($rgb) {
    // Calculate brightness
    $brightness = ($rgb['r'] * 299 + $rgb['g'] * 587 + $rgb['b'] * 114) / 1000;
    return $brightness > 128; // True = use dark text, False = use white text
}

// Template categories/colors for variety
$templates = [
    // Beverage colors
    ['name' => 'cola', 'color' => '#8B3A00', 'emoji' => '🥤'],
    ['name' => 'sprite', 'color' => '#92D050', 'emoji' => '🥤'],
    ['name' => 'fanta-orange', 'color' => '#FF6600', 'emoji' => '🧡'],
    ['name' => 'fanta-grape', 'color' => '#8B008B', 'emoji' => '🍇'],
    ['name' => 'fanta-strawberry', 'color' => '#FF1493', 'emoji' => '🍓'],
    
    // Dairy
    ['name' => 'milk', 'color' => '#FFFACD', 'emoji' => '🥛'],
    ['name' => 'yogurt', 'color' => '#F5DEB3', 'emoji' => '🍶'],
    ['name' => 'cheese', 'color' => '#FFD700', 'emoji' => '🧀'],
    
    // Bakery
    ['name' => 'bread', 'color' => '#D2691E', 'emoji' => '🍞'],
    ['name' => 'pastry', 'color' => '#DEB887', 'emoji' => '🥐'],
    ['name' => 'cake', 'color' => '#CD853F', 'emoji' => '🎂'],
    
    // Snacks
    ['name' => 'biscuit', 'color' => '#8B4513', 'emoji' => '🍪'],
    ['name' => 'chips', 'color' => '#DC143C', 'emoji' => '🍟'],
    ['name' => 'candy', 'color' => '#FF69B4', 'emoji' => '🍭'],
    
    // Fruits
    ['name' => 'apple', 'color' => '#DC143C', 'emoji' => '🍎'],
    ['name' => 'banana', 'color' => '#FFD700', 'emoji' => '🍌'],
    ['name' => 'orange', 'color' => '#FF8C00', 'emoji' => '🍊'],
    ['name' => 'grape', 'color' => '#8B008B', 'emoji' => '🍇'],
    
    // Vegetables
    ['name' => 'carrot', 'color' => '#FF7F00', 'emoji' => '🥕'],
    ['name' => 'lettuce', 'color' => '#228B22', 'emoji' => '🥬'],
    
    // Meats
    ['name' => 'chicken', 'color' => '#CD853F', 'emoji' => '🍗'],
    ['name' => 'beef', 'color' => '#8B0000', 'emoji' => '🥩'],
    ['name' => 'fish', 'color' => '#4169E1', 'emoji' => '🐟'],
    
    // Condiments
    ['name' => 'oil', 'color' => '#FFD700', 'emoji' => '🫒'],
    ['name' => 'sauce', 'color' => '#8B0000', 'emoji' => '🍅'],
    ['name' => 'salt', 'color' => '#FFFFFF', 'emoji' => '🧂'],
    ['name' => 'sugar', 'color' => '#FFFACD', 'emoji' => '🍬'],
    
    // Kitchen items
    ['name' => 'utensil', 'color' => '#C0C0C0', 'emoji' => '🍴'],
    ['name' => 'plate', 'color' => '#F0F8FF', 'emoji' => '🍽️'],
    ['name' => 'cup', 'color' => '#FFE4B5', 'emoji' => '☕'],
    
    // Electronics
    ['name' => 'phone', 'color' => '#000000', 'emoji' => '📱'],
    ['name' => 'laptop', 'color' => '#A9A9A9', 'emoji' => '💻'],
    ['name' => 'tablet', 'color' => '#696969', 'emoji' => '📱'],
    
    // Clothing
    ['name' => 'shirt', 'color' => '#FF0000', 'emoji' => '👕'],
    ['name' => 'pants', 'color' => '#000080', 'emoji' => '👖'],
    ['name' => 'hat', 'color' => '#8B4513', 'emoji' => '🧢'],
    
    // Shoes
    ['name' => 'shoes', 'color' => '#8B4513', 'emoji' => '👟'],
    ['name' => 'sandal', 'color' => '#FF8C00', 'emoji' => '👡'],
    
    // Beauty
    ['name' => 'soap', 'color' => '#FFB6C1', 'emoji' => '🧼'],
    ['name' => 'shampoo', 'color' => '#48D1CC', 'emoji' => '🧴'],
    ['name' => 'perfume', 'color' => '#DDA0DD', 'emoji' => '💐'],
];

// Generate variations to reach 100+
$count = 1;
$generatedFiles = [];

foreach ($templates as $template) {
    // Create base + 3 variations of each (different shades)
    $shades = [0, 0.15, -0.15, 0.30, -0.30];
    
    foreach ($shades as $index => $shadeVariation) {
        if ($count > 100) break 2;
        
        $filename = sprintf('product-%03d.jpg', $count);
        $filepath = $outputDir . '/' . $filename;
        
        // Generate image using GD
        $img = imagecreatetruecolor(200, 200);
        
        // Parse color and apply shade variation
        $rgb = hexToRgb($template['color']);
        $r = max(0, min(255, $rgb['r'] + ($shadeVariation * 255)));
        $g = max(0, min(255, $rgb['g'] + ($shadeVariation * 255)));
        $b = max(0, min(255, $rgb['b'] + ($shadeVariation * 255)));
        
        $color = imagecolorallocate($img, (int)$r, (int)$g, (int)$b);
        $white = imagecolorallocate($img, 255, 255, 255);
        $gray = imagecolorallocate($img, 200, 200, 200);
        
        // Fill background
        imagefilledrectangle($img, 0, 0, 200, 200, $color);
        
        // Add border
        imagerectangle($img, 5, 5, 195, 195, $white);
        
        // Add text label
        $textColor = imagettextcolor($rgb) ? $white : imagecolorallocate($img, 0, 0, 0);
        $label = ucfirst($template['name']);
        if ($index > 0) {
            $label .= ' v' . $index;
        }
        
        // Simple text - use imagestring (built-in font)
        $textColor = imagettextcolor($rgb) ? imagecolorallocate($img, 0, 0, 0) : $white;
        imagestring($img, 2, 20, 90, $label, $textColor);
        imagestring($img, 1, 20, 110, 'Product Template', $textColor);
        
        // Save image
        imagejpeg($img, $filepath, 85);
        imagedestroy($img);
        
        $generatedFiles[] = $filename;
        $count++;
    }
}

echo "✅ Generated " . count($generatedFiles) . " product template images\n";
echo "📁 Location: public/img/products/\n";
echo "📋 Files: product-001.jpg to product-" . sprintf('%03d', count($generatedFiles)) . ".jpg\n";
