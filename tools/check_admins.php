<?php
require __DIR__ . '/../includes/config.php';
$pdo = get_db();
try {
    $stmt = $pdo->query('SELECT id, username, password_hash, created_at FROM admins');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($rows) . " admin(s)\n";
    foreach ($rows as $r) {
        echo sprintf("- %s (id=%s) created=%s\n", $r['username'], $r['id'], $r['created_at']);
    }
    if (count($rows) > 0) {
        $sample = $rows[0];
        echo "\nSample hash for user {$sample['username']}: {$sample['password_hash']}\n";
        // optionally test password (prompt via argv)
        if (isset($argv[1])) {
            $pw = $argv[1];
            $ok = password_verify($pw, $sample['password_hash']);
            echo "password_verify('{$pw}', hash) => " . ($ok ? 'TRUE' : 'FALSE') . "\n";
        } else {
            echo "Run with a password to test password_verify, e.g. php check_admins.php yourpassword\n";
        }
    }
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}
