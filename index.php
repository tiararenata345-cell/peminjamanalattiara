
<?php
require_once "koneksi.php";

if (!isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}

switch ($_SESSION["role"]) {
    case "admin":
        header("Location: admin/index.php");
        break;

    case "petugas":
        header("Location: petugas/index.php");
        break;

    case "peminjam":
        header("Location: peminjam/index.php");
        break;

    default:
        session_destroy();
        header("Location: login.php");
        break;
}

exit;
?>
