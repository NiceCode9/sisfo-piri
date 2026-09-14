import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import api from '../services/api';
import { useExamStore } from '../store/examStore';

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

  return (
    <div className="min-h-screen bg-surface flex flex-col items-center justify-center p-6">
      <div className="card-surface w-full max-w-md p-8">
        <h1 className="font-jakarta text-xl font-bold text-on-surface mb-1">Masukkan Token Ujian</h1>
        <p className="text-sm text-on-surface-variant mb-6">Token 6–20 karakter dari pengawas, huruf kapital</p>
        <input
          value={token}
          onChange={(e) => setToken(e.target.value.toUpperCase())}
          className="rounded-lg border border-outline-variant bg-surface-container-lowest px-4 py-3 text-center text-lg tracking-[0.35em] font-mono focus:border-primary focus:ring-2 focus:ring-primary/20 outline-none"
          placeholder="TOKEN"
          maxLength={20}
        />
        {error && <p className="mt-3 text-sm text-error">{error}</p>}
        <button
          onClick={handleJoin}
          disabled={loading || !token}
          className="mt-4 w-full rounded-lg bg-primary px-6 py-3 text-on-primary font-semibold disabled:opacity-50 hover:bg-primary/90"
        >
          {loading ? 'Memverifikasi...' : 'Masuk Ujian'}
        </button>
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
