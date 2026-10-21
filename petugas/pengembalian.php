<?php
require_once "../koneksi.php";
wajibRole("petugas");

$pilihan = mysqli_query($koneksi, "
    SELECT p.id, u.nama, a.nama_alat, p.jumlah, p.tanggal_kembali
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN alat a ON a.id = p.alat_id
    WHERE p.status = 'disetujui'
    AND NOT EXISTS (
        SELECT 1 FROM pengembalian g
        WHERE g.peminjaman_id = p.id
    )
    ORDER BY p.id DESC
");

$data = mysqli_query($koneksi, "
    SELECT g.*, u.nama, a.nama_alat
    FROM pengembalian g
    JOIN peminjaman p ON p.id = g.peminjaman_id
    JOIN users u ON u.id = p.user_id
    JOIN alat a ON a.id = p.alat_id
    ORDER BY g.id DESC
");

$totalPengembalian = 0;
$totalDenda = 0;
$kondisiBaik = 0;
$kondisiRusak = 0;

if ($data) {
    while ($r = mysqli_fetch_assoc($data)) {
        $totalPengembalian++;
        $totalDenda += (float)$r["denda"];

        if (strtolower($r["kondisi_alat"]) === "baik") {
            $kondisiBaik++;
        } else {
            $kondisiRusak++;
        }
    }

    mysqli_data_seek($data, 0);
}

$totalMenungguKembali = $pilihan ? mysqli_num_rows($pilihan) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pengembalian Alat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar p-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">PEMINJAMAN ALAT</a>

        <div class="text-white">
            Halo, <b><?= e($_SESSION["nama"]) ?></b>
            <a href="../logout.php" class="btn btn-light btn-sm ms-3"
               onclick="return confirm('Yakin ingin logout?')">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <aside class="col-md-2 sidebar">
            <a href="index.php">Dashboard</a>
            <a href="peminjaman.php">Kelola Peminjaman</a>
            <a class="active" href="pengembalian.php">Kelola Pengembalian</a>
        </aside>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Proses Pengembalian</h3>
            <p class="text-muted">Catat pengembalian alat dan periksa kondisi alat setelah digunakan.</p>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    Pengembalian berhasil dicatat.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"])): ?>
                <div class="alert alert-danger">
                    Pengembalian gagal diproses. Periksa data peminjaman dan pastikan alat belum dikembalikan.
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <?php
                $cards = [
                    ["Menunggu Pengembalian", $totalMenungguKembali],
                    ["Total Pengembalian", $totalPengembalian],
                    ["Kondisi Baik", $kondisiBaik],
                    ["Kondisi Perlu Diperiksa", $kondisiRusak],
                    ["Total Denda", "Rp " . number_format($totalDenda, 0, ",", ".")]
                ];

                foreach ($cards as $c):
                ?>
                    <div class="col-md-4 col-lg">
                        <div class="card stat-card h-100">
                            <div class="text-muted small"><?= e($c[0]) ?></div>
                            <div class="stat-number"><?= e((string)$c[1]) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card p-4 mb-4">
                <h5 class="mb-3">Form Pengembalian</h5>

                <form method="POST" action="../proses/pengembalian.php">

                    <div class="mb-3">
                        <label class="form-label">Pilih Peminjaman</label>
                        <select name="peminjaman_id" class="form-select" required>
                            <option value="">-- Pilih Peminjaman --</option>

                            <?php if ($pilihan): ?>
                                <?php while ($p = mysqli_fetch_assoc($pilihan)): ?>
                                    <option value="<?= (int)$p["id"] ?>">
                                        #<?= (int)$p["id"] ?>
                                        - <?= e($p["nama"]) ?>
                                        - <?= e($p["nama_alat"]) ?>
                                        (<?= (int)$p["jumlah"] ?>)
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <?php if ($totalMenungguKembali === 0): ?>
                        <div class="alert alert-info">
                            Tidak ada peminjaman yang menunggu pengembalian saat ini.
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Tanggal Dikembalikan</label>
                        <input type="date" name="tanggal_dikembalikan"
                               class="form-control" value="<?= date("Y-m-d") ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Denda (Rp)</label>
                        <input type="number" name="denda" class="form-control"
                               min="0" step="0.01" value="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kondisi Alat Saat Kembali</label>
                        <select name="kondisi_alat" class="form-select" required>
                            <option value="baik">Baik</option>
                            <option value="rusak ringan">Rusak Ringan</option>
                            <option value="rusak berat">Rusak Berat</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary"
                        <?= $totalMenungguKembali === 0 ? "disabled" : "" ?>>
                        Simpan Pengembalian
                    </button>

                </form>
            </div>

            <div class="card p-3">
                <h5 class="mb-3">Riwayat Pengembalian</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Nama Alat</th>
                                <th>Tanggal Dikembalikan</th>
                                <th>Denda</th>
                                <th>Kondisi Alat</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data && mysqli_num_rows($data) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td><?= (int)$r["id"] ?></td>
                                        <td><?= e($r["nama"]) ?></td>
                                        <td><?= e($r["nama_alat"]) ?></td>
                                        <td><?= e($r["tanggal_dikembalikan"]) ?></td>
                                        <td>
                                            Rp <?= number_format((float)$r["denda"], 0, ",", ".") ?>
                                        </td>
                                        <td>
                                            <?php if (strtolower($r["kondisi_alat"]) === "baik"): ?>
                                                <span class="badge text-bg-success">
                                                    <?= e($r["kondisi_alat"]) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge text-bg-warning">
                                                    <?= e($r["kondisi_alat"]) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">
                                        Belum ada riwayat pengembalian.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>