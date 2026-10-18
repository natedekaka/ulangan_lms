<?php
// config/tuntas.php - Logika "Ulangan Sampai Tuntas"
//
// Aturan:
//   nilai   = (total_skor / total_poin_maks) * 100
//   tuntas  = nilai >= target_nilai (default 75)
//   Bila belum tuntas, siswa WAJIB mengulang (semua soal diacak ulang).
//   Nilai akhir yang dilaporkan = NILAI TERTINGGI dari semua percobaan.
//
// Setiap percobaan dicatat di tabel `percobaan_ujian`.
// Tabel `hasil_ujian` tetap dipakai (kompatibel dengan fitur exam6 yang lain)
// dan hanya menyimpan percobaan TERBAIK per (id_ujian, nis).

/**
 * Ambil total poin maksimal dari seluruh soal sebuah ujian.
 */
function tuntasTotalPoinMaks($conn, $id_ujian) {
    $id_ujian = (int)$id_ujian;
    $result = $conn->query("SELECT COALESCE(SUM(poin), 0) AS total FROM soal WHERE id_ujian = $id_ujian");
    if (!$result) {
        return 0;
    }
    $row = $result->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

/**
 * Ambil pengaturan tuntas sebuah ujian.
 * Return: ['target' => float, 'wajib_tuntas' => bool]
 */
function tuntasInfoUjian($conn, $id_ujian) {
    $stmt = $conn->prepare("SELECT target_nilai, wajib_tuntas FROM ujian WHERE id = ?");
    $info = ['target' => 75.0, 'wajib_tuntas' => true];

    if ($stmt) {
        $stmt->bind_param("i", $id_ujian);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            if (isset($row['target_nilai']) && $row['target_nilai'] !== null) {
                $info['target'] = (float)$row['target_nilai'];
            }
            if (isset($row['wajib_tuntas']) && $row['wajib_tuntas'] !== null) {
                $info['wajib_tuntas'] = ($row['wajib_tuntas'] === 'ya');
            }
        }
    }

    return $info;
}

/**
 * Hitung nilai (persentase 0-100) dari skor mentah.
 */
function tuntasHitungNilai($total_skor, $total_poin_maks) {
    if ($total_poin_maks <= 0) {
        return 0.0;
    }
    return round(((float)$total_skor / (float)$total_poin_maks) * 100, 2);
}

/**
 * Ambil baris hasil TERBAIK (hasil_ujian) untuk siswa.
 */
