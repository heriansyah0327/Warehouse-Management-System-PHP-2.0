<?php
require_once __DIR__ . '/../includes/auth.php';
require_management();   // hanya admin & staff

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: kelola_pemrosesan.php');
    exit;
}

$id   = (int)($_POST['id'] ?? 0);
$aksi = $_POST['aksi'] ?? '';
$uid  = (int)current_user()['id'];

if ($id <= 0 || !in_array($aksi, ['acc', 'tolak', 'selesai'], true)) {
    flash_set('Permintaan tidak valid.', 'danger');
    header('Location: kelola_pemrosesan.php');
    exit;
}

// ---------------------------------------------------
// ACC: hanya dari status "menunggu" -> "diacc".
// ---------------------------------------------------
if ($aksi === 'acc') {
    $stmt = mysqli_prepare($conn, "UPDATE pemrosesan SET status = 'diacc', acc_by = ?, acc_at = NOW() WHERE id = ? AND status = 'menunggu'");
    mysqli_stmt_bind_param($stmt, 'ii', $uid, $id);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_affected_rows($stmt) > 0) {
        flash_set('Pemrosesan #' . $id . ' berhasil di-ACC. Pengaju bisa lapor hasil pemrosesan.');
    } else {
        flash_set('Pemrosesan #' . $id . ' sudah diproses sebelumnya atau tidak ditemukan.', 'danger');
    }
    header('Location: kelola_pemrosesan.php');
    exit;
}

// ---------------------------------------------------
// Tolak: hanya dari status "menunggu" -> "ditolak". Stok dikembalikan.
// ---------------------------------------------------
if ($aksi === 'tolak') {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "UPDATE pemrosesan SET status = 'ditolak' WHERE id = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            restore_stok_pemrosesan($conn, $id);
            mysqli_commit($conn);
            flash_set('Pemrosesan #' . $id . ' ditolak. Stok barang dikembalikan.');
        } else {
            mysqli_rollback($conn);
            flash_set('Pemrosesan #' . $id . ' sudah diproses sebelumnya atau tidak ditemukan.', 'danger');
        }
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        flash_set('Gagal menolak pemrosesan, coba lagi.', 'danger');
    }
    header('Location: kelola_pemrosesan.php');
    exit;
}

// ---------------------------------------------------
// Selesai: hanya dari status "dilaporkan" (pengaju sudah lapor hasil) -> "selesai".
// Di sinilah item hasil yang dilaporkan pengaju BARU ditambahkan ke stok produk.
// ---------------------------------------------------
if ($aksi === 'selesai') {
    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "UPDATE pemrosesan SET status = 'selesai', selesai_by = ?, selesai_at = NOW() WHERE id = ? AND status = 'dilaporkan'");
        mysqli_stmt_bind_param($stmt, 'ii', $uid, $id);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            tambah_stok_pemrosesan_hasil($conn, $id);   // stok baru bertambah di sini
            mysqli_commit($conn);
            flash_set('Pemrosesan #' . $id . ' dikonfirmasi selesai. Stok item hasil sudah ditambahkan.');
        } else {
            mysqli_rollback($conn);
            flash_set('Pemrosesan #' . $id . ' belum dilaporkan atau sudah selesai.', 'danger');
        }
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        flash_set('Gagal mengonfirmasi pemrosesan, coba lagi.', 'danger');
    }
    header('Location: kelola_pemrosesan.php');
    exit;
}
