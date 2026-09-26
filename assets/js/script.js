// ---------- Live search di dalam tabel ----------
document.querySelectorAll('[data-table-search]').forEach(function (input) {
  var table = document.querySelector(input.getAttribute('data-table-search'));
  if (!table) return;
  input.addEventListener('input', function () {
    var q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(function (row) {
      row.style.display = row.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
    });
  });
});

// ---------- Konfirmasi hapus ----------
document.querySelectorAll('[data-delete-url]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var label = btn.getAttribute('data-delete-label') || 'data ini';
    if (confirm('Yakin mau hapus ' + label + '? Tindakan ini tidak bisa dibatalkan.')) {
      window.location.href = btn.getAttribute('data-delete-url');
    }
  });
});

// ---------- Modal generic open/close ----------
function openModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.add('show');
}
function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.remove('show');
}
document.querySelectorAll('[data-open-modal]').forEach(function (btn) {
  btn.addEventListener('click', function () { openModal(btn.getAttribute('data-open-modal')); });
});
document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
  btn.addEventListener('click', function () { closeModal(btn.getAttribute('data-close-modal')); });
});
document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) overlay.classList.remove('show');
  });
});

// ---------- Dropdown menu sidebar (Management / Pembelian) ----------
document.querySelectorAll('[data-nav-toggle]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var group = btn.closest('.nav-group');
    if (!group) return;
    var open = group.classList.toggle('open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });
});

// ---------- Live search untuk kartu produk (Market) ----------
document.querySelectorAll('[data-card-search]').forEach(function (input) {
  var grid = document.querySelector(input.getAttribute('data-card-search'));
  if (!grid) return;
  input.addEventListener('input', function () {
    var q = input.value.toLowerCase();
    grid.querySelectorAll('[data-search-item]').forEach(function (card) {
      card.style.display = card.textContent.toLowerCase().indexOf(q) > -1 ? '' : 'none';
    });
  });
});

// ---------- Stepper jumlah (+ / -) ----------
document.addEventListener('click', function (e) {
  var btn = e.target.closest('[data-step]');
  if (!btn) return;
  var input = btn.parentNode.querySelector('input[type=number]');
  if (!input) return;
  var min = parseInt(input.min || '1', 10);
  var max = parseInt(input.max || '999', 10);
  var v = (parseInt(input.value, 10) || min) + parseInt(btn.getAttribute('data-step'), 10);
  input.value = Math.max(min, Math.min(max, v));
});

// ---------- Popup Detail Pesanan (menu Management > Pemesanan) ----------
document.querySelectorAll('[data-order-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-order-detail')); } catch (err) { return; }

    document.getElementById('odTitle').textContent = 'Detail Pesanan #' + d.id;

    var meta = document.getElementById('odMeta');
    meta.textContent = '';
    [
      ['Homies', d.homies + ' (' + d.username + ')'],
      ['No. HP', d.phone],
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('odItems');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, it.harga, String(it.qty), it.subtotal].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });

    document.getElementById('odTotal').textContent = d.total;
    document.getElementById('odId').value = d.id;

    var actions = document.getElementById('odActions');
    var done = document.getElementById('odDone');
    if (d.status === 'menunggu') {
      actions.style.display = '';
      done.style.display = 'none';
    } else {
      actions.style.display = 'none';
      done.style.display = '';
      done.textContent = 'Pesanan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailPesanan');
  });
});

// Konfirmasi sebelum Selesai / Tolak pesanan
var odForm = document.getElementById('odActions');
if (odForm) {
  odForm.addEventListener('submit', function (e) {
    var aksi = e.submitter ? e.submitter.value : '';
    var msg = aksi === 'tolak'
      ? 'Yakin mau menolak pesanan ini? Stok barang akan dikembalikan.'
      : 'Tandai pesanan ini sebagai selesai?';
    if (!confirm(msg)) e.preventDefault();
  });
}

