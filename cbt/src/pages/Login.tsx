import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import Icon from '../components/Icon';

/** Langkah wajib sebelum ujian, ditampilkan di panel kanan. */
const PETUNJUK = [
  {
    title: 'Daya Perangkat Terpenuhi',
    desc: 'Pastikan baterai laptop terisi penuh atau terhubung pengisi daya listrik selama ujian berlangsung.',
  },
  {
    title: 'Validitas Token Ruang',
    desc: 'Token ujian diperbarui setiap sesi oleh pengawas ruang. Jangan gunakan token sesi sebelumnya.',
  },
  {
    title: 'Integritas Browser Lockdown',
    desc: 'Dilarang membuka tab browser lain, aplikasi perekam, atau berpindah jendela selama sesi ujian.',
  },
];

export default function Login() {
  const [identifier, setIdentifier] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);
    try {
      const res = await api.post('/login', { identifier, password });
      localStorage.setItem('cbt_auth_token', res.data.token);
      localStorage.setItem('cbt_user_name', res.data.user.name);
      navigate('/token');
    } catch (err: any) {
      setError(err.response?.data?.message ?? 'Login gagal. Periksa kembali data Anda.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex min-h-screen flex-col bg-surface">
      <header className="sticky top-0 z-50 border-b border-surface-container-high bg-surface-container-lowest/90 backdrop-blur-md">
        <div className="mx-auto flex h-16 w-full items-center justify-between px-gutter">
          <div className="flex items-center gap-space-sm">
            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary text-on-primary">
              <Icon name="verified" size={20} />
            </span>
            <span className="font-headline-sm text-headline-sm tracking-tight text-primary">Ujian CBT</span>
          </div>

          <div className="flex items-center gap-space-sm rounded-full bg-surface-container-low px-space-sm py-space-xs">
            <span className="relative flex h-2.5 w-2.5">
              <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-tertiary-fixed-dim opacity-75" />
              <span className="relative inline-flex h-2.5 w-2.5 rounded-full bg-tertiary-container" />
            </span>
            <span className="font-label-sm text-label-sm font-medium text-tertiary">Server Ujian Siaga</span>
          </div>
        </div>
      </header>

      <main className="w-full flex-1 px-gutter py-space-xl">
        <div className="mx-auto w-full max-w-6xl">
          <div className="mb-space-xl flex flex-wrap items-center justify-between gap-space-md">
            <div className="flex items-center gap-space-sm">
              <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary text-on-primary shadow-sm">
                <Icon name="verified" size={18} />
              </span>
              <div>
                <p className="font-headline-sm text-headline-sm text-primary">Portal Asesmen Nasional Terstandar</p>
                <p className="font-label-sm text-label-sm text-on-surface-variant">
                  Sistem Computer Based Test (CBT) Mandiri &amp; Kedinasan
                </p>
              </div>
            </div>
            <div className="flex items-center gap-space-sm rounded-full bg-surface-container-lowest px-space-md py-space-xs shadow-sm">
              <span className="h-2.5 w-2.5 animate-pulse rounded-full bg-tertiary-fixed-dim" />
              <span className="font-label-sm text-label-sm font-medium text-on-surface">
                SMKN NGAGLIK
              </span>
              <span className="font-label-sm text-label-sm text-outline-variant">|</span>
              <span className="font-label-sm text-label-sm font-semibold text-primary">Sesi Ujian Online</span>
            </div>
          </div>

          <div className="grid grid-cols-1 items-start gap-space-xl lg:grid-cols-12">
            {/* --- Kartu kredensial --- */}
            <div className="flex flex-col lg:col-span-7">
              <div className="relative rounded-xl bg-surface-container-lowest p-space-xl shadow-xl">
                <div className="mb-space-lg flex items-start gap-space-md">
                  <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary-container/10">
                    <Icon name="shield_person" size={26} className="text-primary-container" />
                  </div>
                  <div>
                    <h1 className="font-headline-md text-headline-md text-on-surface">Masuk Ruang Ujian CBT</h1>
                    <p className="mt-space-xs font-body-sm text-body-sm text-on-surface-variant">
                      Masukkan kredensial yang tercetak pada kartu ujian resmi Anda.
                    </p>
                  </div>
                </div>

                <form onSubmit={handleLogin} className="flex flex-col gap-space-md">
                  <div className="flex flex-col gap-space-xs">
                    <label
                      htmlFor="identifier"
                      className="font-label-md text-label-md text-on-surface"
                    >
                      Nomor Peserta / Username
                    </label>
                    <div className="relative flex items-center">
                      <span className="pointer-events-none absolute left-space-md flex items-center text-on-surface-variant">
                        <Icon name="badge" size={20} />
                      </span>
                      <input
                        id="identifier"
                        value={identifier}
                        onChange={(e) => setIdentifier(e.target.value)}
                        placeholder="Contoh: 2025-04-12940 atau NISN"
                        autoComplete="username"
                        required
                        className="w-full rounded-lg bg-surface-container-low py-3 pl-12 pr-space-md font-body-md text-body-md text-on-surface shadow-sm transition-all duration-150 placeholder:text-outline focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary"
                      />
                    </div>
                    <span className="font-label-sm text-label-sm text-on-surface-variant">
                      Nomor peserta tercetak pada kartu ujian resmi Anda.
                    </span>
                  </div>

                  <div className="flex flex-col gap-space-xs">
                    <label
                      htmlFor="password"
                      className="flex items-center justify-between font-label-md text-label-md text-on-surface"
                    >
                      <span>Kata Sandi / Password</span>
                      <span className="font-label-sm text-label-sm font-normal text-on-surface-variant">
                        Sesuai kartu peserta ujian
                      </span>
                    </label>
                    <div className="relative flex items-center">
                      <span className="pointer-events-none absolute left-space-md flex items-center text-on-surface-variant">
                        <Icon name="lock" size={20} />
                      </span>
                      <input
                        id="password"
                        type={showPassword ? 'text' : 'password'}
                        value={password}
                        onChange={(e) => setPassword(e.target.value)}
                        placeholder="Masukkan kata sandi kartu ujian"
                        autoComplete="current-password"
                        required
                        className="w-full rounded-lg bg-surface-container-low py-3 pl-12 pr-12 font-body-md text-body-md text-on-surface shadow-sm transition-all duration-150 placeholder:text-outline focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary"
                      />
                      <button
                        type="button"
                        onClick={() => setShowPassword((v) => !v)}
                        aria-label={showPassword ? 'Sembunyikan sandi' : 'Tampilkan sandi'}
                        className="absolute right-space-md p-1 text-on-surface-variant transition-colors hover:text-on-surface focus:outline-none"
                      >
                        <Icon name={showPassword ? 'visibility_off' : 'visibility'} size={20} />
                      </button>
                    </div>
                  </div>

                  {error && (
                    <div
                      role="alert"
                      className="flex items-start gap-space-sm rounded-lg bg-error-container p-3"
                    >
                      <Icon name="error" size={20} className="mt-0.5 text-error" />
                      <p className="font-body-sm text-body-sm text-on-error-container">{error}</p>
                    </div>
                  )}

                  <button
                    type="submit"
                    disabled={loading || !identifier || !password}
                    className="mt-space-sm flex w-full items-center justify-center gap-space-sm rounded-lg bg-primary px-space-lg py-3.5 font-headline-sm text-headline-sm text-on-primary shadow-md transition-all duration-200 hover:bg-primary-container hover:shadow-lg active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    {loading ? (
                      <>
                        <Icon name="progress_activity" size={20} className="animate-spin" />
                        <span>Memvalidasi Akses...</span>
                      </>
                    ) : (
                      <>
                        <span>Masuk &amp; Lanjut</span>
                        <Icon name="arrow_forward" size={22} />
                      </>
                    )}
                  </button>

                  <div className="flex items-center justify-between pt-space-xs">
                    <span className="flex items-center gap-1 font-label-sm text-label-sm font-semibold text-primary">
                      <Icon name="help" size={16} />
                      Bantuan Login &amp; Kendala Token
                    </span>
                    <span className="flex items-center gap-1 font-label-sm text-label-sm text-on-surface-variant">
                      <Icon name="menu_book" size={16} />
                      Panduan Peserta Ujian
                    </span>
                  </div>
                </form>
              </div>
            </div>

            {/* --- Panel informasi --- */}
            <div className="flex flex-col gap-space-md lg:col-span-5">
              <div className="rounded-xl bg-surface-container-lowest p-space-lg shadow-md">
                <div className="mb-space-md flex items-center gap-space-sm">
                  <Icon name="info" size={22} className="text-secondary" />
                  <h2 className="font-headline-sm text-headline-sm text-on-surface">
                    Petunjuk Penting Sebelum Ujian
                  </h2>
                </div>
                <ol className="flex flex-col gap-space-md">
                  {PETUNJUK.map((step, i) => (
                    <li key={step.title} className="flex items-start gap-space-sm">
                      <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary-container font-label-sm text-label-sm font-bold text-on-primary">
                        {i + 1}
                      </span>
                      <div>
                        <p className="font-label-md text-label-md text-on-surface">{step.title}</p>
                        <p className="font-body-sm text-body-sm text-on-surface-variant">{step.desc}</p>
                      </div>
                    </li>
                  ))}
                </ol>
              </div>

              <div className="rounded-xl bg-surface-container-lowest p-space-lg shadow-md">
                <div className="mb-space-sm flex items-center justify-between">
                  <span className="font-label-md text-label-md font-semibold text-on-surface">
                    Pemeriksaan Kesiapan Klien
                  </span>
                  <span className="font-label-sm text-label-sm font-bold text-tertiary">100% SIAP</span>
                </div>
                <div className="grid grid-cols-2 gap-space-sm">
                  <div className="flex items-center gap-space-xs rounded-lg bg-surface-container-low p-space-sm">
                    <Icon name="wifi" size={18} className="text-tertiary" />
                    <div className="min-w-0">
                      <p className="truncate font-label-sm text-label-sm font-medium text-on-surface">
                        Koneksi Lokal
                      </p>
                      <p className="font-label-sm text-label-sm text-outline-variant">Tersambung</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-space-xs rounded-lg bg-surface-container-low p-space-sm">
                    <Icon name="lock" size={18} className="text-tertiary" />
                    <div className="min-w-0">
                      <p className="truncate font-label-sm text-label-sm font-medium text-on-surface">
                        Mode Layar Penuh
                      </p>
                      <p className="font-label-sm text-label-sm text-outline-variant">Diperlukan</p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex items-center justify-between rounded-xl bg-surface-container-high p-space-md">
                <div className="flex items-center gap-space-sm">
                  <Icon name="support_agent" size={20} className="text-primary" />
                  <span className="font-body-sm text-body-sm font-medium text-on-surface">
                    Hubungi Proctor Ruangan
                  </span>
                </div>
                <span className="font-label-sm text-label-sm font-bold text-primary">Tersedia</span>
              </div>
            </div>
          </div>
        </div>
      </main>

      <footer className="w-full bg-surface-container-low py-space-md">
        <div className="flex w-full flex-col items-center justify-between gap-space-xs px-gutter md:flex-row">
          <div className="flex items-center gap-space-xs">
            <Icon name="verified_user" size={16} className="text-outline" />
            <p className="font-label-sm text-label-sm text-on-surface-variant">
              &copy; SMKN Ngaglik — Portal Ujian CBT. Hak Cipta Dilindungi.
            </p>
          </div>
          <div className="flex items-center gap-space-md">
            <span className="font-label-sm text-label-sm text-outline">ExamPro Safe Browser Engine</span>
          </div>
        </div>
      </footer>
    </div>
  );
}