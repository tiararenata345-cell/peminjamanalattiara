
<?php
require_once "../koneksi.php";
wajibRole("peminjam");

$q = mysqli_query($koneksi, "
    SELECT a.id, a.nama_alat, a.stok, a.kondisi, a.lokasi,
           k.nama_kategori
    FROM alat a
    LEFT JOIN kategori k ON k.id = a.kategori_id
    ORDER BY a.nama_alat
");

if (!$q) {
    die("Gagal mengambil data alat: " . mysqli_error($koneksi));
}

$total_alat = mysqli_num_rows($q);
$tersedia = 0;
$tidak_tersedia = 0;

while ($item = mysqli_fetch_assoc($q)) {
    if ((int)$item["stok"] > 0 && $item["kondisi"] === "baik") {
        $tersedia++;
    } else {
        $tidak_tersedia++;
    }
}

mysqli_data_seek($q, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar Alat - Peminjam</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar p-3">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">PEMINJAMAN ALAT</a>
        <div class="text-white">
            Halo, <b><?= e($_SESSION["nama"]) ?></b>
            <a href="../logout.php" class="btn btn-light btn-sm ms-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <aside class="col-md-2 sidebar">
            <a href="index.php">Dashboard</a>
            <a href="alat.php" class="active">Lihat Daftar Alat</a>
            <a href="pinjam.php">Ajukan Peminjaman</a>
            <a href="riwayat.php">Riwayat Peminjaman</a>
        </aside>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Daftar Alat</h3>
            <p class="text-muted">
                Lihat alat yang tersedia dan ajukan peminjaman sesuai kebutuhan.
            </p>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="text-muted">Total Jenis Alat</div>
                        <div class="stat-number"><?= $total_alat ?></div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="text-muted">Alat Tersedia</div>
                        <div class="stat-number"><?= $tersedia ?></div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="text-muted">Tidak Tersedia</div>
                        <div class="stat-number"><?= $tidak_tersedia ?></div>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <a href="pinjam.php" class="btn btn-primary">
                    + Ajukan Peminjaman
                </a>
            </div>

            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="mb-0">Data Alat</h5>
                    <input type="text"
                           id="cariAlat"
                           class="form-control"
                           placeholder="Cari nama alat atau kategori..."
                           style="max-width: 300px;">
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tabelAlat">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Kondisi</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if (mysqli_num_rows($q) > 0): ?>
                            <?php $no = 1; ?>

                            <?php while ($r = mysqli_fetch_assoc($q)): ?>
                                <?php
                                $bisa_pinjam =
                                    (int)$r["stok"] > 0 &&
                                    $r["kondisi"] === "baik";
                                ?>

                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= e($r["nama_alat"]) ?></td>
                                    <td><?= e($r["nama_kategori"] ?? "-") ?></td>
                                    <td><?= (int)$r["stok"] ?></td>
                                    <td><?= e($r["kondisi"]) ?></td>
                                    <td><?= e($r["lokasi"] ?? "-") ?></td>
                                    <td>
                                        <?php if ($bisa_pinjam): ?>
                                            <span class="badge bg-success">Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Tidak Tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($bisa_pinjam): ?>
                                            <a href="pinjam.php?alat_id=<?= (int)$r["id"] ?>"
                                               class="btn btn-primary btn-sm">
                                                Pinjam
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                Tidak Tersedia
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">
                                    Belum ada data alat.
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

<script>
document.getElementById("cariAlat").addEventListener("input", function () {
    const kata = this.value.toLowerCase();

    document.querySelectorAll("#tabelAlat tbody tr").forEach(function (baris) {
        baris.style.display = baris.textContent.toLowerCase().includes(kata)
            ? ""
            : "none";
    });
});
</script>

</body>
</html>
