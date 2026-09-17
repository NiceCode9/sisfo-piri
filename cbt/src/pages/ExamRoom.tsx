import { useExamGuard } from '../hooks/useExamGuard';
import { useAutosaveAnswer } from '../hooks/useAutosaveAnswer';
import { useExamStore } from '../store/examStore';
import { finishExam } from '../services/examApi';
import { useNavigate } from 'react-router-dom';

export default function ExamRoom() {
  const navigate = useNavigate();
  const { meta, questions, answers, savingStatus, currentIndex, setAnswer, goToQuestion, reset } =
    useExamStore();

  if (!meta) return null;

  const { saveAnswer, flush } = useAutosaveAnswer(meta.examSessionId);
  const guard = useExamGuard({
    examSessionId: meta.examSessionId,
    maxViolationCount: meta.maxViolationCount,
    expectedEndAt: meta.expectedEndAt,
    initialViolationCount: meta.violationCount ?? 0,
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

  const handleEssayChange = (text: string) => {
    const newAnswer = [text];
    setAnswer(currentQuestion.id, newAnswer);
    saveAnswer(currentQuestion.id, newAnswer);
  };

  const handleManualFinish = async () => {
    if (!confirm('Yakin selesaikan ujian sekarang? Jawaban tidak bisa diubah lagi.')) return;
    await flush();
    try {
      await finishExam(meta.examSessionId, 'manual');
    } catch {
      // retry di examApi sudah 2x; bila masih gagal, server akan finalisasi via grace period
    }
    reset();
    navigate('/finished?reason=manual');
  };

  if (!guard.isFullscreen) {
    return (
      <div className="flex h-screen items-center justify-center bg-surface">
        <button onClick={guard.enterFullscreen} className="rounded-xl bg-primary px-8 py-4 text-on-primary font-semibold shadow-lg">
          Lanjutkan Ujian (Mode Layar Penuh)
        </button>
      </div>
    );
  }

  const timerClass =
    guard.remainingSeconds < 120
      ? 'bg-error text-on-error animate-pulse font-inter tabular-nums'
      : guard.remainingSeconds < 600
        ? 'bg-secondary-fixed text-on-secondary-fixed font-inter tabular-nums'
        : 'bg-surface-container text-on-surface font-inter tabular-nums';

  return (
    <div className="flex h-screen flex-col bg-surface">
      <header className="flex items-center justify-between border-b border-outline-variant bg-surface-container-lowest px-6 py-3">
        <span className="font-jakarta font-semibold text-on-surface">{meta.examName}</span>
        <span className={`rounded-full px-4 py-1 text-sm font-bold ${timerClass}`}>
          {Math.floor(guard.remainingSeconds / 60)}:{String(guard.remainingSeconds % 60).padStart(2, '0')}
        </span>
        <span className="text-sm text-on-surface-variant">Pelanggaran: {guard.remainingViolations} tersisa</span>
      </header>

      <main className="flex-1 overflow-y-auto p-6 max-w-[1680px] mx-auto w-full">
        <p className="mb-4 font-inter text-sm text-on-surface-variant">
          Soal {currentIndex + 1} dari {questions.length}
        </p>
        <p className="mb-4 font-jakarta text-lg font-medium text-on-surface">{currentQuestion.question_text}</p>
        {currentQuestion.question_image && (
          <img src={currentQuestion.question_image} alt="Gambar soal" className="mb-4 max-h-72 rounded-lg border border-outline-variant" />
        )}

        {currentQuestion.type === 'essay' ? (
          <textarea
            value={(answers[currentQuestion.id] ?? [''])[0] ?? ''}
            onChange={(e) => handleEssayChange(e.target.value)}
            rows={6}
            placeholder="Tulis jawaban uraian di sini..."
            className="w-full rounded-lg border border-outline-variant bg-surface-container-lowest p-3 font-inter text-sm focus:border-primary focus:outline-none"
          />
        ) : (
          currentQuestion.options?.map((opt) => {
            const checked = (answers[currentQuestion.id] ?? []).includes(opt.key);
            return (
              <label
                key={opt.key}
                className={`mb-3 flex items-center gap-3 rounded-lg border p-3.5 cursor-pointer ${checked ? 'bg-primary-fixed/30 border-primary' : 'bg-surface-container-lowest border-outline-variant hover:bg-surface-container-low'}`}
              >
                <input
                  type={currentQuestion.type === 'multiple_choice' ? 'checkbox' : 'radio'}
                  checked={checked}
                  onChange={() => handleSelectOption(opt.key)}
                  className="accent-primary"
                />
                <span className={`flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ${checked ? 'bg-primary text-on-primary' : 'bg-surface-container text-on-surface-variant'}`}>{opt.key}</span>
                {opt.text}
              </label>
            );
          })
        )}

        <p className="mt-2 text-xs text-on-surface-variant">
          {savingStatus[currentQuestion.id] === 'saving' && 'Menyimpan...'}
          {savingStatus[currentQuestion.id] === 'saved' && 'Tersimpan ✓'}
          {savingStatus[currentQuestion.id] === 'error' && 'Gagal menyimpan, akan dicoba lagi'}
        </p>
      </main>

      <footer className="flex justify-between border-t border-outline-variant bg-surface-container-lowest p-4">
        <button
          disabled={currentIndex === 0}
          onClick={() => goToQuestion(currentIndex - 1)}
          className="rounded-lg border border-outline-variant px-4 py-2 text-sm disabled:opacity-50"
        >
          Sebelumnya
        </button>
        {currentIndex < questions.length - 1 ? (
          <button onClick={() => goToQuestion(currentIndex + 1)} className="rounded-lg bg-primary px-4 py-2 text-sm text-on-primary">Selanjutnya</button>
        ) : (
          <button onClick={handleManualFinish} className="rounded-lg bg-tertiary px-4 py-2 text-sm font-semibold text-on-tertiary">
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
