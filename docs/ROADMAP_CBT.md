# Roadmap Modul CBT

> Sumber: `docs/PRD.md` §6.3 (CBT-01–14) + `D:\Web Personal\cbt-project` (backend-laravel 7 migrasi, 5 Api/Cbt controllers, ExamQuestionResource, 2 jobs, `routes/api_cbt.php`, frontend-react Vite+TS, `desain-halaman-cbt/modern_cbt_assessment_engine/DESIGN.md` tokens `primary #00288e/#1e40af`, `secondary #fea619`, `tertiary #00563a`, `Plus Jakarta Sans` + `Inter`, layout 70/30, timer 3 state, grid 40×40) + `load-test` k6 500 VU.
> Aturan: **satu fase satu eksekusi — jangan lanjut fase berikutnya sebelum konfirmasi.**

## Keputusan

| # | Keputusan |
|---|---|
| 1 | Copy `exams_*` verbatim (`exams`, `exam_questions`, `exam_tokens`, `exam_participants`, `exam_sessions`, `exam_answers`, `exam_violations`) — 7 migrasi `2025_01_01_*` |
| 2 | Hybrid login Sanctum (`cbt-token`, scope `cbt:access`, role `siswa`) + kode per ujian auto-generate (20 char uppercase, `active_from/until`, `max_usage`) aktif `jadwal_mulai–selesai` |
| 3 | Acak soal & opsi (per request `shuffle_questions`/`shuffle_options`, cache `exam:{id}:questions` 6h, `Cache::forget` saat admin edit) |
| 4 | Timer server-authoritative (`expected_end_at = started_at + duration`, grace +5s, `resolveOngoingSession`), client countdown hanya UI |
| 5 | Redis sejak fase 1 (`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis` opsional, `throttle:heartbeat 6/min`, `Cache::lock` join/submit) |
| 6 | `cbt/` Vite React-TS monorepo `5173` proxy `backend 8000/api/cbt`, Tailwind v3 terisolasi (backend v4 tetap) |
| 7 | Monitoring guru Blade fetch 5s (bukan Livewire `wire:poll`), `remaining_seconds`, `is_online <30s`, `violation_count` |

## Desain Halaman (dari `modern_cbt_assessment_engine/DESIGN.md` + `desain-halaman-cbt`)

* **Palet:** `surface #f8f9ff`, `primary #1E40AF`, `secondary #F59E0B`, `tertiary #10B981`, `on-surface #0B1C30`, `outline #757684`; `elevation` tonal `#F8FAFC→#F1F5F9`, `1px #E2E8F0`.
* **Tipografi:** `Plus Jakarta Sans` headline/body, `Inter` timer `22/28 700 tabular` & grid `14/18 600`.
* **Layout:** Desktop 70-75% workspace / 25-30% matrix min 320px; Tablet single+drawer; Mobile column+bottom sheet. Grid `5 cols`, cell 40×40/44×44, answered `#10B981`, flagged `#F59E0B`, active `2px #1E40AF`.
* **Halaman:** Login (`Nomor Peserta/Username + Password + Token 6 char monospace`), TokenEntry (`toUpperCase Max 20`), ExamRoom (stimulus + opsi A–E + `Ragu-ragu` amber + `Selesaikan Ujian` error-red → modal konfirmasi 23 kosong/3 ragu), Finished (reason `manual/time_up/violation_limit/admin_force`).

## Fase

### Fase CBT-1 — Fondasi (kritis) [SELESAI]

- [x] `docker-compose.yml` Redis 7 + `backend/.env.example` `CACHE_STORE/QUEUE=redis` + `REDIS_*` + `backend/bootstrap/app.php` `api/cbt` + `RateLimiter heartbeat`
- [x] 7 migrasi `2026_09_15_000001..000007` (`exams`+`rombel_id`, `exam_questions`, `exam_tokens`, `exam_participants`, `exam_sessions`, `exam_answers`, `exam_violations`) + model `Exam*` + `ExamQuestionResource` (omit `correct_answer`, shuffle) + `HasApiTokens` di `User`
- [x] `routes/api_cbt.php` (9 endpoint) + `backend/bootstrap/app.php` `api/cbt` + `RateLimiter heartbeat 6/min`
- [x] Jobs `RecordHeartbeat` + `LogViolationDetail` (`ShouldQueue`), `QUEUE_CONNECTION=redis` (test `sync`, prod `redis` via `docker-compose.yml`)
- [ ] VPS tuning `server-config/{php-fpm-pool.conf,mysql-tuning.cnf}` sesuaikan, `SUPERVISOR` queue:work (menyusul staging)

### Fase CBT-2 — API React (timer aman) [SELESAI]

- [x] `cbt/` Vite React-TS (`npm create vite`, copy `frontend-react/src/{services,store,hooks,pages}`, `App.tsx` `RequireAuth` + `hasSession`, `vite.config.ts` proxy `5173→8000/api`, `tailwind.config.js` v3, `npm run build` OK)
- [x] Kontrak tanpa kunci (`ExamQuestionResource`), `expected_end_at` server, `resolveOngoingSession` +5s, `lockForUpdate` join, `Cache::remember` 6h, throttle, proctoring `visibilitychange/blur/fullscreen_exit` → `POST /violation`

### Fase CBT-3 — Guru (paket + koreksi) [BELUM]

- [ ] `admin/cbt/*` CRUD `exams/tokens/questions/participants` (`Cache::forget` saat edit), `ExamMonitoringController` Blade `remaining_seconds/is_online` fetch 5s
- [ ] `LoadTestSeeder` 500 siswa + `students.json` + `load-test-cbt.js` k6 500 VU (`BASE_URL, EXAM_TOKEN, VUS 500, RAMP 15s, HOLD 10m, HEARTBEAT 20s`) threshold `p95<1500ms, http_req_failed<2%`

## Catatan Teknis

* `ExamQuestionResource` satu-satunya outlet soal; jangan `return $questions` raw.
* Whitelist `exam_participants` kosong = semua pemegang token valid boleh join (sesuai `ExamSessionController:68`).
* Acak soal per request, cache 6h invalidasi saat admin edit.
* Skor `calculateScore` sortir set `given vs correct`.
