<?php
$file = 'app/Http/Controllers/AIChatController.php';
$content = file_get_contents($file);

// Count braces
$open = substr_count($content, '{');
$close = substr_count($content, '}');

echo "File: $file\n";
echo "Open braces: $open\n";
echo "Close braces: $close\n";
echo "Diff: " . ($open - $close) . "\n";

// Try to find the line where balance is lost
$lines = file($file);
$balance = 0;
$line_balance = [];

foreach ($lines as $num => $line) {
    $open_in_line = substr_count($line, '{');
    $close_in_line = substr_count($line, '}');
    $balance += $open_in_line - $close_in_line;
    $line_balance[$num + 1] = $balance;

    if ($balance < 0) {
        echo "Balance goes negative at line " . ($num + 1) . ": $balance\n";
        echo "Content: " . trim($line) . "\n";
        break;
    }
}

// Find last positive balance line before ending
$last_positive = 0;
foreach ($line_balance as $line_num => $bal) {
    if ($bal > 0) {
        $last_positive = $line_num;
    }
}

echo "\nLast line with positive balance: $last_positive\n";
echo "Content around that line:\n";
for ($i = max(1, $last_positive - 2); $i <= min(count($lines), $last_positive + 3); $i++) {
    echo "$i: " . $lines[$i - 1];
}
?>
