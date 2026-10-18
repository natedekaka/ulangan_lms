<?php
// config/init_sekolah.php - Inisialisasi data sekolah

require_once 'database.php';
require_once __DIR__ . '/materi.php'; // tabel materi + materi_progress (fitur belajar sebelum ujian)

$konfigurasi_cache = null;

function initKonfigurasiSekolah($conn) {
    global $konfigurasi_cache;
    
    $table_check = $conn->query("SHOW TABLES LIKE 'konfigurasi_sekolah'");

    if ($table_check->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS konfigurasi_sekolah (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nama_sekolah VARCHAR(255) NOT NULL DEFAULT 'SMA Negeri 6 Cimahi',
            logo VARCHAR(255) DEFAULT NULL,
            warna_primer VARCHAR(20) DEFAULT '#667eea',
            warna_sekunder VARCHAR(20) DEFAULT '#764ba2',
            tampilkan_riwayat ENUM('ya','tidak') DEFAULT 'ya',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $conn->query("INSERT INTO konfigurasi_sekolah (nama_sekolah) VALUES ('SMA Negeri 6 Cimahi')");
    } else {
        $col_exists = $conn->query("SHOW COLUMNS FROM konfigurasi_sekolah LIKE 'tampilkan_riwayat'");
        if ($col_exists->num_rows === 0) {
            $conn->query("ALTER TABLE konfigurasi_sekolah ADD COLUMN tampilkan_riwayat ENUM('ya','tidak') DEFAULT 'ya'");
        }
    }
}

function getKonfigurasiSekolah($conn) {
    global $konfigurasi_cache;
    
    if ($konfigurasi_cache !== null) {
        return $konfigurasi_cache;
    }
    
    $result = $conn->query("SELECT * FROM konfigurasi_sekolah LIMIT 1");
    $konfigurasi_cache = $result->fetch_assoc();
    return $konfigurasi_cache;
}

function updateKonfigurasiSekolah($conn, $nama_sekolah, $logo, $warna_primer, $warna_sekunder, $tampilkan_riwayat = 'ya') {
    global $konfigurasi_cache;
    
    $stmt = $conn->prepare("UPDATE konfigurasi_sekolah SET nama_sekolah = ?, logo = ?, warna_primer = ?, warna_sekunder = ?, tampilkan_riwayat = ? WHERE id = 1");
    $stmt->bind_param("sssss", $nama_sekolah, $logo, $warna_primer, $warna_sekunder, $tampilkan_riwayat);
    $result = $stmt->execute();
    $stmt->close();
    
    $konfigurasi_cache = null;
    
    return $result;
}

initKonfigurasiSekolah($conn);

function initConcurrencyControl($conn) {
    $result = $conn->query("SHOW COLUMNS FROM ujian LIKE 'updated_at'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE ujian ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
    
    $result = $conn->query("SHOW COLUMNS FROM soal LIKE 'updated_at'");
    if ($result->num_rows === 0) {
        $conn->query("ALTER TABLE soal ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }
    
    $table_check = $conn->query("SHOW TABLES LIKE 'jawaban_sEMENTARA'");
    if ($table_check->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `jawaban_sEMENTARA` (
            `id` int NOT NULL AUTO_INCREMENT,
            `id_ujian` int NOT NULL,
            `nis` varchar(50) NOT NULL,
            `nama` varchar(100) DEFAULT NULL,
            `kelas` varchar(50) DEFAULT NULL,
            `answers` json DEFAULT NULL,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_ujian_nis` (`id_ujian`, `nis`),
            INDEX `idx_nis` (`nis`),
            INDEX `idx_ujian` (`id_ujian`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    }
    
    $table_check = $conn->query("SHOW TABLES LIKE 'exam_violations'");
    if ($table_check->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `exam_violations` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `id_ujian` INT NOT NULL,
            `nis` VARCHAR(50) NOT NULL,
            `jenis_violation` VARCHAR(50) NOT NULL,
            `detail` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `idx_ujian_nis` (`id_ujian`, `nis`),
            INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    }
}

initConcurrencyControl($conn);

/**
 * Inisialisasi fitur "Ulangan Sampai Tuntas".
 * Idempoten: aman dipanggil berkali-kali.
 */
function initUlanganTuntas($conn) {
    // Kolom target nilai (syarat tuntas, default 75).
    $col = $conn->query("SHOW COLUMNS FROM ujian LIKE 'target_nilai'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE ujian ADD COLUMN target_nilai DECIMAL(5,2) NOT NULL DEFAULT 75.00 AFTER tampilkan_skor");
    }

    // Kolom wajib tuntas (ya = siswa harus mencapai target untuk dinyatakan lulus).
    $col = $conn->query("SHOW COLUMNS FROM ujian LIKE 'wajib_tuntas'");
    if ($col && $col->num_rows === 0) {
        $conn->query("ALTER TABLE ujian ADD COLUMN wajib_tuntas ENUM('ya','tidak') NOT NULL DEFAULT 'ya' AFTER target_nilai");
    }

    // Tabel riwayat percobaan ujian (setiap percobaan dicatat).
    $table = $conn->query("SHOW TABLES LIKE 'percobaan_ujian'");
    if ($table && $table->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `percobaan_ujian` (
            `id` int NOT NULL AUTO_INCREMENT,
            `id_ujian` int NOT NULL,
            `nis` varchar(50) NOT NULL,
            `nama` varchar(100) NOT NULL,
            `kelas` varchar(50) NOT NULL,
            `percobaan_ke` int NOT NULL DEFAULT 1,
            `total_skor` int NOT NULL DEFAULT 0,
            `total_poin_maks` int NOT NULL DEFAULT 0,
            `nilai` decimal(5,2) NOT NULL DEFAULT 0.00,
            `jumlah_benar` int NOT NULL DEFAULT 0,
            `jumlah_soal` int NOT NULL DEFAULT 0,
            `tuntas` tinyint(1) NOT NULL DEFAULT 0,
            `skor_awal` int DEFAULT NULL,
            `detail_jawaban` longtext,
            `ip_address` varchar(45) DEFAULT NULL,
            `device_fingerprint` varchar(64) DEFAULT NULL,
            `waktu_submit` datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_ujian_nis` (`id_ujian`,`nis`),
            KEY `idx_ujian_tuntas` (`id_ujian`,`tuntas`),
            CONSTRAINT `fk_percobaan_ujian` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    }
}

initUlanganTuntas($conn);
?>
