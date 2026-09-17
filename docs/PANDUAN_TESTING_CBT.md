# Panduan Testing Modul CBT

> Langkah uji manual per fase. Asumsi: `docker-compose up -d redis`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `composer run dev` (backend `8000`) + `cbt: npm run dev` (`5173` proxy `8000/api/cbt`), `migrate:fresh --seed` + `LoadTestSeeder` untuk beban.

## Persiapan Umum

```powershell
docker-compose up -d
php artisan migrate:fresh --seed
php artisan db:seed --class=LoadTestSeeder
docker-compose up -d queue-worker   # atau: php artisan queue:work --sleep=3 --tries=3
php artisan storage:link
```

## Fase CBT-1 — Fondasi

1. **Migrasi:** `exams`, `exam_questions` (`correct_answer` JSON), `exam_tokens` (12 char), `exam_sessions` (`expected_end_at`), `exam_answers` (unique session+question), `exam_violations` (6 type), `question_banks` + `personal_access_tokens` terbuat tanpa error.
2. **Sanctum:** `POST /api/cbt/login {identifier: nis/email, password}` → `200 {token}` (bearer `cbt-token`, scope `cbt:access`); `POST /api/cbt/logout` → `204`; `GET /api/cbt/exam/active` tanpa token → `401`.
3. **Token join:** `POST /api/cbt/exam/join {token: 'LOADTEST...'}` tanpa `active_from/until` → `409` bila `status !== published` atau token expired; whitelist `exam_participants` kosong = semua boleh.
4. **Resource anti-bocor:** `GET /api/cbt/exam/questions?exam_session_id=1` → JSON `id,question_text,question_image,type,options,score` **tanpa** `correct_answer`; `Cache::remember exam:{id}:questions` hit, `Cache::forget` setelah admin edit.
5. **Throttle:** `POST /api/cbt/exam/heartbeat` 7×/menit → `429`.
6. **Suite:** `php artisan test --filter=CbtApi` hijau.

## Fase CBT-2 — API React (Timer Aman)

1. **Alur Hybrid:** `cbt: /login` → simpan `cbt_auth_token` → `/token` input kode `max 20 toUpperCase` → `POST /join` → `GET /questions` → `ExamRoom`; refresh → `GET /exam/active` resume (sertakan `violation_count`) tanpa simpan token.
2. **Timer:** `expected_end_at` ISO dari server; `useExamGuard` hitung `remainingSeconds`; `remaining<0 +5s grace` → server `resolveOngoingSession` auto `time_up` + client `finishExam('time_up')` dengan retry.
3. **Autosave:** ketik opsi → `POST /exam/answer` debounce 800ms + `flush()` sebelum `finishExam`, `saved_answers` via `GET /questions` pluck.
4. **Heartbeat:** `POST /exam/heartbeat` tiap 20s (`RecordHeartbeat` async `last_heartbeat_at`), `is_online` di monitoring `<30s`.
5. **Proctoring:** `fullscreenchange` → `fullscreen_exit`, `visibilitychange` → `visibility_hidden`, `blur` → `tab_blur`, `copy_paste_attempt`, `devtools_suspected` via `resize` heuristik, `connection_lost` (tidak hitung batas) → `POST /violation` sync increment (kecuali `connection_lost`) + async `LogViolationDetail`; `>= max_violation_count` → `disqualified`.
6. **Fullscreen gate:** `!isFullscreen` → tombol `enterFullscreen`.

## Fase CBT-3 — Guru

