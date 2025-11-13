<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    $pdo = get_db();
    $pdo->prepare('DELETE FROM news WHERE id = ?')->execute([$id]);
}
header('Location: dashboard.php'); exit;
