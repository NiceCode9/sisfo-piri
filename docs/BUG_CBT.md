# Analisa & Temuan Bug — Modul CBT (Ujian Elektronik)

**Tanggal audit:** 2026-10-10
**Ruang lingkup:** API siswa CBT (`routes/api_cbt.php`), panel admin CBT (`routes/web.php` → `admin/cbt/*`), model & migrasi tabel ujian, job antrean, dan SPA React di `cbt/`.
**Metode:** pembacaan menyeluruh seluruh source modul (13 controller, 8 model, 11 migrasi, 2 job, 2 policy, 15 file React) + verifikasi dengan menulis test regresi. Tidak ada perubahan kode saat audit.
**Referensi kode:** `backend/app/Http/Controllers/{Api,Admin}/Cbt/**`, `backend/app/{Models,Policies,Jobs}/Exam*`, `backend/routes/{api_cbt,web}.php`, `backend/tests/Feature/**`, `cbt/src/**`.

## Ringkasan

| Severity | Jumlah | Karakteristik |
|---|---|---|
| **Critical** | 3 | Otorisasi gagal total, data nilai siswa bocor lintas guru |
| **High** | 6 | Brute force, kebocoran kunci jawaban, kontrol yang tidak pernah dijalankan |
| **Medium** | 7 | Kerusakan saat dipakai, perhitungan nilai tidak benar, jalur happy-path putus |
| **Low** | 8 | Aset mati, token UI tidak terdefinikan, tidak ada test, mobile tidak terpakai |
| **TOTAL** | **24** | **24 selesai** |

Temuan paling struktural: **`ExamPolicy` sudah ditulis lengkap tetapi tidak pernah dipanggil sekali pun** (C2) — seluruh 16 route admin CBT hanya dijaga `middleware('auth')`, sehingga setiap guru bisa membaca dan mengubah ujian milik guru lain. Dan **jawaban benar tidak divalidasi terhadap opsinya** (M4), sehingga satu salah ketik guru membuat soal bernilai 0 untuk semua siswa tanpa satu pun pesan error.

Legend verifikasi:
- ✅ **Sudah diperbaiki** — sudah ada di working tree, lihat bagian "Status Perbaikan".
- 🔍 **Temuan audit** — hasil pembacaan kode, belum diperbaiki.

---

# Status Perbaikan

Ringkasan akhir: **755 test backend** + **21 test frontend** lulus; `pint`, `tsc --noEmit`, `oxlint`, dan `vite build` bersih; `npm audit --omit=dev` melaporkan **0 kerentanan**.

## Tahap 1 — 9 temuan Critical + High ✅

| ID | Temuan | Status |
|---|---|---|
| C1 | Hapus soal ujian lolos tanpa `authorize()` | ✅ `abort_if($bank === null, 404)` |
| C2 | `ExamPolicy` tidak pernah dipanggil (IDOR 16 route) | ✅ 20 titik `authorize()` |
| C3 | Injeksi nilai lewat `max:100` tetap | ✅ `max:` + bobot soal |
| H1 | Jendela `available_from/until` tidak pernah dijalankan | ✅ dijaga di `join()` |
| H2 | `POST /login` tanpa rate limit | ✅ `throttle:cbt-login` |
| H3 | Tulis tanpa batas + tanpa throttle | ✅ `throttle:cbt-write` + validasi berbatas |
| H4 | `score` dikembalikan ke siswa | ✅ dihapus dari respons `finish` |
| H5 | Siswa bisa mengirim `finish_reason=admin_force` | ✅ dibatasi 3 nilai |
| H6 | Job antrean gagal diam-diam | ✅ `$tries`/`$backoff`/`failed()` |

Verifikasi: 13 test regresi baru di `backend/tests/Feature/CbtIsolationTest.php`. Dengan source di-`git stash`, **11 dari 13 test gagal** — jadi test benar-benar mengunci bug, bukan sekadar lulus.

## Tahap 2 — koreksi & ketahanan ✅

| ID | Temuan | Status |
|---|---|---|
| M1 | Pelanggaran hilang saat offline | ✅ antrean + penghitung dari server |
| M2 | `ExamRoom` crash saat auto-finish | ✅ pisahkan gerbang dari komponen ber-hook |
| M3 | `POST /logout` tidak terpanggil | ✅ `logout()` + pembatalan token server |
| M4 | Kunci jawaban tak divalidasi | ✅ FormRequest + cek silang opsi & tipe |
| M5 | Pilihan jamak all-or-nothing | ✅ normalisasi tipe + kompensasi parsial |
| M6 | Sesi terlantar tak pernah berakhir | ✅ `cbt:expire-sessions` tiap menit |
| M7 | Ujian bisa diulang dengan token lain | ✅ indeks unik per ujian + penanda remedial |
| M8 | `active()` memilih sesi acak | ✅ `orderByDesc` + abort bila sudah lewat |
| M9 | N+1 di monitoring | ✅ `withCount('answers')` |

