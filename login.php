
<?php
require_once "koneksi.php";

if (isset($_SESSION["id"])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Peminjaman Alat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="login-wrapper">
    <div class="login-card">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Peminjaman Alat</h3>
            <p class="text-muted">Silakan masuk ke akun Anda</p>
        </div>

        <?php if (isset($_GET["error"])): ?>
            <div class="alert alert-danger">
                <?= e($_GET["error"] === "akun"
                    ? "Username atau password salah, atau akun tidak aktif."
                    : "Silakan login terlebih dahulu.") ?>
            </div>
        <?php endif; ?>

        <form action="proses/login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       required autocomplete="username">
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary w-100">
                Login
            </button>
        </form>
    </div>
</div>
</body>
</html>
