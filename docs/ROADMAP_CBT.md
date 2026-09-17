# Roadmap Modul CBT

> Sumber: `docs/PRD.md` §6.3 (CBT-01–14) + `cbt-project` (backend-laravel 7 migrasi, 5 Api/Cbt controllers, ExamQuestionResource, 2 jobs, `routes/api_cbt.php`, frontend-react Vite+TS, `desain-halaman-cbt/modern_cbt_assessment_engine/DESIGN.md` tokens) + load-test k6 500 VU.
> Keputusan terbaru (2026-09-17): **Bank Soal per Mata Pelajaran oleh Pengampu** — `question_banks(mata_pelajaran_id, guru_id)` lintas rombel/tahun, copy-snapshot saat susun ujian, private per guru + admin lihat semua.
> Aturan: **satu fase satu eksekusi — jangan lanjut fase berikutnya sebelum konfirmasi.**

## Keputusan

| # | Keputusan |
|---|---|
| 1 | Copy `exams_*` verbatim (`exams`, `exam_questions`, `exam_tokens`, `exam_participants`, `exam_sessions`, `exam_answers`, `exam_violations`) — 7 migrasi `2026_09_15_000001..07` + `rombel_id` |
| 2 | Hybrid login Sanctum (`cbt-token`, scope `cbt:access`, role `siswa`) + kode per ujian auto-generate (12 char uppercase, `active_from/until`, `max_usage`) |
| 3 | Acak soal & opsi (per request `shuffle_questions`/`shuffle_options`, cache `exam:{id}:questions` 6h, `Cache::forget` saat admin edit) |
| 4 | Timer server-authoritative (`expected_end_at = started_at + duration`, grace +5s, `resolveOngoingSession`), client countdown hanya UI |
| 5 | Redis sejak fase 1 (`CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis` opsional, `throttle:heartbeat 6/min`, `Cache::lock` join/submit) |
| 6 | `cbt/` Vite React-TS monorepo `5173` proxy `backend 8000/api/cbt`, Tailwind v3 terisolasi (backend v4 tetap) |
| 7 | Monitoring guru Blade fetch 5s (bukan Livewire), `remaining_seconds`, `is_online <30s`, `violation_count` |
| 8 | **Bank Soal**: `question_banks(mata_pelajaran_id, guru_id, nama)` unique `[guru, mapel, nama]`, `is_shared bool`, lintas rombel/tahun; `exams.mata_pelajaran_id` + `exam_questions.question_bank_id/source_question_id` + `exam_id` nullable untuk soal bank; copy-snapshot via `POST admin/cbt/exams/{exam}/banks/{bank}/import` |
| 9 | Izin guru: `guru` role diberi `cbt.view` + `cbt.manage`, scoped via `QuestionBankPolicy`/`ExamPolicy` cek `pengampus(guru, mapel)` & `pengampus(guru, rombel, mapel)` |
| 10 | Menu CBT: `Bank Soal` (`admin.cbt.banks.index`) + `Ujian` (`admin.cbt.exams.index`) di `MenuSeeder` |

## Desain Halaman (dari `modern_cbt_assessment_engine/DESIGN.md`)

* **Palet:** `surface #f8f9ff`, `primary #1E40AF/#00288e`, `secondary #F59E0B/#fea619`, `tertiary #10B981/#00563a`, `on-surface #0B1C30`, `outline #757684`; `elevation` tonal.
* **Tipografi:** `Plus Jakarta Sans` headline/body, `Inter` timer `22/28 700 tabular` & grid `14/18 600`.
* **Layout:** Desktop 70-75% workspace / 25-30% matrix min 320px; Tablet single+drawer; Mobile column+bottom sheet. Grid `5 cols`, cell 40×40/44×44, answered `#10B981`, flagged `#F59E0B`, active `2px #1E40AF`.
* **Halaman:** Login, TokenEntry (`toUpperCase Max 20`), ExamRoom (stimulus + opsi A–E + `Ragu-ragu` amber + `Selesaikan Ujian` → modal 23 kosong/3 ragu), Finished (reason `manual/time_up/violation_limit/admin_force`).

