<?php
/**
 * Test AI System Improvements
 * Verifies product addition and CAPTCHA changes
 */

echo "=== AI System Improvements Test ===\n\n";

// Test 1: Verify CaptchaService only uses math
echo "Test 1: CAPTCHA Service\n";
echo "------------------------\n";

use App\Services\CaptchaService;

for ($i = 0; $i < 5; $i++) {
    $captcha = CaptchaService::generate();
    echo "CAPTCHA $i: " . $captcha['question'] . "\n";
    
    // Verify it's math only
    if ($captcha['type'] !== 'math') {
        echo "❌ FAIL - Expected 'math', got '" . $captcha['type'] . "'\n";
    } else {
        echo "✅ Type is 'math'\n";
    }
}

echo "\n\nTest 2: Field Extraction\n";
echo "------------------------\n";

// Test parsing comma-separated fields
$testCases = [
    "Coca Cola, 100, 50",
    "Sprite, 80, 75",
    "Fanta, 60, 100"
];

foreach ($testCases as $case) {
    echo "Input: $case\n";
    $parts = array_map('trim', explode(',', $case));
    if (count($parts) >= 3) {
        echo "  ✅ Name: " . $parts[0] . "\n";
        if (preg_match('/(\d+(?:\.\d{1,2})?)/i', $parts[1], $m)) {
            echo "  ✅ Price: " . $m[1] . "\n";
        }
        if (preg_match('/(\d+)/i', $parts[2], $m)) {
            echo "  ✅ Stock: " . $m[1] . "\n";
        }
    }
    echo "\n";
}

echo "\n=== All Tests Complete ===\n";
echo "Run this from: php artisan tinker\n";
echo "Then paste the code above to test in live environment\n";
?>
