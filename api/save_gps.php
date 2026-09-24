<?php
/**
 * API Save GPS Data & Reports
 * Endpoint untuk menyimpan data lokasi ke tabel locations (atau loc)
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
$deviceId  = 'WEMOS-GPS-01';

if (is_array($jsonInput)) {
    $latitude  = $jsonInput['latitude'] ?? $jsonInput['lat'] ?? null;
    $longitude = $jsonInput['longitude'] ?? $jsonInput['lng'] ?? $jsonInput['long'] ?? null;
    $deviceId  = $jsonInput['device_id'] ?? $jsonInput['device'] ?? 'WEMOS-GPS-01';
} else {
    $latitude  = $_POST['latitude'] ?? $_POST['lat'] ?? $_GET['latitude'] ?? $_GET['lat'] ?? null;
    $longitude = $_POST['longitude'] ?? $_POST['lng'] ?? $_POST['long'] ?? $_GET['longitude'] ?? $_GET['lng'] ?? $_GET['long'] ?? null;
    $deviceId  = $_POST['device_id'] ?? $_GET['device_id'] ?? 'WEMOS-GPS-01';
}

if ($latitude === null || $longitude === null || $latitude === '' || $longitude === '') {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Parameter latitude dan longitude wajib diisi. Contoh: ?lat=-8.1844&lng=113.6681'
    ], JSON_PRETTY_PRINT);
    exit();
}

$latFloat = (float)$latitude;
$lngFloat = (float)$longitude;

if ($latFloat < -90.0 || $latFloat > 90.0 || $lngFloat < -180.0 || $lngFloat > 180.0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Koordinat tidak valid. Latitude (-90 s.d 90), Longitude (-180 s.d 180).'
    ], JSON_PRETTY_PRINT);
    exit();
}

try {
    // Deteksi nama tabel: 'locations' atau 'loc'
    $tablesStmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    $hasLocationsTable = $tablesStmt->rowCount() > 0;
    $tableName = $hasLocationsTable ? 'locations' : 'loc';

    $stmtCols = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
    $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

    $latCol       = in_array('latitude', $columns) ? 'latitude' : (in_array('lat', $columns) ? 'lat' : 'latitude');
    $lngCol       = in_array('longitude', $columns) ? 'longitude' : (in_array('lng', $columns) ? 'lng' : 'longitude');
    $hasDeviceId  = in_array('device_id', $columns);
    $timeCol      = in_array('created_at', $columns) ? 'created_at' : (in_array('timestamp', $columns) ? 'timestamp' : null);

    if ($hasDeviceId) {
        if ($timeCol) {
            $sql = "INSERT INTO `{$tableName}` (`device_id`, `{$latCol}`, `{$lngCol}`, `{$timeCol}`) VALUES (:dev, :lat, :lng, NOW())";
        } else {
            $sql = "INSERT INTO `{$tableName}` (`device_id`, `{$latCol}`, `{$lngCol}`) VALUES (:dev, :lat, :lng)";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':dev' => $deviceId,
            ':lat' => $latFloat,
            ':lng' => $lngFloat
        ]);
    } else {
        if ($timeCol) {
            $sql = "INSERT INTO `{$tableName}` (`{$latCol}`, `{$lngCol}`, `{$timeCol}`) VALUES (:lat, :lng, NOW())";
        } else {
            $sql = "INSERT INTO `{$tableName}` (`{$latCol}`, `{$lngCol}`) VALUES (:lat, :lng)";
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
        'message' => 'Data lokasi berhasil disimpan ke database',
        'data'    => [
            'id'         => (int)$insertId,
            'device_id'  => $deviceId,
            'latitude'   => $latFloat,
            'longitude'  => $lngFloat,
            'created_at' => date('Y-m-d H:i:s')
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
