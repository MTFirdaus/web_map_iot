<?php
/**
 * API Get GPS Points & Reports
 * Query dari database my_map_project tabel locations (atau loc)
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
    // Deteksi nama tabel: 'locations' atau 'loc'
    $tablesStmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    $hasLocationsTable = $tablesStmt->rowCount() > 0;
    $tableName = $hasLocationsTable ? 'locations' : 'loc';

    // Deteksi nama kolom di tabel
    $stmtCols = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
    $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

    $idCol       = in_array('id', $columns) ? 'id' : $columns[0];
    $deviceIdCol = in_array('device_id', $columns) ? 'device_id' : null;
    $latCol      = in_array('latitude', $columns) ? 'latitude' : (in_array('lat', $columns) ? 'lat' : null);
    $lngCol      = in_array('longitude', $columns) ? 'longitude' : (in_array('lng', $columns) ? 'lng' : (in_array('long', $columns) ? 'long' : null));
    $timeCol     = in_array('created_at', $columns) ? 'created_at' : (in_array('timestamp', $columns) ? 'timestamp' : (in_array('time', $columns) ? 'time' : null));

    if (!$latCol || !$lngCol) {
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => "Kolom latitude/longitude tidak ditemukan pada tabel {$tableName}."
        ], JSON_PRETTY_PRINT);
        exit();
    }

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    if ($limit <= 0) $limit = 100;

    $selectCols = "`{$idCol}`, `{$latCol}`, `{$lngCol}`";
    if ($deviceIdCol) $selectCols .= ", `{$deviceIdCol}`";
    if ($timeCol) $selectCols .= ", `{$timeCol}`";

    $sql = "SELECT {$selectCols} FROM `{$tableName}` ORDER BY `{$idCol}` DESC LIMIT " . $limit;
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $points = [];
    foreach ($rows as $row) {
        $points[] = [
            'id'         => (int)($row[$idCol] ?? 0),
            'device_id'  => $deviceIdCol ? ($row[$deviceIdCol] ?? 'GPS-WEMOS-01') : 'GPS-WEMOS-01',
            'latitude'   => (float)($row[$latCol] ?? 0),
            'longitude'  => (float)($row[$lngCol] ?? 0),
            'created_at' => $timeCol ? ($row[$timeCol] ?? '') : date('Y-m-d H:i:s')
        ];
    }

    // Jika database kosong, sediakan dummy data awal (Jember & sekitarnya) agar peta langsung tampil menarik!
    if (empty($points)) {
        $points = [
            [
                'id'         => 1,
                'device_id'  => 'Laporan #001',
                'latitude'   => -8.1844,
                'longitude'  => 113.6681,
                'judul'      => 'Titik Laporan 1 - Alun-Alun Jember',
                'ket'        => 'Jl. Gajah Mada, Jember',
                'created_at' => date('Y-m-d H:i:s', strtotime('-25 mins'))
            ],
            [
                'id'         => 2,
                'device_id'  => 'Laporan #002',
                'latitude'   => -8.1700,
                'longitude'  => 113.7200,
                'judul'      => 'Titik Laporan 2 - Kampus UNEJ',
                'ket'        => 'Jl. Kalimantan, Jember',
                'created_at' => date('Y-m-d H:i:s', strtotime('-10 mins'))
            ],
            [
                'id'         => 3,
                'device_id'  => 'Laporan #003',
                'latitude'   => -8.2000,
                'longitude'  => 113.6500,
                'judul'      => 'Titik Laporan 3 - Pasar Tanjung',
                'ket'        => 'Jl. Samanhudi, Jember',
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    // Newest record first (DESC)
    echo json_encode([
        'status' => 'success',
        'count'  => count($points),
        'table'  => $tableName,
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