function tuntasHasilTerbaik($conn, $id_ujian, $nis) {
    $stmt = $conn->prepare("SELECT * FROM hasil_ujian WHERE id_ujian = ? AND nis = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/**
 * Apakah siswa punya izin remedi (fitur lama exam6).
 */
function tuntasAdaIzinRemedi($conn, $id_ujian, $nis) {
    $stmt = $conn->prepare("SELECT id FROM izin_remedi WHERE id_ujian = ? AND nis = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("is", $id_ujian, $nis);
    $stmt->execute();
    $has = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $has;
}

/**
 * Apakah siswa masih boleh mengerjakan / mengulang ujian ini?
 *   - Belum pernah submit            -> boleh
 *   - Sudah submit tapi belum tuntas -> boleh (wajib mengulang)
 *   - Ada izin remedi                -> boleh
 *   - Sudah tuntas & tanpa izin      -> TIDAK boleh
 */
function tuntasBolehUlang($conn, $id_ujian, $nis) {
    $hasil = tuntasHasilTerbaik($conn, $id_ujian, $nis);
    if (!$hasil) {
        return true;
    }

    if (tuntasAdaIzinRemedi($conn, $id_ujian, $nis)) {
        return true;
    }

    $info = tuntasInfoUjian($conn, $id_ujian);
    if ($info['wajib_tuntas']) {
        $poin  = tuntasTotalPoinMaks($conn, $id_ujian);
        $nilai = tuntasHitungNilai((int)$hasil['total_skor'], $poin);
        if ($nilai < $info['target']) {
            return true;
        }
    }

    return false;
}

/**
 * Ringkasan status tuntas siswa untuk sebuah ujian.
 * Return: ['pernah' => bool, 'nilai' => float, 'target' => float, 'tuntas' => bool, 'percobaan' => int]
 */
function tuntasStatusSiswa($conn, $id_ujian, $nis) {
    $info = tuntasInfoUjian($conn, $id_ujian);
    $poin = tuntasTotalPoinMaks($conn, $id_ujian);

    $status = [
        'pernah'    => false,
        'nilai'     => 0.0,
        'target'    => $info['target'],
        'tuntas'    => false,
        'percobaan' => 0,
        'boleh_ulang' => true,
    ];

    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM percobaan_ujian WHERE id_ujian = ? AND nis = ?");
    if ($stmt) {
        $stmt->bind_param("is", $id_ujian, $nis);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $status['percobaan'] = (int)($row['c'] ?? 0);
    }

    $hasil = tuntasHasilTerbaik($conn, $id_ujian, $nis);
    if ($hasil) {
        $status['pernah'] = true;
        $status['nilai']  = tuntasHitungNilai((int)$hasil['total_skor'], $poin);
        $status['tuntas'] = ($status['nilai'] >= $info['target']);
    }

    $status['boleh_ulang'] = tuntasBolehUlang($conn, $id_ujian, $nis);

    return $status;
}

/**
 * Catat satu percobaan ujian dan perbarui hasil terbaik.
 *
 * Return array info hasil (nilai, target, tuntas, percobaan_ke, dst).
 */
function tuntasCatatHasil($conn, $id_ujian, $nis, $nama, $kelas, $total_skor, $skor_awal, $detail_jawaban_json, $ip = null, $device = null, $jumlah_benar = null, $jumlah_soal = null) {
    $info = tuntasInfoUjian($conn, $id_ujian);
    $poin = tuntasTotalPoinMaks($conn, $id_ujian);
    $nilai = tuntasHitungNilai($total_skor, $poin);
    $tuntas = ($nilai >= $info['target']) ? 1 : 0;

    // Nomor percobaan berikutnya
    $percobaan_ke = 1;
    $stmt = $conn->prepare("SELECT COALESCE(MAX(percobaan_ke), 0) AS mx FROM percobaan_ujian WHERE id_ujian = ? AND nis = ?");
    if ($stmt) {
        $stmt->bind_param("is", $id_ujian, $nis);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $percobaan_ke = ((int)($row['mx'] ?? 0)) + 1;
    }

    if ($jumlah_benar === null && $detail_jawaban_json) {
        $detail = json_decode($detail_jawaban_json, true);
        if (is_array($detail)) {
            $jumlah_benar = count(array_filter($detail, fn($d) => !empty($d['is_correct'])));
            $jumlah_soal  = count($detail);
        }
    }
    $jumlah_benar = (int)($jumlah_benar ?? 0);
    $jumlah_soal  = (int)($jumlah_soal ?? 0);

    // 1) Simpan percobaan (selalu, walau lebih rendah dari sebelumnya)
    $stmt = $conn->prepare("INSERT INTO percobaan_ujian
        (id_ujian, nis, nama, kelas, percobaan_ke, total_skor, total_poin_maks, nilai,
         jumlah_benar, jumlah_soal, tuntas, skor_awal, detail_jawaban, ip_address, device_fingerprint)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param(
            "isssiiidiiiisss",
            $id_ujian, $nis, $nama, $kelas, $percobaan_ke,
            $total_skor, $poin, $nilai, $jumlah_benar, $jumlah_soal,
            $tuntas, $skor_awal, $detail_jawaban_json, $ip, $device
        );
        $stmt->execute();
        $stmt->close();
    }

    // 2) Perbarui hasil_ujian HANYA bila percobaan ini lebih baik
    $existing = tuntasHasilTerbaik($conn, $id_ujian, $nis);
    $nilai_terbaik = $nilai;

    if ($existing) {
        $nilai_lama = tuntasHitungNilai((int)$existing['total_skor'], $poin);
        $nilai_terbaik = max($nilai, $nilai_lama);

        if ($nilai > $nilai_lama) {
            $stmt = $conn->prepare("UPDATE hasil_ujian
                SET nama = ?, kelas = ?, total_skor = ?, skor_awal = ?, detail_jawaban = ?,
                    ip_address = ?, device_fingerprint = ?, submitted_at = NOW()
                WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ssiisssi",
                    $nama, $kelas, $total_skor, $skor_awal, $detail_jawaban_json,
                    $ip, $device, $existing['id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO hasil_ujian
            (id_ujian, nis, nama, kelas, total_skor, skor_awal, detail_jawaban, ip_address, device_fingerprint, submitted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("isssiisss",
                $id_ujian, $nis, $nama, $kelas, $total_skor, $skor_awal,
                $detail_jawaban_json, $ip, $device);
            $stmt->execute();
            $stmt->close();
        }
    }

    return [
        'nilai'           => $nilai,
        'nilai_terbaik'   => $nilai_terbaik,
        'target'          => $info['target'],
        'wajib_tuntas'    => $info['wajib_tuntas'],
        'total_poin_maks' => $poin,
        'tuntas'          => (bool)$tuntas,
        'percobaan_ke'    => $percobaan_ke,
    ];
}

