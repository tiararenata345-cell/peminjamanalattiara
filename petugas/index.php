
<?php
require_once "../koneksi.php";
wajibRole("petugas");

function hitung($koneksi, $sql) {
    $q = mysqli_query($koneksi, $sql);
    return $q ? (int)mysqli_fetch_row($q)[0] : 0;
}

$menunggu = hitung($koneksi, "SELECT COUNT(*) FROM peminjaman WHERE status='menunggu'");
$disetujui = hitung($koneksi, "SELECT COUNT(*) FROM peminjaman WHERE status='disetujui'");
$ditolak = hitung($koneksi, "SELECT COUNT(*) FROM peminjaman WHERE status='ditolak'");
$kembali = hitung($koneksi, "SELECT COUNT(*) FROM pengembalian");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Petugas</title>
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
<a href="peminjaman.php">Kelola Peminjaman</a>
<a href="pengembalian.php">Kelola Pengembalian</a>
</aside>
<main class="col-md-10 p-4">
<h3 class="page-title">Dashboard Petugas</h3>
<div class="row g-3">
<?php foreach ([
    ["Pengajuan Menunggu", $menunggu],
    ["Peminjaman Disetujui", $disetujui],
    ["Peminjaman Ditolak", $ditolak],
    ["Pengembalian Tercatat", $kembali]
] as $c): ?>
<div class="col-md-3">
<div class="card stat-card">
<div class="text-muted"><?= e($c[0]) ?></div>
<div class="stat-number"><?= $c[1] ?></div>
</div>
</div>
<?php endforeach; ?>
</div>
<div class="card p-4 mt-4">
<h5>Menu Petugas</h5>
<p>Periksa pengajuan peminjaman, setujui atau tolak permintaan, dan proses pengembalian alat.</p>
<a href="peminjaman.php" class="btn btn-primary me-2">Periksa Peminjaman</a>
<a href="pengembalian.php" class="btn btn-outline-primary mt-2">Proses Pengembalian</a>
</div>
</main>
</div>
</div>
</body>
</html>
