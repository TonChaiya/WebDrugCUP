<?php
session_start();
require __DIR__ . '/../includes/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user = $_POST['username'] ?? '';
  $pass = $_POST['password'] ?? '';
  try {
    $pdo = get_db();
    // detect if admins table has a 'role' column
    $hasRole = false;
    try {
      $col = $pdo->prepare("SHOW COLUMNS FROM admins LIKE 'role'"); $col->execute(); $hasRole = (bool)$col->fetch();
    } catch (Exception $e) { $hasRole = false; }

    if ($hasRole) {
      $stmt = $pdo->prepare('SELECT id, password_hash, role FROM admins WHERE username = ? LIMIT 1');
    } else {
      $stmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = ? LIMIT 1');
    }
    $stmt->execute([$user]);
    $row = $stmt->fetch();
    if ($row && password_verify($pass, $row['password_hash'])) {
      // mark session as logged in and store admin id
      $_SESSION['admin_logged'] = true;
      $_SESSION['admin_id'] = $row['id'];
      // store role if present, default to 'admin'
      $_SESSION['admin_role'] = isset($row['role']) ? $row['role'] : 'admin';
      // redirect to site index after login
      header('Location: ' . BASE_URL); exit;
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
  <style>body{background:linear-gradient(180deg,#f8fafc, #eef2f7)}</style>
</head>
<body class="min-h-screen flex items-center justify-center py-12">
  <div class="w-full max-w-4xl mx-auto px-4">
    <div class="bg-white shadow-xl rounded-lg overflow-hidden grid grid-cols-1 md:grid-cols-2">
      <!-- Left branding / illustration -->
      <div class="hidden md:flex items-center justify-center bg-gradient-to-br from-blue-600 to-indigo-600 p-8">
        <div class="text-center text-white max-w-sm">
          <div class="inline-flex items-center justify-center h-16 w-16 rounded-full overflow-hidden bg-white/20 mb-4">
            <img src="/assets/images/logo.png" alt="Logo" class="h-full w-full object-cover">
          </div>

          <h2 class="text-2xl font-semibold mb-2">ระบบจัดการข่าว & ดาวน์โหลด</h2>
          <p class="text-sm opacity-90">เข้าสู่ระบบเพื่อจัดการข่าวประชาสัมพันธ์, เอกสาร และการอัปโหลดไฟล์ไปยัง Google Drive</p>
          <div class="mt-6 text-xs bg-white/10 rounded px-3 py-2 inline-block">ปลอดภัย · ใช้ OAuth / Service Account</div>
        </div>
      </div>

      <!-- Right: form -->
      <div class="p-8">
        <div class="mb-6">
          <h1 class="text-2xl font-semibold">เข้าสู่ระบบผู้ดูแล</h1>
          <p class="text-sm text-gray-500">จัดการข่าวประชาสัมพันธ์ และเอกสารดาวน์โหลด</p>
        </div>

        <?php if ($error): ?>
          <div class="mb-4 rounded border border-red-200 bg-red-50 text-red-700 px-4 py-3">
            <?php echo e($error); ?>
          </div>
        <?php endif; ?>

        <form method="post" class="space-y-4" autocomplete="on">
          <div>
            <label class="block text-sm font-medium text-gray-700">ผู้ใช้</label>
            <input name="username" id="username" autofocus class="mt-1 block w-full rounded-md border-gray-200 shadow-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300" required>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">รหัสผ่าน</label>
            <div class="relative">
              <input name="password" id="password" type="password" class="mt-1 block w-full rounded-md border-gray-200 shadow-sm px-3 py-2 pr-10 focus:outline-none focus:ring-2 focus:ring-blue-300" required>
              <button type="button" id="toggle-pass" class="absolute right-2 top-1/2 -translate-y-1/2 text-sm text-gray-500">แสดง</button>
            </div>
          </div>

          <div class="flex items-center justify-between">
            <label class="inline-flex items-center text-sm"><input type="checkbox" name="remember" class="mr-2">จำเครื่องนี้</label>
            <a href="/" class="text-sm text-gray-500 hover:underline">กลับหน้าแรก</a>
          </div>

          <div>
            <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-md">เข้าสู่ระบบ</button>
          </div>
        </form>

        <div class="mt-6 text-xs text-gray-500">ระบบ admin ใช้ session เพื่อยืนยันตัวตน — ห้ามเผยแพร่รหัสผ่าน</div>
      </div>
    </div>
  </div>

  <script>
    (function(){
      var t = document.getElementById('toggle-pass');
      var p = document.getElementById('password');
      if (!t || !p) return;
      t.addEventListener('click', function(){
        if (p.type === 'password') { p.type = 'text'; t.textContent = 'ซ่อน'; } else { p.type = 'password'; t.textContent = 'แสดง'; }
      });
    })();
  </script>
</body>
</html>
