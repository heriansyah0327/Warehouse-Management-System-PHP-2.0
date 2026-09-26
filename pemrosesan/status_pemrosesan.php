<?php
require_once __DIR__ . '/../includes/auth.php';
require_pemrosesan();

$base = '../';
$active_menu = 'status_pemrosesan';
$page_title = 'Status Pemrosesan - Management';
$page_header = 'Status Pemrosesan';

$uid = (int)current_user()['id'];

// ---------------------------------------------------
// Batalkan pengajuan sendiri (hanya yang masih "menunggu")
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batalkan'])) {
    $pid = (int)$_POST['batalkan'];

    mysqli_begin_transaction($conn);
    try {
        $stmt = mysqli_prepare($conn, "UPDATE pemrosesan SET status = 'dibatalkan' WHERE id = ? AND user_id = ? AND status = 'menunggu'");
        mysqli_stmt_bind_param($stmt, 'ii', $pid, $uid);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) > 0) {
            restore_stok_pemrosesan($conn, $pid);   // stok balik lagi
            mysqli_commit($conn);
            flash_set('Pengajuan #' . $pid . ' berhasil dibatalkan.');
        } else {
            mysqli_rollback($conn);
            flash_set('Pengajuan #' . $pid . ' tidak bisa dibatalkan (sudah diproses atau bukan pengajuanmu).', 'danger');
        }
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        flash_set('Gagal membatalkan pengajuan, coba lagi.', 'danger');
    }
    header('Location: status_pemrosesan.php');
    exit;
}

// ---------------------------------------------------
// Lapor hasil pemrosesan (hanya yang sudah "diacc"): item hasil (dropdown,
// bisa lebih dari 1 baris) + link bukti. Item yang dilaporkan di sini BELUM
// menambah stok produk — stok baru ditambahkan saat admin/staff konfirmasi
// selesai (lihat aksi "selesai" di proses_pemrosesan.php).
// ---------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lapor'])) {
    $pid       = (int)$_POST['lapor'];
    $bukti     = trim($_POST['bukti_link'] ?? '');
    $produkIds = $_POST['hasil_produk_id'] ?? [];
    $qtys      = $_POST['hasil_qty'] ?? [];

    // Kumpulkan & validasi baris item (dropdown produk kategori Narko/Spesial + qty)
    $hasilKat = PEMROSESAN_HASIL_KATEGORI; // ['Narko', 'Spesial'] — fixed 2 kategori

    $items = [];
    $error = null;
    for ($i = 0; $i < count($produkIds); $i++) {
        $ppid = (int)$produkIds[$i];
        $qty  = (int)($qtys[$i] ?? 0);
        if ($ppid <= 0 && $qty <= 0) continue; // baris kosong, dilewati

        if ($ppid <= 0) { $error = 'Pilih produk untuk setiap baris item.'; break; }
        if ($qty < 1)   { $error = 'Jumlah item minimal 1.'; break; }

        $stmt = mysqli_prepare($conn, "SELECT id, nama_produk FROM produk WHERE id = ? AND kategori IN (?, ?) LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'iss', $ppid, $hasilKat[0], $hasilKat[1]);
        mysqli_stmt_execute($stmt);
        $p = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$p) { $error = 'Salah satu produk yang dipilih tidak valid.'; break; }

        $items[] = ['produk_id' => (int)$p['id'], 'nama_produk' => $p['nama_produk'], 'qty' => $qty];
    }

    if ($error) {
        flash_set($error, 'danger');
    } elseif (empty($items)) {
        flash_set('Isi minimal 1 item hasil pemrosesan.', 'danger');
    } elseif ($bukti === '' || !preg_match('#^https?://#i', $bukti) || !filter_var($bukti, FILTER_VALIDATE_URL) || strlen($bukti) > 500) {
        flash_set('Link bukti wajib diisi dan harus link yang valid (https://...).', 'danger');
    } else {
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "UPDATE pemrosesan SET status = 'dilaporkan', bukti_link = ?, lapor_at = NOW() WHERE id = ? AND user_id = ? AND status = 'diacc'");
            mysqli_stmt_bind_param($stmt, 'sii', $bukti, $pid, $uid);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $stmtItem = mysqli_prepare($conn, "INSERT INTO pemrosesan_hasil_item (pemrosesan_id, produk_id, nama_produk, qty) VALUES (?, ?, ?, ?)");
                foreach ($items as $it) {
                    mysqli_stmt_bind_param($stmtItem, 'iisi', $pid, $it['produk_id'], $it['nama_produk'], $it['qty']);
                    mysqli_stmt_execute($stmtItem);
                }
                mysqli_commit($conn);
                flash_set('Hasil pemrosesan #' . $pid . ' terkirim. Tunggu konfirmasi admin/staff.');
            } else {
                mysqli_rollback($conn);
                flash_set('Pemrosesan #' . $pid . ' belum di-ACC, sudah dilaporkan, atau bukan punyamu.', 'danger');
            }
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            flash_set('Gagal mengirim laporan, coba lagi.', 'danger');
        }
    }
    header('Location: status_pemrosesan.php');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT j.*,
               (SELECT GROUP_CONCAT(CONCAT(i.nama_produk, ' x', i.qty) SEPARATOR ', ') FROM pemrosesan_item i WHERE i.pemrosesan_id = j.id) AS ringkasan,
               (SELECT COALESCE(SUM(i.qty),0) FROM pemrosesan_item i WHERE i.pemrosesan_id = j.id) AS total_qty
        FROM pemrosesan j WHERE j.user_id = ? ORDER BY j.created_at DESC, j.id DESC");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$list = [];
