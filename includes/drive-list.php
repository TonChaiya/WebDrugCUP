<?php
// Include this file from news-detail.php after the article. It expects $news to be in scope and
// `get_google_client()` available from includes/google.php (config.php already includes that).

function extract_drive_folder_id($s) {
    if (empty($s)) return null;
    if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $s, $m)) return $m[1];
    if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $s, $m)) return $m[1];
    if (preg_match('#^[a-zA-Z0-9_-]{10,}$#', $s)) return $s;
    return null;
}

$driveFolderRaw = $news['drive_folder_id'] ?? '';
$driveFolderId = extract_drive_folder_id($driveFolderRaw);
$driveFiles = [];
$driveError = null;
$haveClient = false;
// Ensure google helper is loaded so get_google_client() exists
if (!function_exists('get_google_client')) {
  $gfile = __DIR__ . '/google.php';
  if (file_exists($gfile)) {
    require_once $gfile;
  }
}
if ($driveFolderId) {
    try {
        $client = get_google_client();
        $haveClient = $client && class_exists('Google_Service_Drive');
    } catch (Throwable $e) {
        $haveClient = false;
        $driveError = $e->getMessage();
    }

    if ($haveClient) {
        try {
            $svc = new Google_Service_Drive($client);
            $opt = [
                'q' => sprintf("'%s' in parents and trashed=false", $driveFolderId),
                'fields' => 'files(id,name,mimeType,webViewLink,webContentLink,thumbnailLink)'
            ];
            $resp = $svc->files->listFiles($opt);
            foreach ($resp->getFiles() as $f) {
                $driveFiles[] = [
                    'id' => $f->getId(),
                    'name' => $f->getName(),
                    'mime' => $f->getMimeType(),
                    'webView' => $f->getWebViewLink(),
                    'webContent' => $f->getWebContentLink(),
                    'thumb' => $f->getThumbnailLink()
                ];
            }
        } catch (Throwable $e) {
            $driveError = $e->getMessage();
        }
    }
}

// Always show the Drive section when a folder id is set (so admin/user sees reason why nothing appears)
if ($driveFolderId):
?>
<section class="mt-6">
  <div class="bg-white rounded shadow p-4">
    <h2 class="font-semibold mb-3">ไฟล์ที่เกี่ยวข้องจาก Google Drive</h2>
    <?php if (!empty($driveError)): ?>
      <div class="text-sm text-red-600">ไม่สามารถดึงไฟล์จาก Google Drive: <?php echo e($driveError); ?></div>
      <div class="text-xs text-gray-600 mt-2">สาเหตุที่พบบ่อย: ไม่มีการตั้งค่า credentials, token หมดอายุ, หรือ Service Account ไม่มีสิทธิ์เข้าถึงโฟลเดอร์นี้</div>
      <div class="text-xs mt-2">ลองเปิดโฟลเดอร์โดยตรง: <a href="<?php echo e($driveFolderRaw); ?>" target="_blank" class="text-blue-600 underline"><?php echo e($driveFolderRaw); ?></a></div>
    <?php elseif (!$haveClient): ?>
      <div class="text-sm text-yellow-700">ไม่พบการตั้งค่า Google Drive client ในระบบ (ไม่สามารถเชื่อมต่อ API)</div>
      <div class="text-xs text-gray-600 mt-2">ตรวจสอบ `includes/google.php` และไฟล์ credentials ในโฟลเดอร์ `credentials/`</div>
      <div class="text-xs mt-2">เปิดโฟลเดอร์โดยตรง: <a href="<?php echo e($driveFolderRaw); ?>" target="_blank" class="text-blue-600 underline"><?php echo e($driveFolderRaw); ?></a></div>
    <?php elseif (empty($driveFiles)): ?>
      <div class="text-sm text-gray-700">ยังไม่มีไฟล์ในโฟลเดอร์นี้ หรือบัญชีที่เชื่อมต่อไม่มีสิทธิ์เห็นไฟล์</div>
      <div class="text-xs mt-2">เปิดโฟลเดอร์โดยตรงเพื่อตรวจสอบ: <a href="<?php echo e($driveFolderRaw); ?>" target="_blank" class="text-blue-600 underline"><?php echo e($driveFolderRaw); ?></a></div>
    <?php else: ?>
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <?php foreach ($driveFiles as $f): ?>
          <div class="border rounded p-3 bg-gray-50">
            <?php $isImage = strpos($f['mime'], 'image/') === 0; ?>
            <?php if ($isImage): ?>
              <?php
                // Use server-side proxy to stream images so browser can display images even when files are not public
                $proxySrc = rtrim(BASE_URL, '/') . '/drive-file.php?id=' . urlencode($f['id']);
              ?>
              <div class="h-40 w-full overflow-hidden rounded bg-white">
                <img src="<?php echo e($proxySrc); ?>" class="w-full h-full object-cover" alt="<?php echo e($f['name']); ?>">
              </div>
            <?php else: ?>
              <div class="h-40 w-full flex items-center justify-center bg-white rounded text-sm text-gray-700"><?php echo e(strtoupper(pathinfo($f['name'], PATHINFO_EXTENSION))); ?></div>
            <?php endif; ?>
            <div class="mt-2 text-sm">
              <div class="font-medium"><?php echo e($f['name']); ?></div>
              <div class="text-xs text-gray-500"><?php echo e($f['mime']); ?></div>
              <?php if (!empty($f['webView'])): ?>
                <a href="<?php echo e($f['webView']); ?>" target="_blank" class="inline-block mt-2 text-sm text-blue-600">เปิด / ดาวน์โหลด</a>
              <?php elseif (!empty($f['webContent'])): ?>
                <a href="<?php echo e($f['webContent']); ?>" target="_blank" class="inline-block mt-2 text-sm text-blue-600">ดาวน์โหลด</a>
              <?php else: ?>
                <a href="https://drive.google.com/file/d/<?php echo e($f['id']); ?>/view" target="_blank" class="inline-block mt-2 text-sm text-blue-600">ดูใน Drive</a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>
