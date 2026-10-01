<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();   // semua role (admin, staff, homies) boleh lihat — read-only

$base = '../';
$active_menu = 'brangkas';
$page_title = 'Brangkas - Management';
$page_header = 'Brangkas';

// ---------------------------------------------------
// Uang Merah & Uang Putih = produk dengan nama persis ini (tidak peduli
// kategorinya). Nilainya diambil dari kolom stok. Kalau nama produk di
// database beda, ganti di 2 baris ini aja.
// ---------------------------------------------------
$UANG_MERAH = 'Uang Merah';
$UANG_PUTIH = 'Uang Putih';
$uangKeys   = [strtolower($UANG_MERAH), strtolower($UANG_PUTIH)];

// Formatnya ngikutin referensi: $5,777,467
function brangkas_dollar($n) {
    return '$' . number_format((int)$n, 0, '.', ',');
}

// ---------------------------------------------------
// Total uang (ditampilin terus di atas, tidak ikut filter kategori)
// ---------------------------------------------------
$uang = [
    $uangKeys[0] => ['stok' => 0, 'foto' => null],
    $uangKeys[1] => ['stok' => 0, 'foto' => null],
];
$resUang = mysqli_query($conn, "SELECT nama_produk, foto, stok FROM produk WHERE LOWER(TRIM(nama_produk)) IN ('" . $uangKeys[0] . "', '" . $uangKeys[1] . "')");
if ($resUang) {
    while ($r = mysqli_fetch_assoc($resUang)) {
        $k = strtolower(trim($r['nama_produk']));
        if (!isset($uang[$k])) continue;
        $uang[$k]['stok'] += (int)$r['stok'];
        if (!$uang[$k]['foto'] && $r['foto']) $uang[$k]['foto'] = $r['foto'];
    }
}

// ---------------------------------------------------
// Daftar item gudang. Uang Merah & Uang Putih SELALU dikeluarkan dari
// grid (di "Semua" maupun di filter kategori) karena sudah ada di atas.
// ---------------------------------------------------
$kategoriList  = kategori_options();
$kategoriAktif = $_GET['kategori'] ?? '';
if (!in_array($kategoriAktif, $kategoriList, true)) $kategoriAktif = '';

$notMoney = "LOWER(TRIM(nama_produk)) NOT IN ('" . $uangKeys[0] . "', '" . $uangKeys[1] . "')";

$items = [];
if ($kategoriAktif !== '') {
    $stmt = mysqli_prepare($conn, "SELECT nama_produk, kategori, foto, stok FROM produk WHERE kategori = ? AND $notMoney ORDER BY nama_produk ASC");
    mysqli_stmt_bind_param($stmt, 's', $kategoriAktif);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, "SELECT nama_produk, kategori, foto, stok FROM produk WHERE $notMoney ORDER BY nama_produk ASC");
}
if ($res) while ($row = mysqli_fetch_assoc($res)) $items[] = $row;

include __DIR__ . '/../includes/header.php';
?>

<style>
.vault-note { margin: 0 0 16px; font-size: 13.5px; color: var(--text-muted); }

/* ---------- Uang Merah / Uang Putih ---------- */
.vault-money-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 20px; }
.vault-money {
  background: #fff; border: 1px solid var(--border); border-radius: var(--radius);
  border-top: 4px solid var(--border);
  padding: 18px 20px; display: flex; align-items: center; gap: 16px;
}
.vault-money.merah { border-top-color: var(--red-600); }
.vault-money.putih { border-top-color: var(--green-600); }
.vm-img {
  width: 64px; height: 64px; flex-shrink: 0; border-radius: 12px; background: #eef1f7;
  display: flex; align-items: center; justify-content: center; overflow: hidden; font-size: 30px;
}
.vm-img img { width: 100%; height: 100%; object-fit: contain; }
.vm-label { font-size: 12.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; }
.vm-value { font-size: 28px; font-weight: 800; line-height: 1.2; margin: 2px 0; }
.vault-money.merah .vm-value { color: var(--red-600); }
.vault-money.putih .vm-value { color: var(--green-700); }
.vm-sub { font-size: 12.5px; color: var(--text-muted); }

