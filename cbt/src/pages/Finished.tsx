import { useSearchParams, useNavigate } from 'react-router-dom';

const REASON_MESSAGES: Record<string, { title: string; desc: string; tone: 'ok' | 'warn' | 'danger' }> = {
  manual: {
    title: 'Ujian Selesai',
    desc: 'Jawaban Anda telah berhasil disimpan. Terima kasih.',
    tone: 'ok',
  },
  time_up: {
    title: 'Waktu Habis',
    desc: 'Waktu ujian telah berakhir. Jawaban terakhir Anda sudah tersimpan otomatis.',
    tone: 'warn',
  },
  violation_limit: {
    title: 'Ujian Dihentikan',
    desc: 'Anda melebihi batas pelanggaran yang diizinkan (keluar layar penuh / berpindah tab). Ujian dihentikan otomatis. Hubungi pengawas jika ini keliru.',
    tone: 'danger',
  },
  admin_force: {
    title: 'Ujian Dihentikan Pengawas',
    desc: 'Sesi ujian Anda dihentikan oleh pengawas/admin.',
    tone: 'warn',
  },
};

export default function Finished() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const reason = params.get('reason') ?? 'manual';
  const info = REASON_MESSAGES[reason] ?? REASON_MESSAGES.manual;

  const toneClass =
    info.tone === 'ok' ? 'text-tertiary' : info.tone === 'warn' ? 'text-secondary' : 'text-error';

  const handleBackToLogin = () => {
    localStorage.removeItem('cbt_auth_token');
    localStorage.removeItem('cbt_user_name');
    navigate('/login');
  };

  return (
    <div className="flex h-screen flex-col items-center justify-center gap-4 text-center bg-surface p-6">
      <h1 className={`font-jakarta text-3xl font-bold ${toneClass}`}>{info.title}</h1>
      <p className="max-w-md text-on-surface-variant">{info.desc}</p>
      <p className="text-sm text-outline">Nilai akan diumumkan oleh guru melalui sistem sekolah.</p>
      <button onClick={handleBackToLogin} className="mt-4 rounded-lg bg-primary px-6 py-3 text-on-primary font-semibold">
        Kembali ke Login
      </button>
    </div>
  );
}

{/*
Sengaja TIDAK menampilkan skor di sini — nilai ditampilkan lewat sistem
Laravel utama, guru yang kontrol kapan nilai dipublikasi ke siswa.
*/}
