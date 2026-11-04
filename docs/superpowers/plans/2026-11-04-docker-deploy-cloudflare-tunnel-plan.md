# Docker Deployment with Cloudflare Tunnel Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a reproducible Docker deployment for Sisfo Piri while keeping Cloudflare Tunnel, Node/npm, and the WA gateway on the host.

**Architecture:** A single Ubuntu host runs a Compose stack with MySQL, Redis, PHP-FPM, Nginx, a queue worker, a scheduler, and a backup sidecar. Nginx listens only on loopback port 8080. Cloudflare Tunnel maps both application domains to that loopback origin and optionally maps SSH. The full Laravel and CBT repositories remain on the host, are built there, and are bind-mounted into the containers.

**Tech Stack:** Docker Compose, PHP 8.3-FPM, MySQL 8, Redis 7, Nginx, Alpine, Bash, Node 20/npm.

**Spec:** `docs/superpowers/specs/2026-11-04-docker-deploy-cloudflare-tunnel-design.md`

## Global Constraints

- Host paths are fixed to `/opt/sisfo-piri` unless the user chooses a different root.
- Production branch is `develop`; clone with `git clone --branch develop`.
- Only `mysql`, `redis`, `php`, `nginx`, `queue-worker`, `scheduler`, and `backup` belong to Compose.
- Node/npm and WA gateway remain native host services.
- Compose publishes only `127.0.0.1:8080:80`; no service publishes 80, 443, 3001, 3306, or 6379 to the public interface.
- Containers use host bind mounts for code, MySQL, Redis, and backups.
- PHP services reach the host PM2 WA gateway through `host.docker.internal:host-gateway`, never through `127.0.0.1`.
- Upload limit is `50M` for Nginx and PHP.
- Backup retention is 7 days unless changed.
- `--seed` is mandatory only on fresh database creation.
- Trusted proxy configuration must make Laravel recognize Cloudflare HTTPS.

## Review Focus

- A queue or schedule task calls `127.0.0.1:3001` inside a container; expect WhatsApp failure unless `host.docker.internal` is used.
- An SSH tunnel to `localhost:3001` may be required during setup; test it explicitly before declaring WA ready.
- A CBT page refresh on a deep route must resolve through SPA `try_files`, not a 404.
- Laravel's `storage/` link must resolve when `backend` is bind-mounted; test an uploaded file URL.
- A MySQL bind mount imported through Docker host permissions must be readable after reboot; test before sign-off.

## File Structure

Create or modify:
- `docker-compose.yml`
- `.env.compose.example`
- `ops/php/Dockerfile`
- `ops/nginx/Dockerfile`
- `ops/nginx/default.conf`
- `ops/backup/Dockerfile`
- `ops/backup/backup.sh`
- `docs/deploy-docker-cloudflare.md`

Modify optionally in Laravel:
- `backend/bootstrap/app.php` if `TrustProxies` defaults do not trust Cloudflare

Do not modify database migrations, controllers, or CBT source code for this deployment plan.

### Task 1: Prepare Host Skeleton and Ownership

**Files:**
- Create: `/opt/sisfo-piri/docker-compose.yml` at deployment time; source in repo root `docker-compose.yml`
- Create: `/opt/sisfo-piri/data/mysql`
- Create: `/opt/sisfo-piri/data/redis`
- Create: `/opt/sisfo-piri/backups`

**Interfaces:**
- Consumes: none.
- Produces: stable host paths used by every other task.

- [ ] **Step 1: Document the host bootstrap sequence**
  Path, ownership, and reboot behavior are fixed here. MySQL volume is chowned to `999:999`; Laravel storage is writable by PHP; backups are writable by the backup service.
- [ ] **Step 2: Add a host bootstrap script to the deployment guide**
  Run:
