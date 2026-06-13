# SIG Mapping Tanah & Jalan

Aplikasi pemetaan **point & click**: gambar bidang **tanah** (polygon) dan **jalan** (garis)
langsung di peta. **Luas, keliling, dan panjang dihitung otomatis** (di klien saat menggambar,
dan diverifikasi ulang di server saat disimpan).

## Teknologi
- **Frontend:** TailwindCSS (CDN), Leaflet.js + Leaflet.draw
- **Backend:** Vanilla PHP (PDO), pola REST sederhana
- **Database:** MySQL 8

## Struktur
```
sig-02/
├── index.php            UI peta + sidebar + modal form
├── assets/app.js        Logika peta, CRUD, perhitungan klien
├── api/
│   ├── tanah.php        Endpoint CRUD tanah (Polygon)
│   └── jalan.php        Endpoint CRUD jalan (LineString)
├── includes/
│   ├── crud.php         Handler CRUD generik
│   └── geo.php          Haversine + luas geodesik (perhitungan server)
├── config/database.php  Koneksi PDO
└── schema.sql           Skema database
```

## Cara Menjalankan (Laragon)
1. **Import database** (sekali saja):
   ```
   mysql -u root < schema.sql
   ```
   atau via HeidiSQL/phpMyAdmin: jalankan isi `schema.sql`.
2. Pastikan kredensial di `config/database.php` sesuai (default Laragon: `root`, tanpa password).
3. Buka di browser:
   - Laragon (Apache): `http://sig-02.test` atau `http://localhost/sig-02`
   - Atau server bawaan PHP: `php -S 127.0.0.1:8000` lalu buka `http://127.0.0.1:8000`

## Cara Pakai
- Pakai toolbar gambar di **kanan-atas peta**:
  - **Polygon** → tambah **tanah** (luas & keliling muncul otomatis).
  - **Garis (polyline)** → tambah **jalan** (panjang muncul otomatis).
- Selesai menggambar → muncul form untuk nama/pemilik/jenis/deskripsi/warna → **Simpan**.
- **Sidebar kiri:** daftar tanah & jalan (klik untuk fokus, ✎ edit atribut, 🗑 hapus).
- **Edit geometri:** klik ikon edit (toolbar gambar), geser titik, lalu *Save* → tersimpan otomatis.
- Ganti basemap (Peta Jalan / Satelit) di pojok kanan-bawah.

## Catatan Perhitungan
- **Panjang jalan:** jumlah jarak haversine antar titik (meter, ditampilkan m / km).
- **Luas tanah:** rumus area geodesik bola (m², ditampilkan m² / ha).
- **Keliling tanah:** haversine keliling ring polygon.
- Radius bumi: WGS84 (6.378.137 m).
