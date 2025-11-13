<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
$pdo = get_db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$news = ['title'=>'', 'content'=>'', 'category'=>'', 'image'=>''];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM news WHERE id = ?'); $stmt->execute([$id]); $news = $stmt->fetch() ?: $news;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $category = $_POST['category'] ?? '';
    $image = $_POST['image'] ?? '';
    if ($id) {
        $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, category=? WHERE id=?');
        $stmt->execute([$title, $content, $category, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO news (title, content, image, date_posted, category) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$title, $content, $image, date('Y-m-d H:i:s'), $category]);
    }
    header('Location: dashboard.php'); exit;
}
?>
<!doctype html>
<html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>News Form</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="p-6">
  <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4"><?php echo $id ? 'แก้ไขข่าว' : 'เพิ่มข่าว'; ?></h1>
    <form method="post">
      <label class="block mb-2">หัวข้อ<input name="title" value="<?php echo e($news['title']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">หมวด<input name="category" value="<?php echo e($news['category']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">เนื้อหา<textarea name="content" rows="8" class="w-full border px-2 py-1 rounded"><?php echo e($news['content']); ?></textarea></label>
      <label class="block mb-2">image URL (optional)<input name="image" value="<?php echo e($news['image']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <div class="flex gap-2"><button class="bg-blue-600 text-white px-3 py-1 rounded">บันทึก</button><a href="dashboard.php" class="px-3 py-1">ยกเลิก</a></div>
    </form>
  </div>
</body></html>
