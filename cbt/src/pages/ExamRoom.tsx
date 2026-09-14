import { useExamGuard } from '../hooks/useExamGuard';
import { useAutosaveAnswer } from '../hooks/useAutosaveAnswer';
import { useExamStore } from '../store/examStore';
import { finishExam } from '../services/examApi';
import { useNavigate } from 'react-router-dom';

export default function ExamRoom() {
  const navigate = useNavigate();
  const { meta, questions, answers, savingStatus, currentIndex, setAnswer, goToQuestion, reset } =
    useExamStore();

  if (!meta) return null; // dijaga oleh route guard di App.tsx

  const { saveAnswer } = useAutosaveAnswer(meta.examSessionId);
  const guard = useExamGuard({
    examSessionId: meta.examSessionId,
    maxViolationCount: meta.maxViolationCount,
    expectedEndAt: meta.expectedEndAt,
    onForceFinish: (reason) => {
      reset();
      navigate(`/finished?reason=${reason}`);
    },
  });

  const currentQuestion = questions[currentIndex];

  const handleSelectOption = (optionKey: string) => {
    const newAnswer =
      currentQuestion.type === 'multiple_choice'
        ? toggleMultiple(answers[currentQuestion.id] ?? [], optionKey)
        : [optionKey];

    setAnswer(currentQuestion.id, newAnswer);
    saveAnswer(currentQuestion.id, newAnswer);
  };

  const handleManualFinish = async () => {
    if (!confirm('Yakin selesaikan ujian sekarang? Jawaban tidak bisa diubah lagi.')) return;
    await finishExam(meta.examSessionId, 'manual');
    reset();
    navigate('/finished?reason=manual');
  };

  if (!guard.isFullscreen) {
    return (
      <div className="flex h-screen items-center justify-center">
        <button onClick={guard.enterFullscreen} className="rounded bg-blue-600 px-6 py-3 text-white">
          Lanjutkan Ujian (Mode Layar Penuh)
        </button>
      </div>
    );
  }

  return (
    <div className="flex h-screen flex-col">
      <header className="flex justify-between border-b p-4">
        <span>{meta.examName}</span>
        <span>
          {Math.floor(guard.remainingSeconds / 60)}:{String(guard.remainingSeconds % 60).padStart(2, '0')}
        </span>
        <span>Pelanggaran: {guard.remainingViolations} tersisa</span>
      </header>

      <main className="flex-1 overflow-y-auto p-6">
        <p className="mb-4 font-medium">
          Soal {currentIndex + 1} dari {questions.length}
        </p>
        <p className="mb-4">{currentQuestion.question_text}</p>

        {currentQuestion.options?.map((opt) => (
          <label key={opt.key} className="mb-2 flex items-center gap-2">
            <input
              type={currentQuestion.type === 'multiple_choice' ? 'checkbox' : 'radio'}
              checked={(answers[currentQuestion.id] ?? []).includes(opt.key)}
              onChange={() => handleSelectOption(opt.key)}
            />
            {opt.text}
          </label>
        ))}

        <p className="mt-2 text-xs text-gray-500">
          {savingStatus[currentQuestion.id] === 'saving' && 'Menyimpan...'}
          {savingStatus[currentQuestion.id] === 'saved' && 'Tersimpan'}
          {savingStatus[currentQuestion.id] === 'error' && 'Gagal menyimpan, akan dicoba lagi'}
        </p>
      </main>

      <footer className="flex justify-between border-t p-4">
        <button disabled={currentIndex === 0} onClick={() => goToQuestion(currentIndex - 1)}>
          Sebelumnya
        </button>
        {currentIndex < questions.length - 1 ? (
          <button onClick={() => goToQuestion(currentIndex + 1)}>Selanjutnya</button>
        ) : (
          <button onClick={handleManualFinish} className="rounded bg-green-600 px-4 py-2 text-white">
            Selesai Ujian
          </button>
        )}
      </footer>

      {guard.showViolationWarning && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
          <div className="rounded bg-white p-6 text-center">
            <p className="mb-4 font-semibold">Pelanggaran terdeteksi: {guard.lastViolationType}</p>
            <button
              onClick={() => { guard.dismissWarning(); guard.enterFullscreen(); }}
              className="rounded bg-red-600 px-4 py-2 text-white"
            >
              Kembali ke Ujian
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

function toggleMultiple(current: string[], key: string): string[] {
  return current.includes(key) ? current.filter((k) => k !== key) : [...current, key];
}
