<?php
require_once __DIR__ . '/../includes/auth.php';
require_management();   // hanya admin & staff

$base = '../';
$active_menu = 'kelola_pemrosesan';
$page_title = 'Kelola Pemrosesan - Management';
$page_header = 'Kelola Pemrosesan';

$statusList = ['menunggu', 'diacc', 'dilaporkan', 'selesai', 'ditolak', 'dibatalkan'];
$filter = $_GET['status'] ?? '';
if (!in_array($filter, $statusList, true)) $filter = '';

$sql = "SELECT j.*, u.full_name, u.username, u.phone,
               (SELECT GROUP_CONCAT(CONCAT(i.nama_produk, ' x', i.qty) SEPARATOR ', ') FROM pemrosesan_item i WHERE i.pemrosesan_id = j.id) AS ringkasan,
               (SELECT COALESCE(SUM(i.qty),0) FROM pemrosesan_item i WHERE i.pemrosesan_id = j.id) AS total_qty
        FROM pemrosesan j JOIN users u ON u.id = j.user_id";
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
$hasilItemsBy = [];
if ($list) {
    $ids = implode(',', array_map('intval', array_column($list, 'id')));
    $ri = mysqli_query($conn, "SELECT * FROM pemrosesan_item WHERE pemrosesan_id IN ($ids) ORDER BY id ASC");
    while ($it = mysqli_fetch_assoc($ri)) $itemsBy[(int)$it['pemrosesan_id']][] = $it;

    $rh = mysqli_query($conn, "SELECT * FROM pemrosesan_hasil_item WHERE pemrosesan_id IN ($ids) ORDER BY id ASC");
    while ($it = mysqli_fetch_assoc($rh)) $hasilItemsBy[(int)$it['pemrosesan_id']][] = $it;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="chip-row">
    <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="kelola_pemrosesan.php">Semua</a>
    <?php foreach ($statusList as $s): ?>
        <a class="chip <?= $filter === $s ? 'active' : '' ?>" href="kelola_pemrosesan.php?status=<?= e($s) ?>"><?= e(status_label($s)) ?></a>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="panel-toolbar">
        <div class="search-box">
            <input type="text" placeholder="Cari pengajuan / pengaju..." data-table-search="#pemrosesanTable">
            <span class="icon">🔍</span>
        </div>
    </div>

    <table class="data-table" id="pemrosesanTable">
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
                <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">Belum ada pengajuan pemrosesan.</td></tr>
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
                    'hasil_items' => array_map(function ($it) {
                        return [
                            'nama' => $it['nama_produk'],
                            'qty'  => (int)$it['qty'],
                        ];
                    }, $hasilItemsBy[(int)$o['id']] ?? []),
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
                        <button type="button" class="btn btn-outline" data-pemrosesan-detail="<?= e(json_encode($detail, JSON_UNESCAPED_UNICODE)) ?>">Detail</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="table-footer">Menampilkan <?= count($list) ?> pengajuan</div>
</div>

<!-- Popup Detail Pemrosesan -->
<div class="modal-overlay" id="modalDetailPemrosesan">
    <div class="modal-box">
        <button class="modal-close" data-close-modal="modalDetailPemrosesan">✕</button>
        <span class="modal-tag">Pemrosesan</span>
        <h2 id="prTitle">Detail Pemrosesan</h2>

        <div class="od-meta" id="prMeta"></div>

        <table class="data-table od-table">
            <thead>
                <tr><th>Produk</th><th>Jml</th></tr>
            </thead>
            <tbody id="prItem"></tbody>
        </table>

        <!-- Laporan hasil dari pengaju -->
        <div id="prLaporWrap" style="display:none; margin:14px 0;">
            <div class="od-meta" id="prLaporMeta"></div>
            <table class="data-table od-table" style="margin-top:8px;">
                <thead>
                    <tr><th>Item Hasil Dilaporkan</th><th>Jml</th></tr>
                </thead>
                <tbody id="prLaporItem"></tbody>
            </table>
            <div class="hint" style="margin-top:6px;">Stok baru ditambahkan ke produk setelah kamu klik "Konfirmasi Pemrosesan Selesai" di bawah.</div>
        </div>

        <div id="prBuktiWrap" style="margin:14px 0; display:none;">
            <div class="hint" style="margin-bottom:6px;">Bukti dari pengaju:</div>
            <a id="prBuktiLink" href="#" target="_blank" rel="noopener">
                <img id="prBuktiImg" src="" alt="Bukti" style="max-width:220px; border-radius:10px;" onerror="this.style.display='none'; document.getElementById('prBuktiFallback').style.display='block';">
            </a>
            <div class="hint" id="prBuktiFallback" style="display:none;">Gambar tidak bisa dimuat, klik link untuk membuka bukti.</div>
        </div>

        <!-- ACC / Tolak (status Menunggu) -->
        <form method="POST" action="proses_pemrosesan.php" id="prAccForm">
            <input type="hidden" name="id" id="prAccId" value="">
            <div class="form-actions">
                <button type="submit" name="aksi" value="acc" class="btn btn-success">✔ ACC Pemrosesan</button>
                <button type="submit" name="aksi" value="tolak" class="btn btn-danger">✕ Tolak</button>
            </div>
        </form>

        <!-- Konfirmasi selesai (status Dilaporkan) -->
        <form method="POST" action="proses_pemrosesan.php" id="prSelesaiForm" style="display:none;">
            <input type="hidden" name="id" id="prSelesaiId" value="">
            <input type="hidden" name="aksi" value="selesai">
            <div class="form-actions">
                <button type="submit" class="btn btn-success">✔ Konfirmasi Pemrosesan Selesai</button>
            </div>
        </form>

        <div id="prDone" class="hint" style="display:none;"></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