// ---------- Popup Detail Penjualan (menu Management > Kelola Penjualan) ----------
document.querySelectorAll('[data-penjualan-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-penjualan-detail')); } catch (err) { return; }

    document.getElementById('pjTitle').textContent = 'Detail Penjualan #' + d.id;

    var meta = document.getElementById('pjMeta');
    meta.textContent = '';
    [
      ['Pengaju', d.pengaju + ' (' + d.username + ')'],
      ['No. HP', d.phone],
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('pjItem');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, String(it.qty)].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });

    // Laporan hasil dari homies
    var laporWrap = document.getElementById('pjLaporWrap');
    var laporMeta = document.getElementById('pjLaporMeta');
    laporMeta.textContent = '';
    if (d.hasil_uang) {
      [['Hasil penjualan', d.hasil_uang], ['Dilaporkan', d.lapor_at]].forEach(function (r) {
        var line = document.createElement('div');
        var k = document.createElement('strong');
        k.textContent = r[0] + ': ';
        line.appendChild(k);
        line.appendChild(document.createTextNode(r[1]));
        laporMeta.appendChild(line);
      });
      laporWrap.style.display = '';
    } else {
      laporWrap.style.display = 'none';
    }

    var buktiWrap = document.getElementById('pjBuktiWrap');
    if (d.bukti_link) {
      var img = document.getElementById('pjBuktiImg');
      img.style.display = '';
      document.getElementById('pjBuktiFallback').style.display = 'none';
      img.src = d.bukti_link;
      document.getElementById('pjBuktiLink').href = d.bukti_link;
      buktiWrap.style.display = '';
    } else {
      buktiWrap.style.display = 'none';
    }

    var accForm = document.getElementById('pjAccForm');
    var selesaiForm = document.getElementById('pjSelesaiForm');
    var done = document.getElementById('pjDone');

    document.getElementById('pjAccId').value = d.id;
    document.getElementById('pjSelesaiId').value = d.id;

    accForm.style.display = 'none';
    selesaiForm.style.display = 'none';
    done.style.display = 'none';

    if (d.status === 'menunggu') {
      accForm.style.display = '';
    } else if (d.status === 'dilaporkan') {
      selesaiForm.style.display = '';
    } else if (d.status === 'diacc') {
      done.style.display = '';
      done.textContent = 'Sudah di-ACC. Menunggu homies lapor hasil penjualan.';
    } else {
      done.style.display = '';
      done.textContent = 'Pengajuan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailPenjualan');
  });
});

// Konfirmasi sebelum Tolak / Selesai penjualan
var pjAccForm = document.getElementById('pjAccForm');
if (pjAccForm) {
  pjAccForm.addEventListener('submit', function (e) {
    var aksi = e.submitter ? e.submitter.value : '';
    if (aksi === 'tolak' && !confirm('Yakin mau menolak penjualan ini? Stok barang akan dikembalikan.')) {
      e.preventDefault();
    }
  });
}
var pjSelesaiForm = document.getElementById('pjSelesaiForm');
if (pjSelesaiForm) {
  pjSelesaiForm.addEventListener('submit', function (e) {
    if (!confirm('Konfirmasi penjualan ini sudah selesai? Pastikan hasil & bukti dari homies sudah dicek.')) e.preventDefault();
  });
}

// ---------- Status Penjualan: tombol Lapor Hasil Penjualan ----------
document.querySelectorAll('[data-lapor-id]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var id = btn.getAttribute('data-lapor-id');
    document.getElementById('laporId').value = id;
    document.getElementById('laporTitle').textContent = 'Lapor Hasil Penjualan #' + id;
    openModal('modalLapor');
  });
});

