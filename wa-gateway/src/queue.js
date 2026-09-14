/**
 * Antrean FIFO sederhana dengan delay 3 detik antar kirim (anti-banned).
 */

const DELAY_MS = 3000;
const antrean = [];
let memproses = false;

function normalisasiTujuan(tujuan) {
    const angka = String(tujuan).replace(/\D/g, '');

    if (!angka) {
        return null;
    }

    let dinormal = angka;

    if (dinormal.startsWith('0')) {
        dinormal = '62' + dinormal.slice(1);
    }

    if (!dinormal.startsWith('62')) {
        dinormal = '62' + dinormal;
    }

    return dinormal + '@c.us';
}

function enqueue(tujuan, pesan) {
    return new Promise((resolve, reject) => {
        antrean.push({ tujuan, pesan, resolve, reject });
        proses();
    });
}

async function proses() {
    if (memproses || antrean.length === 0) {
        return;
    }

    memproses = true;
    const { tujuan, pesan, resolve, reject } = antrean.shift();
    const client = require('./client').getClient();

    try {
        const chatId = normalisasiTujuan(tujuan);

        if (!chatId) {
            throw new Error('Nomor tujuan tidak valid');
        }

        const hasil = await client.sendMessage(chatId, pesan);
        resolve(hasil);
    } catch (e) {
        reject(e);
    }

    setTimeout(() => {
        memproses = false;
        proses();
    }, DELAY_MS);
}

module.exports = { enqueue, normalisasiTujuan };
