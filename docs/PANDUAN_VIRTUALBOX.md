# Panduan Uji di VirtualBox — Sebelum Naik ke VPS

> Simulasi VPS di VirtualBox agar alur **Docker → Redis → Queue Worker → Migrasi → Build** terverifikasi sebelum deploy produksi. Asumsi: host Windows/Linux, guest **Ubuntu Server 22.04/24.04** (RAM 4GB, vCPU 2, Disk 30GB, Network Bridged/NAT + Host-Only).

## 1. Siapkan VM

| Langkah | Perintah / Aksi |
|---|---|
| Buat VM | VirtualBox → New → Type Linux, Version Ubuntu 64-bit, RAM 4096, Disk 30GB VDI Dynamic |
| Network | Adapter 1 **Bridged** (atau NAT + Port Forward 2222→22, 8000→8000), Adapter 2 Host-Only (opsional) |
| ISO | `ubuntu-22.04-live-server-amd64.iso` → mount → Install → buat user `deploy` |
| Update | `sudo apt update && sudo apt upgrade -y` |
| Tools | `sudo apt install -y git curl nginx mysql-server redis-tools` (opsional bila pakai Docker, MySQL bisa container) |

## 2. Pasang Dependensi

```bash
# Node + PHP 8.3
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-redis php8.3-bcmath unzip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Docker + Compose (jika mau pakai docker-compose.yml repo)
sudo apt install -y docker.io docker-compose-plugin
sudo usermod -aG docker $USER  # logout/login
```

## 3. Clone & Env

```bash
git clone https://github.com/NiceCode9/sisfo-piri.git ~/sisfo-piri
cd ~/sisfo-piri/backend
cp .env.example .env
# edit .env untuk VM (contoh)
# APP_URL=http://192.168.56.10  # IP VM (ip a)
# DB_HOST=127.0.0.1  DB_DATABASE=sisfo_piri  DB_USERNAME=sisfo  DB_PASSWORD=secret
# CACHE_STORE=redis  QUEUE_CONNECTION=redis  SESSION_DRIVER=redis  REDIS_HOST=127.0.0.1
# VITE_API_BASE_URL=http://192.168.56.10/api/cbt  # untuk cbt/.env
nano .env

# cbt/.env
cd ~/sisfo-piri/cbt
cp .env.example .env
# VITE_API_BASE_URL=http://192.168.56.10/api/cbt
nano .env
```

## 4. Database & Redis

```bash
# MySQL (jika tidak pakai Docker)
sudo mysql -e "CREATE DATABASE sisfo_piri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'sisfo'@'localhost' IDENTIFIED BY 'secret'; GRANT ALL ON sisfo_piri.* TO 'sisfo'@'localhost'; FLUSH PRIVILEGES;"

# Redis via Docker (rekomendasi — sama dengan VPS)
cd ~/sisfo-piri
docker compose up -d redis
docker compose ps
redis-cli -h 127.0.0.1 ping  # PONG
# alternatif tanpa Docker: sudo apt install redis-server && sudo systemctl enable --now redis-server
```

## 5. Backend — Install & Migrasi

```bash
cd ~/sisfo-piri/backend
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
# demo CBT 2 kelas 10 soal (sudah di DatabaseSeeder)
# php artisan db:seed --class=DemoCbtSeeder  # sudah jalan via fresh --seed
# beban 500 (staging VM, jangan di dev kecil)
# php artisan db:seed --class=LoadTestSeeder

php artisan config:clear
php artisan cache:clear
php artisan queue:restart
```

## 6. Frontend Build

```bash
cd ~/sisfo-piri/cbt
npm install
npm run build  # tsc -b && vite build → dist/
# dev (opsional): npm run dev -- --host 0.0.0.0 --port 5173
```

## 7. Queue Worker (wajib untuk heartbeat/violation)

```bash
# Opsi A — Docker (sesuai docker-compose.yml queue-worker)
cd ~/sisfo-piri
docker compose up -d queue-worker
docker compose logs -f queue-worker

# Opsi B — systemd (tanpa Docker, mirip VPS)
sudo nano /etc/systemd/system/cbt-queue.service
# [Unit] Description=CBT Queue Worker
# [Service] User=deploy WorkingDirectory=/home/deploy/sisfo-piri/backend ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600 Restart=always
# [Install] WantedBy=multi-user.target
sudo systemctl daemon-reload
sudo systemctl enable --now cbt-queue.service
sudo systemctl status cbt-queue

# Opsi C — dev sementara
# php artisan queue:work --sleep=3 --tries=3  # di window terpisah
```

## 8. Nginx (opsional, simulasi VPS)

```bash
sudo nano /etc/nginx/sites-available/sisfo-piri
# server {
#   listen 80;
#   server_name 192.168.56.10;
#   root /home/deploy/sisfo-piri/backend/public;
#   index index.php;
#   location / { try_files $uri $uri/ /index.php?$query_string; }
#   location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php8.3-fpm.sock; }
#   location /api/cbt/ { try_files $uri $uri/ /index.php?$query_string; }
# }
sudo ln -s /etc/nginx/sites-available/sisfo-piri /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
# tuning prod: copy docs/server-config/php-fpm-pool.conf.example → /etc/php/8.3/fpm/pool.d/www.conf
#          docs/server-config/mysql-tuning.cnf.example → /etc/mysql/conf.d/cbt-tuning.cnf
```

## 9. Verifikasi

```bash
# Backend
curl http://192.168.56.10/api/cbt/login -H "Content-Type: application/json" -d '{"identifier":"superadmin","password":"superadmin123"}'
# → {"token":"...","user":{...}}

# Frontend (dari host)
# http://192.168.56.10:5173  (dev) atau http://192.168.56.10 (nginx)
# Login siswa 0080011001 / 0080011001 → Token DEMOMTK7A01 → ExamRoom (essay, image, matrix 5 cols, Ragu-ragu, timer, modal Selesaikan)

# Test suite di VM
cd ~/sisfo-piri/backend
vendor/bin/pint --test
php artisan test --filter=CbtApi  # 5 hijau
php artisan test --filter=QuestionBank  # 6 hijau
php artisan test --compact  # full

# Queue
docker compose logs queue-worker  # atau journalctl -u cbt-queue -f  # lihat RecordHeartbeat tiap 20s

# Beban (hanya VM staging, RAM ≥4GB)
k6 run -e BASE_URL=http://192.168.56.10/api/cbt -e EXAM_TOKEN=DEMOMTK7A01 -e VUS=50 load-test-cbt.js  # coba 50 dulu, baru 500
```

## 10. Snapshot & Naik ke VPS

```bash
# di VM, setelah hijau, buat snapshot VirtualBox (Machine → Take Snapshot) sebelum migrasi
# ekspor env & DB untuk VPS
mysqldump -u sisfo -p sisfo_piri | gzip > ~/sisfo-piri.sql.gz
# di VPS: git clone sama, docker compose up -d redis queue-worker, migrate:fresh --seed, scp dist/cbt, storage:link, nginx
```

## Catatan

- IP VM lihat `ip a` (Bridged → `192.168.1.x`, Host-Only → `192.168.56.x`). Host Windows `ping` dulu.
- Jika host hanya NAT, forward `2222→22` untuk `ssh -p 2222 deploy@localhost` dan `8000→8000` untuk `http://localhost:8000`.
- Snapshot VM sebelum `migrate:fresh --seed` agar bisa rollback tanpa re-clone.
