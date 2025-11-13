<?php
session_start();
require __DIR__ . '/../includes/config.php';
$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;

$clientId = getenv('GOOGLE_OAUTH_CLIENT_ID');
$clientSecret = getenv('GOOGLE_OAUTH_CLIENT_SECRET');
$redirect = getenv('GOOGLE_OAUTH_REDIRECT');
if (empty($clientId) || empty($clientSecret) || empty($redirect)) {
    echo "OAuth client not configured.\n"; exit;
}

$client = new Google_Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirect);
$client->addScope(Google_Service_Drive::DRIVE);

if (!isset($_GET['code'])) {
    echo "No code returned by Google."; exit;
}

$code = $_GET['code'];
try {
    $token = $client->fetchAccessTokenWithAuthCode($code);
    if (isset($token['error'])) {
        echo "Error fetching token: " . htmlspecialchars(json_encode($token)); exit;
    }
    $tokenPath = __DIR__ . '/../credentials/oauth_token.json';
    file_put_contents($tokenPath, json_encode($token));
    echo "OAuth setup complete. Token saved. You can now return to the admin panel.";
    echo "<p><a href=\"dashboard.php\">Back to dashboard</a></p>";
} catch (Throwable $e) {
    echo "Exception while exchanging code: " . $e->getMessage();
}
