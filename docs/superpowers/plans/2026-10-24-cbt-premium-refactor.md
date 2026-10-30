# CBT Premium UI Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menata ulang seluruh UI CBT (Login, Token Entry, Exam Room, Finished) menjadi nuansa premium: tipografi elegan, aksen terbatas, ruang putih sebagai struktur, tanpa mengubah logika bisnis.

**Architecture:** Refactor visual berbasis Tailwind + token CSS yang sudah ada. Tidak menambah dependency. Komponen tetap sama, hanya kelas, spacing, tipografi, dan hierarki visual yang berubah. Semua state handling, aksesibilitas, dan logika tetap dipertahankan.

**Tech Stack:** React 18, Tailwind CSS, Vite, TypeScript.

**Spec:** `docs/superpowers/specs/2026-10-24-cbt-premium-refactor-design.md`

## Global Constraints

- Tidak menambah dependency baru.
- Semua halaman responsif mobile (R-03).
- Kontras WCAG AA (R-25).
- Semua state (loading, error, empty, disabled) tetap ditangani (R-27).
- Keyboard accessibility tetap berfungsi (R-32).
- Build frontend harus berhasil.
- Lulus antislop Delivery Gate.

## Review Focus

- Kontras teks pada background premium (terutama aksen biru tua + netral hangat).
- Timer digit tetap tabular dan mudah dibaca.
- Opsi jawaban tetap jelas dan tidak kehilangan state selected.
- Modal dan navigator tetap usable di mobile.
- Tidak ada klaim palsu (R-36/R-38) di Login.

---

### Task 1: Token Visual Premium (CSS)

**Files:**
- Modify: `cbt/src/index.css`
- Modify: `cbt/tailwind.config.js` (jika ada token custom)

**Interfaces:**
- Produces: kelas utility premium (surface, primary, accent) yang dipakai task berikutnya.

- [ ] **Step 1: Audit token warna dan tipografi yang ada**  
  Cek `index.css` dan konfigurasi Tailwind untuk token yang sudah tersedia.

- [ ] **Step 2: Tambahkan/rapikan token premium**  
  Definisikan: surface hangat, primary biru tua, accent emas lembut, radius kecil-sedang, shadow minimal. Gunakan system serif stack untuk heading dan system monospace untuk timer.

- [ ] **Step 3: Verifikasi build**  
  Run: `npm run build`  
  Expected: PASS

- [ ] **Step 4: Commit**  
  ```bash
  git add cbt/src/index.css cbt/tailwind.config.js
  git commit -m "style(cbt): add premium design tokens"
  ```

### Task 2: Halaman Login

**Files:**
- Modify: `cbt/src/pages/Login.tsx`

**Interfaces:**
- Consumes: token visual dari Task 1.
- Produces: layout Login premium.

- [ ] **Step 1: Hapus klaim palsu/unverifikasi**  
  Hapus blok "Server Ujian Siaga", "100% SIAP", "Koneksi Lokal Tersambung", "Mode Layar Penuh Diperlukan", "ExamPro Safe Browser Engine" atau ganti dengan informasi jujur.

- [ ] **Step 2: Refactor layout premium**  
  Terapkan tipografi elegan, spacing terstruktur, aksen terbatas, form yang bersih.

- [ ] **Step 3: Verifikasi aksesibilitas**  
  Tab order, focus ring, kontras.

- [ ] **Step 4: Build**  
  Run: `npm run build`  
  Expected: PASS

- [ ] **Step 5: Commit**  
  ```bash
  git add cbt/src/pages/Login.tsx
  git commit -m "style(cbt): premium login page"
  ```

### Task 3: Halaman Token Entry

**Files:**
- Modify: `cbt/src/pages/TokenEntry.tsx`

- [ ] **Step 1: Refactor layout premium**  
  Form token yang lebih tenang, tipografi elegan, aksen terbatas.

- [ ] **Step 2: Verifikasi state loading/error**  
  Pastikan error token tetap tampil jelas.

- [ ] **Step 3: Build & commit**

### Task 4: Komponen Navigasi Soal

**Files:**
- Modify: `cbt/src/components/QuestionNavigator.tsx`

- [ ] **Step 1: Refactor navigator premium**  
  Badge status dengan warna semantik (teal lembut untuk terjawab, emas lembut untuk ragu-ragu), spacing konsisten.

- [ ] **Step 2: Verifikasi mobile**  
  Navigator modal di mobile tetap usable.

- [ ] **Step 3: Build & commit**

### Task 5: Halaman Exam Room

**Files:**
- Modify: `cbt/src/pages/ExamRoom.tsx`

- [ ] **Step 1: Refactor header premium**  
  Timer dengan digit tabular, status simpan yang tenang.

- [ ] **Step 2: Refactor kartu soal dan opsi**  
  Tipografi soal elegan, opsi dengan kontras jelas, selected state tegas.

- [ ] **Step 3: Refactor footer dan modal selesai**  
  Tampilan tenang, aksen terbatas.

- [ ] **Step 4: Verifikasi semua state**  
  Fullscreen gate, violation warning, finish modal.

- [ ] **Step 5: Build & commit**

### Task 6: Halaman Finished

**Files:**
- Modify: `cbt/src/pages/Finished.tsx`

- [ ] **Step 1: Refactor layout premium**  
  Tipografi elegan, aksen terbatas, tone warna yang tenang.

- [ ] **Step 2: Build & commit**

### Task 7: Verifikasi Akhir

**Files:**
- All CBT pages.

- [ ] **Step 1: Build frontend**  
  Run: `npm run build`  
  Expected: PASS

- [ ] **Step 2: Jalankan antislop Delivery Gate**  
  Cek kontras, keyboard, state, dan tidak ada klaim palsu.

- [ ] **Step 3: Uji manual**  
  Login, join, jawab, finish, resume.

- [ ] **Step 4: Commit final**  
  ```bash
  git add -A
  git commit -m "style(cbt): complete premium UI refactor"
  ```