-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: ulangan_lms
-- ------------------------------------------------------
-- Server version	8.0.46

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `role` enum('super_admin','admin') DEFAULT 'admin',
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (1,'admin','$2y$12$uZF2KVRdMyqOKyBhpvCFBe72ia/.CWYZseASp75gvzC9XZ/OVoEGy','Administrator LMS','2026-10-18 20:52:31','super_admin','2026-10-18 20:54:44');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_violations`
--

DROP TABLE IF EXISTS `exam_violations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exam_violations` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_violations`
--

LOCK TABLES `exam_violations` WRITE;
/*!40000 ALTER TABLE `exam_violations` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_violations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hasil_ujian`
--

DROP TABLE IF EXISTS `hasil_ujian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hasil_ujian` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hasil_ujian`
--

LOCK TABLES `hasil_ujian` WRITE;
/*!40000 ALTER TABLE `hasil_ujian` DISABLE KEYS */;
/*!40000 ALTER TABLE `hasil_ujian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `izin_remedi`
--

DROP TABLE IF EXISTS `izin_remedi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `izin_remedi` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `izin_remedi`
--

LOCK TABLES `izin_remedi` WRITE;
/*!40000 ALTER TABLE `izin_remedi` DISABLE KEYS */;
/*!40000 ALTER TABLE `izin_remedi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jawaban_sEMENTARA`
--

DROP TABLE IF EXISTS `jawaban_sEMENTARA`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jawaban_sEMENTARA` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jawaban_sEMENTARA`
--

LOCK TABLES `jawaban_sEMENTARA` WRITE;
/*!40000 ALTER TABLE `jawaban_sEMENTARA` DISABLE KEYS */;
/*!40000 ALTER TABLE `jawaban_sEMENTARA` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jawaban_sementara`
--

DROP TABLE IF EXISTS `jawaban_sementara`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jawaban_sementara` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jawaban_sementara`
--

LOCK TABLES `jawaban_sementara` WRITE;
/*!40000 ALTER TABLE `jawaban_sementara` DISABLE KEYS */;
/*!40000 ALTER TABLE `jawaban_sementara` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `konfigurasi_sekolah`
--

DROP TABLE IF EXISTS `konfigurasi_sekolah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `konfigurasi_sekolah` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(255) NOT NULL DEFAULT 'SMA Negeri 6 Cimahi',
  `logo` varchar(255) DEFAULT NULL,
  `warna_primer` varchar(20) DEFAULT '#667eea',
  `warna_sekunder` varchar(20) DEFAULT '#764ba2',
  `tampilkan_riwayat` enum('ya','tidak') DEFAULT 'ya',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `konfigurasi_sekolah`
--

LOCK TABLES `konfigurasi_sekolah` WRITE;
/*!40000 ALTER TABLE `konfigurasi_sekolah` DISABLE KEYS */;
INSERT INTO `konfigurasi_sekolah` VALUES (1,'SMA Negeri 6 Cimahi',NULL,'#667eea','#764ba2','ya','2026-10-18 12:52:31','2026-10-18 12:52:31');
/*!40000 ALTER TABLE `konfigurasi_sekolah` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materi`
--

DROP TABLE IF EXISTS `materi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materi` (
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materi`
--

LOCK TABLES `materi` WRITE;
/*!40000 ALTER TABLE `materi` DISABLE KEYS */;
INSERT INTO `materi` VALUES (1,1,'Pengertian Jaringan Komputer','<p><b>Jaringan komputer</b> adalah sekumpulan komputer yang saling terhubung untuk bertukar data dan sumber daya.</p><p>Jaringan memungkinkan berbagi <i>hardware</i>, <i>software</i>, dan informasi antar pengguna.</p>',1,'2026-10-18 20:52:31','2026-10-18 12:52:31'),(2,1,'Jenis-Jenis Jaringan Berdasarkan Cakupan','<ul><li><b>LAN</b> (Local Area Network): cakupan kecil, misal sekolah/kantor.</li><li><b>MAN</b> (Metropolitan Area Network): cakupan satu kota.</li><li><b>WAN</b> (Wide Area Network): cakupan sangat luas, contohnya internet.</li></ul>',2,'2026-10-18 20:52:31','2026-10-18 12:52:31'),(3,1,'Perangkat Jaringan Dasar','<ul><li><b>Router</b>: menghubungkan dua jaringan berbeda.</li><li><b>Switch/Hub</b>: menghubungkan perangkat dalam satu jaringan.</li><li><b>NIC</b> (Network Interface Card): kartu jaringan pada komputer.</li><li><b>Kabel UTP</b>: media transmisi dengan konektor RJ45.</li></ul>',3,'2026-10-18 20:52:31','2026-10-18 12:52:31');
/*!40000 ALTER TABLE `materi` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `materi_progress`
--

DROP TABLE IF EXISTS `materi_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `materi_progress` (
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `materi_progress`
--

LOCK TABLES `materi_progress` WRITE;
/*!40000 ALTER TABLE `materi_progress` DISABLE KEYS */;
INSERT INTO `materi_progress` VALUES (1,1,'123456','nate','X-1','2026-10-18 20:54:18'),(2,1,'1234567','nate2','X-1','2026-10-18 20:59:29'),(3,2,'1234567','nate2','X-1','2026-10-18 20:59:33');
/*!40000 ALTER TABLE `materi_progress` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `percobaan_ujian`
--

DROP TABLE IF EXISTS `percobaan_ujian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `percobaan_ujian` (
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `percobaan_ujian`
--

LOCK TABLES `percobaan_ujian` WRITE;
/*!40000 ALTER TABLE `percobaan_ujian` DISABLE KEYS */;
/*!40000 ALTER TABLE `percobaan_ujian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `soal`
--

DROP TABLE IF EXISTS `soal`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `soal` (
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `soal`
--

LOCK TABLES `soal` WRITE;
/*!40000 ALTER TABLE `soal` DISABLE KEYS */;
INSERT INTO `soal` VALUES (1,1,'Pengertian jaringan komputer adalah...',NULL,'Sekumpulan komputer yang saling terhubung untuk bertukar data',NULL,'Sekumpulan kabel yang menghubungkan monitor',NULL,'Sekumpulan website di internet',NULL,'Sekumpulan orang yang menggunakan komputer',NULL,'Sekumpulan printer yang saling terhubung',NULL,'a',10,NULL,0,'2026-10-18 12:52:31'),(2,1,'Jaringan komputer yang mencakup wilayah kecil seperti sekolah disebut...',NULL,'WAN',NULL,'MAN',NULL,'LAN',NULL,'PAN',NULL,'SAN',NULL,'c',10,NULL,0,'2026-10-18 12:52:31'),(3,1,'LAN adalah singkatan dari...',NULL,'Large Area Network',NULL,'Local Area Network',NULL,'Long Area Network',NULL,'Limited Area Network',NULL,'Linked Area Network',NULL,'b',10,NULL,0,'2026-10-18 12:52:31'),(4,1,'Perangkat yang menghubungkan dua jaringan berbeda disebut...',NULL,'Router',NULL,'Modem',NULL,'NIC',NULL,'Switch',NULL,'Hub',NULL,'a',10,NULL,0,'2026-10-18 12:52:31'),(5,1,'Konektor yang digunakan untuk kabel UTP adalah...',NULL,'BNC',NULL,'RJ11',NULL,'RJ45',NULL,'USB',NULL,'HDMI',NULL,'c',10,NULL,0,'2026-10-18 12:52:31'),(6,1,'Topologi jaringan yang semua perangkat terhubung ke satu titik pusat disebut...',NULL,'Bus',NULL,'Ring',NULL,'Star',NULL,'Mesh',NULL,'Tree',NULL,'c',10,NULL,0,'2026-10-18 12:52:31'),(7,1,'Protokol yang digunakan untuk komunikasi data di internet adalah...',NULL,'HTTP',NULL,'FTP',NULL,'TCP/IP',NULL,'SMTP',NULL,'POP3',NULL,'c',10,NULL,0,'2026-10-18 12:52:31'),(8,1,'Fungsi utama router adalah...',NULL,'Menyimpan data pengguna',NULL,'Menghubungkan dua atau lebih jaringan untuk meneruskan data',NULL,'Menampilkan halaman web',NULL,'Mencetak dokumen',NULL,'Menghapus virus',NULL,'b',10,NULL,0,'2026-10-18 12:52:31'),(9,1,'Perangkat yang menangkap sinyal jaringan nirkabel disebut...',NULL,'NIC',NULL,'Wireless Card',NULL,'Router',NULL,'Modem',NULL,'Switch',NULL,'b',10,NULL,0,'2026-10-18 12:52:31'),(10,1,'Kabel jaringan yang terbuat dari serat kaca disebut...',NULL,'UTP',NULL,'STP',NULL,'Coaxial',NULL,'Fiber Optic',NULL,'USB',NULL,'d',10,NULL,0,'2026-10-18 12:52:31');
/*!40000 ALTER TABLE `soal` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ujian`
--

DROP TABLE IF EXISTS `ujian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ujian` (
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
  `target_nilai` decimal(5,2) NOT NULL DEFAULT '75.00',
  `wajib_tuntas` enum('ya','tidak') NOT NULL DEFAULT 'ya',
  PRIMARY KEY (`id`),
  KEY `idx_ujian_status` (`status`,`tgl_dibuat` DESC)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ujian`
--

LOCK TABLES `ujian` WRITE;
/*!40000 ALTER TABLE `ujian` DISABLE KEYS */;
INSERT INTO `ujian` VALUES (1,'Latihan 1 - Pengenalan Jaringan Komputer (Kelas X)','Pelajari materi di bawah ini terlebih dahulu, lalu kerjakan soal. Anda dinyatakan TUNTAS bila mencapai nilai minimal 75. Bila belum tuntas, ulangi sampai tuntas!','aktif','2026-10-18 20:52:31','2026-10-18 12:58:57','',NULL,'tidak',10,'tidak',0,'ya','tidak','ya','ya',75.00,'ya');
/*!40000 ALTER TABLE `ujian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ulangan_lms'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-19  6:26:40
