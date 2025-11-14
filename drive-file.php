<?php
// Proxy endpoint to stream Drive file content to browser using server-side Google client.
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/google.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    http_response_code(400);
    echo 'Missing file id';
    exit;
}

$client = get_google_client();
if (!$client) {
    http_response_code(500);
    echo 'No Google client available';
    exit;
}

if (!class_exists('Google_Service_Drive')) {
    http_response_code(500);
    echo 'Google Drive client library not available';
    exit;
}

try {
    $svc = new Google_Service_Drive($client);
    // fetch metadata to determine mime type and name
    $meta = $svc->files->get($id, ['fields' => 'id,name,mimeType,thumbnailLink']);
    $mime = $meta->getMimeType() ?: 'application/octet-stream';
    // stream file content
    $response = $svc->files->get($id, ['alt' => 'media']);
    // $response is a Guzzle stream
    $body = $response->getBody();

    // Send headers
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=300');
    header('Content-Disposition: inline; filename="' . addslashes($meta->getName()) . '"');

    // Stream the content
    while (!$body->eof()) {
        echo $body->read(1024 * 8);
        flush();
    }
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    if (defined('GOOGLE_DRIVE_DEBUG') && GOOGLE_DRIVE_DEBUG) {
        echo 'Drive proxy error: ' . $e->getMessage();
    } else {
        echo 'Failed to fetch file';
    }
    exit;
}
