# Panduan Deploy VPS Ubuntu — Sisfo Piri (Semua Modul)

> Server target: **Ubuntu 22.04 / 24.04 LTS** (RAM 4GB, vCPU 2, Disk 40GB, IP publik). Monorepo `sisfo-piri` → `backend` (Laravel 13, PHP 8.3, MySQL, Blade) + `cbt` (Vite React) + `wa-gateway` (Node, whatsapp-web.js, port 3001). Semua modul: **PPDB, Akademik, Absensi, E-Learning, CBT**.

## 0. Ringkasan Layanan

| Layanan | Port | Daemon | Cek |
|---|---|---|---|
| Nginx + PHP-FPM | 80/443 | `nginx`, `php8.3-fpm` | `systemctl status nginx php8.3-fpm` |
| MySQL 8 | 3306 | `mysql` | `mysql -e "SELECT 1"` |
| Redis 7 | 6379 (lokal) | `redis-server` atau `docker redis:7` | `redis-cli ping → PONG` |
| Laravel Queue | — | `supervisor` → `queue:work redis` | `supervisorctl status sisfo-worker` |
| Laravel Scheduler | — | `cron * * * * * schedule:run` | `php artisan schedule:list` |
| WA Gateway | 3001 (lokal) | `pm2 → wa-gateway` | `curl 127.0.0.1:3001/status → {"siap":true}` |
| CBT React | 80/443 (dist) | Nginx static | `curl https://cbt.sekolahmu.sch.id` |

## 1. Siapkan VPS

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl nginx mysql-server unzip supervisor
# PHP 8.3 (Ubuntu 22.04 perlu PPA)
sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-redis
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# Node 20 + pnpm/npm
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
sudo npm i -g pm2

# Redis (pilih salah satu — bare metal lebih simpel di VPS)
sudo apt install -y redis-server
sudo systemctl enable --now redis-server
redis-cli ping  # PONG

# atau via Docker
# sudo apt install -y docker.io docker-compose-plugin
# sudo usermod -aG docker $USER  # relogin
# docker compose -f ~/sisfo-piri/docker-compose.yml up -d redis
```

## 2. Clone & Struktur

```bash
sudo mkdir -p /var/www
sudo chown $USER:www-data /var/www
git clone https://github.com/NiceCode9/sisfo-piri.git /var/www/sisfo-piri
cd /var/www/sisfo-piri
git checkout main && git pull
```

## 3. Backend Laravel — `.env` & Install

```bash
cd /var/www/sisfo-piri/backend
cp .env.example .env
nano .env
```

Isi production (ganti `...`):

```ini
APP_NAME="Sisfo Piri"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://admin.sekolahmu.sch.id
APP_KEY=base64:...  # akan di-generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisfo_piri
DB_USERNAME=sisfo
DB_PASSWORD=STR0NGPASS

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

WHATSAPP_URL=http://127.0.0.1:3001/kirim
WHATSAPP_TOKEN=ganti-32-char-sama-dengan-wa-gateway
# alias lama juga didukung: WHATSAPP_GATEWAY_URL / GATEWAY_TOKEN

FILESYSTEM_DISK=public
LOG_CHANNEL=daily
LOG_LEVEL=warning
```

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# DB
sudo mysql -e "CREATE DATABASE sisfo_piri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'sisfo'@'localhost' IDENTIFIED BY 'STR0NGPASS'; GRANT ALL ON sisfo_piri.* TO 'sisfo'@'localhost'; FLUSH PRIVILEGES;"

php artisan migrate --force --seed
# demo CBT 2 kelas 10 soal sudah via DatabaseSeeder → DemoCbtSeeder
# beban 500 HANYA staging: php artisan db:seed --class=LoadTestSeeder

npm install --ignore-scripts
npm run build

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

## 4. Tuning Server (untuk CBT 500 VU — `docs/server-config/`)

```bash
# PHP-FPM — 4GB contoh
sudo cp /var/www/sisfo-piri/docs/server-config/php-fpm-pool.conf.example /etc/php/8.3/fpm/pool.d/www.conf
sudo systemctl restart php8.3-fpm