M7 dikerjakan terpisah setelah keputusan produk — lihat bagian M7 di bawah.

Verifikasi: 32 test regresi baru (14 answer-key + 5 sweeper + 13 isolasi).

## Tahap 3 — kualitas, test, UX ✅

| ID | Temuan | Status |
|---|---|---|
| L1 | Tidak ada test frontend | ✅ vitest + 21 test (guard, autosave, store) |
| L2 | Tidak ada factory model CBT | ✅ 13 factory + 16 test |
| L3 | Ujian mobile tidak terpakai | ✅ panel navigasi layar penuh + layout responsif |
| L4 | Token Tailwind tidak terdefinisi | ✅ `on-surface-variant`; `card-surface` bogus diganti utility asli |
| L5 | Aset Vite sisa | ✅ `App.css`, `src/assets/*`, `public/icons.svg` dihapus |
| L6 | `tsconfig` tanpa `strict` | ✅ `strict: true`, diverifikasi aktif |
| L7 | Soal manual tanpa `question_image` | ✅ + form blade dilengkapi poin/opsi/kunci |
| L8 | `index.html` `lang="en"` | ✅ `lang="id"`, judul & theme-color |

**Temuan tambahan saat Tahap 3:** `tailwindcss`, `postcss`, dan `autoprefixer` berada di `dependencies` (bukan `devDependencies`), sehingga rantai build yang rentan — `chokidar` → `braces`, `postcss-selector-parser`, `source-map-js` — ikut terpasang di produksi. Dipindahkan ke `devDependencies`; `npm audit --omit=dev` kini **0 kerentanan** (sebelumnya 8). Build produksi tetap berhasil.

Ringkasan verifikasi: `vendor/bin/pint`, `tsc --noEmit`, `oxlint`, `vite build` semuanya bersih.

## Tahap 4 — M7: ujian berulang & kebijakan remedial ✅

| ID | Temuan | Status |
|---|---|---|
| M7 | Ujian bisa diulang dengan token lain | ✅ indeks unik per `exam_id` + penanda remedial |

19 test regresi baru di `backend/tests/Feature/CbtRemedialTest.php`; dengan source di-`git stash`, 8 di antaranya gagal — test benar-benar mengunci aturan ini.

# CRITICAL

## C1. Soal ujian bisa dihapus tanpa satu pun pemeriksaan izin ✅

**Lokasi**
- `app/Http/Controllers/Admin/Cbt/QuestionBankQuestionController.php:57-68` — `if ($bank !== null) { $this->authorize('update', $bank); }`
- `routes/web.php:232` — `DELETE banks/questions/{question}`

**Masalah**
`ExamQuestion` serves dua purposes: soal bank (`question_bank_id` terisi, `exam_id` null) dan soal ujian (`exam_id` terisi, `question_bank_id` null). Penghapusannya memakai **satu** route yang hanya mengikat model. Kode lama melompati `authorize()` bila `$bank === null` — dan justru soal ujian itulah yang selalu punya `bank === null`.

**Dampak**
Setiap pemegang `cbt.manage` (role `guru`) bisa menghapus soal ujian **milik guru lain** lewat `DELETE /admin/cbt/banks/questions/{id}`. Kolom `exam_questions.exam_question_id` punya `cascadeOnDelete`, jadi seluruh `exam_answers` siswa ikut terhapus di tengah ujian berjalan — nilai yang sudah dikumpulkan hilang permanen, dan monitoring dashboard langsung tidak sinkron.

**Perbaikan**
Ganti kondisi skip dengan `abort_if($bank === null, 404)`. Route sister `ExamQuestionController::destroy` juga dilindungi `abort_if($question->exam_id === null, 404)` agar dua route tidak saling menoleransi.

---

## C2. `ExamPolicy` lengkap tapi tidak pernah dipanggil — IDOR di 16 route ✅

