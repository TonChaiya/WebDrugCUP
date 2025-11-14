<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $pdo = get_db();
    // attempt to remove local cover image if present
    try {
        $stmt = $pdo->prepare('SELECT image FROM documents WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['image'])) {
            $img = $row['image'];
            $uploadsUrl = rtrim(UPLOADS_URL, '/');
            if (strpos($img, $uploadsUrl) === 0) {
                $rel = substr($img, strlen($uploadsUrl));
                $filePath = rtrim(UPLOADS_DIR, DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                $realBase = realpath(UPLOADS_DIR);
                $realFileDir = realpath(dirname($filePath));
                if ($realBase && $realFileDir && strpos($realFileDir, $realBase) === 0 && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
        }
    } catch (Exception $e) {
        // ignore unlink errors
    }

    $pdo->prepare('DELETE FROM documents WHERE id = ?')->execute([$id]);
}
header('Location: dashboard.php'); exit;
