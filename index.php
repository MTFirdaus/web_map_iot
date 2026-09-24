<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Peta Titik Laporan - IoT GPS Tracking</title>
  
  <!-- Style CSS Internal & External -->
  <link rel="stylesheet" href="style.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body>

  <div class="container">
    
    <!-- Header Section -->
    <header class="header-box">
      <div class="header-title-area">
        <h1>
          <span class="icon"><i class="fa-solid fa-map-location-dot"></i></span>
          Peta Titik Laporan
        </h1>
        <div class="header-subtitle">
          Pemantauan Lokasi Real-Time IoT | Database: <strong>my_map_project</strong> &bull; Tabel: <strong>locations</strong>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary" onclick="seedDummyData()">
          <i class="fa-solid fa-plus-circle"></i> Tambah Data Dummy
        </button>
        <button class="btn btn-primary" onclick="loadMapPoints()">
          <i class="fa-solid fa-rotate"></i> Refresh Data
        </button>
      </div>
    </header>

    <!-- Map Container -->
    <main class="map-card">
      <div id="map"></div>
    </main>

    <!-- Data Table Section -->
    <section class="data-section">
      <div class="data-table-box">
        <div class="table-header">
          <h3><i class="fa-solid fa-list-check"></i> Daftar Titik Lokasi Laporan</h3>
          <span class="badge" id="total-badge">0 Lokasi Terdeteksi</span>
        </div>
        <table class="report-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Device ID / Judul</th>
              <th>Latitude</th>
              <th>Longitude</th>
              <th>Waktu Terdaftar</th>
            </tr>
          </thead>
          <tbody id="table-body">
            <tr>
              <td colspan="5" style="text-align: center; color: var(--text-secondary);">Memuat data titik laporan...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

  </div>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
  
  <script>
    // Helper URL API Dinamis
    const getApiUrl = (endpoint) => {
      const currentPath = window.location.pathname;
      const baseDir = currentPath.substring(0, currentPath.lastIndexOf('/') + 1);
      return baseDir + 'api/' + endpoint;
    };

    // 1. Inisialisasi Peta Leaflet (Default koordinat Jember)
    const map = L.map('map', {
      zoomControl: true
    }).setView([-8.1844, 113.6681], 13);

    // 2. Tile Layer OpenStreetMap Standard (100% Gratis & Tanpa API Key)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom: 19
    }).addTo(map);

    // Dynamic Markers Group & Polyline
    let markersLayer = L.layerGroup().addTo(map);
    let polylineLayer = L.polyline([], { color: '#2563eb', weight: 4, opacity: 0.7 }).addTo(map);

    // 3. Fungsi Ambil Data dari Backend API (tabel locations)
    async function loadMapPoints() {
      try {
        const apiUrl = getApiUrl('get_points.php?limit=100');
        const response = await fetch(apiUrl);
        const result = await response.json();

        if (result.status === 'success' && result.data.length > 0) {
          const points = result.data;

          // Bersihkan marker lama
          markersLayer.clearLayers();
          const latLngs = [];
          const tableBody = document.getElementById('table-body');
          tableBody.innerHTML = '';

          // Render marker & baris tabel untuk setiap titik laporan
          points.forEach((pt) => {
            const lat = parseFloat(pt.latitude);
            const lng = parseFloat(pt.longitude);
            const devId = pt.device_id || `Laporan #${pt.id}`;
            const timeStr = pt.created_at || 'Baru Saja';
            const judul = pt.judul || devId;

            latLngs.push([lat, lng]);

            // Buat Leaflet Marker + Popup Informasi
            const marker = L.marker([lat, lng]);
            marker.bindPopup(`
              <div class="popup-info">
                <h4><i class="fa-solid fa-location-pin"></i> ${judul}</h4>
                <p><strong>Device ID:</strong> <code>${devId}</code></p>
                <p><strong>Lat:</strong> <code>${lat.toFixed(6)}</code></p>
                <p><strong>Lng:</strong> <code>${lng.toFixed(6)}</code></p>
                <p><strong>Waktu:</strong> ${timeStr}</p>
              </div>
            `);
            markersLayer.addLayer(marker);

            // Tambahkan baris ke Tabel UI
            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>${pt.id}</td>
              <td><strong>${devId}</strong></td>
              <td><code>${lat.toFixed(6)}</code></td>
              <td><code>${lng.toFixed(6)}</code></td>
              <td>${timeStr}</td>
            `;
            tableBody.appendChild(tr);
          });

          // Hubungkan antar titik dengan garis polyline jika lebih dari 1 titik
          polylineLayer.setLatLngs(latLngs);

          // Update badge counter
          document.getElementById('total-badge').innerText = `${points.length} Lokasi Terdaftar`;

          // Center map ke titik lokasi terbaru
          const latest = points[points.length - 1];
          map.panTo([latest.latitude, latest.longitude]);

        } else {
          document.getElementById('table-body').innerHTML = `
            <tr>
              <td colspan="5" style="text-align: center; color: var(--text-secondary);">Belum ada data di tabel locations. Klik "Tambah Data Dummy" untuk mengisi data contoh.</td>
            </tr>
          `;
        }
      } catch (err) {
        console.error("Gagal memuat titik laporan:", err);
      }
    }

    // 4. Fungsi Tambah Data Dummy ke Database
    async function seedDummyData() {
      try {
        const apiUrl = getApiUrl('seed_dummy.php');
        const response = await fetch(apiUrl);
        const resData = await response.json();
        if (resData.status === 'success') {
          loadMapPoints();
        } else {
          alert('Gagal tambah data dummy: ' + resData.message);
        }
      } catch (e) {
        alert('Gagal menghubungi API seed data.');
      }
    }

    // Auto Refresh setiap 5 Detik
    setInterval(loadMapPoints, 5000);

    // Initial Load
    loadMapPoints();
  </script>

</body>
</html>