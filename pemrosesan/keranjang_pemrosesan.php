<?php
require_once __DIR__ . '/../includes/auth.php';
require_pemrosesan();

$base = '../';
$active_menu = 'ajukan_pemrosesan';
$page_title = 'Keranjang Pemrosesan - Management';
$page_header = 'Keranjang Pemrosesan';

$me = current_user();
$minQty = PEMROSESAN_MIN_QTY;
$minFmt = number_format($minQty, 0, ',', '.');

// Data produk untuk semua item di keranjang (stok selalu dari DB, wajib Spesial)
function load_proses_items($conn) {
    $items = [];
    $kat = PEMROSESAN_KATEGORI;
    foreach (cart_proses_get() as $pid => $qty) {
        $pid = (int)$pid;
        $stmt = mysqli_prepare($conn, "SELECT id, nama_produk, kategori, foto, stok FROM produk WHERE id = ? AND kategori = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'is', $pid, $kat);
        mysqli_stmt_execute($stmt);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($p) {
            $p['stok'] = (int)$p['stok'];
            $p['qty'] = (int)$qty;
            $items[] = $p;
        } else {
            $c = cart_proses_get(); unset($c[$pid]); $_SESSION['cart_proses'] = $c;
        }
    }
    return $items;
}

// Sesuaikan keranjang dengan stok terbaru. Return: array pesan.
function proses_sync_stok($conn, $minQty) {
    $notes = [];
    $cart = cart_proses_get();
    foreach (load_proses_items($conn) as $it) {
        $pid = (int)$it['id'];
        if ($it['stok'] < $minQty) {
            unset($cart[$pid]);
            $notes[] = '"' . $it['nama_produk'] . '" stoknya di bawah minimum ' . number_format($minQty, 0, ',', '.') . ' dan dihapus dari keranjang.';
        } elseif ($it['qty'] > $it['stok']) {
            $cart[$pid] = $it['stok'];
            $notes[] = 'Jumlah "' . $it['nama_produk'] . '" disesuaikan jadi ' . $it['stok'] . ' (stok tersisa).';
        } elseif ($it['qty'] < $minQty) {
            $cart[$pid] = $minQty;
            $notes[] = 'Jumlah "' . $it['nama_produk'] . '" dinaikkan ke minimum ' . number_format($minQty, 0, ',', '.') . '.';
        }
    }
    $_SESSION['cart_proses'] = $cart;
    return $notes;
}

// ---------------------------------------------------
// Auto-update qty (via fetch dari JS)
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pid = (int)($_POST['produk_id'] ?? 0);
    $q   = (int)($_POST['qty'] ?? 0);
    $cart = cart_proses_get();
    $msg = '';

    if (!isset($cart[$pid])) {
        echo json_encode(['ok' => false, 'msg' => 'Item tidak ada di keranjang.']);
        exit;
    }

    $kat = PEMROSESAN_KATEGORI;
    $stmt = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id = ? AND kategori = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $pid, $kat);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $stok = $p ? (int)$p['stok'] : 0;

    if ($stok < $minQty) {
        unset($cart[$pid]);
        $_SESSION['cart_proses'] = $cart;
        echo json_encode(['ok' => false, 'reload' => true]);
        exit;
    }
    if ($q < $minQty) { $q = $minQty; $msg = 'Minimal ' . $minFmt . ' per barang.'; }
    if ($q > $stok)   { $q = $stok;   $msg = 'Stok tersisa hanya ' . $stok . '.'; }
    $cart[$pid] = $q;
    $_SESSION['cart_proses'] = $cart;

    echo json_encode([
        'ok'  => true,
        'qty' => $q,
        'msg' => $msg,
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Perubahan qty dari form (fallback tanpa JS)
    $cart = cart_proses_get();
    foreach (($_POST['qty'] ?? []) as $pid => $q) {
        $pid = (int)$pid;
        if (!isset($cart[$pid])) continue;
        $cart[$pid] = max($minQty, min(9999999, (int)$q));
    }
    // 2) Hapus satu item
    if (isset($_POST['remove'])) {
        unset($cart[(int)$_POST['remove']]);
    }
    $_SESSION['cart_proses'] = $cart;

    // 3) Ajukan pemrosesan
    if (isset($_POST['konfirmasi'])) {
        $notes = proses_sync_stok($conn, $minQty);
        if ($notes) {
            flash_set(implode(' ', $notes) . ' Cek lagi lalu ajukan.', 'danger');
            header('Location: keranjang_pemrosesan.php');
            exit;
        }

        $items = load_proses_items($conn);
        if (empty($items)) {
            flash_set('Keranjang pemrosesan masih kosong.', 'danger');
            header('Location: keranjang_pemrosesan.php');
            exit;
        }

        $catatan = trim($_POST['catatan'] ?? '');
        if (strlen($catatan) > 255) $catatan = substr($catatan, 0, 255);
        $uid = (int)$me['id'];
        $kat = PEMROSESAN_KATEGORI;

        mysqli_begin_transaction($conn);
        try {
            $stmtStok = mysqli_prepare($conn, "SELECT stok FROM produk WHERE id = ? AND kategori = ? FOR UPDATE");
            $stmtKurang = mysqli_prepare($conn, "UPDATE produk SET stok = stok - ? WHERE id = ?");
            foreach ($items as $it) {
                $pid = (int)$it['id'];
                $qty = (int)$it['qty'];
                if ($qty < $minQty) {
                    throw new Exception('Minimal ' . $minFmt . ' per barang.');
                }
                mysqli_stmt_bind_param($stmtStok, 'is', $pid, $kat);
                mysqli_stmt_execute($stmtStok);
                $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtStok));
                if (!$row || (int)$row['stok'] < $qty) {
                    throw new Exception('Stok "' . $it['nama_produk'] . '" tidak mencukupi.');
                }
                mysqli_stmt_bind_param($stmtKurang, 'ii', $qty, $pid);
                mysqli_stmt_execute($stmtKurang);
            }

            $stmt = mysqli_prepare($conn, "INSERT INTO pemrosesan (user_id, catatan, status) VALUES (?, ?, 'menunggu')");
            mysqli_stmt_bind_param($stmt, 'is', $uid, $catatan);
            mysqli_stmt_execute($stmt);
            $pemrosesanId = mysqli_insert_id($conn);

            $stmtItem = mysqli_prepare($conn, "INSERT INTO pemrosesan_item (pemrosesan_id, produk_id, nama_produk, qty) VALUES (?, ?, ?, ?)");
            foreach ($items as $it) {
                $pid = (int)$it['id'];
                $nama = $it['nama_produk'];
                $qty = (int)$it['qty'];
                mysqli_stmt_bind_param($stmtItem, 'iisi', $pemrosesanId, $pid, $nama, $qty);
                mysqli_stmt_execute($stmtItem);
            }
            mysqli_commit($conn);
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            $m = ($ex instanceof Exception && (strpos($ex->getMessage(), 'Stok') === 0 || strpos($ex->getMessage(), 'Minimal') === 0))
                ? $ex->getMessage() . ' Silakan cek keranjang.'
                : 'Gagal membuat pengajuan, coba lagi.';
            flash_set($m, 'danger');
            header('Location: keranjang_pemrosesan.php');
            exit;
        }

        $_SESSION['cart_proses'] = [];
        flash_set('Pengajuan pemrosesan #' . $pemrosesanId . ' berhasil dibuat. Tunggu di-ACC oleh admin/staff.');
        header('Location: status_pemrosesan.php');
        exit;
    }

    header('Location: keranjang_pemrosesan.php');
    exit;
}

