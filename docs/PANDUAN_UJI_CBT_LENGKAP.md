# Panduan Uji Coba Lengkap — Modul CBT

> Uji komprehensif PRD CBT-01..14 + ROADMAP Fase 1–5. Hasil audit gap 33 langkah manual kini jadi checklist tabel `No | Langkah | Data Uji | Ekspektasi UI/API | Hasil | Bukti`. Pakai data **DemoCbtSeeder** (2 kelas, 10 soal) + akun demo.

## 0. Prasyarat & Env Matrix

| Komponen | Nilai |
|---|---|
| Backend | `composer run dev` → `http://127.0.0.1:8000` ; `docker-compose up -d redis queue-worker` ; `CACHE_STORE=redis` `QUEUE_CONNECTION=redis` `REDIS_HOST=127.0.0.1` |
| Test DB | `phpunit.xml` `QUEUE_CONNECTION=sync` `CACHE_STORE=array` (SQLite :memory:) |
| Frontend CBT | `cbt: npm run dev` → `http://127.0.0.1:5173` proxy `/api → 8000/api/cbt` ; `VITE_API_BASE_URL=http://127.0.0.1:8000/api/cbt` (dev) |
| Storage | `php artisan storage:link` agar `question_image` `storage/cbt/questions/*` tampil |
| Akun | `superadmin / superadmin123` (super-admin), `guru-a / password` (Guru A pengampu MTK 7A), `siswa 0080011001 / 0080011001` (NISN DemoSiswa), token `DEMOMTK*` dari DemoCbtSeeder |

## 1. Setup Sekali

| No | Langkah | Data Uji | Ekspektasi | Hasil | Bukti |
|---|---|---|---|---|---|
| 1.1 | `docker-compose up -d` | — | `redis` + `queue-worker` Up | | `docker ps` |
| 1.2 | `php artisan migrate:fresh --seed` | `DatabaseSeeder` incl. `DemoCbtSeeder` | 2 bank `Bank MTK Demo Guru A/D`, 2 exam `UTS 7A/7B Demo` published, 10 soal snapshot per exam | | `php artisan tinker → QuestionBank::count()` =2 |
| 1.3 | Alternatif manual | `php artisan db:seed --class=DemoCbtSeeder` | Idempotent 2× run tidak duplikat (unique `[guru,mapel,nama]`) | | |
| 1.4 | `php artisan storage:link` | — | `public/storage/cbt/questions/demo-*` terbaca | | |
| 1.5 | `cbt: npm run build` | — | `tsc -b && vite build` hijau 1s | | |

## 2. Matriks Role

| Role | Akun | Hak CBT |
|---|---|---|
| super-admin | `superadmin` | Lihat semua bank/ujian |
| guru pengampu MTK 7A | `guru-a` | Buat/lihat bank MTK, ujian 7A; buat soal di banknya |
| guru non-pengampu | buat user `guru-x` tanpa `pengampus` | `POST banks.store` 403 |
| siswa | `0080011001` | Login React `POST /api/cbt/login` → join |

## 3. Checklist per Fase

### 3.1 Fondasi — 9 Endpoint + Anti-bocor

| No | Langkah | Data Uji | Ekspektasi UI/API | Hasil | Bukti |
|---|---|---|---|---|---|
| 3.1.1 | `GET /api/cbt/exam/questions?exam_session_id=1` tanpa token | — | `401` + interceptor `window.location.href=/login` | | Network 401 |
| 3.1.2 | `POST /api/cbt/login` | `identifier: 0080011001, password: 0080011001` | `200 {token,user}` `cbt-token` `cbt:access` | | |
| 3.1.3 | `GET /questions?exam_session_id=X` setelah join | token `DEMOMTK7A` | JSON `id,question_text,question_image,type,options,score` **tanpa** `correct_answer` | | DevTools Response |
| 3.1.4 | Cache 6h | 2× `GET /questions` sama | Urutan acak per request tapi DB query sekali (`Cache::remember exam:{id}:questions`) | | `tinker → Cache::has(...)` |
| 3.1.5 | Cache forget | `admin/cbt/exams/{id}` update `name` | `Cache::forget` → GET lagi dapat soal baru | | |
| 3.1.6 | Throttle | `POST /exam/heartbeat` 7×/menit | ke-7 → `429` | | |

### 3.2 Auth React

| No | Langkah | Data Uji | Ekspektasi | Hasil |
|---|---|---|---|---|
| 3.2.1 | `/login` kosong password | — | Button disabled | |
| 3.2.2 | `/login` salah password | `password: salah` | `422 Kredensial` `text-red-600` | |
| 3.2.3 | `/login` bukan siswa | `guru-a / password` | `422 Bukan akun siswa` | |
| 3.2.4 | `/login` sukses → `/token` | `0080011001` | `localStorage cbt_auth_token + cbt_user_name`, navigate `/token` | |
| 3.2.5 | Hapus `cbt_auth_token` + reload | — | Hard redirect `/login` (bukan soft) | |

### 3.3 Token Join (7 Kasus)