// ---------- Popup Detail Pemrosesan (menu Management > Kelola Pemrosesan) ----------
document.querySelectorAll('[data-pemrosesan-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-pemrosesan-detail')); } catch (err) { return; }

    document.getElementById('prTitle').textContent = 'Detail Pemrosesan #' + d.id;

    var meta = document.getElementById('prMeta');
    meta.textContent = '';
    [
      ['Pengaju', d.pengaju + ' (' + d.username + ')'],
      ['No. HP', d.phone],
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('prItem');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, String(it.qty)].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });

    // Laporan hasil dari pengaju (item hasil, bukan uang)
    var laporWrap = document.getElementById('prLaporWrap');
    var laporMeta = document.getElementById('prLaporMeta');
    var laporItemBody = document.getElementById('prLaporItem');
    laporMeta.textContent = '';
    laporItemBody.textContent = '';
    if (d.hasil_items && d.hasil_items.length) {
      [['Dilaporkan', d.lapor_at]].forEach(function (r) {
        var line = document.createElement('div');
        var k = document.createElement('strong');
        k.textContent = r[0] + ': ';
        line.appendChild(k);
        line.appendChild(document.createTextNode(r[1]));
        laporMeta.appendChild(line);
      });
      d.hasil_items.forEach(function (it) {
        var tr = document.createElement('tr');
        [it.nama, String(it.qty)].forEach(function (val) {
          var td = document.createElement('td');
          td.textContent = val;
          tr.appendChild(td);
        });
        laporItemBody.appendChild(tr);
      });
      laporWrap.style.display = '';
    } else {
      laporWrap.style.display = 'none';
    }

    var buktiWrap = document.getElementById('prBuktiWrap');
    if (d.bukti_link) {
      var img = document.getElementById('prBuktiImg');
      img.style.display = '';
      document.getElementById('prBuktiFallback').style.display = 'none';
      img.src = d.bukti_link;
      document.getElementById('prBuktiLink').href = d.bukti_link;
      buktiWrap.style.display = '';
    } else {
      buktiWrap.style.display = 'none';
    }

    var accForm = document.getElementById('prAccForm');
    var selesaiForm = document.getElementById('prSelesaiForm');
    var done = document.getElementById('prDone');

    document.getElementById('prAccId').value = d.id;
    document.getElementById('prSelesaiId').value = d.id;

    accForm.style.display = 'none';
    selesaiForm.style.display = 'none';
    done.style.display = 'none';

    if (d.status === 'menunggu') {
      accForm.style.display = '';
    } else if (d.status === 'dilaporkan') {
      selesaiForm.style.display = '';
    } else if (d.status === 'diacc') {
      done.style.display = '';
      done.textContent = 'Sudah di-ACC. Menunggu pengaju lapor hasil pemrosesan.';
    } else {
      done.style.display = '';
      done.textContent = 'Pengajuan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailPemrosesan');
  });
});

// Konfirmasi sebelum Tolak / Selesai pemrosesan
var prAccForm = document.getElementById('prAccForm');
if (prAccForm) {
  prAccForm.addEventListener('submit', function (e) {
    var aksi = e.submitter ? e.submitter.value : '';
    if (aksi === 'tolak' && !confirm('Yakin mau menolak pemrosesan ini? Stok barang akan dikembalikan.')) {
      e.preventDefault();
    }
  });
}
var prSelesaiForm = document.getElementById('prSelesaiForm');
if (prSelesaiForm) {
  prSelesaiForm.addEventListener('submit', function (e) {
    if (!confirm('Konfirmasi pemrosesan ini sudah selesai? Pastikan hasil & bukti dari pengaju sudah dicek.')) e.preventDefault();
  });
}

// ---------- Status Pemrosesan: tombol Lapor Hasil Pemrosesan ----------
(function () {
  var rowsWrap = document.getElementById('laporProsesItemRows');
  var tpl = document.getElementById('laporProsesItemTpl');
  var addBtn = document.getElementById('laporProsesAddItem');
  if (!rowsWrap || !tpl) return;

  function addLaporProsesRow() {
    rowsWrap.appendChild(tpl.content.cloneNode(true));
  }

  if (addBtn) addBtn.addEventListener('click', addLaporProsesRow);

  // Hapus baris item (event delegation, karena baris ditambah dinamis)
  rowsWrap.addEventListener('click', function (e) {
    var btn = e.target.closest('[data-remove-lapor-item]');
    if (!btn) return;
    // Selalu sisakan minimal 1 baris
    if (rowsWrap.querySelectorAll('.lapor-item-row').length > 1) {
      btn.closest('.lapor-item-row').remove();
    }
  });

  document.querySelectorAll('[data-lapor-proses-id]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-lapor-proses-id');
      document.getElementById('laporProsesId').value = id;
      document.getElementById('laporProsesTitle').textContent = 'Lapor Hasil Pemrosesan #' + id;

      // Reset ke 1 baris item kosong setiap kali modal dibuka
      rowsWrap.innerHTML = '';
      addLaporProsesRow();

      openModal('modalLaporProses');
    });
  });
})();

// ---------- Popup Detail Pesanan (menu Status Pesanan - punya sendiri) ----------
document.querySelectorAll('[data-pesanan-user-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-pesanan-user-detail')); } catch (err) { return; }

    document.getElementById('psuTitle').textContent = 'Detail Pesanan #' + d.id;

    var meta = document.getElementById('psuMeta');
    meta.textContent = '';
    [
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('psuItems');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, it.harga, String(it.qty), it.subtotal].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });

    document.getElementById('psuTotal').textContent = d.total;
    document.getElementById('psuId').value = d.id;

    var actions = document.getElementById('psuActions');
    var done = document.getElementById('psuDone');
    if (d.status === 'menunggu') {
      actions.style.display = '';
      done.style.display = 'none';
    } else {
      actions.style.display = 'none';
      done.style.display = '';
      done.textContent = 'Pesanan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailPesananUser');
  });
});

