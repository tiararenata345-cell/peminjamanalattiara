
<?php
require_once "koneksi.php";

if (isset($_SESSION["id"])) {
    catatAktivitas($koneksi, "Logout dari sistem");
}

$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
?>
