<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Peta Lokasi Laporan</title>
  <link rel="stylesheet" href="style.css">
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>

  <h1>Peta Titik Laporan</h1>
  <div id="map"></div>

  <!-- Leaflet JS -->
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    // Inisialisasi peta, koordinat awal = Jember
    const map = L.map('map').setView([-8.1844, 113.6681], 13);

    // Tile layer dari OpenStreetMap (gratis, no API key)
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Data titik-titik (bisa diganti data dari backend/API)
    const laporan = [
      { lat: -8.1844, lng: 113.6681, judul: "Jalan Rusak", ket: "Jl. Gajah Mada" },
      { lat: -8.1700, lng: 113.7200, judul: "Lampu Mati", ket: "Jl. Kalimantan" },
      { lat: -8.2000, lng: 113.6500, judul: "Sampah Menumpuk", ket: "Pasar Tanjung" }
    ];

    // Loop tambahkan marker + popup
    laporan.forEach(item => {
      L.marker([item.lat, item.lng])
        .addTo(map)
        .bindPopup(`<b>${item.judul}</b><br>${item.ket}`);
    });
  </script>

</body>
</html>