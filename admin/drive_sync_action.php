<?php
session_start();
require __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');
if (empty($_SESSION['admin_logged'])) { http_response_code(403); echo json_encode(['error'=>'not_logged']); exit; }

$pdo = get_db();

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) { echo json_encode(['error'=>'invalid_request']); exit; }

$action = $body['action'] ?? '';

try {
    // load google helper if available (used to fetch metadata)
    if (file_exists(__DIR__ . '/../includes/google.php')) {
        require_once __DIR__ . '/../includes/google.php';
    }
    drive_log('drive_sync_action called with: ' . json_encode($body));
    if ($action === 'import' || $action === 'import_all') {
        $ids = [];
        if ($action === 'import') {
            if (empty($body['drive_id'])) { throw new Exception('missing_drive_id'); }
            $ids = [$body['drive_id']];
        } else {
            $ids = is_array($body['drive_ids']) ? $body['drive_ids'] : [];
        }

        $client = get_google_client();
        $driveService = null;
        if ($client) $driveService = new Google_Service_Drive($client);

        $results = [];
        foreach ($ids as $did) {
            // skip if already in DB
            $stmt = $pdo->prepare('SELECT id FROM documents WHERE drive_id = ? LIMIT 1');
            $stmt->execute([$did]);
            if ($stmt->fetch()) {
                $results[$did] = ['ok'=>false,'msg'=>'already_exists'];
                continue;
            }

            $title = $body['name'] ?? null;
            $url = $body['webViewLink'] ?? null;

            // try to fetch metadata from Drive if possible
            if ($driveService) {
                try {
                    $f = $driveService->files->get($did, ['fields'=>'id,name,webViewLink']);
                    if ($f) {
                        $title = $f->getName() ?: $title;
                        $url = $f->getWebViewLink() ?: $url;
                    }
                } catch (Throwable $e) {
                    // ignore and fallback to provided name/url
                }
            }

            if (!$title) $title = 'Imported file ' . $did;
            $uploaded_at = date('Y-m-d H:i:s');
            $ins = $pdo->prepare('INSERT INTO documents (title, drive_id, drive_url, uploaded_at) VALUES (?, ?, ?, ?)');
            $ok = $ins->execute([$title, $did, $url, $uploaded_at]);
            if ($ok) {
                $results[$did] = ['ok'=>true,'msg'=>'imported','doc_id'=>$pdo->lastInsertId()];
            } else {
                $results[$did] = ['ok'=>false,'msg'=>'insert_failed'];
            }
        }

        echo json_encode(['ok'=>true,'results'=>$results]); exit;

    } elseif ($action === 'remove' || $action === 'remove_all') {
        $ids = [];
        if ($action === 'remove') {
            if (empty($body['doc_id'])) { throw new Exception('missing_doc_id'); }
            $ids = [$body['doc_id']];
        } else {
            $ids = is_array($body['doc_ids']) ? $body['doc_ids'] : [];
        }

        $results = [];
        foreach ($ids as $did) {
            $del = $pdo->prepare('DELETE FROM documents WHERE id = ?');
            $ok = $del->execute([$did]);
            if ($ok) $results[$did] = ['ok'=>true,'msg'=>'deleted']; else $results[$did] = ['ok'=>false,'msg'=>'delete_failed'];
        }

        echo json_encode(['ok'=>true,'results'=>$results]); exit;
    }

    echo json_encode(['error'=>'unknown_action']);
} catch (Throwable $e) {
    echo json_encode(['error'=>'exception','message'=>$e->getMessage()]);
}

?>
