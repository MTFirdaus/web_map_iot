<?php
/**
 * API Seed Dummy Data ke Tabel locations
 */

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . '/../config/db.php';

try {
    // Deteksi nama tabel: 'locations' atau 'loc'
    $tablesStmt = $pdo->query("SHOW TABLES LIKE 'locations'");
    $hasLocationsTable = $tablesStmt->rowCount() > 0;
    $tableName = $hasLocationsTable ? 'locations' : 'loc';

    $stmtCols = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
    $columns = $stmtCols->fetchAll(PDO::FETCH_COLUMN);

    $hasDeviceId = in_array('device_id', $columns);
    $latCol      = in_array('latitude', $columns) ? 'latitude' : 'lat';
    $lngCol      = in_array('longitude', $columns) ? 'longitude' : 'lng';
    $timeCol     = in_array('created_at', $columns) ? 'created_at' : (in_array('timestamp', $columns) ? 'timestamp' : null);

    // Sample dummy data lokasi (daerah Jember)
    $dummies = [
        ['device_id' => 'WEMOS-001', 'lat' => -8.1844, 'lng' => 113.6681],
        ['device_id' => 'WEMOS-002', 'lat' => -8.1700, 'lng' => 113.7200],
        ['device_id' => 'WEMOS-003', 'lat' => -8.2000, 'lng' => 113.6500],
        ['device_id' => 'WEMOS-004', 'lat' => -8.1650, 'lng' => 113.7050]
    ];

    $count = 0;
    foreach ($dummies as $d) {
        if ($hasDeviceId) {
            if ($timeCol) {
                $sql = "INSERT INTO `{$tableName}` (`device_id`, `{$latCol}`, `{$lngCol}`, `{$timeCol}`) VALUES (:dev, :lat, :lng, NOW())";
            } else {
                $sql = "INSERT INTO `{$tableName}` (`device_id`, `{$latCol}`, `{$lngCol}`) VALUES (:dev, :lat, :lng)";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':dev' => $d['device_id'], ':lat' => $d['lat'], ':lng' => $d['lng']]);
        } else {
            if ($timeCol) {
                $sql = "INSERT INTO `{$tableName}` (`{$latCol}`, `{$lngCol}`, `{$timeCol}`) VALUES (:lat, :lng, NOW())";
            } else {
                $sql = "INSERT INTO `{$tableName}` (`{$latCol}`, `{$lngCol}`) VALUES (:lat, :lng)";
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':lat' => $d['lat'], ':lng' => $d['lng']]);
        }
        $count++;
    }

    echo json_encode([
        'status'  => 'success',
        'message' => "Berhasil menambahkan {$count} data dummy ke tabel {$tableName}"
    ], JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal seed data: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
