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

<header class="mb-6">
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg p-6">
        <h1 class="text-2xl font-bold">ข่าวประชาสัมพันธ์</h1>
        <p class="mt-1 text-sm">ติดตามข่าวสาร กิจกรรม และประกาศสำคัญของโครงการ</p>
    </div>
</header>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($news as $n): ?>
        <article class="bg-white rounded-lg shadow hover:shadow-lg transition overflow-hidden flex flex-col">
                        <?php if (!empty($n['image'])): ?>
                                <div class="h-40 w-full bg-gray-100 overflow-hidden">
                                        <?php
                                            $objPos = '';
                                            if (!empty($n['image_focus_x']) || !empty($n['image_focus_y'])) {
                                                $x = $n['image_focus_x'] ?? 50;
                                                $y = $n['image_focus_y'] ?? 50;
                                                $objPos = $x . '% ' . $y . '%';
                                            } elseif (!empty($n['image_position'])) {
                                                $objPos = $n['image_position'];
                                            }
                                        ?>
                                        <img src="<?php echo e($n['image']); ?>" alt="<?php echo e($n['title']); ?>" class="w-full h-full object-cover" style="<?php echo $objPos ? 'object-position: ' . htmlspecialchars($objPos) . ';' : ''; ?>">
                                </div>
            <?php endif; ?>
            <div class="p-5 flex-1 flex flex-col">
                <div class="flex items-start justify-between">
                    <h2 class="font-semibold text-lg text-slate-800"><?php echo e($n['title']); ?></h2>
                    <div class="text-xs text-gray-500"><?php echo e(format_datetime($n['date_posted'])); ?></div>
                </div>
                <div class="mt-2 text-sm text-gray-600 flex-1">
                    <?php echo e(mb_substr($n['content'], 0, 220)); ?>...
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-xs text-gray-500"><?php echo e($n['category']); ?></div>
                    <a href="news-detail.php?id=<?php echo $n['id']; ?>" class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">อ่านต่อ</a>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
