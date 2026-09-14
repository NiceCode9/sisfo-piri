require('dotenv').config();

const express = require('express');
const cors = require('cors');
const QRCode = require('qrcode');
const { getClient, getStatus } = require('./client');
const { enqueue } = require('./queue');

const app = express();
const PORT = parseInt(process.env.PORT || '3001', 10);
const HOST = process.env.HOST || '127.0.0.1';
const TOKEN = process.env.GATEWAY_TOKEN || '';

app.use(cors());
app.use(express.json());

// Bearer check bila token diset (VPS). Kosong = LAN only tanpa auth (dev).
// GET /, /status, /qr publik (scan via browser tanpa header); hanya POST /kirim yang wajib token.
const PUBLIC_GET = ['/', '/status', '/qr'];
app.use((req, res, next) => {
    if (!TOKEN) {
        return next();
    }

    if (req.method === 'GET' && PUBLIC_GET.includes(req.path)) {
        return next();
    }

    const auth = req.headers.authorization || '';

    if (auth !== `Bearer ${TOKEN}`) {
        return res.status(401).json({ ok: false, error: 'Unauthorized' });
    }

    return next();
});

app.get('/status', (req, res) => {
    const s = getStatus();
    const client = getStatus().siap ? (() => { try { return getClient().info?.wid?._serialized || null; } catch { return null; } })() : null;
    res.json({ ok: true, siap: s.siap, qrTersedia: s.qrTersedia, nomor: client });
});

app.get('/qr', async (req, res) => {
    const s = getStatus();

    if (!s.qr) {
        return res.status(404).send(s.siap ? 'Sudah terhubung, tidak perlu QR' : 'QR belum tersedia, tunggu sebentar');
    }

    try {
        const png = await QRCode.toBuffer(s.qr, { width: 320 });
        res.type('png').send(png);
    } catch (e) {
        res.status(500).json({ ok: false, error: e.message });
    }
});

app.post('/logout', async (req, res) => {
    try {
        const c = getClient();
        await c.logout();
        res.json({ ok: true });
    } catch (e) {
        res.status(500).json({ ok: false, error: e.message });
    }
});

app.post('/kirim', async (req, res) => {
    const { tujuan, pesan } = req.body || {};

    if (!tujuan || !pesan) {
        return res.status(422).json({ ok: false, error: 'tujuan dan pesan wajib diisi' });
    }

    try {
        await enqueue(tujuan, pesan);
        res.json({ ok: true });
    } catch (e) {
        res.status(500).json({ ok: false, error: e.message });
    }
});

app.get('/', (req, res) => {
    res.json({ ok: true, service: 'wa-gateway', status: getStatus().siap ? 'siap' : 'menunggu-qr', docs: 'GET /status, GET /qr, POST /kirim, POST /logout' });
});

getClient();

app.listen(PORT, HOST, () => {
    console.log(`[WA] Gateway jalan di http://${HOST}:${PORT} — ${TOKEN ? 'Bearer ON' : 'Bearer OFF (LAN only)'}`);
});
