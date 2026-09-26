<?php
require_once __DIR__ . '/../includes/auth.php';
require_management();   // hanya admin & staff

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kelola_penjualan.php');
    exit;
}

$id   = (int)($_POST['id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
$uid  = (int)current_user()['id'];

if ($id <= 0 || !in_array($aksi, ['acc', 'tolak', 'selesai'], true)) {
    flash_set('Permintaan tidak valid.', 'danger');
    header('Location: kelola_penjualan.php');
    exit;
}

// ---------------------------------------------------
// ACC: hanya dari status "menunggu" -> "diacc". Admin/staff tidak upload gambar.
// ---------------------------------------------------
if ($aksi === 'acc') {
    $stmt = mysqli_prepare($conn, "UPDATE penjualan SET status = 'diacc', acc_by = ?, acc_at = NOW() WHERE id = ? AND status = 'menunggu'");
    mysqli_stmt_bind_param($stmt, 'ii', $uid, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        flash_set('Penjualan #' . $id . ' berhasil di-ACC. Homies bisa lapor hasil penjualan.');
    } else {
        flash_set('Penjualan #' . $id . ' sudah diproses sebelumnya atau tidak ditemukan.', 'danger');
    }
    header('Location: kelola_penjualan.php');
    exit;
}

// ---------------------------------------------------
// Tolak: hanya dari status "menunggu" -> "ditolak". Stok dikembalikan.
// ---------------------------------------------------
if ($aksi === 'tolak') {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "UPDATE penjualan SET status = 'ditolak' WHERE id = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            restore_stok_penjualan($conn, $id);
            mysqli_commit($conn);
            flash_set('Penjualan #' . $id . ' ditolak. Stok barang dikembalikan.');
        } else {
            mysqli_rollback($conn);
            flash_set('Penjualan #' . $id . ' sudah diproses sebelumnya atau tidak ditemukan.', 'danger');
        }
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        flash_set('Gagal menolak penjualan, coba lagi.', 'danger');
    }
    header('Location: kelola_penjualan.php');
    exit;
}

// ---------------------------------------------------
// Selesai: hanya dari status "dilaporkan" (homies sudah lapor hasil) -> "selesai".
// ---------------------------------------------------
if ($aksi === 'selesai') {
    $stmt = mysqli_prepare($conn, "UPDATE penjualan SET status = 'selesai', selesai_by = ?, selesai_at = NOW() WHERE id = ? AND status = 'dilaporkan'");
    mysqli_stmt_bind_param($stmt, 'ii', $uid, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        flash_set('Penjualan #' . $id . ' dikonfirmasi selesai.');
    } else {
        flash_set('Penjualan #' . $id . ' belum dilaporkan homies atau sudah selesai.', 'danger');
    }
    header('Location: kelola_penjualan.php');
    exit;
}
