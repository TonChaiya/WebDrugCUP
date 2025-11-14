<?php
require __DIR__ . '/../includes/config.php';
if ($argc < 3) {
    echo "Usage: php create_admin.php username password [role]\n";
    exit(1);
}
$username = $argv[1];
$password = $argv[2];
$role = $argv[3] ?? 'admin';
$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo = get_db();
try {
    // check if role column exists
    $hasRoleCol = false;
    try { $c = $pdo->prepare("SHOW COLUMNS FROM admins LIKE 'role'"); $c->execute(); $hasRoleCol = (bool)$c->fetch(); } catch (Exception $e) { $hasRoleCol = false; }
    if ($hasRoleCol) {
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)');
        $stmt->execute([$username, $hash, $role]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $stmt->execute([$username, $hash]);
    }
    echo "Created admin user '{$username}'\n";
} catch (Exception $e) {
    echo "Failed to create admin: " . $e->getMessage() . "\n";
}
