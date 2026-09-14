const { Client, LocalAuth } = require('whatsapp-web.js');

let qrTerakhir = null;
let siap = false;

function buatClient() {
    const client = new Client({
        authStrategy: new LocalAuth({
            dataPath: process.env.WA_AUTH_DIR || '.wwebjs_auth',
        }),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
        },
    });

    client.on('qr', (qr) => {
        qrTerakhir = qr;
        siap = false;
        console.log('[WA] Scan QR: buka http://127.0.0.1:3001/qr (set SHOW_QR_TERMINAL=1 untuk tampil di terminal)');
        if (process.env.SHOW_QR_TERMINAL === '1') {
            require('qrcode-terminal').generate(qr, { small: true });
        }
    });

    client.on('ready', () => {
        qrTerakhir = null;
        siap = true;
        console.log('[WA] Client siap');
    });

    client.on('authenticated', () => {
        console.log('[WA] Terautentikasi');
    });

    client.on('auth_failure', (msg) => {
        siap = false;
        console.error('[WA] Auth failure:', msg);
    });

    client.on('disconnected', (reason) => {
        siap = false;
        console.warn('[WA] Terputus:', reason, '— mencoba reconnect...');
        setTimeout(() => {
            client.initialize().catch((e) => console.error('[WA] Reinit gagal:', e.message));
        }, 5000);
    });

    client.initialize().catch((e) => {
        console.error('[WA] Initialize gagal:', e.message);
    });

    return client;
}

function getClient() {
    if (!global._waClient) {
        global._waClient = buatClient();
    }

    return global._waClient;
}

function getStatus() {
    return { siap, qrTersedia: Boolean(qrTerakhir), qr: qrTerakhir };
}

module.exports = { getClient, getStatus };
