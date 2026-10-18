<?php
// admin/materi.php - Kelola Materi Belajar per Ujian (wajib dibaca siswa sebelum mengerjakan soal)

session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../config/init_sekolah.php';
require_once '../config/materi.php';

$sekolah = getKonfigurasiSekolah($conn);

$message = '';
$message_type = '';

/** Tampilkan pesan setelah POST-redirect-GET. */
$pesan_map = [
    'tambah'    => ['Materi berhasil ditambahkan.', 'success'],
    'edit'      => ['Materi berhasil diperbarui.', 'success'],
    'hapus'     => ['Materi berhasil dihapus.', 'success'],
    'urutan'    => ['Urutan materi diperbarui.', 'success'],
    'gagal'     => ['Gagal menyimpan materi. Periksa isian Anda.', 'danger'],
    'kosong'    => ['Judul materi tidak boleh kosong.', 'danger'],
    'tidak_ada' => ['Ujian tidak ditemukan.', 'danger'],
];
if (isset($_GET['msg']) && isset($pesan_map[$_GET['msg']])) {
    $message = $pesan_map[$_GET['msg']][0];
    $message_type = $pesan_map[$_GET['msg']][1];
}

/** Normalisasi urutan materi sebuah ujian menjadi 1..N sesuai urutan tampil. */
function materiNormalisasiUrutan($conn, $id_ujian) {
    $stmt = $conn->prepare("SELECT id FROM materi WHERE id_ujian = ? ORDER BY urutan ASC, id ASC");
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['id'];
    }
    $stmt->close();

    $upd = $conn->prepare("UPDATE materi SET urutan = ? WHERE id = ? AND id_ujian = ?");
    foreach ($ids as $i => $id) {
        $urutan = $i + 1;
        $upd->bind_param("iii", $urutan, $id, $id_ujian);
        $upd->execute();
    }
    $upd->close();
}

/** Geser materi naik/turun dalam urutan. */
function materiGeser($conn, $id_ujian, $id_materi, $arah) {
    $stmt = $conn->prepare("SELECT id FROM materi WHERE id_ujian = ? ORDER BY urutan ASC, id ASC");
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = (int)$row['id'];
    }
    $stmt->close();

    $idx = array_search($id_materi, $ids, true);
    if ($idx === false) return false;

    $target = ($arah === 'naik') ? $idx - 1 : $idx + 1;
    if ($target < 0 || $target >= count($ids)) return false;

    [$ids[$idx], $ids[$target]] = [$ids[$target], $ids[$idx]];

    $upd = $conn->prepare("UPDATE materi SET urutan = ? WHERE id = ? AND id_ujian = ?");
    foreach ($ids as $i => $id) {
        $urutan = $i + 1;
        $upd->bind_param("iii", $urutan, $id, $id_ujian);
        $upd->execute();
    }
    $upd->close();
    return true;
}

// === Daftar ujian (untuk pemilih ujian) ===
$ujian_list = [];
$result = $conn->query("SELECT id, judul_ujian, status FROM ujian ORDER BY tgl_dibuat DESC");
while ($row = $result->fetch_assoc()) {
    $ujian_list[] = $row;
}

$id_ujian = isset($_GET['ujian']) ? (int)$_GET['ujian'] : 0;
if ($id_ujian === 0 && !empty($ujian_list)) {
    $id_ujian = (int)$ujian_list[0]['id'];
}

$ujian_aktif = null;
foreach ($ujian_list as $u) {
    if ((int)$u['id'] === $id_ujian) {
        $ujian_aktif = $u;
        break;
    }
}

