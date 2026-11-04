# Design: Docker Deployment with Cloudflare Tunnel for Sisfo Piri

Date: 2026-11-04
Status: Approved design

## Intent

Deploy Sisfo Piri on a single Ubuntu server at school, behind the school router, without requiring a public IP. MySQL, Redis, PHP 8.3, Nginx, queue worker, scheduler, and the daily backup job run in Docker. Node/npm and the WhatsApp gateway remain on the host because they are needed for builds and already have a stable PM2 workflow. Cloudflare Tunnel, installed natively on the host, connects the domains and SSH to the server.

## Success Criteria

- Laravel is reachable at `https://smppiringaglik.sch.id`.
- CBT React is reachable at `https://cbt.smppiringaglik.sch.id`.
- REST API for CBT remains local/HTTPS through the Laravel domain; WA QR remains local only.
- A server reboot restarts the Docker stack and restores service without manual intervention.
- Database, uploads, bank soal media, Redis state, and WhatsApp auth survive container rebuilds.
- Daily compressed backups of the database and Laravel `storage/` are available in `/opt/sisfo-piri/backups`.
- A restore from backup has been tested once before production use.

## Non-Goals

- No native installation guide for MySQL, PHP, Nginx, or Redis on Ubuntu.
- No Cloudflare Tunnel inside Docker; it runs as a host service.
- No public exposure of port 80, 443, 3306, 6379, or 3001.
- No billing, payroll, or additional school modules.
- No change to Laravel domain code unless a small configuration change is required for proxy trust.

## Target Environment

- Host: Ubuntu on a 16 GB RAM / 512 GB SSD server located at school.
- Access: school network behind a router; no public inbound IP.
- Current remote access is Tailscale; the final remote access method is Cloudflare Tunnel, including SSH.
- Disk paths:
  - Application: `/opt/sisfo-piri`
  - Docker data: `/opt/sisfo-piri/data`
  - Backups: `/opt/sisfo-piri/backups`

## Selected Host Layout

```text
/opt/sisfo-piri/
  backend/                 # Laravel repository working tree
  cbt/                     # CBT React repository working tree, including dist/
  data/
    mysql/
    redis/
  backups/
  docker-compose.yml
  .env.compose
  ops/
    php/
      Dockerfile
    nginx/
      Dockerfile
      default.conf
    backup/
      Dockerfile
      backup.sh
```

The full backend repository stays on the host. Docker binds it into containers rather than copying code into images, so updates are performed with `git pull` plus service rebuild/start.

The WA gateway also stays on the host under PM2 on `127.0.0.1:3001`. Backend storage remains inside `backend/storage`, mounted through the bind mount. This keeps uploads visible both to Laravel and to Nginx.

## Docker Services

### `mysql`

- Image: `mysql:8.0`
- Publish ports: none.
- Volume: `/opt/sisfo-piri/data/mysql:/var/lib/mysql`
- Environment from `.env.compose`: `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`.
- Restart: `unless-stopped`
- Health check: `mysqladmin ping`

### `redis`

- Image: `redis:7-alpine`
- Publish ports: none.
- Volume: `/opt/sisfo-piri/data/redis:/data`
- Command: enable AOF (`--appendonly yes`)
- Restart: `unless-stopped`

### `php`

- Built from `ops/php/Dockerfile` based on `php:8.3-fpm`
- Includes: composer, PHP extensions `pdo_mysql`, `redis`, `gd`, `intl`, `zip`, `bcmath`, `xml`
- Restart: `unless-stopped`
- Binds /opt/sisfo-piri/backend:/var/www/backend\n- extra_hosts: host.docker.internal:host-gateway so containerized PHP can reach host PM2
- Runs as the image's default `www-data` worker
- `depends_on`: mysql healthy, redis healthy
- Used by queue and scheduler services with container-specific command

### `queue-worker`

- Same PHP image and bind mount as `php`
- Command: Laravel queue worker
- Restart: `unless-stopped`
- Queue: Redis

### `scheduler`

- Same PHP image and bind mount as `php`
- Command: `php artisan schedule:work`
- Restart: `unless-stopped`

### `nginx`

- Built from `ops/nginx/Dockerfile` based on `nginx:stable-alpine`
- Publish ports: `127.0.0.1:8080:80` only
- Binds:
  - `/opt/sisfo-piri/backend:/var/www/backend:ro`
  - `/opt/sisfo-piri/cbt/dist:/var/www/cbt/dist:ro`
- Host-based virtual hosts:
  - Laravel public root for the main domain
  - CBT `dist` root for the CBT subdomain
- Restart: `unless-stopped`

### `backup`

- Built from `ops/backup/Dockerfile`
- Runs a daily loop and retains files for 7 days
- Volume: `/opt/sisfo-piri/backups:/backups`
- Read-only bind: `/opt/sisfo-piri/backend/storage:/storage:ro`
- MySQL client performs `mysqldump`; storage is compressed with tar
- Restart: `unless-stopped`

## Persistent Data and Anti-Loss Strategy

The following bind mounts must survive every container rebuild:

- `/opt/sisfo-piri/data/mysql:/var/lib/mysql`
- `/opt/sisfo-piri/data/redis:/data`
- `/opt/sisfo-piri/backend` (code, vendor, public/build, and storage)
- `/opt/sisfo-piri/cbt/dist`
- `/opt/sisfo-piri/backups`

Ownership:
- MySQL volume is chowned to UID `999` before first start.
- Laravel storage is writable by the PHP worker UID.
- Public files are served from Laravel's normal `storage/app/public` directory.