**Lokasi**
- `app/Policies/ExamPolicy.php` — **dead code**, nol pemanggilan di seluruh `app/`
- `app/Http/Controllers/Admin/Cbt/ExamController.php` — `show`, `edit`, `destroy`, `importFromBank` tanpa cek
- `ExamTokenController`, `ExamQuestionController`, `ExamMonitoringController` (4 method), `ExamResultController` (2 method), `ExamAnswerController` — seluruhnya tanpa cek
- `routes/web.php:229-246` — grup `admin/cbt/*` hanya dibungkus `middleware('auth')`
- Bandingkan `app/Policies/MateriPolicy.php` + `MateriController` — pola yang benar sudah ada di repo ini

**Masalah**
`ExamPolicy` sudah mengimplementasikan `view`/`update`/`delete` dengan logika rombel + pengampu yang tepat, tapi tidak ada satu pun controller memanggilnya. Satu-satunya proteksi adalah permission `cbt.view` / `cbt.manage` — yang **dimiliki setiap guru**, bukan penanda kepemilikan. Permission menjawab "bolehkah guru memakai fitur CBT?", sedangkan yang dibutuhkan adalah "apakah ujian ini miliknya?".

Beberapa tempat memang mencoba menutupi sendiri, tapi tidak lengkap: `ExamController::index()` menyaring daftar dengan query `Pengampu` buatan sendiri; `authorizePengampu()` hanya dipakai di `store()`/`update()`; `show()` menyaring `$banks` tapi **tidak pernah menyaring `$exam` itu sendiri**.

**Dampak**
Guru mana pun yang memegang `cbt.view` bisa:
- membuka halaman ujian mana pun → melihat **nilai dan jawaban seluruh siswa** sekolah (`ExamResultController::index`)
- mengekspor hasil ke CSV (`results.csv`) dan mengunduh log pelanggaran (`violations.csv`)
- memantau sesi ujian yang sedang berjalan di kelas lain secara real-time

Guru mana pun yang memegang `cbt.manage` bisa menghapus ujian, soal, dan token milik guru lain, menyalin bank soal ke ujian orang lain, serta **mengoreksi nilai siswa ujian milik guru lain** (lihat C3).

**Perbaikan (selesai Tahap 1)**
- `ExamPolicy` ditulis ulang memakai `Rombel::terjangkauOleh()` sesuai aturan `.ai/rules/admin-siswa-ortu.md` (sumber kebenaran tunggal, gagal tertutup), bukan daftar rombel buatan sendiri
- 20 titik `$this->authorize()` dipasang di 6 controller, mengikuti pola `MateriController`
- Route bersarang mengotorisasi **exam induk** lebih dulu (`ExamTokenController::destroy` → `$token->exam`)
- `force` memakai `authorize('update')`, bukan `view` — karena ia mengubah nilai siswa

---

## C3. Koreksi nilai tanpa cek kepemilikan dan plafon tetap 100 ✅

**Lokasi**
- `app/Http/Controllers/Admin/Cbt/ExamAnswerController.php:22` — `'score_obtained' => ['required','numeric','min:0','max:100']`
- `app/Http/Controllers/Admin/Cbt/ExamAnswerController.php:31-33` — `$session->update(['score' => $total])`

**Masalah**
Dua masalah terpisah di satu method yang sama. Pertama, tidak ada `authorize()` sama sekali — jawaban di-route-kan lewat `exam_answer_id`, jadi guru bisa mengoreksi jawaban siswa ujian mana pun. Kedua, plafon `100` adalah angka tetap yang tidak berkaitan dengan bobot soalnya.

**Dampak**
Guru dapat menyetel `score_obtained=100` pada jawaban soal bobot-5 milik ujian guru lain, lalu kode menghitung ulang dan menulis `score` sesi. Karena setiap Upsert menulis ulang seluruh sesi, satu requestenough untuk mengubah nilai siswa di kelas yang berbeda. Tidak ada jejak audit siapa yang mengubahnya.

Pada soal bobot 10, angka 100 lolos validasi dan membuat nilai siswa 10× liput. Tidak ada satu pun pemeriksaan yang menggagalkan request ini.

**Perbaikan (selesai Tahap 1)**
Tambahkan `$this->authorize('update', $answer->session->exam)`, dan ubah plafon menjadi `'max:'.$question->score` agar mengikuti bobot soal sebenarnya.

---

# HIGH

## H1. Jendela Availability Ujian Tidak Pernah Dijalankan ✅

**Lokasi**
- `app/Http/Controllers/Admin/Cbt/ExamController.php:66-67,115-116` — form menerima `available_from` / `available_until`
- `app/Models/Exam.php:11,16-18` — keduanya ada di `$fillable` dan `$casts`
- `app/Http/Controllers/Api/Cbt/ExamSessionController.php:61` — hanya memeriksa `active_from` / `active_until` **token**

