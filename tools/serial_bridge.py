"""
Serial Bridge Script for Wemos D1 + GY-GPS6MV2 (Connected via USB Data Cable to Laptop)
Script ini membaca Serial COM Port dari Wemos dan mengirimkan koordinat GPS secara otomatis ke database localhost!

Cara Menggunakan:
1. Install modul pyserial & requests (jika belum):
   pip install pyserial requests
2. Jalankan script ini:
   python tools/serial_bridge.py
"""

import re
import time
import requests
import serial
import serial.tools.list_ports

# Konfigurasi Server API Localhost
API_URL = "http://localhost/web_map_iot/api/save_gps.php"
BAUDRATE = 115200

def find_com_port():
    ports = list(serial.tools.list_ports.comports())
    if not ports:
        print("❌ Tidak ada COM Port (Wemos USB) yang terdeteksi.")
        return None
    
    print("🔌 COM Port yang tersedia:")
    for p in ports:
        print(f"   - {p.device}: {p.description}")
    
    # Ambil port pertama atau sesuaikan dengan Wemos
    return ports[0].device

def main():
    port = find_com_port()
    if not port:
        print("Gagal menemukan port Wemos. Pastikan kabel data USB terpasang dengan benar.")
        return

    print(f"\n🚀 Membuka Serial Bridge di {port} dengan baudrate {BAUDRATE}...")
    try:
        ser = serial.Serial(port, BAUDRATE, timeout=1)
        time.sleep(2)
        print("✅ Terhubung ke Serial Wemos! Membaca stream data GPS...\n")

        # Regex untuk mencocokkan baris: 📍 [GPS DATA] Lat: -6.208800 | Lng: 106.845600 | Speed: 0.0 km/h
        pattern = re.compile(r"Lat:\s*([-\d.]+)\s*\|\s*Lng:\s*([-\d.]+)(?:\s*\|\s*Speed:\s*([-\d.]+))?")

        while True:
            if ser.in_waiting > 0:
                line = ser.readline().decode('utf-8', errors='ignore').strip()
                if line:
                    print(f"[SERIAL] {line}")
                    
                    match = pattern.search(line)
                    if match:
                        lat = float(match.group(1))
                        lng = float(match.group(2))
                        speed = float(match.group(3)) if match.group(3) else 0.0

                        print(f"📡 Mengirim ke Database -> Lat: {lat}, Lng: {lng}, Speed: {speed}")
                        
                        try:
                            res = requests.post(API_URL, json={
                                "latitude": lat,
                                "longitude": lng,
                                "speed": speed
                            }, timeout=3)
                            
                            if res.status_code in (200, 201):
                                print(f"✅ Data Tersimpan di database 'my_map_project'! Respon: {res.text}")
                            else:
                                print(f"⚠️ Gagal menyimpan. Respon: {res.text}")
                        except Exception as e:
                            print(f"❌ HTTP Request Error: {e}")

    except KeyboardInterrupt:
        print("\n👋 Serial bridge dihentikan oleh pengguna.")
    except Exception as e:
        print(f"\n❌ Error Serial Port: {e}")

if __name__ == "__main__":
    main()
