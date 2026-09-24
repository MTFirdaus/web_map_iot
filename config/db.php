<?php
/**
 * Database Connection Configuration
 * Project: IoT GPS Tracker
 * Database: my_map_project
 * Table: loc
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'my_map_project');
define('DB_PORT', 3306);

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";port=" . DB_PORT . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Koneksi Database Gagal: ' . $e->getMessage()
        ], JSON_PRETTY_PRINT);
        exit();
    } else {
        die("Koneksi Database Gagal: " . $e->getMessage() . "\n");
    }
}
?>
