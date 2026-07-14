(function () {
  'use strict';

  const guestName = (window.GUEST_NAME || '').trim() || 'Tamu Undangan';

  const cover = document.getElementById('cover');
  const main = document.getElementById('main');
  const openBtn = document.getElementById('open-btn');
  const music = document.getElementById('bg-music');
  const musicToggle = document.getElementById('music-toggle');

  // Preload musik agar siap diputar saat undangan dibuka
  music.load();

  // Animasi teks cover setelah load
  requestAnimationFrame(() => cover.classList.add('ready'));

  // ---------- Buka undangan ----------
  openBtn.addEventListener('click', () => {
    // Putar musik segera (masih dalam interaksi user → autoplay diizinkan)
    musicToggle.hidden = false;
    music.volume = 0.6;
    music.play().then(() => {
      musicToggle.classList.remove('paused');
    }).catch(() => {
      musicToggle.classList.add('paused');
    });

    document.body.classList.remove('locked');
    cover.classList.add('open');
    main.classList.add('show');
    main.setAttribute('aria-hidden', 'false');

    setTimeout(() => { cover.style.display = 'none'; }, 1200);
    window.scrollTo({ top: 0, behavior: 'auto' });
    setTimeout(onScroll, 50);
  });

  // ---------- Musik (tombol toggle) ----------
  musicToggle.addEventListener('click', () => {
    if (music.paused) {
      music.play().then(() => musicToggle.classList.remove('paused')).catch(() => {});
    } else {
      music.pause();
      musicToggle.classList.add('paused');
    }
  });

  // ---------- Reveal saat scroll ----------
  const io = new IntersectionObserver((entries) => {
    entries.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        io.unobserve(e.target);
      }
    });
  }, { threshold: 0.15 });
  document.querySelectorAll('.reveal').forEach((el) => io.observe(el));

  // ---------- Parallax halus ----------
  const parallaxEls = Array.from(document.querySelectorAll('.parallax, .gp-item, .cover-bg, .closing-bg'));
  let ticking = false;
  function onScroll() {
    parallaxEls.forEach((el) => {
      const r = el.getBoundingClientRect();
      if (r.bottom < -200 || r.top > window.innerHeight + 200) return;
      const speed = el.classList.contains('cover-bg') ? 0.18 : 0.28;
      const offset = (r.top + r.height / 2) - window.innerHeight / 2;
      el.style.backgroundPositionY = `calc(50% + ${(-offset * speed).toFixed(1)}px)`;
    });
    ticking = false;
  }
  window.addEventListener('scroll', () => {
    if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
  }, { passive: true });
  window.addEventListener('resize', onScroll);
  onScroll();

  // ---------- Countdown ----------
  const cd = document.getElementById('countdown');
  if (cd) {
    const target = new Date(cd.dataset.date).getTime();
    const set = (k, v) => {
      const el = cd.querySelector(`[data-cd="${k}"]`);
      if (el) el.textContent = String(v).padStart(2, '0');
    };
    const tick = () => {
      let diff = Math.max(0, target - Date.now());
      const d = Math.floor(diff / 86400000); diff -= d * 86400000;
      const h = Math.floor(diff / 3600000); diff -= h * 3600000;
      const m = Math.floor(diff / 60000); diff -= m * 60000;
      const s = Math.floor(diff / 1000);
      set('days', d); set('hours', h); set('minutes', m); set('seconds', s);
    };
    tick();
    setInterval(tick, 1000);
  }

  // ---------- Ucapan / Komentar ----------
  const wishForm = document.getElementById('wish-form');
  const wishStatus = document.getElementById('wish-status');
  const wishList = document.getElementById('wish-list');

  function esc(str) {
    return String(str).replace(/[&<>"']/g, (c) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
  }

  async function loadWishes() {
    try {
      const res = await fetch('/api/komentar');
      const data = await res.json();
      if (!data.length) {
        wishList.innerHTML = '<p style="text-align:center;color:#8c8172;font-family:Poppins,sans-serif;font-size:.8rem">Belum ada ucapan. Jadilah yang pertama!</p>';
        return;
      }
      wishList.innerHTML = data.map((w) => `
        <div class="wish-item">
          <div class="wh"><strong>${esc(w.nama)}</strong><span class="badge">${esc(w.kehadiran || 'Hadir')}</span></div>
          <p>${esc(w.pesan)}</p>
          <div class="wd">${esc(w.created_at || '')}</div>
        </div>`).join('');
    } catch (e) {
      wishList.innerHTML = '';
    }
  }

  if (guestName && guestName !== 'Tamu Undangan' && wishForm) {
    wishForm.querySelector('input[name="nama"]').value = guestName;
  }

  wishForm.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const fd = new FormData(wishForm);
    const body = {
      nama: fd.get('nama'),
      pesan: fd.get('pesan'),
      kehadiran: fd.get('kehadiran'),
    };
    wishStatus.textContent = 'Mengirim...';
    wishStatus.className = 'wish-status';
    try {
      const res = await fetch('/api/komentar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body),
      });
      const data = await res.json();
      if (data.ok) {
        wishStatus.textContent = 'Terima kasih atas ucapannya! 💐';
        wishStatus.className = 'wish-status ok';
        wishForm.querySelector('textarea').value = '';
        loadWishes();
      } else {
        throw new Error(data.error || 'Gagal');
      }
    } catch (e) {
      wishStatus.textContent = 'Gagal mengirim: ' + e.message;
      wishStatus.className = 'wish-status err';
    }
  });

  loadWishes();
})();
