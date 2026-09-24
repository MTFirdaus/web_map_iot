<?php
/**
 * API Get GPS Points
 * Endpoint untuk mengambil data titik koordinat lokasi dari database my_map_project tabel loc
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';

try {
    // Deteksi nama kolom di tabel loc agar fleksibel
    $stmtCols = $pdo->query("SHOW COLUMNS FROM `loc`");
    $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

    $idCol    = in_array('id', $columns) ? 'id' : $columns[0];
    $latCol   = in_array('latitude', $columns) ? 'latitude' : (in_array('lat', $columns) ? 'lat' : null);
    $lngCol   = in_array('longitude', $columns) ? 'longitude' : (in_array('lng', $columns) ? 'lng' : (in_array('long', $columns) ? 'long' : null));
    $speedCol = in_array('speed', $columns) ? 'speed' : null;
    $timeCol  = in_array('created_at', $columns) ? 'created_at' : (in_array('timestamp', $columns) ? 'timestamp' : (in_array('time', $columns) ? 'time' : null));

    if (!$latCol || !$lngCol) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Kolom latitude/longitude tidak ditemukan pada tabel loc.'
        ], JSON_PRETTY_PRINT);
        exit();
    }

    // Opsi filter dari parameter URL
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    if ($limit <= 0) $limit = 100;

    $isLatestOnly = isset($_GET['latest']) && ($_GET['latest'] == '1' || $_GET['latest'] == 'true');

    if ($isLatestOnly) {
        $sql = "SELECT * FROM `loc` ORDER BY `{$idCol}` DESC LIMIT 1";
    } else {
        $sql = "SELECT * FROM `loc` ORDER BY `{$idCol}` DESC LIMIT " . $limit;
    }

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format output
    $points = [];
    foreach ($rows as $row) {
        $points[] = [
            'id'        => (int)($row[$idCol] ?? 0),
            'latitude'  => (float)($row[$latCol] ?? 0),
            'longitude' => (float)($row[$lngCol] ?? 0),
            'speed'     => $speedCol ? (float)($row[$speedCol] ?? 0) : 0,
            'created_at'=> $timeCol ? ($row[$timeCol] ?? '') : null
        ];
    }

    // Reorder data dari terlama ke terbaru jika mengambil multiple points (bagus untuk jalur polyline)
    if (!$isLatestOnly) {
        $points = array_reverse($points);
    }

    echo json_encode([
        'status' => 'success',
        'count'  => count($points),
        'data'   => $points
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal mengambil data dari database: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