```bash
sudo mkdir -p /opt/sisfo-piri/{data/mysql,data/redis,backups}
sudo chown -R 999:999 /opt/sisfo-piri/data/mysql
sudo chmod -R 775 /opt/sisfo-piri/backups
sudo chown -R 33:33 /opt/sisfo-piri/backend/storage /opt/sisfo-piri/backend/bootstrap/cache
```
Expected: directories exist and ownership is correct when checked with `ls -ld`.
- [ ] **Step 3: Commit the guide update**
```bash
git add docs/deploy-docker-cloudflare.md
git commit -m "docs: add docker deployment bootstrap"
```

### Task 2: Add Compose Environment Template

**Files:**
- Create: `.env.compose.example`
- Modify at deploy time: `.env.compose`

**Interfaces:**
- Consumes: product secrets supplied by operator.
- Produces: `MYSQL_*`, `APP_KEY`-adjacent values are not stored in Compose except as backend Laravel settings.

- [ ] **Step 1: Define variables once**
  Variables: `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_ROOT_PASSWORD`, `DB_PORT=3306` for internal reference, `BACKUP_RETENTION_DAYS=7`.
- [ ] **Step 2: Check that no secret is hardcoded in Compose**
  Run: `grep -n "password" docker-compose.yml`
  Expected: only variable references such as `${MYSQL_PASSWORD}`; no literal password.
- [ ] **Step 3: Commit**
```bash
git add .env.compose.example
git commit -m "chore: add compose env template"
```

### Task 3: Build the PHP 8.3 FPM Image

**Files:**
- Create: `ops/php/Dockerfile`

**Interfaces:**
- Consumes: PHP packages and Laravel code at runtime.
- Produces: one image for `php`, `queue-worker`, `scheduler`.

- [ ] **Step 1: Install PHP extensions and Composer**
  Extensions: `pdo_mysql`, `redis`, `gd`, `intl`, `zip`, `bcmath`, `xml`, `mbstring`. Keep the image minimal and apt caches cleaned.
- [ ] **Step 2: Verify extension installation**
  Run after first build:
```bash
docker compose run --rm php php -m
```
Expected: `pdo_mysql`, `redis`, `gd`, `intl`, `zip`, `bcmath` all shown.
- [ ] **Step 3: Commit**
```bash
git add ops/php/Dockerfile
git commit -m "chore: add php 8.3 fpm docker image"
```

### Task 4: Build Nginx and Serve Both Applications

**Files:**
- Create: `ops/nginx/Dockerfile`
- Create: `ops/nginx/default.conf`

**Interfaces:**
- Consumes: `backend/public`, `cbt/dist`.
- Produces: HTTP responses on port 80 and FastCGI proxying to `php:9000`.

- [ ] **Step 1: Configure two virtual hosts**
  Use `server_name smppiringaglik.sch.id;` for Laravel and `server_name cbt.smppiringaglik.sch.id;` for CBT.
- [ ] **Step 2: Add front-controller and SPA routing**
  Laravel: `location / { try_files $uri $uri/ /index.php?$query_string; }`
  CBT: `location / { try_files $uri $uri/ /index.html; }`
- [ ] **Step 3: Preserve upload size**
  Set `client_max_body_size 50M;` in the HTTP or both server blocks.
- [ ] **Step 4: Verify with Compose**
  Run after Compose is in place:
```bash
docker compose up -d nginx
curl -I http://127.0.0.1:8080
```
Expected: Nginx responds without a bind error.
- [ ] **Step 5: Commit**
```bash
git add ops/nginx
git commit -m "chore: add nginx image for laravel and cbt"
```

### Task 5: Add Backup Sidecar

**Files:**
- Create: `ops/backup/Dockerfile`
- Create: `ops/backup/backup.sh`

**Interfaces:**
- Consumes: MySQL credentials and `/storage`.
- Produces: `/backups/mysql-*.sql.gz` and `/backups/storage-*.tar.gz`.

- [ ] **Step 1: Implement daily loop**
  The script waits for database readiness, creates dated archives, compresses them, and deletes files older than `BACKUP_RETENTION_DAYS`.
- [ ] **Step 2: Test the archive commands independently**
  Run inside the backup container:
