# Sisfo Piri: Docker Deployment Behind Cloudflare Tunnel

This runbook deploys Sisfo Piri on one Ubuntu server behind the school router. MySQL, Redis, PHP-FPM, Nginx, queue worker, scheduler, and the backup sidecar run in Docker. Cloudflare Tunnel runs natively on the host.

## Server Layout

```bash
cd /opt
sudo git clone --branch develop https://github.com/NiceCode9/sisfo-piri.git /opt/sisfo-piri
sudo mkdir -p /opt/sisfo-piri/{data/mysql,data/redis,backups}
sudo chown -R 999:999 /opt/sisfo-piri/data/mysql /opt/sisfo-piri/data/redis
sudo chown -R 33:33 /opt/sisfo-piri/backend/storage /opt/sisfo-piri/backend/bootstrap/cache
sudo chmod 775 /opt/sisfo-piri/backups
```

Do not apply a recursive host-user chown over the entire project root. The MySQL and Redis directories must keep UID/GID 999, and Laravel storage must remain writable by container UID 33.

If `/opt/sisfo-piri` already exists from another attempt, remove only the directory after confirming nothing useful remains.

## Runtime Requirements

- Docker Engine and Docker Compose plugin
- Node.js 20 and npm
- PM2
- cloudflared
- SSH available for setup

Node, PM2, and cloudflared are intentionally not inside Docker.

## Secrets

```bash
cd /opt/sisfo-piri
cp .env.compose.example .env.compose
cp backend/.env.example backend/.env
nano .env.compose
nano backend/.env
```

Set strong values in `.env.compose`:

```env
MYSQL_DATABASE=sisfo_piri
MYSQL_USER=sisfo
MYSQL_PASSWORD=replace-with-a-strong-password
MYSQL_ROOT_PASSWORD=replace-with-a-different-root-password
BACKUP_RETENTION_DAYS=7
```

Set production values in `backend/.env`, including:

```env
APP_URL=https://smppiringaglik.sch.id
APP_ENV=production
APP_DEBUG=false
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=sisfo_piri
DB_USERNAME=sisfo
DB_PASSWORD=replace-with-the-same-password
REDIS_HOST=redis
REDIS_PORT=6379
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
WHATSAPP_URL=http://host.docker.internal:3001/kirim
WHATSAPP_TOKEN=replace-with-the-wa-gateway-token
```

`backend/.env` is not published into Docker. The PHP service reads it through the bind mount at `/var/www/backend/.env`.

## One-Time Host Prep

Prep Node dependencies for host builds:

```bash
cd /opt/sisfo-piri/backend
npm ci
npm run build

cd /opt/sisfo-piri/cbt
npm ci
npm run build
```

Install Laravel PHP dependencies inside the PHP container:

```bash
cd /opt/sisfo-piri
docker compose run --rm php composer install --no-dev --optimize-autoloader
```

Ensure production host values are set in `backend/.env`:

```bash
sed -i 's/^DB_HOST=.*/DB_HOST=mysql/' backend/.env
sed -i 's/^REDIS_HOST=.*/REDIS_HOST=redis/' backend/.env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/' backend/.env
sed -i 's/^CACHE_STORE=.*/CACHE_STORE=redis/' backend/.env
sed -i 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' backend/.env
sed -i 's#^WHATSAPP_URL=.*#WHATSAPP_URL=http://host.docker.internal:3001/kirim#' backend/.env
```

## Database Creation and Privileges

MySQL is initialized automatically when the container starts for the first time with the values from `.env.compose`: it creates `sisfo_piri`, grants all privileges on that database to `sisfo`, and configures the application password.

Verify the automatic setup after first start:

```bash
set -a
. /opt/sisfo-piri/.env.compose
set +a
docker compose exec mysql mysqladmin ping -h localhost -u root -p"$MYSQL_ROOT_PASSWORD"
docker compose exec mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "SHOW DATABASES; SELECT User, Host FROM mysql.user WHERE User='$MYSQL_USER';"
```

Expected: `sisfo_piri` exists and the `sisfo` user has a `%` host row.

Manual fallback, if the automatic initialization was interrupted or you are reconnecting the data directory to an existing MySQL volume:

```bash
set -a
. /opt/sisfo-piri/.env.compose
set +a
docker compose exec -T mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<'SQL'
CREATE DATABASE IF NOT EXISTS sisfo_piri CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'sisfo'@'%' IDENTIFIED BY 'replace-with-the-same-password';
GRANT ALL PRIVILEGES ON sisfo_piri.* TO 'sisfo'@'%';
FLUSH PRIVILEGES;
SQL
```

The fallback password must exactly match both `MYSQL_PASSWORD` and Laravel `DB_PASSWORD`.
## First Deployment

Start the databases first:

```bash
cd /opt/sisfo-piri
docker compose up -d mysql redis
```

Wait until both are healthy, then migrate, seed, and link storage:

```bash
docker compose run --rm php php artisan migrate --force --seed
docker compose run --rm php php artisan storage:link
```

