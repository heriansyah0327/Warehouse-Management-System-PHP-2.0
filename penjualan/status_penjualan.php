<?php
require_once __DIR__ . '/../includes/auth.php';
require_penjualan();

$base = '../';
$active_menu = 'status_penjualan';
$page_title = 'Status Penjualan - Management';
$page_header = 'Status Penjualan';

$uid = (int)current_user()['id'];

// ---------------------------------------------------
// Batalkan pengajuan sendiri (hanya yang masih "menunggu")
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batalkan'])) {
    $jid = (int)$_POST['batalkan'];

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "UPDATE penjualan SET status = 'dibatalkan' WHERE id = ? AND user_id = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($stmt, 'ii', $jid, $uid);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            restore_stok_penjualan($conn, $jid);   // stok balik lagi
            mysqli_commit($conn);
            flash_set('Pengajuan #' . $jid . ' berhasil dibatalkan.');
        } else {
            mysqli_rollback($conn);
            flash_set('Pengajuan #' . $jid . ' tidak bisa dibatalkan (sudah diproses atau bukan pengajuanmu).', 'danger');
        }
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        flash_set('Gagal membatalkan pengajuan, coba lagi.', 'danger');
    }
    header('Location: status_penjualan.php');
    exit;
}

// ---------------------------------------------------
// Lapor hasil penjualan (hanya yang sudah "diacc"): jumlah uang + link bukti
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lapor'])) {
    $jid   = (int)$_POST['lapor'];
    $uang  = (int)($_POST['hasil_uang'] ?? 0);
    $bukti = trim($_POST['bukti_link'] ?? '');

    if ($uang < 1) {
        flash_set('Jumlah uang hasil penjualan wajib diisi.', 'danger');
    } elseif ($bukti === '' || !preg_match('#^https?://#i', $bukti) || !filter_var($bukti, FILTER_VALIDATE_URL) || strlen($bukti) > 500) {
        flash_set('Link bukti wajib diisi dan harus link yang valid (https://...).', 'danger');
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE penjualan SET status = 'dilaporkan', hasil_uang = ?, bukti_link = ?, lapor_at = NOW() WHERE id = ? AND user_id = ? AND status = 'diacc'");
        mysqli_stmt_bind_param($stmt, 'isii', $uang, $bukti, $jid, $uid);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            flash_set('Hasil penjualan #' . $jid . ' terkirim. Tunggu konfirmasi admin/staff.');
        } else {
            flash_set('Penjualan #' . $jid . ' belum di-ACC, sudah dilaporkan, atau bukan punyamu.', 'danger');
        }
    }
    header('Location: status_penjualan.php');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT j.*,
               (SELECT GROUP_CONCAT(CONCAT(i.nama_produk, ' x', i.qty) SEPARATOR ', ') FROM penjualan_item i WHERE i.penjualan_id = j.id) AS ringkasan,
               (SELECT COALESCE(SUM(i.qty),0) FROM penjualan_item i WHERE i.penjualan_id = j.id) AS total_qty
        FROM penjualan j WHERE j.user_id = ? ORDER BY j.created_at DESC, j.id DESC");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

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
    <a class="page-tab active" href="status_penjualan.php">📋 Status Penjualan</a>
    <a class="page-tab" href="status_jual_barang.php">🧳 Status Jual Barang</a>
</div>

