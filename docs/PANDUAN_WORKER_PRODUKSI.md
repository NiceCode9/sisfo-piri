# Panduan Worker & Scheduler Produksi

> Untuk modul Absensi A4 (notifikasi WA). Berlaku untuk Laravel 11/13.

## Ringkasan

| Proses | Dijalankan via | Perintah | Fungsi |
|---|---|---|---|
| Scheduler | **cron** (tiap menit) | `php artisan schedule:run` | Memicu `routes/console.php` — di app ini `absensi:cek-belum-hadir` tiap 5 menit (self-gating jam `pengaturans.jam_cek_belum_hadir`) |
| Queue worker | **Supervisor** (long-running) | `php artisan queue:work` | Eksekusi `app/Jobs/KirimNotifikasiWhatsapp.php` dari tabel `jobs` |

Jangan tukar: scheduler tidak butuh Supervisor, worker jangan via cron.

## Env

```env
QUEUE_CONNECTION=database
WHATSAPP_URL=https://service-wa.internal/kirim   # kosong = mode log-only
```

Kosong → `notifikasi_logs.respons = "Gateway belum dikonfigurasi (mode log-only)."` (normal untuk testing tanpa service WA).

## 1. Scheduler — cron

```cron
* * * * * cd /path/sisfo-piri/backend && php artisan schedule:run >> /dev/null 2>&1
```

Cek:

```bash
php artisan schedule:list
php artisan absensi:cek-belum-hadir --tanggal=2026-09-12  # manual, bypass gating
```

Jam cek bisa diubah admin via `pengaturans.jam_cek_belum_hadir` (mis. `08:00`), tanpa ubah cron.

## 2. Queue worker — Supervisor

`/etc/supervisor/conf.d/sisfo-worker.conf`:

```ini
[program:sisfo-worker]
command=php /path/backend/artisan queue:work database --sleep=3 --tries=3 --max-time=3600 --queue=default
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/backend/storage/logs/worker.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sisfo-worker
supervisorctl status sisfo-worker
```

Deploy (setiap `git pull`):

```bash
php artisan queue:restart
php artisan config:cache
```

Gagal:

```bash
php artisan queue:failed
php artisan queue:retry all
```

## Alternatif tanpa Supervisor (shared hosting)

Bila tidak ada akses Supervisor, pakai cron tiap menit:

```cron
* * * * * cd /path/backend && php artisan queue:work --once --queue=default >> /dev/null 2>&1
* * * * * cd /path/backend && php artisan schedule:run >> /dev/null 2>&1
```

Lebih boros (boot tiap menit) tapi berfungsi.

## Verifikasi end-to-end

1. Absensi → tandai 1 siswa Alpa → Simpan.
2. `select tujuan,pesan,status,respons from notifikasi_logs order by id desc limit 1;`
   Tanpa worker: `status=antri, respons=log-only`; setelah `queue:work --once`: `terkirim/gagal`.
3. `php artisan absensi:cek-belum-hadir --tanggal=YYYY-MM-DD` → hanya siswa tanpa absensi yang dibuatkan log.

## Catatan

- Tabel `jobs` / `failed_jobs` dari `0001_01_01_000002_create_jobs_table.php`.
- Di test suite `phpunit.xml:QUEUE_CONNECTION=sync` sehingga tidak butuh worker.
