<?php
// Example helper to upload a file to Google Drive using service account
// Requires google/apiclient installed and a service account json path configured.
// This file is included by docs_form.php when class Google_Client exists.

function upload_file_to_drive(array $file)
{
    // Use centralized helper if available
    require_once __DIR__ . '/../includes/google.php';
    drive_log('upload_file_to_drive called');

    $client = get_google_client();
    if (!$client) {
        drive_log('get_google_client returned null');
        return ['error' => 'Google client not configured or missing. Check service account and google/apiclient installation.'];
    }

    $driveService = new Google_Service_Drive($client);

    $name = $file['name'] ?? basename($file['tmp_name']);
    $fileMetadataArr = ['name' => $name];
    if (!empty(GOOGLE_DRIVE_FOLDER_ID)) {
        $fileMetadataArr['parents'] = [GOOGLE_DRIVE_FOLDER_ID];
        drive_log('Uploading into folder: ' . GOOGLE_DRIVE_FOLDER_ID);
    }
    $fileMetadata = new Google_Service_Drive_DriveFile($fileMetadataArr);

    try {
        drive_log('Reading uploaded tmp file: ' . $file['tmp_name']);
        $content = file_get_contents($file['tmp_name']);
        drive_log('Starting Drive file create for name: ' . $name);
        $created = $driveService->files->create($fileMetadata, [
            'data' => $content,
            'uploadType' => 'multipart',
            // support Shared Drives (Team Drives)
            'supportsAllDrives' => true,
            // older clients may use 'supportsTeamDrives'
            'supportsTeamDrives' => true,
            'fields' => 'id,name,webViewLink,webContentLink'
        ]);
        drive_log('File created with id: ' . ($created->id ?? 'NULL'));

        // Make file public (anyoneWithLink)
        try {
            $permission = new Google_Service_Drive_Permission(['type' => 'anyone', 'role' => 'reader']);
            $driveService->permissions->create($created->id, $permission);
            drive_log('Permission set to anyoneWithLink for id: ' . $created->id);
        } catch (Exception $e) {
            drive_log('Permission creation failed: ' . $e->getMessage());
        }

        // Retrieve file metadata including webViewLink
        $f = $driveService->files->get($created->id, ['fields' => 'id,name,webViewLink,webContentLink']);
        drive_log('Retrieved metadata: ' . print_r($f, true));
        return [
            'id' => $f->id,
            'name' => $f->name,
            'webViewLink' => $f->webViewLink ?? null,
            'webContentLink' => $f->webContentLink ?? null,
            'raw' => $f,
        ];
    } catch (Exception $e) {
        // log and return error details for debugging
        drive_log('Upload exception: ' . $e->getMessage());
        drive_log('Stack trace: ' . $e->getTraceAsString());
        return ['error' => 'Upload failed: ' . $e->getMessage(), 'exception' => $e->getMessage()];
    }
}
