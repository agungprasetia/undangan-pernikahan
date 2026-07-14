const QRCode = require('qrcode');

let client = null;
let state = {
  enabled: false,
  status: 'off',
  qr: null,
  info: null,
  error: null,
};

function normalizeNumber(raw) {
  let n = String(raw || '').replace(/[^0-9]/g, '');
  if (!n) return null;
  if (n.startsWith('0')) n = '62' + n.slice(1);
  if (n.startsWith('620')) n = '62' + n.slice(3);
  return n;
}

function waId(raw) {
  const n = normalizeNumber(raw);
  return n ? `${n}@c.us` : null;
}

async function init() {
  if (client) return;
  state.enabled = true;
  state.status = 'initializing';

  const { Client, LocalAuth } = require('whatsapp-web.js');

  client = new Client({
    authStrategy: new LocalAuth({ dataPath: '.wwebjs_auth' }),
    puppeteer: {
      headless: true,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--single-process',
      ],
    },
  });

  client.on('qr', async (qr) => {
    try {
      state.qr = await QRCode.toDataURL(qr, { width: 320, margin: 1 });
    } catch {
      state.qr = null;
    }
    state.status = 'qr';
  });

  client.on('authenticated', () => {
    state.status = 'authenticated';
    state.qr = null;
  });

  client.on('ready', () => {
    state.status = 'ready';
    state.qr = null;
    state.info = client.info
      ? { pushname: client.info.pushname, wid: client.info.wid?.user }
      : null;
  });

  client.on('auth_failure', (msg) => {
    state.status = 'error';
    state.error = 'Auth gagal: ' + msg;
  });

  client.on('disconnected', (reason) => {
    state.status = 'disconnected';
    state.error = 'Terputus: ' + reason;
    client = null;
  });

  try {
    await client.initialize();
  } catch (e) {
    state.status = 'error';
    state.error = e.message;
    client = null;
  }
}

function getStatus() {
  return {
    enabled: state.enabled,
    status: state.status,
    hasQr: !!state.qr,
    info: state.info,
    error: state.error,
  };
}

function getQr() {
  return state.qr;
}

async function logout() {
  if (client) {
    try { await client.logout(); } catch { /* ignore */ }
    try { await client.destroy(); } catch { /* ignore */ }
  }
  client = null;
  state.status = 'off';
  state.qr = null;
  state.info = null;
}

async function sendMessage(number, text) {
  if (!client || state.status !== 'ready') {
    throw new Error('WhatsApp belum terhubung (status: ' + state.status + ')');
  }
  const id = waId(number);
  if (!id) throw new Error('Nomor tidak valid: ' + number);
  await client.sendMessage(id, text);
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

module.exports = {
  init,
  getStatus,
  getQr,
  logout,
  sendMessage,
  normalizeNumber,
  sleep,
};