// Saat halaman dibuka: sesuaikan dengan stok terbaru
$notes = proses_sync_stok($conn, $minQty);
if ($notes) flash_set(implode(' ', $notes), 'danger');

$items = load_proses_items($conn);

include __DIR__ . '/../includes/header.php';
?>

<?php if (empty($items)): ?>
    <div class="panel">
        <div class="empty-state">
            Keranjang pemrosesan kamu masih kosong.<br>
            <a class="btn btn-primary" style="margin-top:14px;" href="ajukan_pemrosesan.php">Pilih Barang</a>
        </div>
    </div>
<?php else: ?>
<form method="POST" action="keranjang_pemrosesan.php">
    <!-- tombol default (kalau user tekan Enter) = simpan qty, BUKAN hapus -->
    <button type="submit" name="update_cart" value="1" class="visually-hidden" tabindex="-1" aria-hidden="true">Perbarui</button>

    <div class="panel">
        <div class="panel-header"><h3>Barang yang Mau Diproses</h3></div>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Jumlah</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td>
                            <div class="cart-prod">
                                <div class="thumb">
                                    <?php if ($it['foto']): ?>
                                        <img src="<?= e($base) ?>assets/uploads/<?= e($it['foto']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>📦<?php endif; ?>
                                </div>
                                <div>
                                    <div><?= e($it['nama_produk']) ?></div>
                                    <span class="pc-cat"><?= e($it['kategori']) ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="qty-stepper">
                                <button type="button" data-step="-1">−</button>
                                <input type="number" name="qty[<?= (int)$it['id'] ?>]" value="<?= (int)$it['qty'] ?>" min="<?= $minQty ?>" max="<?= (int)$it['stok'] ?>" data-cart-qty="<?= (int)$it['id'] ?>" data-cart-url="keranjang_pemrosesan.php">
                                <button type="button" data-step="1">+</button>
                            </div>
                            <div class="hint" style="margin-top:4px;">Min <?= e($minFmt) ?> · Stok: <?= (int)$it['stok'] ?></div>
                        </td>
                        <td>
                            <button type="submit" name="remove" value="<?= (int)$it['id'] ?>" class="btn-icon delete" title="Hapus dari keranjang">🗑️</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <div style="padding:20px;">
            <div class="form-group" style="margin-bottom:16px;">
                <label>Catatan pengajuan (opsional)</label>
                <input type="text" name="catatan" maxlength="255" placeholder="Contoh: mau diproses malam ini">
            </div>
            <div class="hint" id="cartMsg" style="min-height:18px;">Jumlah otomatis tersimpan saat kamu ubah.</div>
            <div class="form-actions" style="margin-top:16px;">
                <button type="submit" name="konfirmasi" value="1" class="btn btn-success">📤 Ajukan Pemrosesan</button>
                <a class="btn btn-outline" href="ajukan_pemrosesan.php">Tambah Barang</a>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