// ---------- Popup Detail Penjualan (menu Status Penjualan - punya sendiri) ----------
document.querySelectorAll('[data-penjualan-user-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-penjualan-user-detail')); } catch (err) { return; }

    document.getElementById('pjuTitle').textContent = 'Detail Penjualan #' + d.id;

    var meta = document.getElementById('pjuMeta');
    meta.textContent = '';
    [
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('pjuItem');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, String(it.qty)].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });

    var laporWrap = document.getElementById('pjuLaporWrap');
    var laporMeta = document.getElementById('pjuLaporMeta');
    laporMeta.textContent = '';
    if (d.hasil_uang) {
      [['Hasil penjualan', d.hasil_uang], ['Dilaporkan', d.lapor_at]].forEach(function (r) {
        var line = document.createElement('div');
        var k = document.createElement('strong');
        k.textContent = r[0] + ': ';
        line.appendChild(k);
        line.appendChild(document.createTextNode(r[1]));
        laporMeta.appendChild(line);
      });
      laporWrap.style.display = '';
    } else {
      laporWrap.style.display = 'none';
    }

    var buktiWrap = document.getElementById('pjuBuktiWrap');
    if (d.bukti_link) {
      var img = document.getElementById('pjuBuktiImg');
      img.style.display = '';
      document.getElementById('pjuBuktiFallback').style.display = 'none';
      img.src = d.bukti_link;
      document.getElementById('pjuBuktiLink').href = d.bukti_link;
      buktiWrap.style.display = '';
    } else {
      buktiWrap.style.display = 'none';
    }

    var batalForm = document.getElementById('pjuBatalForm');
    var laporAction = document.getElementById('pjuLaporAction');
    var done = document.getElementById('pjuDone');

    document.getElementById('pjuBatalId').value = d.id;
    document.getElementById('pjuLaporBtn').setAttribute('data-lapor-id', d.id);

    batalForm.style.display = 'none';
    laporAction.style.display = 'none';
    done.style.display = 'none';

    if (d.status === 'menunggu') {
      batalForm.style.display = '';
    } else if (d.status === 'diacc') {
      laporAction.style.display = '';
    } else if (d.status === 'dilaporkan') {
      done.style.display = '';
      done.textContent = 'Menunggu konfirmasi admin/staff.';
    } else {
      done.style.display = '';
      done.textContent = 'Pengajuan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailPenjualanUser');
  });
});

// Tutup popup detail penjualan saat lanjut ke form Lapor Hasil Penjualan
var pjuLaporBtn = document.getElementById('pjuLaporBtn');
if (pjuLaporBtn) {
  pjuLaporBtn.addEventListener('click', function () {
    closeModal('modalDetailPenjualanUser');
  });
}

// ---------- Popup Detail Pemrosesan (menu Status Pemrosesan - punya sendiri) ----------
document.querySelectorAll('[data-pemrosesan-user-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-pemrosesan-user-detail')); } catch (err) { return; }

    document.getElementById('pruTitle').textContent = 'Detail Pemrosesan #' + d.id;

    var meta = document.getElementById('pruMeta');
    meta.textContent = '';
    [
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('pruItem');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, String(it.qty)].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });

    var laporWrap = document.getElementById('pruLaporWrap');
    var laporMeta = document.getElementById('pruLaporMeta');
    var laporItemBody = document.getElementById('pruLaporItem');
    laporMeta.textContent = '';
    laporItemBody.textContent = '';
    if (d.hasil_items && d.hasil_items.length) {
      [['Dilaporkan', d.lapor_at]].forEach(function (r) {
        var line = document.createElement('div');
        var k = document.createElement('strong');
        k.textContent = r[0] + ': ';
        line.appendChild(k);
        line.appendChild(document.createTextNode(r[1]));
        laporMeta.appendChild(line);
      });
      d.hasil_items.forEach(function (it) {
        var tr = document.createElement('tr');
        [it.nama, String(it.qty)].forEach(function (val) {
          var td = document.createElement('td');
          td.textContent = val;
          tr.appendChild(td);
        });
        laporItemBody.appendChild(tr);
      });
      laporWrap.style.display = '';
    } else {
      laporWrap.style.display = 'none';
    }

    var buktiWrap = document.getElementById('pruBuktiWrap');
    if (d.bukti_link) {
      var img = document.getElementById('pruBuktiImg');
      img.style.display = '';
      document.getElementById('pruBuktiFallback').style.display = 'none';
      img.src = d.bukti_link;
      document.getElementById('pruBuktiLink').href = d.bukti_link;
      buktiWrap.style.display = '';
    } else {
      buktiWrap.style.display = 'none';
    }

    var batalForm = document.getElementById('pruBatalForm');
    var laporAction = document.getElementById('pruLaporAction');
    var done = document.getElementById('pruDone');

    document.getElementById('pruBatalId').value = d.id;
    document.getElementById('pruLaporBtn').setAttribute('data-lapor-proses-id', d.id);

    batalForm.style.display = 'none';
    laporAction.style.display = 'none';
    done.style.display = 'none';

    if (d.status === 'menunggu') {
      batalForm.style.display = '';
    } else if (d.status === 'diacc') {
      laporAction.style.display = '';
    } else if (d.status === 'dilaporkan') {
      done.style.display = '';
      done.textContent = 'Menunggu konfirmasi admin/staff.';
    } else {
      done.style.display = '';
      done.textContent = 'Pengajuan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailPemrosesanUser');
  });
});

