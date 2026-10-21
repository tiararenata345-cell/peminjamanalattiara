
<?php
require_once "../koneksi.php";
wajibRole("peminjam");

$id = (int)$_SESSION["id"];

$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM peminjaman WHERE user_id=?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status='menunggu'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $menunggu);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) FROM peminjaman WHERE user_id=? AND status='disetujui'");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $disetujui);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($koneksi, "
    SELECT p.id, a.nama_alat, p.jumlah, p.tanggal_pinjam,
           p.tanggal_kembali, p.status
    FROM peminjaman p JOIN alat a ON a.id=p.alat_id
    WHERE p.user_id=? ORDER BY p.id DESC LIMIT 10
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$riwayat = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Peminjam</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar p-3">
<div class="container-fluid">
<a class="navbar-brand fw-bold" href="index.php">PEMINJAMAN ALAT</a>
<div class="text-white">Halo, <b><?= e($_SESSION["nama"]) ?></b>
<a href="../logout.php" class="btn btn-light btn-sm ms-3">Logout</a></div>
</div>
</nav>
<div class="container-fluid">
<div class="row">
<aside class="col-md-2 sidebar">
<a class="active" href="index.php">Dashboard</a>
<a href="alat.php">Lihat Daftar Alat</a>
<a href="pinjam.php">Ajukan Peminjaman</a>
<a href="riwayat.php">Riwayat Peminjaman</a>
</aside>
<main class="col-md-10 p-4">
<h3 class="page-title">Dashboard Peminjam</h3>
<p>Selamat datang, <?= e($_SESSION["nama"]) ?>.</p>
<div class="row g-3 mb-4">
<?php foreach ([
["Total Pengajuan", $total],
["Menunggu Persetujuan", $menunggu],
["Sedang Disetujui", $disetujui]
] as $c): ?>
<div class="col-md-4">
<div class="card stat-card">
<div class="text-muted"><?= e($c[0]) ?></div>
<div class="stat-number"><?= $c[1] ?></div>
</div>
</div>
<?php endforeach; ?>
</div>
<a href="pinjam.php" class="btn btn-primary mb-4">+ Ajukan Peminjaman</a>
<div class="card p-3">
<h5>Riwayat Terbaru</h5>
<div class="table-responsive">
<table class="table table-bordered">
<thead><tr><th>ID</th><th>Nama Alat</th><th>Jumlah</th><th>Tanggal Pinjam</th><th>Tanggal Kembali</th><th>Status</th></tr></thead>
<tbody>
<?php while ($r = mysqli_fetch_assoc($riwayat)): ?>
<tr>
<td><?= $r["id"] ?></td><td><?= e($r["nama_alat"]) ?></td>
<td><?= $r["jumlah"] ?></td><td><?= e($r["tanggal_pinjam"]) ?></td>
<td><?= e($r["tanggal_kembali"]) ?></td><td><?= e($r["status"]) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>
</main>
</div>
</div>
</body>
</html>
