<?php
require __DIR__ . '/../includes/config.php';
$pdo = get_db();

echo "Checking and adding focus columns...\n";
$tables = ['news', 'documents'];
foreach ($tables as $t) {
    echo "Table: $t\n";
    $cols = [];
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM $t");
        foreach ($stmt as $c) $cols[] = $c['Field'];
    } catch (Exception $e) {
        echo "  Error reading columns: " . $e->getMessage() . "\n";
        continue;
    }

    if (!in_array('image_position', $cols)) {
        echo "  Adding column image_position...\n";
        $pdo->exec("ALTER TABLE `$t` ADD COLUMN `image_position` VARCHAR(32) DEFAULT 'center'");
    } else {
        echo "  image_position exists\n";
    }
    if (!in_array('image_focus_x', $cols)) {
        echo "  Adding column image_focus_x...\n";
        $pdo->exec("ALTER TABLE `$t` ADD COLUMN `image_focus_x` FLOAT DEFAULT 50");
    } else {
        echo "  image_focus_x exists\n";
    }
    if (!in_array('image_focus_y', $cols)) {
        echo "  Adding column image_focus_y...\n";
        $pdo->exec("ALTER TABLE `$t` ADD COLUMN `image_focus_y` FLOAT DEFAULT 50");
    } else {
        echo "  image_focus_y exists\n";
    }

    // set defaults for existing rows where NULL
    echo "  Normalizing existing rows...\n";
    $pdo->exec("UPDATE `$t` SET image_position = COALESCE(image_position, 'center') WHERE image_position IS NULL");
    $pdo->exec("UPDATE `$t` SET image_focus_x = COALESCE(image_focus_x, 50) WHERE image_focus_x IS NULL");
    $pdo->exec("UPDATE `$t` SET image_focus_y = COALESCE(image_focus_y, 50) WHERE image_focus_y IS NULL");
}

echo "Done.\n";