**Masalah**
Dua kolom jadwal ujian bisa diisi guru dari form create/edit, tampil normal di form, tersimpan ke database — lalu **tidak pernah dibaca di mana pun**. `join()` hanya memeriksa jendela token, bukan jendela ujian.

**Dampak**
Ujian yang dijadwalkan hari Senin tetap bisa dimasuki hari Minggu selama token-nya masih `is_active`. Guru yang memperbarui soal lalu menutup ujian tidak punya cara menutup akses kecuali menghapus token satu per satu. Konsep "jadwal ujian" yang tampil di UI sama sekali tidak ditegakkan.

**Perbaikan (selesai Tahap 1)**
`join()` kini memeriksa `available_from` dan `available_until` sebelum cek status publish.

---

## H2. `POST /api/cbt/login` tanpa rate limit ✅

**Lokasi**
- `routes/api_cbt.php:10` — `Route::post('/login', ...)` tanpa middleware
- `bootstrap/app.php` — hanya `heartbeat`, `pendaftaran`, `elearning-kumpul`, `absensi-scan` yang punya limiter
- Bandingkan `routes/web.php:67` — login panel admin **memakai** `throttle:5,1`

**Masalah**
Endpoint login CBT menerima `identifier` + `password` tanpa batas percobaan, padahal panel admin sudahydia throttle. Kredensial siswa bisa ditebak tanpa batas.

**Dampak**
Sekolah: satu kelas 40 siswa bisa diserang credential-stuffing tanpa terdeteksi, dan karena seluruh siswa berada di balik satu jaringan bersama (NAT), siswa yang sah ikut terkunci kalau limiter dipasang per-IP tanpa pertimbangan.

**Perbaikan (selesai Tahap 1)**
Limiter `cbt-login` = 10/menit per IP, dengan pesan 429 dalam bahasa Indonesia.

---

## H3. Endpoint tulis tanpa rate limit dan tanpa batas ukuran ✅

**Lokasi**
- `routes/api_cbt.php:18,20` — `/exam/answer` dan `/exam/violation` tanpa throttle
- `app/Http/Controllers/Api/Cbt/AnswerController.php:17` — `'answer' => 'required'`
- `app/Http/Controllers/Api/Cbt/ViolationController.php:16` — `'meta' => 'nullable|array'`

**Masalah**
`answer` hanya wajib ada — tidak ada aturan tipe maupun ukuran. Intensitas nyata autosave adalah 800 ms, jadi satu siswa bisa menghasilkan banyak POST per menit. `meta` disimpan sebagai JSON tanpa batas ukuran.

**Dampak**
Satu akun siswa bisa menulis blob JSON berukuran sembarang ke `exam_answers` dan `exam_violations` pada laju tak terbatas. Di luar storage, ini juga menjadi vektor amplifikasi: group `api` tidak punya throttle bawaan pada route ini.

**Perbaikan (selesai Tahap 1)**
Limiter `cbt-write` = 90/menit per user (jauh di atas kebutuhan autosave normal); `answer` dibatasi `array|max:20` dengan tiap elemen `string|max:5000`; `meta` dibatasi `array|max:10`.

---

## H4. `score` dikembalikan ke siswa — kunci jawaban bocor ✅

**Lokasi**
- `app/Http/Controllers/Api/Cbt/ExamSessionController.php:174` — `'score' => $session->fresh()->score`
- `app/Http/Controllers/Admin/Cbt/ExamMonitoringController.php:51` — `'score' => $s->score`

**Masalah**
Respons `POST /exam/finish` mengembalikan nilai akhir. Nilsi disimpan per-soal (`exam_answers.score_obtained`), sehingga totalORE yang dikembalikan adalah jumlah dari komponen yang bisa diisolasi.

**Dampak**
M Lumberjack yang sudah menyelesaikan ujian bisa menyelesaikan satu soal, memanggil `finish` (atau membiarkan waktu habis agar `resolveOngoingSession` auto-finalize), lalu membaca `score` dan membandingkannya dengan Delphi run sebelumnya. Selisihnya-automatis-graduatedpersist ke tebakan berikutnya. Dengan pilihan ganda 4 opsi, dibutuhkan sekitar 2×N percobaan untuk memetakan seluruh kunci — cukup untukgwain Exam 40 soal dalam waktu satuVt, dan tidak ada satu pun permintaan yang terlihat mencurigakan.

