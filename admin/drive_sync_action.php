<?php
// Deprecated stub: the original handler was replaced because it contained
// duplicated/corrupted code. The working implementation lives at:
//   admin/drive_sync_action_fixed.php

header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'error' => 'deprecated',
    'message' => 'admin/drive_sync_action.php has been retired. Call admin/drive_sync_action_fixed.php instead.'
]);
exit;
