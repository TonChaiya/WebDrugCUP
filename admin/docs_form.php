<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { header('Location: login.php'); exit; }
$pdo = get_db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$doc = ['title'=>'','description'=>'','category'=>'','drive_id'=>'','drive_url'=>'','image'=>''];
if ($id) { $stmt=$pdo->prepare('SELECT * FROM documents WHERE id=?'); $stmt->execute([$id]); $doc = $stmt->fetch() ?: $doc; }

// handle form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $drive_url = $_POST['drive_url'] ?? '';
    $drive_id = $_POST['drive_id'] ?? '';
  $image = $_POST['image'] ?? '';
  $image_position = $_POST['image_position'] ?? 'center';
  $image_focus_x = isset($_POST['image_focus_x']) ? (float)$_POST['image_focus_x'] : null;
  $image_focus_y = isset($_POST['image_focus_y']) ? (float)$_POST['image_focus_y'] : null;
  $upload_debug = null;

    // Handle optional cover image upload -> store locally on server
    if (!empty($_FILES['image_file']['tmp_name'])) {
    $tmp = $_FILES['image_file']['tmp_name'];
    $info = @getimagesize($tmp);
    if ($info === false) {
      // not an image; skip local cover save
    } else {
      $dir = UPLOADS_DIR . '/docs';
      if (!is_dir($dir)) @mkdir($dir, 0755, true);
      $ext = image_type_to_extension($info[2], false);
      $fname = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
      $dest = $dir . '/' . $fname;
      if (@move_uploaded_file($tmp, $dest)) {
        $image = rtrim(UPLOADS_URL, '/') . '/docs/' . $fname;
      }
    }
    }

    // Normalize image URL (if user entered a URL)
    if (!empty($image) && function_exists('normalize_drive_image_url')) {
      $normalized = normalize_drive_image_url($image);
      if ($normalized) $image = $normalized;
    }
  $upload_debug = null;

  // If a document file was uploaded, try upload (attempt even if Google client class not yet loaded)
    if (!empty($_FILES['file']['tmp_name'])) {
    // delegate to upload script (example) - this will load autoload/google helper as needed
    require_once __DIR__ . '/upload_drive.php';
    $res = upload_file_to_drive($_FILES['file']);
    if ($res && isset($res['id'])) {
      $drive_id = $res['id'];
      $drive_url = $res['webViewLink'] ?? $res['webContentLink'] ?? $drive_url;
      // if no separate image provided, and uploaded file is an image type, set image
      if (empty($image)) {
        $mime = $_FILES['file']['type'] ?? '';
        if (strpos($mime, 'image/') === 0) {
          // also save a local copy of the image (copy tmp to uploads)
          $tmpPath = $_FILES['file']['tmp_name'];
          $info2 = @getimagesize($tmpPath);
          if ($info2 !== false) {
            $dir2 = UPLOADS_DIR . '/docs';
            if (!is_dir($dir2)) @mkdir($dir2, 0755, true);
            $ext2 = image_type_to_extension($info2[2], false);
            $fname2 = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext2;
            $dest2 = $dir2 . '/' . $fname2;
            @copy($tmpPath, $dest2);
            $image = rtrim(UPLOADS_URL, '/') . '/docs/' . $fname2;
          } else {
            $image = $res['webContentLink'] ?? ('https://drive.google.com/uc?export=view&id=' . $res['id']);
          }
        }
      }
    } else {
      // surface debug info to the admin form
      $upload_debug = $res;
    }
  }

    // Detect if `image` and `image_position` columns exist in `documents` table
    $hasImageColumn = false;
    $hasImagePos = false;
    try {
      $colStmt = $pdo->prepare("SHOW COLUMNS FROM documents LIKE 'image'");
      $colStmt->execute();
      $hasImageColumn = (bool)$colStmt->fetch();
      $colStmt2 = $pdo->prepare("SHOW COLUMNS FROM documents LIKE 'image_position'");
      $colStmt2->execute();
      $hasImagePos = (bool)$colStmt2->fetch();
    } catch (Exception $e) {
      // ignore - assume column missing
      $hasImageColumn = $hasImagePos = false;
    }

    if ($id) {
      if ($hasImageColumn && $hasImagePos) {
        $pdo->prepare('UPDATE documents SET title=?, description=?, category=?, drive_id=?, drive_url=?, image=?, image_position=? WHERE id=?')
          ->execute([$title,$description,$category,$drive_id,$drive_url,$image,$image_position,$id]);
      } elseif ($hasImageColumn) {
        $pdo->prepare('UPDATE documents SET title=?, description=?, category=?, drive_id=?, drive_url=?, image=? WHERE id=?')
          ->execute([$title,$description,$category,$drive_id,$drive_url,$image,$id]);
      } else {
        $pdo->prepare('UPDATE documents SET title=?, description=?, category=?, drive_id=?, drive_url=? WHERE id=?')
          ->execute([$title,$description,$category,$drive_id,$drive_url,$id]);
      }
    } else {
      if ($hasImageColumn && $hasImagePos) {
        $pdo->prepare('INSERT INTO documents (title,description,category,drive_id,drive_url,image,image_position,uploaded_at) VALUES (?,?,?,?,?,?,?,?)')
          ->execute([$title,$description,$category,$drive_id,$drive_url,$image,$image_position,date('Y-m-d H:i:s')]);
      } elseif ($hasImageColumn) {
        $pdo->prepare('INSERT INTO documents (title,description,category,drive_id,drive_url,image,uploaded_at) VALUES (?,?,?,?,?,?,?)')
          ->execute([$title,$description,$category,$drive_id,$drive_url,$image,date('Y-m-d H:i:s')]);
      } else {
        $pdo->prepare('INSERT INTO documents (title,description,category,drive_id,drive_url,uploaded_at) VALUES (?,?,?,?,?,?)')
          ->execute([$title,$description,$category,$drive_id,$drive_url,date('Y-m-d H:i:s')]);
      }
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
      <label class="block mb-2">Cover image URL (optional)<input name="image" value="<?php echo e($doc['image']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">หรืออัปโหลดภาพปก (optional)<input id="docs-image-file" type="file" name="image_file" class="w-full"></label>
      <?php
        $docsPreviewUrl = '';
        if (!empty($doc['image'])) {
            $val = $doc['image'];
            if (preg_match('#^https?://#i', $val)) {
                $docsPreviewUrl = $val;
            } else {
                $base = rtrim(BASE_URL, '/');
                if ($base === '') $base = '/';
                if (strpos($val, '/') === 0) {
                    $docsPreviewUrl = $val;
                } else {
                    $docsPreviewUrl = $base . '/' . ltrim($val, '/');
                }
            }
        }
      ?>
      <?php if (!empty($docsPreviewUrl)): ?>
        <div id="docs-image-preview" class="mt-2">
          <div class="text-xs text-gray-600">Preview:</div>
          <img id="docs-image-preview-img" src="<?php echo e($docsPreviewUrl); ?>" alt="cover" class="mt-1 w-48 h-32 object-cover rounded border" style="object-position: <?php echo e($doc['image_position'] ?? 'center'); ?>;">
          <div class="text-xs mt-1 text-gray-500">URL: <a href="<?php echo e($docsPreviewUrl); ?>" target="_blank" class="text-blue-600 underline"><?php echo e($docsPreviewUrl); ?></a></div>
          <?php $fs = realpath(__DIR__ . '/..') . str_replace('/', DIRECTORY_SEPARATOR, $doc['image']); ?>
          <div class="text-xs text-gray-500">Filesystem: <?php echo e($fs); ?></div>
        </div>
      <?php endif; ?>

      <!-- Focal point editor for docs -->
      <div class="mt-4">
        <div class="text-sm font-medium">เลื่อนจุดโฟกัสภาพปก (คลิกหรือลาก)</div>
        <div id="docs-focal-editor" class="relative mt-2 border rounded overflow-hidden" style="height:160px; width:320px;">
          <img id="docs-focal-img" src="<?php echo e($docsPreviewUrl); ?>" class="w-full h-full object-cover" style="object-position: <?php echo e($doc['image_focus_x'] ?? 50); ?>% <?php echo e($doc['image_focus_y'] ?? 50); ?>%;">
          <div id="docs-focal-handle" title="ลากเพื่อเปลี่ยนจุดโฟกัส" style="position:absolute; width:18px; height:18px; border-radius:9999px; background:rgba(59,130,246,0.9); border:2px solid white; transform:translate(-50%,-50%); left:<?php echo e($doc['image_focus_x'] ?? 50); ?>%; top:<?php echo e($doc['image_focus_y'] ?? 50); ?>%; cursor:grab;"></div>
        </div>
        <input type="hidden" id="docs-image-focus-x" name="image_focus_x" value="<?php echo e($doc['image_focus_x'] ?? 50); ?>">
        <input type="hidden" id="docs-image-focus-y" name="image_focus_y" value="<?php echo e($doc['image_focus_y'] ?? 50); ?>">
      </div>
      <label class="block mb-2">ตำแหน่งภาพปก (preview):
        <select id="docs-image-position" name="image_position" class="border px-2 py-1 rounded">
          <?php $dpos = $doc['image_position'] ?? 'center'; ?>
          <option value="center" <?php echo $dpos==='center' ? 'selected' : ''; ?>>กลาง (center)</option>
          <option value="top" <?php echo $dpos==='top' ? 'selected' : ''; ?>>บน (top)</option>
          <option value="bottom" <?php echo $dpos==='bottom' ? 'selected' : ''; ?>>ล่าง (bottom)</option>
          <option value="left" <?php echo $dpos==='left' ? 'selected' : ''; ?>>ซ้าย (left)</option>
          <option value="right" <?php echo $dpos==='right' ? 'selected' : ''; ?>>ขวา (right)</option>
        </select>
      </label>

      <label class="block mb-2">หรืออัปโหลดไฟล์จากคอมพิวเตอร์ (เอกสารที่จะเก็บใน Google Drive)<input type="file" name="file" class="w-full"></label>
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
