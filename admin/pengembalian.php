<?php
require_once "../koneksi.php";
wajibRole("admin");

$data = mysqli_query($koneksi, "
    SELECT g.*, p.user_id, u.nama, a.nama_alat
    FROM pengembalian g
    JOIN peminjaman p ON p.id = g.peminjaman_id
    JOIN users u ON u.id = p.user_id
    JOIN alat a ON a.id = p.alat_id
    ORDER BY g.id DESC
");

$total = 0;
$totalDenda = 0;
$kondisiBaik = 0;
$kondisiRusak = 0;

if ($data) {
    while ($r = mysqli_fetch_assoc($data)) {
        $total++;
        $totalDenda += (float)$r["denda"];

        if (strtolower($r["kondisi_alat"]) === "baik") {
            $kondisiBaik++;
        } else {
            $kondisiRusak++;
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
    <title>Data Pengembalian</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4">
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

        <div class="col-md-2 sidebar">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Data Pengguna</a>
            <a href="kategori.php">Data Kategori</a>
            <a href="alat.php">Data Alat</a>
            <a href="peminjaman.php">Data Peminjaman</a>
            <a class="active" href="pengembalian.php">Data Pengembalian</a>
            <a href="laporan.php">Laporan</a>
        </div>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Data Pengembalian</h3>
            <p class="text-muted">Informasi pengembalian alat, kondisi alat, dan denda peminjaman.</p>

            <div class="row g-3 mb-4">
                <?php
                $cards = [
                    ["Total Pengembalian", $total],
                    ["Kondisi Baik", $kondisiBaik],
                    ["Kondisi Perlu Diperiksa", $kondisiRusak],
                    ["Total Denda", "Rp " . number_format($totalDenda, 0, ",", ".")]
                ];

                foreach ($cards as $c):
                ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card stat-card h-100">
                            <div class="text-muted small"><?= e($c[0]) ?></div>
                            <div class="stat-number"><?= e((string)$c[1]) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card p-3">
                <h5 class="mb-3">Riwayat Pengembalian Alat</h5>

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
                                <th>Diterima Oleh</th>
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
                                        <td><?= e($r["diterima_oleh"]) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        Belum ada data pengembalian.
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