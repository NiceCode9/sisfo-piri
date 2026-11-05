# Sisfo Piri: Panduan Deploy Docker di Belakang Cloudflare Tunnel

Panduan ini men-deploy Sisfo Piri di satu server Ubuntu sekolah yang berada di balik router. MySQL, Redis, PHP-FPM, Nginx, queue worker, scheduler, dan backup sidecar dijalankan di Docker. Cloudflare Tunnel dijalankan langsung di host.

## Instalasi Docker dan Docker Compose

Instal Docker Engine dan plugin Compose dari repository resmi Docker:

```bash
sudo apt update
sudo apt install -y ca-certificates curl gnupg
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
```

Cek instalasi:

```bash
docker version
docker compose version
```

Opsional: agar user shell bisa memakai Docker tanpa `sudo`, tambahkan:

```bash
sudo usermod -aG docker $USER
```

Lalu logout dan login ulang sebelum menjalankan Docker.

## Instalasi Node.js dan PM2

Instal Node.js 20:

```bash
sudo apt update
sudo apt install -y curl gnupg
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

Jika `npm` belum terinstal:

```bash
sudo apt install -y npm
```

Instal PM2 global untuk WA gateway:

```bash
sudo npm install -g pm2
pm2 -v
```

## Tata Letak Server## Tata Letak Server

```bash
cd /opt
sudo git clone --branch develop https://github.com/NiceCode9/sisfo-piri.git /opt/sisfo-piri
sudo mkdir -p /opt/sisfo-piri/{data/mysql,data/redis,backups}
sudo chown -R 999:999 /opt/sisfo-piri/data/mysql /opt/sisfo-piri/data/redis
sudo chown -R 33:33 /opt/sisfo-piri/backend/storage /opt/sisfo-piri/backend/bootstrap/cache
sudo chmod 775 /opt/sisfo-piri/backups
```

Jangan jalankan `chown` rekursif untuk seluruh project root. Direktori MySQL dan Redis harus tetap UID/GID 999, dan storage Laravel harus tetap writable oleh container UID 33.

Jika `/opt/sisfo-piri` sudah ada dari percobaan sebelumnya, hapus direktori itu hanya setelah memastikan tidak ada data penting yang tertinggal.

## Persyaratan Runtime

- Docker Engine dan plugin Docker Compose
- Node.js 20 dan npm
- PM2
- cloudflared
- SSH untuk setup awal

Node, PM2, dan cloudflared sengaja tidak masuk Docker. Sesuai rancangan akhir, hanya service inti aplikasi dan data yang containerized.

## Rahasia

```bash
cd /opt/sisfo-piri
cp .env.compose.example .env.compose
cp backend/.env.example backend/.env
nano .env.compose
nano backend/.env
```

Isi nilai kuat di `.env.compose`:

```env
MYSQL_DATABASE=sisfo_piri
MYSQL_USER=sisfo
MYSQL_PASSWORD=ganti-dengan-password-kuat
MYSQL_ROOT_PASSWORD=ganti-dengan-password-root-yang-berbeda
BACKUP_RETENTION_DAYS=7
```

Isi nilai produksi di `backend/.env`, termasuk:

```env
APP_URL=https://smppiringaglik.sch.id
APP_ENV=production
APP_DEBUG=false
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=sisfo_piri
DB_USERNAME=sisfo
DB_PASSWORD=ganti-dengan-password-yang-sama
REDIS_HOST=redis
REDIS_PORT=6379
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
WHATSAPP_URL=http://host.docker.internal:3001/kirim
WHATSAPP_TOKEN=ganti-dengan-token-wa-gateway
```

File `backend/.env` tidak dipublish ke Docker. Service PHP tetap membacanya melalui bind mount di `/var/www/backend/.env`.

## Persiapan Host Awal
Pastikan direktori project dimiliki oleh user deploy (akun shell Anda), supaya `npm ci` tidak memerlukan `sudo`:

```bash
cd /opt/sisfo-piri
sudo chown -R $USER:$USER backend cbt wa-gateway
sudo chown -R 33:33 backend/storage backend/bootstrap/cache
sudo chown -R 999:999 data/mysql data/redis
sudo chown -R "$USER:$USER" "$HOME/.npm"
```
Siapkan dependensi Node untuk build di host:

```bash
cd /opt/sisfo-piri/backend
npm ci
npm run build

