import { useState } from 'react';
import { useSearchParams, useNavigate } from 'react-router-dom';
import { logout } from '../services/api';
import Icon from '../components/Icon';

const REASON_MESSAGES: Record<
  string,
  { title: string; desc: string; tone: 'ok' | 'warn' | 'danger' }
> = {
  manual: {
    title: 'Ujian Selesai',
    desc: 'Jawaban Anda telah berhasil disimpan dan dikirim ke server. Terima kasih telah mengerjakan ujian dengan jujur.',
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

/** Ikon + warna per tone, dipakai kartu utama maupun ringkasan. */
const TONE = {
  ok: {
    icon: 'check_circle',
    chipBg: 'bg-tertiary-fixed/30',
    chipText: 'text-tertiary',
    chipBorder: 'border-tertiary-fixed',
    title: 'text-tertiary',
    panel: 'bg-tertiary-fixed/30',
  },
  warn: {
    icon: 'schedule',
    chipBg: 'bg-secondary-fixed/50',
    chipText: 'text-on-secondary-fixed',
    chipBorder: 'border-secondary-fixed-dim',
    title: 'text-secondary',
    panel: 'bg-secondary-fixed/50',
  },
  danger: {
    icon: 'gpp_bad',
    chipBg: 'bg-error-container',
    chipText: 'text-error',
    chipBorder: 'border-error',
    title: 'text-error',
    panel: 'bg-error-container',
  },
} as const;

export default function Finished() {
  const [params] = useSearchParams();
  const navigate = useNavigate();
  const [signingOut, setSigningOut] = useState(false);
  const reason = params.get('reason') ?? 'manual';
  const info = REASON_MESSAGES[reason] ?? REASON_MESSAGES.manual;
  const tone = TONE[info.tone];

  const handleBackToLogin = async () => {
    setSigningOut(true);
    // Memanggil logout() membatalkan token di server; localStorage baru
    // dibersihkan setelahnya (atau meski gagal, lihat services/api.ts).
    await logout();
    navigate('/login');
  };

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-surface px-gutter py-space-xl">
      <div className="w-full max-w-md rounded-2xl bg-surface-container-lowest p-space-lg shadow-xl">
        <div className="flex flex-col items-center gap-space-md text-center">
          <div className={`flex h-16 w-16 items-center justify-center rounded-full ${tone.chipBg} ${tone.chipText}`}>
            <Icon name={tone.icon} size={34} />
          </div>

          <div className="flex flex-col gap-space-xs">
            <h1 className={`font-headline-md text-headline-md ${tone.title}`}>{info.title}</h1>
            <p className="font-body-sm text-body-sm text-on-surface-variant">{info.desc}</p>
          </div>

          <div className={`flex w-full items-start gap-space-sm rounded-xl p-space-md text-left ${tone.panel}`}>
            <Icon name="info" size={20} className="mt-0.5 shrink-0 text-primary" />
            <p className="font-body-sm text-body-sm text-on-surface">
              Nilai akan diumumkan oleh guru melalui sistem sekolah. Sesi ini sudah tercatat sebagai selesai
              dan tidak dapat diulang.
            </p>
          </div>

          <button
            onClick={handleBackToLogin}
            disabled={signingOut}
            className="flex w-full items-center justify-center gap-space-sm rounded-lg bg-primary px-space-lg py-3.5 font-headline-sm text-headline-sm text-on-primary shadow-md transition-all hover:bg-primary-container active:scale-[0.99] disabled:opacity-60"
          >
            {signingOut ? (
              <>
                <Icon name="progress_activity" size={20} className="animate-spin" />
                <span>Keluar...</span>
              </>
            ) : (
              <>
                <span>Kembali ke Login</span>
                <Icon name="arrow_forward" size={22} />
              </>
            )}
          </button>
        </div>
      </div>

      <p className="mt-space-lg flex items-center gap-1.5 font-label-sm text-label-sm text-outline">
        <Icon name="verified_user" size={16} />
        Sesi Terenkripsi &middot; SMKN Ngaglik
      </p>
    </div>
  );
}

/*
Sengaja TIDAK menampilkan skor di sini — nilai ditampilkan lewat sistem
Laravel utama, guru yang kontrol kapan nilai dipublikasi ke siswa.
*/