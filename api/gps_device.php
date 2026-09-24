<?php
/**
 * Dedicated API Endpoint untuk Alat Hardware GPS (ESP8266 / ESP32 / SIM800L / NodeMCU)
 * Endpoint: http://localhost/web_map_iot/api/gps_device.php
 * Menerima parameter: lat (atau latitude), lng (atau longitude), speed (opsional)
 * Method: HTTP POST / GET / JSON
 */

// Memanggil logika utama penyimpanan data GPS
require_once __DIR__ . '/save_gps.php';
?>