```bash
mysqldump -h mysql -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" | gzip -t
```
Expected: gzip reports success only when MySQL credentials are valid.
- [ ] **Step 3: Commit**
```bash
git add ops/backup
git commit -m "chore: add database and storage backup sidecar"
```

### Task 6: Write the Compose File

**Files:**
- Create: `docker-compose.yml`

**Interfaces:**
- Consumes: environment file and all image definitions.
- Produces: service graph.

- [ ] **Step 1: Define all services**
  Include `mysql`, `redis`, `php`, `queue-worker`, `scheduler`, `nginx`, and `backup`.
- [ ] **Step 2: Add host bridge for WA**
  Add `extra_hosts: ["host.docker.internal:host-gateway"]` to PHP-based services.
- [ ] **Step 3: Use loopback publication**
  Nginx uses `ports: ["127.0.0.1:8080:80"]`.
- [ ] **Step 4: Add health checks and dependencies**
  MySQL and Redis must be healthy before PHP-based services start.
- [ ] **Step 5: Commit**
```bash
git add docker-compose.yml
git commit -m "chore: add docker compose stack"
```

### Task 7: Update Laravel Proxy Trust

**Files:**
- Modify: `backend/bootstrap/app.php`

**Interfaces:**
- Consumes: `TrustProxies` middleware from Laravel.
- Produces: HTTPS recognition behind Cloudflare Tunnel.

- [ ] **Step 1: Inspect current middleware configuration**
  Determine whether Cloudflare proxy ranges are trusted by default in this Laravel version.
- [ ] **Step 2: Add trust if missing**
  Use documented Cloudflare IPv4/IPv6 ranges or a deliberately documented value.
- [ ] **Step 3: Test configuration**
  Run after launch:
```bash
docker compose exec php php artisan config:cache
curl -I https://smppiringaglik.sch.id
```
Expected: an HTTPS redirect/session response rather than mixed-content behavior.
- [ ] **Step 4: Commit**
```bash
git add backend/bootstrap/app.php
git commit -m "feat: trust cloudflare proxy for docker deployment"
```

### Task 8: Document Cloudflare Tunnel

**Files:**
- Create or modify: `docs/deploy-docker-cloudflare.md`

**Interfaces:**
- Consumes: tunnel credentials from Cloudflare.
- Produces: host-level tunnel setup.

- [ ] **Step 1: Install cloudflared natively**
  Do not add a Cloudflare container.
- [ ] **Step 2: Create and route the tunnel**
  Tunnel routes exactly:
  - `smppiringaglik.sch.id -> http://127.0.0.1:8080`
  - `cbt.smppiringaglik.sch.id -> http://127.0.0.1:8080`
  - optional SSH host -> `ssh://localhost:22`
- [ ] **Step 3: Enable and verify the service**
  Run:
```bash
cloudflared service install
systemctl enable --now cloudflared
cloudflared tunnel info sisfo-piri-school
```
Expected: both DNS routes appear active.
- [ ] **Step 4: Commit**
```bash
git add docs/deploy-docker-cloudflare.md
git commit -m "docs: add cloudflare tunnel setup"
```

### Task 9: Document Native Node and WA Gateway

**Files:**
- Create or modify: `docs/deploy-docker-cloudflare.md`

**Interfaces:**
- Consumes: Node 20/npm and repository working trees.
- Produces: host build output and host-only WA service.

- [ ] **Step 1: Install Node 20 and npm**
  No Node image is required in Compose.
- [ ] **Step 2: Build Laravel and CBT**
```bash
npm ci
npm run build
```
Run separately in `backend/` and `cbt/`.
 - [ ] **Step 3: Install Laravel Composer dependencies inside Docker**
```bash
docker compose run --rm php composer install --no-dev --optimize-autoloader
```
Expected: vendor directory is populated before migration.
 - [ ] **Step 4: Set production database and Redis hosts**
