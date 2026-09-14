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
    <div className="flex h-screen flex-col items-center justify-center gap-4">
      <h1 className="text-xl font-semibold">Masukkan Token Ujian</h1>
      <input
        value={token}
        onChange={(e) => setToken(e.target.value.toUpperCase())}
        className="rounded border px-4 py-2 text-center text-lg tracking-widest"
        placeholder="TOKEN"
        maxLength={20}
      />
      {error && <p className="text-sm text-red-600">{error}</p>}
      <button
        onClick={handleJoin}
        disabled={loading || !token}
        className="rounded bg-blue-600 px-6 py-2 text-white disabled:opacity-50"
      >
        {loading ? 'Memproses...' : 'Masuk Ujian'}
      </button>
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