while ($row = mysqli_fetch_assoc($res)) $list[] = $row;

// Item per pengajuan
$itemsBy = [];
$hasilItemsBy = [];
if ($list) {
    $ids = implode(',', array_map('intval', array_column($list, 'id')));
    $ri = mysqli_query($conn, "SELECT * FROM pemrosesan_item WHERE pemrosesan_id IN ($ids) ORDER BY id ASC");
    while ($it = mysqli_fetch_assoc($ri)) $itemsBy[(int)$it['pemrosesan_id']][] = $it;

    $rh = mysqli_query($conn, "SELECT * FROM pemrosesan_hasil_item WHERE pemrosesan_id IN ($ids) ORDER BY id ASC");
    while ($it = mysqli_fetch_assoc($rh)) $hasilItemsBy[(int)$it['pemrosesan_id']][] = $it;
}

// Produk untuk dropdown "Lapor Hasil Pemrosesan" — hanya kategori Narko & Spesial
$hasilKat = PEMROSESAN_HASIL_KATEGORI; // ['Narko', 'Spesial']
$stmtP = mysqli_prepare($conn, "SELECT id, nama_produk, kategori FROM produk WHERE kategori IN (?, ?) ORDER BY nama_produk ASC");
mysqli_stmt_bind_param($stmtP, 'ss', $hasilKat[0], $hasilKat[1]);
mysqli_stmt_execute($stmtP);
$produkHasilList = mysqli_fetch_all(mysqli_stmt_get_result($stmtP), MYSQLI_ASSOC);

include __DIR__ . '/../includes/header.php';
?>

<div class="panel">
    <div class="panel-toolbar">
        <div class="search-box">
            <input type="text" placeholder="Cari pengajuan..." data-table-search="#statusPemrosesanTable">
            <span class="icon">🔍</span>
        </div>
    </div>

    <table class="data-table" id="statusPemrosesanTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tanggal</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Status</th>
                <th>Tindakan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list)): ?>
                <tr>
                    <td colspan="6" style="text-align:center; color:var(--text-muted);">
                        Kamu belum punya pengajuan pemrosesan. <a href="ajukan_pemrosesan.php">Ajukan Pemrosesan</a>
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
                    <td><?= e($o['ringkasan'] ?? '-') ?></td>
                    <td><?= (int)$o['total_qty'] ?></td>
                    <td><span class="badge badge-<?= e($o['status']) ?>"><?= e(status_label($o['status'])) ?></span></td>
                    <td>
                        <button type="button" class="btn btn-outline" data-pemrosesan-user-detail="<?= e(json_encode($detail, JSON_UNESCAPED_UNICODE)) ?>">Detail</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
    <div class="table-footer">Menampilkan <?= count($list) ?> pengajuan</div>
</div>

