-- =====================================================================
-- LMS - Belajar Sampai Tuntas
-- Inisialisasi lengkap database `ulangan_lms` (otomatis dijalankan oleh
-- Docker MySQL saat pertama kali start via /docker-entrypoint-initdb.d).
--
-- Skema mengikuti aplikasi ulangan_tuntas (kompatibel) dengan penambahan:
--   * tabel `materi`         -> materi belajar per ujian
--   * tabel `materi_progress`-> penanda siswa sudah membaca materi
-- =====================================================================

-- ---------------------------------------------------------------------
-- admin_users
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Default: admin / admin123 (segera ganti setelah login pertama)
INSERT INTO `admin_users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('admin', '$2y$12$uZF2KVRdMyqOKyBhpvCFBe72ia/.CWYZseASp75gvzC9XZ/OVoEGy', 'Administrator LMS', 'super_admin');

-- ---------------------------------------------------------------------
-- konfigurasi_sekolah
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `konfigurasi_sekolah` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(255) NOT NULL DEFAULT 'SMA Negeri 6 Cimahi',
  `logo` varchar(255) DEFAULT NULL,
  `warna_primer` varchar(20) DEFAULT '#667eea',
  `warna_sekunder` varchar(20) DEFAULT '#764ba2',
  `tampilkan_riwayat` enum('ya','tidak') DEFAULT 'ya',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `konfigurasi_sekolah` (`nama_sekolah`) VALUES ('SMA Negeri 6 Cimahi');

-- ---------------------------------------------------------------------
-- ujian (dengan target ketuntasan / KKM)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ujian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `judul_ujian` varchar(255) NOT NULL,
  `deskripsi` text,
  `status` enum('aktif','nonaktif') DEFAULT 'nonaktif',
  `tgl_dibuat` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `kode_ujian` varchar(20) DEFAULT NULL,
  `allow_ip` text,
  `enable_browser_lock` enum('ya','tidak') DEFAULT 'tidak',
  `max_violations` int DEFAULT '10',
  `enable_device_check` enum('ya','tidak') DEFAULT 'tidak',
  `waktu_tersedia` int DEFAULT '0',
  `acak_soal` enum('ya','tidak') DEFAULT 'tidak',
  `acak_opsi` enum('ya','tidak') DEFAULT 'tidak',
  `tampilkan_review` enum('ya','tidak') DEFAULT 'tidak',
  `tampilkan_skor` enum('ya','tidak') DEFAULT 'ya',
  `target_nilai` decimal(5,2) NOT NULL DEFAULT 75.00,
  `wajib_tuntas` enum('ya','tidak') NOT NULL DEFAULT 'ya',
  PRIMARY KEY (`id`),
  KEY `idx_ujian_status` (`status`,`tgl_dibuat` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- materi (materi belajar yang wajib dibaca sebelum mengerjakan soal)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `judul` varchar(255) NOT NULL,
  `konten` mediumtext,
  `urutan` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_materi_ujian` (`id_ujian`),
  CONSTRAINT `fk_materi_ujian` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- materi_progress (penanda: siswa sudah membaca materi tertentu)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `materi_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_materi` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `waktu_dibaca` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_materi_nis` (`id_materi`,`nis`),
  KEY `idx_progress_nis` (`nis`),
  CONSTRAINT `fk_progress_materi` FOREIGN KEY (`id_materi`) REFERENCES `materi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- soal
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `soal` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `pertanyaan` text NOT NULL,
  `gambar_pertanyaan` varchar(255) DEFAULT NULL,
  `opsi_a` varchar(255) NOT NULL,
  `gambar_a` varchar(255) DEFAULT NULL,
  `opsi_b` varchar(255) NOT NULL,
  `gambar_b` varchar(255) DEFAULT NULL,
  `opsi_c` varchar(255) DEFAULT NULL,
  `gambar_c` varchar(255) DEFAULT NULL,
  `opsi_d` varchar(255) DEFAULT NULL,
  `gambar_d` varchar(255) DEFAULT NULL,
  `opsi_e` varchar(255) DEFAULT NULL,
  `gambar_e` varchar(255) DEFAULT NULL,
  `kunci_jawaban` enum('a','b','c','d','e') NOT NULL,
  `poin` int DEFAULT '10',
  `kategori` varchar(100) DEFAULT NULL,
  `timer_soal` int DEFAULT '0',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_soal_ujian` (`id_ujian`),
  CONSTRAINT `soal_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- hasil_ujian (menyimpan percobaan TERBAIK per siswa)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hasil_ujian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `total_skor` int DEFAULT '0',
  `skor_awal` int DEFAULT NULL,
  `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP,
  `detail_jawaban` longtext,
  `device_fingerprint` varchar(64) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `total_violations` int DEFAULT '0',
  `submitted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_ujian` (`id_ujian`),
  KEY `idx_ujian_nis` (`id_ujian`,`nis`),
  CONSTRAINT `hasil_ujian_ibfk_1` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- percobaan_ujian (riwayat SEMUA percobaan, untuk mode sampai tuntas)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `percobaan_ujian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `kelas` varchar(50) NOT NULL,
  `percobaan_ke` int NOT NULL DEFAULT '1',
  `total_skor` int NOT NULL DEFAULT '0',
  `total_poin_maks` int NOT NULL DEFAULT '0',
  `nilai` decimal(5,2) NOT NULL DEFAULT '0.00',
  `jumlah_benar` int NOT NULL DEFAULT '0',
  `jumlah_soal` int NOT NULL DEFAULT '0',
  `tuntas` tinyint(1) NOT NULL DEFAULT '0',
  `skor_awal` int DEFAULT NULL,
  `detail_jawaban` longtext,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_fingerprint` varchar(64) DEFAULT NULL,
  `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ujian_nis` (`id_ujian`,`nis`),
  KEY `idx_ujian_tuntas` (`id_ujian`,`tuntas`),
  CONSTRAINT `fk_percobaan_ujian` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- izin_remedi (izin mengulang oleh guru)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `izin_remedi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `diberikan_oleh` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_remedi_ujian_nis` (`id_ujian`,`nis`),
  KEY `idx_nis` (`nis`),
  KEY `idx_ujian` (`id_ujian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- jawaban_sementara & jawaban_sEMENTARA (auto-save)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `jawaban_sementara` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ujian_nis` (`id_ujian`,`nis`),
  KEY `idx_nis` (`nis`),
  KEY `idx_ujian` (`id_ujian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS `jawaban_sEMENTARA` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `answers` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_fingerprint` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ujian_nis` (`id_ujian`,`nis`),
  KEY `idx_nis` (`nis`),
  KEY `idx_ujian` (`id_ujian`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ---------------------------------------------------------------------
-- exam_violations
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `exam_violations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_ujian` int NOT NULL,
  `nis` varchar(50) NOT NULL,
  `jenis_violation` varchar(50) NOT NULL,
  `detail` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ujian_nis` (`id_ujian`,`nis`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- =====================================================================
-- SEED DATA CONTOH
-- Satu ujian aktif dengan 3 materi + 10 soal (poin 10/soal).
-- Semua benar => skor 100 x 10 / 100 = NILAI 100 (berapa pun jumlah soal).
-- =====================================================================
INSERT INTO `ujian`
  (`judul_ujian`, `deskripsi`, `status`, `kode_ujian`, `waktu_tersedia`, `acak_soal`, `tampilkan_review`, `tampilkan_skor`, `target_nilai`, `wajib_tuntas`)
VALUES
  ('Latihan 1 - Pengenalan Jaringan Komputer (Kelas X)',
   'Pelajari materi di bawah ini terlebih dahulu, lalu kerjakan soal. Anda dinyatakan TUNTAS bila mencapai nilai minimal 75. Bila belum tuntas, ulangi sampai tuntas!',
   'aktif', NULL, 30, 'ya', 'ya', 'ya', 75.00, 'ya');

SET @id_ujian := LAST_INSERT_ID();

INSERT INTO `materi` (`id_ujian`, `judul`, `konten`, `urutan`) VALUES
(@id_ujian, 'Pengertian Jaringan Komputer',
 '<p><b>Jaringan komputer</b> adalah sekumpulan komputer yang saling terhubung untuk bertukar data dan sumber daya.</p><p>Jaringan memungkinkan berbagi <i>hardware</i>, <i>software</i>, dan informasi antar pengguna.</p>', 1),
(@id_ujian, 'Jenis-Jenis Jaringan Berdasarkan Cakupan',
 '<ul><li><b>LAN</b> (Local Area Network): cakupan kecil, misal sekolah/kantor.</li><li><b>MAN</b> (Metropolitan Area Network): cakupan satu kota.</li><li><b>WAN</b> (Wide Area Network): cakupan sangat luas, contohnya internet.</li></ul>', 2),
(@id_ujian, 'Perangkat Jaringan Dasar',
 '<ul><li><b>Router</b>: menghubungkan dua jaringan berbeda.</li><li><b>Switch/Hub</b>: menghubungkan perangkat dalam satu jaringan.</li><li><b>NIC</b> (Network Interface Card): kartu jaringan pada komputer.</li><li><b>Kabel UTP</b>: media transmisi dengan konektor RJ45.</li></ul>', 3);

INSERT INTO `soal` (`id_ujian`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `opsi_e`, `kunci_jawaban`, `poin`) VALUES
(@id_ujian, 'Pengertian jaringan komputer adalah...', 'Sekumpulan komputer yang saling terhubung untuk bertukar data', 'Sekumpulan kabel yang menghubungkan monitor', 'Sekumpulan website di internet', 'Sekumpulan orang yang menggunakan komputer', 'Sekumpulan printer yang saling terhubung', 'a', 10),
(@id_ujian, 'Jaringan komputer yang mencakup wilayah kecil seperti sekolah disebut...', 'WAN', 'MAN', 'LAN', 'PAN', 'SAN', 'c', 10),
(@id_ujian, 'LAN adalah singkatan dari...', 'Large Area Network', 'Local Area Network', 'Long Area Network', 'Limited Area Network', 'Linked Area Network', 'b', 10),
(@id_ujian, 'Perangkat yang menghubungkan dua jaringan berbeda disebut...', 'Router', 'Modem', 'NIC', 'Switch', 'Hub', 'a', 10),
(@id_ujian, 'Konektor yang digunakan untuk kabel UTP adalah...', 'BNC', 'RJ11', 'RJ45', 'USB', 'HDMI', 'c', 10),
(@id_ujian, 'Topologi jaringan yang semua perangkat terhubung ke satu titik pusat disebut...', 'Bus', 'Ring', 'Star', 'Mesh', 'Tree', 'c', 10),
(@id_ujian, 'Protokol yang digunakan untuk komunikasi data di internet adalah...', 'HTTP', 'FTP', 'TCP/IP', 'SMTP', 'POP3', 'c', 10),
(@id_ujian, 'Fungsi utama router adalah...', 'Menyimpan data pengguna', 'Menghubungkan dua atau lebih jaringan untuk meneruskan data', 'Menampilkan halaman web', 'Mencetak dokumen', 'Menghapus virus', 'b', 10),
(@id_ujian, 'Perangkat yang menangkap sinyal jaringan nirkabel disebut...', 'NIC', 'Wireless Card', 'Router', 'Modem', 'Switch', 'b', 10),
(@id_ujian, 'Kabel jaringan yang terbuat dari serat kaca disebut...', 'UTP', 'STP', 'Coaxial', 'Fiber Optic', 'USB', 'd', 10);
