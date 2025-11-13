<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
$pdo = get_db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc = ['title'=>'','description'=>'','category'=>'','drive_id'=>'','drive_url'=>''];
if ($id) { $stmt=$pdo->prepare('SELECT * FROM documents WHERE id=?'); $stmt->execute([$id]); $doc = $stmt->fetch() ?: $doc; }

// handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $drive_url = $_POST['drive_url'] ?? '';
    $drive_id = $_POST['drive_id'] ?? '';
  $upload_debug = null;

  // If a file was uploaded, try upload (attempt even if Google client class not yet loaded)
  if (!empty($_FILES['file']['tmp_name'])) {
    // delegate to upload script (example) - this will load autoload/google helper as needed
    require __DIR__ . '/upload_drive.php';
    $res = upload_file_to_drive($_FILES['file']);
    if ($res && isset($res['id'])) {
      $drive_id = $res['id'];
      $drive_url = $res['webViewLink'] ?? $res['alternateLink'] ?? $drive_url;
    } else {
      // surface debug info to the admin form
      $upload_debug = $res;
    }
    }

    if ($id) {
        $pdo->prepare('UPDATE documents SET title=?, description=?, category=?, drive_id=?, drive_url=? WHERE id=?')
            ->execute([$title,$description,$category,$drive_id,$drive_url,$id]);
    } else {
        $pdo->prepare('INSERT INTO documents (title,description,category,drive_id,drive_url,uploaded_at) VALUES (?,?,?,?,?,?)')
            ->execute([$title,$description,$category,$drive_id,$drive_url,date('Y-m-d H:i:s')]);
    }
  if ($upload_debug) {
    // do not redirect if upload produced debug info — show it below
  } else {
    header('Location: dashboard.php'); exit;
  }
}

?>
<!doctype html><html lang="th"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Documents Form</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="p-6">
  <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl font-bold mb-4"><?php echo $id ? 'แก้ไขเอกสาร' : 'เพิ่มเอกสาร'; ?></h1>
    <?php
    // Show OAuth connect button if OAuth not configured/connected
    $oauthTokenPath = __DIR__ . '/../credentials/oauth_token.json';
    $showConnect = !file_exists($oauthTokenPath);
    if ($showConnect): ?>
      <div class="mb-4 p-3 bg-blue-50 border rounded">
        <p class="mb-2">ยังไม่ได้เชื่อมต่อกับ Google OAuth — หากต้องการให้อัปโหลดไปยังบัญชี Google ของคุณ ให้คลิกปุ่มด้านล่างเพื่อเชื่อมต่อ (จะขอสิทธิ์)</p>
        <a href="oauth_connect.php" class="inline-block bg-green-600 text-white px-3 py-1 rounded">เชื่อมต่อ Google</a>
      </div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <label class="block mb-2">ชื่อเอกสาร<input name="title" value="<?php echo e($doc['title']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">หมวด<input name="category" value="<?php echo e($doc['category']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">คำอธิบาย<textarea name="description" class="w-full border px-2 py-1 rounded"><?php echo e($doc['description']); ?></textarea></label>
      <label class="block mb-2">ลิงก์ Google Drive (ถ้ามี)<input name="drive_url" value="<?php echo e($doc['drive_url']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">หรืออัปโหลดไฟล์จากคอมพิวเตอร์<input type="file" name="file" class="w-full"></label>
      <div class="flex gap-2 mt-3"><button class="bg-blue-600 text-white px-3 py-1 rounded">บันทึก</button><a href="dashboard.php" class="px-3 py-1">ยกเลิก</a></div>
      <?php if (!empty($upload_debug)): ?>
        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 text-sm text-red-800">
          <strong>Upload debug:</strong>
          <pre style="white-space:pre-wrap;"><?php echo htmlspecialchars(print_r($upload_debug, true)); ?></pre>
          <p class="text-xs text-gray-600">Check credentials/drive_upload.log for more details (enable GOOGLE_DRIVE_DEBUG).</p>
        </div>
      <?php endif; ?>
    </form>
    <p class="mt-3 text-xs text-gray-500">หมายเหตุ: หากต้องการอัปโหลดไปยัง Google Drive อัตโนมัติ ให้ติดตั้ง google/apiclient และตั้งค่า service account ตาม README</p>
  </div>
</body></html>
