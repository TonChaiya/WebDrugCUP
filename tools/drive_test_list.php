<?php
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/google.php';

$folder = $argv[1] ?? '1ZtxTpr0ZNCggHGvjfNkhWUiyBuTWhk0P'; // example from user

function extract_drive_folder_id($s) {
    if (empty($s)) return null;
    if (preg_match('#/folders/([a-zA-Z0-9_-]+)#', $s, $m)) return $m[1];
    if (preg_match('#[?&]id=([a-zA-Z0-9_-]+)#', $s, $m)) return $m[1];
    if (preg_match('#^[a-zA-Z0-9_-]{10,}$#', $s)) return $s;
    return null;
}

$folderId = extract_drive_folder_id($folder);
if (!$folderId) {
    echo "Invalid folder id\n";
    exit(1);
}

$client = null;
try {
    $client = get_google_client();
} catch (Throwable $e) {
    echo "get_google_client() threw: " . $e->getMessage() . "\n";
}

if (!$client) {
    echo "No Google client available. Check includes/google.php and credentials.\n";
    exit(1);
}

if (!class_exists('Google_Service_Drive')) {
    echo "Google_Service_Drive class not found. Is google/apiclient installed?\n";
    exit(1);
}

try {
    $svc = new Google_Service_Drive($client);
    $opt = [
        'q' => sprintf("'%s' in parents and trashed=false", $folderId),
        'fields' => 'files(id,name,mimeType,webViewLink,thumbnailLink)'
    ];
    $resp = $svc->files->listFiles($opt);
    $files = $resp->getFiles();
    echo "Found " . count($files) . " files in folder $folderId\n";
    foreach ($files as $f) {
        printf("- %s (%s)\n", $f->getName(), $f->getMimeType());
    }
} catch (Throwable $e) {
    echo "Drive API error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
