<?php

define('GOOGLE_DRIVE_FOLDER_ID', '1Tyyk9Z_S247lvmlw8G9g3Y06Nq-RMETs');
define('GOOGLE_SERVICE_ACCOUNT_PATH', __DIR__ . '/../credentials/service-account.json');
// OAuth client credentials (from credentials/client_secret_...json)
// These are used by admin/oauth_connect.php and the OAuth flow.
// For local dev it's convenient to set them here; do NOT commit secrets to public repos.
putenv('GOOGLE_OAUTH_CLIENT_ID=471777378740-7bjvlchi7olelqts2c577v0cb5cic347.apps.googleusercontent.com');
putenv('GOOGLE_OAUTH_CLIENT_SECRET=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');
putenv('GOOGLE_OAUTH_REDIRECT=http://localhost/admin/oauth_callback.php');
// Basic configuration
// If you want to use MySQL (XAMPP + phpMyAdmin), set DB_TYPE to 'mysql' and
// configure MYSQL_HOST, MYSQL_DB, MYSQL_USER, MYSQL_PASS below or via environment variables.

// Editable defaults (change here or set environment variables)
// Project is placed directly under C:\xampp\htdocs — use root BASE_URL '/'
if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

// DB_TYPE: 'sqlite' or 'mysql'
// Default to MySQL for this installation (change to 'sqlite' if you prefer the bundled SQLite)
if (!defined('DB_TYPE')) {
    $envDbType = getenv('DB_TYPE');
    define('DB_TYPE', $envDbType ? $envDbType : 'mysql');
}

// SQLite defaults
if (!defined('DB_PATH')) {
    define('DB_PATH', __DIR__ . '/../data/database.sqlite');
}

// MySQL defaults (used when DB_TYPE === 'mysql')
if (!defined('MYSQL_HOST')) {
    $env = getenv('MYSQL_HOST');
    define('MYSQL_HOST', $env ? $env : '127.0.0.1');
}
if (!defined('MYSQL_DB')) {
    $env = getenv('MYSQL_DB');
    define('MYSQL_DB', $env ? $env : 'webtest');
}
if (!defined('MYSQL_USER')) {
    $env = getenv('MYSQL_USER');
    define('MYSQL_USER', $env ? $env : 'root');
}
if (!defined('MYSQL_PASS')) {
    $env = getenv('MYSQL_PASS');
    define('MYSQL_PASS', $env ? $env : '');
}

// Admin users are stored in the database (table `admins`).
// Create admin accounts via phpMyAdmin or the command-line (see README.md).

function get_db()
{
    try {
        if (DB_TYPE === 'mysql') {
            $host = MYSQL_HOST;
            $db = MYSQL_DB;
            $user = MYSQL_USER;
            $pass = MYSQL_PASS;
            $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            return $pdo;
        }

        // Default: sqlite
        $dsn = 'sqlite:' . DB_PATH;
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (Exception $e) {
        // Simple error output for local development
        die('Database connection failed: ' . $e->getMessage());
    }
}

// Helper: escape output
function e($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Set timezone to Thailand
date_default_timezone_set('Asia/Bangkok');

// Optional: enable verbose debug logging for Google Drive operations during testing
if (!defined('GOOGLE_DRIVE_DEBUG')) {
    // change to true to enable file logging in credentials/drive_upload.log
    define('GOOGLE_DRIVE_DEBUG', true);
}

// Helper: format datetime string to readable Thai/local time
function format_datetime($dt, $fmt = 'd/m/Y H:i')
{
    if (empty($dt)) return '';
    $ts = strtotime($dt);
    if ($ts === false) return $dt;
    return date($fmt, $ts);
}

?>
