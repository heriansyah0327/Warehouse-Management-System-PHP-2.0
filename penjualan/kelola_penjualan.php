<?php
require_once __DIR__ . '/../includes/auth.php';
require_management();   // hanya admin & staff

$base = '../';
$active_menu = 'kelola_penjualan';
$page_title = 'Kelola Penjualan - Management';
$page_header = 'Kelola Penjualan';

$statusList = ['menunggu', 'diacc', 'dilaporkan', 'selesai', 'ditolak', 'dibatalkan'];
$filter = $_GET['status'] ?? '';
if (!in_array($filter, $statusList, true)) $filter = '';

$sql = "SELECT j.*, u.full_name, u.username, u.phone,
               (SELECT GROUP_CONCAT(CONCAT(i.nama_produk, ' x', i.qty) SEPARATOR ', ') FROM penjualan_item i WHERE i.penjualan_id = j.id) AS ringkasan,
               (SELECT COALESCE(SUM(i.qty),0) FROM penjualan_item i WHERE i.penjualan_id = j.id) AS total_qty
        FROM penjualan j JOIN users u ON u.id = j.user_id";
if ($filter !== '') {
    $stmt = mysqli_prepare($conn, $sql . " WHERE j.status = ? ORDER BY j.created_at DESC, j.id DESC");
    mysqli_stmt_bind_param($stmt, 's', $filter);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
} else {
    $res = mysqli_query($conn, $sql . " ORDER BY FIELD(j.status,'menunggu','dilaporkan','diacc','selesai','ditolak','dibatalkan'), j.created_at DESC, j.id DESC");
}

$list = [];
while ($row = mysqli_fetch_assoc($res)) $list[] = $row;

// Item per pengajuan (untuk popup detail)
$itemsBy = [];
if ($list) {
    $ids = implode(',', array_map('intval', array_column($list, 'id')));
    $ri = mysqli_query($conn, "SELECT * FROM penjualan_item WHERE penjualan_id IN ($ids) ORDER BY id ASC");
    while ($it = mysqli_fetch_assoc($ri)) $itemsBy[(int)$it['penjualan_id']][] = $it;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-tabs">
    <a class="page-tab active" href="kelola_penjualan.php">💊 Kelola Penjualan</a>
    <a class="page-tab" href="kelola_jual_barang.php">🧳 Kelola Jual Barang</a>
</div>

<div class="chip-row">
    <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="kelola_penjualan.php">Semua</a>
    <?php foreach ($statusList as $s): ?>
        <a class="chip <?= $filter === $s ? 'active' : '' ?>" href="kelola_penjualan.php?status=<?= e($s) ?>"><?= e(status_label($s)) ?></a>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="panel-toolbar">
        <div class="search-box">
            <input type="text" placeholder="Cari pengajuan / pengaju..." data-table-search="#penjualanTable">
            <span class="icon">🔍</span>
        </div>
    </div>

    <table class="data-table" id="penjualanTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Pengaju</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
                <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">Belum ada pengajuan penjualan.</td></tr>
            <?php else: foreach ($list as $o):
                $detail = [
                    'id'         => (int)$o['id'],
                    'tanggal'    => date('d M Y, H:i', strtotime($o['created_at'])),
                    'pengaju'    => $o['full_name'],
                    'username'   => $o['username'],
                    'phone'      => $o['phone'] ?: '-',
                    'items'      => array_map(function ($it) {
                        return [
                            'nama' => $it['nama_produk'],
                            'qty'  => (int)$it['qty'],
                        ];
                    }, $itemsBy[(int)$o['id']] ?? []),
                    'catatan'    => $o['catatan'] ?: '',
                    'status'     => $o['status'],
                    'status_label' => status_label($o['status']),
                    'hasil_uang' => $o['hasil_uang'] !== null ? rupiah($o['hasil_uang']) : '',
                    'bukti_link' => $o['bukti_link'] ?: '',
                    'lapor_at'   => $o['lapor_at'] ? date('d M Y, H:i', strtotime($o['lapor_at'])) : '',
                ];
            ?>
                <tr>
                    <td>#<?= (int)$o['id'] ?></td>
                    <td><?= e(date('d M Y, H:i', strtotime($o['created_at']))) ?></td>
                    <td><?= e($o['full_name']) ?> <span class="muted">(<?= e($o['username']) ?>)</span></td>
                    <td><?= e($o['ringkasan'] ?? '-') ?></td>
                    <td><?= (int)$o['total_qty'] ?></td>
                    <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                    <td>
                        <button type="button" class="btn btn-outline" data-penjualan-detail="<?= e(json_encode($detail, JSON_UNESCAPED_UNICODE)) ?>">Detail</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="table-footer">Menampilkan <?= count($list) ?> pengajuan</div>
</div>

<!-- Popup Detail Penjualan -->
<div class="modal-overlay" id="modalDetailPenjualan">
    <div class="modal-box">
        <button class="modal-close" data-close-modal="modalDetailPenjualan">✕</button>
        <span class="modal-tag">Penjualan</span>
        <h2 id="pjTitle">Detail Penjualan</h2>

        <div class="od-meta" id="pjMeta"></div>

        <table class="data-table od-table">
            <thead>
                <tr><th>Produk</th><th>Jml</th></tr>
            </thead>
            <tbody id="pjItem"></tbody>
        </table>

        <!-- Laporan hasil dari homies -->
        <div id="pjLaporWrap" style="display:none; margin:14px 0;">
            <div class="od-meta" id="pjLaporMeta"></div>
        </div>

        <div id="pjBuktiWrap" style="margin:14px 0; display:none;">
            <div class="hint" style="margin-bottom:6px;">Bukti dari homies:</div>
            <a id="pjBuktiLink" href="#" target="_blank" rel="noopener">
                <img id="pjBuktiImg" src="" alt="Bukti" style="max-width:220px; border-radius:10px;" onerror="this.style.display='none'; document.getElementById('pjBuktiFallback').style.display='block';">
            </a>
            <div class="hint" id="pjBuktiFallback" style="display:none;">Gambar tidak bisa dimuat, klik link untuk membuka bukti.</div>
        </div>

        <!-- ACC / Tolak (status Menunggu). ACC polos, admin tidak upload gambar. -->
        <form method="POST" action="proses_penjualan.php" id="pjAccForm">
            <input type="hidden" name="id" id="pjAccId" value="">
            <div class="form-actions">
                <button type="submit" name="aksi" value="acc" class="btn btn-success">✔ ACC Penjualan</button>
                <button type="submit" name="aksi" value="tolak" class="btn btn-danger">✕ Tolak</button>
            </div>
        </form>

        <!-- Konfirmasi selesai (status Dilaporkan) -->
        <form method="POST" action="proses_penjualan.php" id="pjSelesaiForm" style="display:none;">
            <input type="hidden" name="id" id="pjSelesaiId" value="">
            <input type="hidden" name="aksi" value="selesai">
            <div class="form-actions">
                <button type="submit" class="btn btn-success">✔ Konfirmasi Penjualan Selesai</button>
            </div>
        </form>

        <div id="pjDone" class="hint" style="display:none;"></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
