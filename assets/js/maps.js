const map = L.map('map').setView([-8.1844, 113.6681], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

fetch('/api/laporan') // sesuaikan endpoint API kamu
  .then(res => res.json())
  .then(data => {
    data.forEach(item => {
      L.marker([item.lat, item.lng])
        .addTo(map)
        .bindPopup(`<b>${item.judul}</b><br>${item.ket}`);
    });
  })
  .catch(err => console.error('Gagal ambil data:', err));