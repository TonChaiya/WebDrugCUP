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

<h1 class="text-xl font-bold mb-4">ศูนย์ดาวน์โหลด</h1>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($docs as $d): ?>
        <div class="bg-white rounded shadow p-4">
            <div class="font-semibold"><?php echo e($d['title']); ?></div>
            <div class="text-xs text-gray-500"><?php echo e($d['category']); ?> — <?php echo e(format_datetime($d['uploaded_at'])); ?></div>
            <p class="mt-2 text-sm text-gray-700"><?php echo e(mb_substr($d['description'], 0, 150)); ?>...</p>
            <div class="mt-3">
                <a href="<?php echo e($d['drive_url']); ?>" target="_blank" class="inline-block text-sm bg-blue-600 text-white px-3 py-1 rounded">ดาวน์โหลด</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- pagination -->
<?php if ($totalPages > 1): ?>
    <nav class="mt-6 flex items-center justify-center space-x-2">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo $page-1; ?>" class="px-3 py-1 bg-gray-200 rounded">Prev</a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?php echo $p; ?>" class="px-3 py-1 rounded <?php echo $p==$page ? 'bg-blue-600 text-white' : 'bg-gray-100'; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo $page+1; ?>" class="px-3 py-1 bg-gray-200 rounded">Next</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