/* ---------- Grid item gudang (read-only) ---------- */
.vault-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 16px; }
.vault-card {
  background: #fff; border: 1px solid var(--border); border-radius: var(--radius);
  padding: 12px; display: flex; flex-direction: column; gap: 8px;
  transition: box-shadow .15s ease, transform .15s ease;
}
.vault-card:hover { box-shadow: 0 8px 22px rgba(11,27,58,0.10); transform: translateY(-2px); }
.vc-img {
  height: 120px; border-radius: 10px; background: #eef1f7;
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.vc-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
.vc-img .pc-noimg { font-size: 40px; }
.vc-cat { align-self: flex-start; background: var(--blue-50); color: var(--blue-600); font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.vc-name { font-size: 14px; font-weight: 600; line-height: 1.35; }
.vc-qty { font-size: 22px; font-weight: 800; margin-top: auto; }
.vault-card.is-habis .vc-img { opacity: .5; }
.vault-card.is-habis .vc-name, .vault-card.is-habis .vc-qty { color: var(--text-muted); }
.vc-habis { font-size: 12px; font-weight: 600; color: var(--red-600); margin-top: auto; }

@media (max-width: 860px) {
  .vault-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
  .vc-img { height: 100px; }
}
@media (max-width: 560px) {
  .vault-money-grid { grid-template-columns: 1fr; }
  .vm-value { font-size: 24px; }
}
</style>

<p class="vault-note">Stok yang ada di gudang saat ini.</p>

<div class="vault-money-grid">
    <?php
    $cards = [
        ['cls' => 'merah', 'label' => $UANG_MERAH, 'key' => $uangKeys[0], 'icon' => '💸'],
        ['cls' => 'putih', 'label' => $UANG_PUTIH, 'key' => $uangKeys[1], 'icon' => '💵'],
    ];
    foreach ($cards as $c):
        $u = $uang[$c['key']];
    ?>
        <div class="vault-money <?= e($c['cls']) ?>">
            <div class="vm-img">
                <?php if ($u['foto']): ?>
                    <img src="<?= e($base) ?>assets/uploads/<?= e($u['foto']) ?>" alt="<?= e($c['label']) ?>">
                <?php else: ?>
                    <?= $c['icon'] ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="vm-label"><?= e($c['label']) ?></div>
                <div class="vm-value"><?= e(brangkas_dollar($u['stok'])) ?></div>
                <div class="vm-sub">Total stok <?= e($c['label']) ?> di gudang</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="market-bar">
    <div class="search-box">
        <input type="text" placeholder="Cari nama item..." data-card-search="#vaultGrid">
        <span class="icon">🔍</span>
    </div>
</div>

<div class="chip-row">
    <a class="chip <?= $kategoriAktif === '' ? 'active' : '' ?>" href="brangkas.php">Semua</a>
    <?php foreach ($kategoriList as $k): ?>
        <a class="chip <?= $kategoriAktif === $k ? 'active' : '' ?>" href="brangkas.php?kategori=<?= urlencode($k) ?>"><?= e($k) ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($items)): ?>
    <div class="panel"><div class="empty-state">Belum ada item di kategori ini.</div></div>
<?php else: ?>
    <div class="vault-grid" id="vaultGrid">
        <?php foreach ($items as $it): ?>
            <?php $stok = (int)$it['stok']; ?>
            <div class="vault-card <?= $stok <= 0 ? 'is-habis' : '' ?>" data-search-item>
                <div class="vc-img">
                    <?php if ($it['foto']): ?>
                        <img src="<?= e($base) ?>assets/uploads/<?= e($it['foto']) ?>" alt="<?= e($it['nama_produk']) ?>">
                    <?php else: ?>
                        <span class="pc-noimg">📦</span>
                    <?php endif; ?>
                </div>
                <span class="vc-cat"><?= e($it['kategori']) ?></span>
                <div class="vc-name"><?= e($it['nama_produk']) ?></div>
                <?php if ($stok <= 0): ?>
                    <div class="vc-habis">Stok habis</div>
                <?php else: ?>
                    <div class="vc-qty"><?= number_format($stok, 0, '.', ',') ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