| No | Langkah | Data Uji | Ekspektasi |
|---|---|---|---|
| 3.3.1 | Join token expired | `active_until` lewat | `422 Token sudah tidak berlaku` |
| 3.3.2 | Token `is_active false` | — | `422 Token tidak ditemukan` |
| 3.3.3 | Exam `draft` | status draft | `422 Ujian belum dipublikasikan` |
| 3.3.4 | `max_usage=1` join 2 user | user2 | `422 Kuota habis` + `used_count` 1 |
| 3.3.5 | Whitelist `exam_participants=[0080011001]` join `0080011002` | — | `422 Tidak terdaftar sebagai peserta` ; kosong = semua boleh |
| 3.3.6 | Rombel mismatch | siswa `7A` join exam `7B` | `422 Tidak terdaftar di rombel` |
| 3.3.7 | Re-join cepat 2× | double-click | `lockForUpdate` → `exam_session_id` sama; jika `finished` → `422 Sudah menyelesaikan` |

### 3.4 Pengerjaan Ujian (Workspace)

| No | Langkah | Data Uji | Ekspektasi |
|---|---|---|---|
| 3.4.1 | `TokenEntry` input `loadtestxxx` | — | Auto `LOADTESTXXX` `maxLength20` monospace `tracking-[0.35em]` |
| 3.4.2 | Fullscreen gate `!isFullscreen` | — | Tombol `Lanjutkan Ujian (Mode Layar Penuh)` → `requestFullscreen` |
| 3.4.3 | Timer Standard/Warning/Critical | `duration 60` → `<10m amber` `<2m red pulse tabular` | `timerClass` `bg-error animate-pulse` vs `bg-secondary-fixed` |
| 3.4.4 | Single choice `radio` | klik A → B | `[A] → [B]` |
| 3.4.5 | Multiple `checkbox` | A→C→A | `[A] → [A,C] → [C]` via `toggleMultiple` |
| 3.4.6 | Essay `textarea rows6` | ketik uraian | `answers[id]=[text]` `placeholder Tulis jawaban uraian...` |
| 3.4.7 | Image | soal dengan `question_image` | `<img max-h-72 border>` tampil (butuh `storage:link`) |
| 3.4.8 | Autosave | pilih opsi | `Menyimpan...` 800ms → `Tersimpan ✓` ; offline → `Gagal menyimpan...` |
| 3.4.9 | Saved_answers pluck | jawab 3 → refresh | `GET /questions saved_answers` prefill tidak hilang ; `violation_count` juga via `GET /exam/active` |
| 3.4.10 | `Ragu-ragu` | klik | Badge `Ragu-ragu secondary-fixed` ↔ `Batal Ragu`, `flagged` di `examStore` |

### 3.5 Timer, Heartbeat, Queue

| No | Langkah | Data Uji | Ekspektasi |
|---|---|---|---|
| 3.5.1 | `expected_end_at` ISO | join | `expected_end_at ≈ now+60m` |
| 3.5.2 | Grace +5s | ubah `expected_end_at=now()-3s` refresh | masih ongoing; `-6s` → `409 Waktu sudah habis` + `time_up` |
| 3.5.3 | Heartbeat | Network tab 20s | `POST /heartbeat {exam_session_id}` tiap 20s `LogViolationDetail` async `last_heartbeat_at` |
| 3.5.4 | `is_online` | `admin/cbt/{exam}/monitoring/data` | `is_online = last_heartbeat_at > now()-30s` dot hijau tiap 5s fetch |
| 3.5.5 | Throttle di UI | spam 7 heartbeat | ke-7 `429` tapi Ujian tetap ongoing |

### 3.6 Proctoring 6 Type

| No | Tipe | Cara Uji | Warning Modal | Hitung Batas |
|---|---|---|---|---|
| 3.6.1 | `fullscreen_exit` | `Esc` | `Pelanggaran terdeteksi: fullscreen_exit` + `Kembali` → `enterFullscreen` | Ya |
| 3.6.2 | `visibility_hidden` | `Alt+Tab` | idem `visibility_hidden` | Ya |
| 3.6.3 | `tab_blur` | klik window lain | `tab_blur` | Ya |
| 3.6.4 | `copy_paste_attempt` | `F12` / `Ctrl+Shift+I` / `Ctrl+C` | `copy_paste_attempt` (block `contextmenu`) | Ya |
| 3.6.5 | `devtools_suspected` | drag DevTools hingga `outer-inner>160` | `devtools_suspected` via `resize` | Ya |
| 3.6.6 | `connection_lost` | DevTools Network Offline | `connection_lost` **tidak** increment `violation_count` (server & `useExamGuard` kecualikan) | Tidak |
| 3.6.7 | Threshold | `max_violation_count=3` langgar 3× (kecuali offline) | auto `finishExam violation_limit` → `Finished tone danger` | |
| 3.6.8 | BeforeUnload | reload di ExamRoom | native `Apakah Anda yakin?` + `contextmenu` disable | |

### 3.7 Bank Soal (CBT-01..04)

