<?php
session_start();
require __DIR__ . '/../includes/config.php';
if (empty($_SESSION['admin_logged'])) { http_response_code(403); echo json_encode(['error'=>'not_logged']); exit; }
header('Content-Type: application/json');

$response = ['steps'=>[], 'result'=>null];

// helper to append step
function add_step(&$resp, $msg) {
    $t = date('H:i:s');
    $resp['steps'][] = "[{$t}] {$msg}";
}

try {
    add_step($response, 'Preparing test file');
    $tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'drive_test_' . uniqid() . '.txt';
    file_put_contents($tmp, "Test upload from admin at " . date('c'));
    add_step($response, 'Temp file created: ' . $tmp);

    // require upload helper
    require __DIR__ . '/upload_drive.php';
    add_step($response, 'Calling upload_file_to_drive()');

    // call the upload helper and capture return
    $res = upload_file_to_drive(['tmp_name'=>$tmp, 'name'=>'admin_test_upload.txt']);

    if (is_array($res) && isset($res['error'])) {
        add_step($response, 'Upload reported error: ' . $res['error']);
        $response['result'] = ['success'=>false, 'error'=>$res];
    } elseif (is_array($res) && isset($res['id'])) {
        add_step($response, 'Upload succeeded, id: ' . $res['id']);
        $response['result'] = ['success'=>true, 'file'=>$res];
    } else {
        add_step($response, 'Upload returned unexpected result: ' . print_r($res, true));
        $response['result'] = ['success'=>false, 'file'=>$res];
    }

    @unlink($tmp);
    add_step($response, 'Temporary file removed');

} catch (Throwable $e) {
    add_step($response, 'Exception: ' . $e->getMessage());
    $response['result'] = ['success'=>false, 'exception'=>$e->getMessage()];
}

echo json_encode($response);

?>