Seed on fresh installs is mandatory because permissions and menus come from seeders.

Start the remaining services:

```bash
docker compose up -d --build
```

Confirm state:

```bash
docker compose ps
```

Expected running services: `mysql`, `redis`, `php`, `queue-worker`, `scheduler`, `nginx`, `backup`.

## Verification

Health and route checks:

```bash
curl -I http://127.0.0.1:8080/up
curl -I https://smppiringaglik.sch.id/up
curl -I https://cbt.smppiringaglik.sch.id
```

Application checks:

- Log in to Laravel.
- Open CBT and refresh a deep route; it must not 404.
- Upload a file and request the resulting public storage URL.
- Inspect `php artisan route:list` through the container if needed.
- Check queue and scheduler status.

Local WhatsApp check:

```bash
ssh -L 3001:127.0.0.1:3001 your-user@smppiringaglik.sch.id
```

Open `http://127.0.0.1:3001/status` and `/qr` in the browser. From inside Laravel, notifications use `http://host.docker.internal:3001/kirim`, not `localhost`.

Redis check:

```bash
docker compose exec redis redis-cli ping
```

## Backup and Restore

The backup sidecar runs daily and retains seven days. Inspect output:

```bash
ls -lh /opt/sisfo-piri/backups
gzip -t /opt/sisfo-piri/backups/*.sql.gz
```

Storage is copied into the same directory as `storage-*.tar.gz`.

Restore drill before production sign-off:

1. Create or identify an empty test database.
2. Restore one `mysql-*.sql.gz` archive into it.
3. Restore one `storage-*.tar.gz` archive into a temporary storage path.
4. Compare key table counts and inspect one uploaded file.
5. Record the result in the deployment notes.

Copy backups to an external disk as part of your manual routine. The backups live on the same SSD as the database, so they do not protect against hardware loss.

## Cloudflare Tunnel

Install cloudflared natively:

```bash
sudo apt update
sudo apt install -y cloudflared
```

Create one tunnel and map DNS:

```bash
cloudflared tunnel login
cloudflared tunnel create sisfo-piri-school
cloudflared tunnel route dns sisfo-piri-school smppiringaglik.sch.id
cloudflared tunnel route dns sisfo-piri-school cbt.smppiringaglik.sch.id
```

Example config at `/root/.cloudflared/config.yml`:

```yaml
tunnel: replace-with-tunnel-uuid
credentials-file: /root/.cloudflared/replace-with-tunnel-uuid.json

ingress:
  - hostname: smppiringaglik.sch.id
    service: http://127.0.0.1:8080
  - hostname: cbt.smppiringaglik.sch.id
    service: http://127.0.0.1:8080
  - service: http_status:404
```

Optional SSH tunnel:

```yaml
  - hostname: ssh.smppiringaglik.sch.id
    service: ssh://localhost:22
```

Install and enable the service:

```bash
cloudflared service install
systemctl enable --now cloudflared
cloudflared tunnel info sisfo-piri-school
```

Enable Cloudflare Access for SSH before relying on it publicly. If Access is not configured, keep SSH available through Tailscale as a fallback.

## Host Firewall

```bash
sudo ufw default deny incoming
sudo ufw allow from 10.0.0.0/8 to any port 22 proto tcp
```

Adjust `10.0.0.0/8` to the school/Tailscale subnet. Remove the SSH rule completely only after Cloudflare Access SSH or Tailscale is verified.

```bash
sudo ufw deny 80/tcp
sudo ufw deny 443/tcp
sudo ufw deny 3001/tcp
sudo ufw deny 3306/tcp
sudo ufw deny 6379/tcp
sudo ufw enable
```

Do not change UFW to allow Docker's published port `8080`; Nginx must remain bound to `127.0.0.1:8080`. If you later open `8080`, Cloudflare would be bypassed.

## Native Node and WA Gateway

Install Node.js 20 and npm, then build both applications on the host:

```bash
cd /opt/sisfo-piri/backend
npm ci
npm run build

cd /opt/sisfo-piri/cbt
npm ci
npm run build
```

Run the gateway on its host:

```bash
cd /opt/sisfo-piri/wa-gateway
cp .env.example .env
npm ci --production
pm2 start ecosystem.config.js --env production
pm2 save
pm2 startup
```

Example `wa-gateway/.env`:

```env
NODE_ENV=production
PORT=3001
HOST=127.0.0.1
GATEWAY_TOKEN=replace-with-the-same-token
```

Do not expose `3001` to the internet. Scan the QR using an SSH tunnel to `localhost:3001`.

## Updates

Later deployments follow this sequence:

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

## Open Decisions

- Exact Cloudflare Access policy for SSH.
- Whether the final backend domain uses the apex or `admin.` subdomain.
- Retention period if seven days is not enough.

See `docs/superpowers/specs/2026-11-04-docker-deploy-cloudflare-tunnel-design.md` for the approved design decisions.
