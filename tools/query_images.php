<?php
require __DIR__ . '/../includes/config.php';
$pdo = get_db();
$stmt = $pdo->query('SELECT id, image FROM news LIMIT 20');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

// diagnostic info for uploads
echo "UPLOADS_DIR=" . UPLOADS_DIR . PHP_EOL;
echo "UPLOADS_URL=" . UPLOADS_URL . PHP_EOL;
foreach ($rows as $r) {
	if (!empty($r['image'])) {
		$path = realpath(__DIR__ . '/..') . str_replace('/', DIRECTORY_SEPARATOR, $r['image']);
		echo "record id={$r['id']} image_field={$r['image']}\n";
		echo "computed path: $path\n";
		echo "is_file: " . (is_file($path) ? 'yes' : 'no') . "\n";
	}
}