Backup strategy:
- `mysqldump` of `sisfo_piri` into `/opt/sisfo-piri/backups/mysql-YYYYMMDD-HHMMSS.sql.gz`
- tar of `backend/storage/` into `/opt/sisfo-piri/backups/storage-YYYYMMDD-HHMMSS.tar.gz`
- delete backups older than 7 days
- user manually copies backups to another disk or external storage on their schedule

Restore strategy is part of the deployment checklist, not an assumed capability. The server must complete a scratch restore test using the generated backup before production sign-off.

## Nginx Routing

Nginx listens internally on port 80. Two virtual hosts:

- `smppiringaglik.sch.id`: document root `/var/www/backend/public`, front controller `/index.php`
- `cbt.smppiringaglik.sch.id`: document root `/var/www/cbt/dist`, SPA fallback to `/index.html`

PHP is passed to the `php` service as FastCGI. Upload limit is 50 MB for CBT question import and school document uploads. Storage files are served through the normal Laravel public storage path.

## Laravel Environment

Production `.env` values include:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://smppiringaglik.sch.id`
- `DB_HOST=mysql`
- `REDIS_HOST=redis`
- `SESSION_DRIVER=redis`
- `CACHE_STORE=redis`
- `QUEUE_CONNECTION=redis`
- `WHATSAPP_URL=http://127.0.0.1:3001/kirim`
- upload, queue, and storage settings preserved from the existing production guide

The application must trust the Cloudflare proxy for HTTPS detection. `TrustProxies` should be configured to trust `*` or the documented Cloudflare ranges before deployment.

## Cloudflare Tunnel and DNS

Cloudflare is installed natively on the host. The tunnel maps:

- `smppiringaglik.sch.id -> http://127.0.0.1:8080`
- `cbt.smppiringaglik.sch.id -> http://127.0.0.1:8080`
- optional SSH host -> `ssh://localhost:22`

No other service is published through Cloudflare. The WA gateway remains reachable only at `localhost:3001`.

SSH access is protected by Cloudflare Access before production use. If Access is not available, SSH remains available only through Tailscale as a fallback.

## Host Services

### Node/npm

Installed natively for:

- Laravel Vite: `npm ci && npm run build` in `/opt/sisfo-piri/backend`
- CBT Vite: `npm ci && npm run build` in `/opt/sisfo-piri/cbt`

Build output lands in `backend/public/build` and `cbt/dist`, then is bound into Nginx.

### WhatsApp Gateway

Runs on the host:
- `pm2` starts `wa-gateway`
- `HOST=127.0.0.1`
- `PORT=3001`
- token matches backend WHATSAPP_TOKEN\n- QR is viewed through an SSH tunnel, not a public route\n- PHP containers need host.docker.internal:host-gateway in Compose

## Firewall and SSH

- UFW denies incoming by default.
- Allow `22/tcp` only for Tailscale or Cloudflare Tunnel operation as needed.
- Deny `80/tcp`, `443/tcp`, `3001/tcp`, `3306/tcp`, and `6379/tcp`.
- Docker publish syntax remains `127.0.0.1:8080:80`, never `8080:80`.
- Scheduler, queue, and backup run inside Docker, so no separate supervisor or cron configuration is needed on the host.

## Seed and Migration

Initial deployment:
1. clone repository into `/opt/sisfo-piri`
2. create `.env` and `.env.compose`
3. create bind-mount directories and chown MySQL volume
4. build PHP and backup images
5. host Node build for Laravel and CBT\n- Production branch: `develop` (`git clone --branch develop`)
6. start `mysql`, `redis`, wait for health
7. `php artisan migrate --force --seed`
8. run `php artisan storage:link`
9. start full Compose stack
10. run backup restore test

Subsequent deployments:
1. `git pull origin develop`
2. host Node builds
3. `docker compose up -d --build php nginx queue-worker scheduler backup`
4. `php artisan migrate --force`
5. `php artisan queue:restart`

`--seed` is required on fresh installs because permissions and menus come from seeders.

## Security Notes

- Cloudflare terminates TLS; origin Docker Nginx only accepts loopback.
- No raw WA token or gateway URL is exposed publicly.
- `APP_DEBUG=false`.
- Trust proxy settings are required so Laravel sees the real protocol.
- Cloudflare Access for SSH should be enabled before relying on public SSH.

## Verification Checklist

- `docker compose ps` all application services healthy
- `curl -I https://smppiringaglik.sch.id/up`
- `curl -I https://cbt.smppiringaglik.sch.id`
- Laravel login, navigation, and admin pages work
- CBT login with demo credentials and exam flow works
- upload and storage path returns files correctly
- queue worker status and scheduler list are correct
- Redis responds with `PONG`
- backup timestamp exists and `gzip -t` succeeds
- backup restored to a fresh MySQL container or database
- WhatsApp status endpoint is reachable locally and token matches

## Open Decisions

- develop is chosen as the initial production branch, ahead of main by 76 commits.

- Exact Cloudflare Access policy for SSH, if used.
- Final domain spelling and whether the main Laravel app uses the apex domain or `admin.` subdomain.
- Backup retention period; 7 days is the initial default.

## Calls Made During Design

- `wa-gateway` remains on host PM2 rather than entering Docker.
- Build frontend assets on the host, not in Node containers.
- Use bind mounts rather than Docker named volumes for all critical data.
- Use a sidecar backup service instead of host cron.

