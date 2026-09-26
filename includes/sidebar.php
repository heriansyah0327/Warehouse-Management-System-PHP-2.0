<?php
$isManagement = has_role('admin', 'staff');
$isHomies     = has_role('homies');

$mgmtActive     = in_array($active_menu, ['produk', 'pemesanan', 'kelola_penjualan', 'kelola_pemrosesan', 'staff', 'homies', 'request_password'], true);
$pembelianActive = in_array($active_menu, ['market', 'status_pesanan'], true);
$penjualanActive = in_array($active_menu, ['ajukan_penjualan', 'status_penjualan'], true);
$pemrosesanActive = in_array($active_menu, ['ajukan_pemrosesan', 'status_pemrosesan'], true);

// Jumlah pesanan yang masih menunggu (badge di menu Pemesanan)
$pendingCount = 0;
$resetCount = 0;   // permintaan reset password yang menunggu
$penjualanPendingCount = 0;   // pengajuan menunggu ACC + laporan hasil yang menunggu konfirmasi
$pemrosesanPendingCount = 0;   // pengajuan pemrosesan menunggu ACC + laporan hasil yang menunggu konfirmasi
if ($isManagement) {
    $qp = mysqli_query($conn, "SELECT COUNT(*) c FROM pesanan WHERE status = 'menunggu'");
    if ($qp) $pendingCount = (int)mysqli_fetch_assoc($qp)['c'];

    $qr = mysqli_query($conn, "SELECT COUNT(*) c FROM password_reset_request WHERE status = 'menunggu'");
    if ($qr) $resetCount = (int)mysqli_fetch_assoc($qr)['c'];

    $qj = mysqli_query($conn, "SELECT COUNT(*) c FROM penjualan WHERE status IN ('menunggu','dilaporkan')");
    if ($qj) $penjualanPendingCount = (int)mysqli_fetch_assoc($qj)['c'];

    $qpr = mysqli_query($conn, "SELECT COUNT(*) c FROM pemrosesan WHERE status IN ('menunggu','dilaporkan')");
    if ($qpr) $pemrosesanPendingCount = (int)mysqli_fetch_assoc($qpr)['c'];
}
?>
<aside class="sidebar">
    <div class="brand">
        <span>📦 Management</span>
        <button type="button" class="sidebar-close" data-sidebar-close aria-label="Tutup menu">✕</button>
    </div>
    <nav>
        <a class="nav-item <?= $active_menu === 'dashboard' ? 'active' : '' ?>" href="<?= e($base) ?>dashboard.php">
            🏠 Dashboard
        </a>

        <?php if ($isManagement): ?>

            <!-- Dropdown: Management (admin & staff saja) -->
            <div class="nav-group <?= $mgmtActive ? 'open' : '' ?>">
                <button type="button" class="nav-group-toggle" data-nav-toggle aria-expanded="<?= $mgmtActive ? 'true' : 'false' ?>">
                    <span>🗂️ Management</span>
                    <span class="chev">▾</span>
                </button>
                <div class="nav-children">
                    <a class="nav-item <?= $active_menu === 'produk' ? 'active' : '' ?>" href="<?= e($base) ?>produk/produk.php">
                        📦 Produk
                    </a>
                    <a class="nav-item <?= $active_menu === 'pemesanan' ? 'active' : '' ?>" href="<?= e($base) ?>pemesanan/pemesanan.php">
                        🧾 Pemesanan
                        <?php if ($pendingCount > 0): ?><span class="nav-badge"><?= $pendingCount ?></span><?php endif; ?>
                    </a>
                    <a class="nav-item <?= $active_menu === 'kelola_penjualan' ? 'active' : '' ?>" href="<?= e($base) ?>penjualan/kelola_penjualan.php">
                        💊 Kelola Penjualan
                        <?php if ($penjualanPendingCount > 0): ?><span class="nav-badge"><?= $penjualanPendingCount ?></span><?php endif; ?>
                    </a>
                    <a class="nav-item <?= $active_menu === 'kelola_pemrosesan' ? 'active' : '' ?>" href="<?= e($base) ?>pemrosesan/kelola_pemrosesan.php">
                        🧪 Kelola Pemrosesan
                        <?php if ($pemrosesanPendingCount > 0): ?><span class="nav-badge"><?= $pemrosesanPendingCount ?></span><?php endif; ?>
                    </a>
                    <?php if (has_role('admin')): ?>
                    <a class="nav-item <?= $active_menu === 'staff' ? 'active' : '' ?>" href="<?= e($base) ?>staff/management_staff.php">
                        🧑‍💼 Management Staff
                    </a>
                    <?php endif; ?>
                    <a class="nav-item <?= $active_menu === 'homies' ? 'active' : '' ?>" href="<?= e($base) ?>homies/management_homies.php">
                        🧑‍🤝‍🧑 Management Homies
                    </a>
                    <a class="nav-item <?= $active_menu === 'request_password' ? 'active' : '' ?>" href="<?= e($base) ?>homies/request_password.php">
                        🔑 Request Password
                        <?php if ($resetCount > 0): ?><span class="nav-badge"><?= $resetCount ?></span><?php endif; ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($isManagement || $isHomies): ?>
            <!-- Dropdown: Pembelian (admin, staff, homies) -->
            <div class="nav-group <?= $pembelianActive ? 'open' : '' ?>">
                <button type="button" class="nav-group-toggle" data-nav-toggle aria-expanded="<?= $pembelianActive ? 'true' : 'false' ?>">
                    <span>🛍️ Pembelian</span>
                    <span class="chev">▾</span>
                </button>
                <div class="nav-children">
                    <a class="nav-item <?= $active_menu === 'market' ? 'active' : '' ?>" href="<?= e($base) ?>pembelian/market.php">
                        🏪 Market
                    </a>
                    <a class="nav-item <?= $active_menu === 'status_pesanan' ? 'active' : '' ?>" href="<?= e($base) ?>pembelian/status_pesanan.php">
                        📋 Status Pesanan
                    </a>
                </div>
            </div>

            <!-- Dropdown: Penjualan (admin, staff, homies) — khusus produk Narko -->
            <div class="nav-group <?= $penjualanActive ? 'open' : '' ?>">
                <button type="button" class="nav-group-toggle" data-nav-toggle aria-expanded="<?= $penjualanActive ? 'true' : 'false' ?>">
                    <span>💊 Penjualan</span>
                    <span class="chev">▾</span>
                </button>
                <div class="nav-children">
                    <a class="nav-item <?= $active_menu === 'ajukan_penjualan' ? 'active' : '' ?>" href="<?= e($base) ?>penjualan/ajukan_penjualan.php">
                        📤 Ajukan Penjualan
                    </a>
                    <a class="nav-item <?= $active_menu === 'status_penjualan' ? 'active' : '' ?>" href="<?= e($base) ?>penjualan/status_penjualan.php">
                        📋 Status Penjualan
                    </a>
                </div>
            </div>

            <!-- Dropdown: Pemrosesan (admin, staff, homies) — khusus produk Spesial -->
            <div class="nav-group <?= $pemrosesanActive ? 'open' : '' ?>">
                <button type="button" class="nav-group-toggle" data-nav-toggle aria-expanded="<?= $pemrosesanActive ? 'true' : 'false' ?>">
                    <span>🧪 Pemrosesan</span>
                    <span class="chev">▾</span>
                </button>
                <div class="nav-children">
                    <a class="nav-item <?= $active_menu === 'ajukan_pemrosesan' ? 'active' : '' ?>" href="<?= e($base) ?>pemrosesan/ajukan_pemrosesan.php">
                        📤 Ajukan Pemrosesan
                    </a>
                    <a class="nav-item <?= $active_menu === 'status_pemrosesan' ? 'active' : '' ?>" href="<?= e($base) ?>pemrosesan/status_pemrosesan.php">
                        📋 Status Pemrosesan
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </nav>

    <!-- Info akun + aksi (tampil di HP saja; di desktop ada di topbar) -->
    <div class="sidebar-user">
        <div class="su-name"><?= e($me['full_name']) ?></div>
        <div class="su-role"><?= e(role_label($me['role'])) ?></div>
        <a class="nav-item" href="<?= e($base) ?>ganti_password.php">🔑 Ganti Password</a>
        <a class="nav-item" href="<?= e($base) ?>logout.php">🚪 Keluar</a>
    </div>
</aside>
