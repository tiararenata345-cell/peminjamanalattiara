
<?php
require_once "../koneksi.php";
wajibRole("admin");

function jumlahData($koneksi, $sql) {
    $hasil = mysqli_query($koneksi, $sql);
    if (!$hasil) return 0;
    return (int)mysqli_fetch_row($hasil)[0];
}

$users = jumlahData($koneksi, "SELECT COUNT(*) FROM users");
$alat = jumlahData($koneksi, "SELECT COUNT(*) FROM alat");
$kategori = jumlahData($koneksi, "SELECT COUNT(*) FROM kategori");
$peminjaman = jumlahData($koneksi, "SELECT COUNT(*) FROM peminjaman");
$menunggu = jumlahData($koneksi, "SELECT COUNT(*) FROM peminjaman WHERE status='menunggu'");
$pengembalian = jumlahData($koneksi, "SELECT COUNT(*) FROM pengembalian");

$data = mysqli_query($koneksi, "
    SELECT p.id, u.nama, a.nama_alat, p.jumlah,
           p.tanggal_pinjam, p.tanggal_kembali, p.status
    FROM peminjaman p
    JOIN users u ON u.id=p.user_id
    JOIN alat a ON a.id=p.alat_id
    ORDER BY p.id DESC LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Admin</title>
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
<a class="active" href="index.php">Dashboard</a>
<a href="users.php">Data Pengguna</a>
<a href="kategori.php">Data Kategori</a>
<a href="alat.php">Data Alat</a>
<a href="peminjaman.php">Data Peminjaman</a>
<a href="pengembalian.php">Data Pengembalian</a>
<a href="laporan.php">Laporan</a>
</div>

<main class="col-md-10 p-4">
<h3 class="page-title">Dashboard Admin</h3>
<p class="text-muted">Ringkasan pengelolaan peminjaman alat.</p>

<div class="row g-3 mb-4">
<?php
$cards = [
    ["Total Pengguna", $users],
    ["Total Alat", $alat],
    ["Kategori", $kategori],
    ["Total Peminjaman", $peminjaman],
    ["Menunggu Persetujuan", $menunggu],
    ["Total Pengembalian", $pengembalian]
];
foreach ($cards as $c):
?>
<div class="col-md-4 col-lg-2">
<div class="card stat-card h-100">
<div class="text-muted small"><?= e($c[0]) ?></div>
<div class="stat-number"><?= $c[1] ?></div>
</div>
</div>
<?php endforeach; ?>
</div>

<div class="card p-3">
<h5 class="mb-3">Peminjaman Terbaru</h5>
<div class="table-responsive">
<table class="table table-bordered table-hover">
<thead>
<tr>
<th>ID</th><th>Peminjam</th><th>Alat</th><th>Jumlah</th>
<th>Tanggal Pinjam</th><th>Tanggal Kembali</th><th>Status</th>
</tr>
</thead>
<tbody>
<?php if ($data && mysqli_num_rows($data)): ?>
<?php while ($r = mysqli_fetch_assoc($data)): ?>
<tr>
<td><?= $r["id"] ?></td>
<td><?= e($r["nama"]) ?></td>
<td><?= e($r["nama_alat"]) ?></td>
<td><?= $r["jumlah"] ?></td>
<td><?= e($r["tanggal_pinjam"]) ?></td>
<td><?= e($r["tanggal_kembali"]) ?></td>
<td><span class="badge text-bg-secondary"><?= e($r["status"]) ?></span></td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="7" class="text-center">Belum ada peminjaman.</td></tr>
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
