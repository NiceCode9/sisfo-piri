# CBT — Ujian Elektronik

SPA React untuk siswa. Menyambung ke API Laravel di `backend/routes/api_cbt.php`
(prefix `api/cbt`, Sanctum bearer token).

## Menjalankan

```bash
cp .env.example .env      # VITE_API_BASE_URL=https://admin.sekolahmu.sch.id/api/cbt
npm install
npm run dev               # http://127.0.0.1:5173
```

Tanpa `.env`, `VITE_API_BASE_URL` kosong sehingga axios memakai path relatif
(`/exam/active`) yang tidak melewati proxy Vite `/api`. Untuk dev lokal, arahkan
`baseURL` ke `http://127.0.0.1:8000/api/cbt`.

## Perintah

| Perintah | Guna |
|---|---|
| `npm run dev` | dev server + HMR |
| `npm run build` | build produksi ke `dist/` |
| `npm run preview` | hasil build di atas server statis |
| `npm run lint` | oxlint (termasuk `react/rules-of-hooks`) |
| `npm run typecheck` | `tsc --noEmit` |
| `npm test` | vitest |

## Struktur

```
src/
  pages/       Login, TokenEntry, ExamRoom, Finished
  hooks/       useExamGuard (proctoring + timer + heartbeat), useAutosaveAnswer
  services/    api.ts (axios + logout), examApi.ts (endpoint + antrean pelanggaran)
  store/       examStore.ts (zustand, memory-only)
  components/  QuestionNavigator (sidebar desktop + panel mobile)
```

## Catatan perilaku

- **Token ujian tidak pernah disimpan** di client. Setelah login, resume sesi
  selalu lewat `GET /exam/active`.
- **Skor tidak pernah tampil di SPA ini.** Nilai dibaca guru lewat panel admin;
  `POST /exam/finish` sengaja tidak mengembalikan skor server agar tidak
  menjadi kunci jawaban.
- **Proctoring murni client-side** (fullscreen, blur, copy/paste). Ia hanya
  alat bantu — penghitung pelanggaran yang sah adalah `violation_count` dari
  server. Lihat `backend/app/Http/Controllers/Api/Cbt/ViolationController.php`.
- Store bersifat *memory-only*: refresh menghapus state, pemulihan lewat
  `/exam/active`.

Bug yang sudah ditemukan dan diperbaiki: `docs/BUG_CBT.md`.