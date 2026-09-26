<?php
require_once __DIR__ . '/../includes/auth.php';
require_pemrosesan();

$base = '../';
$active_menu = 'ajukan_pemrosesan';
$page_title = 'Ajukan Pemrosesan - Management';
$page_header = 'Ajukan Pemrosesan';

$isManagement = has_role('admin', 'staff');
$minQty = PEMROSESAN_MIN_QTY;
$minFmt = number_format($minQty, 0, ',', '.');
$kategoriSpesial = PEMROSESAN_KATEGORI;

// ---------------------------------------------------
// Pastikan tabel whitelist pemrosesan tersedia
// ---------------------------------------------------
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS pemrosesan_whitelist (
    produk_id INT(11) NOT NULL COMMENT 'Produk Spesial yang boleh diproses homies lewat Ajukan Pemrosesan',
    added_by INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (produk_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ---------------------------------------------------
// Simpan whitelist (khusus admin/staff, cuma boleh produk Spesial)
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_whitelist'])) {
    if (!$isManagement) {
        flash_set('Kamu tidak punya akses untuk mengatur barang yang bisa diproses.', 'danger');
        header('Location: ajukan_pemrosesan.php');
        exit;
    }

    $idsRaw = array_map('intval', $_POST['whitelist'] ?? []);
    $idsRaw = array_values(array_unique(array_filter($idsRaw, fn($v) => $v > 0)));

    // Saring ulang di server: hanya id yang benar-benar berkategori Spesial yang disimpan
    $ids = [];
    if (!empty($idsRaw)) {
        $placeholders = implode(',', array_fill(0, count($idsRaw), '?'));
        $types = str_repeat('i', count($idsRaw)) . 's';
        $stmtChk = mysqli_prepare($conn, "SELECT id FROM produk WHERE id IN ($placeholders) AND kategori = ?");
        $paramsChk = $idsRaw;
        $paramsChk[] = $kategoriSpesial;
        $refsChk = [];
        foreach ($paramsChk as $k => $v) $refsChk[$k] = &$paramsChk[$k];
        array_unshift($refsChk, $types);
        mysqli_stmt_bind_param($stmtChk, ...$refsChk);
        mysqli_stmt_execute($stmtChk);
        $resChk = mysqli_stmt_get_result($stmtChk);
        while ($row = mysqli_fetch_assoc($resChk)) $ids[] = (int)$row['id'];
    }

    $uid = (int)current_user()['id'];

    mysqli_begin_transaction($conn);
    try {
        mysqli_query($conn, "DELETE FROM pemrosesan_whitelist");
        if (!empty($ids)) {
            $stmt = mysqli_prepare($conn, "INSERT INTO pemrosesan_whitelist (produk_id, added_by) VALUES (?, ?)");
            foreach ($ids as $pid) {
                mysqli_stmt_bind_param($stmt, 'ii', $pid, $uid);
                mysqli_stmt_execute($stmt);
            }
        }
        mysqli_commit($conn);
        flash_set('Barang yang bisa diproses berhasil disimpan (' . count($ids) . ' produk Spesial).');
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        flash_set('Gagal menyimpan pengaturan, coba lagi.', 'danger');
    }
    header('Location: ajukan_pemrosesan.php');
    exit;
}

// ---------------------------------------------------
// Tambah ke keranjang pemrosesan (hanya Spesial & sudah di-whitelist)
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
    $pid = (int)($_POST['produk_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);

    $kat = $kategoriSpesial;
    $stmt = mysqli_prepare($conn, "SELECT p.nama_produk, p.stok FROM produk p JOIN pemrosesan_whitelist w ON w.produk_id = p.id WHERE p.id = ? AND p.kategori = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $pid, $kat);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p) {
        flash_set('Produk Spesial tidak ditemukan atau belum diizinkan untuk diproses.', 'danger');
    } elseif ($qty < $minQty) {
        flash_set('Minimal ambil ' . $minFmt . ' per barang.', 'danger');
    } else {
        $stok = (int)$p['stok'];
        $cart = cart_proses_get();
        $dikeranjang = (int)($cart[$pid] ?? 0);

        if ($stok < $minQty) {
            flash_set('Stok "' . $p['nama_produk'] . '" (' . $stok . ') kurang dari minimum ' . $minFmt . '.', 'danger');
        } elseif ($dikeranjang + $qty > $stok) {
            $sisa = $stok - $dikeranjang;
            if ($sisa < $minQty) {
                flash_set('Stok "' . $p['nama_produk'] . '" hanya ' . $stok . ' dan hampir semuanya sudah ada di keranjang pemrosesan.', 'danger');
            } else {
                flash_set('Stok "' . $p['nama_produk'] . '" hanya ' . $stok . '. Kamu cuma bisa nambah ' . $sisa . ' lagi.', 'danger');
            }
        } else {
            $cart[$pid] = $dikeranjang + $qty;
            $_SESSION['cart_proses'] = $cart;
            flash_set($qty . 'x "' . $p['nama_produk'] . '" masuk ke keranjang pemrosesan.');
        }
    }
    header('Location: ajukan_pemrosesan.php');
    exit;
}

// ---------------------------------------------------
// Daftar produk Spesial yang sudah di-whitelist
// ---------------------------------------------------
$produkList = [];
$stmt = mysqli_prepare($conn, "SELECT p.* FROM produk p JOIN pemrosesan_whitelist w ON w.produk_id = p.id WHERE p.kategori = ? ORDER BY p.nama_produk ASC");
mysqli_stmt_bind_param($stmt, 's', $kategoriSpesial);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $produkList[] = $row;

$cartCount = cart_proses_count();

// Produk kategori Spesial (untuk modal pengaturan, admin/staff saja)
$allProduk = [];
$whitelistedIds = [];
if ($isManagement) {
    $ra = mysqli_prepare($conn, "SELECT id, nama_produk, kategori FROM produk WHERE kategori = ? ORDER BY nama_produk ASC");
    mysqli_stmt_bind_param($ra, 's', $kategoriSpesial);
    mysqli_stmt_execute($ra);
    $resA = mysqli_stmt_get_result($ra);
    while ($row = mysqli_fetch_assoc($resA)) $allProduk[] = $row;

    $rw = mysqli_query($conn, "SELECT produk_id FROM pemrosesan_whitelist");
    while ($row = mysqli_fetch_assoc($rw)) $whitelistedIds[(int)$row['produk_id']] = true;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="market-bar">
    <div class="search-box">
        <input type="text" placeholder="Cari produk..." data-card-search="#productGrid">
        <span class="icon">🔍</span>
    </div>
    <div style="display:flex; gap:10px;">
        <?php if ($isManagement): ?>
            <button type="button" class="btn btn-outline" data-open-modal="modalWhitelistProses">⚙️ Atur Barang yang Bisa Diproses</button>
        <?php endif; ?>
        <a class="btn btn-primary" href="keranjang_pemrosesan.php">🛒 Keranjang<?php if ($cartCount > 0): ?> <span class="cart-pill"><?= $cartCount ?></span><?php endif; ?></a>
    </div>
</div>

<p class="hint" style="margin:0 0 14px;">Pengajuan dikirim dari keranjang, statusnya <strong>Menunggu</strong> sampai di-ACC admin/staff.</p>

<?php if (empty($produkList)): ?>
    <div class="panel"><div class="empty-state">Belum ada barang Spesial yang diizinkan untuk diproses.<?= $isManagement ? ' Klik "Atur Barang yang Bisa Diproses" untuk menambahkan.' : ' Minta admin/staff untuk menambahkannya.' ?></div></div>
<?php else: ?>
    <div class="product-grid" id="productGrid">
        <?php foreach ($produkList as $p): $stok = (int)$p['stok']; $kurang = $stok < $minQty; ?>
            <form method="POST" action="ajukan_pemrosesan.php" class="product-card <?= $stok <= 0 ? 'is-habis' : '' ?>" data-search-item>
                <input type="hidden" name="produk_id" value="<?= (int)$p['id'] ?>">
                <div class="pc-img">
                    <?php if ($p['foto']): ?>
                        <img src="<?= e($base) ?>assets/uploads/<?= e($p['foto']) ?>" alt="<?= e($p['nama_produk']) ?>">
                    <?php else: ?>
                        <span class="pc-noimg">📦</span>
                    <?php endif; ?>
                </div>
                <div class="pc-body">
                    <span class="pc-cat"><?= e($p['kategori']) ?></span>
                    <div class="pc-name"><?= e($p['nama_produk']) ?></div>
                    <div class="pc-stok <?= $stok <= 0 ? 'habis' : ($stok <= 5 ? 'menipis' : '') ?>">
                        <?= $stok <= 0 ? 'Stok habis' : 'Stok: ' . $stok ?>
                    </div>
                    <div class="pc-actions">
                        <?php if ($stok <= 0): ?>
                            <button type="button" class="btn btn-outline" disabled>Stok Habis</button>
                        <?php elseif ($kurang): ?>
                            <button type="button" class="btn btn-outline" disabled>Stok &lt; <?= e($minFmt) ?></button>
                        <?php else: ?>
                            <div class="qty-stepper">
                                <button type="button" data-step="-1">−</button>
                                <input type="number" name="qty" value="<?= $minQty ?>" min="<?= $minQty ?>" max="<?= $stok ?>">
                                <button type="button" data-step="1">+</button>
                            </div>
                            <button type="submit" name="add_cart" value="1" class="btn btn-primary">+ Keranjang</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($isManagement): ?>
<!-- Popup Atur Barang yang Bisa Diproses (hanya kategori Spesial) -->
<div class="modal-overlay" id="modalWhitelistProses">
    <div class="modal-box">
        <button class="modal-close" data-close-modal="modalWhitelistProses">✕</button>
        <span class="modal-tag">Ajukan Pemrosesan</span>
        <h2>Atur Barang yang Bisa Diproses</h2>
        <p class="hint" style="margin-bottom:12px;">Centang produk kategori <strong>Spesial</strong> yang boleh muncul di halaman Ajukan Pemrosesan. Produk yang tidak dicentang tidak akan tampil ke homies.</p>
        <form method="POST" action="ajukan_pemrosesan.php">
            <div class="form-group" style="margin-bottom:10px;">
                <input type="text" placeholder="Cari produk..." data-card-search="#wlListProses">
            </div>
            <div class="wl-list" id="wlListProses">
                <?php if (empty($allProduk)): ?>
                    <div class="wl-item">Belum ada produk kategori Spesial.</div>
                <?php else: foreach ($allProduk as $p): ?>
                    <div class="wl-item" data-search-item>
                        <label>
                            <input type="checkbox" name="whitelist[]" value="<?= (int)$p['id'] ?>" <?= isset($whitelistedIds[(int)$p['id']]) ? 'checked' : '' ?>>
                            <span><?= e($p['nama_produk']) ?></span>
                            <span class="wl-cat"><?= e($p['kategori']) ?></span>
                        </label>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="form-actions" style="margin-top:16px;">
                <button type="submit" name="save_whitelist" value="1" class="btn btn-success">💾 Simpan Pengaturan</button>
                <button type="button" class="btn btn-outline" data-close-modal="modalWhitelistProses">Batal</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>