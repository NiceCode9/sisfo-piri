# WA Gateway — Sisfo Piri

Service Node `whatsapp-web.js` colocated dengan backend Laravel di VPS yang sama (`127.0.0.1:3001`). Backend antrikan via `app/Jobs/KirimNotifikasiWhatsapp.php`.

## Jalan dev (Windows)

```powershell
cd "D:\Web Personal\sisfo-piri\wa-gateway"
copy .env.example .env
# isi GATEWAY_TOKEN dengan random 32 char
npm install
npm run dev
```

Buka `http://127.0.0.1:3001/qr` (PNG, tanpa token — `GET /qr` & `/status` publik) atau set `SHOW_QR_TERMINAL=1` untuk tampil di terminal. `GET /status` → `{"siap":true}` bila terhubung.

## Test tanpa WA

```powershell
# POST /kirim wajib Bearer bila GATEWAY_TOKEN terisi:
curl -H "Authorization: Bearer isi-token" -H "Content-Type: application/json" -d "{\"tujuan\":\"081200000001\",\"pesan\":\"Tes gateway\"}" http://127.0.0.1:3001/kirim
# Bila GATEWAY_TOKEN kosong (LAN only) tanpa header juga bisa:
curl -H "Content-Type: application/json" -d "{\"tujuan\":\"0812...\",\"pesan\":\"...\"}" http://127.0.0.1:3001/kirim
# GET /qr tanpa token (baru):
curl http://127.0.0.1:3001/qr -o qr.png
```

## Produksi (VPS Ubuntu)

```bash
cd /path/wa-gateway
npm ci --production
# .env: PORT=3001 HOST=127.0.0.1 GATEWAY_TOKEN=... (isi)
pm2 start ecosystem.config.js --env production
pm2 save
pm2 startup
```

Jangan expose 3001 via Nginx. Backend `WHATSAPP_URL=http://127.0.0.1:3001/kirim` + `WHATSAPP_TOKEN` sama dengan `GATEWAY_TOKEN`.

## Catatan

- Sesi di `.wwebjs_auth/` — backup folder ini bila migrasi VPS, agar tidak scan ulang.
- Antrean delay 3 detik antar kirim (anti-banned) di `src/queue.js`.
- Log backend di `notifikasi_logs` (`terkirim/gagal/antri`).
