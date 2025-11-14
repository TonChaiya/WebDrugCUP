<?php
require __DIR__ . '/includes/config.php';
$pdo = get_db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: news.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?');
$stmt->execute([$id]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$news) {
    header('Location: news.php');
    exit;
}

$pageTitle = e($news['title']);
include __DIR__ . '/includes/header.php';
?>

<article class="bg-white rounded shadow p-6">
    <h1 class="text-2xl font-bold mb-2"><?php echo e($news['title']); ?></h1>
    <div class="text-xs text-gray-500 mb-4"><?php echo e(format_datetime($news['date_posted'])); ?> — <?php echo e($news['category']); ?></div>
    <div class="prose max-w-none text-gray-800"><?php echo nl2br(e($news['content'])); ?></div>
</article>

<?php include __DIR__ . '/includes/drive-list.php'; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