<!-- Popup Detail Pemrosesan -->
<div class="modal-overlay" id="modalDetailPemrosesanUser">
    <div class="modal-box">
        <button class="modal-close" data-close-modal="modalDetailPemrosesanUser">✕</button>
        <span class="modal-tag">Pemrosesan</span>
        <h2 id="pruTitle">Detail Pemrosesan</h2>

        <div class="od-meta" id="pruMeta"></div>

        <table class="data-table od-table">
            <thead>
                <tr><th>Produk</th><th>Jml</th></tr>
            </thead>
            <tbody id="pruItem"></tbody>
        </table>

        <!-- Laporan hasil pemrosesan -->
        <div id="pruLaporWrap" style="display:none; margin:14px 0;">
            <div class="od-meta" id="pruLaporMeta"></div>
            <table class="data-table od-table" style="margin-top:8px;">
                <thead>
                    <tr><th>Hasil Proses</th><th>Jml</th></tr>
                </thead>
                <tbody id="pruLaporItem"></tbody>
            </table>
        </div>

        <div id="pruBuktiWrap" style="margin:14px 0; display:none;">
            <div class="hint" style="margin-bottom:6px;">Bukti kamu:</div>
            <a id="pruBuktiLink" href="#" target="_blank" rel="noopener">
                <img id="pruBuktiImg" src="" alt="Bukti" style="max-width:220px; border-radius:10px;" onerror="this.style.display='none'; document.getElementById('pruBuktiFallback').style.display='block';">
            </a>
            <div class="hint" id="pruBuktiFallback" style="display:none;">Gambar tidak bisa dimuat, klik link untuk membuka bukti.</div>
        </div>

        <!-- Batalkan (status Menunggu) -->
        <form method="POST" action="status_pemrosesan.php" id="pruBatalForm" data-confirm="Yakin mau membatalkan pengajuan ini? Stok akan dikembalikan.">
            <input type="hidden" name="batalkan" id="pruBatalId" value="">
            <div class="form-actions">
                <button type="submit" class="btn btn-danger">✕ Batalkan Pengajuan</button>
            </div>
        </form>

        <!-- Lapor Hasil Pemrosesan (status Diacc) -->
        <div id="pruLaporAction" class="form-actions" style="display:none;">
            <button type="button" class="btn btn-success" id="pruLaporBtn" data-lapor-proses-id="">📦 Lapor Hasil Pemrosesan</button>
        </div>

        <div id="pruDone" class="hint" style="display:none;"></div>
    </div>
</div>

<!-- Popup Lapor Hasil Pemrosesan -->
<div class="modal-overlay" id="modalLaporProses">
    <div class="modal-box">
        <button class="modal-close" data-close-modal="modalLaporProses">✕</button>
        <span class="modal-tag">Pemrosesan</span>
        <h2 id="laporProsesTitle">Lapor Hasil Pemrosesan</h2>

        <form method="POST" action="status_pemrosesan.php">
            <input type="hidden" name="lapor" id="laporProsesId" value="">

            <div class="form-group" style="margin-bottom:8px;">
                <label>Item Hasil Pemrosesan</label>
                <div class="hint">Pilih produk (kategori Narko / Spesial) dan jumlahnya. Item baru muncul di daftar setelah dikonfirmasi admin/staff.</div>
            </div>
            <div id="laporProsesItemRows"></div>
            <button type="button" class="btn btn-outline" id="laporProsesAddItem" style="margin:4px 0 14px;">+ Tambah Item</button>

            <div class="form-group" style="margin-bottom:12px;">
                <label>Link bukti (SS) — Discord / link online lainnya</label>
                <input type="url" name="bukti_link" required placeholder="https://cdn.discordapp.com/...">
                <div class="hint">Upload SS-nya ke Discord (atau hosting gambar), lalu tempel link gambarnya di sini.</div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-success">📤 Kirim Laporan</button>
                <button type="button" class="btn btn-outline" data-close-modal="modalLaporProses">Batal</button>
            </div>
        </form>

        <!-- Template 1 baris item (dipakai JS, tidak dikirim langsung) -->
        <template id="laporProsesItemTpl">
            <div class="lapor-item-row" style="display:flex; gap:8px; align-items:flex-start; margin-bottom:8px;">
                <select name="hasil_produk_id[]" style="flex:2;" required>
                    <option value="">Pilih produk...</option>
                    <?php foreach ($produkHasilList as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['nama_produk']) ?> (<?= e($p['kategori']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="hasil_qty[]" min="1" step="1" value="1" required placeholder="Jumlah" style="flex:1;">
                <button type="button" class="btn btn-danger" data-remove-lapor-item style="flex:0 0 auto;">✕</button>
            </div>
        </template>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
