<?php
require_once "../koneksi.php";
wajibRole("peminjam");

$id = (int)$_SESSION["id"];

$stmt = mysqli_prepare($koneksi, "
    SELECT p.id, a.nama_alat, p.jumlah, p.tanggal_pinjam,
           p.tanggal_kembali, p.status, g.tanggal_dikembalikan,
           g.denda, g.kondisi_alat
    FROM peminjaman p
    JOIN alat a ON a.id = p.alat_id
    LEFT JOIN pengembalian g ON g.peminjaman_id = p.id
    WHERE p.user_id = ?
    ORDER BY p.id DESC
");

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Riwayat Peminjaman</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar p-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">
            PEMINJAMAN ALAT
        </a>

        <div class="text-white">
            Halo, <b><?= e($_SESSION["nama"]) ?></b>
            <a href="../logout.php" class="btn btn-light btn-sm ms-3">
                Logout
            </a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <aside class="col-md-2 sidebar">
            <a href="index.php">Dashboard</a>
            <a href="alat.php">Lihat Daftar Alat</a>
            <a href="pinjam.php">Ajukan Peminjaman</a>
            <a class="active" href="riwayat.php">Riwayat Peminjaman</a>
        </aside>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Riwayat Peminjaman</h3>
            <p>Berikut adalah daftar pengajuan dan pengembalian alat milikmu.</p>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    Pengajuan peminjaman berhasil dikirim.
                </div>
            <?php endif; ?>

            <div class="card p-3">
                <h5 class="mb-3">Daftar Riwayat Peminjaman</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Alat</th>
                                <th>Jumlah</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Status</th>
                                <th>Tanggal Dikembalikan</th>
                                <th>Denda</th>
                                <th>Kondisi Kembali</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (mysqli_num_rows($data) > 0): ?>

                                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td><?= (int)$r["id"] ?></td>

                                        <td>
                                            <?= e($r["nama_alat"]) ?>
                                        </td>

                                        <td>
                                            <?= (int)$r["jumlah"] ?>
                                        </td>

                                        <td>
                                            <?= e($r["tanggal_pinjam"]) ?>
                                        </td>

                                        <td>
                                            <?= e($r["tanggal_kembali"]) ?>
                                        </td>

                                        <td>
                                            <?php
                                            $status = $r["status"];
                                            $badge = "bg-secondary";

                                            if ($status === "menunggu") {
                                                $badge = "bg-warning text-dark";
                                            } elseif ($status === "disetujui") {
                                                $badge = "bg-success";
                                            } elseif ($status === "ditolak") {
                                                $badge = "bg-danger";
                                            } elseif ($status === "dikembalikan") {
                                                $badge = "bg-info text-dark";
                                            }
                                            ?>

                                            <span class="badge <?= $badge ?>">
                                                <?= e(ucfirst($status)) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= e($r["tanggal_dikembalikan"] ?? "-") ?>
                                        </td>

                                        <td>
                                            Rp <?= number_format(
                                                (float)($r["denda"] ?? 0),
                                                0,
                                                ",",
                                                "."
                                            ) ?>
                                        </td>

                                        <td>
                                            <?= e($r["kondisi_alat"] ?? "-") ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="9" class="text-center">
                                        Belum ada riwayat peminjaman.
                                        <br>
                                        <a href="pinjam.php" class="btn btn-primary btn-sm mt-2">
                                            Ajukan Peminjaman
                                        </a>
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
```