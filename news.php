<?php
require __DIR__ . '/includes/config.php';
$pdo = get_db();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare('SELECT * FROM news ORDER BY date_posted DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'ข่าวประชาสัมพันธ์';
include __DIR__ . '/includes/header.php';
?>

<h1 class="text-xl font-bold mb-4">ข่าวประชาสัมพันธ์</h1>
<div class="grid grid-cols-1 gap-4">
    <?php foreach ($news as $n): ?>
        <article class="bg-white rounded shadow p-4">
            <h2 class="font-semibold text-blue-600"><?php echo e($n['title']); ?></h2>
            <div class="text-xs text-gray-500"><?php echo e(format_datetime($n['date_posted'])); ?> — <?php echo e($n['category']); ?></div>
            <p class="mt-2 text-sm text-gray-700"><?php echo e(mb_substr($n['content'], 0, 250)); ?>...</p>
            <a href="news-detail.php?id=<?php echo $n['id']; ?>" class="inline-block mt-3 text-sm text-blue-600">อ่านต่อ</a>
        </article>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