## Fase

### Fase CBT-1 — Fondasi [SELESAI]

- [x] `docker-compose.yml` Redis 7 + `backend/.env.example` `CACHE_STORE/QUEUE=redis` + `REDIS_*` + `backend/bootstrap/app.php` `api/cbt` + `RateLimiter heartbeat`
- [x] 7 migrasi `2026_09_15_000001..07` + model `Exam*` + `ExamQuestionResource` (omit `correct_answer`, shuffle) + `HasApiTokens`
- [x] `routes/api_cbt.php` (9 endpoint) + Jobs `RecordHeartbeat` + `LogViolationDetail` (`ShouldQueue`)
- [ ] VPS tuning `server-config/{php-fpm-pool.conf,mysql-tuning.cnf}` + `SUPERVISOR` queue:work (menyusul staging)

### Fase CBT-2 — API React (timer aman) [SELESAI]

- [x] `cbt/` Vite React-TS (`services/api,examApi,store/examStore,hooks/useExamGuard,useAutosaveAnswer,pages/Login,TokenEntry,ExamRoom,Finished` + `App.tsx` guard, `tailwind.config.js` v3, `VITE_API_BASE_URL`)
- [x] Kontrak tanpa kunci, `expected_end_at` server, `resolveOngoingSession` +5s, `lockForUpdate` join, `Cache::remember` 6h, throttle, `visibilitychange/blur/fullscreen_exit` → `POST /violation`

### Fase CBT-3 — Guru paket + monitoring [SELESAI]

- [x] `admin/cbt/*` CRUD `exams/tokens/questions` (`Cache::forget`), `ExamMonitoringController` Blade fetch 5s + `violations.csv` + `admin_force` + `answers/nilai` essay
- [x] `CBTAdminTest` 5 test hijau, full suite 374 hijau (saat itu)
- [x] `MenuSeeder` CBT header + `Ujian`, `PermissionSeeder` `cbt.view/manage`

### Fase CBT-4 — Bank Soal per Mapel oleh Pengampu (copy-snapshot) [SELESAI — 2026-09-17]

- [x] Migrasi `2026_09_17_075214_create_question_banks_table` + `075216_add_mata_pelajaran_id_to_exams` + `075217_add_bank_refs_to_exam_questions` + `075446_make_exam_id_nullable`
- [x] Model `QuestionBank` + update `Exam(mataPelajaran)` + `ExamQuestion(bank,sourceQuestion)` + `Guru::questionBanks`
- [x] Policy `QuestionBankPolicy` / `ExamPolicy` cek `pengampus` lintas rombel; `guru` diberi `cbt.view/manage`
- [x] Controller `QuestionBankController` + `QuestionBankQuestionController` + `ExamController@importFromBank` (replicate + `Cache::forget`)
- [x] Route `admin.cbt.banks.*` + `banks.questions.*` + `exams.banks.import` di `routes/web.php`
- [x] Blade `admin/cbt/banks/{index,create,edit,show}` + update `admin/cbt/exams/{create,edit,show}` (pilih mapel + impor ceklis bank)
- [x] Test `QuestionBankTest` 6 test (pengampu ok/forbidden, scope, snapshot, hapus bank tak hapus snapshot, unik) + update `CBTAdminTest` (mata_pelajaran_id) — 11 test hijau
- [x] Commit `441730c cbt: bank soal per mapel oleh pengampu (copy-snapshot) + exam mapel scope`

### Fase CBT-5 — Perbaikan & Hardening (gabungan) [BELUM — eksekusi bertahap 3 sub-fase]

#### 5a. Blokir fungsional React [BELUM]

- [ ] Tailwind token `cbt/tailwind.config.js` `theme.extend.colors` (`primary #1E40AF`, `secondary #F59E0B`, `tertiary #10B981`, `surface #f8f9ff` dll.) + `fontFamily jakarta/inter` — `cbt/src/index.css` tetap `@tailwind`
- [ ] `cbt/src/pages/ExamRoom.tsx`: essay `<textarea>` + `question_image <img>` render
- [ ] `cbt/src/store/examStore.ts` + `useExamGuard.ts` + `useAutosaveAnswer.ts`: violation sync (`active` sertakan `violation_count`), flush autosave sebelum `finishExam`, retry `finishExam` dengan backoff
- [ ] `backend/app/Http/Controllers/Api/Cbt/ExamSessionController.php:active` tambah `violation_count` + `cbt/src/App.tsx:checkActiveSession` teruskan ke guard

