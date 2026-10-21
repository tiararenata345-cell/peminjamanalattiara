<?php
require_once "../koneksi.php";
wajibRole("admin");

$pesan = "";
$jenis_pesan = "danger";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";
    $id = (int)($_POST["id"] ?? 0);

    if ($aksi === "simpan") {
        $nama = trim($_POST["nama_alat"] ?? "");
        $kategori_id = (int)($_POST["kategori_id"] ?? 0);
        $stok = filter_var($_POST["stok"] ?? null, FILTER_VALIDATE_INT);
        $kondisi = $_POST["kondisi"] ?? "baik";
        $lokasi = trim($_POST["lokasi"] ?? "");

        if (
            $nama === "" ||
            $kategori_id < 1 ||
            $stok === false ||
            $stok === null ||
            $stok < 0 ||
            !in_array($kondisi, ["baik", "rusak ringan", "rusak berat"], true)
        ) {
            $pesan = "Periksa kembali data alat yang kamu masukkan.";
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT id FROM kategori WHERE id = ?"
            );
            mysqli_stmt_bind_param($stmt, "i", $kategori_id);
            mysqli_stmt_execute($stmt);
            $kategori_ada = mysqli_fetch_assoc(
                mysqli_stmt_get_result($stmt)
            );
            mysqli_stmt_close($stmt);

            if (!$kategori_ada) {
                $pesan = "Kategori yang dipilih tidak ditemukan.";
            } else {
                if ($id > 0) {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE alat
                         SET nama_alat = ?, kategori_id = ?, stok = ?,
                             kondisi = ?, lokasi = ?
                         WHERE id = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "siissi",
                        $nama,
                        $kategori_id,
                        $stok,
                        $kondisi,
                        $lokasi,
                        $id
                    );
                } else {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "INSERT INTO alat
                         (nama_alat, kategori_id, stok, kondisi, lokasi)
                         VALUES (?, ?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "siiss",
                        $nama,
                        $kategori_id,
                        $stok,
                        $kondisi,
                        $lokasi
                    );
                }

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);

                    catatAktivitas(
                        $koneksi,
                        "Menyimpan data alat " . $nama
                    );

                    header("Location: alat.php?success=1");
                    exit;
                } else {
                    $pesan = "Data alat gagal disimpan.";
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }

    if ($aksi === "hapus" && $id > 0) {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT COUNT(*) FROM peminjaman WHERE alat_id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $jumlah_peminjaman);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($jumlah_peminjaman > 0) {
            header("Location: alat.php?error=digunakan");
            exit;
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "DELETE FROM alat WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($berhasil) {
            catatAktivitas(
                $koneksi,
                "Menghapus alat ID " . $id
            );

            header("Location: alat.php?deleted=1");
        } else {
            header("Location: alat.php?error=hapus");
        }

        exit;
    }
}

$edit = null;

if (isset($_GET["edit"])) {
    $id_edit = (int)$_GET["edit"];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM alat WHERE id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id_edit);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
}

$kategori = mysqli_query(
    $koneksi,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);

if (!$kategori) {
    die("Gagal mengambil data kategori.");
}

$data = mysqli_query(
    $koneksi,
    "SELECT a.*, k.nama_kategori
     FROM alat a
     LEFT JOIN kategori k ON k.id = a.kategori_id
     ORDER BY a.id DESC"
);

if (!$data) {
    die("Gagal mengambil data alat.");
}

$total_alat = mysqli_num_rows($data);
$total_stok = 0;
$total_baik = 0;
$total_rusak = 0;

while ($baris = mysqli_fetch_assoc($data)) {
    $total_stok += (int)$baris["stok"];

    if ($baris["kondisi"] === "baik") {
        $total_baik++;
    } else {
        $total_rusak++;
    }
}

