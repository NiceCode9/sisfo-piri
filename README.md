# Sisfo Piri — Monorepo

Sistem Informasi Sekolah SMKN Ngaglik.

```
sisfo-piri/
  backend/     # Laravel 13 (SPMB, Akademik, Absensi, E-Learning, CBT API)
  wa-gateway/  # Node whatsapp-web.js (colocated 127.0.0.1:3001)
  cbt/         # (kosong, disiapkan untuk frontend React CBT)
  docs/        # PRD, roadmap, panduan worker & testing (terpusat)
```

## Quick start

```powershell
# Backend
cd backend
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
composer run dev

# WA Gateway (dev)
cd ../wa-gateway
copy .env.example .env
npm install
npm run dev  # scan QR http://127.0.0.1:3001/qr
```

## Docs

- `docs/PRD.md` — Product Requirements
- `docs/ROADMAP_ABSENSI.md` — Roadmap Absensi
- `docs/PANDUAN_TESTING_ABSENSI.md`
- `docs/PANDUAN_WORKER_PRODUKSI.md`
- `docs/ALUR_ROMBEL.md`
