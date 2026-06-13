USE sig_bansos;

-- ============================================================
-- Seed: religious_centers (5 titik di Pontianak)
-- ============================================================
INSERT INTO religious_centers (name, address, kas, latitude, longitude, radius) VALUES
('Masjid Jami Pontianak',      'Jl. Tanjungpura No.1, Pontianak Selatan',       12500000, -0.0294,  109.3225, 400),
('Masjid Mujahidin',           'Jl. Ahmad Yani, Pontianak Barat',                8750000,  -0.0510,  109.3100, 350),
('Gereja Katedral Santo Yosef','Jl. Rahadi Usman No.2, Pontianak Kota',          6200000,  -0.0245,  109.3344, 300),
('Vihara Bodhisattva Karaniya','Jl. Diponegoro No.47, Pontianak Kota',           4300000,  -0.0348,  109.3278, 280),
('Masjid Al-Falah',            'Jl. Pahlawan, Pontianak Timur',                  5100000,  -0.0600,  109.3600, 320);

-- ============================================================
-- Seed: houses (15 rumah miskin)
-- ============================================================
INSERT INTO houses (latitude, longitude, address, rt, rw, kelurahan, status_miskin, jumlah_anggota, anggota, aid_status, has_data) VALUES

-- Cluster sekitar Masjid Jami (radius 400m)
(-0.0301, 109.3240, 'Jl. Tanjungpura Gang Melati No.3, Pontianak Selatan', '002', '001', 'Dalam Bugis',
 'sangat_miskin', 4,
 '[{"nama":"Suryanto","statusAnggota":"Kepala Keluarga","tglLahir":"1978-04-12","pekerjaan":"Buruh/Karyawan","gaji":900000},{"nama":"Dewi Suryanto","statusAnggota":"Istri/Suami","tglLahir":"1982-07-20","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Rian Suryanto","statusAnggota":"Anak","tglLahir":"2008-01-15","pekerjaan":"Pelajar/Mahasiswa","gaji":0},{"nama":"Sari Suryanto","statusAnggota":"Anak","tglLahir":"2011-09-03","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'helped', 1),

(-0.0310, 109.3255, 'Jl. Tanjungpura Gang Anggrek No.7, Pontianak Selatan', '003', '001', 'Dalam Bugis',
 'miskin', 3,
 '[{"nama":"Hendra Wijaya","statusAnggota":"Kepala Keluarga","tglLahir":"1985-06-28","pekerjaan":"Petani/Nelayan","gaji":1200000},{"nama":"Yuli Wijaya","statusAnggota":"Istri/Suami","tglLahir":"1988-11-14","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Budi Wijaya","statusAnggota":"Anak","tglLahir":"2014-03-22","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'not_helped', 1),

(-0.0285, 109.3210, 'Jl. Gajah Mada Gg. Sejahtera No.12, Pontianak Selatan', '001', '002', 'Benua Melayu Darat',
 'sangat_miskin', 5,
 '[{"nama":"Ahmad Fauzi","statusAnggota":"Kepala Keluarga","tglLahir":"1970-02-10","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Siti Aminah","statusAnggota":"Istri/Suami","tglLahir":"1974-08-30","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Rizky Fauzi","statusAnggota":"Anak","tglLahir":"1998-05-17","pekerjaan":"Buruh/Karyawan","gaji":800000},{"nama":"Nurul Fauzi","statusAnggota":"Anak","tglLahir":"2002-12-01","pekerjaan":"Pelajar/Mahasiswa","gaji":0},{"nama":"Kakek Hamid","statusAnggota":"Orang Tua","tglLahir":"1945-01-01","pekerjaan":"Tidak Bekerja","gaji":0}]',
 'helped', 1),

-- Cluster sekitar Masjid Mujahidin (radius 350m)
(-0.0490, 109.3085, 'Jl. Ahmad Yani Gang Damai No.4, Pontianak Barat', '005', '003', 'Akcaya',
 'miskin', 4,
 '[{"nama":"Bambang Santoso","statusAnggota":"Kepala Keluarga","tglLahir":"1975-09-05","pekerjaan":"Wiraswasta","gaji":1500000},{"nama":"Rahayu Santoso","statusAnggota":"Istri/Suami","tglLahir":"1979-04-18","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Dika Santoso","statusAnggota":"Anak","tglLahir":"2005-07-11","pekerjaan":"Pelajar/Mahasiswa","gaji":0},{"nama":"Tika Santoso","statusAnggota":"Anak","tglLahir":"2009-02-25","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'not_helped', 1),

(-0.0530, 109.3115, 'Jl. Kom. Yos Sudarso Gg. Mawar No.9, Pontianak Barat', '007', '004', 'Akcaya',
 'sangat_miskin', 2,
 '[{"nama":"Pak Idrus","statusAnggota":"Kepala Keluarga","tglLahir":"1952-03-14","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Bu Rohani","statusAnggota":"Istri/Suami","tglLahir":"1956-11-08","pekerjaan":"Tidak Bekerja","gaji":0}]',
 'helped', 1),

(-0.0480, 109.3070, 'Jl. Purnama Gang Murni No.2, Pontianak Barat', '004', '002', 'Sungai Beliung',
 'miskin', 3,
 '[{"nama":"Eko Prasetyo","statusAnggota":"Kepala Keluarga","tglLahir":"1983-12-20","pekerjaan":"Buruh/Karyawan","gaji":1100000},{"nama":"Fitri Prasetyo","statusAnggota":"Istri/Suami","tglLahir":"1986-05-30","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Kevin Prasetyo","statusAnggota":"Anak","tglLahir":"2012-08-14","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'not_helped', 1),

-- Cluster sekitar Gereja Katedral (radius 300m)
(-0.0260, 109.3360, 'Jl. Rahadi Usman Gg. Teratai No.6, Pontianak Kota', '001', '001', 'Darat Sekip',
 'tidak_miskin', 4,
 '[{"nama":"Antonius Liong","statusAnggota":"Kepala Keluarga","tglLahir":"1972-07-22","pekerjaan":"Wiraswasta","gaji":3500000},{"nama":"Maria Liong","statusAnggota":"Istri/Suami","tglLahir":"1975-10-09","pekerjaan":"Buruh/Karyawan","gaji":1800000},{"nama":"Felix Liong","statusAnggota":"Anak","tglLahir":"2003-04-05","pekerjaan":"Pelajar/Mahasiswa","gaji":0},{"nama":"Clara Liong","statusAnggota":"Anak","tglLahir":"2007-01-17","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'outside', 1),

(-0.0235, 109.3330, 'Jl. Tanjung Raya Gg. Bakti No.11, Pontianak Kota', '002', '001', 'Darat Sekip',
 'miskin', 3,
 '[{"nama":"Yohanes Budi","statusAnggota":"Kepala Keluarga","tglLahir":"1980-08-16","pekerjaan":"Petani/Nelayan","gaji":900000},{"nama":"Elisabeth Budi","statusAnggota":"Istri/Suami","tglLahir":"1984-03-27","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Theresia Budi","statusAnggota":"Anak","tglLahir":"2010-06-13","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'not_helped', 1),

-- Cluster sekitar Vihara (radius 280m)
(-0.0365, 109.3290, 'Jl. Diponegoro Gg. Lestari No.5, Pontianak Kota', '003', '002', 'Mariana',
 'miskin', 4,
 '[{"nama":"Lim Ah Kow","statusAnggota":"Kepala Keluarga","tglLahir":"1968-05-10","pekerjaan":"Wiraswasta","gaji":1200000},{"nama":"Tan Siu Lan","statusAnggota":"Istri/Suami","tglLahir":"1972-09-24","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Lim Wei","statusAnggota":"Anak","tglLahir":"2000-11-29","pekerjaan":"Buruh/Karyawan","gaji":700000},{"nama":"Lim Hui","statusAnggota":"Anak","tglLahir":"2006-02-08","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'helped', 1),

(-0.0340, 109.3265, 'Jl. Veteran Gg. Rukun No.8, Pontianak Kota', '006', '003', 'Mariana',
 'sangat_miskin', 2,
 '[{"nama":"Wang Cheng","statusAnggota":"Kepala Keluarga","tglLahir":"1960-04-04","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Liu Fang","statusAnggota":"Istri/Suami","tglLahir":"1963-12-18","pekerjaan":"Tidak Bekerja","gaji":0}]',
 'not_helped', 1),

-- Cluster sekitar Masjid Al-Falah (radius 320m)
(-0.0580, 109.3580, 'Jl. Pahlawan Gg. Bersatu No.3, Pontianak Timur', '001', '001', 'Saigon',
 'sangat_miskin', 5,
 '[{"nama":"Syaiful Anwar","statusAnggota":"Kepala Keluarga","tglLahir":"1973-01-25","pekerjaan":"Buruh/Karyawan","gaji":750000},{"nama":"Nurhayati","statusAnggota":"Istri/Suami","tglLahir":"1977-06-14","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Fajar Anwar","statusAnggota":"Anak","tglLahir":"1999-10-07","pekerjaan":"Buruh/Karyawan","gaji":600000},{"nama":"Dewi Anwar","statusAnggota":"Anak","tglLahir":"2004-03-19","pekerjaan":"Pelajar/Mahasiswa","gaji":0},{"nama":"Si Kecil","statusAnggota":"Anak","tglLahir":"2016-07-30","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'helped', 1),

(-0.0620, 109.3615, 'Jl. Sultan Hamid Gg. Damai No.10, Pontianak Timur', '002', '002', 'Saigon',
 'miskin', 3,
 '[{"nama":"Mukhtar Halim","statusAnggota":"Kepala Keluarga","tglLahir":"1981-11-03","pekerjaan":"Petani/Nelayan","gaji":1000000},{"nama":"Salmah Halim","statusAnggota":"Istri/Suami","tglLahir":"1984-08-22","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Hafiz Halim","statusAnggota":"Anak","tglLahir":"2013-05-16","pekerjaan":"Pelajar/Mahasiswa","gaji":0}]',
 'not_helped', 1),

(-0.0565, 109.3560, 'Jl. DR. Wahidin Gg. Sempurna No.15, Pontianak Timur', '004', '003', 'Banjar Serasan',
 'miskin', 4,
 '[{"nama":"Roslan Idris","statusAnggota":"Kepala Keluarga","tglLahir":"1976-02-17","pekerjaan":"Wiraswasta","gaji":1300000},{"nama":"Yanti Idris","statusAnggota":"Istri/Suami","tglLahir":"1979-07-05","pekerjaan":"Tidak Bekerja","gaji":0},{"nama":"Reza Idris","statusAnggota":"Anak","tglLahir":"2007-09-28","pekerjaan":"Pelajar/Mahasiswa","gaji":0},{"nama":"Nenek Maimunah","statusAnggota":"Orang Tua","tglLahir":"1950-06-12","pekerjaan":"Tidak Bekerja","gaji":0}]',
 'not_helped', 1),

-- Pin tanpa data (has_data=0)
(-0.0330, 109.3310, 'Jl. Nusa Indah, Pontianak Kota', '005', '002', 'Mariana',
 '', 0, '[]', 'outside', 0),

(-0.0520, 109.3090, 'Jl. Ahmad Yani, Pontianak Barat', '008', '005', 'Akcaya',
 '', 0, '[]', 'outside', 0);

-- ============================================================
-- Seed: laporan
-- ============================================================
INSERT INTO laporan (pelapor, deskripsi, lokasi, status) VALUES
('Budi Santoso',   'Ada keluarga lansia di Gang Flamboyan RT 007 yang tinggal sendiri, rumahnya hampir roboh. Perlu perhatian segera.', 'Gang Flamboyan RT 007, Pontianak Barat', 'baru'),
('Siti Rahayu',    'Keluarga dengan 6 anak di Jl. Pahlawan belum pernah menerima bantuan apapun. Kondisi rumah sangat tidak layak.', 'Jl. Pahlawan, Pontianak Timur', 'ditangani'),
('Anonim',         'Ada balita yang kekurangan gizi di RT 003 RW 001, orang tuanya pengangguran. Butuh bantuan pangan segera.', 'RT 003/001, Dalam Bugis, Pontianak Selatan', 'baru'),
('Pak RT Hamzah',  'Terdapat 3 kepala keluarga di gang belakang pasar yang belum terdata. Mereka tinggal di bantaran sungai.', 'Bantaran Sungai Kapuas, Pontianak Kota', 'selesai'),
('Marlina Dewi',   'Ibu tunggal dengan 4 anak di Jl. Veteran, suami meninggal tahun lalu. Sangat membutuhkan bantuan sosial.', 'Jl. Veteran, Pontianak Kota', 'baru');

-- ============================================================
-- Seed: aid_logs (riwayat bantuan)
-- ============================================================
INSERT INTO aid_logs (house_id, religious_center_id, status, timestamp) VALUES
(1, 1, 'helped',   '2026-03-10 09:00:00'),
(3, 1, 'helped',   '2026-03-12 10:30:00'),
(5, 2, 'helped',   '2026-03-15 08:45:00'),
(9, 4, 'helped',   '2026-04-01 11:00:00'),
(11, 5, 'helped',  '2026-04-05 09:30:00'),
(2, 1, 'reverted', '2026-02-20 14:00:00'),
(4, 2, 'reverted', '2026-02-25 13:00:00');
