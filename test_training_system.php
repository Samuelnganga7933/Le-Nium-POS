<?php
require 'vendor/autoload.php';
use App\Services\AITrainingSystem;

$data = AITrainingSystem::getTrainingDataset();

echo "=== AI Training System Status ===\n";
echo "Total Categories: " . count($data) . "\n\n";

$total = 0;
foreach ($data as $category => $answers) {
    echo $category . ": " . count($answers) . " Q&A pairs\n";
    $total += count($answers);
}

echo "\n=== TOTAL: " . $total . " Q&A PAIRS ===\n";

echo "\nSample Q&A:\n";
$first = $data['revenue_improvement'][0];
echo "Question: " . $first['question'] . "\n";
echo "Confidence: " . $first['confidence'] . "%\n";
echo "Answer preview: " . substr($first['answer'], 0, 100) . "...\n";

echo "\n✓ Training system loaded successfully!\n";
