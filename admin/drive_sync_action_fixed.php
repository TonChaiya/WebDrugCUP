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

    // IMPORT actions: single import or bulk import
    if ($action === 'import' || $action === 'import_all') {
        $ids = [];
        if ($action === 'import') {
            if (empty($body['drive_id'])) { throw new Exception('missing_drive_id'); }
            $ids = [$body['drive_id']];
        } else {
            $ids = is_array($body['drive_ids']) ? $body['drive_ids'] : [];
        }

        $client = function_exists('get_google_client') ? get_google_client() : null;
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

    } elseif ($action === 'skip' || $action === 'unskip' || $action === 'skip_all') {
        // ensure skip table exists (create if missing)
        try {
            $pdo->exec('CREATE TABLE IF NOT EXISTS drive_sync_skips (file_id VARCHAR(255) PRIMARY KEY, reason TEXT, created_at DATETIME)');
        } catch (Throwable $e) {
            // ignore
        }

        if ($action === 'skip') {
            $driveId = $body['drive_id'] ?? null;
            if (empty($driveId)) throw new Exception('missing_drive_id');
            $reason = $body['reason'] ?? null;
            $ins = $pdo->prepare('REPLACE INTO drive_sync_skips (file_id, reason, created_at) VALUES (?, ?, ?)');
            $ok = $ins->execute([$driveId, $reason, date('Y-m-d H:i:s')]);
            echo json_encode(['ok'=>$ok, 'action'=>'skip', 'drive_id'=>$driveId]); exit;
        }

        if ($action === 'unskip') {
            $driveId = $body['drive_id'] ?? null;
            if (empty($driveId)) throw new Exception('missing_drive_id');
            $del = $pdo->prepare('DELETE FROM drive_sync_skips WHERE file_id = ?');
            $ok = $del->execute([$driveId]);
            echo json_encode(['ok'=>$ok, 'action'=>'unskip', 'drive_id'=>$driveId]); exit;
        }

        if ($action === 'skip_all') {
            $ids = is_array($body['drive_ids']) ? $body['drive_ids'] : [];
            $results = [];
            $ins = $pdo->prepare('REPLACE INTO drive_sync_skips (file_id, reason, created_at) VALUES (?, ?, ?)');
            foreach ($ids as $did) {
                $ok = $ins->execute([$did, $body['reason'] ?? null, date('Y-m-d H:i:s')]);
                $results[$did] = $ok ? 'skipped' : 'failed';
            }
            echo json_encode(['ok'=>true,'results'=>$results]); exit;
        }

    } elseif ($action === 'delete_drive') {
        // delete a file from Drive (requires google client)
        $driveId = $body['drive_id'] ?? null;
        if (empty($driveId)) throw new Exception('missing_drive_id');
        $client = function_exists('get_google_client') ? get_google_client() : null;
        if (!$client) throw new Exception('no_google_client');
        $driveService = new Google_Service_Drive($client);
        try {
            $driveService->files->delete($driveId);
            echo json_encode(['ok'=>true,'action'=>'delete_drive','drive_id'=>$driveId]); exit;
        } catch (Throwable $e) {
            echo json_encode(['ok'=>false,'error'=>'delete_failed','message'=>$e->getMessage()]); exit;
        }
    }

    echo json_encode(['error'=>'unknown_action']);
} catch (Throwable $e) {
    echo json_encode(['error'=>'exception','message'=>$e->getMessage()]);
}

?>