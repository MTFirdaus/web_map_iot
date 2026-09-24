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
          <span class="icon"><i class="fa-solid fa-satellite-dish"></i></span>
          IoT GPS Live Tracking
          <span class="live-badge"><span class="live-dot"></span> LIVE UPDATE</span>
        </h1>
        <div class="header-subtitle">
          Pemantauan Lokasi Real-Time &bull; Database: <strong>my_map_project</strong> &bull; Mode: <strong>Single Row Update</strong>
        </div>
      </div>
      <div class="header-actions">
        <button class="btn btn-secondary" onclick="centerToDevice()">
          <i class="fa-solid fa-crosshairs"></i> Pusatkan Alat
        </button>
        <button class="btn btn-primary" onclick="loadMapPoints()">
          <i class="fa-solid fa-rotate"></i> Refresh Manual
        </button>
      </div>
    </header>

    <!-- Realtime Telemetry HUD Bar -->
    <section class="telemetry-grid">
      <div class="telemetry-card">
        <div class="telemetry-icon icon-cyan">
          <i class="fa-solid fa-microchip"></i>
        </div>
        <div class="telemetry-info">
          <span class="telemetry-label">Device Tracker</span>
          <span class="telemetry-value" id="hud-device">WEMOS-GPS-01</span>
        </div>
      </div>

      <div class="telemetry-card">
        <div class="telemetry-icon icon-blue">
          <i class="fa-solid fa-location-dot"></i>
        </div>
        <div class="telemetry-info">
          <span class="telemetry-label">Latitude Terkini</span>
          <span class="telemetry-value" id="hud-lat">-0.000000</span>
        </div>
      </div>

      <div class="telemetry-card">
        <div class="telemetry-icon icon-green">
          <i class="fa-solid fa-map-pin"></i>
        </div>
        <div class="telemetry-info">
          <span class="telemetry-label">Longitude Terkini</span>
          <span class="telemetry-value" id="hud-lng">0.000000</span>
        </div>
      </div>

      <div class="telemetry-card">
        <div class="telemetry-icon icon-purple">
          <i class="fa-solid fa-clock-rotate-left"></i>
        </div>
        <div class="telemetry-info">
          <span class="telemetry-label">Terakhir Diperbarui</span>
          <span class="telemetry-value" id="hud-time" style="font-size: 1rem;">Menghubungkan...</span>
        </div>
      </div>
    </section>

    <!-- Map Container -->
    <main class="map-card">
      <div class="map-toolbar">
        <div class="map-layer-selector">
          <button class="layer-btn active" id="btn-layer-dark" onclick="setTileLayer('dark')">
            <i class="fa-solid fa-moon"></i> Cyber Dark
          </button>
          <button class="layer-btn" id="btn-layer-voyager" onclick="setTileLayer('voyager')">
            <i class="fa-solid fa-map"></i> Street Map
          </button>
          <button class="layer-btn" id="btn-layer-satellite" onclick="setTileLayer('satellite')">
            <i class="fa-solid fa-satellite"></i> Satelit
          </button>
        </div>

        <div class="map-quick-actions">
          <label class="toggle-switch-label">
            <input type="checkbox" id="auto-center-toggle" checked style="accent-color: #3b82f6;">
            <span>Auto Follow Pergerakan Alat</span>
          </label>
        </div>
      </div>

      <div id="map"></div>
    </main>

    <!-- Data Table Section -->
    <section class="data-section">
      <div class="data-table-box">
        <div class="table-header">
          <h3><i class="fa-solid fa-list-check"></i> Status Data Lokasi Alat Terdaftar</h3>
          <span class="badge" id="total-badge" style="color: var(--cyan-neon); font-size: 0.85rem; font-weight: 700;">1 Device Terpantau</span>
        </div>
        <table class="report-table">
          <thead>
            <tr>
              <th>ID Baris</th>
              <th>Device ID</th>
              <th>Latitude</th>
              <th>Longitude</th>
              <th>Waktu Update Terakhir</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="table-body">
            <tr>
              <td colspan="6" style="text-align: center; color: var(--text-muted);">Memuat koordinat GPS langsung dari database...</td>
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

    // 1. Inisialisasi Peta Leaflet (Default Jember)
    const map = L.map('map', {
      zoomControl: true
    }).setView([-8.1577, 113.7229], 16);

    // 2. Tile Layers Modern (100% Bebas Watermark & Tanpa API Key)
    const tileLayers = {
      dark: L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19
      }),
      voyager: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19
      }),
      satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
        maxZoom: 19
      })
    };

    const mapElement = document.getElementById('map');

    // Default Tile: Cyber Dark (OpenStreetMap + Dark Filter)
    mapElement.classList.add('cyber-dark-mode');
    let currentTileLayer = tileLayers.dark.addTo(map);

    function setTileLayer(type) {
      map.removeLayer(currentTileLayer);
      currentTileLayer = tileLayers[type].addTo(map);

      // Terapkan filter dark hanya jika mode Cyber Dark dipilih
      if (type === 'dark') {
        mapElement.classList.add('cyber-dark-mode');
      } else {
        mapElement.classList.remove('cyber-dark-mode');
      }

      document.querySelectorAll('.layer-btn').forEach(b => b.classList.remove('active'));
      const activeBtn = document.getElementById('btn-layer-' + type);
      if (activeBtn) activeBtn.classList.add('active');
    }

    // Dynamic Markers Group & Accuracy Halo
    let markersLayer = L.layerGroup().addTo(map);
    let lastLatestCoords = null;

    // Helper Buat Modern Neon Radar Marker
    function createModernPulseIcon(label) {
      return L.divIcon({
        className: 'custom-leaflet-div-icon',
        html: `
          <div class="modern-gps-marker">
            <div class="pulse-ring"></div>
            <div class="pulse-ring-inner"></div>
            <div class="marker-core">
              <i class="fa-solid fa-location-arrow"></i>
            </div>
            <div class="marker-floating-label">● ${label}</div>
          </div>
        `,
        iconSize: [54, 54],
        iconAnchor: [27, 27]
      });
    }

    // 3. Fungsi Ambil Data dari Backend API (tabel locations)
    async function loadMapPoints() {
      try {
        const apiUrl = getApiUrl('get_points.php?limit=10&t=' + Date.now());
        const response = await fetch(apiUrl);
        const result = await response.json();

        if (result.status === 'success' && result.data.length > 0) {
          const points = result.data;

          // Bersihkan layer marker lama
          markersLayer.clearLayers();
          const tableBody = document.getElementById('table-body');
          tableBody.innerHTML = '';

          // Titik paling akhir (terbaru)
          const latest = points[0]; // Diurutkan DESC dari API
          const lat = parseFloat(latest.latitude);
          const lng = parseFloat(latest.longitude);
          const devId = latest.device_id || `WEMOS-GPS-01`;
          const timeStr = latest.created_at || 'Baru Saja';

          lastLatestCoords = [lat, lng];

          // Update HUD Telemetri
          document.getElementById('hud-device').innerText = devId;
          document.getElementById('hud-lat').innerText = lat.toFixed(6);
          document.getElementById('hud-lng').innerText = lng.toFixed(6);
          document.getElementById('hud-time').innerText = timeStr.split(' ')[1] || timeStr;

          // Pasang Marker Modern dengan Radar Pulse
          const customMarker = L.marker([lat, lng], {
            icon: createModernPulseIcon(devId)
          });

          customMarker.bindPopup(`
            <div class="popup-info">
              <h4><i class="fa-solid fa-satellite-dish"></i> ${devId}</h4>
              <p><strong>Status:</strong> <span style="color:#10b981; font-weight:700;">● Aktif Terhubung</span></p>
              <p><strong>Latitude:</strong> <code>${lat.toFixed(6)}</code></p>
              <p><strong>Longitude:</strong> <code>${lng.toFixed(6)}</code></p>
              <p><strong>Terakhir Update:</strong> <code>${timeStr}</code></p>
            </div>
          `);
          markersLayer.addLayer(customMarker);

          // Render baris ke Tabel UI
          points.forEach((pt) => {
            const pLat = parseFloat(pt.latitude);
            const pLng = parseFloat(pt.longitude);
            const pDevId = pt.device_id || `WEMOS-GPS-01`;
            const pTime = pt.created_at || 'Baru Saja';

            const tr = document.createElement('tr');
            tr.innerHTML = `
              <td>#${pt.id}</td>
              <td><strong style="color: #67e8f9;">${pDevId}</strong></td>
              <td><code>${pLat.toFixed(6)}</code></td>
              <td><code>${pLng.toFixed(6)}</code></td>
              <td>${pTime}</td>
              <td>
                <button class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.78rem;" onclick="map.flyTo([${pLat}, ${pLng}], 17)">
                  <i class="fa-solid fa-crosshairs"></i> Lihat
                </button>
              </td>
            `;
            tableBody.appendChild(tr);
          });

          document.getElementById('total-badge').innerText = `${points.length} Baris Lokasi Terdaftar (Update In-Place)`;

          // Auto center jika toggle aktif
          const autoCenter = document.getElementById('auto-center-toggle').checked;
          if (autoCenter) {
            map.panTo([lat, lng], { animate: true, duration: 1.0 });
          }

        }
      } catch (err) {
        console.error("Gagal memuat koordinat GPS:", err);
      }
    }

    function centerToDevice() {
      if (lastLatestCoords) {
        map.flyTo(lastLatestCoords, 17, { animate: true, duration: 1.2 });
      }
    }

    // Auto Refresh setiap 2.5 Detik agar update Wemos langsung muncul seketika!
    setInterval(loadMapPoints, 2500);

    // Initial Load
    loadMapPoints();
  </script>

</body>
</html>