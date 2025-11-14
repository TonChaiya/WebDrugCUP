<?php
require __DIR__ . '/../includes/config.php';
$pdo = get_db();

echo "Checking 'drive_folder_id' column on news table...\n";
$cols = [];
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM news");
    foreach ($stmt as $c) $cols[] = $c['Field'];
} catch (Exception $e) {
    echo "Failed to read columns: " . $e->getMessage() . "\n";
    exit(1);
}
if (!in_array('drive_folder_id', $cols)) {
    echo "Adding column drive_folder_id...\n";
    $pdo->exec("ALTER TABLE `news` ADD COLUMN `drive_folder_id` VARCHAR(255) DEFAULT NULL");
    echo "Added.\n";
} else {
    echo "Column already exists.\n";
}
