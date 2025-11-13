<?php
// CLI test to use admin/upload_drive.php -> upload_file_to_drive()
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/google.php';
require __DIR__ . '/../admin/upload_drive.php';

$tmp = __DIR__ . '/test_upload.txt';
file_put_contents($tmp, "This is a test file created at " . date('c'));

$fileArr = ['tmp_name' => $tmp, 'name' => 'test_upload.txt'];

echo "Calling upload_file_to_drive()...\n";
$res = upload_file_to_drive($fileArr);
echo "Result:\n";
print_r($res);

// cleanup
@unlink($tmp);

?>
