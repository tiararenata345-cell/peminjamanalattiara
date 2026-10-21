<?php
require_once "../koneksi.php";
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT id, nama, username, password, role, status
     FROM users WHERE username = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user || $user["status"] !== "aktif") {
    header("Location: ../login.php?error=akun");
    exit;
}

/* Mendukung password_hash() dan password lama berbentuk teks biasa. */
$valid = password_verify($password, $user["password"]);

if (!$valid && !password_get_info($user["password"])["algo"]) {
    $valid = hash_equals((string)$user["password"], $password);
}

if (!$valid) {
    header("Location: ../login.php?error=akun");
    exit;
}


if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

session_regenerate_id(true);

$_SESSION["id"] = (int)$user["id"];
$_SESSION["nama"] = $user["nama"];
$_SESSION["username"] = $user["username"];
$_SESSION["role"] = $user["role"];

catatAktivitas($koneksi, "Login ke sistem");

header("Location: ../index.php");
exit;

?>