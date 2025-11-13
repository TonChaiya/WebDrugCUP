<?php
// Simple drive debug script — does NOT print service account contents.
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/google.php';

echo "Drive debug script starting...\n";

$client = get_google_client();
if (!$client) {
    echo "get_google_client() returned null. Check credentials/service-account.json and google/apiclient installation.\n";
    // show log tail if exists
    $log = __DIR__ . '/../credentials/drive_upload.log';
    if (file_exists($log)) {
        echo "\n--- tail of drive_upload.log ---\n";
        $lines = @file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $tail = array_slice($lines, -50);
        foreach ($tail as $l) echo $l . "\n";
    }
    exit(1);
}

echo "Google_Client created. Trying Drive API listFiles()...\n";
try {
    $drive = new Google_Service_Drive($client);
    // Call listFiles() without optional params to avoid parameter-handling differences
    $resp = $drive->files->listFiles();
    $files = $resp->getFiles();
    if (empty($files)) {
        echo "No files returned (Drive accessible but empty or service account has no view access).\n";
    } else {
        echo "Files:\n";
        foreach ($files as $f) {
            echo sprintf("%s — %s\n", $f->getId(), $f->getName());
        }
    }
} catch (Throwable $e) {
    echo "Throwable calling Drive API: " . $e->getMessage() . "\n";
    // write a detailed debug file
    $dbgFile = __DIR__ . '/../credentials/drive_debug_exception.log';
    file_put_contents($dbgFile, date('c') . " " . $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL, FILE_APPEND);
    echo "Wrote detailed exception to credentials/drive_debug_exception.log\n";
    // show short tail of main log if present
    $log = __DIR__ . '/../credentials/drive_upload.log';
    if (file_exists($log)) {
        echo "\n--- tail of drive_upload.log ---\n";
        $lines = @file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $tail = array_slice($lines, -50);
        foreach ($tail as $l) echo $l . "\n";
    }
    exit(1);
}

echo "Drive debug finished.\n";

?>
