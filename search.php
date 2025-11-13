<?php
require __DIR__ . '/includes/config.php';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$resultsNews = [];
$resultsDocs = [];
if ($q !== '') {
    $pdo = get_db();
    $like = '%' . str_replace(' ', '%', $q) . '%';
    // news
    $stmt = $pdo->prepare('SELECT * FROM news WHERE title LIKE ? ORDER BY date_posted DESC LIMIT 50');
    $stmt->execute([$like]);
    $resultsNews = $stmt->fetchAll();
    // documents
    $stmt = $pdo->prepare('SELECT * FROM documents WHERE title LIKE ? ORDER BY uploaded_at DESC LIMIT 50');
    $stmt->execute([$like]);
    $resultsDocs = $stmt->fetchAll();
}
$pageTitle = 'ค้นหา: ' . ($q ?: '');
include __DIR__ . '/includes/header.php';
?>

<h1 class="text-xl font-bold mb-4">ผลการค้นหา: <?php echo e($q); ?></h1>

<?php if ($q === ''): ?>
  <p class="text-sm text-gray-600">ป้อนคำค้นในช่องค้นหาด้านบนเพื่อค้นหาข่าวหรือเอกสาร</p>
<?php else: ?>
  <section class="mb-6">
    <h2 class="text-lg font-semibold">ข่าวที่เกี่ยวข้อง (<?php echo count($resultsNews); ?>)</h2>
    <?php if (count($resultsNews) === 0): ?>
      <div class="text-sm text-gray-500 mt-2">ไม่พบข่าวที่ตรงกับคำค้น</div>
    <?php else: ?>
      <div class="mt-2 grid grid-cols-1 gap-3">
        <?php foreach ($resultsNews as $n): ?>
          <div class="bg-white p-3 rounded shadow">
            <div class="font-semibold text-blue-600"><?php echo e($n['title']); ?></div>
            <div class="text-xs text-gray-500"><?php echo e(format_datetime($n['date_posted'])); ?> — <?php echo e($n['category']); ?></div>
            <p class="mt-2 text-sm text-gray-700"><?php echo e(mb_substr($n['content'], 0, 160)); ?>...</p>
            <a href="news-detail.php?id=<?php echo $n['id']; ?>" class="text-sm text-blue-600">อ่านต่อ</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section>
    <h2 class="text-lg font-semibold">เอกสารที่เกี่ยวข้อง (<?php echo count($resultsDocs); ?>)</h2>
    <?php if (count($resultsDocs) === 0): ?>
      <div class="text-sm text-gray-500 mt-2">ไม่พบเอกสารที่ตรงกับคำค้น</div>
    <?php else: ?>
      <div class="mt-2 grid grid-cols-1 gap-3">
        <?php foreach ($resultsDocs as $d): ?>
          <div class="bg-white p-3 rounded shadow">
            <div class="font-semibold"><?php echo e($d['title']); ?></div>
            <div class="text-xs text-gray-500"><?php echo e($d['category']); ?> — <?php echo e(format_datetime($d['uploaded_at'])); ?></div>
            <p class="mt-2 text-sm text-gray-700"><?php echo e(mb_substr($d['description'], 0, 160)); ?>...</p>
            <a href="<?php echo e($d['drive_url']); ?>" target="_blank" class="text-sm text-blue-600">ดาวน์โหลด</a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
