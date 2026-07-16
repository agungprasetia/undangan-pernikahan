const QRCode = require('qrcode');
const fs = require('fs');
const path = require('path');

let client = null;
let state = {
  enabled: false,
  status: 'off',
  qr: null,
  info: null,
  error: null,
};

const AUTH_PATH = path.join(__dirname, '.wwebjs_auth');
const CACHE_PATH = path.join(__dirname, '.wwebjs_cache');

function normalizeNumber(raw) {
  let s = String(raw || '').trim();
  if (/^\d+(\.\d+)?[eE][+\-]?\d+$/.test(s)) {
    s = Number(s).toFixed(0);
  }

  let n = s.replace(/[^0-9]/g, '');
  if (!n) return null;

  if (n.startsWith('0')) n = '62' + n.slice(1);
  if (n.startsWith('620')) n = '62' + n.slice(3);
  if (!n.startsWith('62') && n.startsWith('8')) n = '62' + n;

  return n;
}

function waId(raw) {
  const n = normalizeNumber(raw);
  return n ? `${n}@c.us` : null;
}

function rmDirSafe(dir) {
  try {
    if (fs.existsSync(dir)) {
      fs.rmSync(dir, { recursive: true, force: true });
    }
  } catch (e) {
    console.warn('Gagal hapus', dir, e.message);
  }
}

async function destroyClient() {
  if (!client) return;
  try { await client.destroy(); } catch { /* ignore */ }
  client = null;
}

async function init(options = {}) {
  const force = !!options.force;

  if (client && !force) {
    return getStatus();
  }

  await destroyClient();

  state.enabled = true;
  state.status = 'initializing';
  state.qr = null;
  state.info = null;
  state.error = null;

  if (force) {
    rmDirSafe(AUTH_PATH);
    rmDirSafe(CACHE_PATH);
  }

  const { Client, LocalAuth } = require('whatsapp-web.js');

  const executablePath = process.env.PUPPETEER_EXECUTABLE_PATH || undefined;
  if (executablePath) {
    console.log('Pakai Chromium:', executablePath);
  }

  // Catatan: JANGAN pakai --single-process (sering bikin "Navigating frame was detached")
  client = new Client({
    authStrategy: new LocalAuth({ dataPath: AUTH_PATH }),
    puppeteer: {
      headless: true,
      executablePath,
      args: [
        '--no-sandbox',
        '--disable-setuid-sandbox',
        '--disable-dev-shm-usage',
        '--disable-gpu',
        '--disable-extensions',
        '--disable-software-rasterizer',
        '--no-zygote',
        '--window-size=1280,720',
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
    state.error = null;
    console.log('QR siap di-scan');
  });

  client.on('authenticated', () => {
    state.status = 'authenticated';
    state.qr = null;
    state.error = null;
    console.log('Authenticated');
  });

  client.on('ready', () => {
    state.status = 'ready';
    state.qr = null;
    state.error = null;
    state.info = client.info
      ? { pushname: client.info.pushname, wid: client.info.wid?.user }
      : null;
    console.log('WhatsApp ready', state.info);
  });

  client.on('auth_failure', (msg) => {
    state.status = 'error';
    state.error = 'Auth gagal: ' + msg;
    console.error('Auth failure', msg);
  });

  client.on('disconnected', (reason) => {
    state.status = 'disconnected';
    state.error = 'Terputus: ' + reason;
    client = null;
    console.warn('Disconnected', reason);
  });

  try {
    await client.initialize();
  } catch (e) {
    state.status = 'error';
    state.error = e.message;
    console.error('Initialize error:', e.message);
    await destroyClient();
  }

  return getStatus();
}

async function reset() {
  await destroyClient();
  rmDirSafe(AUTH_PATH);
  rmDirSafe(CACHE_PATH);
  state = {
    enabled: false,
    status: 'off',
    qr: null,
    info: null,
    error: null,
  };
  return init({ force: false });
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
  }
  await destroyClient();
  rmDirSafe(AUTH_PATH);
  rmDirSafe(CACHE_PATH);
  state.status = 'off';
  state.qr = null;
  state.info = null;
  state.error = null;
  state.enabled = false;
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
  reset,
  getStatus,
  getQr,
  logout,
  sendMessage,
  normalizeNumber,
  sleep,
};
