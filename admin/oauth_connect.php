<?php
session_start();
require __DIR__ . '/../includes/config.php';
// Start OAuth flow: redirect admin to Google consent screen
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;

// Read OAuth client settings from environment (or set in includes/config.php)
$clientId = getenv('GOOGLE_OAUTH_CLIENT_ID');
$clientSecret = getenv('GOOGLE_OAUTH_CLIENT_SECRET');
$redirect = getenv('GOOGLE_OAUTH_REDIRECT');
if (empty($clientId) || empty($clientSecret) || empty($redirect)) {
    echo "OAuth client not configured. Set GOOGLE_OAUTH_CLIENT_ID, GOOGLE_OAUTH_CLIENT_SECRET and GOOGLE_OAUTH_REDIRECT (callback URL) in environment or includes/config.php.";
    exit;
}

$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirect);
$client->addScope(Google_Service_Drive::DRIVE);
$client->setAccessType('offline');
$client->setPrompt('consent'); // ensure refresh_token is returned

$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
