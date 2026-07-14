(function () {
  'use strict';
  const $ = (s) => document.querySelector(s);
  const login = $('#login');
  const dash = $('#dash');

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }
  async function api(url, opts = {}) {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json' },
      ...opts,
    });
    return res;
  }

  // ---------- AUTH ----------
  async function checkAuth() {
    const res = await fetch('/api/admin/me');
    const data = await res.json();
    if (data.ok) showDash();
  }
  $('#login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const err = $('#login-err');
    err.textContent = '';
    const res = await api('/api/admin/login', {
      method: 'POST',
      body: JSON.stringify({ password: $('#login-pass').value }),
    });
    if (res.ok) showDash();
    else err.textContent = 'Password salah';
  });
  $('#logout').addEventListener('click', async () => {
    await api('/api/admin/logout', { method: 'POST' });
    dash.classList.add('hidden');
    login.classList.remove('hidden');
  });

  function showDash() {
    login.classList.add('hidden');
    dash.classList.remove('hidden');
    loadUndangan();
    loadUcapan();
    refreshWaStatus();
  }

  // ---------- TABS ----------
  document.querySelectorAll('.tab').forEach((t) => {
    t.addEventListener('click', () => {
      document.querySelectorAll('.tab').forEach((x) => x.classList.remove('active'));
      document.querySelectorAll('.panel').forEach((x) => x.classList.remove('active'));
      t.classList.add('active');
      $(`.panel[data-panel="${t.dataset.tab}"]`).classList.add('active');
      if (t.dataset.tab === 'whatsapp') startWaPolling();
      else stopWaPolling();
      if (t.dataset.tab === 'ucapan') loadUcapan();
    });
  });

  // ---------- UNDANGAN ----------
  async function loadUndangan() {
    const res = await fetch('/api/admin/undangan');
    if (!res.ok) return;
    const data = await res.json();
    $('#stat-total').textContent = data.total;
    $('#stat-terkirim').textContent = data.terkirim;
    $('#stat-belum').textContent = data.total - data.terkirim;
    const tb = $('#tbl-undangan tbody');
    tb.innerHTML = data.rows.map((r, i) => `
      <tr>
        <td>${i + 1}</td>
        <td>${esc(r.nama)}</td>
        <td>${esc(r.no)}</td>
        <td>${r.sent
          ? '<span class="tag sent">Terkirim</span>'
          : '<span class="tag unsent">Belum</span>'}</td>
        <td><button class="del" data-id="${r.id}" title="Hapus">✕</button></td>
      </tr>`).join('') || '<tr><td colspan="5" style="text-align:center;color:#8b93a1">Belum ada data. Upload spreadsheet dulu.</td></tr>';
    tb.querySelectorAll('.del').forEach((b) => b.addEventListener('click', async () => {
      await api('/api/admin/undangan/' + b.dataset.id, { method: 'DELETE' });
      loadUndangan();
    }));
  }

  $('#upload-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = $('#upload-msg');
    const file = $('#file').files[0];
    if (!file) return;
    msg.textContent = 'Mengupload...'; msg.className = 'msg';
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch('/api/admin/upload', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) {
      msg.textContent = `Berhasil: ${data.inserted} ditambahkan, ${data.skipped} dilewati (dari ${data.totalBaris} baris).`;
      msg.className = 'msg ok';
      $('#upload-form').reset();
      loadUndangan();
    } else {
      msg.textContent = data.error || 'Gagal upload';
      msg.className = 'msg err';
    }
  });

  $('#add-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = $('#add-msg');
    const res = await api('/api/admin/undangan', {
      method: 'POST',
      body: JSON.stringify({ nama: $('#add-nama').value, no: $('#add-no').value }),
    });
    const data = await res.json();
    if (data.ok) {
      msg.textContent = 'Ditambahkan'; msg.className = 'msg ok';
      $('#add-form').reset();
      loadUndangan();
    } else {
      msg.textContent = data.error || 'Gagal'; msg.className = 'msg err';
    }
  });

  $('#btn-clear').addEventListener('click', async () => {
    if (!confirm('Hapus SEMUA data undangan?')) return;
    await api('/api/admin/undangan', { method: 'DELETE' });
    loadUndangan();
  });

  $('#btn-kirim').addEventListener('click', async () => {
    const onlyUnsent = $('#only-unsent').checked;
    if (!confirm('Kirim undangan WhatsApp sekarang?')) return;
    const btn = $('#btn-kirim');
    btn.disabled = true; btn.textContent = 'Mengirim...';
    try {
      const res = await api('/api/admin/kirim', {
        method: 'POST',
        body: JSON.stringify({ onlyUnsent }),
      });
      const data = await res.json();
      renderSendResult(data);
      loadUndangan();
    } catch (e) {
      alert('Gagal: ' + e.message);
    } finally {
      btn.disabled = false; btn.textContent = '📤 Kirim Undangan WhatsApp';
    }
  });

  function renderSendResult(data) {
    const card = $('#send-result-card');
    const box = $('#send-result');
    card.style.display = 'block';
    let head = '';
    if (data.mode === 'link') {
      head = `<p class="hint">WhatsApp belum terhubung, jadi tersedia link kirim manual. Hubungkan WhatsApp di tab <b>WhatsApp</b> untuk pengiriman otomatis.</p>`;
    } else {
      head = `<p class="hint">${data.dikirim} dari ${data.total} pesan terkirim otomatis.</p>`;
    }
    box.innerHTML = head + data.results.map((r) => {
      const st = `<span class="st-${r.status}">${r.status}</span>`;
      const link = r.link ? ` <a href="${esc(r.link)}" target="_blank">buka wa.me →</a>` : '';
      const err = r.error ? ` <span class="st-gagal">(${esc(r.error)})</span>` : '';
      return `<div class="r"><span>${esc(r.nama)} · ${esc(r.no)}</span><span>${st}${link}${err}</span></div>`;
    }).join('');
  }

  // ---------- TEMPLATE CSV ----------
  $('#download-tmpl').addEventListener('click', (e) => {
    e.preventDefault();
    const csv = 'no,nama\n081234567890,Budi Santoso\n082198765432,Siti Aminah\n';
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'template-undangan.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  });

  // ---------- UCAPAN ----------
  async function loadUcapan() {
    const res = await fetch('/api/admin/komentar');
    if (!res.ok) return;
    const data = await res.json();
    $('#ucapan-count').textContent = data.length;
    const box = $('#ucapan-list');
    box.innerHTML = data.map((u) => `
      <div class="uc">
        <div class="uh">
          <strong>${esc(u.nama)}</strong>
          <span style="display:flex;gap:8px;align-items:center">
            <span class="badge">${esc(u.kehadiran || 'Hadir')}</span>
            <button class="del" data-id="${u.id}">✕</button>
          </span>
        </div>
        <p>${esc(u.pesan)}</p>
        <div class="ud">${esc(u.created_at)}</div>
      </div>`).join('') || '<p class="hint">Belum ada ucapan.</p>';
    box.querySelectorAll('.del').forEach((b) => b.addEventListener('click', async () => {
      await api('/api/admin/komentar/' + b.dataset.id, { method: 'DELETE' });
      loadUcapan();
    }));
  }

  // ---------- WHATSAPP ----------
  let waTimer = null;
  async function refreshWaStatus() {
    try {
      const res = await fetch('/api/admin/wa/status');
      const s = await res.json();
      const badge = $('#wa-status');
      badge.textContent = s.status;
      badge.className = 'badge-status ' + s.status;
      const note = $('#wa-note');
      if (!s.waEnabled) note.textContent = 'WA_ENABLED=false — pengiriman memakai mode link manual.';
      else if (s.error) note.textContent = s.error;
      else note.textContent = '';

      const qwrap = $('#wa-qr-wrap');
      if (s.status === 'qr' && s.hasQr) {
        const q = await (await fetch('/api/admin/wa/qr')).json();
        qwrap.innerHTML = q.qr ? `<img src="${q.qr}" alt="QR WhatsApp"/><p class="hint">Scan QR ini dengan WhatsApp kamu</p>` : '';
      } else if (s.status === 'ready') {
        qwrap.innerHTML = `<p class="hint">✅ Terhubung${s.info && s.info.pushname ? ' sebagai <b>' + esc(s.info.pushname) + '</b>' : ''}. Siap mengirim undangan.</p>`;
      } else if (s.status === 'initializing') {
        qwrap.innerHTML = '<p class="hint">Menyiapkan koneksi... tunggu sebentar lalu QR akan muncul.</p>';
      } else {
        qwrap.innerHTML = '';
      }
    } catch (e) { /* ignore */ }
  }
  function startWaPolling() {
    refreshWaStatus();
    if (!waTimer) waTimer = setInterval(refreshWaStatus, 3000);
  }
  function stopWaPolling() {
    if (waTimer) { clearInterval(waTimer); waTimer = null; }
  }
  $('#wa-connect').addEventListener('click', async () => {
    await api('/api/admin/wa/init', { method: 'POST' });
    startWaPolling();
  });
  $('#wa-logout').addEventListener('click', async () => {
    await api('/api/admin/wa/logout', { method: 'POST' });
    refreshWaStatus();
  });

  checkAuth();
})();