# MySQL
sudo cp /var/www/sisfo-piri/docs/server-config/mysql-tuning.cnf.example /etc/mysql/conf.d/cbt-tuning.cnf
sudo systemctl restart mysql
```

## 5. Nginx — 2 Vhost (Admin Laravel + CBT React)

```bash
sudo nano /etc/nginx/sites-available/sisfo-piri-admin
```

```nginx
server {
    listen 80;
    server_name admin.sekolahmu.sch.id;
    root /var/www/sisfo-piri/backend/public;
    index index.php;
    client_max_body_size 20M;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
    location /storage/ { try_files $uri $uri/ =404; }
    location ~ /\.ht { deny all; }
}
```

```bash
sudo nano /etc/nginx/sites-available/sisfo-piri-cbt
```

```nginx
server {
    listen 80;
    server_name cbt.sekolahmu.sch.id;
    root /var/www/sisfo-piri/cbt/dist;
    index index.html;
    location / { try_files $uri $uri/ /index.html; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/sisfo-piri-admin /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/sisfo-piri-cbt /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# SSL
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d admin.sekolahmu.sch.id -d cbt.sekolahmu.sch.id
```

## 6. CBT Frontend — Build

```bash
cd /var/www/sisfo-piri/cbt
cp .env.example .env
# VITE_API_BASE_URL=https://admin.sekolahmu.sch.id/api/cbt  (prod)
nano .env

npm install
npm run build  # tsc -b && vite build → dist/
# dist/ dilayani Nginx cbt vhost di atas
```

## 7. WA Gateway — PM2 di 127.0.0.1:3001 (jangan expose publik)

```bash
cd /var/www/sisfo-piri/wa-gateway
cp .env.example .env
# PORT=3001  HOST=127.0.0.1  WA_AUTH_DIR=.wwebjs_auth  GATEWAY_TOKEN=sama-dengan-WHATSAPP_TOKEN
nano .env
# generate token bila belum: openssl rand -base64 24

npm ci --production
pm2 start ecosystem.config.js --env production
pm2 save
pm2 startup  # jalankan perintah sudo yang dicetak
pm2 status
pm2 logs wa-gateway --lines 50

# cek
curl http://127.0.0.1:3001/status  # {"siap":true} setelah scan QR
curl http://127.0.0.1:3001/qr      # PNG QR publik

# scan QR via browser VPS IP: http://VPS_IP:3001/qr (hanya saat setup, lalu tutup firewall)
# atau SHOW_QR_TERMINAL=1 di .env untuk QR di terminal
```

Backup sesi:
```bash
tar czf ~/wa_auth_backup_$(date +%F).tar.gz -C /var/www/sisfo-piri/wa-gateway .wwebjs_auth
```

## 8. Queue Worker & Scheduler (wajib untuk Absensi & CBT)

**Queue — Supervisor (1 worker cukup, tambah `numprocs` bila 500 VU):**

```bash
sudo nano /etc/supervisor/conf.d/sisfo-worker.conf
```

```ini
[program:sisfo-worker]
command=php /var/www/sisfo-piri/backend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --queue=default
directory=/var/www/sisfo-piri/backend
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/sisfo-piri/backend/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sisfo-worker
sudo supervisorctl status sisfo-worker
# deploy nanti: php artisan queue:restart
```

*Alternatif Docker:* `docker compose up -d queue-worker` (image `php:8.3-cli`, `REDIS_HOST=redis`).

**Scheduler — Cron tiap menit (untuk `absensi:cek-belum-hadir` tiap 5 menit + self-gating jam 08:00):**

```bash
sudo crontab -e -u www-data
# tambah:
* * * * * cd /var/www/sisfo-piri/backend && php artisan schedule:run >> /dev/null 2>&1
```

```bash
php artisan schedule:list  # harus tampil absensi:cek-belum-hadir everyFiveMinutes
```

## 9. Setup per Modul (setelah deploy)

| Modul | Setup Khusus | Verifikasi |
|---|---|---|
| **PPDB** | Tidak ada daemon. Pastikan `storage/app/public/{berkas,bukti,galeri,brosur}` writable + `storage:link`. Route publik `GET /`, `/pendaftaran` throttle `5,1`. | Buka `https://admin.sekolahmu.sch.id/pendaftaran` → daftar calon → `admin/calon-siswas` |
| **Akademik** | `pengampus` unique `(mapel,rombel)`. Jalankan `php artisan db:seed --class=AkademikSeeder` bila fresh (sudah via `migrate --seed`). Menu Akademik order 21-27. | `admin/pengampus`, `admin/rombels` + `RombelController:historiLengkap` |
| **Absensi** | Butuh **cron + queue + WA Gateway** (lihat §8 & §7). Atur `admin/pengaturans` → `jam_cek_belum_hadir 08:00`, `batas_terlambat 07:00`. Tabel `pengaturans cek_belum_hadir_terakhir` cegah dobel. | `php artisan absensi:cek-belum-hadir --tanggal=2026-09-17` manual → cek `notifikasi_logs` + WA masuk |
| **E-Learning** | Butuh `storage` untuk `materi` (dokumen/video/link) + `tugas`. Tidak ada queue. | `admin/materis`, `admin/tugas`, `siswa/materi`, `ortu/materi/tugas` + export `tugas/rekap/{excel,pdf}` |
| **CBT** | Butuh **Redis + queue-worker**. Pastikan `CACHE_STORE=redis` (soal `Cache::remember 6h`), `QUEUE=redis`, `VITE_API_BASE_URL` prod, `storage/cbt/questions` writable. | `admin/cbt/banks` → buat bank MTK → impor ke `admin/cbt/exams/{exam}` → `admin/cbt/{exam}/monitoring` 5s + `results` matriks + CSV. React `https://cbt.sekolahmu.sch.id` → Login `0080011001 / 0080011001` → Token `DEMOMTK7A01` → Essay/Image/Matrix/Ragu/Modal |
| **WhatsApp** | Colocated `127.0.0.1:3001`, `GATEWAY_TOKEN == WHATSAPP_TOKEN`, PM2, `queue delay 3s` anti-banned. | `admin/whatsapp` QR/status/disconnect + `curl /status` |
| **Scheduler/Queue Global** | Cron + Supervisor seperti §8 | `supervisorctl status`, `pm2 list`, `php artisan queue:failed` |

## 10. Verifikasi Akhir di VPS

```bash
# Health
curl https://admin.sekolahmu.sch.id/up
php artisan config:show app.name
php artisan route:list --except-vendor | grep -E "cbt|absensi|materi"

# Test
php artisan test --filter=CbtApi  # 5 hijau
php artisan test --filter=QuestionBank  # 6 hijau
php artisan test --compact  # full

# Queue & WA
supervisorctl status sisfo-worker  # RUNNING
pm2 list  # wa-gateway online
curl http://127.0.0.1:3001/status
redis-cli ping  # PONG

# Beban — HANYA staging, jangan prod 500 VU langsung
# php artisan db:seed --class=LoadTestSeeder
# k6 run -e BASE_URL=https://admin.sekolahmu.sch.id/api/cbt -e EXAM_TOKEN=LOADTESTXXXX -e VUS=50 load-test-cbt.js
```

## 11. Backup & Maintenance

```bash
# DB harian
mysqldump -u sisfo -p sisfo_piri | gzip > /backups/sisfo_piri_$(date +%F).sql.gz
# WA sesi
tar czf /backups/wa_auth_$(date +%F).tar.gz -C /var/www/sisfo-piri/wa-gateway .wwebjs_auth
# Kode
cd /var/www/sisfo-piri && git pull  # lalu: composer install --no-dev, npm run build (backend+cbt), php artisan migrate --force, config:cache, queue:restart, pm2 restart wa-gateway
# Maintenance mode (503 kecuali super-admin)
php artisan tinker --execute "App\Models\Pengaturan::updateOrCreate(['key'=>'maintenance_mode'],['value'=>'1']);"
# atau admin/pengaturans → maintenance_mode ON
```

## 12. Keamanan

```bash
sudo ufw allow OpenSSH,80,443/tcp
sudo ufw deny 6379/tcp  # Redis lokal saja
sudo ufw deny 3001/tcp  # WA Gateway lokal saja
sudo ufw enable
sudo apt install -y fail2ban unattended-upgrades
# REDIS requirepass bila expose, WHATSAPP_TOKEN random 32 char: openssl rand -base64 24
# APP_KEY jaga, jangan commit .env
```

## 13. Troubleshooting

| Gejala | Cek |
|---|---|
| `ViteException Unable to locate file in manifest` | `npm run build` di `backend` + `cbt` belum, atau `public/build/manifest.json` hilang |
| `personal_access_tokens` missing (test) | migrasi `2026_09_17_082215` belum `migrate --force` |
| `is_online` monitoring selalu Offline | `queue-worker` mati → `supervisorctl restart sisfo-worker` + `redis-cli ping` |
| `question_image` 404 | `php artisan storage:link` + `chown www-data storage/app/public` + Nginx `location /storage/` |
| WA tidak terkirim `notifikasi_logs=gagal` | `pm2 logs wa-gateway`, `curl /status`, `.wwebjs_auth` terhapus, token mismatch `GATEWAY_TOKEN vs WHATSAPP_TOKEN` |
| `throttle:heartbeat 429` di ExamRoom | Normal 6/min, spam 7× → tunggu 1 menit |
| Cron tidak jalan | `crontab -l -u www-data` + `grep CRON /var/log/syslog` + `php artisan schedule:list` |
```
