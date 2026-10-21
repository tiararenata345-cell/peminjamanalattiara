
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$koneksi = mysqli_connect("localhost", "root", "", "db_peminjaman");

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

function e($nilai) {
    return htmlspecialchars((string)($nilai ?? ""), ENT_QUOTES, "UTF-8");
}

function wajibLogin() {
    if (!isset($_SESSION["id"])) {
        header("Location: ../login.php");
        exit;
    }
}

function wajibRole($role) {
    if (!isset($_SESSION["id"]) || !isset($_SESSION["role"])) {
        header("Location: ../login.php");
        exit;
    }

    if ($_SESSION["role"] !== $role) {
        header("Location: ../login.php");
        exit;
    }
}

function catatAktivitas($koneksi, $aktivitas) {
    if (!isset($_SESSION["id"])) {
        return;
    }

    $id = (int)$_SESSION["id"];

    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO log_aktivitas (user_id, aktivitas) VALUES (?, ?)"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "is", $id, $aktivitas);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}
?>