// === Proses POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($ujian_list)) {
    $id_ujian = (int)($_POST['id_ujian'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    $redirect = "materi.php?ujian=$id_ujian";

    $valid = false;
    foreach ($ujian_list as $u) {
        if ((int)$u['id'] === $id_ujian) { $valid = true; break; }
    }

    if (!$valid) {
        header('Location: materi.php?msg=tidak_ada');
        exit;
    }

    if ($aksi === 'tambah_materi') {
        $judul = trim($_POST['judul'] ?? '');
        $konten = trim($_POST['konten'] ?? '');
        $urutan = max(0, (int)($_POST['urutan'] ?? 0));

        if ($judul === '') {
            header('Location: ' . $redirect . '&msg=kosong');
            exit;
        }

        if ($urutan === 0) {
            $stmt = $conn->prepare("SELECT COALESCE(MAX(urutan), 0) + 1 AS next_urutan FROM materi WHERE id_ujian = ?");
            $stmt->bind_param("i", $id_ujian);
            $stmt->execute();
            $urutan = (int)($stmt->get_result()->fetch_assoc()['next_urutan'] ?? 1);
            $stmt->close();
        }

        $stmt = $conn->prepare("INSERT INTO materi (id_ujian, judul, konten, urutan) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isii", $id_ujian, $judul, $konten, $urutan);
        $ok = $stmt->execute();
        $stmt->close();

        materiNormalisasiUrutan($conn, $id_ujian);
        header('Location: ' . $redirect . '&msg=' . ($ok ? 'tambah' : 'gagal'));
        exit;

    } elseif ($aksi === 'edit_materi') {
        $id_materi = (int)($_POST['id_materi'] ?? 0);
        $judul = trim($_POST['judul'] ?? '');
        $konten = trim($_POST['konten'] ?? '');
        $urutan = max(1, (int)($_POST['urutan'] ?? 1));

        if ($judul === '') {
            header('Location: ' . $redirect . '&msg=kosong');
            exit;
        }

        $stmt = $conn->prepare("UPDATE materi SET judul = ?, konten = ?, urutan = ? WHERE id = ? AND id_ujian = ?");
        $stmt->bind_param("isiii", $judul, $konten, $urutan, $id_materi, $id_ujian);
        $ok = $stmt->execute();
        $stmt->close();

        materiNormalisasiUrutan($conn, $id_ujian);
        header('Location: ' . $redirect . '&msg=' . ($ok ? 'edit' : 'gagal'));
        exit;

    } elseif ($aksi === 'hapus_materi') {
        $id_materi = (int)($_POST['id_materi'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM materi WHERE id = ? AND id_ujian = ?");
        $stmt->bind_param("ii", $id_materi, $id_ujian);
        $ok = $stmt->execute();
        $stmt->close();

        materiNormalisasiUrutan($conn, $id_ujian);
        header('Location: ' . $redirect . '&msg=' . ($ok ? 'hapus' : 'gagal'));
        exit;

    } elseif ($aksi === 'geser_urutan') {
        $id_materi = (int)($_POST['id_materi'] ?? 0);
        $arah = ($_POST['arah'] ?? '') === 'naik' ? 'naik' : 'turun';
        materiGeser($conn, $id_ujian, $id_materi, $arah);
        header('Location: ' . $redirect . '&msg=urutan');
        exit;
    }

    header('Location: materi.php');
    exit;
}

// === Data materi + jumlah pembaca ===
$materi_daftar = [];
$jumlah_pembaca = [];
if ($id_ujian > 0) {
    $materi_daftar = materiDaftar($conn, $id_ujian);

    $stmt = $conn->prepare("
        SELECT p.id_materi, COUNT(*) AS jml
        FROM materi_progress p
        JOIN materi m ON m.id = p.id_materi
        WHERE m.id_ujian = ?
        GROUP BY p.id_materi
    ");
    $stmt->bind_param("i", $id_ujian);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $jumlah_pembaca[(int)$row['id_materi']] = (int)$row['jml'];
    }
    $stmt->close();
}

// Mode edit
$edit_materi = null;
if (isset($_GET['edit']) && $id_ujian > 0) {
    $edit_id = (int)$_GET['edit'];
    foreach ($materi_daftar as $m) {
        if ((int)$m['id'] === $edit_id) {
            $edit_materi = $m;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Materi - Admin</title>
    <link href="../vendor/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link href="../vendor/fonts/inter.css" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --sidebar-width: 260px;
        }

        * { font-family: 'Inter', sans-serif; }

        body { background-color: #f1f5f9; min-height: 100vh; }

        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-brand h5 { color: #fff; font-weight: 600; margin: 0; }

        .school-logo {
            width: 55px;
            height: 55px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            margin-bottom: 8px;
        }

        .sidebar a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            padding: 0.875rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
            font-size: 0.9375rem;
        }

        .sidebar a:hover { background: rgba(255,255,255,0.05); color: #fff; }
        .sidebar a.active { background: rgba(79, 70, 229, 0.2); color: #fff; border-left-color: var(--primary); }

        .main-content { margin-left: var(--sidebar-width); padding: 2rem; transition: margin-left 0.3s ease; }

        .page-header {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header h3 { margin: 0; font-weight: 600; color: var(--dark); }

        .card { border: none; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-header { background: #fff; border-bottom: 1px solid var(--border); padding: 1.25rem 1.5rem; font-weight: 600; }

        .form-control, .form-select {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.625rem 0.875rem;
        }

        textarea.form-control { min-height: 180px; font-size: 0.95rem; }

        .btn-primary { background: var(--primary); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }

        .materi-preview {
            background: var(--light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px 16px;
            line-height: 1.7;
            font-size: 0.95rem;
        }
        .materi-preview img { max-width: 100%; height: auto; }

        .urutan-btn {
            padding: 2px 6px;
            font-size: 0.8rem;
            line-height: 1;
        }

        .animate-fade-in { animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .mobile-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background: #1e293b;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 1.2rem;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
            .sidebar.active { transform: translateX(0); }
            .mobile-toggle { display: flex; }
            .overlay.show { display: block; }
            .main-content { margin-left: 0; width: 100%; padding: 4rem 1rem 1rem; }
            .page-header { padding: 1rem; flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .page-header h3 { font-size: 1.1rem; }
        }

        @media (max-width: 767.98px) {
            .card-body { padding: 1rem; }
            .btn { width: 100%; margin-bottom: 0.5rem; }
            .btn.urutan-btn, .btn.btn-icon { width: auto; margin-bottom: 0; }
        }
    </style>
</head>
<body>
    <button class="mobile-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="school-logo">
                <i class="bi bi-mortarboard-fill" style="font-size: 1.8rem;"></i>
            </div>
            <div class="text-white fw-bold" style="font-size: 0.85rem;"><?= htmlspecialchars($sekolah['nama_sekolah']) ?></div>
            <h5 class="mt-2"><i class="bi bi-gear me-1"></i>Admin Panel</h5>
        </div>
        <div class="sidebar-menu">
            <a href="index.php"><i class="bi bi-grid-1x2-fill"></i> Manajemen Ujian</a>
            <a href="materi.php" class="active"><i class="bi bi-book-half"></i> Materi Belajar</a>
            <a href="tambah_soal.php"><i class="bi bi-question-circle-fill"></i> Bank Soal</a>
            <a href="rekap_nilai.php"><i class="bi bi-bar-chart-fill"></i> Rekap Nilai</a>
            <a href="analytics.php"><i class="bi bi-graph-up"></i> Analytics</a>
            <a href="monitor_ujian.php"><i class="bi bi-display"></i> Monitor Ujian</a>
            <a href="profil_sekolah.php"><i class="bi bi-building"></i> Profil Sekolah</a>
            <?php if (isset($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'super_admin'): ?>
            <a href="manage_users.php"><i class="bi bi-people-fill"></i> Kelola Admin</a>
            <?php endif; ?>
            <a href="logout.php" class="text-warning mt-3"><i class="bi bi-box-arrow-right"></i> Logout (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header animate-fade-in">
            <h3><i class="bi bi-book-half me-2"></i>Kelola Materi Belajar</h3>
            <?php if (!empty($ujian_list)): ?>
            <form method="GET" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 text-muted small">Ujian:</label>
                <select name="ujian" class="form-select" style="min-width: 320px;" onchange="this.form.submit()">
                    <?php foreach ($ujian_list as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= (int)$u['id'] === $id_ujian ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['judul_ujian']) ?> (<?= $u['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show animate-fade-in">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (empty($ujian_list)): ?>
        <div class="card animate-fade-in">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-2 mb-0">Belum ada ujian. Buat ujian terlebih dahulu di menu Manajemen Ujian.</p>
                <a href="index.php" class="btn btn-primary mt-3"><i class="bi bi-plus-circle me-2"></i>Buat Ujian</a>
            </div>
        </div>
        <?php else: ?>

        <!-- Form Tambah / Edit Materi -->
        <div class="card animate-fade-in mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <i class="bi bi-<?= $edit_materi ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
                    <?= $edit_materi ? 'Edit Materi' : 'Tambah Materi Baru' ?>
                </span>
                <?php if ($edit_materi): ?>
                <a href="materi.php?ujian=<?= $id_ujian ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-x-lg"></i> Batal Edit
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" autocomplete="off">
                    <input type="hidden" name="id_ujian" value="<?= $id_ujian ?>">
                    <input type="hidden" name="aksi" value="<?= $edit_materi ? 'edit_materi' : 'tambah_materi' ?>">
                    <?php if ($edit_materi): ?>
                    <input type="hidden" name="id_materi" value="<?= (int)$edit_materi['id'] ?>">
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-semibold">Judul Materi <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" required maxlength="255"
                                   placeholder="Contoh: Pengertian Jaringan Komputer"
                                   value="<?= $edit_materi ? htmlspecialchars($edit_materi['judul']) : '' ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Urutan Tampil</label>
                            <input type="number" name="urutan" class="form-control" min="1"
                                   value="<?= $edit_materi ? max(1, (int)$edit_materi['urutan']) : count($materi_daftar) + 1 ?>">
                            <small class="text-muted">Kosongkan/klik naik-turun di daftar untuk atur urutan.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Isi Materi <span class="text-danger">*</span></label>
                        <textarea name="konten" class="form-control" required
                                  placeholder="Tulis materi di sini. Boleh memakai tag HTML sederhana: <p>, <b>, <i>, <ul>, <li>, <h5>, <img src=&quot;uploads/nama.jpg&quot;>"><?= htmlspecialchars($edit_materi['konten'] ?? '') ?></textarea>
                        <div class="form-text">
                            <i class="bi bi-info-circle me-1"></i>
                            Ditampilkan apa adanya kepada siswa. Gunakan tag HTML sederhana untuk paragraf dan daftar.
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i><?= $edit_materi ? 'Simpan Perubahan' : 'Tambah Materi' ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePreview()">
                            <i class="bi bi-eye me-2"></i>Preview
                        </button>
                    </div>

                    <div id="formPreview" class="materi-preview mt-3" style="display: none;"></div>
                </form>
            </div>
        </div>

        <!-- Daftar Materi -->
        <div class="card animate-fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-2"></i>Daftar Materi (<?= count($materi_daftar) ?>)</span>
                <small class="text-muted fw-normal">Siswa wajib membaca semua materi sebelum mengerjakan soal</small>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($materi_daftar)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;" class="text-center">No</th>
                                <th>Judul &amp; Isi Materi</th>
                                <th style="width: 110px;" class="text-center">Urutan</th>
                                <th style="width: 130px;" class="text-center">Dibaca</th>
                                <th style="width: 220px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($materi_daftar as $i => $m): ?>
                            <tr>
                                <td class="text-center fw-semibold"><?= $i + 1 ?></td>
                                <td>
                                    <div class="fw-semibold mb-1"><?= htmlspecialchars($m['judul']) ?></div>
                                    <details>
                                        <summary class="text-muted small" style="cursor: pointer;">
                                            <i class="bi bi-eye me-1"></i>Lihat isi materi
                                        </summary>
                                        <div class="materi-preview mt-2"><?= $m['konten'] ?></div>
                                    </details>
                                </td>
                                <td class="text-center">
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="id_ujian" value="<?= $id_ujian ?>">
                                        <input type="hidden" name="aksi" value="geser_urutan">
                                        <input type="hidden" name="id_materi" value="<?= (int)$m['id'] ?>">
                                        <div class="btn-group-vertical btn-group-sm">
                                            <button type="submit" name="arah" value="naik" class="btn btn-outline-secondary urutan-btn"
                                                    title="Naikkan" <?= $i === 0 ? 'disabled' : '' ?>>
                                                <i class="bi bi-chevron-up"></i>
                                            </button>
                                            <button type="submit" name="arah" value="turun" class="btn btn-outline-secondary urutan-btn"
                                                    title="Turunkan" <?= $i === count($materi_daftar) - 1 ? 'disabled' : '' ?>>
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        </div>
                                    </form>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $jumlah_pembaca[(int)$m['id']] ?? 0 ?> siswa</span>
                                </td>
                                <td class="text-center">
                                    <a href="materi.php?ujian=<?= $id_ujian ?>&edit=<?= (int)$m['id'] ?>"
                                       class="btn btn-sm btn-outline-primary btn-icon" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus materi ini? Progres baca siswa untuk materi ini ikut terhapus.');">
                                        <input type="hidden" name="id_ujian" value="<?= $id_ujian ?>">
                                        <input type="hidden" name="aksi" value="hapus_materi">
                                        <input type="hidden" name="id_materi" value="<?= (int)$m['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger btn-icon" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-2 mb-1">Belum ada materi untuk ujian ini.</p>
                    <small class="text-muted">Tambahkan materi di formulir di atas agar siswa membacanya sebelum mengerjakan soal.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <script src="../vendor/bootstrap/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.querySelector('.overlay').classList.toggle('show');
        }

        function togglePreview() {
            const box = document.getElementById('formPreview');
            const ta = document.querySelector('textarea[name="konten"]');
            if (box.style.display === 'none') {
                box.innerHTML = ta.value || '<em>Belum ada isi materi.</em>';
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
            }
        }
    </script>
</body>
</html>