// Tutup popup detail pemrosesan saat lanjut ke form Lapor Hasil Pemrosesan
var pruLaporBtn = document.getElementById('pruLaporBtn');
if (pruLaporBtn) {
  pruLaporBtn.addEventListener('click', function () {
    closeModal('modalDetailPemrosesanUser');
  });
}

// ---------- Konfirmasi submit form generik (data-confirm) ----------
document.querySelectorAll('form[data-confirm]').forEach(function (form) {
  form.addEventListener('submit', function (e) {
    if (!confirm(form.getAttribute('data-confirm'))) e.preventDefault();
  });
});

// ---------- Keranjang: qty otomatis tersimpan (tanpa tombol Perbarui) ----------
(function () {
  var inputs = document.querySelectorAll('[data-cart-qty]');
  if (!inputs.length) return;

  var msgEl = document.getElementById('cartMsg');
  var totalEl = document.getElementById('cartTotal');
  var defaultMsg = msgEl ? msgEl.textContent : '';
  var timers = {};
  var seq = {};

  function setMsg(text, isError) {
    if (!msgEl) return;
    msgEl.textContent = text;
    msgEl.style.color = isError ? 'var(--red-600)' : '';
  }

  function save(input) {
    var pid = input.getAttribute('data-cart-qty');
    var max = parseInt(input.max || '999', 10);
    var min = parseInt(input.min || '1', 10);
    var url = input.getAttribute('data-cart-url') || 'keranjang.php';
    var q = parseInt(input.value, 10);
    if (isNaN(q) || q < min) q = min;
    if (q > max) { q = max; setMsg('Stok tersisa hanya ' + max + '.', true); }
    input.value = q;

    var mine = (seq[pid] = (seq[pid] || 0) + 1);
    var body = new URLSearchParams();
    body.append('ajax_update', '1');
    body.append('produk_id', pid);
    body.append('qty', q);

    setMsg('Menyimpan...', false);
    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body.toString(),
      credentials: 'same-origin'
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (mine !== seq[pid]) return;          // ada perubahan lebih baru, abaikan respon lama
        if (d.reload) { window.location.reload(); return; }
        if (!d.ok) { setMsg(d.msg || 'Gagal menyimpan.', true); return; }
        input.value = d.qty;
        var sub = document.querySelector('[data-cart-subtotal="' + pid + '"]');
        if (sub) sub.textContent = d.subtotal;
        if (totalEl) totalEl.textContent = d.total;
        if (d.msg) setMsg(d.msg, true); else setMsg('✔ Tersimpan otomatis.', false);
      })
      .catch(function () { setMsg('Gagal menyimpan, cek koneksi lalu coba lagi.', true); });
  }

  function schedule(input, delay) {
    var pid = input.getAttribute('data-cart-qty');
    clearTimeout(timers[pid]);
    timers[pid] = setTimeout(function () { save(input); }, delay);
  }

  inputs.forEach(function (input) {
    input.addEventListener('input', function () { schedule(input, 500); });
    input.addEventListener('change', function () { schedule(input, 0); });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); schedule(input, 0); }
    });
    // tombol + / - : script stepper mengubah value, lalu kita simpan
    var wrap = input.closest('.qty-stepper');
    if (wrap) wrap.querySelectorAll('[data-step]').forEach(function (b) {
      b.addEventListener('click', function () { schedule(input, 350); });
    });
  });
})();

