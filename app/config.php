<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Change this line to point to the project root directory
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Database connection parameters
define('DB_HOST', $_ENV['DB_HOST']);
define('DB_USER', $_ENV['DB_USER']);
define('DB_PASS', $_ENV['DB_PASS']);
define('DB_NAME', $_ENV['DB_NAME']);

// Session configuration
define('SESSION_LIFETIME', $_ENV['SESSION_LIFETIME']);
define('SESSION_PATH', '/');
define('SESSION_DOMAIN', '');
define('SESSION_SECURE', $_ENV['SESSION_SECURE']);
define('SESSION_HTTPONLY', true);

// Application settings
define('APP_NAME', $_ENV['APP_NAME']);
define('APP_URL', $_ENV['APP_URL']);
define('MAX_NOTE_LENGTH', $_ENV['MAX_NOTE_LENGTH']);

// Create database connection
function getDbConnection()
{
    try {
        $conn = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch (PDOException $e) {
        die('Database Connection Error: ' . $e->getMessage());
    }
}