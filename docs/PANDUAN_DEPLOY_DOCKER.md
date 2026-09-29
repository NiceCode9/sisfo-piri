# Panduan Deploy Docker Penuh — Sisfo Piri (Branch `eksperimen/docker-deploy`)

> Alternatif dari `docs/PANDUAN_DEPLOY_VPS.md` (install langsung). Di sini **nginx, MySQL, Redis**
> ikut di-container; PHP-FPM, queue-worker, scheduler, dan WA Gateway juga container.
> CBT React punya **2 varian** (lihat §1) — pilih satu.
>
> Branch ini untuk **uji coba** — jangan merge ke `main` sebelum keduanya terverifikasi.

## 0. Gambaran Service

| Service | Image | Peran |
|---|---|---|
| `mysql` | `mysql:8` | DB `sisfo_piri` (volume `mysql_data`) |
| `redis` | `redis:7-alpine` | Cache 6h soal CBT + queue + throttle heartbeat (tanpa publish port) |
| `backend` | build `docker/backend/Dockerfile` | PHP-FPM Laravel (`migrate --force` otomatis via entrypoint) |
| `nginx` | `nginx:alpine` | Reverse-proxy `:80/:443` → `backend:9000`, static `/build` + `/storage` |
| `queue-worker` | build sama backend | `queue:work redis --sleep=3 --tries=3` (heartbeat, violation, WA) |
| `scheduler` | build sama backend | Loop `schedule:run` tiap 60s → `absensi:cek-belum-hadir` |
| `wa-gateway` | build `docker/wa-gateway/Dockerfile` | whatsapp-web.js `:3001` **internal saja** (volume `wa_auth` persist sesi) |

## 1. Pilih Varian CBT

### Varian A — CBT beda server (di server sekolah) [default]

- Compose yang dipakai: `docker-compose.yml` saja.
- Nginx hanya serve `admin.ncode.my.id`.
- Di **server sekolah**: clone repo (atau cukup folder `cbt/`), isi `cbt/.env`:
  `VITE_API_BASE_URL=https://admin.ncode.my.id/api/cbt`, lalu `npm install && npm run build`
  dan serve `cbt/dist` (Nginx static / `vite preview --host`).
- Firewall VPS: izinkan IP publik sekolah → `443` (API). Tidak ada yang perlu dibuka selain `80/443/22`.

```bash
docker compose up -d --build
```

### Varian B — CBT satu server

- Compose yang dipakai: basis + override.
- Nginx serve `admin.ncode.my.id` **dan** `cbt.ncode.my.id` (2 A record → IP VPS yang sama).
- `dist/` di-build **di VPS dari repo yang sama** (konsisten versi backend↔frontend).

```bash
cd cbt
VITE_API_BASE_URL=https://admin.ncode.my.id/api/cbt npm run build
cd ..
docker compose -f docker-compose.yml -f docker-compose.cbt.yml up -d --build
```

- SSL: `certbot --nginx -d admin.ncode.my.id -d cbt.ncode.my.id`
  (bila certbot di host; alternatif: jalankan certbot manual lalu mount `/etc/letsencrypt` ke service `nginx`).

## 2. Persiapan VPS (sekali saja)

```bash
sudo apt update && sudo apt install -y git docker.io docker-compose-plugin
sudo usermod -aG docker $USER  # lalu logout/login
git clone https://github.com/NiceCode9/sisfo-piri.git /opt/sisfo-piri
cd /opt/sisfo-piri
git checkout eksperimen/docker-deploy
```

## 3. Env (wajib sebelum `up`)

```bash
# Backend (dibaca service backend/queue-worker/scheduler via env_file)
cp .env.docker.example backend/.env
nano backend/.env
# WAJIB GANTI: APP_URL, APP_KEY (atau key:generate nanti), DB_PASSWORD,
#              WHATSAPP_TOKEN (32 char, SAMA dengan bawah).
# JANGAN UBAH: DB_HOST=mysql, REDIS_HOST=redis, WHATSAPP_URL=http://wa-gateway:3001/kirim

# Password DB untuk service mysql (harus SAMA dengan DB_PASSWORD di atas)
export DB_PASSWORD='isi-sama-dengan-backend-.env'
export DB_ROOT_PASSWORD='isi-beda-yang-kuat'

# WA Gateway (dibaca service wa-gateway via env_file)
cp wa-gateway/.env.example wa-gateway/.env
nano wa-gateway/.env
# WAJIB: GATEWAY_TOKEN=<sama dengan WHATSAPP_TOKEN>, PORT=3001, HOST=0.0.0.0
# (HOST 0.0.0.0 karena di dalam container; akses tetap internal karena port tak dipublish)
# Generate bila belum: openssl rand -base64 24
```

