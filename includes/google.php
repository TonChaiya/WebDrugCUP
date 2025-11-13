<?php
// Helper to create and configure Google_Client for Drive operations
// Reads service account path and optional folder ID from constants or falls back to defaults.

if (!defined('GOOGLE_SERVICE_ACCOUNT_PATH')) {
    // default path expected by existing upload helper
    define('GOOGLE_SERVICE_ACCOUNT_PATH', __DIR__ . '/../credentials/service-account.json');
}

if (!defined('GOOGLE_DRIVE_FOLDER_ID')) {
    // optional: if set, uploads can be placed under this folder
    define('GOOGLE_DRIVE_FOLDER_ID', '');
}

// Enable debug logging for Drive operations (set to true to write logs to credentials/drive_upload.log)
if (!defined('GOOGLE_DRIVE_DEBUG')) {
    $env = getenv('GOOGLE_DRIVE_DEBUG');
    define('GOOGLE_DRIVE_DEBUG', $env ? filter_var($env, FILTER_VALIDATE_BOOLEAN) : false);
}

function drive_log($message)
{
    if (!GOOGLE_DRIVE_DEBUG) return;
    $logDir = __DIR__ . '/../credentials';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $logFile = $logDir . '/drive_upload.log';
    $time = date('Y-m-d H:i:s');
    $text = "[{$time}] " . (is_string($message) ? $message : print_r($message, true)) . PHP_EOL;
    @file_put_contents($logFile, $text, FILE_APPEND | LOCK_EX);
}

function get_google_client()
{
    // try to load Composer autoloader if present so Google_Client class is available
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    // Prefer OAuth tokens if configured (environment variables + saved token)
    $oauthClientId = getenv('GOOGLE_OAUTH_CLIENT_ID');
    $oauthClientSecret = getenv('GOOGLE_OAUTH_CLIENT_SECRET');
    $oauthRedirect = getenv('GOOGLE_OAUTH_REDIRECT');
    $tokenPath = __DIR__ . '/../credentials/oauth_token.json';

    if ($oauthClientId && file_exists($tokenPath) && class_exists('Google_Client')) {
        drive_log('Found OAuth client config and token — building OAuth Google_Client');
        try {
            $client = new Google_Client();
            $client->setClientId($oauthClientId);
            if ($oauthClientSecret) $client->setClientSecret($oauthClientSecret);
            if ($oauthRedirect) $client->setRedirectUri($oauthRedirect);
            $client->addScope(Google_Service_Drive::DRIVE);

            $token = json_decode(file_get_contents($tokenPath), true);
            if ($token) {
                $client->setAccessToken($token);
                if ($client->isAccessTokenExpired() && isset($token['refresh_token'])) {
                    try {
                        $newToken = $client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
                        if (isset($newToken['access_token'])) {
                            if (!isset($newToken['refresh_token'])) {
                                $newToken['refresh_token'] = $token['refresh_token'];
                            }
                            file_put_contents($tokenPath, json_encode($newToken));
                            $client->setAccessToken($newToken);
                            drive_log('Access token refreshed via refresh_token');
                        }
                    } catch (Throwable $e) {
                        drive_log('Failed to refresh access token: ' . $e->getMessage());
                    }
                }
            }

            // set http client fallback
            if (class_exists('\GuzzleHttp\HandlerStack') && class_exists('\GuzzleHttp\Handler\StreamHandler') && class_exists('\GuzzleHttp\Client')) {
                try {
                    $stack = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\StreamHandler());
                    $httpClient = new \GuzzleHttp\Client(['handler' => $stack]);
                    $client->setHttpClient($httpClient);
                    drive_log('Custom Guzzle StreamHandler set as HTTP client for OAuth');
                } catch (Throwable $e) {
                    drive_log('Failed to set custom Guzzle StreamHandler (OAuth): ' . $e->getMessage());
                }
            }

            return $client;
        } catch (Throwable $e) {
            drive_log('Failed to create OAuth Google_Client: ' . $e->getMessage());
            // fall through to service-account-based client
        }
    }

    // Fallback to Service Account if present
    if (!file_exists(GOOGLE_SERVICE_ACCOUNT_PATH)) {
        drive_log('Service account JSON not found: ' . GOOGLE_SERVICE_ACCOUNT_PATH);
        return null;
    }
    if (!class_exists('Google_Client')) {
        drive_log('Google_Client class not found (google/apiclient not installed or autoloader not loaded)');
        return null;
    }

    try {
        $client = new Google_Client();
        $client->setAuthConfig(GOOGLE_SERVICE_ACCOUNT_PATH);
        $client->addScope(Google_Service_Drive::DRIVE);
        // Ensure Guzzle uses a non-curl handler if curl-based handler causes issues
        if (class_exists('\GuzzleHttp\HandlerStack') && class_exists('\GuzzleHttp\Handler\StreamHandler') && class_exists('\GuzzleHttp\Client')) {
            try {
                $stack = \GuzzleHttp\HandlerStack::create(new \GuzzleHttp\Handler\StreamHandler());
                $httpClient = new \GuzzleHttp\Client(['handler' => $stack]);
                $client->setHttpClient($httpClient);
                drive_log('Custom Guzzle StreamHandler set as HTTP client');
            } catch (Throwable $e) {
                drive_log('Failed to set custom Guzzle StreamHandler: ' . $e->getMessage());
            }
        }
        drive_log('Google_Client created successfully (service account)');
        return $client;
    } catch (Exception $e) {
        drive_log('Failed to create Google_Client: ' . $e->getMessage());
        return null;
    }
}

?>