Frontend sendiri sudah benar: `Finished.tsx` sengaja tidak menampilkan skor, dan `examStore.ts:9` yang punya `score` adalah **bobot soal**, bukan nilai sesi. Bocornya murni di sisi server.

**Perbaikan (selesai Tahap 1)**
`score` dihapus dari respons `finish`. Panel monitoring guru tetap menampilkan skor karena itu memang gunanya.

---

## H5. Siswa bisa menyamar sebagai pengawas di jejak audit ✅

**Lokasi**
- `app/Http/Controllers/Api/Cbt/ExamSessionController.php:164` — `'finish_reason' => 'required|in:manual,time_up,violation_limit,admin_force'`
- `app/Http/Controllers/Admin/Cbt/ExamMonitoringController.php:77` — `finalizeSession($session, 'admin_force')`

**Masalah**
`admin_force` adalah tindakan moderator yang hanya boleh berasal dari panel admin, tetapi validasi API menerimanya juga dari payload siswa.

**Dampak**
`POST /exam/finish {"finish_reason":"admin_force"}` menghasilkan baris riwayat yang **tidak dapat dibedakan** dari penghentian sungguhan oleh pengawas. Jejak audit menjadi tak berguna tepat saat dibutuhkan: guru mengira siswa dihentikan pengawas, padahal siswa yang menghentikan dirinya sendiri.

Frontend tidak pernah mengirim nilai ini (`examApi.ts` membatasi `reason` ke tiga nilai lawful), jadi tidak ada alasan bisnis untuk menerimanya.

**Perbaikan (selesai Tahap 1)**
Aturan validasi disempitkan ke `manual|time_up|violation_limit`. Panel admin tetap bisa menghasilkan `admin_force` karena tidak lewat endpoint ini.

---

## H6. Job antrean gagal tanpa jejak — jejak audit hilang diam-diam ✅

**Lokasi**
- `app/Jobs/RecordHeartbeat.php` — `ShouldQueue`, tanpa `$tries`/`$timeout`/`$failed()`
- `app/Jobs/LogViolationDetail.php` — idem
- `.env:38` `QUEUE_CONNECTION=database` vs `.env.example:43` `redis`
- `docs/PANDUAN_DEPLOY_VPS.md:230` `queue:work redis` vs `docs/PANDUAN_WORKER_PRODUKSI.md:44` `queue:work database`

**Masalah**
Kedua job CBT bergantung penuh pada worker, tapi tidak punya retry maupun handler kegagalan. Selain itu driver-nya berbeda antara `.env`, `.env.example`, dan dua panduan deploy yang saling bertentangan.

**Dampak**
Kalau driver di `.env` tidak cocok dengan yang dijalankan supervisor, atau worker mati: `last_heartbeat_at` tidak pernah terisi sehingga monitoring selalu menandai siswa offline, dan **rincian pelanggaran hilang permanen** — sementara `violation_count` tetap naik karena increment-nya sinkron di `ViolationController:25`. Guru melihat angka-naik tanpa bukti, dan `violations.csv` tidak bisa dipakai saat sengketa. Pada kelas penuh, 40 siswa × 3 heartbeat/menit = 120 job/menit menumpuk tanpa batas di tabel `jobs`.

**Perbaikan (selesai Tahap 1)**
Tambah `$tries`, `$timeout`, `$backoff`, dan `failed()` yang menulis ke log pada kedua job; samakan `PANDUAN_WORKER_PRODUKSI.md` ke `redis` sesuai `.env.example` dan panduan VPS.

> ⚠️ **Tindakan manual:** `.env` lokal masih `QUEUE_CONNECTION=database`. Job CBT bergantung pada worker — pastikan driver lokal dan produksi konsisten sebelum ujian berikutnya.

---

# MEDIUM

## M1. Pelanggaran diabaikan saat offline — angka client dan server berbeda ✅

**Lokasi** — `cbt/src/hooks/useExamGuard.ts:60-65`; `catch {}` di `:193` (heartbeat)

**Masalah** `reportViolation` dipanggil tanpa antrean. Saat request gagal, error ditelan, tapi counter lokal **tetap naik** (`:50-58`).

**Dampak** Saat koneksi putus sesaat, sebagian pelanggaran hilang permanen dari `exam_violations` sementara siswa tetap melihat penghitung yang lebih tinggi. Guru dan siswa menghitung dengan angka yang berbeda, dan batas `max_violation_count` bisa terlewati.

