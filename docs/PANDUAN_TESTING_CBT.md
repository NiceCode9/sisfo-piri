# Panduan Testing Modul CBT

> Langkah uji manual per fase. Asumsi: `docker-compose up -d redis`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `composer run dev` (backend `8000`) + `cbt: npm run dev` (`5173` proxy `8000/api/cbt`), `migrate:fresh --seed` + `LoadTestSeeder` untuk beban.

## Persiapan Umum

```powershell
docker-compose up -d
php artisan migrate:fresh --seed
php artisan db:seed --class=LoadTestSeeder
php artisan queue:work redis --queue=cbt --tries=3  # atau Supervisor
```

## Fase CBT-1 — Fondasi

1. **Migrasi:** `exams`, `exam_questions` (`correct_answer` JSON), `exam_tokens` (20 char), `exam_sessions` (`expected_end_at`), `exam_answers` (unique session+question), `exam_violations` (6 type) terbuat tanpa error.
2. **Sanctum:** `POST /api/cbt/login {identifier: nis/email, password}` → `200 {token}` (bearer `cbt-token`, scope `cbt:access`); `POST /api/cbt/logout` → `204`; `GET /api/cbt/exam/active` tanpa token → `401` (wipe `cbt_auth_token` di React).
3. **Token join:** `POST /api/cbt/exam/join {token: 'LOADTEST...'}` tanpa `active_from/until` → `409` bila `status !== published` atau token expired; whitelist `exam_participants` kosong = semua boleh.
4. **Resource anti-bocor:** `GET /api/cbt/exam/questions?exam_session_id=1` → JSON `id,question_text,question_image,type,options,score` **tanpa** `correct_answer`; `Cache::remember exam:{id}:questions` hit, `Cache::forget` setelah admin edit.
5. **Throttle:** `POST /api/cbt/exam/heartbeat` 7×/menit → `429`.
6. **Suite:** `php artisan test --filter=CBT` hijau (auth, join, question resource, heartbeat throttle).

## Fase CBT-2 — API React (Timer Aman)

1. **Alur Hybrid:** `cbt: /login` (identifier+password) → simpan `cbt_auth_token` → `/token` input kode `max 20 toUpperCase` → `POST /join` → `GET /questions` → `ExamRoom`; refresh → `GET /exam/active` resume tanpa simpan token (aman).
2. **Timer:** `expected_end_at` ISO dari server; `useExamGuard` hitung `remainingSeconds = (expectedEnd - Date.now())/1000`; `remaining<0 +5s grace` → server `resolveOngoingSession` auto `time_up` `409` + client `finishExam('time_up')`.
3. **Autosave:** ketik opsi → `POST /exam/answer {exam_session_id,exam_question_id,answer:["A"]}` debounce 800ms, `is_correct` null untuk essay, `updateOrCreate`; `saved_answers` via `GET /questions` pluck.
4. **Heartbeat:** `POST /exam/heartbeat` tiap 20s (`LogViolationDetail` async `last_heartbeat_at`), `is_online` di monitoring `<30s`.
5. **Proctoring:** `fullscreenchange` → `fullscreen_exit`, `visibilitychange` → `visibility_hidden`, `blur` → `tab_blur`, `copy_paste_attempt` (block `F12/Ctrl+Shift+I/J/C,V,U`) → `POST /violation` sync `increment(violation_count)` + async `LogViolationDetail`; `>= max_violation_count` → `disqualified` + `finish reason violation_limit`; `beforeunload` confirm.
6. **Fullscreen gate:** `!isFullscreen` → tombol `enterFullscreen` sebelum soal tampil.

## Fase CBT-3 — Guru

1. **CRUD:** `admin/cbt` buat `exam` (rombel, `duration_minutes`, `available_from/until`, `max_violation_count 3`, `shuffle_questions/options true`, `status draft→published`), tambah `question` (type `single/multiple/essay`, `options [{key,text}]`, `correct_answer ["A"]`, `score`), buat `token` (20 char, `active_from/until`, `max_usage`).
2. **Monitoring:** `admin/cbt/{exam}/monitoring` Blade fetch 5s → `sessions[] {student_name, status blue/green/red, online dot, remaining_seconds, violation_count, answered_count}` + summary `ongoing/finished/disqualified`.
3. **Koreksi:** `exam_answers` PG auto `calculateScore` saat `finish`, essay `score_obtained` manual via `admin/cbt/hasil` + `finished_at/score` update.

## Beban (Staging Saja)

```powershell
k6 run -e BASE_URL=http://127.0.0.1:8000 -e EXAM_TOKEN=LOADTESTXXXX -e VUS=500 -e RAMP_UP=15s -e HOLD=10m load-test/load-test-cbt.js
# threshold p95<1500ms, cbt_join p95<3000, http_req_failed<2%, join_failures<10
# cleanup: php artisan tinker --execute "App\Models\Exam::where('name','like','LOAD TEST%')->delete()"
```

## Desain Halaman (dari `modern_cbt_assessment_engine/DESIGN.md`)

* **Tokens:** `primary #1E40AF`, `secondary #F59E0B` (ragu), `tertiary #10B981` (terjawab), `surface #f8f9ff`, `Inter` timer/grid, `Plus Jakarta Sans` headline; layout Desktop 70/30, grid `5 cols` 40×40/44×44, timer Standard slate → Warning `<10m` amber → Critical `<2m` red pulse.
* **Verifikasi visual:** Login (`shield_person`, Token 6 char monospace `tracking-[0.35em]`), Ruang Ujian (wacana + opsi A–E `selected #EFF6FF 2px #1E40AF` + `Soal Sebelumnya/Berikutnya` + `Ragu-ragu` amber + `Selesaikan Ujian` error-red → modal 23 kosong/3 ragu), `font resizer` 3 tier.

## Suite

```powershell
php artisan test --compact  # termasuk CBTAuthTest, CBTJoinTest, CBTAnswerTest, CBTViolationTest, CBTConcurrencyTest
```
