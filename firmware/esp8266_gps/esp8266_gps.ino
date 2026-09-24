/*
 * ======================================================================================
 * SKETCH FIRMWARE WEMOS D1 (ESP8266) + MODUL GY-GPS6MV2 (NEO-6M)
 * Project: IoT GPS Tracking Real-time
 * Web Server API: http://<IP_LAPTOP_ANDA>/web_map_iot/api/save_gps.php
 * ======================================================================================
 *
 * SKEMA SKEMA PIN KABEL (WEMOS D1 MINI <-> GY-GPS6MV2):
 * --------------------------------------------------------
 *  Wemos D1 Mini/R1       Modul GY-GPS6MV2
 *  ----------------       ----------------
 *  5V (atau 3.3V)   --->  VCC
 *  GND              --->  GND
 *  D5 (GPIO14)      --->  TX  (Transmit GPS ke RX Wemos)
 *  D6 (GPIO12)      --->  RX  (Receive Wemos ke TX GPS)
 * --------------------------------------------------------
 */

#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <SoftwareSerial.h>
#include <TinyGPS++.h>

// --- KONFIGURASI WIFI & LAPTOP ---
const char* wifi_ssid     = "NAMA_WIFI_LAPTOP_ATAU_HOTSPOT"; // SSID WiFi Laptop/Hotspot
const char* wifi_password = "PASSWORD_WIFI";                  // Password WiFi

// GANTI DENGAN IP LAPTOP ANDA (Cek via cmd laptop: `ipconfig` -> IPv4 Address)
// Contoh IP: http://192.168.1.15/web_map_iot/api/save_gps.php
const char* server_api_url = "http://192.168.1.10/web_map_iot/api/save_gps.php";

// --- PINOUT SOFTWAERIAL UNTUK WEMOS ---
#define RX_PIN D5 // D5 Wemos dihubungkan ke PIN TX Modul GY-GPS6MV2
#define TX_PIN D6 // D6 Wemos dihubungkan ke PIN RX Modul GY-GPS6MV2

SoftwareSerial gpsSerial(RX_PIN, TX_PIN);
TinyGPSPlus gps;

unsigned long lastSendTime = 0;
const unsigned long sendInterval = 4000; // Kirim data lokasi setiap 4 detik

void setup() {
  // Indikator LED Onboard Wemos (D4 / LED_BUILTIN)
  pinMode(LED_BUILTIN, OUTPUT);
  digitalWrite(LED_BUILTIN, HIGH); // Off (Active Low)

  // Serial Monitor Baudrate (Kabel Data USB ke Laptop)
  Serial.begin(115200);
  
  // Serial GPS GY-GPS6MV2 (Baudrate default NEO-6M = 9600)
  gpsSerial.begin(9600);

  Serial.println("\n==================================================");
  Serial.println("   WEMOS D1 + GY-GPS6MV2 - REALTIME GPS TRACKER   ");
  Serial.println("==================================================");
  Serial.print("Connecting to WiFi: ");
  Serial.println(wifi_ssid);

  WiFi.mode(WIFI_STA);
  WiFi.begin(wifi_ssid, wifi_password);

  int attempt = 0;
  while (WiFi.status() != WL_CONNECTED && attempt < 30) {
    delay(500);
    Serial.print(".");
    attempt++;
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ WiFi Terhubung!");
    Serial.print("IP Wemos: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println("\n⚠️ WiFi belum terhubung. Wemos akan tetap membaca sinyal GPS dari Serial USB.");
  }
}

void loop() {
  // Pembacaan stream serial dari GY-GPS6MV2
  while (gpsSerial.available() > 0) {
    gps.encode(gpsSerial.read());
  }

  // Interval pengiriman data GPS ke server lokal laptop
  if (millis() - lastSendTime >= sendInterval) {
    lastSendTime = millis();

    if (gps.location.isValid()) {
      float latitude  = gps.location.lat();
      float longitude = gps.location.lng();
      float speed     = gps.speed.kmph();

      // Output ke Serial Monitor USB Laptop (Bisa dibaca via Arduino IDE / Python Bridge)
      Serial.print("📍 [GPS DATA] Lat: ");
      Serial.print(latitude, 6);
      Serial.print(" | Lng: ");
      Serial.print(longitude, 6);
      Serial.print(" | Speed: ");
      Serial.print(speed, 1);
      Serial.println(" km/h");

      // Kirim data via HTTP POST jika WiFi laptop terhubung
      if (WiFi.status() == WL_CONNECTED) {
        kirimKeServer(latitude, longitude, speed);
      }
    } else {
      Serial.print("⏳ Menunggu Sinyal Satelit GPS... Satelit Terdeteksi: ");
      Serial.println(gps.satellites.value());
    }
  }
}

void kirimKeServer(float lat, float lng, float speed) {
  WiFiClient client;
  HTTPClient http;

  http.begin(client, server_api_url);
  http.addHeader("Content-Type", "application/json");

  // Format JSON payload
  String jsonPayload = "{";
  jsonPayload += "\"latitude\":" + String(lat, 6) + ",";
  jsonPayload += "\"longitude\":" + String(lng, 6) + ",";
  jsonPayload += "\"speed\":" + String(speed, 2);
  jsonPayload += "}";

  // Blink LED saat mengirim
  digitalWrite(LED_BUILTIN, LOW);
  int httpCode = http.POST(jsonPayload);
  digitalWrite(LED_BUILTIN, HIGH);

  if (httpCode > 0) {
    String response = http.getString();
    Serial.print("✅ Status Kirim HTTP: ");
    Serial.println(httpCode);
    Serial.print("📩 Respon Server: ");
    Serial.println(response);
  } else {
    Serial.print("❌ HTTP Error: ");
    Serial.println(http.errorToString(httpCode).c_str());
  }

  http.end();
}