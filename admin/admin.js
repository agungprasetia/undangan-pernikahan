(function () {
  'use strict';
  const $ = (s) => document.querySelector(s);
  const login = $('#login');
  const dash = $('#dash');

  const API = {
    me: '/api/admin/me.php',
    login: '/api/admin/login.php',
    logout: '/api/admin/logout.php',
    undangan: '/api/admin/undangan.php',
    upload: '/api/admin/upload.php',
    kirim: '/api/admin/kirim.php',
    komentar: '/api/admin/komentar.php',
    instagram: '/api/admin/instagram.php',
    waStatus: '/api/admin/wa/status.php',
    waQr: '/api/admin/wa/qr.php',
    waInit: '/api/admin/wa/init.php',
    waLogout: '/api/admin/wa/logout.php',
  };

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  async function api(url, opts = {}) {
    const headers = { ...(opts.headers || {}) };
    if (opts.body && !(opts.body instanceof FormData) && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    return fetch(url, { credentials: 'same-origin', ...opts, headers });
  }

  async function readJsonSafe(res) {
    const raw = await res.text();
    try { return raw ? JSON.parse(raw) : {}; }
    catch { return { ok: false, error: 'Server: ' + raw.slice(0, 160) }; }
  }

  async function copyText(text) {
    try {
      await navigator.clipboard.writeText(text);
      return true;
    } catch {
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      try { document.execCommand('copy'); return true; }
      catch { return false; }
      finally { document.body.removeChild(ta); }
    }
  }

  // ---------- AUTH ----------
  async function checkAuth() {
    try {
      const res = await api(API.me);
      const data = await readJsonSafe(res);
      if (data.ok) showDash();
    } catch (e) { /* ignore */ }
  }

  $('#login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const err = $('#login-err');
    err.textContent = 'Memproses...';
    try {
      const res = await api(API.login, {
        method: 'POST',
        body: JSON.stringify({ password: $('#login-pass').value }),
      });
      const data = await readJsonSafe(res);
      if (res.ok && data.ok) {
        err.textContent = '';
        showDash();
      } else {
        err.textContent = data.error || ('Gagal login (HTTP ' + res.status + ')');
      }
    } catch (ex) {
      err.textContent = 'Gagal koneksi: ' + ex.message;
    }
  });

  $('#logout').addEventListener('click', async () => {
    await api(API.logout, { method: 'POST' });
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
    const res = await api(API.undangan);
    if (!res.ok) return;
    const data = await readJsonSafe(res);
    $('#stat-total').textContent = data.total || 0;
    $('#stat-terkirim').textContent = data.terkirim || 0;
    $('#stat-ig').textContent = (data.ig_done || 0) + '/' + (data.ig_total || 0);

    const tb = $('#tbl-undangan tbody');
    const rows = data.rows || [];
    tb.innerHTML = rows.map((r, i) => {
      const ig = r.instagram
        ? `<a href="${esc(r.ig_profile)}" target="_blank" rel="noopener">@${esc(r.instagram)}</a>`
        : '<span style="color:#8b93a1">—</span>';
      const waStatus = r.no
        ? (r.sent ? '<span class="tag sent">Terkirim</span>' : '<span class="tag unsent">Belum</span>')
        : '<span style="color:#8b93a1">—</span>';
      const igStatus = r.instagram
        ? (r.sent_ig ? '<span class="tag sent">Selesai</span>' : '<span class="tag unsent">Belum</span>')
        : '<span style="color:#8b93a1">—</span>';
      const igBtns = r.instagram
        ? `<button class="btn-mini ig" data-copy="${r.id}">Salin</button>
           <a class="btn-mini ig" href="${esc(r.ig_dm)}" target="_blank" rel="noopener">DM</a>
           <button class="btn-mini ok" data-done-ig="${r.id}">✓</button>`
        : '';
      return `<tr>
        <td>${i + 1}</td>
        <td>${esc(r.nama)}</td>
        <td>${r.no ? esc(r.no) : '—'}</td>
        <td>${ig}</td>
        <td>${waStatus}</td>
        <td>${igStatus}</td>
        <td>
          ${igBtns}
          <button class="del" data-id="${r.id}" title="Hapus">✕</button>
        </td>
      </tr>`;
    }).join('') || '<tr><td colspan="7" style="text-align:center;color:#8b93a1">Belum ada data.</td></tr>';

    // simpan pesan IG di memory untuk tombol Salin
    window.__igPesan = {};
    rows.forEach((r) => {
      if (r.instagram && r.ig_pesan) window.__igPesan[r.id] = r.ig_pesan;
    });

    tb.querySelectorAll('.del').forEach((b) => b.addEventListener('click', async () => {
      await api(API.undangan + '?id=' + b.dataset.id, { method: 'DELETE' });
      loadUndangan();
    }));

    tb.querySelectorAll('[data-copy]').forEach((b) => b.addEventListener('click', async () => {
      const text = window.__igPesan[b.dataset.copy] || '';
      const ok = await copyText(text);
      b.textContent = ok ? 'Tersalin' : 'Gagal';
      setTimeout(() => { b.textContent = 'Salin'; }, 1200);
    }));

    tb.querySelectorAll('[data-done-ig]').forEach((b) => b.addEventListener('click', async () => {
      await api(API.instagram, {
        method: 'POST',
        body: JSON.stringify({ id: Number(b.dataset.doneIg) }),
      });
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
    const res = await api(API.upload, { method: 'POST', body: fd });
    const data = await readJsonSafe(res);
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
    const res = await api(API.undangan, {
      method: 'POST',
      body: JSON.stringify({
        nama: $('#add-nama').value,
        no: $('#add-no').value,
        instagram: $('#add-ig').value,
      }),
    });
    const data = await readJsonSafe(res);
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
    await api(API.undangan, { method: 'DELETE' });
    loadUndangan();
  });

  $('#btn-kirim').addEventListener('click', async () => {
    const onlyUnsent = $('#only-unsent').checked;
    if (!confirm('Kirim undangan WhatsApp sekarang? (hanya tamu yang punya nomor WA)')) return;
    const btn = $('#btn-kirim');
    btn.disabled = true; btn.textContent = 'Mengirim...';
    try {
      const res = await api(API.kirim, {
        method: 'POST',
        body: JSON.stringify({ onlyUnsent }),
      });
      const data = await readJsonSafe(res);
      renderSendResult(data);
      loadUndangan();
    } catch (e) {
      alert('Gagal: ' + e.message);
    } finally {
      btn.disabled = false; btn.textContent = '📤 Kirim WhatsApp';
    }
  });

  $('#btn-ig').addEventListener('click', async () => {
    const onlyUnsent = $('#only-unsent').checked;
    const res = await api(API.instagram + '?onlyUnsent=' + (onlyUnsent ? '1' : '0'));
    const data = await readJsonSafe(res);
    renderIgResult(data);
  });

  function renderSendResult(data) {
    const card = $('#send-result-card');
    const box = $('#send-result');
    card.style.display = 'block';
    let head = '';
    if (data.mode === 'link') {
      head = `<p class="hint">WhatsApp belum terhubung — tersedia link wa.me manual.</p>`;
    } else {
      head = `<p class="hint">${data.dikirim || 0} dari ${data.total || 0} pesan terkirim otomatis.</p>`;
    }
    const results = data.results || [];
    box.innerHTML = head + results.map((r) => {
      const st = `<span class="st-${r.status}">${r.status}</span>`;
      const link = r.link ? ` <a href="${esc(r.link)}" target="_blank">buka wa.me →</a>` : '';
      const err = r.error ? ` <span class="st-gagal">(${esc(r.error)})</span>` : '';
      return `<div class="r"><span>${esc(r.nama)} · ${esc(r.no)}</span><span>${st}${link}${err}</span></div>`;
    }).join('');
  }

  function renderIgResult(data) {
    const card = $('#ig-result-card');
    const box = $('#ig-result');
    card.style.display = 'block';
    const results = data.results || [];
    if (!results.length) {
      box.innerHTML = '<p class="hint">Tidak ada tamu Instagram yang perlu diproses.</p>';
      return;
    }
    box.innerHTML = results.map((r) => `
      <div class="ig-row" data-igid="${r.id}">
        <div class="ig-row-top">
          <strong>${esc(r.nama)} · @${esc(r.instagram)}</strong>
          <div class="ig-actions">
            <button class="btn-mini ig" data-ig-copy="${r.id}">Salin pesan</button>
            <a class="btn-mini ig" href="${esc(r.dm)}" target="_blank" rel="noopener">Buka DM</a>
            <a class="btn-mini" href="${esc(r.profile)}" target="_blank" rel="noopener">Profil</a>
            <button class="btn-mini ok" data-ig-done="${r.id}">Tandai selesai</button>
          </div>
        </div>
        <pre class="ig-pesan">${esc(r.pesan)}</pre>
      </div>
    `).join('');

    window.__igBatchPesan = {};
    results.forEach((r) => { window.__igBatchPesan[r.id] = r.pesan; });

    box.querySelectorAll('[data-ig-copy]').forEach((b) => b.addEventListener('click', async () => {
      const ok = await copyText(window.__igBatchPesan[b.dataset.igCopy] || '');
      b.textContent = ok ? 'Tersalin ✓' : 'Gagal';
      setTimeout(() => { b.textContent = 'Salin pesan'; }, 1200);
    }));

    box.querySelectorAll('[data-ig-done]').forEach((b) => b.addEventListener('click', async () => {
      await api(API.instagram, {
        method: 'POST',
        body: JSON.stringify({ id: Number(b.dataset.igDone) }),
      });
      const row = box.querySelector(`[data-igid="${b.dataset.igDone}"]`);
      if (row) row.remove();
      loadUndangan();
    }));
  }

  // ---------- TEMPLATE CSV ----------
  $('#download-tmpl').addEventListener('click', (e) => {
    e.preventDefault();
    const csv = 'nama,no,instagram\nBudi Santoso,081234567890,\nSiti Aminah,,siti.aminah\nAgung Prasetia,0895326643940,agungprasetia\n';
    const blob = new Blob([csv], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'template-undangan.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  });

  // ---------- UCAPAN ----------
  async function loadUcapan() {
    const res = await api(API.komentar);
    if (!res.ok) return;
    const data = await readJsonSafe(res);
    const list = Array.isArray(data) ? data : [];
    $('#ucapan-count').textContent = list.length;
    const box = $('#ucapan-list');
    box.innerHTML = list.map((u) => `
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
      await api(API.komentar + '?id=' + b.dataset.id, { method: 'DELETE' });
      loadUcapan();
    }));
  }

  // ---------- WHATSAPP ----------
  let waTimer = null;
  async function refreshWaStatus() {
    try {
      const res = await api(API.waStatus);
      const s = await readJsonSafe(res);
      const badge = $('#wa-status');
      badge.textContent = s.status || '-';
      badge.className = 'badge-status ' + (s.status || '');
      const note = $('#wa-note');
      if (!s.waEnabled) note.textContent = 'wa_enabled=false — pengiriman memakai mode link manual.';
      else if (s.error) note.textContent = s.error;
      else note.textContent = '';

      const qwrap = $('#wa-qr-wrap');
      if (s.status === 'qr' && s.hasQr) {
        const q = await readJsonSafe(await api(API.waQr));
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
    await api(API.waInit, { method: 'POST', body: '{}' });
    startWaPolling();
  });
  $('#wa-logout').addEventListener('click', async () => {
    await api(API.waLogout, { method: 'POST', body: '{}' });
    refreshWaStatus();
  });

  checkAuth();
})();
