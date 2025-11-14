<?php
require __DIR__ . '/includes/config.php';
$pdo = get_db();

// Pagination: show 20 items per page
$perPage = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// total count
$countStmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM documents');
$countStmt->execute();
$total = (int)$countStmt->fetchColumn();
$totalPages = $total > 0 ? (int)ceil($total / $perPage) : 1;
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;
$stmt = $pdo->prepare('SELECT * FROM documents ORDER BY uploaded_at DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'ศูนย์ดาวน์โหลด';
include __DIR__ . '/includes/header.php';
?>

<header class="mb-6">
    <div class="bg-white rounded-lg p-6 shadow-sm">
        <h1 class="text-2xl font-bold">ศูนย์ดาวน์โหลด</h1>
        <p class="mt-1 text-sm text-gray-600">รวมไฟล์เอกสารแบบฟอร์มและข้อมูลสาธารณะที่สามารถดาวน์โหลดได้</p>
    </div>
</header>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($docs as $d): ?>
        <div class="bg-white rounded-lg shadow hover:shadow-md transition overflow-hidden flex flex-col">
                        <?php if (!empty($d['image'])): ?>
                                <div class="h-40 w-full bg-gray-100 overflow-hidden">
                                        <?php
                                            $objPos = '';
                                            if (!empty($d['image_focus_x']) || !empty($d['image_focus_y'])) {
                                                $x = $d['image_focus_x'] ?? 50;
                                                $y = $d['image_focus_y'] ?? 50;
                                                $objPos = $x . '% ' . $y . '%';
                                            } elseif (!empty($d['image_position'])) {
                                                $objPos = $d['image_position'];
                                            }
                                        ?>
                                        <img src="<?php echo e($d['image']); ?>" alt="<?php echo e($d['title']); ?>" class="w-full h-full object-cover" style="<?php echo $objPos ? 'object-position: ' . htmlspecialchars($objPos) . ';' : ''; ?>">
                                </div>
                        <?php endif; ?>
            <div class="p-5 flex-1 flex flex-col">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1">
                        <div class="font-semibold text-lg text-slate-800"><?php echo e($d['title']); ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?php echo e($d['category']); ?> — <?php echo e(format_datetime($d['uploaded_at'])); ?></div>
                        <div class="mt-3 text-sm text-gray-600"><?php echo e(mb_substr($d['description'], 0, 140)); ?>...</div>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between">
                    <div class="text-xs text-gray-500">เข้าดู: <?php echo e($d['views'] ?? 0); ?></div>
                    <a href="<?php echo e($d['drive_url']); ?>" target="_blank" class="inline-block text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">ดาวน์โหลด</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- pagination -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-6 flex items-center justify-center space-x-2">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">ก่อนหน้า</a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?php echo $p; ?>" class="px-3 py-1 rounded <?php echo $p==$page ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>" class="px-3 py-1 bg-gray-100 rounded hover:bg-gray-200">ถัดไป</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
