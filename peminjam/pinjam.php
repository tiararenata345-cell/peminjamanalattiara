<?php
require_once "../koneksi.php";
wajibRole("peminjam");

$alat_id = (int)($_GET["alat_id"] ?? 0);
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $alat_id = (int)($_POST["alat_id"] ?? 0);
    $jumlah = (int)($_POST["jumlah"] ?? 0);
    $pinjam = $_POST["tanggal_pinjam"] ?? "";
    $kembali = $_POST["tanggal_kembali"] ?? "";
    $user_id = (int)$_SESSION["id"];

    if (
        $alat_id < 1 ||
        !$pinjam ||
        !$kembali ||
        $pinjam < date("Y-m-d") ||
        $kembali < $pinjam ||
        $jumlah < 1
    ) {
        $error = "Data peminjaman tidak valid. Periksa kembali formulir.";
    } else {
        mysqli_begin_transaction($koneksi);

        try {
            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT stok, kondisi FROM alat WHERE id = ? FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $alat_id);
            mysqli_stmt_execute($stmt);

            $hasil = mysqli_stmt_get_result($stmt);
            $data_alat = mysqli_fetch_assoc($hasil);

            mysqli_stmt_close($stmt);

            if (
                !$data_alat ||
                $data_alat["kondisi"] !== "baik" ||
                $jumlah > (int)$data_alat["stok"]
            ) {
                throw new Exception("Stok alat tidak mencukupi.");
            }

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO peminjaman
                (user_id, alat_id, jumlah, tanggal_pinjam, tanggal_kembali, status)
                VALUES (?, ?, ?, ?, ?, 'menunggu')"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iiiss",
                $user_id,
                $alat_id,
                $jumlah,
                $pinjam,
                $kembali
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            catatAktivitas(
                $koneksi,
                "Mengajukan peminjaman alat ID " . $alat_id
            );

            mysqli_commit($koneksi);

            header("Location: riwayat.php?success=1");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            $error = "Pengajuan gagal. Pastikan alat tersedia dan stok mencukupi.";
        }
    }
}

$alat = mysqli_query(
    $koneksi,
    "SELECT id, nama_alat, stok, kondisi
     FROM alat
     WHERE stok > 0 AND kondisi = 'baik'
     ORDER BY nama_alat ASC"
);

if (!$alat) {
    die("Gagal mengambil data alat: " . e(mysqli_error($koneksi)));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ajukan Peminjaman</title>

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
            <a class="active" href="pinjam.php">Ajukan Peminjaman</a>
            <a href="riwayat.php">Riwayat Peminjaman</a>
        </aside>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Ajukan Peminjaman</h3>
            <p>Silakan isi formulir untuk mengajukan peminjaman alat.</p>

            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="card stat-card">
                        <div class="text-muted">Jenis Alat Tersedia</div>
                        <div class="stat-number">
                            <?= mysqli_num_rows($alat) ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card stat-card">
                        <div class="text-muted">Informasi</div>
                        <p class="mb-0 mt-2">
                            Pilih alat dengan kondisi baik, tentukan jumlah,
                            lalu isi tanggal peminjaman dan pengembalian.
                        </p>
                    </div>
                </div>

            </div>

            <div class="card p-4">
                <h5 class="mb-3">Form Peminjaman Alat</h5>

                <?php if ($error !== ""): ?>
                    <div class="alert alert-danger">
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET["success"])): ?>
                    <div class="alert alert-success">
                        Pengajuan peminjaman berhasil dikirim.
                    </div>
                <?php endif; ?>

                <?php if (mysqli_num_rows($alat) > 0): ?>

                    <form method="POST" action="pinjam.php">

                        <div class="mb-3">
                            <label class="form-label">Pilih Alat</label>

                            <select name="alat_id" class="form-select" required>
                                <option value="">-- Pilih Alat --</option>

                                <?php
                                mysqli_data_seek($alat, 0);

                                while ($a = mysqli_fetch_assoc($alat)):
                                ?>
                                    <option
                                        value="<?= (int)$a["id"] ?>"
                                        <?= $alat_id === (int)$a["id"] ? "selected" : "" ?>
                                    >
                                        <?= e($a["nama_alat"]) ?>
                                        — Stok <?= (int)$a["stok"] ?>
                                    </option>
                                <?php endwhile; ?>

                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jumlah</label>
                            <input
                                type="number"
                                name="jumlah"
                                class="form-control"
                                min="1"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Pinjam</label>
                            <input
                                type="date"
                                name="tanggal_pinjam"
                                class="form-control"
                                min="<?= date("Y-m-d") ?>"
                                value="<?= date("Y-m-d") ?>"
                                required
                            >
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                Rencana Tanggal Kembali
                            </label>

                            <input
                                type="date"
                                name="tanggal_kembali"
                                class="form-control"
                                min="<?= date("Y-m-d") ?>"
                                required
                            >
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Kirim Pengajuan
                        </button>

                        <a href="alat.php" class="btn btn-secondary">
                            Kembali
                        </a>

                    </form>

                <?php else: ?>

                    <div class="alert alert-warning mb-0">
                        Belum ada alat yang tersedia untuk dipinjam.
                        Silakan periksa kembali nanti.
                    </div>

                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script>
const tanggalPinjam = document.querySelector('[name="tanggal_pinjam"]');
const tanggalKembali = document.querySelector('[name="tanggal_kembali"]');

if (tanggalPinjam && tanggalKembali) {
    tanggalPinjam.addEventListener("change", function () {
        tanggalKembali.min = this.value;

        if (
            tanggalKembali.value &&
            tanggalKembali.value < this.value
        ) {
            tanggalKembali.value = this.value;
        }
    });
}
</script>

</body>
</html>
```