<div class="panel">
    <div class="panel-toolbar">
        <div class="search-box">
            <input type="text" placeholder="Cari pengajuan..." data-table-search="#statusPenjualanTable">
            <span class="icon">🔍</span>
        </div>
    </div>

    <table class="data-table" id="statusPenjualanTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Hasil Penjualan</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:var(--text-muted);">
                        Kamu belum punya pengajuan penjualan. <a href="ajukan_penjualan.php">Ajukan Penjualan</a>
                    </td>
                </tr>
            <?php else: foreach ($list as $o):
                $detail = [
                    'id'         => (int)$o['id'],
                    'tanggal'    => date('d M Y, H:i', strtotime($o['created_at'])),
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
                    <td><?= e($o['ringkasan'] ?? '-') ?></td>
                    <td><?= (int)$o['total_qty'] ?></td>
                    <td><?= $o['hasil_uang'] !== null ? rupiah($o['hasil_uang']) : '-' ?></td>
                    <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                    <td>
                        <button type="button" class="btn btn-outline" data-penjualan-user-detail="<?= e(json_encode($detail, JSON_UNESCAPED_UNICODE)) ?>">Detail</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="table-footer">Menampilkan <?= count($list) ?> pengajuan</div>
</div>

<!-- Popup Detail Penjualan -->
<div class="modal-overlay" id="modalDetailPenjualanUser">
    <div class="modal-box">
        <button class="modal-close" data-close-modal="modalDetailPenjualanUser">✕</button>
        <span class="modal-tag">Penjualan</span>
        <h2 id="pjuTitle">Detail Penjualan</h2>

        <div class="od-meta" id="pjuMeta"></div>

        <table class="data-table od-table">
            <thead>
                <tr><th>Produk</th><th>Jml</th></tr>
            </thead>
            <tbody id="pjuItem"></tbody>
        </table>

        <!-- Laporan hasil penjualan -->
        <div id="pjuLaporWrap" style="display:none; margin:14px 0;">
            <div class="od-meta" id="pjuLaporMeta"></div>
        </div>

        <div id="pjuBuktiWrap" style="margin:14px 0; display:none;">
            <div class="hint" style="margin-bottom:6px;">Bukti kamu:</div>
            <a id="pjuBuktiLink" href="#" target="_blank" rel="noopener">
                <img id="pjuBuktiImg" src="" alt="Bukti" style="max-width:220px; border-radius:10px;" onerror="this.style.display='none'; document.getElementById('pjuBuktiFallback').style.display='block';">
            </a>
            <div class="hint" id="pjuBuktiFallback" style="display:none;">Gambar tidak bisa dimuat, klik link untuk membuka bukti.</div>
        </div>

        <!-- Batalkan (status Menunggu) -->
        <form method="POST" action="status_penjualan.php" id="pjuBatalForm" data-confirm="Yakin mau membatalkan pengajuan ini? Stok akan dikembalikan.">
            <input type="hidden" name="batalkan" id="pjuBatalId" value="">
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">✕ Batalkan Pengajuan</button>
            </div>
        </form>

        <!-- Lapor Hasil Penjualan (status Diacc) -->
        <div id="pjuLaporAction" class="form-actions" style="display:none;">
            <button type="button" class="btn btn-success" id="pjuLaporBtn" data-lapor-id="">💰 Lapor Hasil Penjualan</button>
        </div>

        <div id="pjuDone" class="hint" style="display:none;"></div>
    </div>
</div>

<!-- Popup Lapor Hasil Penjualan -->
<div class="modal-overlay" id="modalLapor">
    <div class="modal-box">
        <button class="modal-close" data-close-modal="modalLapor">✕</button>
        <span class="modal-tag">Penjualan</span>
        <h2 id="laporTitle">Lapor Hasil Penjualan</h2>

        <form method="POST" action="status_penjualan.php">
            <input type="hidden" name="lapor" id="laporId" value="">
            <div class="form-group" style="margin-bottom:12px;">
                <label>Jumlah uang yang didapat (Rp)</label>
                <input type="number" name="hasil_uang" min="1" step="1" required placeholder="Contoh: 1500000">
            </div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Link bukti (SS) — Discord / link online lainnya</label>
                <input type="url" name="bukti_link" required placeholder="https://cdn.discordapp.com/...">
                <div class="hint">Upload SS-nya ke Discord (atau hosting gambar), lalu tempel link gambarnya di sini.</div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">📤 Kirim Laporan</button>
                <button type="button" class="btn btn-outline" data-close-modal="modalLapor">Batal</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