| No | Langkah | Data Uji | Ekspektasi |
|---|---|---|---|
| 3.7.1 | `admin/cbt/banks` sebagai `guru-a` | — | Hanya lihat `Bank MTK Demo Guru A` ; `superadmin` lihat semua |
| 3.7.2 | Filter `?mapel=MTK` | — | dropdown `Mata Pelajaran` filter |
| 3.7.3 | Buat bank mapel tidak diampu | `guru non-pengampu` | `403 Anda tidak mengampu` |
| 3.7.4 | `is_shared` | checkbox | — |
| 3.7.5 | Tambah soal essay | — | `exam_id null` di bank |
| 3.7.6 | Impor snapshot | `admin/cbt/exams/{exam}/banks/{bank}/import` ceklis 10 | `exam.questions count +10` `source_question_id` jejak ; edit bank Q1 text → exam snapshot tetap lama |
| 3.7.7 | Hapus bank | `DELETE banks/{bank}` | `nullOnDelete` snapshot exam tetap |
| 3.7.8 | `board` validation | `bank.mata_pelajaran_id !== exam.mata_pelajaran_id` | error `Bank dan ujian harus mapel sama` |

### 3.8 Monitoring & Hasil

| No | Langkah | Data Uji | Ekspektasi |
|---|---|---|---|
| 3.8.1 | `monitoring/data` | — | `sessions[] {student_name,status,online,remaining_seconds,violation_count,answered_count,score}` + `summary` fetch 5s |
| 3.8.2 | Force | `POST .../sessions/{session}/force` | `Cache lock force:{id} 10s` → `status admin_force` |
| 3.8.3 | CSV violations | `violations.csv` | header `exam_session_id,user,type,occurred_at,meta` |
| 3.8.4 | Hasil matriks | `admin/cbt/exams/{exam}/results` | tabel `Nama,NIS,Status,Skor,Q{id}` + export `hasil-{exam}.csv` BOM + header `Q{id} ({score}p)` |
| 3.8.5 | Essay nilai | `POST answers/{answer}/nilai {score_obtained}` 0-100 | `session.score = sum score_obtained` |

## 4. Negative & Edge Lanjutan

| No | Kasus | Ekspektasi |
|---|---|---|
| 4.1 | `expected_end_at +5s` grace | `resolveOngoingSession` `time_up` `409` bukan client auto |
| 4.2 | `Cache::lock` force race | 2 admin force bersamaan → satu `Sesi sedang diproses.` |
| 4.3 | `toUpperCase 20char` TokenEntry | `tracking-[0.35em] font-mono` |
| 4.4 | Refresh mid-exam | `GET /exam/active violation_count` tidak reset |
| 4.5 | Matikan jaringan → `Selesaikan` | `flush()` + `finishExam` retry 2× backoff 800ms tetap terkirim ; server grace finalisasi |

## 5. Visual Regression (DESIGN.md)

| Elemen | Token |
|---|---|
| Palet | `primary #1E40AF/#00288e`, `secondary #F59E0B/#fea619`, `tertiary #10B981/#00563a`, `surface #f8f9ff`, `on-surface #0B1C30`, `outline #757684`, `error #dc2626` |
| Tipografi | `Plus Jakarta Sans` headline, `Inter` timer `22/28 700 tabular` |
| Layout | Desktop 70/30 min 320px `grid 5 cols 40×40` `answered #10B981` `flagged #F59E0B` `active 2px #1E40AF` |
| Timer | Standard `bg-surface-container` → Warning `<10m amber` → Critical `<2m red pulse tabular` |
| Mobile/Tablet | Matrix `hidden lg:block`, periksa `lg:w-[70%]` |

## 6. Beban Staging (Jangan di Lokal)

```powershell
php artisan db:seed --class=LoadTestSeeder
k6 run -e BASE_URL=http://127.0.0.1:8000/api/cbt -e EXAM_TOKEN=LOADTESTXXXX -e VUS=500 load-test-cbt.js
# threshold p95<1500ms http_req_failed<2%
php artisan tinker --execute "App\Models\Exam::where('name','like','LOAD TEST%')->delete()"
```

## 7. Suite & Build

```powershell
vendor/bin/pint --dirty --format agent
php artisan test --compact  # QuestionBank 6 + CBTAdmin 5 + CbtApi 5 = 16 hijau (+ Demo seeder idempotent)
cbt: npm run build         # tsc -b && vite build hijau 1s
php artisan migrate:fresh --seed  # DemoCbtSeeder idempotent 2×
```

## Lampiran — Contoh Curl

```bash
# join
curl -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" -d '{"token":"DEMOMTK7A01"}' http://127.0.0.1:8000/api/cbt/exam/join
# questions (cek tanpa correct_answer)
curl -H "Authorization: Bearer $TOKEN" "http://127.0.0.1:8000/api/cbt/exam/questions?exam_session_id=1" | grep -v correct_answer
# violation
curl -H "Authorization: Bearer $TOKEN" -d '{"exam_session_id":1,"type":"visibility_hidden"}' http://127.0.0.1:8000/api/cbt/exam/violation
```

