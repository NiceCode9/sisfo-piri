# Design: Refactor UI CBT Premium

Tanggal: 2026-10-24

## Intent

Menata ulang seluruh halaman CBT (Login, Token Entry, Exam Room, Finished) agar tampil formal dan elegan, dengan nuansa premium: tipografi elegan, aksen warna terbatas, dan komposisi yang tenang namun tegas. Refactor ini hanya visual/UX, tidak mengubah logika bisnis, API, atau state.

## Goals

- Konsistensi visual premium di semua halaman CBT.
- Tipografi yang jelas: heading elegan, body nyaman dibaca, digit timer tabular.
- Palet terbatas: netral hangat + satu aksen biru tua, dengan kontras yang memenuhi WCAG AA.
- Ruang putih sebagai struktur, bukan sisa.
- Semua state (loading, error, empty, disabled) tetap ditangani.
- Aksesibilitas keyboard dan fokus tetap berfungsi.

## Non-Goals

- Tidak mengubah logika ujian, timer, autosave, atau keamanan.
- Tidak menambah fitur baru di luar refactor tampilan.
- Tidak mengubah struktur routing atau store.

## Constraints

- Tetap memakai stack yang ada: React + Tailwind + Vite.
- Tidak menambah dependency baru.
- Semua halaman harus responsif mobile (R-03).
- Harus lulus Delivery Gate antislop (kontras, keyboard, state, dll).

## Arah Desain (keputusan)

- Mode: terang (light) sebagai default.
- Aksen utama: biru tua (primary) + netral hangat (surface) + satu aksen sekunder (emas lembut untuk highlight penting).
- Tipografi: heading dengan serif/semibold elegan; body dengan sans yang bersih; timer monospaced tabular.
- Radius: kecil hingga sedang, konsisten; tidak pill di semua elemen.
- Shadow: minimal, hanya untuk elevation penting.
- Motion: rendah (hover + transisi halus), sesuai MOTION 1-2.
- Rhythm: bervariasi antar section, tetapi tetap tenang.

## Halaman yang Disentuh

1. Login
2. Token Entry
3. Exam Room (header, soal, opsi, footer, modal)
4. Finished

## Keputusan dari Builder Check

1. **Warna status (Terjawab/Ragu-ragu):** dipertahankan sebagai warna semantik, tetapi didesaturasi agar selaras dengan palet premium (jawaban = teal lembut, ragu-ragu = emas lembut). Bukan bagian dari aksen brand utama.
2. **Klaim status/brand di Login:** semua klaim yang tidak terverifikasi ("Server Ujian Siaga", "100% SIAP", "Koneksi Lokal Tersambung", "Mode Layar Penuh Diperlukan", "ExamPro Safe Browser Engine") dihapus. Diganti dengan informasi jujur atau dihilangkan jika tidak ada data.
3. **Tipografi:** gunakan system serif stack untuk heading dan system monospace untuk timer; tidak menambah dependency/font baru.
4. **Sumber kebenaran desain:** spec ini supersede desain lama; komponen existing diadaptasi, bukan dicopy.
5. **Empty states:** pertahankan penanganan state yang ada; tambah empty state baru hanya jika diperlukan untuk kelengkapan R-27.
6. **Dark mode:** light-only, dibenarkan oleh konteks ujian/ruang kelas.

## Yang Diserahkan ke Builder

- Implementasi Tailwind classes dan struktur komponen.
- Penyesuaian token warna di CSS (jika diperlukan).
- Penyesuaian spacing, tipografi, dan hierarki visual.
- Tidak boleh menghapus state handling atau aksesibilitas.

## Kriteria Selesai

- Semua halaman terlihat konsisten dan premium.
- Tidak ada regresi fungsional (login, join, jawab, finish, resume).
- Lulus antislop Delivery Gate.
- Build frontend berhasil.