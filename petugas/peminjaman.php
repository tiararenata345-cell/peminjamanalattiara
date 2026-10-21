<?php
require_once "../koneksi.php";
wajibRole("petugas");

$data = mysqli_query($koneksi, "
    SELECT p.*, u.nama, a.nama_alat
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN alat a ON a.id = p.alat_id
    ORDER BY p.id DESC
");

$total = 0;
$menunggu = 0;
$disetujui = 0;
$ditolak = 0;
$selesai = 0;

if ($data) {
    while ($r = mysqli_fetch_assoc($data)) {
        $total++;

        if ($r["status"] === "menunggu") {
            $menunggu++;
        } elseif ($r["status"] === "disetujui") {
            $disetujui++;
        } elseif ($r["status"] === "ditolak") {
            $ditolak++;
        } elseif ($r["status"] === "selesai") {
            $selesai++;
        }
    }

    mysqli_data_seek($data, 0);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Peminjaman</title>

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
            <a class="active" href="peminjaman.php">Kelola Peminjaman</a>
            <a href="pengembalian.php">Kelola Pengembalian</a>
        </aside>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Kelola Peminjaman</h3>
            <p class="text-muted">Periksa pengajuan dan tentukan status peminjaman alat.</p>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    Status peminjaman berhasil diperbarui.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"])): ?>
                <div class="alert alert-danger">
                    Gagal memproses pengajuan. Periksa status peminjaman dan stok alat.
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <?php
                $cards = [
                    ["Total Peminjaman", $total],
                    ["Pengajuan Menunggu", $menunggu],
                    ["Peminjaman Disetujui", $disetujui],
                    ["Peminjaman Ditolak", $ditolak],
                    ["Peminjaman Selesai", $selesai]
                ];

                foreach ($cards as $c):
                ?>
                    <div class="col-md-4 col-lg">
                        <div class="card stat-card h-100">
                            <div class="text-muted"><?= e($c[0]) ?></div>
                            <div class="stat-number"><?= $c[1] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card p-3">
                <h5 class="mb-3">Daftar Pengajuan Peminjaman</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Nama Alat</th>
                                <th>Jumlah</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($data && mysqli_num_rows($data) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td><?= (int)$r["id"] ?></td>
                                        <td><?= e($r["nama"]) ?></td>
                                        <td><?= e($r["nama_alat"]) ?></td>
                                        <td><?= (int)$r["jumlah"] ?></td>
                                        <td><?= e($r["tanggal_pinjam"]) ?></td>
                                        <td><?= e($r["tanggal_kembali"]) ?></td>

                                        <td>
                                            <?php if ($r["status"] === "menunggu"): ?>
                                                <span class="badge text-bg-warning">Menunggu</span>
                                            <?php elseif ($r["status"] === "disetujui"): ?>
                                                <span class="badge text-bg-success">Disetujui</span>
                                            <?php elseif ($r["status"] === "ditolak"): ?>
                                                <span class="badge text-bg-danger">Ditolak</span>
                                            <?php elseif ($r["status"] === "selesai"): ?>
                                                <span class="badge text-bg-primary">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-secondary"><?= e($r["status"]) ?></span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?php if ($r["status"] === "menunggu"): ?>
                                                <div class="d-flex gap-1">
                                                    <form method="POST" action="../proses/peminjaman.php">
                                                        <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                                                        <button type="submit" name="aksi" value="disetujui"
                                                                class="btn btn-success btn-sm">
                                                            Setujui
                                                        </button>
                                                    </form>

                                                    <form method="POST" action="../proses/peminjaman.php"
                                                          onsubmit="return confirm('Tolak pengajuan ini?')">
                                                        <input type="hidden" name="id" value="<?= (int)$r["id"] ?>">
                                                        <button type="submit" name="aksi" value="ditolak"
                                                                class="btn btn-danger btn-sm">
                                                            Tolak
                                                        </button>
                                                    </form>
                                                </div>

                                            <?php elseif ($r["status"] === "disetujui"): ?>
                                                <a href="pengembalian.php?id=<?= (int)$r["id"] ?>"
                                                   class="btn btn-primary btn-sm">
                                                    Kembalikan
                                                </a>

                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">
                                        Belum ada data peminjaman.
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