mysqli_data_seek($data, 0);
mysqli_data_seek($kategori, 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Alat - Admin</title>

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
            <a href="kategori.php">Data Kategori</a>
            <a class="active" href="alat.php">Data Alat</a>
            <a href="peminjaman.php">Data Peminjaman</a>
            <a href="pengembalian.php">Data Pengembalian</a>
            <a href="laporan.php">Laporan</a>
        </div>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Data Alat</h3>
            <p class="text-muted">
                Kelola informasi alat, kategori, stok, kondisi, dan lokasi alat.
            </p>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    Data alat berhasil disimpan.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["deleted"])): ?>
                <div class="alert alert-success">
                    Data alat berhasil dihapus.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "digunakan"): ?>
                <div class="alert alert-danger">
                    Alat tidak dapat dihapus karena sudah tercatat dalam riwayat peminjaman.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "hapus"): ?>
                <div class="alert alert-danger">
                    Data alat gagal dihapus atau tidak ditemukan.
                </div>
            <?php endif; ?>

            <?php if ($pesan !== ""): ?>
                <div class="alert alert-<?= e($jenis_pesan) ?>">
                    <?= e($pesan) ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Jenis Alat</div>
                        <div class="stat-number"><?= $total_alat ?></div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Total Stok</div>
                        <div class="stat-number"><?= $total_stok ?></div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Kondisi Baik</div>
                        <div class="stat-number"><?= $total_baik ?></div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Kondisi Rusak</div>
                        <div class="stat-number"><?= $total_rusak ?></div>
                    </div>
                </div>

            </div>

            <div class="card p-4 mb-4">
                <h5 class="mb-3">
                    <?= $edit ? "Edit Data Alat" : "Tambah Alat" ?>
                </h5>

                <?php if (mysqli_num_rows($kategori) > 0): ?>

                    <form method="POST" class="row g-3">
                        <input type="hidden" name="aksi" value="simpan">

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)($edit["id"] ?? 0) ?>"
                        >

                        <div class="col-md-6">
                            <label class="form-label">Nama Alat</label>
                            <input
                                type="text"
                                name="nama_alat"
                                class="form-control"
                                value="<?= e($edit["nama_alat"] ?? "") ?>"
                                placeholder="Masukkan nama alat"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>

                            <select name="kategori_id" class="form-select" required>
                                <option value="">-- Pilih Kategori --</option>

                                <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                                    <option
                                        value="<?= (int)$k["id"] ?>"
                                        <?= (int)($edit["kategori_id"] ?? 0) === (int)$k["id"] ? "selected" : "" ?>
                                    >
                                        <?= e($k["nama_kategori"]) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Stok</label>
                            <input
                                type="number"
                                name="stok"
                                min="0"
                                class="form-control"
                                value="<?= (int)($edit["stok"] ?? 0) ?>"
                                required
                            >
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Kondisi</label>

                            <select name="kondisi" class="form-select" required>
                                <?php foreach (["baik", "rusak ringan", "rusak berat"] as $k): ?>
                                    <option
                                        value="<?= e($k) ?>"
                                        <?= ($edit["kondisi"] ?? "baik") === $k ? "selected" : "" ?>
                                    >
                                        <?= e(ucfirst($k)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Lokasi</label>
                            <input
                                type="text"
                                name="lokasi"
                                class="form-control"
                                value="<?= e($edit["lokasi"] ?? "") ?>"
                                placeholder="Masukkan lokasi alat"
                            >
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <?= $edit ? "Simpan Perubahan" : "+ Tambah Alat" ?>
                            </button>

                            <?php if ($edit): ?>
                                <a href="alat.php" class="btn btn-secondary">
                                    Batal
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>

                <?php else: ?>

                    <div class="alert alert-warning mb-0">
                        Belum ada kategori. Tambahkan kategori terlebih dahulu
                        melalui menu Data Kategori sebelum menambahkan alat.
                        <a href="kategori.php" class="alert-link">
                            Buka Data Kategori
                        </a>
                    </div>

                <?php endif; ?>
            </div>

            <div class="card p-3">
                <h5 class="mb-3">Daftar Alat</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Kondisi</th>
                                <th>Lokasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (mysqli_num_rows($data) > 0): ?>

                                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td><?= (int)$r["id"] ?></td>

                                        <td><?= e($r["nama_alat"]) ?></td>

                                        <td>
                                            <?= e($r["nama_kategori"] ?? "-") ?>
                                        </td>

                                        <td><?= (int)$r["stok"] ?></td>

                                        <td>
                                            <?php if ($r["kondisi"] === "baik"): ?>
                                                <span class="badge text-bg-success">
                                                    Baik
                                                </span>
                                            <?php elseif ($r["kondisi"] === "rusak ringan"): ?>
                                                <span class="badge text-bg-warning">
                                                    Rusak Ringan
                                                </span>
                                            <?php else: ?>
                                                <span class="badge text-bg-danger">
                                                    Rusak Berat
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= e($r["lokasi"] ?? "-") ?></td>

                                        <td class="text-nowrap">
                                            <a
                                                href="alat.php?edit=<?= (int)$r["id"] ?>"
                                                class="btn btn-warning btn-sm"
                                            >Edit</a>

                                            <form
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Yakin ingin menghapus alat ini?')"
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
                                                >Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="text-center">
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

</body>
</html>