**Perbaikan (selesai Tahap 2)** `examApi.ts` menyimpan permintaan yang gagal ke antrean dan mengirim ulang saat kesempatan berikutnya. `useExamGuard` menampilkan angka optimistis untuk umpan balik visual, lalu **menggantinya dengan `violation_count` dari respons server** begitu tiba — jadi penghitung siswa tidak pernah menyimpang dari yang tercatat.

---

## M2. `ExamRoom.tsx` crash saat transisi selesai ✅

**Lokasi** — `cbt/src/pages/ExamRoom.tsx:13`

**Masalah** `if (!meta) return null;` dieksekusi **sebelum** `useAutosaveAnswer` (`:15`) dan `useExamGuard` (`:16`). Hook store Zustand tidak memakai middleware persist, dan `onForceFinish` (`:21-24`) memanggil `reset()` yang mennullkan `meta`.

**Dampak** Saat auto-finish terpicu (waktu habis / batas pelanggaran), `reset()` mennullkan `meta` di tengah fase effect → React melempar "Rendered fewer hooks than expected" → **tab ujian blank, bukan menampilkan layar selesai**. Siswa yang tetap di form tidak tahu apakah ujiannya sudah tersimpan. `.oxlintrc.json` sudah menandai `react/rules-of-hooks: error`, tapi `npm run lint` tidak pernah dijalankan di build maupun CI.

**Perbaikan (selesai Tahap 2)** Gerbang `if (!meta) return null` dipindah ke komponen luar (`ExamRoom`), sedangkan semua hook pindah ke `ExamRoomInner`. Saat `reset()` mennullkan `meta`, komponen dalam **di-unmount** dengan utuh — bukan dirender dengan jumlah hook berbeda. `npm run lint` dan `tsc --noEmit` keduanya bersih; build produksi lolos.

---

## M3. Token log-out tidak pernah dipanggil server ✅

**Lokasi** — `cbt/src/pages/Finished.tsx:36-37`; `cbt/src/services/api.ts:18-19`; `backend/app/Http/Controllers/Api/Cbt/AuthController.php:50` — `logout()` tidak pernah terpanggil

**Masalah** Frontend hanya menghapus `localStorage`; token Sanctum tetap valid di server.

**Dampak** `AuthController::logout` ada tapi tak terjangkau dari UI, dan `AuthController::login:37` hanya menghapus token bernama `cbt-token`. Satu-satunya pembersihan terjadi saat login berikutnya. Token yang bocor (XSS, perangkat bersama) tetap hidup **sampai login berikutnya** — bisa berhari-hari setelah ujian selesai.

**Perbaikan (selesai Tahap 2)** `services/api.ts` menambah `logout()` yang memanggil `POST /logout` sebelum menghapus `localStorage`, plus `clearAuthToken()` yang dipakai bersama oleh interceptor 401. `Finished.tsx` memanggilnya di tombol "Kembali ke Login". Kegagalan jaringan tidak memblokir perpindahan layar.

---

## M4. Jawaban benar tidak divalidasi terhadap opsi soal ✅

**Lokasi**
- `app/Http/Controllers/Admin/Cbt/ExamQuestionController.php:29` — `'correct_answer' => ['nullable','array']`
- `app/Http/Controllers/Admin/Cbt/QuestionBankQuestionController.php:35` — idem
- `app/Http/Controllers/Admin/Cbt/ExamController.php:166` — menyalin `correct_answer` apa adanya saat import

**Masalah** Tidak ada satu pun pemeriksaan bahwa kunci di `correct_answer` benar-benar ada di `options[].key`, atau bahwa jumlahnya cocok dengan `type` (single = 1, multiple ≥ 2).

**Dampak** `single_choice` dengan `correct_answer: ["a","b"]` mustahil dijawab benar. Kunci `"Z"` yang tidak ada di opsi juga. Keduanya **skor 0 untuk semua siswa tanpa pesan error apa pun** — guru melihat semua nilai nol dan mengira soalnya susah, bukan salah konfigurasi. Karena tidak ada validasi, bank soal yang sudah ada menyimpan soal rusak yang disalin terus ke ujian berikutnya.

**Perbaikan (selesai Tahap 2)** Trait `ValidasiKunciJawaban` (dipakai `StoreExamQuestionRequest` dan `StoreBankQuestionRequest`) memeriksa silang: kunci wajib ada di `options[].key`, `single_choice` tepat satu kunci, `multiple_choice` minimal dua, tidak ada duplikat, dan soal pilihan wajib punya opsi. `ExamController::importFromBank` kini **melewati** soal yang kuncinya rusak dan memberi tahu guru berapa yang dilewati, alih-alih menyalin kerusakan itu buta ke ujian.

