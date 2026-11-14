import { useEffect, useState } from 'react';
import api from '../services/api';

/**
 * Ambil gambar soal privat sebagai blob lokal.
 *
 * Gambar soal tidak lagi hidup di disk publik; route-nya memakai header
 * `Authorization` (Sanctum), sedangkan `<img src>` tidak bisa mengirim header.
 * Jadi gambarnya diambil lewat fetch lalu dijadikan object URL.
 *
 * `URL.revokeObjectURL` WAJIB dipanggil saat soal berganti. Tanpa itu setiap
 * perpindahan soal menahan satu blob di memori sampai tab ditutup — pada
 * ujian 40 soal itu penumpukan nyata.
 *
 * `src` disimpan bersama url-nya lalu dicocokkan saat render. Kalau hanya
 * disimpan sebagai string, gambar soal sebelumnya akan tetap tampil sesaat
 * selama gambar yang baru diunduh.
 */
export function useQuestionImage(url: string | null | undefined): string | null {
  const [termuat, setTermuat] = useState<{ url: string; src: string } | null>(null);

  useEffect(() => {
    if (!url) return;

    let dibatalkan = false;
    let objectUrl: string | null = null;

    // Backend mungkin mengirim URL absolut (lama, dari route()) atau path
    // relatif (baru). Normalisasi ke path relatif agar axios memakai baseURL
    // yang benar dan cookie ikut terkirim.
    const path = url.startsWith('http') ? new URL(url).pathname.replace(/^\/api\/cbt/, '') : url;

    api
      .get(path, { responseType: 'blob' })
      .then((res) => {
        if (dibatalkan) return;

        objectUrl = URL.createObjectURL(res.data as Blob);
        setTermuat({ url, src: objectUrl });
      })
      .catch(() => {
        // 404 (soal tanpa gambar / berkas hilang) atau 403. Soal tetap tampil
        // teksnya; gambar hanya pelengkap.
        if (!dibatalkan) setTermuat((sebelumnya) => (sebelumnya?.url === url ? null : sebelumnya));
      });

    return () => {
      dibatalkan = true;
      if (objectUrl) URL.revokeObjectURL(objectUrl);
    };
  }, [url]);

  return termuat && termuat.url === url ? termuat.src : null;
}