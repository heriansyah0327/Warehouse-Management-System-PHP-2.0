<?php
require_once __DIR__ . '/../includes/auth.php';
require_penjualan();

$base = '../';
$active_menu = 'ajukan_penjualan';
$page_title = 'Ajukan Penjualan - Management';
$page_header = 'Ajukan Penjualan';

$minQty = PENJUALAN_MIN_QTY;
$minFmt = number_format($minQty, 0, ',', '.');

// ---------------------------------------------------
// Tambah ke keranjang penjualan (hanya Narko, minimal 1000 per barang)
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
    $pid = (int)($_POST['produk_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 0);

    $kat = PENJUALAN_KATEGORI;
    $stmt = mysqli_prepare($conn, "SELECT nama_produk, stok FROM produk WHERE id = ? AND kategori = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $pid, $kat);
    mysqli_stmt_execute($stmt);
    $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$p) {
        flash_set('Produk Narko tidak ditemukan.', 'danger');
    } elseif ($qty < $minQty) {
        flash_set('Minimal ambil ' . $minFmt . ' per barang.', 'danger');
    } else {
        $stok = (int)$p['stok'];
        $cart = cart_jual_get();
        $dikeranjang = (int)($cart[$pid] ?? 0);

        if ($stok < $minQty) {
            flash_set('Stok "' . $p['nama_produk'] . '" (' . $stok . ') kurang dari minimum ' . $minFmt . '.', 'danger');
        } elseif ($dikeranjang + $qty > $stok) {
            $sisa = $stok - $dikeranjang;
            if ($sisa < $minQty) {
                flash_set('Stok "' . $p['nama_produk'] . '" hanya ' . $stok . ' dan hampir semuanya sudah ada di keranjang penjualan.', 'danger');
            } else {
                flash_set('Stok "' . $p['nama_produk'] . '" hanya ' . $stok . '. Kamu cuma bisa nambah ' . $sisa . ' lagi.', 'danger');
            }
        } else {
            $cart[$pid] = $dikeranjang + $qty;
            $_SESSION['cart_jual'] = $cart;
            flash_set($qty . 'x "' . $p['nama_produk'] . '" masuk ke keranjang penjualan.');
        }
    }
    header('Location: ajukan_penjualan.php');
    exit;
}

// ---------------------------------------------------
// Daftar produk Narko
// ---------------------------------------------------
$produkList = [];
$kategoriNarko = PENJUALAN_KATEGORI;
$stmt = mysqli_prepare($conn, "SELECT * FROM produk WHERE kategori = ? ORDER BY nama_produk ASC");
mysqli_stmt_bind_param($stmt, 's', $kategoriNarko);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) $produkList[] = $row;

$cartCount = cart_jual_count();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-tabs">
    <a class="page-tab active" href="ajukan_penjualan.php">📤 Ajukan Penjualan</a>
    <a class="page-tab" href="jual_barang.php">🧳 Jual Barang</a>
</div>

<div class="market-bar">
    <div class="search-box">
        <input type="text" placeholder="Cari produk..." data-card-search="#productGrid">
        <span class="icon">🔍</span>
    </div>
    <a class="btn btn-primary" href="keranjang_penjualan.php">🛒 Keranjang<?php if ($cartCount > 0): ?> <span class="cart-pill"><?= $cartCount ?></span><?php endif; ?></a>
</div>

<p class="hint" style="margin:0 0 14px;">Minimal ambil <strong><?= e($minFmt) ?></strong> per barang. Pengajuan dikirim dari keranjang, statusnya <strong>Menunggu</strong> sampai di-ACC admin/staff.</p>

<?php if (empty($produkList)): ?>
    <div class="panel"><div class="empty-state">Belum ada produk kategori Narko.</div></div>
<?php else: ?>
    <div class="product-grid" id="productGrid">
        <?php foreach ($produkList as $p): $stok = (int)$p['stok']; $kurang = $stok < $minQty; ?>
            <form method="POST" action="ajukan_penjualan.php" class="product-card <?= $stok <= 0 ? 'is-habis' : '' ?>" data-search-item>
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
                                <button type="button" data-step="-100">−</button>
                                <input type="number" name="qty" value="<?= $minQty ?>" min="<?= $minQty ?>" max="<?= $stok ?>">
                                <button type="button" data-step="100">+</button>
                            </div>
                            <button type="submit" name="add_cart" value="1" class="btn btn-primary">+ Keranjang</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
