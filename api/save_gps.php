<?php
/**
 * API Save GPS Data
 * Endpoint untuk menerima data dari alat GPS / ESP8266 / Client
 * Database: my_map_project | Table: loc
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';

// Ambil input dari JSON raw body, $_POST, atau $_GET
$jsonInput = json_decode(file_get_contents('php://input'), true);

$latitude  = null;
$longitude = null;
$speed     = 0.0;

if (is_array($jsonInput)) {
    $latitude  = $jsonInput['latitude'] ?? $jsonInput['lat'] ?? null;
    $longitude = $jsonInput['longitude'] ?? $jsonInput['lng'] ?? $jsonInput['long'] ?? null;
    $speed     = $jsonInput['speed'] ?? 0.0;
} else {
    $latitude  = $_POST['latitude'] ?? $_POST['lat'] ?? $_GET['latitude'] ?? $_GET['lat'] ?? null;
    $longitude = $_POST['longitude'] ?? $_POST['lng'] ?? $_POST['long'] ?? $_GET['longitude'] ?? $_GET['lng'] ?? $_GET['long'] ?? null;
    $speed     = $_POST['speed'] ?? $_GET['speed'] ?? 0.0;
}

// Validasi input
if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter latitude dan longitude wajib diisi. Contoh: ?lat=-6.2088&lng=106.8456'
    ], JSON_PRETTY_PRINT);
    exit();
}

$latFloat = (float)$latitude;
$lngFloat = (float)$longitude;
$speedFloat = (float)$speed;

// Validasi rentang koordinat GPS
if ($latFloat < -90.0 || $latFloat > 90.0 || $lngFloat < -180.0 || $lngFloat > 180.0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koordinat tidak valid. Latitude (-90 s.d 90), Longitude (-180 s.d 180).'
    ], JSON_PRETTY_PRINT);
    exit();
}

try {
    // Deteksi struktur kolom di tabel loc (latitude/longitude atau lat/lng)
    $stmtCols = $pdo->query("SHOW COLUMNS FROM `loc`");
    $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);
    
    $latCol = in_array('latitude', $columns) ? 'latitude' : (in_array('lat', $columns) ? 'lat' : 'latitude');
    $lngCol = in_array('longitude', $columns) ? 'longitude' : (in_array('lng', $columns) ? 'lng' : 'longitude');
    $hasSpeed = in_array('speed', $columns);
    $hasCreatedAt = in_array('created_at', $columns) || in_array('timestamp', $columns) || in_array('time', $columns);

    $timeCol = in_array('created_at', $columns) ? 'created_at' : (in_array('timestamp', $columns) ? 'timestamp' : (in_array('time', $columns) ? 'time' : null));

    if ($hasSpeed) {
        if ($timeCol) {
            $sql = "INSERT INTO `loc` (`{$latCol}`, `{$lngCol}`, `speed`, `{$timeCol}`) VALUES (:lat, :lng, :speed, NOW())";
        } else {
            $sql = "INSERT INTO `loc` (`{$latCol}`, `{$lngCol}`, `speed`) VALUES (:lat, :lng, :speed)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':lat'   => $latFloat,
            ':lng'   => $lngFloat,
            ':speed' => $speedFloat
        ]);
    } else {
        if ($timeCol) {
            $sql = "INSERT INTO `loc` (`{$latCol}`, `{$lngCol}`, `{$timeCol}`) VALUES (:lat, :lng, NOW())";
        } else {
            $sql = "INSERT INTO `loc` (`{$latCol}`, `{$lngCol}`) VALUES (:lat, :lng)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':lat' => $latFloat,
            ':lng' => $lngFloat
        ]);
    }

    $insertId = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        'status'  => 'success',
        'message' => 'Data GPS berhasil disimpan',
        'data'    => [
            'id'        => (int)$insertId,
            'latitude'  => $latFloat,
            'longitude' => $lngFloat,
            'speed'     => $speedFloat,
            'saved_at'  => date('Y-m-d H:i:s')
        ]
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal menyimpan data ke database: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
