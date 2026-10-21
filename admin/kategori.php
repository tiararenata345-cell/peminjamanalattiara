<?php
require_once "../koneksi.php";
wajibRole("admin");

$pesan = "";
$jenis_pesan = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";

    if ($aksi === "tambah") {
        $nama = trim($_POST["nama_kategori"] ?? "");

        if ($nama === "") {
            $pesan = "Nama kategori tidak boleh kosong.";
            $jenis_pesan = "danger";
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO kategori (nama_kategori) VALUES (?)"
            );

            mysqli_stmt_bind_param($stmt, "s", $nama);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                catatAktivitas($koneksi, "Menambahkan kategori " . $nama);

                header("Location: kategori.php?success=1");
                exit;
            } else {
                $pesan = "Kategori gagal ditambahkan.";
                $jenis_pesan = "danger";
                mysqli_stmt_close($stmt);
            }
        }
    }

    if ($aksi === "hapus") {
        $id_kategori = (int)($_POST["id"] ?? 0);

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT COUNT(*) FROM alat WHERE kategori_id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_kategori);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $jumlah_alat);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($id_kategori < 1) {
            header("Location: kategori.php?error=id");
            exit;
        }

        if ($jumlah_alat > 0) {
            header("Location: kategori.php?error=digunakan");
            exit;
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "DELETE FROM kategori WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_kategori);
        mysqli_stmt_execute($stmt);

        $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($berhasil) {
            catatAktivitas($koneksi, "Menghapus kategori ID " . $id_kategori);
            header("Location: kategori.php?deleted=1");
        } else {
            header("Location: kategori.php?error=hapus");
        }

        exit;
    }
}

$data = mysqli_query(
    $koneksi,
    "SELECT k.id, k.nama_kategori, COUNT(a.id) AS jumlah_alat
     FROM kategori k
     LEFT JOIN alat a ON a.kategori_id = k.id
     GROUP BY k.id, k.nama_kategori
     ORDER BY k.id DESC"
);

if (!$data) {
    die("Gagal mengambil data kategori.");
}

$total_kategori = mysqli_num_rows($data);
$total_alat_terkategori = 0;

while ($baris = mysqli_fetch_assoc($data)) {
    $total_alat_terkategori += (int)$baris["jumlah_alat"];
}

mysqli_data_seek($data, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Kategori - Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold" href="index.php">
            PEMINJAMAN ALAT
        </a>

        <div class="text-white">
            Halo, <b><?= e($_SESSION["nama"]) ?></b>
            <a
                href="../logout.php"
                class="btn btn-light btn-sm ms-3"
                onclick="return confirm('Yakin ingin logout?')"
            >Logout</a>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">

        <div class="col-md-2 sidebar">
            <a href="index.php">Dashboard</a>
            <a href="users.php">Data Pengguna</a>
            <a class="active" href="kategori.php">Data Kategori</a>
            <a href="alat.php">Data Alat</a>
            <a href="peminjaman.php">Data Peminjaman</a>
            <a href="pengembalian.php">Data Pengembalian</a>
            <a href="laporan.php">Laporan</a>
        </div>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Data Kategori Alat</h3>
            <p class="text-muted">
                Kelola kategori alat yang tersedia dalam sistem peminjaman.
            </p>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    Kategori berhasil ditambahkan.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["deleted"])): ?>
                <div class="alert alert-success">
                    Kategori berhasil dihapus.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "digunakan"): ?>
                <div class="alert alert-danger">
                    Kategori tidak dapat dihapus karena masih digunakan oleh alat.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "hapus"): ?>
                <div class="alert alert-danger">
                    Kategori gagal dihapus atau tidak ditemukan.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "id"): ?>
                <div class="alert alert-danger">
                    ID kategori tidak valid.
                </div>
            <?php endif; ?>

            <?php if ($pesan !== ""): ?>
                <div class="alert alert-<?= e($jenis_pesan) ?>">
                    <?= e($pesan) ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="card stat-card h-100">
                        <div class="text-muted">Total Kategori</div>
                        <div class="stat-number">
                            <?= $total_kategori ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card stat-card h-100">
                        <div class="text-muted">Total Alat dalam Kategori</div>
                        <div class="stat-number">
                            <?= $total_alat_terkategori ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="card p-4 mb-4">
                <h5 class="mb-3">Tambah Kategori</h5>

                <form method="POST" class="row g-2">
                    <input type="hidden" name="aksi" value="tambah">

                    <div class="col-md-9">
                        <input
                            type="text"
                            name="nama_kategori"
                            class="form-control"
                            placeholder="Masukkan nama kategori baru"
                            required
                        >
                    </div>

                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            + Tambah Kategori
                        </button>
                    </div>
                </form>
            </div>

            <div class="card p-3">
                <h5 class="mb-3">Daftar Kategori</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Kategori</th>
                                <th>Jumlah Alat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (mysqli_num_rows($data) > 0): ?>

                                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td><?= (int)$r["id"] ?></td>

                                        <td>
                                            <?= e($r["nama_kategori"]) ?>
                                        </td>

                                        <td>
                                            <?= (int)$r["jumlah_alat"] ?>
                                        </td>

                                        <td>
                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Yakin ingin menghapus kategori ini?')"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="aksi"
                                                    value="hapus"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="id"
                                                    value="<?= (int)$r["id"] ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-danger btn-sm"
                                                    <?= (int)$r["jumlah_alat"] > 0 ? "disabled" : "" ?>
                                                >
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4" class="text-center">
                                        Belum ada kategori alat.
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