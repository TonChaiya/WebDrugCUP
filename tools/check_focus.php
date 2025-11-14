<?php
require __DIR__ . '/../includes/config.php';
$pdo = get_db();

echo "-- COLUMNS IN news TABLE --\n";
foreach ($pdo->query("SHOW COLUMNS FROM news") as $col) {
    echo $col['Field'] . "\t" . $col['Type'] . "\n";
}

echo "\n-- LATEST 20 news ROWS (id, title, image, image_position, image_focus_x, image_focus_y) --\n";
$stmt = $pdo->query('SELECT id, title, image, image_position, image_focus_x, image_focus_y FROM news ORDER BY id DESC LIMIT 20');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
}