1. **CRUD:** `admin/cbt` buat `exam` (rombel + mapel, `duration_minutes`, `available_from/until`, `max_violation_count 3`, `shuffle_* true`, `status draft→published`), tambah `question` langsung di exam atau via bank, buat `token` (12 char, `active_from/until`, `max_usage`).
2. **Bank Soal:** `admin/cbt/banks` per `(mapel, guru)` lintas rombel/tahun, `unique [guru, mapel, nama]`; tambah soal ke bank (image upload `cbt/questions`), impor ceklis ke exam (copy-snapshot, `question_bank_id/source_question_id`), hapus bank tak hapus snapshot.
3. **Monitoring:** `admin/cbt/{exam}/monitoring` Blade fetch 5s → `sessions[] {student_name, status, online, remaining_seconds, violation_count, answered_count}` + summary.
4. **Koreksi & Hasil:** PG auto `calculateScore` saat `finish`, essay `score_obtained` manual via `POST admin/cbt/answers/{answer}/nilai`; `admin/cbt/{exam}/results` matriks siswa×soal + export CSV `hasil-{exam}.csv` & `violations-{exam}.csv`.

## Fase 5a — Blokir Fungsional React

1. **Tailwind:** `cbt/tailwind.config.js` token `primary #1E40AF`, `secondary #F59E0B`, `tertiary #10B981`, `surface #f8f9ff` + `font jakarta/inter`; `npm run build` hijau.
2. **Essay & Image:** buat bank soal essay + image → impor → `ExamRoom` textarea terisi & `<img>` tampil; `question_image` tersimpan `storage/cbt/questions`.
3. **Violation sync:** `GET /exam/active` kembalikan `violation_count`; refresh ExamRoom hitungan tidak reset; `connection_lost` tidak increment.
4. **Flush & Retry:** jawab soal → langsung `Selesaikan Ujian` → `flush()` terkirim sebelum `finishExam`; matikan jaringan simulasi → `finishExam` retry 2× dengan backoff.

## Fase 5b — Paritas Desain

1. **Matrix 70/30:** `ExamRoom` desktop `70% workspace / 30% matrix min 320px`, grid `5 cols` `40×40` (answered `#10B981`, flagged `#F59E0B`, active `2px #1E40AF` ring); flagged via `Ragu-ragu` amber toggle di `examStore.flagged`.
2. **Modal Selesaikan:** klik `Selesaikan Ujian` → modal `X kosong / Y ragu / Z terjawab` + `Batal` / `Ya, Selesaikan`.

## Fase 5c — Hasil & Beban

1. **Hasil:** `admin/cbt/{exam}/results` + `results.csv` matriks; cek `php artisan test --filter=CbtApi` 5 hijau termasuk `connection_lost` & throttle.
2. **Queue Worker:** `docker-compose up -d queue-worker` (`php artisan queue:work --sleep=3 --tries=3`); `is_online` monitoring hidup.
3. **Tuning:** `docs/server-config/php-fpm-pool.conf.example` & `mysql-tuning.cnf.example` untuk 4GB VPS.

## Beban (Staging Saja)

```powershell
# siapkan token LOADTEST
php artisan db:seed --class=LoadTestSeeder
# jalankan k6 (butuh k6 terinstall: https://k6.io/docs/get-started/installation/)
k6 run -e BASE_URL=http://127.0.0.1:8000/api/cbt -e EXAM_TOKEN=LOADTESTXXXX -e VUS=500 load-test-cbt.js
# threshold p95<1500ms, http_req_failed<2%
# cleanup: php artisan tinker --execute "App\Models\Exam::where('name','like','LOAD TEST%')->delete()"
```

## Desain Halaman (dari `modern_cbt_assessment_engine/DESIGN.md`)

* **Tokens:** `primary #1E40AF`, `secondary #F59E0B` (ragu), `tertiary #10B981` (terjawab), `surface #f8f9ff`, `Inter` timer/grid, `Plus Jakarta Sans` headline; layout Desktop 70/30, grid `5 cols` 40×40/44×44, timer Standard → Warning `<10m` amber → Critical `<2m` red pulse.
* **Verifikasi visual:** Login (`shield_person`, Token 6 char monospace), Ruang Ujian (wacana + opsi A–E `selected #EFF6FF 2px #1E40AF` + `Ragu-ragu` amber + `Selesaikan Ujian` → modal).

## Suite

```powershell
php artisan test --compact  # termasuk QuestionBankTest (6), CBTAdminTest (5), CbtApiTest (5)
cbt: npm run build          # tsc + vite
vendor/bin/pint --dirty
```
