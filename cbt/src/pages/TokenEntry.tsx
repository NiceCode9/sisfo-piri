import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import Icon from '../components/Icon';
import { useExamStore } from '../store/examStore';

const MAX_TOKEN_LENGTH = 20;

export default function TokenEntry() {
  const [token, setToken] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const setExamData = useExamStore((s) => s.setExamData);

  const handleJoin = async () => {
    setError(null);
    setLoading(true);
    try {
      const joinRes = await api.post('/exam/join', { token });
      const { exam_session_id, exam, started_at, expected_end_at } = joinRes.data;

      const questionsRes = await api.get('/exam/questions', {
        params: { exam_session_id },
      });

      setExamData(
        {
          examSessionId: exam_session_id,
          examId: exam.id,
          examName: exam.name,
          maxViolationCount: exam.max_violation_count,
          startedAt: started_at,
          expectedEndAt: expected_end_at,
        },
        questionsRes.data.questions,
        questionsRes.data.saved_answers ?? {}
      );

      navigate('/exam');
    } catch (err: any) {
      setError(err.response?.data?.message ?? 'Token tidak valid.');
    } finally {
      setLoading(false);
    }
  };

  const studentName = localStorage.getItem('cbt_user_name');

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-surface px-gutter py-space-xl">
      <div className="w-full max-w-md rounded-xl bg-surface-container-lowest p-space-xl shadow-xl">
        <div className="mb-space-lg flex items-start gap-space-md">
          <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-secondary-container/20">
            <Icon name="vpn_key" size={26} className="text-on-secondary-container" />
          </div>
          <div>
            <h1 className="font-headline-md text-headline-md text-on-surface">Masukkan Token Ujian</h1>
            <p className="mt-space-xs font-body-sm text-body-sm text-on-surface-variant">
              Token diberikan langsung oleh pengawas ruang, hanya berlaku untuk sesi ini.
            </p>
          </div>
        </div>

        {studentName && (
          <div className="mb-space-md flex items-center justify-between rounded-lg bg-surface-container-low px-space-sm py-space-xs">
            <span className="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
              <Icon name="person" size={16} />
              Peserta
            </span>
            <span className="truncate font-label-md text-label-md font-semibold text-on-surface">
              {studentName}
            </span>
          </div>
        )}

        <div className="flex flex-col gap-space-xs">
          <div className="flex items-center justify-between">
            <label htmlFor="token" className="flex items-center gap-space-xs font-label-md text-label-md text-on-surface">
              <span>Token Ujian</span>
              <span className="inline-flex items-center rounded bg-secondary-container/20 px-2 py-0.5 font-label-sm text-label-sm font-semibold text-on-secondary-container">
                Diberikan Pengawas
              </span>
            </label>
            <span
              className={`font-label-sm text-label-sm ${
                token.length > 0 ? 'font-semibold text-tertiary' : 'text-outline'
              }`}
            >
              {token.length}/{MAX_TOKEN_LENGTH} Karakter
            </span>
          </div>

          <div className="relative flex items-center">
            <span className="pointer-events-none absolute left-space-md flex items-center text-on-surface-variant">
              <Icon name="vpn_key" size={20} />
            </span>
            <input
              id="token"
              value={token}
              onChange={(e) => setToken(e.target.value.toUpperCase())}
              className="w-full rounded-lg bg-surface-container-low py-3 pl-12 pr-space-md font-inter text-lg font-bold uppercase tracking-[0.35em] text-on-surface shadow-sm transition-all duration-150 placeholder:font-normal placeholder:tracking-[0.35em] placeholder:text-outline focus:bg-surface-container-lowest focus:outline-none focus:ring-2 focus:ring-primary"
              placeholder="TOKEN"
              maxLength={MAX_TOKEN_LENGTH}
              autoComplete="off"
              autoCapitalize="characters"
              spellCheck={false}
              onKeyDown={(e) => {
                if (e.key === 'Enter') handleJoin();
              }}
            />
          </div>
          <span className="font-label-sm text-label-sm text-on-surface-variant">
            Token 6&ndash;20 karakter, huruf kapital, dari papan tulis atau pengawas ujian.
          </span>
        </div>

        {error && (
          <div role="alert" className="mt-space-md flex items-start gap-space-sm rounded-lg bg-error-container p-3">
            <Icon name="error" size={20} className="mt-0.5 text-error" />
            <p className="font-body-sm text-body-sm text-on-error-container">{error}</p>
          </div>
        )}

        <button
          onClick={handleJoin}
          disabled={loading || !token}
          className="mt-space-lg flex w-full items-center justify-center gap-space-sm rounded-lg bg-primary px-space-lg py-3.5 font-headline-sm text-headline-sm text-on-primary shadow-md transition-all duration-200 hover:bg-primary-container active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-50"
        >
          {loading ? (
            <>
              <Icon name="progress_activity" size={20} className="animate-spin" />
              <span>Memverifikasi Token...</span>
            </>
          ) : (
            <>
              <span>Masuk Ruangan Ujian</span>
              <Icon name="arrow_forward" size={22} />
            </>
          )}
        </button>

        <div className="mt-space-md flex items-center justify-between border-t border-surface-container-high pt-space-md">
          <span className="flex items-center gap-space-xs font-label-sm text-label-sm text-on-surface-variant">
            <Icon name="help" size={16} />
            Token ditolak?
          </span>
          <span className="flex items-center gap-1 font-label-sm text-label-sm font-semibold text-primary">
            Tanya pengawas ruang
            <Icon name="arrow_forward" size={14} />
          </span>
        </div>
      </div>
    </div>
  );
}

/*
CATATAN RESUME SESSION:
Kalau siswa refresh browser di tengah ujian, alur normal (App.tsx) akan cek
endpoint GET /exam/active terlebih dahulu sebelum menampilkan halaman ini —
kalau ada sesi ongoing, siswa langsung diarahkan ke ExamRoom tanpa perlu
input token lagi. Ini LEBIH AMAN daripada menyimpan token di sessionStorage,
karena token ujian tidak pernah tersimpan di client sama sekali.
*/