---

## M5. Pilihan jamak bernilai all-or-nothing ✅

**Lokasi** — `app/Http/Controllers/Api/Cbt/ExamSessionController.php:254-263`

**Masalah** `answerMatches()` membandingkan dengan `sort()` lalu `===`. Tidak ada kompensasi parsial, dan tidak ada normalisasi tipe.

**Dampak** Siswa yang benar memilih 3 dari 4 opsi benar tetap dapat 0 — kelonggaran separuh lebih wajar daripada nol. Selain itu `correct_answer` dibaca dari JSON (bisa integer) sementara siswa mengirim kunci opsi sebagai string, sehingga `["1"]` dan `[1]` tidak akan pernah dianggap sama meski maksudnya sama.

**Perbaikan (selesai Tahap 2)** `answerMatches()` diganti `skorUntuk()` + `normalizeKeys()`. Semua kunci dinormalisasi ke string sebelum dibandingkan. Pilihan tunggal tetap all-or-nothing; pilihan jamak memakai rasio `benar / max(kunci, jawaban)` sehingga menjawab semua opsi (termasuk yang salah) menurunkan skor, dan skornya dibatasi `question->score`.

---

## M6. Sesi terlantar tidak pernah berakhir ✅

**Lokasi** — enum `status` memuat `'expired'`, tetapi tidak ada kode yang pernah menulisnya; `routes/console.php` hanya punya `absensi:cek-belum-hadir`

**Masalah** `resolveOngoingSession()` menutup sesi lewat `finalizeSession($session, 'time_up')` yang menulis status `'finished'`, bukan `'expired'`. Nilai `'expired'` tidak pernah dipakai. Sweeper terjadwal tidak ada.

**Dampak** Siswa yang menutup laptop di tengah ujian membiarkan sesinya `ongoing` selamanya. Monitoring menampilkan baris stale dengan `remaining_seconds` negatif, dan `GET /exam/active` mengembalikan sesi itu lagi sehingga siswa tampak "sedang ujian" padahal sudah berhenti. Status `ongoing` juga dipakai `summary` monitoring, sehingga angka siswa aktif tidak pernah turun.

**Perbaikan (selesai Tahap 2)** Perintah `cbt:expire-sessions` (dengan `--dry-run`) menutup sesi yang melewati `expected_end_at`, dijadwalkan `everyMinute()->withoutOverlapping()`. `finalizeSession()` menerima status akhir eksplisit sehingga sweeper menulis status **`expired`** yang selama ini tidak pernah dipakai, sementara `finish_reason` tetap `time_up`. `GET /exam/active` juga menolak mengembalikan sesi yang sudah lewat. Ringkasan monitoring menambah penghitung `expired`.

---

## M7. Ujian bisa diulang dengan token lain ✅

**Lokasi** — `database/migrations/2026_09_15_000005_create_exam_sessions_table.php` — `unique(['exam_token_id','user_id'])`

**Masalah** Pemeriksaan "sudah menyelesaikan ujian ini" discope ke **token**, bukan ke ujian.

**Dampak** Siswa yang sudah menyelesaikan ujian bisa mengulang ujian yang sama memakai token lain untuk ujian tersebut. Token dibuat per jendela waktu, jadi membuat token kedua bukan kejadian langka — dan ini membuka akses ulang.

**Keputusan produk (dipilih pengguna)**
- Larangan total: satu siswa satu sesi per ujian, token berapa pun.
- Remedial harus ujian baru, ditandai eksplisit.
- Sesi `expired` (laptop mati) tetap memblokir — remedial tetap butuh ujian baru.
- Rapor memakai skor terbaik dalam satu rantai remedial.

**Perbaikan**
- **Migrasi** `2026_10_10_230527_skor_unik_sesi_per_ujian_dan_tandai_remedial` — unik pindah ke `['exam_id','user_id']`; tabel `exams` dapat `is_remedial` + `remedial_of_id`.
- **`ExamSessionController::join()`** mencari sesi per `exam_id`, bukan `exam_token_id`. Sesi `ongoing` tetap di-resume lewat token berbeda dengan timer utuh.
- **Panel admin** punya checkbox "Ujian remedial" + dropdown ujian asal. `rapikanRemedial()` menolak asal lintas mapel/rombel, menunjuk dirinya sendiri, dan rantai bercabang.
- **`RiwayatSiswa::rekapCbt()`** mengelompokkan per rantai remedial dan memakai skor tertinggi.