#### 5b. Paritas desain [BELUM]

- [ ] `cbt/src/pages/ExamRoom.tsx` + `cbt/src/store/examStore.ts`: matrix 70/30 grid `5 cols 40×40` (answered `#10B981`, flagged `#F59E0B`, active `2px #1E40AF`) + flagged `Ragu-ragu` amber + modal `Selesaikan` (X kosong/Y ragu)
- [ ] `backend/app/Http/Controllers/Api/Cbt/ViolationController.php` pisah `connection_lost` dari `max_violation_count`; `cbt/src/hooks/useExamGuard.ts` heuristik `devtools_suspected` via `resize`

#### 5c. Hasil agregat & beban CBT-14 [BELUM]

- [ ] `admin/cbt/exams/{exam}/hasil` matriks siswa×soal + koreksi inline essay + export Excel/PDF nilai (reuse `ExamAnswerController:update`)
- [ ] `docker-compose.yml` service `queue-worker` (`php artisan queue:work --sleep=3 --tries=3`) atau `supervisor.conf` + `question_image` storage `php artisan storage:link`
- [ ] `load-test-cbt.js` k6 500 VU (`BASE_URL, EXAM_TOKEN, VUS 500, RAMP 15s, HOLD 10m, HEARTBEAT 20s`) threshold `p95<1500ms, http_req_failed<2%` vs `LoadTestSeeder` (`php artisan db:seed --class=LoadTestSeeder` → 500 siswa token `LOADTEST`)
- [ ] PHP-FPM tuning `pm.max_children` + MySQL `innodb_buffer_pool_size` doc di `docs/`
- [ ] Tambah `backend/tests/Feature/CbtApiTest.php` (heartbeat/violation/timer/concurrency) target ≥ 385 hijau; `cbt: npm run build` hijau
- [ ] Update `docs/PANDUAN_TESTING_CBT.md` langkah manual per fase

> **Aturan eksekusi Fase 5**: kerjakan **5a → 5b → 5c** berurutan, satu sub-fase satu commit (verifikasi `npm run build` + `pint` + `php artisan test --compact` tiap sub-fase). Fase 5a adalah prioritas pertama.

## Tahapan Perbaikan CBT (ringkas, urut eksekusi)

| Tahap | Judul | Status | Pintu Keluar |
|---|---|---|---|
| 1 | Fondasi (Redis, migrasi, API) | Selesai | 7 tabel, 9 endpoint, jobs queue |
| 2 | React timer aman | Selesai | Login→Token→ExamRoom→Finished jalan |
| 3 | Admin paket & monitoring | Selesai | CRUD ujian, token, soal exam, monitoring 5s, koreksi essay |
| 4 | Bank Soal per Mapel (copy-snapshot) | **Selesai 2026-09-17** | Bank lintas rombel/tahun, impor snapshot, hapus bank aman |
| 5a | Blokir fungsional React | Belum | Essay/image, violation sync, flush/retry |
| 5b | Paritas desain | Belum | Matrix 70/30, flagged, modal |
| 5c | Hasil & beban 500 | Belum | Hasil agregat + k6 p95 + worker |

## Catatan Teknis

* `ExamQuestionResource` satu-satunya outlet soal; jangan `return $questions` raw.
* Whitelist `exam_participants` kosong = semua pemegang token valid boleh join.
* Acak soal per request, cache 6h invalidasi `Cache::forget` saat admin edit.
* Skor `calculateScore` sortir set `given vs correct`.
* Bank `question_banks` unique `[guru, mapel, nama]`; hapus bank `nullOnDelete` — snapshot exam tetap.
* `exam_questions.exam_id` nullable: soal di bank `exam_id=null`, di exam `exam_id` terisi + `question_bank_id/source_question_id` jejak.

