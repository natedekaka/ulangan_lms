<?php
// config/materi.php - Helper fitur Materi (wajib dibaca siswa sebelum mengerjakan soal)
//
// Tabel:
//   materi          -> daftar materi belajar per ujian (dikelola admin/guru)
//   materi_progress -> penanda bahwa siswa (NIS) sudah membaca materi tertentu
//
// Idempoten: semua fungsi aman dipanggil berkali-kali.

require_once __DIR__ . '/database.php';

function initMateriTables($conn) {
    $table = $conn->query("SHOW TABLES LIKE 'materi'");
    if ($table && $table->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `materi` (
            `id` int NOT NULL AUTO_INCREMENT,
            `id_ujian` int NOT NULL,
            `judul` varchar(255) NOT NULL,
            `konten` mediumtext,
            `urutan` int DEFAULT 0,
            `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_materi_ujian` (`id_ujian`),
            CONSTRAINT `fk_materi_ujian` FOREIGN KEY (`id_ujian`) REFERENCES `ujian` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    }

    $table = $conn->query("SHOW TABLES LIKE 'materi_progress'");
    if ($table && $table->num_rows === 0) {
        $conn->query("CREATE TABLE IF NOT EXISTS `materi_progress` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci");
    }
}

/** Daftar materi sebuah ujian, terurut. $with_konten = false untuk daftar ringkas. */
function materiDaftar($conn, $id_ujian, $with_konten = true) {
    $kolom = $with_konten ? 'id, judul, konten, urutan' : 'id, judul, urutan';
    $stmt = $conn->prepare("SELECT $kolom FROM materi WHERE id_ujian = ? ORDER BY urutan ASC, id ASC");
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $daftar = [];
    while ($row = $result->fetch_assoc()) {
        $daftar[] = $row;
    }
    $stmt->close();
    return $daftar;
}

/** Jumlah materi pada sebuah ujian. */
function materiJumlah($conn, $id_ujian) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM materi WHERE id_ujian = ?");
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

/** ID materi yang sudah dibaca siswa (NIS) pada ujian tertentu. */
function materiSudahDibaca($conn, $id_ujian, $nis) {
    $stmt = $conn->prepare("
        SELECT m.id FROM materi m
        JOIN materi_progress p ON p.id_materi = m.id AND p.nis = ?
        WHERE m.id_ujian = ?
        ORDER BY m.urutan ASC, m.id ASC
    ");
    $stmt->bind_param("si", $nis, $id_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['id'];
    }
    $stmt->close();
    return $ids;
}

/**
 * Jumlah materi yang BELUM dibaca siswa pada ujian tertentu.
 * Return 0 artinya (bila ada materi) siswa sudah membaca semuanya.
 */
function materiBelumDibaca($conn, $id_ujian, $nis) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total FROM materi m
        WHERE m.id_ujian = ?
          AND NOT EXISTS (
              SELECT 1 FROM materi_progress p
              WHERE p.id_materi = m.id AND p.nis = ?
          )
    ");
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['total'] ?? 0);
}

/** Tandai satu materi sudah dibaca (idempoten via ON DUPLICATE KEY). */
function materiTandaiDibaca($conn, $id_materi, $nis, $nama = null, $kelas = null) {
    $stmt = $conn->prepare("
        INSERT INTO materi_progress (id_materi, nis, nama, kelas) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE nama = VALUES(nama), kelas = VALUES(kelas), waktu_dibaca = waktu_dibaca
    ");
    $stmt->bind_param("isss", $id_materi, $nis, $nama, $kelas);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/** Hapus progres baca materi siswa untuk sebuah ujian (agar wajib baca ulang saat mengulang). */
function materiResetProgress($conn, $id_ujian, $nis) {
    $stmt = $conn->prepare("
        DELETE p FROM materi_progress p
        JOIN materi m ON m.id = p.id_materi
        WHERE m.id_ujian = ? AND p.nis = ?
    ");
    if (!$stmt) return false;
    $stmt->bind_param("is", $id_ujian, $nis);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}


initMateriTables($conn);
?>
