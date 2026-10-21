<?php
require_once "../koneksi.php";
wajibRole("admin");

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "";
    $id = (int)($_POST["id"] ?? 0);

    if ($aksi === "tambah") {
        $nama = trim($_POST["nama"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "peminjam";

        if ($nama === "" || $username === "" || $password === "") {
            $error = "Semua data pengguna wajib diisi.";
        } elseif (strlen($password) < 8) {
            $error = "Password minimal harus 8 karakter.";
        } elseif (!in_array($role, ["admin", "petugas", "peminjam"], true)) {
            $error = "Role yang dipilih tidak valid.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO users (nama, username, password, role, status)
                 VALUES (?, ?, ?, ?, 'aktif')"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $nama,
                $username,
                $hash,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                catatAktivitas(
                    $koneksi,
                    "Menambahkan pengguna " . $username
                );

                header("Location: users.php?success=1");
                exit;
            } else {
                $error = "Gagal menambahkan pengguna. Username mungkin sudah digunakan.";
                mysqli_stmt_close($stmt);
            }
        }
    }

    if ($aksi === "status") {
        if ($id < 1 || $id === (int)$_SESSION["id"]) {
            header("Location: users.php?error=status");
            exit;
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE users
             SET status = IF(status = 'aktif', 'nonaktif', 'aktif')
             WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($berhasil) {
            catatAktivitas(
                $koneksi,
                "Mengubah status pengguna ID " . $id
            );

            header("Location: users.php?status=1");
        } else {
            header("Location: users.php?error=status");
        }

        exit;
    }

    if ($aksi === "hapus") {
        if ($id < 1 || $id === (int)$_SESSION["id"]) {
            header("Location: users.php?error=hapus");
            exit;
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT
                (SELECT COUNT(*) FROM peminjaman WHERE user_id = ?) +
                (SELECT COUNT(*) FROM log_aktivitas WHERE user_id = ?)"
        );

        mysqli_stmt_bind_param($stmt, "ii", $id, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $jumlah);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($jumlah > 0) {
            header("Location: users.php?error=digunakan");
            exit;
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "DELETE FROM users WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);

        $berhasil = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        if ($berhasil) {
            catatAktivitas(
                $koneksi,
                "Menghapus pengguna ID " . $id
            );

            header("Location: users.php?deleted=1");
        } else {
            header("Location: users.php?error=hapus");
        }

        exit;
    }
}

function jumlahDataUsers($koneksi, $sql) {
    $hasil = mysqli_query($koneksi, $sql);

    if (!$hasil) {
        return 0;
    }

    return (int)mysqli_fetch_row($hasil)[0];
}

$total_users = jumlahDataUsers(
    $koneksi,
    "SELECT COUNT(*) FROM users"
);

$total_admin = jumlahDataUsers(
    $koneksi,
    "SELECT COUNT(*) FROM users WHERE role = 'admin'"
);

$total_petugas = jumlahDataUsers(
    $koneksi,
    "SELECT COUNT(*) FROM users WHERE role = 'petugas'"
);

$total_peminjam = jumlahDataUsers(
    $koneksi,
    "SELECT COUNT(*) FROM users WHERE role = 'peminjam'"
);

$data = mysqli_query(
    $koneksi,
    "SELECT id, nama, username, role, status, created_at
     FROM users
     ORDER BY id DESC"
);

if (!$data) {
    die("Gagal mengambil data pengguna.");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Pengguna - Admin</title>

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
            <a class="active" href="users.php">Data Pengguna</a>
            <a href="kategori.php">Data Kategori</a>
            <a href="alat.php">Data Alat</a>
            <a href="peminjaman.php">Data Peminjaman</a>
            <a href="pengembalian.php">Data Pengembalian</a>
            <a href="laporan.php">Laporan</a>
        </div>

        <main class="col-md-10 p-4">

            <h3 class="page-title">Data Pengguna</h3>
            <p class="text-muted">
                Kelola akun admin, petugas, dan peminjam dalam sistem.
            </p>

            <?php if (isset($_GET["success"])): ?>
                <div class="alert alert-success">
                    Pengguna berhasil ditambahkan.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["status"])): ?>
                <div class="alert alert-success">
                    Status pengguna berhasil diperbarui.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["deleted"])): ?>
                <div class="alert alert-success">
                    Pengguna berhasil dihapus.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "digunakan"): ?>
                <div class="alert alert-danger">
                    Pengguna tidak dapat dihapus karena masih memiliki riwayat
                    peminjaman atau aktivitas.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "status"): ?>
                <div class="alert alert-danger">
                    Status tidak dapat diubah. Periksa kembali akun yang dipilih.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["error"]) && $_GET["error"] === "hapus"): ?>
                <div class="alert alert-danger">
                    Pengguna gagal dihapus atau tidak ditemukan.
                </div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="alert alert-danger">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Total Pengguna</div>
                        <div class="stat-number"><?= $total_users ?></div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Admin</div>
                        <div class="stat-number"><?= $total_admin ?></div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Petugas</div>
                        <div class="stat-number"><?= $total_petugas ?></div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card stat-card h-100">
                        <div class="text-muted small">Peminjam</div>
                        <div class="stat-number"><?= $total_peminjam ?></div>
                    </div>
                </div>

            </div>

            <div class="card p-4 mb-4">
                <h5 class="mb-3">Tambah Pengguna</h5>

                <form method="POST" class="row g-3">
                    <input type="hidden" name="aksi" value="tambah">

                    <div class="col-md-6">
                        <label class="form-label">Nama</label>
                        <input
                            type="text"
                            name="nama"
                            class="form-control"
                            placeholder="Masukkan nama pengguna"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input
                            type="text"
                            name="username"
                            class="form-control"
                            placeholder="Masukkan username"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            minlength="8"
                            placeholder="Minimal 8 karakter"
                            required
                        >
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin">Admin</option>
                            <option value="petugas">Petugas</option>
                            <option value="peminjam" selected>Peminjam</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            + Tambah Pengguna
                        </button>
                    </div>
                </form>
            </div>

            <div class="card p-3">
                <h5 class="mb-3">Daftar Pengguna</h5>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (mysqli_num_rows($data) > 0): ?>

                                <?php while ($r = mysqli_fetch_assoc($data)): ?>
                                    <tr>
                                        <td><?= (int)$r["id"] ?></td>
                                        <td><?= e($r["nama"]) ?></td>
                                        <td><?= e($r["username"]) ?></td>
                                        <td>
                                            <span class="badge text-bg-secondary">
                                                <?= e(ucfirst($r["role"])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($r["status"] === "aktif"): ?>
                                                <span class="badge text-bg-success">
                                                    Aktif
                                                </span>
                                            <?php else: ?>
                                                <span class="badge text-bg-danger">
                                                    Nonaktif
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($r["created_at"] ?? "-") ?></td>

                                        <td class="text-nowrap">
                                            <?php if ((int)$r["id"] !== (int)$_SESSION["id"]): ?>

                                                <form method="POST" class="d-inline">
                                                    <input
                                                        type="hidden"
                                                        name="aksi"
                                                        value="status"
                                                    >
                                                    <input
                                                        type="hidden"
                                                        name="id"
                                                        value="<?= (int)$r["id"] ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-warning btn-sm"
                                                        onclick="return confirm('Yakin ingin mengubah status pengguna ini?')"
                                                    >
                                                        Ubah Status
                                                    </button>
                                                </form>

                                                <form
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')"
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
                                                    >
                                                        Hapus
                                                    </button>
                                                </form>

                                            <?php else: ?>
                                                <span class="text-muted">
                                                    Akun sendiri
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="7" class="text-center">
                                        Belum ada data pengguna.
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