cd /opt/sisfo-piri/cbt
npm ci
npm run build
```

Instal dependensi PHP Laravel di dalam container PHP:

```bash
cd /opt/sisfo-piri
docker compose run --rm php composer install --no-dev --optimize-autoloader
```

Pastikan nilai host produksi terisi di `backend/.env`:

```bash
sed -i 's/^DB_HOST=.*/DB_HOST=mysql/' backend/.env
sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' backend/.env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/' backend/.env
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=redis/' backend/.env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' backend/.env
sed -i 's#^WHATSAPP_URL=.*#WHATSAPP_URL=http://host.docker.internal:3001/kirim#' backend/.env
```

## Pembuatan dan Hak Akses Database

MySQL diinisialisasi otomatis saat container pertama kali jalan dengan nilai dari `.env.compose`: database `sisfo_piri` dibuat, user `sisfo` diberi semua hak pada database itu, dan password aplikasi disesuaikan.

Verifikasi setup otomatis setelah pertama jalan:

```bash
set -a
. /opt/sisfo-piri/.env.compose
set +a
docker compose exec mysql mysqladmin ping -h localhost -u root -p"$MYSQL_ROOT_PASSWORD"
docker compose exec mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES; SELECT User, Host FROM mysql.user WHERE User='$MYSQL_USER';"
```

Hasil yang diharapkan: `sisfo_piri` ada dan user `sisfo` punya host row `%`.

Fallback manual, jika inisialisasi otomatis terputus atau direktori data MySQL lama disambungkan ulang:

```bash
set -a
. /opt/sisfo-piri/.env.compose
set +a
docker compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<'SQL'
CREATE DATABASE IF NOT EXISTS sisfo_piri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sisfo'@'%' IDENTIFIED BY 'ganti-dengan-password-yang-sama';
GRANT ALL PRIVILEGES ON sisfo_piri.* TO 'sisfo'@'%';
FLUSH PRIVILEGES;
SQL
```

Password fallback harus persis sama dengan `MYSQL_PASSWORD` dan `DB_PASSWORD` di Laravel.

## Persiapan Image Docker

Sebelum menjalankan stack, unduh image dasar MySQL dan Redis, lalu build image PHP dan Nginx. Queue worker, scheduler, dan backup memakai image PHP yang sama.

```bash
cd /opt/sisfo-piri
docker compose pull mysql redis
docker compose build php nginx backup
```

Image yang diunduh/dibangun meliputi:
- `mysql:8.0`
- `redis:7-alpine`
- image PHP-FPM 8.3 dari `ops/php/Dockerfile`
- image Nginx dari `ops/nginx/Dockerfile`
- image backup dari `ops/backup/Dockerfile`
## Deployment Pertama

Jalankan database dulu:

```bash
cd /opt/sisfo-piri
docker compose up -d mysql redis
```

Tunggu keduanya healthy, lalu migrate, seed, dan buat link storage:

```bash
docker compose run --rm php php artisan migrate --force --seed
docker compose run --rm php php artisan storage:link
```

Seed wajib pada instalasi fresh karena permission dan menu datang dari seeder.

Jalankan sisa service:

```bash
docker compose up -d --build
```

Periksa state:

```bash
docker compose ps
```

Service yang diharapkan jalan: `mysql`, `redis`, `php`, `queue-worker`, `scheduler`, `nginx`, `backup`.

## Verifikasi

Pemeriksaan health dan route:

```bash
curl -I http://127.0.0.1:8080/up
curl -I https://smppiringaglik.sch.id/up
curl -I https://cbt.smppiringaglik.sch.id
```

Pemeriksaan aplikasi:

- Login ke Laravel.
- Buka CBT lalu refresh deep route; jangan sampai 404.
- Upload satu file dan request URL public storage-nya.
- Cek queue dan scheduler status.
- Lakukan restore drill sebelum sign-off produksi.

Pemeriksaan WhatsApp lokal:

```bash
ssh -L 3001:127.0.0.1:3001 your-user@smppiringaglik.sch.id
```

Buka `http://127.0.0.1:3001/status` dan `/qr` di browser. Dari dalam Laravel, notifikasi menggunakan `http://host.docker.internal:3001/kirim`, bukan `localhost`. Jika `backend/.env` membiarkan `WHATSAPP_URL` kosong, fallback aplikasi menunjuk ke host-localhost dan akan gagal dari dalam container; periksa ini sebelum sign-off.

Pemeriksaan Redis:

```bash
docker compose exec redis redis-cli ping
```

Hasil yang diharapkan: `PONG`.

## Backup dan Restore

Backup sidecar jalan harian dan menyimpan 7 hari. Periksa output-nya:

```bash
ls -lh /opt/sisfo-piri/backups
gzip -t /opt/sisfo-piri/backups/*.sql.gz
```

Storage ikut disalin ke direktori yang sama sebagai `storage-*.tar.gz`.

Restore drill sebelum sign-off produksi:

1. Buat atau tentukan database test kosong.
2. Restore satu arsip `mysql-*.sql.gz` ke database itu.
3. Restore satu arsip `storage-*.tar.gz` ke path storage sementara.
4. Bandingkan jumlah baris pada tabel penting dan periksa satu file upload.
5. Catat hasilnya di catatan deployment.

Salin backup ke disk eksternal sebagai rutinitas manual. Backup yang tersimpan di SSD yang sama dengan database tidak melindungi dari kerusakan hardware.

## Cloudflare Tunnel

Instal cloudflared langsung di host:

```bash
sudo apt update
sudo apt install -y cloudflared
```

Buat satu tunnel dan petakan DNS:

```bash
cloudflared tunnel login
cloudflared tunnel create sisfo-piri-school
cloudflared tunnel route dns sisfo-piri-school smppiringaglik.sch.id
cloudflared tunnel route dns sisfo-piri-school cbt.smppiringaglik.sch.id
```

