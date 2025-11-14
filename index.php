<?php
require __DIR__ . '/includes/config.php';
$pdo = get_db();

// fetch latest news (6)
$newsStmt = $pdo->prepare('SELECT * FROM news ORDER BY date_posted DESC LIMIT 6');
$newsStmt->execute();
$latestNews = $newsStmt->fetchAll(PDO::FETCH_ASSOC);

// fetch latest documents (5)
$docStmt = $pdo->prepare('SELECT * FROM documents ORDER BY uploaded_at DESC LIMIT 5');
$docStmt->execute();
$latestDocs = $docStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'หน้าแรก - ข่าวและดาวน์โหลด';
include __DIR__ . '/includes/header.php';
?>

<section class="mb-8">
    <div class="bg-blue-600 text-white rounded-lg p-6">
        <h1 class="text-2xl font-bold">ข่าวประชาสัมพันธ์</h1>
        <p class="mt-2">รวมข่าวสารและกิจกรรมล่าสุดของโครงการ</p>
    </div>
</section>

<section class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <h2 class="text-lg font-semibold mb-4">ข่าวล่าสุด</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($latestNews as $n): ?>
                <article class="bg-white rounded shadow overflow-hidden hover:shadow-lg transition">
                    <?php if (!empty($n['image'])): ?>
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
                        <div class="h-40 w-full bg-gray-100 overflow-hidden">
                            <img src="<?php echo e($n['image']); ?>" alt="<?php echo e($n['title']); ?>" class="w-full h-full object-cover" style="<?php echo $objPos ? 'object-position: ' . htmlspecialchars($objPos) . ';' : ''; ?>">
                        </div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-semibold text-blue-600"><?php echo e($n['title']); ?></h3>
                        <div class="text-xs text-gray-500"><?php echo e(format_datetime($n['date_posted'])); ?> — <?php echo e($n['category']); ?></div>
                        <p class="mt-2 text-sm text-gray-700"><?php echo e(mb_substr($n['content'], 0, 120)); ?>...</p>
                        <a href="news-detail.php?id=<?php echo $n['id']; ?>" class="inline-block mt-3 text-sm text-blue-600">อ่านต่อ</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="mt-4">
            <a href="news.php" class="text-sm text-blue-600">ดูข่าวทั้งหมด</a>
        </div>
    </div>

    <aside>
        <h2 class="text-lg font-semibold mb-4">ดาวน์โหลดล่าสุด</h2>
        <div class="space-y-3">
            <?php foreach ($latestDocs as $d): ?>
                <div class="bg-white rounded shadow p-3">
                    <div class="font-medium"><?php echo e($d['title']); ?></div>
                    <div class="text-xs text-gray-500"><?php echo e($d['category']); ?> — <?php echo e(format_datetime($d['uploaded_at'])); ?></div>
                    <div class="mt-2 text-sm text-gray-700"><?php echo e(mb_substr($d['description'], 0, 100)); ?>...</div>
                    <a href="<?php echo e($d['drive_url']); ?>" target="_blank" class="mt-2 inline-block text-sm text-blue-600">ดาวน์โหลด</a>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4">
            <a href="downloads.php" class="text-sm text-blue-600">ศูนย์ดาวน์โหลดทั้งหมด</a>
        </div>
    </aside>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
