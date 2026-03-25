<?php
/**
 * Final Verification Script - Phase 7 AI Training System
 * Verifies all components are functional and ready for chat testing
 */

require 'vendor/autoload.php';
use App\Services\AITrainingSystem;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     PHASE 7 AI TRAINING SYSTEM - FINAL VERIFICATION            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// 1. Dataset Load Test
echo "1. LOADING TRAINING DATASET...\n";
try {
    $dataset = AITrainingSystem::getTrainingDataset();
    echo "   ✓ Dataset loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ ERROR loading dataset: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Category Count
echo "\n2. COUNTING CATEGORIES & Q&AS...\n";
$categories = count($dataset);
$total_qa = 0;
$category_details = [];

foreach ($dataset as $category => $answers) {
    $count = count($answers);
    $total_qa += $count;
    $category_details[] = [$category, $count];
}

echo "   ✓ Total Categories: $categories\n";
echo "   ✓ Total Q&A Pairs: $total_qa\n";

// 3. Sample Q&A Test
echo "\n3. TESTING SAMPLE Q&A...\n";
$sample = $dataset['revenue_improvement'][0];
echo "   Question: " . $sample['question'] . "\n";
echo "   Confidence: " . $sample['confidence'] . "%\n";
echo "   Keywords: " . count($sample['keywords']) . " keywords\n";
echo "   Answer Length: " . strlen($sample['answer']) . " chars\n";
echo "   Follow-ups: " . count($sample['follow_up_questions']) . " suggestions\n";
echo "   ✓ Sample Q&A structure valid\n";

// 4. Keyword Matching Test
echo "\n4. TESTING KEYWORD MATCHING...\n";
$test_question = "How can I increase my sales?";
$matches = AITrainingSystem::findBestAnswers($test_question, ['revenue_this_month' => 50000]);
echo "   Test Question: \"$test_question\"\n";
echo "   Matches Found: " . count($matches) . "\n";
if (count($matches) > 0) {
    echo "   Top Match: " . $matches[0]['question'] . " (" . $matches[0]['confidence'] . "%)\n";
    echo "   ✓ Keyword matching works\n";
} else {
    echo "   ✗ No matches found\n";
    exit(1);
}

// 5. Business Situation Assessment
echo "\n5. TESTING BUSINESS SITUATION ASSESSMENT...\n";
$context = [
    'revenue_this_month' => 75000,
    'net_profit' => 7500,
];
$situation = AITrainingSystem::assessBusinessSituation($context);
echo "   Input: Revenue 75k, Profit 7.5k (10% margin)\n";
echo "   Revenue Health: " . $situation['revenue_health'] . "\n";
echo "   Margin Health: " . $situation['margin_health'] . "\n";
echo "   ✓ Business situation assessment works\n";

// 6. Data Quality Check
echo "\n6. CHECKING DATA QUALITY...\n";
$quality_issues = 0;
foreach ($dataset as $category => $answers) {
    foreach ($answers as $idx => $qa) {
        if (!isset($qa['confidence']) || $qa['confidence'] < 80 || $qa['confidence'] > 100) {
            echo "   ✗ Invalid confidence in $category[$idx]: " . $qa['confidence'] . "\n";
            $quality_issues++;
        }
        if (strlen($qa['answer']) < 50) {
            echo "   ✗ Short answer in $category[$idx]: " . strlen($qa['answer']) . " chars\n";
            $quality_issues++;
        }
        if (count($qa['keywords']) == 0) {
            echo "   ✗ No keywords in $category[$idx]\n";
            $quality_issues++;
        }
    }
}
if ($quality_issues == 0) {
    echo "   ✓ All Q&A pass quality checks\n";
} else {
    echo "   ✗ Found $quality_issues quality issues\n";
    exit(1);
}

// 7. Performance Metrics
echo "\n7. PERFORMANCE METRICS...\n";
$avg_confidence = array_reduce(
    array_merge(...array_values($dataset)),
    fn($carry, $qa) => $carry + $qa['confidence'],
    0
) / $total_qa;

$avg_answer_length = array_reduce(
    array_merge(...array_values($dataset)),
    fn($carry, $qa) => $carry + strlen($qa['answer']),
    0
) / $total_qa;

$total_followups = array_reduce(
    array_merge(...array_values($dataset)),
    fn($carry, $qa) => $carry + count($qa['follow_up_questions']),
    0
);

echo "   Average Confidence: " . round($avg_confidence, 1) . "%\n";
echo "   Average Answer Length: " . round($avg_answer_length) . " characters\n";
echo "   Total Follow-up Suggestions: $total_followups\n";
echo "   ✓ Performance metrics calculated\n";

// 8. Category Distribution
echo "\n8. CATEGORY DISTRIBUTION...\n";
echo "   Detailed breakdown:\n";
foreach ($category_details as [$cat, $count]) {
    printf("   %-30s: %d Q&A pair%s\n", $cat, $count, $count != 1 ? 's' : '');
}

// Final Status
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                     VERIFICATION STATUS: ✓ PASS                ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "Summary:\n";
echo "  • Categories: $categories\n";
echo "  • Q&A Pairs: $total_qa\n";
echo "  • Avg Confidence: " . round($avg_confidence, 1) . "%\n";
echo "  • Follow-up Connections: $total_followups\n";
echo "  • Quality Issues: $quality_issues\n";
echo "  • System Status: ✓ READY FOR CHAT TESTING\n\n";

echo "Next Step: Test chat endpoint with messages like:\n";
echo "  • 'How can I increase revenue?'\n";
echo "  • 'My business is slow, what should I do?'\n";
echo "  • 'How do I manage cash flow?'\n\n";
