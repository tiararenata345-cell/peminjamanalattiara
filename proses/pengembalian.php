
<?php
require_once "../koneksi.php";
wajibLogin();

if (!in_array($_SESSION["role"], ["admin", "petugas"], true)
    || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$id = (int)($_POST["peminjaman_id"] ?? 0);
$tanggal = $_POST["tanggal_dikembalikan"] ?? date("Y-m-d");
$denda = (float)($_POST["denda"] ?? 0);
$kondisi = $_POST["kondisi_alat"] ?? "baik";
$petugas = (int)$_SESSION["id"];

if ($denda < 0 || !in_array($kondisi, ["baik", "rusak ringan", "rusak berat"], true)) {
    exit("Data pengembalian tidak valid.");
}

mysqli_begin_transaction($koneksi);

try {
    $stmt = mysqli_prepare($koneksi, "
        SELECT id, alat_id, jumlah, status
        FROM peminjaman WHERE id=? FOR UPDATE
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$p || $p["status"] !== "disetujui") {
        throw new Exception("Peminjaman belum disetujui atau sudah dikembalikan.");
    }

    $stmt = mysqli_prepare($koneksi, "
        SELECT id FROM pengembalian WHERE peminjaman_id=? LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $sudah = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($sudah) {
        throw new Exception("Pengembalian sudah dicatat.");
    }

    $stmt = mysqli_prepare($koneksi, "
        INSERT INTO pengembalian
        (peminjaman_id, tanggal_dikembalikan, denda, kondisi_alat, diterima_oleh)
        VALUES (?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "isdsi", $id, $tanggal, $denda, $kondisi, $petugas);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($koneksi, "
        UPDATE peminjaman SET status='dikembalikan' WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    /* Stok hanya ditambah untuk alat yang masih layak dipakai. */
    if ($kondisi === "baik" || $kondisi === "rusak ringan") {
        $stmt = mysqli_prepare($koneksi, "
            UPDATE alat SET stok=stok+? WHERE id=?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $p["jumlah"], $p["alat_id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    if ($kondisi !== "baik") {
        $stmt = mysqli_prepare($koneksi, "UPDATE alat SET kondisi=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "si", $kondisi, $p["alat_id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    catatAktivitas($koneksi, "Mencatat pengembalian peminjaman ID $id");

    mysqli_commit($koneksi);
    header("Location: " . ($_SESSION["role"] === "admin"
        ? "../admin/pengembalian.php" : "../petugas/pengembalian.php") . "?success=1");
    exit;
} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    header("Location: " . ($_SESSION["role"] === "admin"
        ? "../admin/pengembalian.php" : "../petugas/pengembalian.php") . "?error=1");
    exit;
}
?>