> ⚠️ **Pelajaran dari migrasi ini:** `dropUnique()` di MySQL gagal dengan error 1553 selama FK masih bergantung pada index itu. Test suite memakai SQLite dan **tidak menangkapnya** — 755 test hijau sementara migrasi gagal di MySQL sungguhan. FK harus dilepas sebelum index unik, di `up()` maupun `down()`.

### Mengapa bukan sekadar mengelompokkan per mapel

"Kelompokkan per mapel" saja terlihat lebih sederhana, tapi salah untuk dua penilaian biasa pada mapel yang sama:

| Ujian | Jenis | Skor |
|---|---|---|
| UH-1 | biasa | 55 |
| UH-2 | biasa | 88 |
| Remedial | remedial | 95 |

Pengelompokan per mapel akan merapatkan ketiganya jadi satu baris 95 — padahal UH-1 dan UH-2 adalah dua penilaian sah yang wajib dihitung, bukan salah satu yang dibuang. Karena itu remedial butuh penanda eksplisit.

---

## M8. Sesi aktif dipilih acak ✅

**Lokasi** — `app/Http/Controllers/Api/Cbt/ExamSessionController.php:20-23` — `->first()` tanpa `orderBy`

**Masalah** Karena uniqueness-nya per token (M7), seorang siswa bisa punya lebih dari satu sesi `ongoing`.

**Dampak** `GET /exam/active` mengembalikan sesi tanpa urutan yang ditentukan, sehingga frontend melanjutkan ujian yang mana saja yang kebetulan pertama — bisa bukan ujian yang sedang dikerjakan siswa.

**Perbaikan (selesai Tahap 2)** Query diurutkan `started_at DESC, id DESC` sehingga sesi terbaru yang selalu dipilih. Sesi yang waktunya sudah lewat juga difinalisasi dan dilaporkan `active: false`, bukan ditawarkan ke frontend.

---

## M9. N+1 di monitoring yang di-poll terus-menerus ✅

**Lokasi** — `app/Http/Controllers/Admin/Cbt/ExamMonitoringController.php:50` — `$s->answers()->count()` per sesi, tanpa pagination

**Masalah** `data()` dipanggil berkala oleh halaman monitoring, mengambil seluruh sesi beserta relasinya.

**Dampak** Di kelas penuh, tiap poll menembak satu query tambahan per siswa. Pada 40 siswa itu 40 query sia-sia tiap beberapa detik, hanya untuk menghitung angka yang bisa diambil lewat `withCount`.

**Perbaikan (selesai Tahap 2)** `withCount('answers')` menggantikan `$s->answers()->count()` per sesi. Eager-load `violations` yang tidak pernah dibaca ikut dibuang.

---

# LOW

| ID | Temuan | Status | Perbaikan |
|---|---|---|---|
| **L1** | Tidak ada test frontend sama sekali | ✅ | vitest 4 + jsdom; 21 test untuk `useExamGuard`, `useAutosaveAnswer`, `examStore`; `npm test` |
| **L2** | Tidak ada factory model CBT | ✅ | 13 factory (8 CBT + Kelas/TahunAjaran/Guru/MataPelajaran/Rombel sebagai prasyarat); default soal **sah** sehingga test tidak lagi menulis kunci di luar opsi |
| **L3** | Ujian mobile tidak bisa dipakai | ✅ | `QuestionNavigator` dipakai sidebar desktop **dan** panel layar penuh di mobile; header membungkus, padding adaptif |
| **L4** | Token Tailwind dipakai tapi tidak didefinisikan | ✅ | `on-surface-variant` ditambahkan; `card-surface` (tunggal, mustahil dari 3 namespace) diganti utility asli di `TokenEntry.tsx` |
| **L5** | Aset Vite sisa | ✅ | `App.css` (184 baris), `src/assets/*`, `public/icons.svg` dihapus; `README.md` ditulis ulang |
| **L6** | `tsconfig` tanpa `strict` | ✅ | `strict: true`; kode sudah memenuhi tanpa perubahan. Diverifikasi aktif dengan menyuntikkan error lalu mengamatinya terdeteksi |
| **L7** | `ExamQuestionController::store` tidak menerima `question_image` | ✅ | Rules disamakan dengan varian bank; form blade dilengkapi field poin/opsi/kunci yang sebelumnya hilang |
| **L8** | `index.html` `lang="en"` | ✅ | `lang="id"`, judul bermakna, `theme-color` |