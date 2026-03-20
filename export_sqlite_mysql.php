<?php

// Improved script to export SQLite database to MySQL-compatible SQL dump

$dbPath = __DIR__ . '/database/database.sqlite';

if (!file_exists($dbPath)) {
    die("Database file not found: $dbPath\n");
}

try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get all table names
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);

    $sql = "-- MySQL dump from SQLite\n\n";

    foreach ($tables as $table) {
        // Get CREATE TABLE statement
        $createStmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();

        // Convert SQLite syntax to MySQL
        $createStmt = str_replace('"', '`', $createStmt); // Replace double quotes with backticks
        $createStmt = preg_replace('/integer primary key autoincrement/i', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY', $createStmt);
        $createStmt = preg_replace('/primary key \(([^)]+)\)/i', 'PRIMARY KEY ($1)', $createStmt);
        // strip stray 'not null' following a primary key
        $createStmt = preg_replace('/PRIMARY KEY\s+not null/i', 'PRIMARY KEY', $createStmt);
        
        // Add length for varchar types
        // replace "varchar" or "varchar not null" with varchar(255) or varchar(255) NOT NULL
        $createStmt = preg_replace('/varchar(\s+not\s+null)?/i', 'varchar(255)$1', $createStmt);

        // remove any remaining SQLite-specific checks or defaults not supported
        $createStmt = preg_replace('/\s+check\s*\([^)]+\)/i', '', $createStmt);

        $sql .= $createStmt . ";\n\n";

        // Get data
        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $columns = array_keys($rows[0]);
            $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";

            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = $pdo->quote($value);
                    }
                }
                $values[] = '(' . implode(', ', $rowValues) . ')';
            }
            $sql .= implode(",\n", $values) . ";\n\n";
        }
    }

    // Save to file
    file_put_contents(__DIR__ . '/database_dump.sql', $sql);
    echo "Database exported to database_dump.sql\n";

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
?>