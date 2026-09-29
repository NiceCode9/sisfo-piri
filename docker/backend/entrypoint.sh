#!/bin/sh
set -e

# Entrypoint service `backend`: siapkan Laravel setiap container start.
# Aman diulang (idempoten) — migrate --force hanya menjalankan migrasi yang belum jalan.

php artisan config:clear --quiet || true

# Tunggu MySQL siap (maks ~60 detik) sebelum migrate.
for i in $(seq 1 30); do
    if php artisan db:show --quiet >/dev/null 2>&1; then
        break
    fi
    echo "Menunggu MySQL... ($i/30)"
    sleep 2
done

# Seed hanya bila diminta eksplisit (jangan seed otomatis di prod!).
# Contoh first deploy: SEED_ON_BOOT=true docker compose up -d
if [ "${SEED_ON_BOOT:-false}" = "true" ]; then
    php artisan migrate:fresh --force --seed
else
    php artisan migrate --force
fi

php artisan config:cache --quiet || true
php artisan route:cache --quiet || true
php artisan view:cache --quiet || true
php artisan queue:restart --quiet || true

exec "$@"
