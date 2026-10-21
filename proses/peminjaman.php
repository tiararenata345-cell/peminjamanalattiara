
<?php
require_once "../koneksi.php";
wajibLogin();

if (!in_array($_SESSION["role"], ["admin", "petugas"], true)
    || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$id = (int)($_POST["id"] ?? 0);
$aksi = $_POST["aksi"] ?? "";
$petugas = (int)$_SESSION["id"];

if (!in_array($aksi, ["disetujui", "ditolak"], true)) {
    header("Location: ../index.php");
    exit;
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

    if (!$p || $p["status"] !== "menunggu") {
        throw new Exception("Pengajuan sudah diproses.");
    }

    if ($aksi === "disetujui") {
        $stmt = mysqli_prepare($koneksi, "
            UPDATE alat
            SET stok = stok - ?
            WHERE id=? AND stok >= ? AND kondisi='baik'
        ");
        mysqli_stmt_bind_param($stmt, "iii", $p["jumlah"], $p["alat_id"], $p["jumlah"]);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            throw new Exception("Stok alat tidak mencukupi.");
        }
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($koneksi, "
        UPDATE peminjaman SET status=?, diproses_oleh=? WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "sii", $aksi, $petugas, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    catatAktivitas($koneksi, "Mengubah status peminjaman ID $id menjadi $aksi");

    mysqli_commit($koneksi);
    header("Location: " . ($_SESSION["role"] === "admin"
        ? "../admin/peminjaman.php" : "../petugas/peminjaman.php") . "?success=1");
    exit;
} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    header("Location: " . ($_SESSION["role"] === "admin"
        ? "../admin/peminjaman.php" : "../petugas/peminjaman.php") . "?error=1");
    exit;
}
?>
