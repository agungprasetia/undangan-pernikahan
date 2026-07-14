require('dotenv').config();
const express = require('express');
const wa = require('./whatsapp');

const app = express();
app.use(express.json({ limit: '2mb' }));

const PORT = parseInt(process.env.PORT || '8080', 10);
const API_KEY = process.env.WA_API_KEY || '';
const SEND_DELAY = parseInt(process.env.SEND_DELAY_MS || '2500', 10);

function requireApiKey(req, res, next) {
  const auth = req.headers.authorization || '';
  const token = auth.startsWith('Bearer ') ? auth.slice(7) : '';
  if (!API_KEY || token !== API_KEY) {
    return res.status(401).json({ ok: false, error: 'Unauthorized' });
  }
  next();
}

app.get('/health', (_req, res) => {
  res.json({ ok: true, service: 'wa-service' });
});

app.get('/status', requireApiKey, (_req, res) => {
  res.json({ ok: true, ...wa.getStatus() });
});

app.get('/qr', requireApiKey, (_req, res) => {
  res.json({ ok: true, qr: wa.getQr() });
});

app.post('/init', requireApiKey, (_req, res) => {
  wa.init().catch(() => {});
  res.json({ ok: true });
});

app.post('/logout', requireApiKey, async (_req, res) => {
  await wa.logout();
  res.json({ ok: true });
});

// Kirim satu pesan
app.post('/send', requireApiKey, async (req, res) => {
  const { number, text } = req.body || {};
  if (!number || !text) {
    return res.status(400).json({ ok: false, error: 'number & text wajib' });
  }
  try {
    await wa.sendMessage(number, text);
    res.json({ ok: true, status: 'terkirim' });
  } catch (e) {
    res.status(500).json({ ok: false, error: e.message, status: 'gagal' });
  }
});

// Kirim batch berurutan — 1 klik dari admin PHP
app.post('/send-batch', requireApiKey, async (req, res) => {
  const messages = req.body?.messages;
  if (!Array.isArray(messages) || messages.length === 0) {
    return res.status(400).json({ ok: false, error: 'messages array wajib' });
  }

  const status = wa.getStatus();
  if (status.status !== 'ready') {
    return res.status(503).json({
      ok: false,
      error: 'WhatsApp belum ready (status: ' + status.status + ')',
    });
  }

  const results = [];
  for (let i = 0; i < messages.length; i++) {
    const m = messages[i];
    const number = m.number || m.no;
    const text = m.text || m.pesan;
    const id = m.id;
    const nama = m.nama || '';

    try {
      await wa.sendMessage(number, text);
      results.push({ id, nama, number, text, status: 'terkirim' });
    } catch (e) {
      results.push({
        id,
        nama,
        number,
        text,
        status: 'gagal',
        error: e.message,
      });
    }

    if (i < messages.length - 1) {
      await wa.sleep(SEND_DELAY);
    }
  }

  res.json({
    ok: true,
    dikirim: results.filter((r) => r.status === 'terkirim').length,
    total: results.length,
    results,
  });
});

app.listen(PORT, () => {
  console.log('WA Service running on port', PORT);
});
