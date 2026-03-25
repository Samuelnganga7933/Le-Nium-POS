<?php
/**
 * Test semantic AI matching
 * Verifies that the new intelligent system works without exact keyword matching
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Services\AITrainingSystem;

// Create a minimal context for testing
$context = [
    'revenue_this_month' => 50000,
    'net_profit' => 15000,
    'total_customers' => 150,
    'business_situation' => 'moderate_revenue',
];

echo "========================================\n";
echo "🧪 SEMANTIC AI MATCHING TEST\n";
echo "========================================\n\n";

$testQuestions = [
    "How do I earn more?" => ["revenue", "increase", "sales"],
    "I'm stuck" => ["problem", "challenge", "issue"],
    "Make customers come back" => ["customer", "loyal", "repeat"],
    "What's my margin?" => ["profit", "percentage", "return"],
    "How can I improve my business?" => ["optimization", "improvement", "growth"],
    "I need more money" => ["revenue", "income", "cash"],
];

foreach ($testQuestions as $question => $expectedTopics) {
    echo "🔍 Testing: \"$question\"\n";
    echo "   Expected topics: " . implode(", ", $expectedTopics) . "\n";
    
    try {
        $answers = AITrainingSystem::findBestAnswers($question, $context);
        
        if (!empty($answers)) {
            echo "   ✅ Found " . count($answers) . " relevant answer(s):\n";
            foreach ($answers as $idx => $answer) {
                echo "      " . ($idx + 1) . ". {$answer['question']} [score: " . 
                     round($answer['match_score'], 1) . "]\n";
            }
        } else {
            echo "   ❌ No answers found!\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "========================================\n";
echo "✅ Test Complete!\n";
echo "========================================\n";
echo "\nKey Observations:\n";
echo "• Questions without exact keywords should still match relevant answers\n";
echo "• Synonym matching should work (e.g., 'earn' matches 'revenue')\n";
echo "• Intent detection should understand user goals\n";
echo "• Multiple answers returned (top 3) for better selection\n";
?>
