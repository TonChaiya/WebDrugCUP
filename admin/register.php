<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
$pdo = get_db();
$error = '';
$success = '';
// simple CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$token = $_SESSION['csrf_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $token) {
        $error = 'การส่งแบบฟอร์มไม่ถูกต้อง';
    } else {
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
          $username = trim($_POST['username'] ?? '');
          $password = $_POST['password'] ?? '';
          $role = trim($_POST['role'] ?? 'admin');
            if ($username === '' || $password === '') {
                $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
            } else {
                // ensure username unique
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'ชื่อผู้ใช้นี้มีอยู่แล้ว';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
              // insert with role if column exists
              $hasRoleCol = false;
              try { $c = $pdo->prepare("SHOW COLUMNS FROM admins LIKE 'role'"); $c->execute(); $hasRoleCol = (bool)$c->fetch(); } catch (Exception $e) { $hasRoleCol = false; }
              if ($hasRoleCol) {
                $ins = $pdo->prepare('INSERT INTO admins (username, password_hash, role) VALUES (?, ?, ?)');
                $ins->execute([$username, $hash, $role]);
              } else {
                $ins = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
                $ins->execute([$username, $hash]);
              }
                    $success = 'สร้างผู้ดูแลเรียบร้อย';
                }
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
            $delId = (int)($_POST['id'] ?? 0);
            // cannot delete if only one admin remains
            $cnt = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
            if ($cnt <= 1) {
                $error = 'ไม่สามารถลบผู้ดูแลคนสุดท้ายได้ เพื่อความปลอดภัย';
            } else {
                // optional: prevent deleting own account
                if (!empty($_SESSION['admin_id']) && $delId == (int)$_SESSION['admin_id']) {
                    $error = 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้';
                } else {
                    $del = $pdo->prepare('DELETE FROM admins WHERE id = ?');
                    $del->execute([$delId]);
                    $success = 'ลบผู้ดูแลเรียบร้อย';
                }
            }
        }
    }
}

// fetch admins
$admins = $pdo->query('SELECT id, username, created_at FROM admins ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/../includes/header.php';
?>
<div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
  <h1 class="text-2xl font-semibold mb-4">จัดการผู้ดูแลระบบ</h1>
  <?php if ($error): ?>
    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700"><?php echo e($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700"><?php echo e($success); ?></div>
  <?php endif; ?>

  <div class="mb-6">
    <h2 class="font-medium">สร้างแอดมินใหม่</h2>
    <form method="post" class="mt-3">
      <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
      <input type="hidden" name="action" value="create">
      <label class="block mb-2">ชื่อผู้ใช้<input name="username" class="w-full border px-2 py-1 rounded" required></label>
      <label class="block mb-2">รหัสผ่าน<input name="password" type="password" class="w-full border px-2 py-1 rounded" required></label>
      <?php
        // show role select only if role column exists (but show anyway and server will ignore if no column)
        $showRole = false;
        try { $rc = $pdo->prepare("SHOW COLUMNS FROM admins LIKE 'role'"); $rc->execute(); $showRole = (bool)$rc->fetch(); } catch (Exception $e) { $showRole = false; }
      ?>
      <label class="block mb-2">บทบาท
        <select name="role" class="w-full border px-2 py-1 rounded">
          <option value="admin">admin</option>
          <option value="editor">editor</option>
        </select>
      </label>
      <div><button class="bg-blue-600 text-white px-4 py-2 rounded">สร้าง</button></div>
    </form>
  </div>

  <div>
    <h2 class="font-medium">รายชื่อผู้ดูแล</h2>
    <table class="w-full mt-3 text-sm">
      <thead>
        <tr class="text-left text-gray-600"><th>id</th><th>username</th><th>created</th><th>action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($admins as $a): ?>
          <tr class="border-t">
            <td class="py-2"><?php echo e($a['id']); ?></td>
            <td class="py-2"><?php echo e($a['username']); ?></td>
            <td class="py-2"><?php echo e($a['created_at']); ?></td>
            <td class="py-2">
              <?php if ((int)$a['id'] !== (int)($_SESSION['admin_id'] ?? 0)): ?>
                <form method="post" style="display:inline" onsubmit="return confirm('ยืนยันการลบผู้ดูแลนี้?');">
                  <input type="hidden" name="csrf" value="<?php echo e($token); ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo e($a['id']); ?>">
                  <button class="text-red-600">ลบ</button>
                </form>
              <?php else: ?>
                <span class="text-gray-400">(กำลังใช้งาน)</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
