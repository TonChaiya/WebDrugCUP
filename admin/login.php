<?php
session_start();
require __DIR__ . '/../includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = $_POST['username'] ?? '';
  $pass = $_POST['password'] ?? '';
  try {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$user]);
    $row = $stmt->fetch();
    if ($row && password_verify($pass, $row['password_hash'])) {
      // mark session as logged in and store admin id
      $_SESSION['admin_logged'] = true;
      $_SESSION['admin_id'] = $row['id'];
      header('Location: dashboard.php'); exit;
    } else {
      $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
  } catch (Exception $e) {
    $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
  }
}

?><!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Login</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>body{background:#f3f4f6}</style>
</head>
<body class="flex items-center justify-center h-screen">
  <div class="w-full max-w-md bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4">เข้าสู่ระบบผู้ดูแล</h1>
    <?php if ($error): ?><div class="text-red-600 mb-3"><?php echo e($error); ?></div><?php endif; ?>
    <form method="post">
      <label class="block mb-2">ผู้ใช้
        <input name="username" class="w-full border px-2 py-1 rounded" required>
      </label>
      <label class="block mb-2">รหัสผ่าน
        <input name="password" type="password" class="w-full border px-2 py-1 rounded" required>
      </label>
      <div class="flex justify-between items-center mt-4">
        <button class="bg-blue-600 text-white px-4 py-2 rounded">เข้าสู่ระบบ</button>
        <a href="/" class="text-sm text-gray-600">กลับหน้าแรก</a>
      </div>
    </form>
  </div>
</body>
</html>