// ---------- Popup Detail Jual Barang (halaman Status Jual Barang, milik sendiri) ----------
document.querySelectorAll('[data-jualbarang-user-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-jualbarang-user-detail')); } catch (err) { return; }

    document.getElementById('jbuTitle').textContent = 'Detail Jual Barang #' + d.id;

    var meta = document.getElementById('jbuMeta');
    meta.textContent = '';
    [
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('jbuItem');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, it.harga, String(it.qty), it.subtotal].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });
    document.getElementById('jbuTotal').textContent = d.total;

    var batalForm = document.getElementById('jbuBatalForm');
    var done = document.getElementById('jbuDone');
    document.getElementById('jbuBatalId').value = d.id;

    batalForm.style.display = 'none';
    done.style.display = 'none';

    if (d.status === 'menunggu') {
      batalForm.style.display = '';
    } else if (d.status === 'diacc') {
      done.style.display = '';
      done.textContent = 'Penjualan ini sudah selesai.';
    } else {
      done.style.display = '';
      done.textContent = 'Pengajuan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailJualBarangUser');
  });
});

// ---------- Popup Detail Jual Barang (menu Management > Kelola Jual Barang) ----------
document.querySelectorAll('[data-jualbarang-detail]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var d;
    try { d = JSON.parse(btn.getAttribute('data-jualbarang-detail')); } catch (err) { return; }

    document.getElementById('jbTitle').textContent = 'Detail Jual Barang #' + d.id;

    var meta = document.getElementById('jbMeta');
    meta.textContent = '';
    [
      ['Pengaju', d.pengaju + ' (' + d.username + ')'],
      ['No. HP', d.phone],
      ['Tanggal', d.tanggal],
      ['Status', d.status_label]
    ].concat(d.catatan ? [['Catatan', d.catatan]] : []).forEach(function (r) {
      var line = document.createElement('div');
      var k = document.createElement('strong');
      k.textContent = r[0] + ': ';
      line.appendChild(k);
      line.appendChild(document.createTextNode(r[1]));
      meta.appendChild(line);
    });

    var body = document.getElementById('jbItem');
    body.textContent = '';
    d.items.forEach(function (it) {
      var tr = document.createElement('tr');
      [it.nama, it.harga, String(it.qty), it.subtotal].forEach(function (val) {
        var td = document.createElement('td');
        td.textContent = val;
        tr.appendChild(td);
      });
      body.appendChild(tr);
    });
    document.getElementById('jbTotal').textContent = d.total;

    var accForm = document.getElementById('jbAccForm');
    var done = document.getElementById('jbDone');
    document.getElementById('jbAccId').value = d.id;

    accForm.style.display = 'none';
    done.style.display = 'none';

    if (d.status === 'menunggu') {
      accForm.style.display = '';
    } else {
      done.style.display = '';
      done.textContent = 'Pengajuan ini sudah ' + d.status_label.toLowerCase() + '.';
    }

    openModal('modalDetailJualBarang');
  });
});

var jbAccForm = document.getElementById('jbAccForm');
if (jbAccForm) {
  jbAccForm.addEventListener('submit', function (e) {
    var aksi = e.submitter ? e.submitter.value : '';
    if (aksi === 'acc' && !confirm('ACC pengajuan ini? Stok barang akan otomatis bertambah.')) {
      e.preventDefault();
    } else if (aksi === 'tolak' && !confirm('Yakin mau menolak pengajuan jual barang ini?')) {
      e.preventDefault();
    }
  });
}

// ---------- Sidebar off-canvas (HP) ----------
(function () {
  var toggle = document.querySelector('[data-sidebar-toggle]');
  if (!toggle) return;

  function setOpen(open) {
    document.body.classList.toggle('nav-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  }

  toggle.addEventListener('click', function () {
    setOpen(!document.body.classList.contains('nav-open'));
  });
  document.querySelectorAll('[data-sidebar-close]').forEach(function (el) {
    el.addEventListener('click', function () { setOpen(false); });
  });
  // tutup saat pilih menu, tekan Esc, atau layar dilebarkan lagi
  document.querySelectorAll('.sidebar a.nav-item').forEach(function (a) {
    a.addEventListener('click', function () { setOpen(false); });
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 860) setOpen(false);
  });
})();