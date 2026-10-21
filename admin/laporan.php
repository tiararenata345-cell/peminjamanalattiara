<?php
require_once "../koneksi.php";
wajibRole("admin");

$data = mysqli_query($koneksi, "
    SELECT p.id, u.nama, a.nama_alat, p.jumlah,
           p.tanggal_pinjam, p.tanggal_kembali, p.status,
           g.tanggal_dikembalikan, g.denda, g.kondisi_alat
    FROM peminjaman p
    JOIN users u ON u.id = p.user_id
    JOIN alat a ON a.id = p.alat_id
    LEFT JOIN pengembalian g ON g.peminjaman_id = p.id
    ORDER BY p.id DESC
");

$total = 0;
$menunggu = 0;
$disetujui = 0;
$ditolak = 0;
$selesai = 0;
$totalDenda = 0;

if ($data) {
    while ($r = mysqli_fetch_assoc($data)) {
        $total++;
        $totalDenda += (float)($r["denda"] ?? 0);

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
    <title>Laporan Peminjaman Alat</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .card {
                box-shadow: none !important;
            }

            main {
                width: 100% !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            table {
                font-size: 10px;
            }

            th, td {
                padding: 4px !important;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg no-print">
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

        <div class="col-md-2 sidebar no-print">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Data Pengguna</a>
            <a href="kategori.php">Data Kategori</a>
            <a href="alat.php">Data Alat</a>
            <a href="peminjaman.php">Data Peminjaman</a>
            <a href="pengembalian.php">Data Pengembalian</a>
            <a class="active" href="laporan.php">Laporan</a>
        </div>

        <main class="col-md-10 p-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h3 class="page-title">Laporan Peminjaman dan Pengembalian</h3>
                    <p class="text-muted mb-0">Laporan seluruh transaksi peminjaman alat.</p>
                </div>

                <button onclick="window.print()" class="btn btn-primary btn-sm no-print">
                    Cetak Laporan
                </button>
            </div>

            <div class="row g-3 mb-4 no-print">
                <?php
                $cards = [
                    ["Total Peminjaman", $total],
                    ["Menunggu Persetujuan", $menunggu],
                    ["Disetujui", $disetujui],
                    ["Ditolak", $ditolak],
                    ["Selesai", $selesai],
                    ["Total Denda", "Rp " . number_format($totalDenda, 0, ",", ".")]
                ];

                foreach ($cards as $c):
                ?>
                    <div class="col-md-4 col-lg-2">
                        <div class="card stat-card h-100">
                            <div class="text-muted small"><?= e($c[0]) ?></div>
                            <div class="stat-number"><?= e((string)$c[1]) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="card p-3">
                <h5 class="mb-3">Data Laporan Transaksi</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Peminjam</th>
                                <th>Nama Alat</th>
                                <th>Jumlah</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Status</th>
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
                                        <td><?= (int)$r["jumlah"] ?></td>
                                        <td><?= e($r["tanggal_pinjam"]) ?></td>
                                        <td><?= e($r["tanggal_kembali"]) ?></td>
                                        <td><?= e($r["status"]) ?></td>
                                        <td><?= e($r["tanggal_dikembalikan"] ?? "-") ?></td>
                                        <td>
                                            Rp <?= number_format((float)($r["denda"] ?? 0), 0, ",", ".") ?>
                                        </td>
                                        <td><?= e($r["kondisi_alat"] ?? "-") ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="10" class="text-center">
                                        Belum ada data laporan.
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