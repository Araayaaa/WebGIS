<?php
/**
 * Seeder data contoh — area Pontianak, Kalimantan Barat.
 * Jalankan: php seed.php   (atau buka http://localhost/sig-02/seed.php)
 *
 * Menghitung luas/keliling/panjang memakai fungsi yang sama dengan aplikasi
 * agar konsisten, lalu insert ke database.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/geo.php';

$pdo = db();

// Bersihkan data lama (opsional). Komentari bila ingin menambah saja.
$pdo->exec('DELETE FROM tanah');
$pdo->exec('DELETE FROM jalan');
$pdo->exec('ALTER TABLE tanah AUTO_INCREMENT = 1');
$pdo->exec('ALTER TABLE jalan AUTO_INCREMENT = 1');

/* ============================ TANAH (Polygon) ============================
 * Koordinat GeoJSON: [longitude, latitude]
 */
$tanah = [
    [
        'nama' => 'Kawasan Tugu Khatulistiwa',
        'pemilik' => 'Pemkot Pontianak',
        'kategori' => 'Sertifikat Hak Pakai (HP)',
        'deskripsi' => 'Area landmark Tugu Khatulistiwa, Pontianak Utara.',
        'warna' => '#16a34a',
        'ring' => [
            [109.3215, -0.0005], [109.3232, -0.0005],
            [109.3232, -0.0022], [109.3215, -0.0022], [109.3215, -0.0005],
        ],
    ],
    [
        'nama' => 'Lahan Alun-Alun Kapuas',
        'pemilik' => 'Pemkot Pontianak',
        'kategori' => 'Sertifikat Hak Pakai (HP)',
        'deskripsi' => 'Ruang terbuka di tepi Sungai Kapuas, dekat Pelabuhan.',
        'warna' => '#22c55e',
        'ring' => [
            [109.3438, -0.0265], [109.3452, -0.0263],
            [109.3454, -0.0276], [109.3440, -0.0279], [109.3438, -0.0265],
        ],
    ],
    [
        'nama' => 'Blok Permukiman Sungai Jawi',
        'pemilik' => 'Warga RW 04',
        'kategori' => 'Sertifikat Hak Milik (SHM)',
        'deskripsi' => 'Petak permukiman padat di Kecamatan Pontianak Kota.',
        'warna' => '#15803d',
        'ring' => [
            [109.3290, -0.0360], [109.3312, -0.0358],
            [109.3314, -0.0378], [109.3292, -0.0380], [109.3290, -0.0360],
        ],
    ],
    [
        'nama' => 'Kawasan Komersial Jl. Gajah Mada',
        'pemilik' => 'Swasta',
        'kategori' => 'Sertifikat Hak Guna Bangunan (HGB)',
        'deskripsi' => 'Blok pertokoan / ruko di koridor Jalan Gajah Mada.',
        'warna' => '#65a30d',
        'ring' => [
            [109.3360, -0.0310], [109.3378, -0.0312],
            [109.3376, -0.0328], [109.3358, -0.0326], [109.3360, -0.0310],
        ],
    ],
];

/* ============================ JALAN (LineString) ============================ */
$jalan = [
    [
        'nama' => 'Jl. Ahmad Yani',
        'jenis' => 'Aspal',
        'kategori' => 'Jalan Nasional',
        'deskripsi' => 'Jalan arteri utama Kota Pontianak.',
        'warna' => '#ef4444',
        'coords' => [
            [109.3260, -0.0540], [109.3300, -0.0470],
            [109.3345, -0.0400], [109.3390, -0.0330],
        ],
    ],
    [
        'nama' => 'Jl. Gajah Mada',
        'jenis' => 'Aspal',
        'kategori' => 'Jalan Kabupaten',
        'deskripsi' => 'Koridor pusat kuliner & perdagangan.',
        'warna' => '#dc2626',
        'coords' => [
            [109.3352, -0.0300], [109.3372, -0.0335], [109.3390, -0.0368],
        ],
    ],
    [
        'nama' => 'Jl. Tanjungpura',
        'jenis' => 'Aspal',
        'kategori' => 'Jalan Provinsi',
        'deskripsi' => 'Menghubungkan pusat kota ke kawasan Kapuas.',
        'warna' => '#f97316',
        'coords' => [
            [109.3400, -0.0270], [109.3415, -0.0300], [109.3428, -0.0330],
        ],
    ],
    [
        'nama' => 'Jl. Sultan Abdurrahman',
        'jenis' => 'Beton / Cor',
        'kategori' => 'Jalan Kabupaten',
        'deskripsi' => 'Akses menuju kawasan Sungai Jawi.',
        'warna' => '#b91c1c',
        'coords' => [
            [109.3280, -0.0350], [109.3320, -0.0345], [109.3360, -0.0342],
        ],
    ],
];

/* ============================ INSERT ============================ */
$stmtT = $pdo->prepare(
    'INSERT INTO tanah (nama, pemilik, kategori, deskripsi, luas, keliling, warna, geojson)
     VALUES (?,?,?,?,?,?,?,?)'
);
foreach ($tanah as $t) {
    $geo = ['type' => 'Polygon', 'coordinates' => [$t['ring']]];
    $stmtT->execute([
        $t['nama'], $t['pemilik'], $t['kategori'], $t['deskripsi'],
        round(ring_area($t['ring']), 2),
        round(ring_perimeter($t['ring']), 2),
        $t['warna'],
        json_encode($geo, JSON_UNESCAPED_UNICODE),
    ]);
}

$stmtJ = $pdo->prepare(
    'INSERT INTO jalan (nama, jenis, kategori, deskripsi, panjang, warna, geojson)
     VALUES (?,?,?,?,?,?,?)'
);
foreach ($jalan as $j) {
    $geo = ['type' => 'LineString', 'coordinates' => $j['coords']];
    $stmtJ->execute([
        $j['nama'], $j['jenis'], $j['kategori'], $j['deskripsi'],
        round(line_length($j['coords']), 2),
        $j['warna'],
        json_encode($geo, JSON_UNESCAPED_UNICODE),
    ]);
}

$msg = sprintf("Seeding selesai: %d tanah, %d jalan (area Pontianak).", count($tanah), count($jalan));

if (PHP_SAPI === 'cli') {
    echo $msg . PHP_EOL;
} else {
    header('Content-Type: text/plain; charset=utf-8');
    echo $msg . "\nBuka kembali index.php untuk melihat hasilnya.";
}
