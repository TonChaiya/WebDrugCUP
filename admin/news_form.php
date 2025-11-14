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
  $image_position = $_POST['image_position'] ?? 'center';
  $image_focus_x = isset($_POST['image_focus_x']) ? (float)$_POST['image_focus_x'] : null; // percent 0-100
  $image_focus_y = isset($_POST['image_focus_y']) ? (float)$_POST['image_focus_y'] : null; // percent 0-100
  $drive_folder_id = $_POST['drive_folder_id'] ?? '';
  $errorMsg = '';

  // Handle optional image file upload (cover image) -> store locally on server
  if (!empty($_FILES['image_file']['tmp_name'])) {
    $size = $_FILES['image_file']['size'] ?? 0;
    if ($size > MAX_COVER_UPLOAD_BYTES) {
      $errorMsg = 'ไฟล์ภาพปกมีขนาดใหญ่เกิน ' . (MAX_COVER_UPLOAD_BYTES/1024/1024) . ' MB';
    } else {
      $tmp = $_FILES['image_file']['tmp_name'];
      // basic validation: is image?
      $info = @getimagesize($tmp);
      if ($info !== false) {
        // prepare upload directory
        $dir = UPLOADS_DIR . '/news';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        // generate unique filename
        $ext = image_type_to_extension($info[2], false); // e.g. jpg png
        $fname = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = $dir . '/' . $fname;
        if (@move_uploaded_file($tmp, $dest)) {
            // public URL
            $image = rtrim(UPLOADS_URL, '/') . '/news/' . $fname;
          }
      }
    }
  }

    if ($id) {
    // include image and position in update if columns exist
    try {
      $colStmt = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image'");
      $colStmt->execute();
      $hasImage = (bool)$colStmt->fetch();
      $posStmt = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_position'");
      $posStmt->execute();
      $hasImagePos = (bool)$posStmt->fetch();
    } catch (Exception $e) { $hasImage = $hasImagePos = false; }

      // determine if image_focus columns exist
      $hasFocusX = false; $hasFocusY = false;
      try {
        $f1 = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_focus_x'"); $f1->execute(); $hasFocusX = (bool)$f1->fetch();
        $f2 = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_focus_y'"); $f2->execute(); $hasFocusY = (bool)$f2->fetch();
      } catch (Exception $e) { $hasFocusX = $hasFocusY = false; }

      if ($hasImage && $hasImagePos && $hasFocusX && $hasFocusY) {
      $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, category=?, image=?, image_position=?, image_focus_x=?, image_focus_y=? WHERE id=?');
      $stmt->execute([$title, $content, $category, $image, $image_position, $image_focus_x, $image_focus_y, $id]);
    } elseif ($hasImage && $hasImagePos) {
      $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, category=?, image=?, image_position=? WHERE id=?');
      $stmt->execute([$title, $content, $category, $image, $image_position, $id]);
    } elseif ($hasImage) {
      if ($hasFocusX && $hasFocusY) {
        $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, category=?, image=?, image_focus_x=?, image_focus_y=? WHERE id=?');
        $stmt->execute([$title, $content, $category, $image, $image_focus_x, $image_focus_y, $id]);
      } else {
        $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, category=?, image=? WHERE id=?');
        $stmt->execute([$title, $content, $category, $image, $id]);
      }
    } else {
      $stmt = $pdo->prepare('UPDATE news SET title=?, content=?, category=? WHERE id=?');
      $stmt->execute([$title, $content, $category, $id]);
    }
  } else {
    // insert
    try {
      $colStmt2 = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_position'");
      $colStmt2->execute();
      $hasImagePosInsert = (bool)$colStmt2->fetch();
    } catch (Exception $e) { $hasImagePosInsert = false; }

    if ($hasImagePosInsert) {
      // check focus cols for insert
      try {
        $fi1 = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_focus_x'"); $fi1->execute(); $hfx = (bool)$fi1->fetch();
        $fi2 = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_focus_y'"); $fi2->execute(); $hfy = (bool)$fi2->fetch();
      } catch (Exception $e) { $hfx = $hfy = false; }
      if ($hfx && $hfy) {
        $stmt = $pdo->prepare('INSERT INTO news (title, content, image, image_position, image_focus_x, image_focus_y, date_posted, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $content, $image, $image_position, $image_focus_x, $image_focus_y, date('Y-m-d H:i:s'), $category]);
      } else {
        $stmt = $pdo->prepare('INSERT INTO news (title, content, image, image_position, date_posted, category) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $content, $image, $image_position, date('Y-m-d H:i:s'), $category]);
      }
        // after insert, get inserted id and save drive_folder_id if column exists
        $newId = $pdo->lastInsertId();
        if (!empty($drive_folder_id) && $newId) {
            try {
                $col = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'drive_folder_id'"); $col->execute();
                if ($col->fetch()) {
                    $up = $pdo->prepare('UPDATE news SET drive_folder_id = ? WHERE id = ?');
                    $up->execute([$drive_folder_id, $newId]);
                }
            } catch (Exception $e) { /* ignore */ }
        }
    } else {
      // no image_position column; try insert focus if possible
      try {
        $fi1 = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_focus_x'"); $fi1->execute(); $hfx = (bool)$fi1->fetch();
        $fi2 = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'image_focus_y'"); $fi2->execute(); $hfy = (bool)$fi2->fetch();
      } catch (Exception $e) { $hfx = $hfy = false; }
      if ($hfx && $hfy) {
        $stmt = $pdo->prepare('INSERT INTO news (title, content, image, image_focus_x, image_focus_y, date_posted, category) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $content, $image, $image_focus_x, $image_focus_y, date('Y-m-d H:i:s'), $category]);
      } else {
        $stmt = $pdo->prepare('INSERT INTO news (title, content, image, date_posted, category) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$title, $content, $image, date('Y-m-d H:i:s'), $category]);
      }
    }
  }
  // update drive_folder_id if column exists
  if (isset($id) && !empty($drive_folder_id)) {
    try {
      $col = $pdo->prepare("SHOW COLUMNS FROM news LIKE 'drive_folder_id'"); $col->execute();
      if ($col->fetch()) {
        $up = $pdo->prepare('UPDATE news SET drive_folder_id = ? WHERE id = ?');
        $up->execute([$drive_folder_id, $id]);
      }
    } catch (Exception $e) { /* ignore */ }
  }
  if (!empty($errorMsg)) {
    // do not redirect; fall through to show form with error
  } else {
    header('Location: dashboard.php'); exit;
  }
}
// render page via header/footer for consistent styling
$pageTitle = $id ? 'แก้ไขข่าว' : 'เพิ่มข่าว';
require __DIR__ . '/../includes/header.php';
?>
  <div class="max-w-3xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-semibold mb-4"><?php echo htmlspecialchars($pageTitle); ?></h1>
    <?php if (!empty($errorMsg)): ?>
      <div class="mb-4 text-red-600"><?php echo e($errorMsg); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <label class="block mb-2">หัวข้อ<input name="title" value="<?php echo e($news['title']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">หมวด<input name="category" value="<?php echo e($news['category']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">เนื้อหา<textarea name="content" rows="8" class="w-full border px-2 py-1 rounded"><?php echo e($news['content']); ?></textarea></label>
      <label class="block mb-2">ลิงก์ภาพปก (ถ้ามี)<input name="image" value="<?php echo e($news['image']); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">หรืออัปโหลดภาพปก (optional)<input type="file" id="news-image-file" name="image_file" class="w-full"></label>
        <label class="block mb-2">ไดเรกทอรี Google Drive สำหรับข่าวนี้ (Folder ID หรือลิงก์)<input name="drive_folder_id" value="<?php echo e($news['drive_folder_id'] ?? ''); ?>" class="w-full border px-2 py-1 rounded"></label>
      <label class="block mb-2">ตำแหน่งภาพปก (preview):
        <select id="news-image-position" name="image_position" class="border px-2 py-1 rounded">
          <?php $pos = $news['image_position'] ?? 'center'; ?>
          <option value="center" <?php echo $pos==='center' ? 'selected' : ''; ?>>กลาง (center)</option>
          <option value="top" <?php echo $pos==='top' ? 'selected' : ''; ?>>บน (top)</option>
          <option value="bottom" <?php echo $pos==='bottom' ? 'selected' : ''; ?>>ล่าง (bottom)</option>
          <option value="left" <?php echo $pos==='left' ? 'selected' : ''; ?>>ซ้าย (left)</option>
          <option value="right" <?php echo $pos==='right' ? 'selected' : ''; ?>>ขวา (right)</option>
        </select>
      </label>
      <div class="text-xs text-gray-500 mb-2">ขนาดไฟล์สูงสุด: <?php echo (MAX_COVER_UPLOAD_BYTES/1024/1024); ?> MB</div>
      <?php
        // compute preview URL for admin diagnostics
        $imgPreviewUrl = '';
        if (!empty($news['image'])) {
            $imgVal = $news['image'];
            if (preg_match('#^https?://#i', $imgVal)) {
                $imgPreviewUrl = $imgVal;
            } else {
                $base = rtrim(BASE_URL, '/');
                if ($base === '') $base = '/';
                // if image is absolute path (starts with /) keep it, else join
                if (strpos($imgVal, '/') === 0) {
                    $imgPreviewUrl = $imgVal;
                } else {
                    $imgPreviewUrl = $base . '/' . ltrim($imgVal, '/');
                }
            }
        }
      ?>
      <div id="news-image-preview" class="mt-2" style="display:<?php echo !empty($imgPreviewUrl) ? 'block' : 'none'; ?>;">
        <div class="text-xs text-gray-600">Preview:</div>
        <img id="news-image-preview-img" src="<?php echo e($imgPreviewUrl); ?>" alt="cover" class="mt-1 w-64 h-40 object-cover rounded border" style="object-position: <?php echo e($news['image_position'] ?? 'center'); ?>;">
        <?php if (!empty($imgPreviewUrl)): ?>
          <div class="text-xs mt-1 text-gray-500">URL: <a href="<?php echo e($imgPreviewUrl); ?>" target="_blank" class="text-blue-600 underline"><?php echo e($imgPreviewUrl); ?></a></div>
          <?php
            // show filesystem path for debugging
            $fsPath = realpath(__DIR__ . '/..') . str_replace('/', DIRECTORY_SEPARATOR, $news['image']);
          ?>
          <div class="text-xs text-gray-500">Filesystem: <?php echo e($fsPath); ?></div>
        <?php endif; ?>
        </div>

        <!-- Focal point editor (drag to set) -->
        <div class="mt-4">
          <div class="text-sm font-medium">เลื่อนจุดโฟกัสภาพปก (คลิกหรือลาก)</div>
          <div id="news-focal-editor" class="relative mt-2 border rounded overflow-hidden" style="height:160px;">
            <img id="news-focal-img" src="<?php echo e($imgPreviewUrl); ?>" class="w-full h-full object-cover" style="object-position: <?php echo e($news['image_focus_x'] ?? 50); ?>% <?php echo e($news['image_focus_y'] ?? 50); ?>%;">
            <div id="news-focal-handle" title="ลากเพื่อเปลี่ยนจุดโฟกัส" style="position:absolute; width:18px; height:18px; border-radius:9999px; background:rgba(59,130,246,0.9); border:2px solid white; transform:translate(-50%,-50%); left:<?php echo e($news['image_focus_x'] ?? 50); ?>%; top:<?php echo e($news['image_focus_y'] ?? 50); ?>%; cursor:grab;"></div>
          </div>
          <input type="hidden" id="news-image-focus-x" name="image_focus_x" value="<?php echo e($news['image_focus_x'] ?? 50); ?>">
          <input type="hidden" id="news-image-focus-y" name="image_focus_y" value="<?php echo e($news['image_focus_y'] ?? 50); ?>">
        </div>
      <div class="flex gap-2"><button class="bg-blue-600 text-white px-4 py-2 rounded">บันทึก</button><a href="dashboard.php" class="px-4 py-2">ยกเลิก</a></div>
    </form>
  </div>
<?php
require __DIR__ . '/../includes/footer.php';
?>