```bash
sed -i "s/^DB_HOST=.*/DB_HOST=mysql/" backend/.env
sed -i "s/^REDIS_HOST=.*/REDIS_HOST=redis/" backend/.env
sed -i "s/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/" backend/.env
sed -i "s/^CACHE_STORE=.*/CACHE_STORE=redis/" backend/.env
sed -i "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/" backend/.env
sed -i "s#^WHATSAPP_URL=.*#WHATSAPP_URL=http://host.docker.internal:3001/kirim#" backend/.env
```
Expected: backend/.env no longer points to localhost for containerized services.
Expected final: `/opt/sisfo-piri/backend/public/build/manifest.json` and `/opt/sisfo-piri/cbt/dist/index.html`.
- [ ] **Step 3: Run WA gateway with PM2**
  Set `HOST=127.0.0.1`, `PORT=3001`, `WHATSAPP_TOKEN` matching the backend.
- [ ] **Step 4: Access QR through SSH tunnel**
  Command:
```bash
ssh -L 3001:127.0.0.1:3001 your-user@smppiringaglik.sch.id
```
Then open `http://127.0.0.1:3001/qr` locally in the browser.
- [ ] **Step 5: Commit**
```bash
git add docs/deploy-docker-cloudflare.md
git commit -m "docs: add host node and wa gateway deployment"
```

### Task 10: Document First Deployment and Migration

**Files:**
- Create or modify: `docs/deploy-docker-cloudflare.md`

**Interfaces:**
- Consumes: Compose services, database, storage.
- Produces: a working first deployment.

- [ ] **Step 1: Clone and prepare**
```bash
git clone --branch develop https://github.com/NiceCode9/sisfo-piri.git /opt/sisfo-piri
cd /opt/sisfo-piri
cp .env.compose.example .env.compose
cp backend/.env.example backend/.env
```
- [ ] **Step 2: Start databases**
```bash
docker compose up -d mysql redis
```
Expected: both services become healthy.
- [ ] **Step 3: Migrate and seed**
```bash
docker compose exec php php artisan migrate --force --seed
docker compose exec php php artisan storage:link
```
Expected: no migration errors and seed output completes.
- [ ] **Step 4: Start the application stack**
```bash
docker compose up -d --build
```
Expected: `php`, `nginx`, `queue-worker`, `scheduler`, and `backup` all run.
- [ ] **Step 5: Commit each concrete file separately after implementation; then full stack validation**
  Validation commands are in Task 11.
- [ ] **Step 6: Commit documentation**
```bash
git add docs/deploy-docker-cloudflare.md
git commit -m "docs: add first deployment runbook"
```

### Task 11: Verification and Restore Test

**Files:**
- Create or modify: `docs/deploy-docker-cloudflare.md`

**Interfaces:**
- Consumes: all completed services.
- Produces: evidence that deployment is signed off.

- [ ] **Step 1: Check service health**
```bash
docker compose ps
```
Expected: all services show `running (healthy)` or stable `running`.
- [ ] **Step 2: Check web routes**
```bash
curl -I https://smppiringaglik.sch.id/up
curl -I https://cbt.smppiringaglik.sch.id
```
Expected: successful HTTP responses from Cloudflare.
- [ ] **Step 3: Test upload path**
  Upload one file through Laravel and request its storage URL; then check the refreshed CBT deep route does not 404.
- [ ] **Step 4: Test automated backup**
```bash
ls -lh /opt/sisfo-piri/backups
gzip -t /opt/sisfo-piri/backups/*.sql.gz
```
Expected: at least one MySQL backup and one storage archive.
- [ ] **Step 5: Perform restore drill**
  Restore one MySQL archive into a temporary or empty database and compare key counts; record evidence in the runbook.
- [ ] **Step 6: Update Open Decisions if needed**
  If Cloudflare Access SSH is used, record the exact policy and verify tunnel access.
- [ ] **Step 7: Commit final documentation**
```bash
git add docs/deploy-docker-cloudflare.md
git commit -m "docs: add deployment verification runbook"
```


