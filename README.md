# Proyek SIG

Kumpulan proyek **Sistem Informasi Geografis (SIG)** berbasis web yang dijalankan di atas Laragon (Apache + PHP + MySQL).

## Proyek

| Folder | Judul | Deskripsi |
|--------|-------|-----------|
| [`sig-01/`](sig-01/) | WebGIS SPBU | Peta sebaran Stasiun Pengisian Bahan Bakar Umum (SPBU) dengan CRUD dan filter buka-24-jam. |
| [`sig-02/`](sig-02/) | SIG Mapping Tanah & Jalan | Pemetaan point & click: gambar polygon tanah dan garis jalan, luas/panjang dihitung otomatis. |
| [`sig-03/`](sig-03/) | Poverty-Mapping GIS | Distribusi bantuan sosial: peta sebaran rumah penerima, pusat distribusi (masjid/mushola), dan laporan jangkauan radius. |

## Teknologi

- **Frontend:** Leaflet.js, TailwindCSS
- **Backend:** PHP (PDO), REST sederhana
- **Database:** MySQL 8
- **Server lokal:** Laragon (Apache)

## Cara Menjalankan

### Prasyarat
- [Laragon](https://laragon.org/) sudah terpasang dan berjalan
- Folder proyek ini berada di `C:\laragon\www\`

### Setup database (per proyek)

| Proyek | Database | File SQL |
|--------|----------|----------|
| sig-01 | `sig_spbu` | `sig-01/setup.sql` |
| sig-02 | `sig_mapping` | `sig-02/schema.sql` |
| sig-03 | `sig_bansos` | `sig-03/database.sql` |

Import via phpMyAdmin atau terminal:

```bash
mysql -u root < sig-01/setup.sql
mysql -u root < sig-02/schema.sql
mysql -u root < sig-03/database.sql
```

### Buka di browser

```
http://localhost/          ← halaman daftar proyek
http://localhost/sig-01/   ← WebGIS SPBU
http://localhost/sig-02/   ← SIG Mapping Tanah & Jalan
http://localhost/sig-03/   ← Poverty-Mapping GIS
```

> Jika menggunakan virtual host Laragon: `http://sig-01.test`, `http://sig-02.test`, dst.

## Struktur Direktori

```
www/
├── index.php        ← landing page daftar proyek
├── sig-01/          ← WebGIS SPBU
├── sig-02/          ← SIG Mapping Tanah & Jalan
├── sig-03/          ← Poverty-Mapping GIS
└── lab/             ← folder tugas & eksperimen
```
