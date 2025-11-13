<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { http_response_code(403); echo json_encode(['error'=>'not_logged']); exit; }
header('Content-Type: application/json');

$pdo = get_db();

$folderId = defined('GOOGLE_DRIVE_FOLDER_ID') ? GOOGLE_DRIVE_FOLDER_ID : '';
if (empty($folderId)) {
    echo json_encode(['error'=>'no_folder_config']); exit;
}

require_once __DIR__ . '/../includes/google.php';
$client = get_google_client();
if (!$client) {
    echo json_encode(['error'=>'no_google_client']); exit;
}

try {
    $driveService = new Google_Service_Drive($client);
    $files = [];
    $pageToken = null;
    $q = sprintf("'%s' in parents and trashed=false", $folderId);
    do {
        $opt = ['q' => $q, 'fields' => 'nextPageToken, files(id, name, webViewLink)', 'pageSize' => 1000];
        if ($pageToken) $opt['pageToken'] = $pageToken;
        $resp = $driveService->files->listFiles($opt);
        foreach ($resp->getFiles() as $f) {
            $files[$f->getId()] = ['id'=>$f->getId(), 'name'=>$f->getName(), 'webViewLink'=>$f->getWebViewLink()];
        }
        $pageToken = $resp->getNextPageToken();
    } while ($pageToken);

    // fetch DB drive_ids
    $stmt = $pdo->prepare('SELECT id, title, drive_id FROM documents WHERE drive_id IS NOT NULL AND drive_id != ""');
    $stmt->execute();
    $db = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dbByDriveId = [];
    foreach ($db as $r) {
        $dbByDriveId[$r['drive_id']] = ['doc_id'=>$r['id'], 'title'=>$r['title']];
    }

    $driveIds = array_keys($files);
    $dbDriveIds = array_keys($dbByDriveId);

    $onDriveNotInDB = [];
    foreach ($driveIds as $did) {
        if (!isset($dbByDriveId[$did])) {
            $onDriveNotInDB[] = $files[$did];
        }
    }

    $inDBNotOnDrive = [];
    foreach ($dbDriveIds as $did) {
        if (!isset($files[$did])) {
            $inDBNotOnDrive[] = ['drive_id'=>$did, 'doc_id'=>$dbByDriveId[$did]['doc_id'], 'title'=>$dbByDriveId[$did]['title']];
        }
    }

    echo json_encode([
        'ok'=>true,
        'counts'=>['drive_total'=>count($driveIds),'db_total'=>count($dbDriveIds),'onDriveNotInDB'=>count($onDriveNotInDB),'inDBNotOnDrive'=>count($inDBNotOnDrive)],
        'onDriveNotInDB'=>$onDriveNotInDB,
        'inDBNotOnDrive'=>$inDBNotOnDrive,
    ]);

} catch (Throwable $e) {
    echo json_encode(['error'=>'exception','message'=>$e->getMessage()]);
}

?>