> `APP_KEY`: setelah `up`, jalankan sekali
> `docker compose exec backend php artisan key:generate` lalu `docker compose restart backend queue-worker scheduler`.

## 4. First Deploy

```bash
# Varian A:
docker compose up -d --build
# Varian B:
docker compose -f docker-compose.yml -f docker-compose.cbt.yml up -d --build

docker compose ps
docker compose logs -f backend  # tunggu "Menunggu MySQL..." selesai → migrate jalan

# Seed awal (database kosong):
docker compose exec backend php artisan migrate --force --seed
# (DemoCbtSeeder 2 kelas × 10 soal ikut via DatabaseSeeder.)
# Beban 500 HANYA staging: docker compose exec backend php artisan db:seed --class=LoadTestSeeder
```

Catatan entrypoint: setiap start, container `backend` otomatis `migrate --force` + `config/route/view:cache`
+ `queue:restart`. Untuk fresh total: `SEED_ON_BOOT=true docker compose up -d`
(menjalankan `migrate:fresh --seed` — **HATI-HATI, menghapus data!**).

## 5. Scan QR WhatsApp (sekali saja / bila sesi hilang)

Port 3001 tidak dipublish, jadi gunakan container sekali-jalan:

```bash
# Dari VPS (atau via SSH tunnel: ssh -L 3001:127.0.0.1:3001 user@vps lalu buka di laptop)
docker compose run --rm -p 127.0.0.1:3001:3001 wa-gateway
# Buka http://127.0.0.1:3001/qr → scan dengan HP → tunggu "Client siap"
# Ctrl+C setelah siap. Sesi tersimpan di volume `wa_auth`, service utama langsung pakai.
```

Verifikasi: `curl http://127.0.0.1:3001/status` harus `{"siap":true}`
(bila port tak dipublish, cek lewat backend: buka `admin/whatsapp` → status hijau).

## 6. Verifikasi Akhir

```bash
curl https://admin.ncode.my.id/up
docker compose exec backend php artisan test --filter=CbtApi   # 5 hijau
docker compose exec backend php artisan schedule:list          # absensi:cek-belum-hadir ada
docker compose logs queue-worker --tail 50                     # heartbeat tiap 20s saat ujian
# Varian B: curl https://cbt.ncode.my.id → HTML React
# Varian A: di server sekolah buka CBT → Login 0080011001 → Token DEMOMTK7A01
```

## 7. Operasional

```bash
# Update kode:
git pull
docker compose up -d --build backend queue-worker scheduler nginx
docker compose exec backend php artisan queue:restart
docker compose restart wa-gateway  # bila wa-gateway berubah

# Backup:
docker compose exec mysql mysqldump -u root -p"$DB_ROOT_PASSWORD" sisfo_piri | gzip > /backups/sisfo_$(date +%F).sql.gz
docker run --rm -v sisfo-piri_wa_auth:/a -v /backups:/b alpine tar czf /b/wa_auth_$(date +%F).tar.gz -C /a .

# Log:
docker compose logs -f backend queue-worker wa-gateway scheduler
```

## 8. Perbandingan vs Install Langsung

| Aspek | Docker (file ini) | Langsung (`PANDUAN_DEPLOY_VPS.md`) |
|---|---|---|
| Dependensi host | Hanya Docker | PHP, Nginx, MySQL, Redis, Supervisor, PM2 satu-satu |
| Replikasi env | `up --build` di VPS mana pun | Ulangi apt/apt per server |
| Tuning CBT 500 VU | Mount `docs/server-config/*.example` ke container (tahap lanjut) | Copy ke `/etc/php`, `/etc/mysql` |
| QR WA | `run --rm -p 127.0.0.1:3001` sementara | PM2 + buka `:3001/qr` langsung |
| Biaya belajar | Compose, volume, entrypoint | Systemd/cron/supervisor klasik |

## 9. Batasan Uji Coba (branch ini)

- [ ] `docker compose config` (basis) valid — cek di mesin ber-Docker
- [ ] `docker compose -f docker-compose.yml -f docker-compose.cbt.yml config` valid
- [ ] Build image backend sukses (`composer install` di dalam build)
- [ ] Build image wa-gateway sukses (postinstall Puppeteer unduh Chromium)
- [ ] First deploy + seed + QR + 1 ujian penuh (Varian A dulu, lalu B)