Contoh konfigurasi di `/root/.cloudflared/config.yml`:

```yaml
tunnel: ganti-dengan-uuid-tunnel
credentials-file: /root/.cloudflared/ganti-dengan-uuid-tunnel.json

ingress:
  - hostname: smppiringaglik.sch.id
    service: http://127.0.0.1:8080
  - hostname: cbt.smppiringaglik.sch.id
    service: http://127.0.0.1:8080
  - service: http_status:404
```

Tunnel SSH opsional:

```yaml
  - hostname: ssh.smppiringaglik.sch.id
    service: ssh://localhost:22
```

Instal dan aktifkan service:

```bash
cloudflared service install
systemctl enable --now cloudflared
cloudflared tunnel info sisfo-piri-school
```

Aktifkan Cloudflare Access untuk SSH sebelum mengandalkannya di produksi. Jika belum dikonfigurasi, SSH tetap tersedia lewat Tailscale sebagai fallback.

## Firewall Host

```bash
sudo ufw default deny incoming
sudo ufw allow from 10.0.0.0/8 to any port 22 proto tcp
```

Sesuaikan `10.0.0.0/8` dengan subnet sekolah/Tailscale. Hapus rule SSH setelah Cloudflare Access SSH atau Tailscale terverifikasi.

```bash
sudo ufw deny 80/tcp
sudo ufw deny 443/tcp
sudo ufw deny 3001/tcp
sudo ufw deny 3306/tcp
sudo ufw deny 6379/tcp
sudo ufw enable
```

Jangan ubah UFW agar membuka port Docker `8080`. Nginx harus tetap bound ke `127.0.0.1:8080`. Jika nanti port 8080 dibuka, Cloudflare bisa dibypass.

## Node dan WA Gateway Native

Instal Node.js 20 dan npm, lalu build kedua aplikasi di host:

```bash
cd /opt/sisfo-piri/backend
npm ci
npm run build

cd /opt/sisfo-piri/cbt
npm ci
npm run build
```

Jalankan gateway di host:

```bash
cd /opt/sisfo-piri/wa-gateway
cp .env.example .env
npm ci --production
pm2 start ecosystem.config.js --env production
pm2 save
pm2 startup
```

Contoh `wa-gateway/.env`:

```env
NODE_ENV=production
PORT=3001
HOST=127.0.0.1
GATEWAY_TOKEN=ganti-dengan-token-yang-sama
```

Jangan expose `3001` ke internet. Scan QR dengan SSH tunnel ke `localhost:3001`.

## Pembaruan

Deployment berikutnya mengikuti urutan ini:

```bash
cd /opt/sisfo-piri
git pull origin develop
docker compose run --rm php composer install --no-dev --optimize-autoloader

cd backend && npm ci && npm run build
cd ../cbt && npm ci && npm run build

docker compose up -d --build php nginx queue-worker scheduler backup
docker compose exec php php artisan migrate --force
docker compose exec php php artisan queue:restart
```

## Command Docker yang Sering Dipakai

```bash
cd /opt/sisfo-piri
docker compose ps
docker compose logs -f
docker compose logs -f nginx
docker compose logs -f php
docker compose restart nginx
docker compose restart queue-worker
docker compose stop
docker compose down
docker system df
```

### Menjalankan Artisan di container yang sudah jalan

```bash
docker compose exec php php artisan migrate --force
docker compose exec php php artisan migrate --force --seed
docker compose exec php php artisan storage:link
docker compose exec php php artisan route:list
docker compose exec php php artisan queue:restart
docker compose exec php php artisan config:cache
docker compose exec php php artisan route:cache
docker compose exec php php artisan view:cache
```

### Menjalankan Artisan di container sementara

```bash
docker compose run --rm php php artisan migrate --force --seed
docker compose run --rm php php artisan storage:link
```

### Perbarui setelah git pull

```bash
cd /opt/sisfo-piri
git pull origin develop
docker compose up -d --build php nginx queue-worker scheduler backup
docker compose exec php php artisan migrate --force
docker compose exec php php artisan queue:restart
```

### Catatan tentang `--rm`

- `docker compose run --rm service command` membuat container sementara, menjalankan command, lalu otomatis menghapus container setelah selesai.
- `docker compose exec service command` menjalankan command di container yang sedang running dan container tidak dihapus.
- `--rm` berguna untuk perintah satu kali, seperti migrasi awal atau command Artisan sekali jalan, supaya container percobaan tidak menumpuk.
- Untuk command harian di service yang sudah jalan, gunakan `docker compose exec`.
## Keputusan Terbuka

- Kebijakan Cloudflare Access yang tepat untuk SSH.
- Apakah domain backend Laravel final memakai domain apex atau subdomain `admin.`.
- Masa retensi jika 7 hari belum cukup.

Lihat `docs/superpowers/specs/2026-11-04-docker-deploy-cloudflare-tunnel-design.md` untuk keputusan desain yang sudah disetujui.
